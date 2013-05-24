<?php

namespace Fuel\Tasks;

class Ffmpeg
{
	
	/**
	 * Converts a video into .webm and .mp4 formats for HTML5 video
	 * 
	 * @return void
	 */
	public function webvideo()
	{
		try {
		    set_time_limit(0);
		    ini_set('memory_limit', '256M');
		} catch (\Exception $e) {
		    // Nothing!
		}
		
		// Get params:
		$video_path = \Cli::option('file', null);
		if ($video_path === null) return;
		
		\Package::load(array('log'));
		
		// We need the log package loaded for Monolog
		$doc_root = realpath(APPPATH.'../../public').'/';
		$config = \Config::get('cmf.ffmpeg');
		$logger = new \Monolog\Logger('WebVideoConverter');
		$logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(APPPATH.'logs/ffmpeg.log'));
		
		// Get path info about the video
		$video_path = $doc_root.$video_path;
		$video_id = md5($video_path);
		$path_info = pathinfo($video_path);
		$converted_dir = $path_info['dirname'].'/converted';
		$progress_file = $video_path.'.progress';
		touch($progress_file);
		if (!is_dir($converted_dir)) $made_dir = @mkdir($converted_dir, 0775, true);

		// Set up the FFMpeg instances
		$ffprobe = new \FFMpeg\FFProbe($config['ffprobe_binary'], $logger);
		$ffmpeg = new \FFMpeg\FFMpeg($config['ffmpeg_binary'], $logger);
		$ffmpeg->setProber($ffprobe);

		// Probe the video for info
		$format_info = json_decode($ffprobe->probeFormat($video_path));
		$video_streams = json_decode($ffprobe->probeStreams($video_path));
		$video_info = null;
		foreach ($video_streams as $num => $stream) {
		    if ($stream->codec_type == 'video') {
		        $video_info = $stream;
		        break;
		    }
		}

		// Serve up an error if we can't find a video stream
		if ($video_info === null) {
		    return;
		}

		// Determine the frame rate
		if (isset($video_info->r_frame_rate)) {
		    $video_framerate = strval($video_info->r_frame_rate);
		    $parts = explode('/', $video_framerate);
		    $video_framerate = round(intval($parts[0]) / intval($parts[1]));
		} else {
		    $video_framerate = intval($config['default_framerate']);
		}
		
		// Get the size
		$video_width = intval(isset($video_info->width) ? $video_info->width : $config['default_size']['width']);
		$video_height = intval(isset($video_info->height) ? $video_info->height : $config['default_size']['height']);
		$video_kilobitrate = round(($video_width * $video_height) * .0019);
		$video_duration = floatval($format_info->duration);
		$still_frame_pos = $video_duration * 0.1;
		if (isset($format_info->bit_rate) && round($format_info->bit_rate / 1024) < $video_kilobitrate) $video_kilobitrate = round($format_info->bit_rate / 1024);
		
		// Set up the helper that outputs conversion progress
		$progressHelper = new \FFMpeg\Helper\VideoProgressHelper(function($percent, $remaining, $rate) use($progress_file) {
			$data = array( 'percent' => $percent, 'remaining' => $remaining, 'rate' => $rate );
			file_put_contents($progress_file, json_encode($data));
		});
		
		// Finally, convert to each format:
		$webMFormat = new \FFMpeg\Format\Video\WebM();
		$webMFormat->setDimensions($video_width, $video_height)
		->setFrameRate($video_framerate)
		->setKiloBitrate($video_kilobitrate)
		->setGopSize(25);
		
		$x264Format = new \FFMpeg\Format\Video\X264();
		$x264Format->setDimensions($video_width, $video_height)
		->setFrameRate($video_framerate)
		->setKiloBitrate($video_kilobitrate)
		->setGopSize(25);
		
		$ffmpeg->open($video_path)
		->attachHelper($progressHelper)
		->encode($webMFormat, $converted_dir.'/'.$path_info['filename'].'.webm')
		->encode($x264Format, $converted_dir.'/'.$path_info['filename'].'.mp4')
		->extractImage($still_frame_pos, $path_info['dirname'].'/'.$path_info['basename'].'.jpg')
		->close();
		
		// Delete the progress file to show that the process is complete
		unlink($progress_file);
		
	}

}
