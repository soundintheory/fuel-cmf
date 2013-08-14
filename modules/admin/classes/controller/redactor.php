<?php

namespace Admin;

class Controller_Redactor extends \Controller {
    
    /**
     * Uploads an image to the uploads folder
     */
    public function action_imageupload()
    {
        if(isset($_FILES)){
            $result = $this->uploadImage();
            echo $result;
        }
        exit;
    }
    
    /**
     * Uploads a file to the uploads folder
     */
    public function action_fileupload()
    {
        if(isset($_FILES)){
            $result = $this->uploadFile();
            echo $result;
        }
        exit;
    }
    
    /**
     * Retrieves a list of images from the uploads folder in JSON format
     */
    public function action_images()
    {
        $folder = 'uploads/images/content';
        $all_files = \File::read_dir(DOCROOT . DS . $folder, 0, array(
            '!^\.', // no hidden files/dirs
            '!' => 'dir', // no dirs
            '\.png$' => 'file', // only get png's
            '\.jpg$' => 'file', // or jpg files
            '\.gif$' => 'file', // or gif files
            '!^_', // exclude everything that starts with an underscore.
        ));
        $image_array = array();
        foreach ($all_files as $key => $file) {
            $image_array[] = array(
                'thumb'=>'/image/1/40/40/'.$folder.DS.$file,
                'image'=> DS . $folder . DS . $file
            );
        }
        $json = json_encode($image_array);
        echo $json;
        exit;

    }
    
    private function uploadImage()
    {
        // files storage folder
        $public_dir = $_SERVER['DOCUMENT_ROOT'] . DS . 'uploads/images/';
        if (!($exists = is_dir($public_dir))) {
            $exists = @mkdir($public_dir, 0775, true);
        }
        
        $_FILES['file']['type'] = strtolower($_FILES['file']['type']);
        
        if ($_FILES['file']['type'] == 'image/png' 
        || $_FILES['file']['type'] == 'image/jpg' 
        || $_FILES['file']['type'] == 'image/gif' 
        || $_FILES['file']['type'] == 'image/jpeg'
        || $_FILES['file']['type'] == 'image/pjpeg')
        {	
            // setting file's mysterious name
            //$filename = md5(date('YmdHis')).'.'.$extension;
            
            $path_parts = pathinfo($_FILES['file']['name']);

            $raw_filename = preg_replace("/\\.[^.\\s]{3,4}$/", "", $_FILES['file']['name']);

            $filename = \CMF::slug($raw_filename).'.'.$path_parts['extension'];
            $file = $public_dir.$filename;
            if(file_exists($file)){
                $file = $public_dir . date('ymdHis') . $filename;
            }
            // copying
            copy($_FILES['file']['tmp_name'], $file);

            // displaying file    
        	$array = array(
        		'filelink' => '/uploads/images/'.$filename
        	);
        	
        	return stripslashes(json_encode($array));
        }
    }

    private function uploadFile()
    {
        $public_dir = DOCROOT . DS .'uploads/files/';
        if (!($exists = is_dir($public_dir))) {
            $exists = @mkdir($public_dir, 0775, true);
        }
        
        copy($_FILES['file']['tmp_name'], $public_dir.$_FILES['file']['name']);
        
        $array = array(
            'filelink' => '/uploads/files/'.$_FILES['file']['name'],
            'filename' => $_FILES['file']['name']
        );
        
        echo stripslashes(json_encode($array));
    }
    
    public function action_getimages(){
        
        // find all images in said folder, encode as json.
        $folder = 'uploads/images';
        $full_folder = DOCROOT . DS . $folder;
        if(!is_dir($folder)){
            mkdir($folder);
        }
        if(is_dir($folder)){
            $all_files = \File::read_dir($folder, 0, array(
                '!^\.', // no hidden files/dirs
                '!' => 'dir', // no dirs
                '\.png$' => 'file', // only get png's
                '\.jpg$' => 'file', // or jpg files
                '\.gif$' => 'file', // or gif files
                '!^_', // exclude everything that starts with an underscore.
            ));
            $image_array = array();
            foreach ($all_files as $key => $file) {
                $image_array[] = array(
                    'thumb'=>'/image/1/90/90/'.$folder.DS.$file,
                    'image'=> DS . $folder . DS . $file
                );
            }
            $json = json_encode($image_array);
            echo $json;
            exit;
        }
    }
}
?>