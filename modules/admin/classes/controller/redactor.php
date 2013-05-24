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
        // This is a simplified example, which doesn't cover security of uploaded images. 
        // This example just demonstrate the logic behind the process.
         
        // files storage folder
        // $dir = CMFPATH.'modules'.DS.'admin'.DS.'public'.DS.'assets'.DS.'filemanager'.DS;;
        $public_dir = $_SERVER['DOCUMENT_ROOT'] . DS . 'uploads/images/';

        $_FILES['file']['type'] = strtolower($_FILES['file']['type']);
        //$extension = pathinfo($_FILES['file']['tmp_name'], PATHINFO_EXTENSION);

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
        // This is a simplified example, which doesn't cover security of uploaded files. 
        // This example just demonstrate the logic behind the process.

        copy($_FILES['file']['tmp_name'], DOCROOT . DS .'uploads/files/'.$_FILES['file']['name']);
                            
        $array = array(
            'filelink' => '/uploads/files/'.$_FILES['file']['name'],
            'filename' => $_FILES['file']['name']
        );

        echo stripslashes(json_encode($array));
            
    }
    public function action_getimages(){
        //find all images in said folder, encode as json.

        /* example
        [
            { "thumb": "/img/1m.jpg", "image": "/img/1.jpg", "title": "Image 1", "folder": "Folder 1" },
            { "thumb": "/img/2m.jpg", "image": "/img/2.jpg", "title": "Image 2", "folder": "Folder 1" },
            { "thumb": "/img/3m.jpg", "image": "/img/3.jpg", "title": "Image 3", "folder": "Folder 1" },
            { "thumb": "/img/4m.jpg", "image": "/img/4.jpg", "title": "Image 4", "folder": "Folder 2" },
            { "thumb": "/img/5m.jpg", "image": "/img/5.jpg", "title": "Image 5", "folder": "Folder 2" }
        ]
        */
        //lets just get one level, no folders.
        //read the uploads folder, depth 0.
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
                    'thumb'=>'/image/1/40/40/'.$folder.DS.$file,
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