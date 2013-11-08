<?php

return array (
    
    'admin' => array(
        'title' => 'Website Administration',
        'interface_templates' => array(
            'default' => 'admin/shared/interface.twig',
            'inline' => 'admin/shared/chromeless.twig'
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
            'in_use'   => false,
            'method' => 'digest',
            'realm'  => 'Protected by Sound in Theory',
            'users' => array(
                //'user' => 'password'
            )
        )
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
            DOCROOT.'assets'
        ),
        
        // Session vars to index the cache by
        'session_index' => array()
    ),
    
    // Maps field types to field classes
    'fields_types' => array(
        'string' => 'CMF\\Field\\Text',
        'integer' => 'CMF\\Field\\Integer',
        'smallint' => 'CMF\\Field\\Integer',
        'bigint' => 'CMF\\Field\\Integer',
        'boolean' => 'CMF\\Field\\Checkbox',
        'decimal' => 'CMF\\Field\\Base',
        'datetime' => 'CMF\\Field\\DateTime',
        'date' => 'CMF\\Field\\Date',
        'time' =>'CMF\\Field\\DateTime',
        'text' => 'CMF\\Field\\Textarea',
        'color' => 'CMF\\Field\\Color',
        'richtext' => 'CMF\\Field\\Redactor',
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
        
        // Associations with specific tables can be mapped...
        'onetoone_urls' => 'CMF\\Field\\Relation\\URL',
        'manytomany_permissions' => 'CMF\\Field\\Auth\\Permissions',
        
        // Associations with orphanRemoval=true can be mapped...
        'manytomany_inline' => 'CMF\\Field\\Collection\\TabularInline',
        'onetomany_inline' => 'CMF\\Field\\Collection\\TabularInline',
        'manytomany_inline_stacked' => 'CMF\\Field\\Collection\\StackedInline',
        'onetomany_inline_stacked' => 'CMF\\Field\\Collection\\StackedInline',
        'manytomany_inline_gallery' => 'CMF\\Field\\Collection\\GalleryInline',
        'onetomany_inline_gallery' => 'CMF\\Field\\Collection\\GalleryInline'
    ),
    
    'languages' => array(
        'enabled' => false,
        'translatable_fields' => array(
            'string',
            'text',
            'richtext'
        )
    ),
    
);