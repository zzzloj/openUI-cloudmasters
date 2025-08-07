<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

class app_class_form
{
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;

	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
        
		$registry->cache()->getCache( array( 'forms', 'form_fields' ) );
        
		if( !IN_ACP )
		{			
            $registry->getClass('class_localization')->loadLanguageFile( array( 'public_form' ), 'form' );
		}        
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'form' ) . "/sources/forms.php", 'class_forms', 'form' );		
		$registry->setClass( 'formForms', new $classToLoad( $registry ) );   
        $registry->getClass( 'formForms' )->init(); 
        
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'form' ) . "/sources/fields.php", 'class_fields', 'form' );		
		$registry->setClass( 'formFields', new $classToLoad( $registry ) );   
        $registry->getClass( 'formFields' )->init();    
        
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'form' ) . "/sources/functions.php", 'formFunctions', 'form' );
		$registry->setClass( 'formFunctions', new $classToLoad( $registry ) );        
               
	}
}