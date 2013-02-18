<?php

namespace CMF\Field\Relation;

class ManyToOne extends \CMF\Field\Base {
    
    protected static $defaults = array(
        'select2' => array(
            'allowClear' => false
        ),
        'create' => true,
        'edit' => true,
        'input_attributes' => array(
            'class' => ''
        )
    );
    
    protected $options = array();
    protected $parent_entity;
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return (is_null($value)) ? '(empty)' : '<a href="'.$edit_link.'" class="item-link">'.$value->display().'</a>';
    }
    
    public static function getAssets()
    {
        return array(
            'js' => array('/admin/assets/fancybox/jquery.fancybox.pack.js'),
            'css' => array('/admin/assets/fancybox/jquery.fancybox.css')
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	$id = isset($value) ? $value->id : '';
    	
        $settings = static::settings($settings);
        $settings['cid'] = 'field_'.md5($settings['mapping']['fieldName'].static::type());
        $required = isset($settings['required']) ? $settings['required'] : false;
        $include_label = isset($settings['label']) ? $settings['label'] : true;
    	$target_class = $settings['mapping']['targetEntity'];
        $target_table = \CMF\Admin::getTableForClass($target_class);
        $target_prop = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        if (empty($target_prop) || is_null($model->id)) $target_prop = false;
        $add_link = '/admin/'.$target_table.'/create?_mode=inline&_cid='.$settings['cid'].($target_prop !== false ? '&'.$target_prop.'='.$model->id : '');
    	$options = $target_class::options();
        $null_option = array( '' => '' );
        $options = $null_option + $options;
    	
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = $settings['input_attributes'];
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        
        $add_link = html_tag('a', array( 'href' => $add_link, 'class' => 'btn btn-mini btn-add' ), '<i class="icon icon-plus"></i> &nbsp;create '.strtolower($target_class::singular()));

        
        // Permissions
        $settings['can_edit'] = \CMF\Auth::can('edit', $target_class);
        $settings['can_create'] = \CMF\Auth::can('create', $target_class) && $settings['can_edit'];
        $settings['create'] = $settings['create'] && $settings['can_create'];
        $settings['edit'] = $settings['edit'] && $settings['can_edit'];
        
        if($settings['create'] === false){
            $add_link = " ";
        }

        $controls_top = html_tag('div', array( 'class' => 'controls-top' ), $add_link);
        
        if (is_array($settings['select2'])) {
            
            $input_attributes['class'] .= 'input-xxlarge select2';
            $input = \Form::select($settings['mapping']['fieldName'], $id, $options, $input_attributes);
            $settings['select2']['placeholder'] = 'click to select a '.strtolower($target_class::singular()) . '...';
            $settings['select2']['target_table'] = $target_table;
            
            // Permissions
            $settings['select2']['create'] = $settings['create'];
            $settings['select2']['edit'] = $settings['edit'];
            
            if (!$required) {
                $settings['select2']['allowClear'] = true;
            }
            
            return array(
                'content' => html_tag('div', array( 'class' => 'controls control-group field-with-controls'.($has_errors ? ' error' : ''), 'id' => $settings['cid'] ), $label.$input.$controls_top).'<div class="clear"><!-- --></div>',
                'widget' => false,
                'assets' => array(
                    'css' => array('/admin/assets/select2/select2.css'),
                    'js' => array('/admin/assets/select2/select2.min.js', '/admin/assets/js/fields/select2.js')
                ),
                'js_data' => $settings['select2']
            );
            
        }
        
        $input_attributes['class'] .= ' input-xxlarge';
        
        $input = \Form::select($settings['mapping']['fieldName'], $id, $options, $input_attributes);
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        return html_tag('div', array( 'class' => 'controls control-group field-with-controls'.($has_errors ? ' error' : ''), 'id' => $settings['cid'] ), $label.$input).'<div class="clear"><!-- --></div>';
    }
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'select';
    }
	
}