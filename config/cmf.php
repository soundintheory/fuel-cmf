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
        ),
        
    ),
    
    'cache' => array(
        
        'enabled' => true,
        'driver' => 'auto',
        
        // Prevent these urls from being cached
        'excluded_urls' => array(
            '/admin/*',
            '/image/*'
        ),
        
        // Session vars to index the cache by
        'session_index' => array()
        
    ),
    
    // Maps field types to field classes
    'fields_types' => array(
        
        'string' => 'CMF\\Field\\Base',
        'integer' => 'CMF\\Field\\Integer',
        'smallint' => 'CMF\\Field\\Integer',
        'bigint' => 'CMF\\Field\\Integer',
        'boolean' => 'CMF\\Field\\Checkbox',
        'decimal' => 'CMF\\Field\\Base',
        'datetime' => 'CMF\\Field\\DateTime',
        'date' => 'CMF\\Field\\Date',
        'time' =>'CMF\\Field\\DateTime',
        'text' => 'CMF\\Field\\Textarea',
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
        'fileobject' => 'CMF\\Field\\Object\\FileObject',
        'imageobject' => 'CMF\\Field\\Object\\ImageObject',
        'link' => 'CMF\\Field\\Object\\Link',
        'file' => 'CMF\\Field\\File',
        'image' => 'CMF\\Field\\Image',
        
        // Associations with specific tables can be mapped...
        'onetoone_urls' => 'CMF\\Field\\Relation\\URL',
        'manytomany_permissions' => 'CMF\\Field\\Auth\\Permissions',
        
        // Associations with orphanRemoval=true can be mapped...
        'manytomany_inline' => 'CMF\\Field\\Collection\\TabularInline',
        'onetomany_inline' => 'CMF\\Field\\Collection\\TabularInline'
    ),
    
);