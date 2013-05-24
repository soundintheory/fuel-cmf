<?php

namespace CMF\Core;

class Upload extends \Fuel\Core\Upload
{
	protected static $_files_prepared = false;
	protected static $_files_saved = false;
	
	public static function _init()
	{
		if (!empty($_FILES)) {
			\Fuel\Core\Upload::_init();
		}
	}
	
	/**
	 * Allows updating a file's settings
	 * @param integer $index The index of the file to update
	 * @param array $file The array of file settings
	 */
	public static function set_file($index, $file)
	{
		static::$files[$index] = $file;
	}
	
	/**
	 * Almost the same as the normal save() method, but will use the 'saved_to'
	 * property in each file object as the path if it finds it
	 * inheritdoc
	 */
	public static function save()
	{
		if (static::$_files_saved === true || empty($_FILES)) return;
		
		foreach(static::get_files() as $num => $file)
		{
			if (isset($file['saved_to'])) {
				parent::save($num, $file['saved_to']);
			} else {
				parent::save($num);
			}
		}
		
		static::$_files_saved = true;
		
	}
	
	/**
	 * This merges any posted files into the POST array in a format that CMF's models will easily understand
	 * @return void
	 */
	public static function prepare()
	{
		if (static::$_files_prepared === true || empty($_FILES)) return;
        
        // Process any uploads and merge them into the POST data
        $files = \Input::file();
        
        if (!empty($files)) {
            
            $data = \Input::post();
            
            $files = \Upload::get_files();
            foreach ($files as $num => $file) {
                $field_key = str_replace(':', '.', $file['field']);
                $file['file_index'] = $num;
                $file['field_key'] = $field_key;
                static::set_file($num, $file);
                \Arr::set($data, $field_key, $file);
            }
            
            // Updates the model when the file is saved
            static::register('after', function (&$file) {
                $field_name = $file['field_settings']['mapping']['fieldName'];
                //print("setting $field_name to ".$file['saved_to'].$file['saved_as']);
                $file['model']->set($field_name, $file['saved_to'].$file['saved_as']);
            });
            
            static::$_files_prepared = true;
            $_POST = $data;
            
        }
	}
	
}