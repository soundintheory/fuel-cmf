<?php

namespace CMF\Field\Collection;

class Checkbox extends Multiselect {

    protected static $defaults = array(
        'cols' => 2,
        'select2' => array(  ),
        'transfer' => false,
        'widget' => false,
        'edit' => true,
        'create' => true,
        'input_attributes' => array(
            'class' => '',
            'multiple' => 'multiple',
            'size' => '10'
        )
    );
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        $target_class = $settings['mapping']['targetEntity'];
        $target_table = \Admin::getTableForClass($target_class);
        
        if ($value instanceof \Doctrine\Common\Collections\Collection && count($value) > 0) {
            
            $values = $value->toArray();
            $output = '';
            foreach ($values as $val) {
                $output .= \Html::anchor("/admin/$target_table/".$val->id."/edit", $val->display()).', ';
            }
            return rtrim($output, ', ');
            
        } else {
            return '-';
        }
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
    	$value = ($value instanceof \Doctrine\Common\Collections\Collection && count($value) > 0) ? $value->toArray() : array();
    	$value_ids = array_map(function($val) {
    		return $val->id;
    	}, $value);
    	
    	$target_class = $settings['mapping']['targetEntity'];
    	$targets = $target_class::findAll();
    	$checkboxes = array();

        $cols = intval($settings['cols'] ?: 1);
        $per_col = ceil(count($targets) / $cols);

        if ($per_col > 10) {
            $cols = ceil(count($targets) / 10);
            if ($cols > 4) {
                $cols = 4;
            }
            $per_col = ceil(count($targets) / $cols);
        }

        $col_class = $cols ? 'span'.floor(12 / $cols) : 'span12';
        $content = '';
        $num = 0;
    	$hidden = \Form::hidden($settings['mapping']['fieldName'], null);

        for ($i=0; $i < $cols; $i++) {

            $col_content = '';

            for ($j=0; $j < $per_col; $j++) { 

                if ($num >= count($targets)) {
                    break;
                }
                
                $checkbox = \Form::checkbox($settings['mapping']['fieldName'].'[]', $targets[$num]->id, in_array($targets[$num]->id, $value_ids));
                $col_content .= html_tag('label', array( 'class' => 'checkbox' ), $checkbox.' '.$targets[$num]->display());
                $num++;

            }

            $content .= html_tag('div', array( 'class' => $col_class ), $col_content);
            
        }
    	
    	$group_label = html_tag('label', array(), $settings['title']);
    	$group = html_tag('div', array( 'class' => 'controls control-group checkbox-group' ), $hidden.html_tag('div', array( 'class' => 'row-fluid' ), $content));
    	
        return html_tag('div', array( 'class' => 'controls control-group' ), $group_label.$group);
    }
	
}

?>