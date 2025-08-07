<?php

/**
* Tracker 2.1.0
* 
* Modules Javascript PHP Interface
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_tracker_ajax_modules extends ipsAjaxCommand 
{
	/**
	 * Skin functions object handle
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * HTML Skin object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $html;
	
	/**
	 * Main executable
	 *
	 * @access	public
	 * @param	object	registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Check for hacks */
		$oldName					 = $this->request['moduleName'];
		$this->request['moduleName'] = preg_replace( "#[^a-zA-Z]#", '', $this->request['moduleName'] );
		
		if ( ! $this->request['moduleName'] OR $oldName != $this->request['moduleName'] )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not locate the correct module'));
			return;
		}
		
		/* Load our module data */
		$folder = IPSLib::getAppDir('tracker') . '/modules/' . $this->request['moduleName'] . '/';
		
		/* XML Files? */
		if ( ! file_exists( $folder . 'xml/information.xml' ) )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not locate the correct module'));
			return;
		}
		
		/* Load the content of the file */
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( file_get_contents($folder . 'xml/information.xml') );
		
		foreach( $xml->fetchElements('data') as $data )
		{
			$data	= $xml->fetchElementsFromRecord( $data );
		}
		
		/* Store it */
		$this->module = $data;
		
		/* Sequence of Events:
			# SQL
			# App Module
			# Check for more modules
			# Templates
			# Languages
			# Tasks
			# Settings
			# Template Cache
			# Caches / Done
		*/		
		switch( $this->request['do'] )
		{
			case 'start':
				$this->start();
				break;
			case 'sql':
				$this->sqlBasics();
				break;
			case 'sql_steps':
				$this->sqlSteps();
				break;
			case 'next_check':
				$this->nextCheck();
				break;
			
			case 'templates':
				$this->templates();
				break;
			case 'languages':
				$this->languages();
				break;
			case 'tasks':
				$this->tasks();
				break;
			case 'help':
				$this->help();
				break;
			case 'settings':
				$this->settings();
				break;
			case 'tplcache':
				$this->recacheTemplates();
				break;
			case 'finish':
				$this->finish();
				break;
			
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
			break;
		}
	}
	
	/**
	 * Rebuild PHP Templates Cache
	 *
	 * @access	public
	 * @return	void
	 */
	public function recacheTemplates()
	{
		//-----------------------------------------
		// Determine if we need to recache templates
		//-----------------------------------------
		
		$vars		= $this->getVars();
		$hasSkin	= false;
		
		if ( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_replacements.xml' ) )
		{
			$hasSkin	= true;
		}
		
		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_root_templates.xml' ) OR
				file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_lofi_templates.xml' ) OR
				file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_xmlskin_templates.xml' ) )
			{
				$hasSkin	= true;
			}
		}

		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_root_css.xml' ) OR
				file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_lofi_css.xml' ) OR
				file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_xmlskin_css.xml' ) )
			{
				$hasSkin	= true;
			}
		}
		
		if( !$hasSkin )
		{
			$this->returnJsonArray(
				array(
					'totalSteps' 	=> 4,
					'step'			=> 1,
					'status'		=> 'continue',
					'stepStatus'	=> 'Inserting SQL into Databases',
					'url'			=> '&do=modules'
				)
			);
			$this->showRedirectScreen( $vars['module_directory'], array( $this->lang->words['redir__no_template_re'] ) , '', $this->getNextURL( 'finish', $vars ) );
		}
		
		/* INIT */
		$setID = intval( $this->request['setID'] );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		
		$skinFunctions = new skinImportExport( $this->registry );
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );	
		
		/* Get first set id */
		if( ! $setID )
		{
			ksort( $this->registry->output->allSkins );
			$_skins = $this->registry->output->allSkins;
			$_set   = array_shift( $_skins );
			$setID  = $_set['set_id'];
		}

		/* Rebuild */
		$skinFunctions->rebuildPHPTemplates( $setID );
		$skinFunctions->rebuildCSS( $setID );
		$skinFunctions->rebuildReplacementsCache( $setID );
		$skinFunctions->rebuildSkinSetsCache();
				
		/* Fetch next id */
		$nextID = $setID;
		
		ksort( $this->registry->output->allSkins );
		
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			if ( $id > $nextID )
			{
				$nextID = $id;
				break;
			}
		}
		if ( $nextID != $setID )
		{
			$this->showRedirectScreen( $vars['module_directory'], array( $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] ), '', $this->getNextURL( 'tplcache&amp;setID=' . $nextID, $vars ) );
		}
		else
		{
			$this->showRedirectScreen( $vars['module_directory'], array( $this->lang->words['to_recache_done'] ) , '', $this->getNextURL( 'finish', $vars ) );
		}

	}	
	
	/**
	 * Finalizes installation and rebuilds caches
	 *
	 * @access	public
	 * @return	void
	 **/
	public function finish()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = TrackerModules::fetchXmlModuleInformation( $vars['module_directory'] );
		$_numbers  = TrackerModules::fetchModuleVersionNumbers( $vars['module_directory'] );
		
		/* Grab Data */
		$data['module_directory'] = $vars['module_directory'];
		$data['current_version']  = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']   = $_numbers['latest'][1];
		$data['next_version']     = $_numbers['next'][0];
		
		/* Rebuild modules and modules cache */
		//$this->cache->rebuildCache( 'module_cache' );
		$this->cache->rebuildCache( 'group_cache', 'global' );

		/* Rebuild module specific caches */
		$_file = $this->module_full_path . 'extensions/coreVariables.php';
			
		if( file_exists( $_file ) )
		{
			require( $_file );
			
			if( is_array( $CACHE ) AND count( $CACHE ) )
			{
				foreach( $CACHE as $key => $cdata )
				{
					$this->cache->rebuildCache( $key, $vars['module_directory'] );
				}
			}
		}		
		
		/* Show completed screen... */
		$this->returnJsonArray(
			array(
				'step'			=> 1,
				'status'		=> 'continue',
				'stepStatus'	=> 'Inserting SQL into Databases',
				'url'			=> '&do=modules'
			)
		);
	}
	
	/**
	 * Next Check
	 *
	 * @access	public
	 * @return	void
	 **/
	public function nextCheck()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $vars['module_directory'] );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $vars['module_directory'] );
		$modules   = IPSSetUp::fetchXmlAppModules( $vars['module_directory'] );
		
		/* Grab Data */
		$data['module_directory']   = $vars['module_directory'];
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Update the module DB */
		if( $vars['type'] == 'install' )
		{
			/* Get current max position */
			$pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(module_position) as pos', 'from' => 'core_modules' ) );
			$new_pos = intval( $pos['pos'] ) + 1;
			
			/* Insert details into the DB */
			$this->DB->insert( 'core_modules', array( 
																'module_title'			=> $this->product_information['name'],
																'module_public_title'	=> $this->product_information['public_name'],
																'module_author'		=> $this->product_information['author'],
																'module_description'	=> $this->product_information['description'],
																'module_version'		=> $_numbers['latest'][1],
																'module_long_version'	=> $_numbers['latest'][0],
																'module_directory'		=> $vars['module_directory'],
																'module_location'		=> $vars['module_location'],
																'module_added'			=> time(),
																'module_position'		=> $new_pos,
																'module_protected'		=> 0,
																'module_enabled'		=> $this->product_information['disabledatinstall'] ? 0 : 1
															)
								);
								
			$this->DB->insert( 'upgrade_history', array( 
														'upgrade_version_id'	=> $_numbers['latest'][0],
														'upgrade_version_human'	=> $_numbers['latest'][1],
														'upgrade_date'			=> time(),
														'upgrade_notes'			=> '',
														'upgrade_mid'			=> $this->memberData['member_id'],
														'upgrade_module'			=> $vars['module_directory']
												)	);
			
			/* Insert the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->insert( 'core_sys_module', $module );
			}
		}
		else
		{
			$this->DB->update( 'core_modules', array( 
															'module_version'      => $_numbers['current'][1],
															'module_long_version' => $_numbers['current'][0] 
							), "module_directory='" . $vars['module_directory'] . "'" );
			
			/* Update the modules */
			foreach( $modules as $key => $module )
			{
				$this->DB->update( 'core_sys_module', $module, "sys_module_module='{$module['sys_module_module']}' AND sys_module_key='{$module['sys_module_key']}'" );
			}
		}
		
		/* Finish? */
		if( $vars['type'] == 'install' OR $vars['version'] == $_numbers['latest'][0] )
		{
			/* Go back and start over with the new version */
			$output[] = $this->lang->words['redir__nomore_modules'];

			$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'templates', $vars ) );
		}
		else
		{
			/* Go back and start over with the new version */
			$output[] = sprintf( $this->lang->words['redir__upgraded_to'], $_numbers['current'][1] );
			
			/* Work out the next step */
			$vars['version'] = $_numbers['next'][0];
			
			$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'sql', $vars ) );
		}
	}

	/**
	 * Import Settings
	 *
	 * @access	public
	 * @return	void
	 **/
	public function settings()
	{
		/* INIT */
		$vars          = $this->getVars();
		$output        = array();
		$errors        = array();
		$knownSettings = array();
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		
		if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_settings.xml' ) )
		{
			/* Get the settings class */			
			require_once( IPS_ROOT_PATH . 'modules/core/modules_admin/tools/settings.php' );
			$settings = new admin_core_tools_settings( $this->registry );
			$settings->makeRegistryShortcuts( $this->registry );
			
			$this->request['module_dir'] = $vars['module_directory'];
			
			//-----------------------------------------
			// Known settings
			//-----------------------------------------

			if ( substr( $this->settings['_original_base_url'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_url', substr( $this->settings['_original_base_url'], 0, -1 ) );
			}
			
			if ( substr( $this->settings['base_dir'], -1 ) == '/' )
			{
				IPSSetUp::setSavedData('install_dir', substr( $this->settings['base_dir'], 0, -1 ) );
			}
			
			/* Fetch known settings  */
			if ( file_exists( IPSLib::getAppDir( $vars['module_directory'] ) . '/setup/versions/install/knownSettings.php' ) )
			{
				require( IPSLib::getAppDir( $vars['module_directory'] ) . '/setup/versions/install/knownSettings.php' );
			}
			
			$settings->importAllSettings( 1, 1, $knownSettings );
			$settings->settingsRebuildCache();
		}
		else
		{
			$this->registry->output->global_message	= $this->lang->words['settings_nofile'];
		}
		
		$output[] = $this->registry->output->global_message;
		
		/* Clear main messaage */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'hooks', $vars ) );
	}
	
	/**
	 * Import tasks
	 *
	 * @access	public
	 * @return	void
	 **/
	public function tasks()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_tasks.xml' ) )
		{
			/* Get the language class */
			require_once( IPS_ROOT_PATH . 'modules/core/modules_admin/system/taskmanager.php' );
			$task_obj = new admin_core_system_taskmanager( $this->registry );
			$task_obj->makeRegistryShortcuts( $this->registry );
			
			$task_obj->tasksImportFromXML( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_tasks.xml', true );
		}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['no_tasks_for_import'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'bbcode', $vars ) );
	}

	
	/**
	 * Import help
	 *
	 * @access	public
	 * @return	void
	 **/
	public function help()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_help.xml' ) )
		{
			require_once( IPS_ROOT_PATH . 'modules/core/modules_admin/tools/help.php' );
			$help = new admin_core_tools_help();
			$help->makeRegistryShortcuts( $this->registry );

			$done = $help->helpFilesXMLImport_module( $vars['module_directory'] );
			
			$output[] = sprintf( $this->lang->words['imported_x_help'], ($done['added'] + $done['updated']) );
		}
		else
		{
			$output[] = $this->lang->words['imported_no_help'];
		}
		

		/* Show redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'settings', $vars ) );
	}
	
	/**
	 * Language Import
	 *
	 * @access	public
	 * @return	void
	 **/
	public function languages()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		/* Load the language file */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ) );
		
		/* Get the language stuff */
		require_once( IPS_ROOT_PATH . 'modules/core/modules_admin/languages/manage_languages.php' );
		$lang = new admin_core_languages_manage_languages( $this->registry );		
		$lang->makeRegistryShortcuts( $this->registry );	
			
		/* Loop through the xml directory and look for lang packs */
		$_PATH = $this->module_full_path . '/xml/';		
		
		try
		{
			foreach( new DirectoryIterator( $_PATH ) as $f )
			{
				if( preg_match( "#(.+?)_language_pack.xml#", $f->getFileName() ) )
				{
					$this->request['file_location'] = $_PATH . $f->getFileName();
					$lang->imprtFromXML( true, true, true, $vars['module_directory'] );				
				}
			}
		} catch ( Exception $e ) {}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['redir__nolanguages'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'tasks', $vars ) );
	}
	
	/**
	 * Install templates
	 *
	 * @access	public
	 * @return	void
	 **/
	public function templates()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );
		$skinFunctions	= new skinImportExport( $this->registry );
		$skinCaching	= new skinCaching( $this->registry );
		
		/* Grab skin data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Bit of jiggery pokery... */
			if ( $row['set_key'] == 'default' )
			{
				$row['set_key'] = 'root';
				$row['set_id']  = 0;
			}
			
			$skinSets[ $row['set_key'] ] = $row;
		}
			
		foreach( $skinSets as $skinKey => $skinData )
		{
			/* Skin files first */
			if( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_' . $skinKey . '_templates.xml' ) )
			{
				$return = $skinFunctions->importTemplateAppXML( $vars['module_directory'], $skinKey, $skinData['set_id'], TRUE );
				
				$output[] = sprintf( $this->lang->words['redir__templates'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* Then CSS files */
			if ( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_' . $skinKey . '_css.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importCSSAppXML( $vars['module_directory'], $skinKey, $skinData['set_id'] );
				
				$output[] = sprintf( $this->lang->words['redir__cssfiles'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* And we can support replacements for good measure */
			if ( file_exists( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_replacements.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importReplacementsXMLArchive( $this->module_full_path . 'xml/' . $vars['module_directory'] . '_' . $skinKey . '_replacements.xml' );
				
				$output[] = $this->lang->words['redir__replacements'];
			}
		}
		
		/* Recache */
		//$skinCaching->rebuildPHPTemplates( 0 );
		//$skinCaching->rebuildCSS( 0 );
		//$skinCaching->rebuildReplacementsCache( 0 );

		/* Show redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'languages', $vars ) );
	}
	
	/**
	 * Runs any additional sql files
	 *
	 * @access	public
	 * @return	void
	 **/
	public function sqlSteps()
	{
		/* INIT */
		$vars      = $this->getVars();
		$output    = array();
		$errors    = array();
		$id        = intval( $this->request['id'] );
		$id        = ( $id < 1 ) ? 1 : $id;
		$sql_files = array();
		
		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}
		
		
		/* Run any sql files we found */
		if( file_exists( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' ) )
		{
			/* INIT */
			$new_id = $id + 1;
			$count  = 0;
			
			/* Get the sql file */
			require_once( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' );

			$this->DB->return_die = 1;
			
			/* Run the queries */
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';

				$query = str_replace( "<%time%>", time(), $query );
				
				if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
				{
					 $query = $extra_install->process_query_insert( $query );
				}
				
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$errors[] = $query."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}				
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_run'], $count );
			
			/* Show redirect... */
			$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) . '&amp;id=' . $new_id );
		}
		else
		{
			$output[] = $this->lang->words['redir__nomore_sql'];

			/* Show redirect... */
			$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'next_check', $vars ) );
		}
	}
	
	/**
	 * Creates Tables, Runs Inserts, and Indexes
	 *
	 * @access	public
	 * @return	void
	 **/
	public function sqlBasics()
	{
		/* INIT */
		$vars		= $this->getVars();
		$output		= array();
		$errors		= array();
		$skipped	= 0;
		$count		= 0;

		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . strtolower( $this->settings['sql_driver'] ) . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}

		//-----------------------------------------
		// Tables
		//-----------------------------------------
		
		$this->DB->return_die = 1;

		if ( file_exists( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' ) )
		{
			include( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' );

			if ( is_array( $TABLE ) and count( $TABLE ) )
			{
				foreach( $TABLE as $q )
				{
					//-----------------------------------------
					// Is this a create?
					//-----------------------------------------
					
					preg_match("/CREATE TABLE (\S+)(\s)?\(/", $q, $match);

					if( $match[1] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropTable( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ) );
					}
					else if( $match[1] )
					{
						if( $this->DB->getTableSchematic( $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
					
					//-----------------------------------------
					// Is this an alter?
					//-----------------------------------------
					
					preg_match("/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(\S+)\s+/i", $q, $match);

					if( $match[1] AND $match[2] AND $vars['dupe_tables'] == 'drop' )
					{
						$this->DB->dropField( str_replace( $this->settings['sql_tbl_prefix'], '', $match[1] ), $match[2] );
					}
					else if( $match[1] AND $match[2] )
					{
						if( $this->DB->checkForField( $match[2], $match[1] ) )
						{
							$skipped++;
							continue;
						}
					}
		
					if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
					{
						 $q = $extra_install->process_query_create( $q );
					}
					$this->DB->error = '';
				
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_tables'], $count, $skipped );
		}
		
		//---------------------------------------------
		// Create the fulltext index...
		//---------------------------------------------

		if ( $this->DB->checkFulltextSupport() )
		{
			if ( file_exists( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' ) )
			{
				include( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' );
				
				$count	= 0;
	
				foreach( $INDEX as $q )
				{
					//---------------------------------------------
					// Pass to handler
					//---------------------------------------------
					
					if ( $extra_install AND method_exists( $extra_install, 'process_query_index' ) )
					{
						$q = $extra_install->process_query_index( $q );
					}
					
					//---------------------------------------------
					// Pass query
					//---------------------------------------------
					$this->DB->error = '';
					$this->DB->query( $q );
					
					if ( $this->DB->error )
					{
						$errors[] = $q."<br /><br />".$this->DB->error;
					}
					else
					{
						$count++;
					}
				}
				
				$output[] = sprintf( $this->lang->words['redir__sql_indexes'], $count );
			}
		}
		
		/* INSERTS */
		if ( file_exists( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' ) )
		{
			$count   = 0;
			
			/* Get the SQL File */
			include( $this->module_full_path . 'setup/versions/install/sql/' . $vars['module_directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' );
			
			foreach( $INSERT as $q )
			{
				/* Extra Handler */
			 	if( $extra_install AND method_exists( $extra_install, 'process_query_insert' ) )
			 	{
					$q = $extra_install->process_query_insert( $q );
				}
				
				$q = str_replace( "<%time%>", time(), $q );
				$this->DB->error = '';
				$this->DB->query( $q );
				
				if ( $this->DB->error )
				{
					$errors[] = $q."<br /><br />".$this->DB->error;
				}
				else
				{
					$count++;
				}
			}
			
			$output[] = sprintf( $this->lang->words['redir__sql_inserts'], $count );
		}

		/* Show Redirect... */
		$this->showRedirectScreen( $vars['module_directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) );
	}
	
	/**
	 * Begin installation
	 *
	 * @access	public
	 * @return	void
	 **/
	public function start()
	{
		/* INIT */
		$module_directory = IPSText::alphanumericalClean( $this->request['module_directory'] );
		$type          = 'upgrade';
		$data          = array();
		$ok            = 1;
		$errors        = array();
		$localfiles    = array( DOC_IPS_ROOT_PATH . 'cache/skin_cache' );
		$info          = array();
		
		/* Init Data */
		$data      = IPSSetUp::fetchXmlAppInformation( $module_directory );
		$_numbers  = IPSSetUp::fetchAppVersionNumbers( $module_directory );
		$_files    = IPSSetUp::fetchXmlAppWriteableFiles( $module_directory );
		
		/* Grab Data */
		$data['module_directory']   = $module_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Install, or upgrade? */
		if ( ! $_numbers['current'][0] )
		{
			$type = 'install';
		}
		
		//-----------------------------------------
		// For upgrade, redirect
		//-----------------------------------------
		
		else
		{
			@header( "Location: {$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/" );
			exit;
		}
		
		/* Version Check */
		if( $data['current_version'] > 0 AND $data['current_version'] == $data['latest_version'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__up_to_date'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] );
			return;
		}
		
		/* Check local files */
		foreach( $localfiles as $_path )
		{
			if ( ! file_exists( $_path ) )
			{
				if ( $data['dir'] )
				{
					if ( ! @mkdir( $_path, 0777, TRUE ) )
					{
						$info['notexist'][] = $_path;
					}
				}
				else
				{
					$info['notexist'][] = $_path;
				}
			}
			else if ( ! is_writeable( $_path ) )
			{
				if ( ! @chmod( $_path, 0777 ) )
				{
					$info['notwrite'][] = $_path;
				}
			}
		}
		
		/* Check files... */
		if( is_array( $_files ) AND count( $_files ) )
		{
			$info = array_merge( $info, $_files );
		}
		
 		if ( count( $info['notexist'] ) )
		{
			foreach( $info['notexist'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_missing'], $path );
			}
		}
		
		if ( count( $info['notwrite'] ) )
		{
			foreach( $info['notwrite'] as $path )
			{
				$errors[] = sprintf( $this->lang->words['error__file_chmod'], $path );
			}
		}
		
		/**
		 * Custom errors
		 */
		if ( count( $info['other'] ) )
		{
			foreach( $info['other'] as $error )
			{
				$errors[]	= $error;
			}
		}
		
		/* Check for xml files */
		$required_xml = array( 
								"information",
								//"{$module_directory}_modules",
								//"{$module_directory}_settings",
								//"{$module_directory}_tasks",
								//"{$module_directory}_templates", 
							);

		foreach( $required_xml as $r )
		{
			if( ! file_exists( $this->module_full_path . "xml/{$r}.xml" ) )
			{
				$errors[] = sprintf( $this->lang->words['error__file_needed'], $this->module_full_path . "xml/{$r}.xml" );
			}
		}

		/* Show splash */
		$this->registry->output->html .= $this->html->setup_splash_screen( $data, $errors, $type );
	}
	
	/**
	 * Get environment vars
	 *
	 * @access	private
	 * @return	array
	 **/
	private function getVars()
	{
		/* INIT */
		$env = array();
		
		/* Get the infos */
		$env['type']			= strtolower( $this->request['type'] );
		$env['version']			= $this->request['version'];
		$env['dupe_tables']		= $this->request['dupe_tables'];
		$env['module_directory']	= $this->request['module_directory'];
		
		$env['module_location']	= 'other';
		
		if( $this->product_information['ipskey'] )
		{
			if ( strstr( $this->module_full_path, 'modules_addon/ips' ) or strstr( $this->module_full_path, 'modules/' ) )
			{
				if ( md5( 'ips_' . basename($this->module_full_path) ) == $this->product_information['ipskey'] )
				{
					$env['module_location']	= 'ips';
				}
			}
		}
		
		$env['path'] = ( $env['type'] == 'install' ) ? $this->module_full_path . 'setup/versions/install'
											         : $this->module_full_path . 'setup/versions/' . $env['version'];

		return $env;
	}
	
	/**
	 * Get next action URL
	 *
	 * @access	private
	 * @param	string	$next_action
	 * @param	array	$env
	 * @return	string
	 **/
	private function getNextURL( $next_action, $env )
	{
		return $this->settings['base_url'] . $this->form_code . '&amp;do=' . $next_action . '&amp;module_directory=' . $env['module_directory'] . '&amp;type=' . $env['type'] . '&amp;version=' . $env['version'];
	}
	
	private function _prerequisites()
	{
		$this->returnJsonArray(
			array(
				'totalSteps' 	=> 4,
				'step'			=> 1,
				'status'		=> 'continue',
				'stepStatus'	=> 'Inserting SQL into Databases',
				'url'			=> '&do=modules'
			)
		);
	} 
	   
	private function _modules()
	{
		$this->returnJsonArray(
			array(
				'step'			=> 2,
				'status'		=> 'continue',
				'stepStatus'	=> 'Inserting Tracker Modules',
				'url'			=> '&do=recache'
			)
		);
	}
	   
	private function _recache()
	{
		$this->returnJsonArray(
			array(
				'step'			=> 3,
				'status'		=> 'continue',
				'stepStatus'	=> 'Recaching Skins',
				'url'			=> '&do=finalise'
			)
		);
	}
	   
	private function _done()
	{
		$this->returnJsonArray(
			array(
				'step'			=> 4,
				'status'		=> 'success',
				'stepStatus'	=> 'Installation Complete',
				'moduleData'	=> array(
					'title'		=> 'Versions',
					'id'		=> 1,
					'author'	=> 'Tracker Team',
					'version'	=> '1.0.0',
					'directory'	=> 'versions'
				)
			)
		);
	}

}