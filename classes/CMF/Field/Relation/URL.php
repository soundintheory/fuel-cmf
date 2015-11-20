<?php

namespace CMF\Field\Relation;

class URL extends OneToOne {
	
	/** inheritdoc */
    public static function getAssets()
    {
		return array(
			'js' => array(
				'/admin/assets/js/fields/url.js'
			)
		);
    }

    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        if ($value instanceof \CMF\Model\URL) {

            $alias = $value->alias;
            if ($alias instanceof \CMF\Model\URL) {

                // Kick the alias to update, in case it hasn't been triggered
                $alias->set('updated_at', new \DateTime());

                // Populate any required fields on the model that are null, since they won't have been touched
                $model->blank(array('menu_title'));
            }

        }

        return $value;
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $target_class = $settings['mapping']['targetEntity'];
        if (is_null($value) || !$value instanceof $target_class) $value = new $target_class();

        // Show a simple alias form if the input var is set
        if (\Input::param('alias', false) !== false) {

            $alias = $value->alias;
            $label = \Form::label(\Arr::get($settings, 'title', ucfirst($settings['mapping']['fieldName'])), $settings['mapping']['fieldName'].'[alias]', array( 'class' => 'item-label' ));
            $options = \CMF\Field\Object\Link::getOptions($settings, $model);
            $select = \Form::select($settings['mapping']['fieldName'].'[alias]', ($alias instanceof \CMF\Model\URL) ? $alias->id : null, $options, array( 'class' => 'input input-xlarge select2' ));

            return array(
                'content' => html_tag('div', array( 'class' => 'controls control-group field-type-url-alias' ), $label.$select),
                'widget' => false,
                'assets' => array(
                    'css' => array('/admin/assets/select2/select2.css'),
                    'js' => array('/admin/assets/select2/select2.min.js')
                )
            );

        }
    	
    	$model_class = get_class($model);
    	$errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $attributes = array( 'class' => 'field-type-url controls control-group'.($has_errors ? ' error' : '') );
        $slug_name = $settings['mapping']['fieldName'].'[slug]';
        $label_text = $settings['title'].($has_errors ? ' - '.$errors[0] : '');
        
        if (\CMF::$lang_enabled && !\CMF::langIsDefault()) {
            
            if (!$value->hasTranslation('slug')) {
                $attributes['class'] .= ' no-translation';
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::defaultLang().'.png" />&nbsp; '.$label_text;
            } else {
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::lang().'.png" />&nbsp; '.$label_text;
            }
            
        }
        
        $keep_updated_setting = 'settings['.$settings['mapping']['fieldName'].'][keep_updated]';
        $keep_updated = \Form::hidden($keep_updated_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox keep-updated' ), \Form::checkbox($keep_updated_setting, '1', \Arr::get($settings, 'keep_updated', true), array()).strtolower(__('admin.common.auto_update')));
        $input = \Form::input($slug_name, $value->slug, array( 'class' => 'input-xlarge', 'data-copy-from' => implode(',', $model_class::slugFields()) ));
        $label = (!$include_label) ? '' : html_tag('label', array( 'class' => 'item-label', 'for' => $slug_name ), $label_text).$keep_updated.html_tag('div', array( 'class' => 'clear' ), '&nbsp;');
        $prefix = $value->prefix;
        $prepend = html_tag('span', array( 'class' => 'add-on' ), empty($prefix) ? '/' : $prefix );
        $input = html_tag('div', array( 'class' => 'input-prepend' ), $prepend.$input);
        $clear = '<div class="clear"><!-- --></div>';
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
    	
    	return html_tag('div', $attributes, $label.$input).$clear;
    }
	
}