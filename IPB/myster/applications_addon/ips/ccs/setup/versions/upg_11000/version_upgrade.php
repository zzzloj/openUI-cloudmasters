<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->_importDbStuff();
		
		$this->request['workact']	= '';
		return true;
	}
	
	/**
	 * Run SQL files
	 * 
	 * @param	int
	 */
	public function _importDbStuff()
	{
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/demosite.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		foreach( $xml->fetchElements('container') as $container )
		{
			$_container	= $xml->fetchElementsFromRecord( $container );

			//-----------------------------------------
			// DB category is id 2
			//-----------------------------------------
			
			if( $_container['container_id'] == 2 )
			{
				unset($_container['container_id']);
				$this->DB->insert( "ccs_containers", $_container );
				$_templateId	= $this->DB->getInsertId();
			}
		}

		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			//-----------------------------------------
			// DB templates are ID 2 and 3
			//-----------------------------------------
			
			if( $_template['template_key'] == 'generic_database_view' OR $_template['template_key'] == 'generic_database_categories' )
			{
				unset($_template['template_id']);
				$_template['template_updated']	= str_replace( '{--time--}', time(), $_template['template_updated'] );
				$_template['template_category']	= $_templateId;
				
				$this->DB->insert( "ccs_page_templates", $_template );
			}
		}
		
		//-----------------------------------------
		// Now recache new page templates
		//-----------------------------------------
		
		$templates	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$cache	= array(
							'cache_type'	=> 'template',
							'cache_type_id'	=> $r['template_id'],
							);
	
			require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );/*noLibHook*/
			$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
			$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $r['template_key'], $r['template_database'] ? '$data' : '', $r['template_content'], '', false, true );
			
			$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$r['template_id']}" ) );
			
			if( $hasIt['cache_id'] )
			{
				$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$r['template_id']}" );
			}
			else
			{
				$this->DB->insert( 'ccs_template_cache', $cache );
			}
	
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass = new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache( $engine );
		}
	}	
}