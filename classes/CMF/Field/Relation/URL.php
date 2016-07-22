<?php

namespace CMF\Field\Relation;

use MyProject\Proxies\__CG__\stdClass;

class URL extends OneToOne {
	
	/** inheritdoc */
    public static function getAssets()
    {
		return array(
			'js' => array(
				'/admin/assets/js/fields/url.js',
                '/admin/assets/select2/select2.min.js',
                '/admin/assets/js/fields/link.js'
			),
            'css' => array(
                '/admin/assets/select2/select2.css'
            )
		);
    }

    public static function preProcess($value, $settings, $model)
    {
        if (is_array($value))
        {
            if (!empty($model)) $model->changed = true;

            if (isset($value['href']) && is_numeric($value['href']))
            {
                $url = \CMF\Model\URL::find(intval($value['href']));
                if (!empty($url))
                {
                    $value = new \CMF\Model\URL();
                    $value->populate(array(
                        'alias' => $url
                    ));
                }
            }
            else if (isset($value['external']) && intval($value['external']))
            {
                $url = $model->get($settings['mapping']['fieldName']);
                if (empty($url)) $url = new \CMF\Model\URL();

                $url->populate(array(
                    'url' => @$value['href'],
                    'slug' => '',
                    'prefix' => '',
                    'item_id' => null,
                    'type' => \CMF\Model\URL::TYPE_EXTERNAL,
                    'alias' => null
                ));

                return $url;
            }
        }

        return $value;
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
        if (\Input::param('alias', false) !== false)
        {
            $linkValue = array();
            if (!empty($value))
            {
                $linkValue['href'] = $value->isExternal() ? $value->url : strval($value->id);
                $linkValue['external'] = $value->isExternal();
            }
            return \CMF\Field\Object\Link::displayForm($linkValue, $settings, $model);
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
        $keep_updated = \Form::hidden($keep_updated_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox keep-updated' ), \Form::checkbox($keep_updated_setting, '1', \Arr::get($settings, 'keep_updated', true), array()).strtolower(\Lang::get('admin.common.auto_update')));
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