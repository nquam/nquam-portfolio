<?php
/**
 * Provides a very simple way to resize an image.
 *
 * Credits to Jarrod Oberto.
 * Jarrod wrote a tutorial on NetTuts.
 * http://net.tutsplus.com/tutorials/php/image-resizing-made-easy-with-php/
 *
 * I only turned it into a Laravel bundle.
 *
 * @package Resizer
 * @version 1.1
 * @author Maikel D (original author Jarrod Oberto)
 * @author Nathan Quam <nquam@redacted.com>
 * @link
 * @example
 * 		Resizer::open( mixed $file )
 *			->resize( int $width , int $height , string 'exact, portrait, landscape, auto or crop' )
 *			->save( string 'path/to/file.jpg' , int $quality );
 *
 *		// Resize and save an image.
 * 		Resizer::open( Input::file('field_name') )
 *			->resize( 800 , 600 , 'crop' )
 *			->save( 'path/to/file.jpg' , 100 );
 *
 *		// Recompress an image.
 *		Resizer::open( 'path/to/image.jpg' )
 *			->save( 'path/to/new_image.jpg' , 60 );
 */
class Resizer
{
	/**
	 * Store the image resource which we'll modify.
	 *
	 * @var Resource
	 */
	private $image;

	/**
	 * Original width of the image we're modifying.
	 *
	 * @var int
	 */
	private $width;

	/**
	 * Original height of the image we're modifying.
	 *
	 * @var int
	 */
	private $height;

	/**
	 * Store the resource of the resized image.
	 *
	 * @var Resource
	 */
	private $image_resized;

	/**
	 * Instantiates the Resizer and receives the path to an image we're working with.
	 *
	 * @param mixed $file The file array provided by Laravel's Input::file('field_name') or a path to a file
	 * @return void
	 */
	function __construct($file)
	{
		// Open up the file.
		$this->image = $this->open_image($file);
		if (!$this->image)
		{
			die('File not recognised. Possibly because the path is wrong. Keep in mind, paths are relative to the main index.php file. Try including $_SERVER["DOCUMENT_ROOT"] before the path. It might be that your server is not using the correct path for your config');
		}

		// Get width and height of our image.
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	/**
	 * Static call, Laravel style.
	 * Returns a new Resizer object, allowing for chainable calls.
	 *
	 * @param  mixed $file The file array provided by Laravel's Input::file('field_name') or a path to a file
	 * @return Resizer
	 */
	public static function open($file)
	{
		return new Resizer($file);
	}

	/**
	 * Plug in call to build cropper tool within the page. Call unlimited times on images within the same form.
	 * Example: {{ Resizer::open($content->image_1)->build_cropper( 'animals', 'image_1', $content->image_1) }}
	 *
	 * @param string $ref_model
	 * @param string $field_name
	 * @param string $image_name
	 * @param integer|null $module_installation_id
	 * @return boolean|View
	 */
	public function build_cropper($ref_model, $field_name, $image_name, $module_installation_id = null, $field_name_in_db = null)
	{
		$field_name_in_db = is_null($field_name_in_db) ? $field_name : $field_name_in_db;

		$image_name_parts = pathinfo($image_name);
		$data['image_name'] = $image_name_parts['basename'];
		$data['image_sizes'] = $this->get_image_sizes($ref_model, $field_name_in_db, $module_installation_id);
		$data['image_field'] = $field_name;
		if ($data['image_sizes'])
		{
			return View::make('admin.crop_tool.plugin', $data);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Called to auto create crops based on db list for the field
	 *
	 * @param string $ref_model
	 * @param string $field_name
	 * @param string $image_name
	 * @param integer|null $module_installation_id
	 * @return boolean
	 */
	public function crop_on_upload($ref_model, $field_name, $image_name, $module_installation_id = null, $field_name_in_db = null)
	{
		$field_name_in_db = is_null($field_name_in_db) ? $field_name : $field_name_in_db;

		$image_sizes = $this->get_image_sizes($ref_model, $field_name_in_db, $module_installation_id);
		$image_name_parts = pathinfo($image_name);
		$image_name = $image_name_parts['basename'];

		if ($image_sizes)
		{
			foreach ($image_sizes as $size)
			{
				$destination_folder = path('public').'uploads'.DS.'images'.DS.'cropped'.DS.$size['w'].'_'.$size['h'];
				File::mkdir($destination_folder, 0777);
				$this->resize($size['w'], $size['h'], 'crop')->save($destination_folder.DS.$image_name, 100);
			}
		}

		return true;
	}

	/**
	 * Loop through sizes and remove all old images plus original when being replaced
	 *
	 * @param string $ref_model
	 * @param string $field_name
	 * @param string $image_name
	 * @param integer|null $module_installation_id
	 * @return boolean
	 */
	public function remove_old($ref_model, $field_name, $image_name, $module_installation_id = null)
	{
		$image_sizes = $this->get_image_sizes($ref_model, $field_name, $module_installation_id);
		$image_name_parts = pathinfo($image_name);
		$image_name = $image_name_parts['basename'];

		$this->delete_file(path('public').'uploads'.DS.'images'.DS.'original'.DS, $image_name);
		if ($image_sizes)
		{
			foreach ($image_sizes as $size)
			{
				$destination_folder = path('public').'uploads'.DS.'images'.DS.'cropped'.DS.$size['w'].'_'.$size['h'];
				$this->delete_file($destination_folder, $image_name);
			}
		}

		return TRUE;
	}

	/**
	 * Check if exists then delete the file
	 *
	 * @param string $destination_folder
	 * @param string $image_name
	 * @return void
	 */
	public function delete_file($destination_folder, $image_name)
	{
		if (File::exists($destination_folder.'/'.$image_name))
		{
			File::delete($destination_folder.'/'.$image_name);
		}
	}

	/**
	 * Returns image size array or false based on section name and field name
	 * Example: array('title' => array('width','height'))
	 *
	 * @param string $ref_model
	 * @param string $field_name
	 * @param integer|null $module_installation_id
	 * @return boolean|string[]
	 */
	public function get_image_sizes($ref_model, $field_name, $module_installation_id = null)
	{
		if (is_null($module_installation_id))
		{
			$sizes = DB::table('image_sizes')
				->where('ref_model', '=', $ref_model)
				->where('field_name', '=', $field_name)
				->get(array('title', 'width', 'height'));
		}
		else
		{
			$sizes = DB::table('image_sizes')
				->where('ref_model', '=', $ref_model)
				->where('field_name', '=', $field_name)
				->where('module_installation_id', '=', $module_installation_id)
				->get(array('title', 'width', 'height'));
		}

		if (count($sizes) > 0)
		{
			$reformat = array();
			foreach ($sizes as $size)
			{
				$reformat[$size->title] = array('w' => $size->width, 'h' => $size->height);
			}
			return $reformat;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Resizes and/or crops an image.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @param  string $option     Either exact, portrait, landscape, auto or crop.
	 * @return [type]
	 */
	public function resize($new_width, $new_height, $option = 'auto')
	{
		// Get optimal width and height - based on $option.
		$option_array = $this->get_dimensions($new_width, $new_height, $option);

		$optimal_width = $option_array['optimal_width'];
		$optimal_height = $option_array['optimal_height'];

		// Resample - create image canvas of x, y size.
		$this->image_resized = imagecreatetruecolor($optimal_width, $optimal_height);
		$image_background = imagecreatetruecolor($this->width, $this->height);

		// Retain transparency for PNG and GIF files.
		$background_colour = imagecolorallocate(
			$image_background,
			Config::get('resizer::defaults.background_color.r'),
			Config::get('resizer::defaults.background_color.g'),
			Config::get('resizer::defaults.background_color.b')
		);


		// MAKE TRANSPARENT
		imagealphablending($this->image_resized, false);
		imagealphablending($image_background, false);
		imagesavealpha($image_background,true);
		imagesavealpha($this->image_resized,true);
		imagefilledrectangle($image_background, 0, 0, $this->width, $this->height, $background_colour);
		imagefilledrectangle($this->image_resized, 0, 0, $this->width, $this->height, $background_colour);
		// END MAKE TRANSPARENT

		imagecopy($image_background, $this->image, 0, 0, 0, 0, $this->width, $this->height);
		// imagecolortransparent( $this->image_resized , imagecolorallocatealpha( $this->image_resized , 255 , 255 , 255 , 127 ) );
		// imagealphablending( $this->image_resized , false );
		// imagesavealpha( $this->image_resized , true );

		// convert transparency to white when converting from PNG to JPG.
		// PNG to PNG should retain transparency as per normal.
		// imagefill($this->image_resized, 0, 0, IMG_COLOR_TRANSPARENT);

		// Create the new image.
		imagecopyresampled($this->image_resized, $image_background, 0, 0, 0, 0, $optimal_width, $optimal_height, $this->width, $this->height);

		// if option is 'crop' or 'fit', then crop too.
		if ($option == 'crop' || $option == 'fit')
		{
			$this->crop($optimal_width, $optimal_height, $new_width, $new_height);
		}

		// Return $this to allow calls to be chained.
		return $this;
	}

	/**
	 * Resizes and/or crops an image.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @param  string $option     Either exact, portrait, landscape, auto or crop.
	 * @return [type]
	 */
	public function crop_resize($x, $y, $old_width, $old_height, $new_width, $new_height, $option = 'auto')
	{
		// Get optimal width and height - based on $option.
		$option_array = $this->get_dimensions($old_width, $old_height, $option);

		$optimal_width	= $option_array['optimal_width'];
		$optimal_height	= $option_array['optimal_height'];

		// Resample - create image canvas of x, y size.
		$this->image_resized = imagecreatetruecolor($new_width, $new_height);
		$image_background = imagecreatetruecolor($this->width, $this->height);
		$image_cropped = imagecreatetruecolor($this->width, $this->height);

		// Retain transparency for PNG and GIF files.
		$background_colour = imagecolorallocate(
			$image_background,
			Config::get('resizer::defaults.background_color.r'),
			Config::get('resizer::defaults.background_color.g'),
			Config::get('resizer::defaults.background_color.b')
		);

		// MAKE TRANSPARENT
		imagealphablending($this->image_resized, false);
		imagealphablending($image_cropped, false);
		imagealphablending($image_background, false);
		imagesavealpha($image_background,true);
		imagesavealpha($image_cropped,true);
		imagesavealpha($this->image_resized,true);
		imagefilledrectangle($image_background, 0, 0, $this->width, $this->height, $background_colour);
		imagefilledrectangle($image_cropped, 0, 0, $this->width, $this->height, $background_colour);
		imagefilledrectangle($this->image_resized, 0, 0, $this->width, $this->height, $background_colour);
		// END MAKE TRANSPARENT

		imagecopy($image_background, $this->image, 0, 0, 0, 0, $this->width, $this->height);

		// Create the new image cropped.
		imagecopyresampled($image_cropped, $image_background, 0, 0, $x, $y, $this->width, $this->height, $this->width, $this->height);

		// resize the image
		if (max($this->height, $this->width) > max($new_width, $new_height))
		{
			imagecopyresampled($this->image_resized, $image_cropped, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
		}

		// Return $this to allow calls to be chained.
		return $this;
	}

	/**
	 * Save the image based on its file type.
	 *
	 * @param  string $save_path     Where to save the image
	 * @param  int    $image_quality The output quality of the image
	 * @return boolean
	 */
	public function save($save_path, $image_quality = 95)
	{
		// If the image wasn't resized, fetch original image.
		if (!$this->image_resized)
		{
			$this->image_resized = $this->image;
		}
		// Get extension of the output file.
		$extension = strtolower(File::extension($save_path));

		// Create and save an image based on it's extension.
		switch($extension)
		{
			case 'jpg':
			case 'jpeg':
				if (imagetypes() & IMG_JPG)
				{
					imagejpeg($this->image_resized, $save_path, $image_quality);
				}
				break;

			case 'gif':
				if (imagetypes() & IMG_GIF)
				{
					imagegif($this->image_resized , $save_path);
				}
				break;

			case 'png':
				// Scale quality from 0-100 to 0-9.
				$scale_quality = round(($image_quality/100) * 9);

				// Invert quality setting as 0 is best, not 9.
				$invert_scale_quality = 9 - $scale_quality;

				if (imagetypes() & IMG_PNG)
				{
					imagepng($this->image_resized, $save_path, $invert_scale_quality);
				}
				break;

			default:
				return false;
				break;
		}

		// Remove the resource for the resized image.
		imagedestroy($this->image_resized);

		return true;
	}

	/**
	 * Open a file, detect its mime-type and create an image resrource from it.
	 *
	 * @param  array $file Attributes of file from the $_FILES array
	 * @return mixed
	 */
	private function open_image($file)
	{
		// If $file isn't an array, we'll turn it into one.
		if (!is_array($file))
		{
			$file = array(
				'type'		=> File::mime(strtolower(File::extension($file))),
				'tmp_name'	=> $file
			);
		}

		$mime = $file['type'];
		$file_path = $file['tmp_name'];
		if (strpos($file_path, path('public')) === false)
		{
			$file_path = path('public').$file_path;
		}
		ini_set('memory_limit', '256M');
		switch ($mime)
		{
			case 'image/pjpeg': // IE6
			case File::mime('jpg'):
				$img = @imagecreatefromjpeg($file_path);
				break;
			case File::mime('gif'):
				$img = @imagecreatefromgif($file_path);
				break;
			case File::mime('png'):
				$img = @imagecreatefrompng($file_path);
				break;
			default:
				$img = false;
				break;
		}

		return $img;
	}

	/**
	 * Return the image dimentions based on the option that was chosen.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @param  string $option     Either exact, portrait, landscape, auto or crop.
	 * @return array
	 */
	private function get_dimensions($new_width, $new_height, $option)
	{
		switch ($option)
		{
			case 'exact':
				$optimal_width = $new_width;
				$optimal_height = $new_height;
				break;
			case 'portrait':
				$optimal_width = $this->get_size_by_fixed_height($new_height);
				$optimal_height = $new_height;
				break;
			case 'landscape':
				$optimal_width = $new_width;
				$optimal_height = $this->get_size_by_fixed_width($new_width);
				break;
			case 'auto':
				$option_array = $this->get_size_by_auto($new_width, $new_height);
				$optimal_width = $option_array['optimal_width'];
				$optimal_height = $option_array['optimal_height'];
				break;
			case 'fit':
				$option_array = $this->get_size_by_fit($new_width, $new_height);
				$optimal_width = $option_array['optimal_width'];
				$optimal_height = $option_array['optimal_height'];
				break;
			case 'crop':
				$option_array = $this->get_optimal_crop($new_width, $new_height);
				$optimal_width = $option_array['optimal_width'];
				$optimal_height = $option_array['optimal_height'];
				break;
			case 'manual_crop':
				$optimal_width = $new_width;
				$optimal_height = $new_height;
				break;
		}

		return array(
			'optimal_width'		=> $optimal_width,
			'optimal_height'	=> $optimal_height
		);
	}

	/**
	 * Returns the width based on the image height.
	 *
	 * @param  int    $new_height The height of the image
	 * @return int
	 */
	private function get_size_by_fixed_height($new_height)
	{
		$ratio = $this->width / $this->height;
		$new_width = $new_height * $ratio;

		return $new_width;
	}

	/**
	 * Returns the height based on the image width.
	 *
	 * @param  int    $new_width The width of the image
	 * @return int
	 */
	private function get_size_by_fixed_width($new_width)
	{
		$ratio = $this->height / $this->width;
		$new_height = $new_width * $ratio;

		return $new_height;
	}

	/**
	 * Checks to see if an image is portrait or landscape and resizes accordingly.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @return array
	 */
	private function get_size_by_auto($new_width, $new_height)
	{
		// Image to be resized is wider (landscape).
		if ($this->height < $this->width)
		{
			$optimal_width = $new_width;
			$optimal_height	= $this->get_size_by_fixed_width($new_width);
		}
		// Image to be resized is taller (portrait).
		elseif ($this->height > $this->width)
		{
			$optimal_width = $this->get_size_by_fixed_height($new_height);
			$optimal_height	= $new_height;
		}
		// Image to be resizerd is a square.
		else
		{
			if ($new_height < $new_width)
			{
				$optimal_width = $new_width;
				$optimal_height = $this->get_size_by_fixed_width($new_width);
			}
			elseif ($new_height > $new_width)
			{
				$optimal_width = $this->get_size_by_fixed_height($new_height);
				$optimal_height = $new_height;
			}
			else
			{
				// Sqaure being resized to a square.
				$optimal_width = $new_width;
				$optimal_height = $new_height;
			}
		}

		return array(
			'optimal_width'		=> $optimal_width,
			'optimal_height'	=> $optimal_height
		);
	}

	/**
	 * Resizes an image so it fits entirely inside the given dimensions.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @return array
	 */
	private function get_size_by_fit($new_width, $new_height)
	{
		$height_ratio = $this->height / $new_height;
		$width_ratio = $this->width / $new_width;

		$max = max($height_ratio, $width_ratio);

		return array(
			'optimal_width'		=> $this->width / $max,
			'optimal_height'	=> $this->height / $max,
		);
	}

	/**
	 * Crops an image from its center.
	 *
	 * @param  int    $optimal_width  The width of the image
	 * @param  int    $optimal_height The height of the image
	 * @param  int    $new_width      The new width
	 * @param  int    $new_height     The new height
	 * @return true
	 */
	private function manual_crop($crop_start_x, $crop_start_y, $new_width, $new_height)
	{
		// Find center - this will be used for the crop.

		// $crop = $this->image_resized;
		$dst_w = 0;
		$dst_h = 0;
		$src_w = 0;
		$src_h = 0;
		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;

		// Now crop from center to exact requested size.
		// $this->image_resized = imagecreatetruecolor( $this->width , $this->height );

		// imagealphablending( $crop , true );
		// imagealphablending( $this->image_resized , false );
		// imagesavealpha( $this->image_resized , true );

		// imagefilledrectangle( $this->image_resized , 0 , 0 , $new_width , $new_height,
		// 	imagecolorallocatealpha( $this->image_resized , 255 , 255 , 255 , 127 )
		// );

		// imagecopyresampled(dst_image, src_image, dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h)
		imagecopyresampled($this->image, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

		return true;
	}

	/**
	 * Attempts to find the best way to crop. Whether crop is based on the
	 * image being portrait or landscape.
	 *
	 * @param  int    $new_width  The width of the image
	 * @param  int    $new_height The height of the image
	 * @return array
	 */
	private function get_optimal_crop($new_width, $new_height)
	{
		$height_ratio = $this->height / $new_height;
		$width_ratio = $this->width / $new_width;

		if ($height_ratio < $width_ratio)
		{
			$optimal_ratio = $height_ratio;
		}
		else
		{
			$optimal_ratio = $width_ratio;
		}

		$optimal_height	= $this->height / $optimal_ratio;
		$optimal_width = $this->width / $optimal_ratio;

		return array(
			'optimal_width'		=> $optimal_width,
			'optimal_height'	=> $optimal_height
		);
	}

	/**
	 * Crops an image from its center.
	 *
	 * @param  int    $optimal_width  The width of the image
	 * @param  int    $optimal_height The height of the image
	 * @param  int    $new_width      The new width
	 * @param  int    $new_height     The new height
	 * @return true
	 */
	private function crop($optimal_width, $optimal_height, $new_width, $new_height)
	{
		$crop_points = $this->get_crop_points($optimal_width, $optimal_height, $new_width, $new_height);

		// Find center - this will be used for the crop.
		$crop_start_x = $crop_points['x'];
		$crop_start_y = $crop_points['y'];

		$crop = $this->image_resized;

		$dest_offset_x	= max(0, -$crop_start_x);
		$dest_offset_y	= max(0, -$crop_start_y);
		$crop_start_x	= max(0, $crop_start_x);
		$crop_start_y	= max(0, $crop_start_y);
		$dest_width		= min($optimal_width, $new_width);
		$dest_height	= min($optimal_height, $new_height);

		// Now crop from center to exact requested size.
		$this->image_resized = imagecreatetruecolor($new_width, $new_height);

		imagealphablending($crop, true);
		imagealphablending($this->image_resized, false);
		imagesavealpha($this->image_resized, true);

		imagefilledrectangle($this->image_resized, 0, 0, $new_width, $new_height,
			imagecolorallocatealpha($this->image_resized, 255, 255, 255, 127)
		);

		imagecopyresampled($this->image_resized, $crop, $dest_offset_x, $dest_offset_y, $crop_start_x, $crop_start_y, $dest_width, $dest_height, $dest_width, $dest_height);

		return true;
	}

	/**
	 * Gets the crop points based on the configuration either set in the file
	 * or overridden by user in their own config file, or on the fly.
	 *
	 * @param  int    $optimal_width  The width of the image
	 * @param  int    $optimal_height The height of the image
	 * @param  int    $new_width      The new width
	 * @param  int    $new_height     The new height
	 * @return array                  Array containing the crop x and y points.
	 */
	private function get_crop_points($optimal_width, $optimal_height, $new_width, $new_height)
	{
		$crop_points = array();

		// Where is our vertical starting crop point?
		// changed from > switch ( Config::get('resizer::defaults.crop_vertical_start_point') ) {
		// to static element
		$crop_vertical_start_point = 'top';
		$crop_horizontal_start_point = 'left';

		switch ($crop_vertical_start_point)
		{
			case 'top':
				$crop_points['y'] = 0;
				break;
			case 'center':
				$crop_points['y'] = ($optimal_height / 2) - ($new_height / 2);
				break;
			case 'bottom':
				$crop_points['y'] = $optimal_height - $new_height;
				break;

			default:
				throw new Exception('Unknown value for crop_vertical_start_point: '.Config::get('resizer::defaults.crop_vertical_start_point').'. Please check config file in the Resizer bundle.');
				break;
		}

		// Where is our horizontal starting crop point?
		switch ($crop_horizontal_start_point)
		{
			case 'left':
				$crop_points['x'] = 0;
				break;
			case 'center':
				$crop_points['x'] = ($optimal_width / 2) - ($new_width / 2);
				break;
			case 'right':
				$crop_points['x'] = $optimal_width - $new_width;
				break;

			default:
				throw new Exception('Unknown value for crop_horizontal_start_point: '.Config::get('resizer::defaults.crop_horizontal_start_point').'. Please check config file in the Resizer bundle.');
				break;
		}

		return $crop_points;
	}
}
