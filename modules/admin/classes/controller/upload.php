<?php

namespace Admin;

use Monolog\Logger,
    Monolog\Handler\NullHandler;

/*
    Deals with asyncronous file uploads
 */
class Controller_Upload extends Controller_Base {

    protected $allowedExtensions = array();
    protected $sizeLimit = null;
    protected $inputName = 'qqfile';
    protected $chunksFolder = 'chunks';

    protected $chunksCleanupProbability = 0.001; // Once in 1000 requests on avg
    protected $chunksExpireIn = 604800; // One week

    protected $uploadName;
    protected $originalName;
    protected $target;
    
    /**
     * This is the endpoint for async file uploads
     */
    function action_index()
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '256M');
        } catch (\Exception $e) {
            // Nothing!
        }
        
        // Get the size limit as defined by PHP
        $this->sizeLimit = min($this->toBytes(ini_get('upload_max_filesize')), $this->toBytes(ini_get('post_max_size')));
        
        // If you want to use resume feature for uploader, specify the folder to save parts.
        $this->chunksFolder = APPPATH.'tmp/chunks';
        
        // Call handleUpload() with the name of the folder
        $path = \Input::get('path', urldecode(\Input::post('path', 'uploads')));
        $path = trim($path, '/').'/';
        $result = $this->handleUpload(DOCROOT.$path);
        
        // We may have been passed details about the owner of this video field
        $model_class = \Input::get('model', urldecode(\Input::post('model', null)));
        $item_id = \Input::get('item_id', urldecode(\Input::post('item_id', null)));
        $field_name = \Input::get('fieldName', urldecode(\Input::post('fieldName', null)));
        
        // Log any errors
        if (isset($result['error'])) {
            logger(\Fuel::L_ERROR, 'UPLOAD ERROR: '.$result['error']);
        }
        
        if (!empty($this->target)) {
            
            // Set the data for the response
            $result['uploadName'] = $this->getUploadName();
            $result['originalName'] = $this->getOriginalName();
            $result['path'] = $path.$result['uploadName'];
            $result['fieldName'] = $field_name;
            
            $info = @getimagesize($this->target);
            if ($info !== false) $result['info'] = $info;
            
        }
        
        $this->headers = array("Content-Type: text/plain");
        return \Response::forge(json_encode($result), $this->status, $this->headers);
        
    }
    
    /**
     * A special endpoint for video uploads. This starts the process of conversion into web formats
     */
    function action_video()
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '256M');
        } catch (\Exception $e) {
            // Nothing!
        }
        
        \Package::load(array('log'));
        
        // Get the size limit as defined by PHP
        $this->sizeLimit = min($this->toBytes(ini_get('upload_max_filesize')), $this->toBytes(ini_get('post_max_size')));
        
        // If you want to use resume feature for uploader, specify the folder to save parts.
        $this->chunksFolder = APPPATH.'tmp/chunks';
        
        // Call handleUpload() with the name of the folder
        $path = \Input::get('path', urldecode(\Input::post('path', 'uploads')));
        $path = trim($path, '/').'/';
        $result = $this->handleUpload(DOCROOT.$path);
        
        // We may have been passed details about the owner of this video field
        $model_class = \Input::get('model', urldecode(\Input::post('model', null)));
        $item_id = \Input::get('item_id', urldecode(\Input::post('item_id', null)));
        $field_name = \Input::get('fieldName', urldecode(\Input::post('fieldName', null)));
        
        // Set the data for the response
        $result['uploadName'] = $this->getUploadName();
        $result['originalName'] = $this->getOriginalName();
        $result['path'] = $path.$result['uploadName'];
        $result['fieldName'] = $field_name;
        
        // We need to work out where the PID file for this conversion will sit...
        $video_path = $result['path'];
        $video_pid = 'videoconvert_'.md5(DOCROOT.$video_path);
        $result['pid'] = $video_pid;
        $pid_dir = APPPATH.'run';
        if (!is_dir($pid_dir)) $dir_result = @mkdir($pid_dir, 0775, true);
        
        // Get the dimensions. These are important...
        $config = \Config::get('cmf.ffmpeg');
        $logger = new Logger('WebVideoConverter');
        $logger->pushHandler(new NullHandler());
        $full_video_path = DOCROOT.$video_path;
        
        // Set up the FFMpeg instances
        $ffprobe = new \FFMpeg\FFProbe($config['ffprobe_binary'], $logger);
        
        // Probe the video for info
        $format_info = json_decode($ffprobe->probeFormat($full_video_path));
        $video_streams = json_decode($ffprobe->probeStreams($full_video_path));
        $video_info = null;
        foreach ($video_streams as $num => $stream) {
            if ($stream->codec_type == 'video') {
                $video_info = $stream;
                break;
            }
        }

        // Serve up an error if we can't find a video stream
        if ($video_info === null) {
            $this->headers = array("Content-Type: text/plain");
            return \Response::forge(json_encode(array( 'error' => 'This is not a supported video type' )), $this->status, $this->headers);
        }
        
        // Now add some useful video stats to the result
        $result['width'] = intval(isset($video_info->width) ? $video_info->width : $config['default_size']['width']);
        $result['height'] = intval(isset($video_info->height) ? $video_info->height : $config['default_size']['height']);
        $result['duration'] = $format_info->duration;
        
        // Execute the oil conversion process as a separate 'thread'
        $oil_path = realpath(APPPATH.'../../oil');
        exec("php $oil_path r ffmpeg:webvideo -file='$video_path' > /dev/null 2>&1 & echo $! >> /dev/null");
        
        $this->headers = array("Content-Type: text/plain");
        return \Response::forge(json_encode($result), $this->status, $this->headers);
    }
    
    /**
     * Get the original filename
     */
    public function getName()
    {
        if (isset($_REQUEST['qqfilename']))
            return $_REQUEST['qqfilename'];

        if (isset($_FILES[$this->inputName]))
            return $_FILES[$this->inputName]['name'];
    }

    /**
     * Get the name of the uploaded file
     */
    public function getUploadName()
    {
        return $this->uploadName;
    }
    
    /**
     * Get the name of the uploaded file
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Process the upload.
     * @param string $uploadDirectory Target directory.
     * @param string $name Overwrites the name of the file.
     */
    public function handleUpload($uploadDirectory, $name = null)
    {
        // Make the chunks and upload directories if they don't exist
        $chunks_folder = (!is_dir($this->chunksFolder)) ? @mkdir($this->chunksFolder, 0775, true) : true;
        $upload_folder = (!is_dir($uploadDirectory)) ? @mkdir($uploadDirectory, 0775, true) : true;
        
        if (is_writable($this->chunksFolder) &&
            1 == mt_rand(1, 1/$this->chunksCleanupProbability)){

            // Run garbage collection
            $this->cleanupChunks();
        }

        // Check that the max upload size specified in class configuration does not
        // exceed size allowed by server config
        if ($this->toBytes(ini_get('post_max_size')) < $this->sizeLimit ||
            $this->toBytes(ini_get('upload_max_filesize')) < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            return array('error'=>"Server error. Increase post_max_size and upload_max_filesize to ".$size);
        }

        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Uploads directory isn't writable or executable.");
        }

        if(!isset($_SERVER['CONTENT_TYPE'])) {
            return array('error' => "No files were uploaded.");
        } else if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') !== 0){
            return array('error' => "Server error. Not a multipart request. Please set forceMultipart to default value (true).");
        }

        // Get size and name

        $file = $_FILES[$this->inputName];
        $size = $file['size'];

        if ($name === null){
            $name = $this->originalName = $this->getName();
        }

        // Validate name

        if ($name === null || $name === ''){
            return array('error' => 'File name empty.');
        }

        // Validate file size

        if ($size == 0){
            return array('error' => 'File is empty.');
        }

        if ($size > $this->sizeLimit){
            return array('error' => 'File is too large.');
        }

        // Validate file extension

        $pathinfo = pathinfo($name);
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        if($this->allowedExtensions && !in_array(strtolower($ext), array_map("strtolower", $this->allowedExtensions))){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }

        // Save a chunk

        $totalParts = isset($_REQUEST['qqtotalparts']) ? (int)$_REQUEST['qqtotalparts'] : 1;

        if ($totalParts > 1){

            $chunksFolder = $this->chunksFolder;
            $partIndex = (int)$_REQUEST['qqpartindex'];
            $uuid = $_REQUEST['qquuid'];

            if (!is_writable($chunksFolder) || !is_executable($uploadDirectory)){
                return array('error' => "Server error. Chunks directory isn't writable or executable.");
            }

            $targetFolder = $this->chunksFolder.DIRECTORY_SEPARATOR.$uuid;

            if (!file_exists($targetFolder)){
                mkdir($targetFolder);
            }

            $target = $targetFolder.'/'.$partIndex;
            $success = move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $target);

            // Last chunk saved successfully
            if ($success AND ($totalParts-1 == $partIndex)){

                $target = \CMF::getUniqueFilePath($uploadDirectory, $name);
                $this->uploadName = basename($target);

                $target = fopen($target, 'w');

                for ($i=0; $i<$totalParts; $i++){
                    $chunk = fopen($targetFolder.'/'.$i, "rb");
                    stream_copy_to_stream($chunk, $target);
                    fclose($chunk);
                }

                // Success
                fclose($target);

                for ($i=0; $i<$totalParts; $i++){
                    $chunk = fopen($targetFolder.'/'.$i, "r");
                    unlink($targetFolder.'/'.$i);
                }

                rmdir($targetFolder);

                return array("success" => true);

            }

            return array("success" => true);

        } else {

            $target = $this->target = \CMF::getUniqueFilePath($uploadDirectory, $name);

            if ($target){
                $this->uploadName = basename($target);

                if (move_uploaded_file($file['tmp_name'], $target)){
                    return array('success'=> true);
                }
            }

            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
    }

    /**
     * Deletes all file parts in the chunks folder for files uploaded
     * more than chunksExpireIn seconds ago
     */
    protected function cleanupChunks()
    {
        foreach (scandir($this->chunksFolder) as $item){
            if ($item == "." || $item == "..")
                continue;

            $path = $this->chunksFolder.DIRECTORY_SEPARATOR.$item;

            if (!is_dir($path))
                continue;

            if (time() - filemtime($path) > $this->chunksExpireIn){
                $this->removeDir($path);
            }
        }
    }

    /**
     * Removes a directory and all files contained inside
     * @param string $dir
     */
    protected function removeDir($dir){
        foreach (scandir($dir) as $item){
            if ($item == "." || $item == "..")
                continue;

            unlink($dir.DIRECTORY_SEPARATOR.$item);
        }
        rmdir($dir);
    }

    /**
     * Converts a given size with units to bytes.
     * @param string $str
     */
    protected function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
}