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
	 * Fetch output
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
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch( $this->request['workact'] )
		{
			default:
			case 'step1':
				$this->fixCustomDatabases();

				$this->request['workact']	= 'step2';
			break;
			
			case 'step2':
				$this->fixDatabaseTemplates();

				$this->request['workact']	= 'step3';
			break;
			
			case 'step3':
				$this->importArticlesDatabase();

				$this->request['workact']	= '';
			break;
		}
		
		//-----------------------------------------
		// Return false if there's more to do
		//-----------------------------------------

		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Import the articles database
	 *
	 * @return	@e void
	 */
	public function importArticlesDatabase()
	{
		define( 'CCS_UPGRADE', true );
		
		require_once( IPSLib::getAppDir( 'ccs' ) . "/setup/articles.php" );/*noLibHook*/
		$_setupDb	= new setup_articles();
		$_setupDb->doExecute( $this->registry );
	}
	
	/**
	 * Fix database templates and containers
	 *
	 * @return	@e void
	 */
	public function fixDatabaseTemplates()
	{
		//-----------------------------------------
		// Update containers
		//-----------------------------------------
		
		$options		= IPSSetUp::getSavedData('custom_options');
		$containers		= $options['ccs'][20000]['containers'];
		$_containers	= explode( ',', $containers );
		
		if( $containers AND count($_containers) AND is_array($_containers) )
		{
			$this->DB->update( "ccs_containers", array( 'container_type' => "dbtemplate" ), 'container_id IN(' . $containers . ')' );
		}
		
		//-----------------------------------------
		// Update templates
		//-----------------------------------------
		
		$_templates		= unserialize( $options['ccs'][20000]['templates'] );
		
		if( count($_templates) )
		{
			foreach( $_templates as $templateId => $templateType )
			{
				$this->DB->update( "ccs_page_templates", array( 'template_database' => $templateType ), 'template_id=' . $templateId );
			}
		}
		
		$this->registry->output->addMessage( count($_containers) . " template containers updated, and " . count($_templates) . " database templates updated..." );
		
		//-----------------------------------------
		// And now import the new article templates
		//-----------------------------------------
		
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/demosite.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		$_includeTemplates	= array( 'frontpage', 'article_view', 'frontpage_blog_format', 'frontpage_single_column', 'article_archives', 'article_categories' );
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			if( !in_array( $_template['template_key'], $_includeTemplates ) )
			{
				continue;
			}
			
			unset($_template['template_id']);
			unset($_template['template_category']);
			
			$_template['template_updated']	= str_replace( '{--time--}', time(), $_template['template_updated'] );
			
			$this->DB->setTableIdentityInsert( 'ccs_page_templates', 'ON' );
			$this->DB->insert( "ccs_page_templates", $_template );
			$this->DB->setTableIdentityInsert( 'ccs_page_templates', 'OFF' );
		}
		
		//-----------------------------------------
		// Now rebuild cache
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pages	= new $classToLoad( $this->registry );
		$_pages->recacheTemplateCache();
		
		$this->registry->output->addMessage( "New articles templates imported..." );
	}
	
	/**
	 * Fix custom databases (add new fields)
	 *
	 * @return	@e void
	 */
	public function fixCustomDatabases()
	{
		//-----------------------------------------
		// Get databases
		//-----------------------------------------
		
		$databases	= array();
		
		$this->DB->build( array( 'select' => 'database_database, database_id', 'from' => 'ccs_databases' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$databases[]	= $r;
		}
		
		//-----------------------------------------
		// Loop over databases
		//-----------------------------------------
		
		if( count($databases) )
		{
			foreach( $databases as $database )
			{
				//-----------------------------------------
				// Fields
				//-----------------------------------------
				
				$this->DB->addField( $database['database_database'], "record_template", "INT", "0" );
				$this->DB->addField( $database['database_database'], "record_topicid", "INT", "0" );
				$this->DB->addField( $database['database_database'], "record_meta_keywords", "TEXT", NULL );
				$this->DB->addField( $database['database_database'], "record_meta_description", "TEXT", NULL );
				$this->DB->addField( $database['database_database'], "record_dynamic_furl", "VARCHAR( 255 )", NULL );
				$this->DB->addField( $database['database_database'], "record_static_furl", "VARCHAR( 255 )", NULL );
				
				//-----------------------------------------
				// Indexes
				//-----------------------------------------
				
				$this->DB->addIndex( $database['database_database'], "record_static_furl", "record_static_furl" );
				$this->DB->addIndex( $database['database_database'], "record_topicid", "record_topicid" );
			}
			
			$this->registry->output->addMessage( count($databases) . " custom databases updated with new table schematic..." );
		}
		else
		{
			$this->registry->output->addMessage( "No custom databases found (skipping custom database updates)..." );
		}
	}
}