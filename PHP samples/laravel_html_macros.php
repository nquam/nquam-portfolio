<?php
/**
 * Define custom Form macros here
 *
 * @author redacted
 * @author Nathan Quam
 * @since October 23, 2012
 */

/**
 * Produce a WYSIWYG
 *
 * @param string $name
 * @param string $input
 * @param string[] $attributes
 * @return string
 */
Form::macro('wysiwyg', function($name, $input = '', $attributes = array(), $use_js = TRUE)
{
    // Add the $name as the HTML textarea id
    if (empty($attributes['id']))
    {
        $attributes = array_merge($attributes, array('id' => $name));
    }

    // Generate a normal HTML textarea
    $return = Form::textarea($name, $input, $attributes);

    if ($use_js)
    {
        // Add some JavaScript to initialize the editor
        $return .= ' <script>$(document).ready(function() { var editor_'.$name.' = CKEDITOR.replace(\''.$name.'\'); CKFinder.setupCKEditor(editor_'.$name.', \'/vendors/ckeditor/ckfinder/\'); });</script>';
    }

    return $return;
});

/**
 * Generate a submit button that will submit the form
 *
 * @param string $name display name for the button
 * @param string $classes space separated string of classes names for the button
 * @return string
 */
Form::macro('submit_button', function($name = 'Submit', $classes = 'btn large')
{
    return '<input type="submit" value="Submit">';
});

/**
 * Generate a cancel button that will reset the form
 *
 * @param string $name display name for the button
 * @param string $classes space separated string of classes names for the button
 * @return string
 */
Form::macro('cancel_button', function($name = 'Reset', $classes = 'button flat radius cancel')
{
    return '<button class="'.$classes.'" type="reset">'.$name.'</button>';
});


/**
 * Generate the code for frontend comments.
 * This will display all current comments for the given parameters and
 * create the form needed to create a new comment.
 *
 * @param integer $section_id
 * @param integer $structure_id
 * @param integer $parent_id
 * @return string the rendered view
 */
Form::macro('comments', function($section_id, $structure_id = null, $parent_id = null)
{
    $user = User::current_user();

    if (is_null($user))
    {
        return HTML::link('/login', 'Login to leave a comment.');
    }

    $lang_key = Session::get('lang_key');

    $comments = is_null($structure_id)
        ? Comment::where_null('parent_id')
            ->where_lang_key($lang_key)
            ->where_section_id($section_id)
            ->where_null('structure_id')->get()
        : Comment::where_null('parent_id')
            ->where_lang_key($lang_key)
            ->where_section_id($section_id)
            ->where_structure_id($structure_id)->get();

    $data = array(
        'lang_key'      => $lang_key,
        'section_id'    => $section_id,
        'structure_id'  => $structure_id,
        'parent_id'     => $parent_id,
        'user'          => $user,
        'comments'      => $comments
    );

    $view = '';
    if (count($comments))
    {
        foreach ($comments as $comment)
        {
            // Don't display spam or unapproved comments
            if (!$comment->spam && $comment->approved)
            {
                $view .= render('comments.show', array(
                    'lang_key'      => $lang_key,
                    'comment'       => $comment,
                    'section_id'    => $section_id,
                    'structure_id'  => $structure_id,
                    'user'          => $user
                ));
            }
        }

        // Commented out because we are saving "commenting on a comment" until later
        // $view .= render('comments.show_js');
    }

    $view .= render('comments.new', $data);

    return $view;
});

/**
 * Build a form output to make all upload fields consistent and auto include the cropper if sizes are in the DB
 * Sample: {{ Form::foundation_upload('Image_2', $content->image_2 , 'Resource_Content_Animal', 'image_2') }}
 *
 * @param string $name
 * @param string $file
 * @param string $ref_model
 * @param string $field_name
 * @param integer $module_installation_id
 * @param string[] $additional_html
 * @param string $field_name_in_db
 * @return string the rendered view
 */

Form::macro('foundation_upload', function($name = FALSE, $file = FALSE, $ref_model = FALSE, $field_name = FALSE, $module_installation_id = null, $additional_html = array(), $field_name_in_db = null)
{
    $html = '
    <div class="row">
        <fieldset>
            <legend>'.$name.' Upload</legend>
            <label>Upload New File</label>
            '.Form::file($field_name, array('id' => $field_name, 'class' => 'label secondary button radius'));

    $html .= '<p>';
    if ($fileinfo = pathinfo(path('public').$file))
    {
        if (!empty($fileinfo['extension']))
        {
            $mime = File::mime($fileinfo['extension']);

            if (preg_match('/image/', $mime))
            {
                $html .= '
                    <fieldset>
                        <legend>Current Original</legend>
                        <div class="left">
                            <a href="javascript:void(0)" class="th thumb">'.HTML::image($file).'</a>
                        </div>
                        <div class="right">
                            '.Resizer::open($file)->build_cropper($ref_model, $field_name, $file, $module_installation_id, $field_name_in_db).'
                        </div>
                    </fieldset>';
            }
            else
            {
                $html .= '<a href="/'.$file.'" target="_blank">'.$fileinfo['basename'].'</a>';
            }
        }
    }
    else
    {
        $html .= 'No File Uploaded Yet';
    }
    $html .= '</p>';

    foreach ($additional_html as $additional_html_item)
    {
        $html .= '
            '.$additional_html_item;
    }


    $html .= '
        </fieldset>
    </div>';

    return $html;
});

/**
 * Build a form output to make a custom foundation style select box
 * Sample: {{ Form::foundation_select($preview_group_options, $content->preview_group_id) }}
 *
 * @param boolean|string $name
 * @param boolean|string $options
 * @param boolean|string $content_id
 * @return string
 */
Form::macro('foundation_select', function($name = FALSE, $options = FALSE, $content_id = FALSE)
{
    $html = Form::select($name, $options, Input::old($name, $content_id), array('class' => 'hidden'));

    $selected = $content_id !== FALSE && isset($options[$content_id])
        ? $options[$content_id]
        : reset($options);

    $html .= '
        <div class="custom dropdown">
            <a href="#" class="current">
                '.$selected.'
            </a>
            <a href="#" class="selector"></a>
            <ul>';

    foreach ($options as $select_item)
    {
        $html .= '
                <li>'.$select_item.'</li>';
    }

    $html .='
            </ul>
        </div>';

    return $html;
});

/**
 * Build a form output to make a custom foundation style checkbox
 * Sample: {{ Form::foundation_checkbox('category_'.$category->id, 1, in_array($category->id, $resource_categories), $category->name) }}
 *
 * @param boolean|string $name
 * @param boolean|string $value
 * @param boolean|string $checked
 * @param boolean|string $label
 * @return string
 */
Form::macro('foundation_checkbox', function($name = FALSE, $value = FALSE, $checked = FALSE, $label = FALSE)
{
    $html = '
    <label for="'.$name.'">'.Form::checkbox($name, $value, $checked).'<span class="custom checkbox checked"></span> '.$label.'</label>
';

    return $html;
});

/**
 * Image with a link around it. We use this because HTML::link html_entities the content
 * so you can't have an image in there.
 *
 * @param string $url
 * @param string $image_src
 * @param string $alt
 * @param string[] $link_attributes
 * @param string[] $image_attributes
 * @param boolean $https
 * @return string
 */
HTML::macro('link_image', function($url, $image_src, $alt = '', $link_attributes = array(), $image_attributes = array(), $https = null)
{
    $url = URL::to($url, $https);

    return '<a href="'.$url.'" '.HTML::attributes($link_attributes).'><img src="'.$image_src.'" alt="'.$alt.'" '.HTML::attributes($image_attributes).' /></a>';
});

/**
 * Generate a HTML link without using htmlentities for the display text.
 *
 * <code>
 *      // Generate a link to a location within the application
 *      echo HTML::link('user/profile', 'User Profile');
 *
 *      // Generate a link to a location outside of the application
 *      echo HTML::link('http://google.com', 'Google');
 * </code>
 *
 * @param  string  $url
 * @param  string  $title
 * @param  array   $attributes
 * @param  bool    $https
 * @return string
 */
HTML::macro('link_raw', function($url, $title = null, $attributes = array(), $https = null)
{
    $url = URL::to($url, $https);

    if (is_null($title)) $title = $url;

    return '<a href="'.$url.'"'.HTML::attributes($attributes).'>'.$title.'</a>';
});

/**
 * YouTube embed code
 *
 * @param string $youtube_code The YouTube code for the video you want to embed
 * @param string[] $classes array of class name you want added to the container around the iFrame
 * @return string
 */
HTML::macro('youtube', function($youtube_code, $classes = array())
{
    return
        '<div class="youtube-embed-container '.implode(' ', $classes).'" id="youtube-embed-container-'.$youtube_code.'">
            <iframe class="youtube-player" type="text/html" src="http://www.youtube.com/embed/'.$youtube_code.'?enablejsapi=1" allowfullscreen frameborder="0"></iframe>
        </div>';
});

/**
 * YouTube embed code
 *
 * @param string $code The YouTube code for the video you want to embed
 * @return string
 */
HTML::macro('youtube_with_cover', function($youtube_code, $image = null, $width = 640, $height = 360, $play_button = true)
{
    if (is_null($image) || empty($image) || !is_file(path('public').$image))
    {
        $image = 'http://img.youtube.com/vi/'.$youtube_code.'/0.jpg';
    }
    else
    {
        $image = Rabble::display_image($image, $width, $height);
    }

    $code = '
<div class="youtube-container-with-cover">
    <div class="youtube-cover">
        <img src="'.$image.'" alt="YouTube Video" />';
    if ($play_button === true)
    {
        $code .= '
            <span class="play-button"></span>';
    }
    $code .= '
    </div>
    <div class="youtube-embed-container" id="youtube-embed-container-'.$youtube_code.'">
        <iframe class="youtube-player" type="text/html" width="'.$width.'" height="'.$height.'" src="http://www.youtube.com/embed/'.$youtube_code.'?enablejsapi=1" allowfullscreen frameborder="0"></iframe>
    </div>
</div>
';

    return $code;
});

/**
 * Google Maps embed code
 *
 * @param string $link
 * @param integer $width
 * @param integer $height
 * @param boolean $link_to_larger
 */
HTML::macro('google_map', function($link, $width = 425, $height = 350, $link_to_larger = true)
{
    $return = '<iframe width="'.$width.'" height="'.$height.'" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.$link.'&output=embed"></iframe>';
    if ($link_to_larger)
        $return .= '<br /><small><a href="'.$link.'" style="color:#0000FF;text-align:left">View Larger Map</a></small>';

    return $return;
});

/**
 * Produce a link setup for FancyBox
 * To add a title, pass it in the $attributes array.
 *
 * @param string $url
 * @param string $text
 * @param array $attributes
 * @param string|null $group
 * @param string $class
 * @param string|null $config
 * @return string
 */
HTML::macro('fancybox_link', function($url, $text, $attributes = array(), $group = null, $class = 'fancybox', $config = null)
{
    $attributes = FancyBox2::add_fancy_class($class, $attributes);

    if (!isset($attributes['data-fancybox-type']) || empty($attributes['data-fancybox-type']))
        $attributes['data-fancybox-type'] = 'iframe';

    // If $group is passed in, set the rel attribute to the value of $group
    if (!is_null($group))
        $link_attributes['rel'] = $group;

    // Generate the link
    $return = HTML::link($url, $text, $attributes);

    // Generate the JavaScript
    $return .= FancyBox2::generate_fancy_js($class, $config);

    return $return;
});

/**
 * Produce a link around an image that is setup for FancyBox
 * To add a title, pass it in the $link_attributes array.
 * To add an "alt" tag, pass it in the $image_attributes array.
 *
 * @param string $url
 * @param string $image_src
 * @param array $link_attributes
 * @param array $image_attributes
 * @param string|null $group
 * @param string $class
 * @param string|null $config
 * @return string
 */
HTML::macro('fancybox_link_image', function($url, $image_src, $link_attributes = array(), $image_attributes = array(), $group = null, $class = 'fancybox', $config = null)
{
    $link_attributes = FancyBox2::add_fancy_class($class, $link_attributes);

    // If $group is passed in, set the rel attribute to the value of $group
    if (!is_null($group))
        $link_attributes['rel'] = $group;

    // Generate the link
    $return = '<a href="'.$url.'" '.HTML::attributes($link_attributes).'><img src="'.$image_src.'" '.HTML::attributes($image_attributes).' /></a>';

    // Generate the JavaScript
    $return .= FancyBox2::generate_fancy_js($class, $config);

    return $return;
});

/**
 * Generate a delete icon that you can click to visit the given URL.
 *
 * @param string $url
 * @param boolean $https
 * @return string
 */
HTML::macro('make_icon', function($url, $type, $https = null)
{
    $url = URL::to($url, $https);
    switch ($type) {
        case 'edit':
            return '<a href="'.$url.'"><i class="edit">E</i></a>';
            break;
        case 'delete':
            return '<a href="'.$url.'" data-method="delete"><i class="remove">X</i></a>';
            break;
        case 'view':
            return '<a href="'.$url.'"><i class="view">V</i></a>';
            break;
        default:
            # code...
            break;
    }
});

/**
 * Returns a string (usually intended for class usage) of 'even' or 'odd'
 * Pass an optional string to use as a key for separate instances
 *
 * @param string $url
 * @return string
 */
HTML::macro('oddeven', function($name = 'default')
{
    static $status = array();

    if (!isset($status[$name]))
    {
        $status[$name] = 0;
    }

    $status[$name] = 1 - $status[$name];
    return ($status[$name] % 2 == 0) ? 'even' : 'odd';
});

HTML::macro('get_subscriptions', function($user)
{
    $email = $user->email;
    $subscriptions = CampaignMonitor::clients('8249a99ef3951186e6401a6447bc8ec5')->get_lists_for_email($email)->response;
    $data = array();
    foreach($subscriptions as $sub)
    {
        if($sub->SubscriberState == "Active")
        {
            switch ($sub->ListID) {
                case '2e8e236a6cfe3b88c8a28fb11a8940cc':
                    $list_name = 'livewell_moms_momentum';
                    break;
                case 'bc53e12d8b2d78a9bb03ac4cf906904d':
                    $list_name = 'rally_man_newsletter';
                    break;
                case 'eb726918ec275329e2fa7a9ba1af3b06':
                    $list_name = 'livewell_moms_blog';
                    break;
                case 'bc1f8bca7df0d9441b1372323b8f8bba':
                    $list_name = 'livewell_eletter';
                    break;
                case '1fb9d40ffd4442707c1518de626940ad':
                    $list_name = 'livewell_blog';
                    break;
                default:
                    $list_name = false;
                    break;
            }
            $data[] = $list_name;
        }
    }
    return $data;
});

/**
 * A helper class for the generation of FancyBoxes
 */
class FancyBox2
{
    /**
     * Generate the JavaScript nessicary to instantiate FancyBox on the element with the given class
     *
     * @param integer $class
     * @param string|null $config
     * @return string
     */
    public static function generate_fancy_js($class, $config = null)
    {
        // If no configuration array is passed in, get our default configuration
        if (is_null($config))
        {
            $config_from_file = json_encode(Config::get('fancybox::fancybox'));
            $config = (is_null($config_from_file) || $config_from_file == 'null') ? '' : $config_from_file;
        }

        return '<script>$(document).ready(function() { $(\'.'.$class.'\').fancybox('.$config.'); });</script>';
    }

    /**
     * Take in an array of attributes and add $class to $attributes['class']
     *
     * @param string $class_to_add
     * @param array $attributes
     * @return array
     */
    public static function add_fancy_class($class_to_add, $attributes)
    {
        // If $class is not in $attributes['class'], add it
        $classes = explode(' ', isset($attributes['class']) ? $attributes['class'] : '');
        if (!in_array($class_to_add, $classes))
            array_push($classes, $class_to_add);
        $attributes['class'] = trim(implode(' ', $classes), ' ');

        return $attributes;
    }
}