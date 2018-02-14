<?php

return array(

    'admin' => array(
        'title' => 'Website Administration',
        'interface_templates' => array(
            'default' => 'admin/shared/interface.twig',
            'inline' => 'admin/shared/chromeless.twig'
        )
    ),

    // Settings for the file manager
    'finder' => array(

        // Only supports LocalFileSystem for now
        'driver' => 'LocalFileSystem',

        // Upload path from public root
        'path' => '/uploads/editor/',

        // Root alias name
        'alias' => 'uploads',

        // File permissions
        'attributes' => array(
            array(
                'pattern' => '/\/\./',
                'read' => false,
                'write' => false,
                'hidden' => true
            )
        )
    ),

    'auth' => array(
        // Set the remember-me cookie lifetime, in seconds. The default
        // lifetime is two weeks.
        'lifetime' => 1209600,

        'default_role' => null,

        // Use these on top of the defaults: view, edit, create, delete
        'permissions' => array(''),

        // Additional resources to configure access to
        'resources' => array(
            // Whether they are able to log into the admin site
            'admin_site' => 'Admin Site'
        ),

        'http_authenticatable' => array(
            'in_use' => false,
            'method' => 'digest',
            'realm' => 'Protected by Sound in Theory',
            'users' => array(//'user' => 'password'
            )
        ),

        'recoverable' => array(
            'reset_password_within' => 86400, // 24-hours,
            'url' => 'admin/reset'
        ),

        'requirements' => array(
            'min_length' => 8,
            'max_length' => 128,
            'force_symbols' => true,
            'allowed_symbols' => '!£$%#@?+='
        ),
    ),

    'ffmpeg' => array(
        // These binaries are in varying positions depending on whether we use homebrew etc
        'ffmpeg_binary' => '/usr/local/bin/ffmpeg',
        'ffprobe_binary' => '/usr/local/bin/ffprobe',

        'default_framerate' => 25,
        'default_size' => array(
            'width' => 1280,
            'height' => 720
        )
    ),

    'cdn' => array(
        'enabled' => false,
        'base_url' => null,
        'sync' => array(
            'enabled' => true,
            'paths' => array(
                'assets'
            ),
            'exclude' => array(
                '/\.scss$/',
                '/\.less$/',
                '/\.mustache$/'
            )
        ),
        'adapter' => function () {
            // Return a flysystem adapter here
            return null;
        }
    ),

    'cache' => array(
        'enabled' => true,
        'driver' => 'auto',

        // Prevent these urls from being cached
        'excluded_urls' => array(
            '/admin/*',
            '/image/*'
        ),

        // Also check these files (or all files in a directory) for last modified date
        'check_files' => array(
            DOCROOT . 'assets'
        ),

        // Session vars to index the cache by
        'session_index' => array()
    ),

    // Maps field types to field classes
    'fields_types' => array(
        'string' => 'CMF\\Field\\Text',
        'random_key' => 'CMF\\Field\\RandomKey',
        'integer' => 'CMF\\Field\\Integer',
        'smallint' => 'CMF\\Field\\Integer',
        'bigint' => 'CMF\\Field\\Integer',
        'boolean' => 'CMF\\Field\\Checkbox',
        'decimal' => 'CMF\\Field\\Decimal',
        'datetime' => 'CMF\\Field\\DateTime',
        'date' => 'CMF\\Field\\Date',
        'time' => 'CMF\\Field\\Time',
        'text' => 'CMF\\Field\\Textarea',
        'color' => 'CMF\\Field\\Color',
        'richtext' => 'CMF\\Field\\CKEditor',
        'htaccess' => 'CMF\\Field\\Htaccess',
        'object' => 'CMF\\Field\\Object\\Object',
        'array' => 'CMF\\Field\\Object\\ArrayField',
        'float' => 'CMF\\Field\\Base',
        'binary' => 'CMF\\Field\\ReadOnly',
        'password' => 'CMF\\Field\\Password',
        'none' => 'CMF\\Field\\None',
        'manytoone' => 'CMF\\Field\\Relation\\ManyToOne',
        'onetoone' => 'CMF\\Field\\Relation\\OneToOne',
        'manytomany' => 'CMF\\Field\\Collection\\Multiselect',
        'onetomany' => 'CMF\\Field\\Collection\\Multiselect',
        'link' => 'CMF\\Field\\Object\\Link',
        'file' => 'CMF\\Field\\Object\\File',
        'image' => 'CMF\\Field\\Object\\Image',
        'videoembed' => 'CMF\\Field\\Object\\VideoEmbed',
        'video' => 'CMF\\Field\\Object\\Video',
        'enum' => 'CMF\\Field\\Select',
        'enum_integer' => 'CMF\\Field\\Select',
        'enum_float' => 'CMF\\Field\\Select',
        'enum_decimal' => 'CMF\\Field\\Select',
        'measurement' => 'CMF\\Field\\Measurement',
        'language' => 'CMF\\Field\\Language',
        'config' => 'CMF\\Field\\ConfigSelect',
        'array_config' => 'CMF\\Field\\Object\\ArrayConfig',
        'latlng' => 'CMF\\Field\\Object\\GoogleMap',
        'latlng_area' => 'CMF\\Field\\Object\\GoogleMap',
        'latlng_bing' => 'CMF\\Field\\Object\\BingMap',
        'latlng_bing_area' => 'CMF\\Field\\Object\\BingMap',
        'google_place' => 'CMF\\Field\\Object\\GooglePlace',

        // Associations with specific tables can be mapped...
        'onetoone_urls' => 'CMF\\Field\\Relation\\URL',
        'manytomany_permissions' => 'CMF\\Field\\Auth\\Permissions',

        // Associations with orphanRemoval=true can be mapped...
        'manytomany_inline' => 'CMF\\Field\\Collection\\TabularInline',
        'onetomany_inline' => 'CMF\\Field\\Collection\\TabularInline',
        'manytomany_inline_stacked' => 'CMF\\Field\\Collection\\StackedInline',
        'onetomany_inline_stacked' => 'CMF\\Field\\Collection\\StackedInline',
        'manytomany_inline_gallery' => 'CMF\\Field\\Collection\\GalleryInline',
        'onetomany_inline_gallery' => 'CMF\\Field\\Collection\\GalleryInline',
        'manytomany_inline_popup' => 'CMF\\Field\\Collection\\PopupInline',
        'onetomany_inline_popup' => 'CMF\\Field\\Collection\\PopupInline'
    ),

    'languages' => array(
        'enabled' => false,
        'use_tld' => false,
        'google_translate' => array(
            'api_key' => null,
            'base_url' => 'https://www.googleapis.com/language/translate/v2'
        ),
        'exclude_auto_translate' => array(
            'CMF\\Model\\User',
            'CMF\\Model\\Log',
            'CMF\\Model\\Language',
            'CMF\\Model\\Permission'
        ),
        'translatable_fields' => array(
            'string',
            'text',
            'richtext',
            'object',
            'link'
        )
    ),

);