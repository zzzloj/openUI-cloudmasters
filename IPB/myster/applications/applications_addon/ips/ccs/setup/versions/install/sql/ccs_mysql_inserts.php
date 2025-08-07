<?php

$INSERT	= array();

$INSERT[]	= "INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, extra_data, lockd, app) VALUES(1, 'IP.Content Plugin', 'Allows reports of database records and comments', 'Invision Power Services, Inc.', 'http://invisionpower.com', 'v1.0', 'ccs', 'N;', 1, 'ccs');";


class ccs_templates
{
	/**#@+
	 * Registry objects
	 *
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		
		/* Block templates */
		$this->_importTemplates();
		
		/* Default site */
		$this->_importSite();
		
		/* Databases */
		$this->_importDatabase();
	}
	
	/**
	 * Import the block templates
	 *
	 * @return	@e void
	 * @see		admin_ccs_templates_blocks::importTemplates()
	 */
	protected function _importTemplates()
	{
		//-----------------------------------------
		// First we do the plugin block templates
		//-----------------------------------------
		
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/plugin_block_templates.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			if( $_template['tpb_name'] )
			{
				$this->DB->insert( "ccs_template_blocks", $_template );
			}
		}

		//-----------------------------------------
		// Now the feed block templates
		//-----------------------------------------

		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/feed_block_templates.xml' );

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/functions.php', 'ccsFunctions', 'ccs' );
		$functions		= new $classToLoad( $this->registry );

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive	= new classXMLArchive();

		//-----------------------------------------
		// Read the XML archive
		//-----------------------------------------
		
		$xmlarchive->readXML( $content );

		foreach( $xmlarchive->asArray() as $fileName => $fileMeta )
		{
			if( $fileName == 'btemplates_data.xml' )
			{
				$templateData = $fileMeta['content'];
			}
			else
			{
				$templateResources[ $fileMeta['path'] ][ $fileMeta['filename'] ] = $fileMeta['content'];
			}
		}

		//-----------------------------------------
		// Import the actual XML
		//-----------------------------------------

		$return = $functions->importBlockTemplates( $templateData, $templateResources, true );

		if( count( $return['success'] ) )
		{
			//-----------------------------------------
			// Skin file is recached later, so just recache
			// our static resources
			//-----------------------------------------

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$functions		= new $classToLoad( $this->registry );

			$functions->recacheResources();
		}

		//-----------------------------------------
		// Ignore errors here - shouldn't happen with defaults anyways
		//-----------------------------------------
	}
	
	/**
	 * Now we get to import the default site.  Fun!
	 *
	 * @return	@e void
	 */
	protected function _importSite()
	{
		//-----------------------------------------
		// Get the data from XML
		//-----------------------------------------
		
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/demosite.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Blocks
		//-----------------------------------------
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );

			$this->DB->insert( "ccs_blocks", $_block );
		}
		
		//-----------------------------------------
		// Containers
		//-----------------------------------------
		
		foreach( $xml->fetchElements('container') as $container )
		{
			$_container	= $xml->fetchElementsFromRecord( $container );

			$this->DB->insert( "ccs_containers", $_container );
		}

		//-----------------------------------------
		// Templates
		//-----------------------------------------
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			$_template['template_updated']	= str_replace( '{--time--}', time(), $_template['template_updated'] );
			
			$this->DB->insert( "ccs_page_templates", $_template );
		}
		
		//-----------------------------------------
		// Pages
		//-----------------------------------------
		
		foreach( $xml->fetchElements('page') as $page )
		{
			$_page	= $xml->fetchElementsFromRecord( $page );

			$_page['page_last_edited']	= str_replace( '{--time--}', time(), $_page['page_last_edited'] );
			
			$this->DB->insert( "ccs_pages", $_page );
		}
		
		//-----------------------------------------
		// Block templates
		//-----------------------------------------
		
		foreach( $xml->fetchElements('tblock') as $tblock )
		{
			$_tblock	= $xml->fetchElementsFromRecord( $tblock );

			unset($_tblock['tpb_id']);
			
			$this->DB->insert( "ccs_template_blocks", $_tblock );
		}
		
		//-----------------------------------------
		// Rebuild normal template caches
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );/*noLibHook*/
		$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			$cache	= array(
							'cache_type'	=> 'template',
							'cache_type_id'	=> $r['template_id'],
							);
	
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
		}
		
		//-----------------------------------------
		// And rebuild the rest...
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass = new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );

		//-----------------------------------------
		// Done?
		//-----------------------------------------
		
		return true;
	}
	
	/**
	 * Insert the default database stuff too...
	 *
	 * @return	@e void
	 */
	public function _importDatabase()
	{
		define( 'CCS_UPGRADE', false );
		
		require_once( IPSLib::getAppDir( 'ccs' ) . "/setup/articles.php" );/*noLibHook*/
		$_setupDb	= new setup_articles();
		$_setupDb->doExecute( $this->registry );
	}
}

$templateInstall = new ccs_templates();
