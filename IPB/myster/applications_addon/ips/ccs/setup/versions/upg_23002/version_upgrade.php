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
				$this->addTeaserField();

				$this->request['workact']	= 'step3';
			break;
			
			case 'step3':
				$this->deleteOldBlocks();

				$this->request['workact']	= 'step4';
			break;

			case 'step4':
				$this->updateTemplates();

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
	 * Add the new article teaser field
	 *
	 * @return	@e void
	 */
	public function addTeaserField()
	{
		$articles	= $this->DB->buildAndFetch( array( 'select' => 'database_id, database_database', 'from' => 'ccs_databases', 'where' => "database_is_articles=1" ) );

		if( $articles['database_id'] )
		{
			$this->DB->insert( 'ccs_database_fields', array(
															'field_database_id'		=> $articles['database_id'],
															'field_name'			=> 'Teaser Paragraph',
															'field_description'		=> 'Enter a small teaser, shown in listings and at the start of the article',
															'field_key'				=> 'teaser_paragraph',
															'field_type'			=> 'editor',
															'field_user_editable'	=> 1,
															'field_position'		=> 1,
															'field_truncate'		=> 0,
							)								);
			
			$fieldId	= $this->DB->getInsertId();

			$this->DB->addField( $articles['database_database'], 'field_' . $fieldId, ' MEDIUMTEXT' );
		}
		
		$this->registry->output->addMessage( "Added article teaser paragraph field" );
	}
	
	/**
	 * Update database and article templates
	 * 
	 * @return	@e void
	 */
 	public function updateTemplates()
 	{
		//-----------------------------------------
		// Update containers
		//-----------------------------------------
		
		$options	= IPSSetUp::getSavedData('custom_options');
		$update		= $options['ccs'][23002]['updateTemplates'];
		
		if( !$update )
		{
			$this->registry->output->addMessage( "Skipped updating default database and article templates..." );
			return;
		}

		//-----------------------------------------
		// And now import the default templates
		//-----------------------------------------
		
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/demosite.xml' );
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );

		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			unset($_template['template_id']);
			unset($_template['template_category']);
			
			$_template['template_updated']	= str_replace( '{--time--}', time(), $_template['template_updated'] );

			$this->DB->update( "ccs_page_templates", $_template, "template_key='{$_template['template_key']}'" );

			$thisTemplate	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_key='{$_template['template_key']}'" ) );

			if( $thisTemplate['template_id'] )
			{
				//-----------------------------------------
				// Recache the template
				//-----------------------------------------
				
				$cache	= array(
								'cache_type'	=> 'template',
								'cache_type_id'	=> $thisTemplate['template_id'],
								);

				$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $_template['template_key'], $_template['template_database'] ? '$data' : '', $_template['template_content'], '', false, true );
				
				$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$thisTemplate['template_id']}" ) );
				
				if( $hasIt['cache_id'] )
				{
					$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$thisTemplate['template_id']}" );
				}
				else
				{
					$this->DB->insert( 'ccs_template_cache', $cache );
				}
			}
		}
		
		//-----------------------------------------
		// Now rebuild cache
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pages	= new $classToLoad( $this->registry );
		$_pages->recacheTemplateCache( $engine );
		
		$this->registry->output->addMessage( "Database and article templates updated..." );
 	}
	
	/**
	 * Delete watched content blocks
	 *
	 * @return	@e void
	 */
	public function deleteOldBlocks()
	{
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => "block_type='plugin'" ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$_config	= unserialize( $r['block_config'] );
			
			if( $_config['plugin'] == 'watched_content' )
			{
				$this->DB->delete( 'ccs_blocks', "block_id=" . $r['block_id'] );
				$_t	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='block__watched_content_{$r['block_id']}'" ) );
				$this->DB->delete( 'ccs_template_blocks', "tpb_id=" . $_t['tpb_id'] );
				$this->DB->delete( 'ccs_template_cache', "cache_type='block' AND cache_type_id=" . $_t['tpb_id'] );
			}
		}
		
		$this->registry->output->addMessage( "Removed outdated 'Watched Content' blocks" );
	}
	
	/**
	 * Fix custom databases (reset values/permissions as needed)
	 *
	 * @return	@e void
	 */
	public function fixCustomDatabases()
	{
		//-----------------------------------------
		// Get databases
		//-----------------------------------------
		
		$databases	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases' ) );
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
				// Permissions
				//-----------------------------------------
				
				$_permissionIndex	= array();
				
				if( !$database['database_open'] )
				{
					$_permissionIndex['perm_view']	= '';
				}

				if( !$database['database_comments'] )
				{
					$_permissionIndex['perm_5']	= '';
				}

				if( !$database['database_rate'] )
				{
					$_permissionIndex['perm_6']	= '';
				}

				if( !$database['database_user_editable'] )
				{
					$_permissionIndex['perm_3']	= '';
					$_permissionIndex['perm_4']	= '';
				}

				if( count($_permissionIndex) )
				{
					$this->DB->update( 'permission_index', $_permissionIndex, "app='ccs' AND perm_type='databases' AND perm_type_id={$database['database_id']}" );
				}
				
				//-----------------------------------------
				// RSS
				//-----------------------------------------
				
				if( $database['database_rss'] )
				{
					$_values	= explode( ';', $database['database_rss'] );
					$this->DB->update( 'ccs_databases', array( 'database_rss' => $_values[0] ? intval($_values[2]) : 0 ), 'database_id=' . $database['database_id'] );
				}
				
				//-----------------------------------------
				// Add field
				//-----------------------------------------
				
				$this->DB->addField( $database['database_database'], 'record_comments_queued', 'int', '0' );
			}
			
			//-----------------------------------------
			// Now tackle categories
			//-----------------------------------------
		
			$categories	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_categories' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$categories[]	= $r;
			}
			
			if( count($categories) )
			{
				foreach( $categories as $cat )
				{
					if( $cat['category_rss'] )
					{
						$_values	= explode( ';', $cat['category_rss'] );
						$this->DB->update( 'ccs_database_categories', array( 'category_rss' => $_values[0] ? intval($_values[2]) : 0, 'category_rss_exclude' => ( intval($_values[3]) > 0 ) ? 1 : 0 ), 'category_id=' . $cat['category_id'] );
					}
				}
			}
			
			$this->registry->output->addMessage( count($databases) . " custom databases updated..." );
		}
		else
		{
			$this->registry->output->addMessage( "No custom databases found (skipping custom database updates)..." );
		}
		
		//-----------------------------------------
		// Make the actual database changes
		//-----------------------------------------
		
		$this->DB->changeField( 'ccs_databases', 'database_rss', 'database_rss', 'INT', '0' );
		$this->DB->changeField( 'ccs_database_categories', 'category_rss', 'category_rss', 'INT', '0' );
		$this->DB->dropField( 'ccs_databases', 'database_open' );
		$this->DB->dropField( 'ccs_databases', 'database_comments' );
		$this->DB->dropField( 'ccs_databases', 'database_rate' );
		$this->DB->dropField( 'ccs_databases', 'database_user_editable' );
	}
}