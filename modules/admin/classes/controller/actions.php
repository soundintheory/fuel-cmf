<?php

namespace Admin;

class Controller_Actions extends Controller_Base {
	
	/**
	 * Finds every image field in the DB and re-builds the stored array info for each
	 */
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
		$this->heading = \Lang::get('admin.messages.reset_images_success');
		$this->template = 'admin/generic.twig';
	}

	/**
	 * Syncs the files table with file and image fields
	 */
	public function action_sync_files_table()
	{	
		\CMF\Storage::syncFiles();
		$this->heading = 'All files were synced';
		$this->template = 'admin/generic.twig';
	}

	/**
	 * Remove old and broken URL entries
	 */
	public function action_clean_urls()
	{
		$deleted = \CMF\Model\URL::cleanOld();
		echo \Lang::get('admin.messages.num_type_deleted', array( 'num' => $deleted, 'type' => strtolower(\CMF\Model\URL::plural()) ));
		exit();
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
    		    
		        $output .= \Lang::get('admin.messages.item_updated', array( 'resource' => $item->getUrl() ))."<br />\n";
		        $updated++;
		        usleep(5000);
		        
		    } else {
		        
		        $type = $url->getType();
		        if (!in_array($type, $failed_types)) {
		            $failed_types[] = $type;
		        }
		        
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
		$output .= "<br /><br />".\Lang::get('admin.messages.num_items_updated', array( 'num' => $updated ))."<br />";
		
		return $output;
		
	}

	/**
	 * Save everything in the entire DB again
	 */
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

        foreach ($this->classNames as $class) {
            if (is_subclass_of($class,'\\CMF\\Model\\Base')) {
            	
            	$metadata = $class::metadata();

            	// Don't process super classes!
            	if ($class::superclass() || $metadata->isMappedSuperclass) {
            	    continue;
            	}

                $class::saveAll();
                \D::manager()->clear();
                sleep(1);
            }
        }

		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => \Lang::get('admin.messages.save_all_success') ));
		\Response::redirect_back();
	}
	
}