<?php

namespace Admin;

class Controller_Actions extends Controller_Base {
	
	public function action_reset_images()
	{
		try {
		    set_time_limit(0);
		    ini_set('memory_limit', '512M');
		} catch (\Exception $e) {
		    // Nothing!
		}
		
		$em = \D::manager();
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		$tables_fields = array();
		$sql = array();
		
		// Loop through all the model metadata and check for image fields
		foreach ($driver->getAllClassNames() as $class) {
		    
		    $metadata = $em->getClassMetadata($class);
		    $fields = $metadata->fieldMappings;
		    $image_fields = array();
		    
		    foreach ($fields as $field_name => $field) {
		        
		        if ($field['type'] == 'image') $image_fields[] = $field_name;
		        
		    }
		    
		    if (count($image_fields) === 0) continue;
		    
		    $items = $class::select('item')->getQuery()->getResult();
		    foreach ($items as $num => $item) {
		    	
		    	$item->set('updated_at', new \Datetime());
		    	$data = array();
		    	
		    	foreach ($image_fields as $image_field) {
		    		
		    		$image_value = $item->$image_field;
		    		
		    		if (is_array($image_value))
		    			$data[$image_field] = \Arr::filter_keys($image_value, array('src', 'width', 'height', 'alt'));
		    		
		    	}
		    	
		    	$item->populate($data);
		    	\D::manager()->persist($item);
		    }
		    
		}
		
		\D::manager()->flush();
		$this->heading = 'All the images have been reset!';
		$this->template = 'admin/generic.twig';
	}
	
	/**
	 * Go through every entry in the system and make sure the URL is up to date
	 */
	public function action_update_all_urls() {
		
		$urls = \CMF\Model_Url::findAll();
		$output = "";
		$updated = 0;
		$failed_types = array();
				
		foreach ($urls as $url)
		{
		    $item = $url->getItem();
		    if (is_null($item)) {
		        $output .= $url->getUrl()." was null!<br />";
		        continue;
		    }
		    
		    if (is_array($item) && count($item) > 0) {
		        
		        $item = $item[0];
		        
		        $item->update_url();
    		    \D::manager()->persist($item);
    		    \D::manager()->persist($url);
    		    
		        $output .= "updated ".$item->getUrl()."<br />";
		        $updated++;
		        usleep(5000);
		        
		    } else {
		        
		        $type = $url->getType();
		        if (!in_array($type, $failed_types)) {
		            $failed_types[] = $type;
		        }
		        //$output .= "skipping ".$item."<br />";
		        
		    }

		    
		}
		
		if (count($failed_types) > 0) {
		    
		    foreach ($failed_types as $failed_type)
		    {
		        
		        $all = $failed_type::findAll();
		        foreach ($all as $item)
		        {
		            $item->update_url();
		            \D::manager()->persist($item);
		            $updated++;
		            usleep(5000);
		        }
		        
		    }
		    
		}
		
		\D::manager()->flush();
		$output .= "<br /><br />updated ".$updated." urls<br />";
		
		return $output;
		
	}
	
	public function action_empty_objects()
	{
		return;
		$table = 'ad_spaces';
		$field = 'link';
		
		$res = \DB::query("SELECT id, $field FROM $table")->execute();
		
		foreach ($res->as_array() as $num => $result) {
			
			$link = $result[$field];
			
			if (empty($link) || is_null($link)) {
				
				$id = $result['id'];
				$newlink = serialize(array());
				$result = \DB::update($table)
			    ->value($field, $newlink)
			    ->where('id', '=', strval($id))
			    ->execute();
				
			}
			
		}
		print('done '.$table);
		exit();
	}
	
	public function action_convert_links()
	{
		return;
		$table = 'ad_spaces';
		$field = 'fallback_link';
		
		$res = \DB::query("SELECT id, $field FROM $table")->execute();
		
		foreach ($res->as_array() as $num => $result) {
			
			$link = $result[$field];
			if (!empty($link)) {
				
				$id = $result['id'];
				$len = strlen($link);
				$newlink = 'a:2:{s:8:"external";s:1:"1";s:4:"href";s:'.$len.':"'.$link.'";}';
				$result = \DB::update($table)
			    ->value($field, $newlink)
			    ->where('id', '=', strval($id))
			    ->execute();
				
			}
			
		}
		print('done '.$table);
		exit();
	}
	
	public function action_convert_images()
	{
		return;
		$table = 'news';
		$field = 'image';
		
		$res = \DB::query("SELECT id, $field FROM $table")->execute();
		
		foreach ($res->as_array() as $num => $result) {
			
			$image = $result[$field];
			if (!empty($image)) {
				
				$id = $result['id'];
				$len = strlen($image);
				$newimg = 'a:2:{s:3:"alt";s:0:"";s:3:"src";s:'.$len.':"'.$image.'";}';
				$result = \DB::update($table)
			    ->value($field, $newimg)
			    ->where('id', '=', strval($id))
			    ->execute();
				
			}
		}
		print('done '.$table);
		exit();
		//print_r($res->as_array());
		//exit();
	}
	
	public function action_make_visible()
	{
		$table = 'categories';
		$field = 'visible';
		
		$result = \DB::update($table)
			    ->value($field, 1)
			    ->execute();
		
		print('enabled '.$field.' on '.$table);
		exit();
		//print_r($res->as_array());
		//exit();
	}
	
	public function action_null_field()
	{
		return;
		$table = 'suppliers';
		$field = 'settings_id';
		
		$result = \DB::update($table)
			    ->value($field, null)
			    ->execute();
		
		print('nulled '.$field.' on '.$table);
		exit();
		//print_r($res->as_array());
		//exit();
	}
	
	public function action_fix_logo()
	{
		return;
		$table = 'suppliers';
		$field = 'logo';
		
		$res = \DB::query("SELECT id, logo FROM $table")->execute()->as_array();
		
		foreach ($res as $num => $result) {
			
			$id = $result['logo'];
			$_id = $result['id'];
			$image = \DB::select("image", "alt")->from('images')->where('id', '=', intval($id))->execute()->as_array();
			if (count($image) > 0) {
				
				
				$src = $image[0]['image'];
				$alt = $image[0]['alt'];
				
				$newlogo = 'a:2:{s:3:"alt";s:'.strlen($alt).':"'.$alt.'";s:3:"src";s:'.strlen($src).':"'.$src.'";}';
				
				$result = \DB::update('suppliers')
			    ->value('logo', $newlogo)
			    ->where('id', '=', strval($_id))
			    ->execute();
				
			}
		}
		
		print('done '.$table);
		exit();
		
	}

	public function action_save_all()
    {
		try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');
        } catch (\Exception $e) {
            // Nothing!
        }

        // Get driver and get all class names
        $driver = \D::manager()->getConfiguration()->getMetadataDriverImpl();
        $this->classNames = $driver->getAllClassNames();
        foreach ($this->classNames as $aClass) {
            if (is_subclass_of($aClass,'\\CMF\\Model\\Base')) {
                $aClass::saveall();
                sleep(1);
            }
        }

		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => "Everything was saved" ));
		\Response::redirect_back();
	}
	
}