<?php

namespace CMF\Field\Collection;

class Multiselect extends \CMF\Field\Base {
    
    protected static $defaults = array(
        'select2' => array(  ),
        'transfer' => false,
        'widget' => false,
        'edit' => true,
        'create' => true,
        'delete' => true,
        'input_attributes' => array(
            'class' => '',
            'multiple' => 'multiple',
            'size' => '10'
        ),
        'group_by' => null
    );
    
    public static function getAssets()
    {
        return array(
            'js' => array('/assets/fancybox/jquery.fancybox.pack.js'),
            'css' => array('/assets/fancybox/jquery.fancybox.css')
        );
    }
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        $target_class = $settings['mapping']['targetEntity'];
        $target_table = \Admin::getTableForClass($target_class);
        
        if ($value instanceof \Doctrine\Common\Collections\Collection && count($value) > 0) {
            return \Html::anchor(\CMF::adminPath("/$target_table"), count($value).' &raquo;');
        } else {
            return '0';
        }
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        // Set up the values for the select
        $values = array();
        if (isset($value) && $value instanceof \Doctrine\Common\Collections\Collection) {
            foreach ($value as $val) {
                $values[] = strval($val->get('id'));
            }
        }
        
        $target_prop = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        if (empty($target_prop) || is_null($model->id)) $target_prop = false;
        
        // Set up the values for the template
        $settings = static::settings($settings);
        $target_class = $settings['mapping']['targetEntity'];
        $target_table = \CMF\Admin::getTableForClass($target_class);
        $options = $target_class::options(\Arr::get($settings, 'filters', array()), array(), null, null, null, is_array($settings['select2']), \Arr::get($settings, 'group_by'));
        $settings['required'] = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $settings['title'] = $settings['title'].($settings['required'] ? ' *' : '').($has_errors ? ' - '.$errors[0] : '');
        $settings['cid'] = 'field_'.md5($settings['mapping']['fieldName'].static::type());
        $settings['add_link'] = \CMF::adminUrl('/'.$target_table.'/create?_mode=inline&_cid='.$settings['cid'].($target_prop !== false ? '&'.$target_prop.'='.$model->id : ''));
        $settings['singular'] = $target_class::singular();
        $settings['icon'] = $target_class::icon();
        $settings['is_select2'] = false;
        
        // Permissions
        $settings['can_edit'] = \CMF\Auth::can('edit', $target_class);
        $settings['can_create'] = \CMF\Auth::can('create', $target_class) && $settings['can_edit'];
        $settings['create'] = $settings['create'] && $settings['can_create'];
        $settings['edit'] = $settings['edit'] && $settings['can_edit'];
        
        if ($settings['transfer'] === true) {
            
            $settings['input_attributes']['class'] .= ' input-xxlarge';
            
            $transfer_options = array();
            foreach ($options as $key => $value) {
               $transfer_options[] = array( 'value' => $key, 'content' => $value );
            }
            
            $content = strval(\View::forge('admin/fields/collection/multiselect.twig', array( 'settings' => $settings, 'options' => $options, 'values' => $values ), false));
            
            return array(
                'content' => $content,
                'widget' => $settings['widget'],
                'assets' => array(
                    'js' => array(
                        '/assets/js/bootstrap-transfer.js',
                        '/assets/js/fields/collection/transfer.js'
                    ),
                    'css' => array(
                        '/assets/css/bootstrap-transfer.css'
                    )
                ),
                'js_data' => array( 'options' => $transfer_options, 'values' => $values, 'edit' => $settings['edit'], 'create' => $settings['create'] )
            );
            
        } else if (is_array($settings['select2'])) {
            
            $settings['sortable'] = $settings['select2']['sortable'] = $target_class::sortable() && isset($settings['mapping']['orderBy']) && isset($settings['mapping']['orderBy']['pos']) && $settings['mapping']['orderBy']['pos'] == 'ASC';
            $settings['is_select2'] = true;
            $settings['input_attributes']['class'] .= 'input-xxlarge select2';
            $content = strval(\View::forge('admin/fields/collection/multiselect.twig', array( 'settings' => $settings, 'options' => $options, 'values' => $values ), false));
            $settings['select2']['placeholder'] = 'click to select a '.strtolower($settings['singular']) . '...';
            $settings['select2']['target_table'] = $target_table;
            
            // Permissions
            $settings['select2']['create'] = $settings['create'];
            $settings['select2']['edit'] = $settings['edit'];
            
            return array(
                'content' => $content,
                'widget' => $settings['widget'],
                'assets' => array(
                    'css' => array('/assets/select2/select2.css'),
                    'js' => array('/assets/select2/select2.min.js', '/assets/js/fields/select2.js')
                ),
                'js_data' => $settings['select2']
            );
            
        }
        
        $settings['input_attributes']['class'] .= ' input-xxlarge';
        
        return array(
            'content' => strval(\View::forge('admin/fields/collection/multiselect.twig', array( 'settings' => $settings, 'options' => $options, 'values' => $values ), false)),
            'assets' => array( 'js' => array('/assets/js/fields/collection/multiselect.js') ),
            'widget' => false
        );
        
        return ;
    }
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'multiselect'.(isset($settings['transfer']) && $settings['transfer'] === true) ? '-transfer' : '';
    }
	
}

?>