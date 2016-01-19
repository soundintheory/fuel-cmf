<?php

namespace CMF\Admin;

class ModelForm
{
	// For the templates to access when rendering
	public $content = array();
	public $assets = array( 'css' => array(), 'js' => array() );
	public $hidden_fields = array();
	public $js_field_settings = array();
	public $fields;
	public $field_keys;
	public $table_name;
	public $disable_groups = false;
	public $disable_widgets = false;
	public $icon = null;
	public $plural = null;
	public $singular = null;
	
	// For internal workings
	protected $tabs;
	protected $groups;
	protected $default_group;
	protected $default_tab;
	protected $fields_groups = array();
	protected $validator_meta;
	protected $prepopulate;
	protected $exclude;
	
	public function __construct($metadata, $model, $prefix = '', $prepopulate = array(), $exclude = array(), $disable_groups = false, $disable_widgets = false, $extra_settings = null)
	{
		$class_name = $metadata->name;
		$model_id = $model->id;
		$this->table_name = $metadata->table['name'];
		$this->prepopulate = \Arr::merge(\Input::get(), $prepopulate);
		$this->exclude = $exclude;
		$this->disable_groups = $disable_groups;
		$this->disable_widgets = $disable_widgets;
		$this->title = ($model_id && method_exists($model, 'getFormTitle')) ? $model->getFormTitle() : $class_name::singular();
		
		if (\Input::param('alias', false) !== false) {
			$this->icon = 'link';
			$this->plural = 'Links';
			$this->singular = 'Link';
		} else {
			$this->icon = $class_name::icon();
			$this->plural = $class_name::plural();
			$this->singular = $class_name::singular();
		}

		// Tabs, Groups, Fields
		$this->tabs = $class_name::tabs();
		$this->groups = $class_name::groups();
		$this->default_tab = $class_name::defaultTab();
		$this->default_group = $class_name::defaultGroup();

		// Merge in extra field settings
		$this->fields = \Admin::getFieldSettings($class_name);
		if ($extra_settings !== null && is_array($extra_settings)) {
			$this->fields = \Arr::merge($this->fields, $extra_settings);
		}

		$this->validator_meta = \D::validator()->getMetadataFactory()->getMetadataFor($class_name);
		
		// Merge any DB settings into the mix...
		$model_settings = $model->settings;
		if (is_array($model_settings)) {
			$_model_settings = array();
			foreach ($model_settings as $key => $value) {
				if (is_array($value) && ($metadata->hasField($key) || $metadata->hasAssociation($key))) {
					$_model_settings[$key] = $value;
				}
			}
			$this->fields = \Arr::merge($this->fields, $_model_settings);
		}
		
		// The field data
		$this->processFieldSettings($metadata, $model, $prefix);
		
		// The group data
		$this->processGroups();
		
		// The form structure
		$this->processFormStructure();
		
		$this->assets['js'] = array_unique($this->assets['js']);
		$this->assets['css'] = array_unique($this->assets['css']);
		
	}
	
	protected function processFormStructure()
	{
		$this->field_keys = array();
		
		foreach ($this->content as $tab_index => &$tab) {
			
			foreach ($tab['groups'] as $tab_group_index => &$group) {
				
				$fields = $this->groups[$group['index']]['fields'];
				$new_fields = array();
				
				foreach ($fields as $field_index => $field_name) {
					
					if (isset($this->fields[$field_name]['visible']) && $this->fields[$field_name]['visible'] === false) continue;
					if ((isset($this->fields[$field_name]['used']) && $this->fields[$field_name]['used'] === true)) continue;
					$field_collection = array();
					$this->getFieldCollection($field_name, $field_collection);
					$new_fields = array_merge($new_fields, $field_collection);
					
				}
				
				// Delete the group if there aren't any fields actually in it
				if (empty($new_fields)) {
					unset($tab['groups'][$tab_group_index]);
					continue;
				}
				
				$this->field_keys = array_merge($this->field_keys, $new_fields);
				$group['fields'] = $new_fields;
				
			}

			if (empty($tab['groups'])) {
				unset($this->content[$tab_index]);
			}
			
		}
		
	}
	
	protected function getFieldCollection($field_name, &$collection)
	{
		if (!array_key_exists($field_name, $this->fields)) return;
		$field_settings = $this->fields[$field_name];
		if (isset($field_settings['fields_before'])) {
			foreach ($field_settings['fields_before'] as $field_before) {
				$this->getFieldCollection($field_before, $collection);
			}
		}
		if (!in_array($field_name, $collection)
			&& (!isset($field_settings['visible']) || $field_settings['visible'] !== false)) $collection[] = $field_name;
		if (isset($field_settings['fields_after'])) {
			foreach ($field_settings['fields_after'] as $field_after) {
				$this->getFieldCollection($field_after, $collection);
			}
		}
	}
	
	protected function processFieldSettings($metadata, $model, $prefix = '')
	{
		$fields = $this->fields;
		
		foreach ($fields as $field_name => $field) {
			
			// Add the prefix to the field name with array syntax if provided
			if (!empty($prefix)) {
				$field['mapping']['fieldName'] = $prefix.'['.$field_name.']';
			}
			
			$excluded = in_array($field_name, $this->exclude);
			$prepopulated = isset($this->prepopulate[$field_name]);
			$hidden = (isset($field['visible']) && $field['visible'] === false) || $prepopulated || $excluded;
			
			// If the field is prepopulated via the url but not visible, add it as a hidden input
			if ($hidden) {
				$field['visible'] = $this->fields[$field_name]['visible'] = false;
				if ($prepopulated) {
					$this->hidden_fields[$field_name] = \Form::hidden($field['mapping']['fieldName'], $this->prepopulate[$field_name], array( 'data-field-name' => $field_name ));
				}
			}
			
			if ($this->disable_groups === true) {
				$field['sub_group'] = $this->fields[$field_name]['sub_group'] = false;
			}
			
			$field['required'] = $this->isRequired($field_name);

			$field_title_method = 'get'.\Inflector::camelize($field_name).'Title';
			if(method_exists($model,$field_title_method)){
				$field['title'] = $model->$field_title_method();
			}
			
			// Get the field's content
			$field_class = $field['field'];
			$this->fields[$field_name]['field_type'] = $field_class::type($field);
			$field_content = $this->fields[$field_name]['content'] = ($hidden) ? '' : $field_class::displayForm($model->get($field_name), $field, $model);
			$is_widget = false;
			
			// Get the field's assets
			$field_assets = $field_class::getAssets();
			
			// If the result is an array, merge the assets into this form's and set the field to be a widget unless told otherwise
			if (is_array($field_content)) {
				
				if (isset($field_content['js_data'])) {
					$merge = isset($field_content['merge_data']) && $field_content['merge_data'] === true;
					if ($merge === true) {
						$this->js_field_settings = array_merge($this->js_field_settings, $field_content['js_data']);
					} else {
						$this->js_field_settings[$field['mapping']['fieldName']] = $field_content['js_data'];
					}
				}
				
				$this->fields[$field_name] = $field = \Arr::merge($field_content, $this->fields[$field_name]);
				if (isset($field_content['assets'])) {
					$field_assets = \Arr::merge(($field_assets !== null && is_array($field_assets)) ? $field_assets : array(), $field_content['assets']);
				} else {
					$field_assets = ($field_assets !== null && is_array($field_assets)) ? $field_assets : array();
				}
				
				$this->fields[$field_name]['content'] = $field_content = $field_content['content'];
				
				// If this is a widget, generate it
				if ($is_widget = (isset($field['widget']) && $field['widget'] === true))
					$group_index = $this->fields[$field_name]['group_index'] = $this->getFieldGroupWidget($field_name);
				
			}
			
			// If the field is not a widget, work out what group the field should belong to
			if (!$is_widget){
				$group_index = $this->fields[$field_name]['group_index'] = $this->getFieldGroup($field_name);
			}
			
			if (isset($this->groups[$group_index]['fields'])) {
				$this->groups[$group_index]['fields'][] = $field_name;
			} else {
				$this->groups[$group_index]['fields'] = array($field_name);
			}
			
			if ($hidden === true) continue;
			
			// Add the assets to this form if there are any
			if($field_assets !== null && is_array($field_assets)) {
				if (isset($field_assets['css'])) {
					foreach ($field_assets['css'] as $asset) {
						$this->assets['css'][] = $asset;
					}
				}
				if (isset($field_assets['js'])) {
					foreach ($field_assets['js'] as $asset) {
						$this->assets['js'][] = $asset;
					}
				}
			}
			
		}
		
	}
	
	protected function processGroups()
	{
		// Add the order attribute in...
		$i = 0;
		foreach ($this->groups as $group_index => $group) {
			$this->groups[$group_index]['order'] = isset($group['order']) ? $group['order'] : $i;
			$i++;
		}
		
		// Order the groups...
		uasort($this->groups, function($a, $b) {
			return $a['order'] >= $b['order'];
		});
		
		// File them off into their tabs...
		foreach ($this->groups as $group_index => $group) {
			
			$group_title = isset($group['title']) ? $group['title'] : ucfirst($group_index);
			$tab_index = isset($group['tab']) ? $group['tab'] : $this->default_tab;
			
			// Standardise the data in the group
			$group_data = array_merge(array(
				'title' => $group_title,
				'slug' => \Inflector::friendly_title($group_title, '-', true),
				'tab' => $tab_index,
				'index' => $group_index,
				'collapsible' => true,
				'icon' => 'reorder',
				'fields' => array()
			), $group);
			
			// Add the group to the tab
			$form_tab = $this->getFormTab($tab_index);
			$form_tab['groups'][] = $group_data;
			$this->groups[$group_index] = $group_data;
			$this->content[$tab_index] = $form_tab;
			
			$i++;
			
		}
		
	}
	
	protected function getFormTab($tab_index)
	{
		if (isset($this->content[$tab_index])) return $this->content[$tab_index];
		$tab_title = isset($this->tabs[$tab_index]) ? $this->tabs[$tab_index] : ucfirst($tab_index);
		return $this->content[$tab_index] = array(
			'title' => $tab_title,
			'slug' => \Inflector::friendly_title($tab_title, '-', true),
			'groups' => array()
 		);
	}
	
	protected function getFieldGroupWidget($field_name)
	{
		$settings = $this->fields[$field_name];
		$group_id = 'field_'.$field_name;
		$field_class = $settings['field'];
		
		$group_data = isset($this->groups[$group_id]) ? $this->groups[$group_id] : array();
		$group_index = $this->getFieldGroup($field_name, false);
		$group_pos = array_search($group_index, array_keys($this->groups));
		$group_data = array_merge(array(
			'title' => isset($settings['widget_title']) ? $settings['widget_title'] : \Inflector::humanize($field_name),
			'icon' => isset($settings['widget_icon']) ? $settings['widget_icon'] : 'reorder',
			'tab' => isset($settings['widget_tab']) ? $settings['widget_tab'] : $this->default_tab,
			'class' => 'widget-type-'.$field_class::type($settings)
		), $group_data);
		
		if (!isset($this->groups[$group_id])) {
			
			if ($group_pos !== false) {
				$group_data['order'] = $group_pos;
			}
			
		}
		
		$this->groups[$group_id] = $group_data;
		
		return $group_id;
	}
	
	protected function getFieldGroup($field_name, $add_to_others = true)
	{
		if (isset($this->fields_groups[$field_name])) return $this->fields_groups[$field_name];
		$settings = $this->fields[$field_name];
		if ($add_to_others && isset($settings['after']) && isset($this->fields[$settings['after']])) {
			$fields_after = isset($this->fields[$settings['after']]['fields_after']) ? $this->fields[$settings['after']]['fields_after'] : array();
			$fields_after[] = $field_name;
			$this->fields[$settings['after']]['fields_after'] = $fields_after;
			$this->fields[$field_name]['used'] = true;
			return $this->fields_groups[$field_name] = $this->getFieldGroup($settings['after']);
		} else if ($add_to_others && isset($settings['before']) && isset($this->fields[$settings['before']])) {
			$fields_before = isset($this->fields[$settings['before']]['fields_before']) ? $this->fields[$settings['before']]['fields_before'] : array();
			$fields_before[] = $field_name;
			$this->fields[$settings['before']]['fields_before'] = $fields_before;
			$this->fields[$field_name]['used'] = true;
			return $this->fields_groups[$field_name] = $this->getFieldGroup($settings['before']);
		}
		return $this->fields_groups[$field_name] = (isset($settings['group']) ? $settings['group'] : $this->default_group);
	}
	
	/**
	 * Given a model and a field name prefix, returns a keyed array of field content for that model.
	 * @param object $model
	 * @param string $prefix
	 * @return array
	 */
	public function getFields($model = null, $prefix = '')
	{
		$fields = array();
		$hidden_fields = array();
		$js_field_settings = array();
		$has_prefix = !empty($prefix);
		
		foreach ($this->field_keys as $field_name) {
			
			$field = $this->fields[$field_name];
			if (isset($field['visible']) && $field['visible'] === false) continue;
			
			// Add the prefix to the field name with array syntax if provided
			if ($has_prefix) $field['mapping']['fieldName'] = $prefix.'['.$field_name.']';
			
			// Get the field's content
			$field_class = $field['field'];
			$field_content = $field_class::displayForm($model->get($field_name), $field, $model);
			$is_widget = false;
			
			// If the result is an array, merge the assets into this form's
			if (is_array($field_content)) {
				
				if (isset($field_content['js_data'])) {
					$merge = isset($field_content['merge_data']) && $field_content['merge_data'] === true;
					if ($merge === true) {
						$js_field_settings = array_merge($js_field_settings, $field_content['js_data']);
					} else {
						$js_field_settings[$field['mapping']['fieldName']] = $field_content['js_data'];
					}
				}
				
				$field = \Arr::merge($field_content, $field);
				$field_content = $field_content['content'];
				
			}
			
			$fields[$field_name] = $field_content;
			
		}
		
		foreach ($this->hidden_fields as $field_name => $hidden_field) {
			
			$hidden_field_name = ($has_prefix) ? $prefix.'['.$field_name.']' : $field_name;
			$hidden_fields[$field_name] = \Form::hidden($hidden_field_name, $this->prepopulate[$field_name], array( 'data-field-name' => $field_name ));
			
		}
		
		return array(
			'fields' => $fields,
			'hidden_fields' => $hidden_fields,
			'js_field_settings' => $js_field_settings
		);
	}
	
	public function getFieldContent()
	{
		$fields = array();
		
		foreach ($this->field_keys as $field_name) {
			
			$fields[$field_name] = $this->fields[$field_name]['content'];
			
		}
		
		return $fields;
	}
	
	public function getContentFlat()
	{
		$output = array();
		
		foreach ($this->content as $tab) {
			
			foreach ($tab['groups'] as $group) {
				
				foreach ($group['fields'] as $field_name) {
					
					$output[] = $this->fields[$field_name]['content'];
					
				}
				
			}
			
		}
		
		return implode("\n\n", $output);
		
	}
	
	public function getFieldsFlat()
	{
		$output = array();
		
		foreach ($this->content as $tab) {
			
			foreach ($tab['groups'] as $group) {
				
				foreach ($group['fields'] as $field_name) {
					
					$output[] = $field_name;
					
				}
				
			}
			
		}
		
		return $output;
		
	}
	
	protected function isRequired($field_name)
	{
		$nullable = isset($this->fields[$field_name]['mapping']['nullable']) ? $this->fields[$field_name]['mapping']['nullable'] : true;
		if ($nullable === false) return true;
		return $this->hasConstraint($field_name, array('NotBlank', 'NotNull'));
	}
	
	protected function hasConstraint($field_name, $constraint_type)
	{
		if (!array_key_exists($field_name, $this->validator_meta->members)) return false;
		$constraints = $this->validator_meta->members[$field_name][0]->constraints;
		
		if (is_array($constraint_type)) {
			
			foreach ($constraints as $constraint) {
				foreach ($constraint_type as $type) {
					if (strpos(get_class($constraint), $type) !== false) return true;
				}
			}
			
		} else {
			
			foreach ($constraints as $constraint) {
				if (strpos(get_class($constraint), $constraint_type) !== false) return true;
			}
			
		}
		return false;
	}
	
}