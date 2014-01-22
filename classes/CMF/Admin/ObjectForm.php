<?php

namespace CMF\Admin;

/**
 * 
 * Given a simple array of settings, will prepare and render a simple form using the CMF's field classes.
 * Created for use with the 'object' field type, a parent model is still needed for getting / setting errors etc.
 * 
 */
class ObjectForm
{
	// For the templates to access when rendering
	public $assets = array( 'css' => array(), 'js' => array() );
	public $settings;
	public $values;
	public $title;
	public $attributes;
	public $js_field_settings = array();
	
	public function __construct($settings, $values)
	{
		$this->settings = $settings;
		$this->values = (isset($values) && is_array($values)) ? $values : array();
		
		// The field settings
		$this->processFieldSettings();
		
	}
	
	/**
	 * Goes through the defined fields, standardises the data for each and populates the rendered
	 * content into each field's array.
	 * @param object $model The model containing the object
	 * @return void
	 */
	protected function processFieldSettings()
	{
		if (!isset($this->settings['fields'])) return;
		
		$fields_types = \Config::get('cmf.fields_types');
		$fields_keys = array_keys($this->settings['fields']);
		
		// Merge in the values in the DB if this is dynamic
		if (@$this->settings['dynamic'] && !@$this->settings['tabular'])
			$fields_keys = array_unique(array_merge($fields_keys, array_keys($this->values)));
		
		foreach ($fields_keys as $field_name) {
			
			if (isset($this->settings['fields'][$field_name])) {
				$field = $this->settings['fields'][$field_name];
			} else {
				$field = array( 'dynamic' => true );
			}
			
			// Generate the title from the field name if not defined
			if (!isset($field['title'])) $field['title'] = \Inflector::humanize($field_name);
			
			// Populate the type with the default if it's not there, or if it isn't set...
			if (!isset($field['type']) || !array_key_exists($field['type'], $fields_types)) {
				$field['type'] = 'string';
			}
			
			$field['original_name'] = $field_name;
			// Prefix the field name in array syntax if there is one
			$real_field_name = $this->settings['mapping']['fieldName'].'['.$field_name.']';
			
			// Mimic the way Doctrine fields are mapped, so we can use the normal field classes
			$field['mapping'] = array(
				'fieldName' => $real_field_name
			);
			
			// Get the field class from the config if not already set
			$field_class = $field['field'] = (!isset($field['field'])) ? $fields_types[$field['type']] : $field['field'];
			
			$this->settings['fields'][$field_name] = $field;
			
		}
		
	}
	
	public function getContent($model)
	{
		// If there's a custom template defined, use that instead
		if (isset($this->settings['template'])) {
			
			foreach ($this->settings['fields'] as $field_name => $field) {
				
				$field_class = $field['field'];
				$field_assets = $field_class::getAssets();
				if (is_array($field_assets)) {
					$this->assets = \Arr::merge($this->assets, $field_assets);
				}
				$field['label'] = false;
				$field['wrap'] = false;
				//$field['mapping']['fieldName'] = $this->settings['mapping']['fieldName'].'['.$field['mapping']['fieldName'].']';
				$field_content = $field_class::displayForm(isset($this->values[$field_name]) ? $this->values[$field_name] : null, $field, $model);
				
				if (is_array($field_content)) {
					if (isset($field_content['assets']) && is_array($field_content['assets'])) $this->assets = \Arr::merge($this->assets, $field_content['assets']);
					$this->settings['fields'][$field_name]['content'] = $field_content['content'];
					
					if (isset($field_content['js_data'])) {
						$merge = isset($field_content['merge_data']) && $field_content['merge_data'] === true;
						if ($merge === true) {
							$this->js_field_settings = array_merge($this->js_field_settings, $field_content['js_data']);
						} else {
							$this->js_field_settings[$field['mapping']['fieldName']] = $field_content['js_data'];
						}
					}
					
				} else {
					$this->settings['fields'][$field_name]['content'] = $field_content;
				}
				
			}
			
			return \View::forge($this->settings['template'], array( 'form' => $this ), false);
			
		}
		
		$is_widget = isset($this->settings['widget']) ? $this->settings['widget'] : true;
		$errors = $model->getErrorsForField($this->settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $output = array();
		
		// This is a dynamic key/value object
		if ($this->settings['dynamic'] === true && $this->settings['array'] !== true) {
			
			// Add the necessary JS
			$this->assets['js'][] = '/admin/assets/js/fields/object.js';
			
			// Get each field's HTML
			foreach ($this->settings['fields'] as $field_name => $field) {
				if (isset($field['visible']) && $field['visible'] === false || 
					isset($field['delete']) && $field['delete'] === true) continue;
				$field_class = $field['field'];
				$field_assets = $field_class::getAssets();
				if (is_array($field_assets)) {
					$this->assets = \Arr::merge($this->assets, $field_assets);
				}
				$field['label'] = false;
				$field['mapping']['fieldName'] = $this->settings['mapping']['fieldName'].'[values][]';
				$field_content = $field_class::displayForm(isset($this->values[$field_name]) ? $this->values[$field_name] : null, $field, $model);
				
				if (is_array($field_content)) {
					$this->assets = \Arr::merge($this->assets, $field_content['assets']);
					$output[] = $this->settings['fields'][$field_name]['content'] = $field_content['content'];
				} else {
					$output[] = $this->settings['fields'][$field_name]['content'] = $field_content;
				}
				
			}
			
			return \View::forge('admin/fields/object/dynamic.twig', array( 'form' => $this ), false);
			
		}
		
		// This is an array of objects with a number of defined fields
		if ($this->settings['dynamic'] === false && $this->settings['array'] === true) {
			
			// Add the necessary JS
			$this->assets['js'][] = '/admin/assets/js/fields/collection/array-inline.js';
			
			// Create the info for the field template
			$rows = array();
			$cols = array_keys($this->settings['fields']);
			$sortable = true;
			$template = array();
			
			// Get each field's HTML
			foreach ($this->settings['fields'] as $field_name => $field) {
				if (isset($field['visible']) && $field['visible'] === false || 
					isset($field['delete']) && $field['delete'] === true) continue;
				$field_class = $field['field'];
				$field_assets = $field_class::getAssets();
				if (is_array($field_assets)) {
					$this->assets = \Arr::merge($this->assets, $field_assets);
				}
				$field['label'] = false;
				$field['mapping']['fieldName'] = '__TEMP__'.$this->settings['mapping']['fieldName'].'[__NUM__]['.$field['original_name'].']';
				$field['field_type'] = $field_class::type($field);
				$this->settings['fields'][$field_name] = $field;
				
				// Add the field to the template
				$field_content = $field_class::displayForm(null, $field, $model);
				
				if (is_array($field_content)) {
					$this->assets = \Arr::merge($this->assets, $field_content['assets']);
					$template[$field_name] = $field_content['content'];
				} else {
					$template[$field_name] = $field_content;
				}
			}
			
			// Now we need fields for each row
			foreach ($this->values as $num => $row) {
				
				$row_fields = array();
				
				// Not yet!
				foreach ($row as $field_name => $value) {
					
					if (!isset($this->settings['fields'][$field_name])) continue;
					
					$field = $this->settings['fields'][$field_name];
					$field_class = $field['field'];
					$field['mapping']['fieldName'] = $this->settings['mapping']['fieldName'].'[__NUM__]['.$field['original_name'].']';
					
					$row_fields[$field_name] = $field_class::displayForm($value, $field, $model);
				}
				
				$rows[] = $row_fields;
				
			}
			
			return \View::forge('admin/fields/collection/tabular-array.twig', array(
				'form' => $this,
				'template' => $template,
				'rows' => $rows,
				'cols' => $cols,
				'settings' => $this->settings,
				'sortable' => $sortable,
			), false);
			
		}
		
		// This is a normal object containing a number of defined fields. Get each field's HTML
		foreach ($this->settings['fields'] as $field_name => $field) {
			if (isset($field['visible']) && $field['visible'] === false || 
					isset($field['delete']) && $field['delete'] === true) continue;
			$field_class = $field['field'];
			$field_assets = $field_class::getAssets();
			if (is_array($field_assets)) {
				$this->assets = \Arr::merge($this->assets, $field_assets);
			}
			$field_content = $field_class::displayForm(isset($this->values[$field_name]) ? $this->values[$field_name] : null, $field, $model);
			
			if (is_array($field_content)) {
				if (isset($field_content['assets']) && is_array($field_content['assets'])) $this->assets = \Arr::merge($this->assets, $field_content['assets']);
				$output[] = $this->settings['fields'][$field_name]['content'] = $field_content['content'];
				
				if (isset($field_content['js_data'])) {
					$merge = isset($field_content['merge_data']) && $field_content['merge_data'] === true;
					if ($merge === true) {
						$this->js_field_settings = array_merge($this->js_field_settings, $field_content['js_data']);
					} else {
						$this->js_field_settings[$field['mapping']['fieldName']] = $field_content['js_data'];
					}
				}
				
			} else {
				$output[] = $this->settings['fields'][$field_name]['content'] = $field_content;
			}
		}
		
		$attributes = $this->attributes = array( 'class' => 'field-type-object controls control-group'.($has_errors ? ' error' : '') );
		
		// If it isn't a widget, maybe it wants to be a sub group (wrapped in a grey box)...
		if ($this->settings['widget'] !== true && $this->settings['sub_group'] === true) {
			$title = $this->settings['title'].($has_errors ? ' - '.$errors[0] : '');
			if (isset($this->settings['widget_icon']) && !empty($this->settings['widget_icon']))
				$title = '<i class="icon icon-'.$this->settings['widget_icon'].'"></i> '.$title;
			
			$this->title = $title;
			$heading = html_tag('h5', array(), $title);
			$content = html_tag('div', array( 'class' => 'sub-group' ), $heading.implode("\n\n", $output));
            return html_tag('div', $attributes, $content);
        }
		
		return html_tag('div', $attributes, implode("\n\n", $output));
	}
	
	public function processFields($model)
	{
		foreach ($this->settings['fields'] as $field_name => $field) {
			if (isset($field['delete']) && $field['delete'] === true) continue;
			$field_class = $field['field'];
			$this->values[$field_name] = $field_class::process(isset($this->values[$field_name]) ? $this->values[$field_name] : null, $field, $model);
		}
	}
	
}