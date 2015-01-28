<?php

	//** Set global Settings *********************************************************************************

	if (PHP_OS == "Darwin" || PHP_OS == "Linux")
	{
		define('ISWIN', FALSE);
	}
	else
	{
		define('ISWIN', TRUE);
	}

	$settings = new stdClass();
	$settings->plDir = getcwd();
	$settings->tempPath = pathFormat($settings->plDir . "/TEMP/");
	$settings->plTempDir = pathFormat($settings->plDir . "/TEMP/");
	$settings->temp2Path = pathFormat($settings->plDir . "/videos_transcoded/");
	$settings->dropPath = pathFormat($settings->plDir . '/videos_original/');

	$filename = "movie data 0001";
	$filepath = array($settings->dropPath."$filename.mov");

	transcode($filepath, $filename, 0);
	transcode($filepath, $filename, 1);
	transcode($filepath, $filename, 2);
	transcode($filepath, $filename, 3);
	transcode($filepath, $filename, 4);
	transcode($filepath, $filename, 5);
	transcode($filepath, $filename, 6);
	transcode($filepath, $filename, 7);
	transcode($filepath, $filename, 8);
	transcode($filepath, $filename, 9);

	/**
	 * Transcode the video
	 *
	 * @param $filepath
	 * @param $filename
	 */
	function transcode($filepath, $filename, $method, $audio = 0)
	{
		global $settings;
		emptyTempdir($settings->plTempDir);
		echo "Transcode video... \n";

		$quality = 28; //FIX
		$hasAudio = 0;
		$addAudio = '';
		$audioCmd = '-an';
		$audioCmdTs = '-an';
		$avconv = $settings->plDir . '/ffmpeg/bin/ffmpeg';
		$encodeFile = $settings->plTempDir . "/encode.txt";

		if (ISWIN)
		{
			$avconv = $settings->plDir . '\\trans\\ffmpeg\\bin\\ffmpeg.exe';
		}

		$encode = fopen($encodeFile, "w");

		$c = 0;
		foreach($filepath as $video)
		{
			// check and add audio
			if ($audio == 1)
			{
				$filePathMXF = '';
				$audioCmdTs = '-c:a libvo_aacenc -ac 2 -ar 44100 -shortest -async 1';
				if (isset($filePathMXF))
				{
					$hasAudio = hasAudio($filePathMXF);
				}
				if ($hasAudio != 1)
				{
					//If we do not have Audio we need to add silence as audio
					$addAudio = '-f lavfi -i aevalsrc=0';
					$audioCmdTs = '-c:a libvo_aacenc -ac 2 -ar 44100 -shortest -async 1 ';
				}
			}
			switch($method)
			{
				case 0:
					// makecutup sportscode method
					$ffOptionsMpgTs = "-y -crf $quality -vcodec libx264 $audioCmdTs -preset veryfast -g 15 -bf 16 -b_strategy 1 -s 1024x576 -aspect 16:9 -pix_fmt yuv420p -threads 0 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
				case 1:
					// serverlync method
					$ffOptionsMpgTs = " -y -pix_fmt yuv420p -r 25 -threads 0 -c:v libx264 -bf 0 -g 4 -vf yadif -s 1024x576 -aspect 16:9 -preset ultrafast -acodec libvo_aacenc -ac 2 -ab 128k -crf 34 ";
					break;
				case 2:
					// makecutup dvsport method
					$inputFrameRate = 59.597;
					$outputFrameRate = $inputFrameRate;//30000 / 1001;
					$ffOptionsMpgTs = "-y -crf $quality -vcodec libx264 $audioCmdTs -preset veryfast -g 15 -bf 16 -b_strategy 1 -r $outputFrameRate -s 1024x576 -aspect 16:9 -pix_fmt yuv420p -threads 0 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
				case 3:
					// no output framerate
					$ffOptionsMpgTs = "-y -crf $quality -vcodec libx264 $audioCmdTs -preset veryfast -g 15 -bf 16 -b_strategy 1 -s 1024x576 -aspect 16:9 -pix_fmt yuv420p -threads 0 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
				case 4:
					$outputFrameRate = 30000 / 1001;
					$ffOptionsMpgTs = "-y -crf $quality -vcodec libx264 $audioCmdTs -preset veryfast -g 15 -bf 16 -b_strategy 1 -r $outputFrameRate -s 1024x576 -aspect 16:9 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
				case 5:
					// no extra options
					$ffOptionsMpgTs = "-y -crf $quality $audioCmdTs -preset veryfast -r $outputFrameRate -s 1024x576 -aspect 16:9 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
				case 6:
					//no options
					$ffOptionsMpgTs = "-y -crf $quality $audioCmdTs";
					break;
				case 7:
					// no audio option
					$inputFrameRate = 59.597;
					$outputFrameRate = $inputFrameRate;//30000 / 1001;
					$ffOptionsMpgTs = "-y -crf $quality -vcodec libx264 -preset veryfast -g 15 -bf 16 -b_strategy 1 -r $outputFrameRate -s 1024x576 -aspect 16:9 -pix_fmt yuv420p -threads 0 -profile:v high -level:v 4.1 -tune film -coder 1";
					break;
			}

			/**
			 * -y (overwrite files)
			 * -pix_fmt (pixel format)
			 * -map (chosen input stream)
			 * -target pal- ntsc- film- vcd svcd dvd dv dv50
			 * -g (recommended 250)
			 * -maxrate 6000k
			 * -minrate 4000k
			 */

			$outputFileMP4 = $settings->plTempDir . "$filename-$c.mp4";
			fwrite($encode, "file '$outputFileMP4'\n");
			$cmd = "\"$avconv\" $addAudio -i \"$video\"  $ffOptionsMpgTs \"$outputFileMP4\" 2>&1";
			echo $cmd . "\n";
			exec($cmd);
			$c++;
		}

		fclose($encode);
		$filename = "$filename-method-$method";
		// write final mp4
		$filePathMp4 = $settings->plTempDir . "$filename.mp4";
		$encOptions = "-y -c:v copy";
		$cmd = "\"$avconv\" -f concat -i \"$encodeFile\" $encOptions $audioCmd \"$filePathMp4\" 2>&1";
		echo $cmd . "\n";
		exec($cmd);
		moveFiles($filename, $settings->temp2Path);
	}

	/**
	 * Move pldata and mp4 files back to the SCpkg folder
	 * @param $cutupName
	 * @param $path
	 */
	function moveFiles($cutupName, $path)
	{
		global $settings;
		$mp4File = pathFormat($settings->tempPath . "$cutupName.mp4");
		$mp4FileTemp2 = pathFormat($path . "$cutupName.mp4");

		$mp4File = str_replace('\\\\', '\\', $mp4File);
		$mp4FileTemp2 = str_replace('\\\\', '\\', $mp4FileTemp2);

		if (!empty($mp4File))
		{
			//move the mp4 file
			if (ISWIN)
			{
				$cmd = 'MOVE /Y "' . $mp4File . '" "' . $mp4FileTemp2 . '"';
			}
			else
			{
				$cmd = 'mv "' . $mp4File . '" "' . $mp4FileTemp2 . '"';
			}
			echo "$cmd\n";
			system($cmd, $movemp4);
		}
	}

	/**
	 * Empty the temp directory
	 *
	 * @param string $plTempDir
	 *
	 * @example $plBaseDir\\TEMP\\
	 */
	function emptyTempdir($plTempDir)
	{
		if (!empty($plTempDir))
		{
			if (ISWIN)
			{
				$cmd = 'DEL /Q /S "' . $plTempDir . '*.*"';
			}
			else
			{
				$cmd = 'rm -fdR "' . $plTempDir . '"';
				echo $cmd . "\n";
				exec($cmd);
				$cmd = 'mkdir "' . $plTempDir . '"';
			}
			echo $cmd . "\n";
			exec($cmd);
		}
	}

	/**
	 * Check our file path format for win or linux
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	function pathFormat($string)
	{
		if (ISWIN)
		{
			return str_replace("/", '\\', $string);
		}
		else
		{
			return $string;
		}

	}

?>