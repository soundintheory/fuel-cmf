<?php

return array (
    
    'admin' => array(
        
        'title' => 'New Website',
        
        'sidebar' => array(
            
            array( 'heading' => '' ),
            array( 'model' => 'Model_Page' ),
            array( 'model' => 'Model_Settings' ),
            
            array( 'heading' => '' ),
            array( 'model' => 'Admin\\Model_User' ),
            array( 'model' => 'CMF\\Model\\Role' )
            
        )
        
    )
    
);