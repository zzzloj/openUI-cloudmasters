<?php

/**
* Tracker 2.1.0
* 
* ACP Field Modules Setup file
* Last Updated: $Date: 2013-01-24 04:55:40 +0000 (Thu, 24 Jan 2013) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1400 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Field modules setup ACP class
 *
 * @package Tracker
 * @subpackage Admin
 * @since 1.4.0
 */
class admin_tracker_modules_setup extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Current module path
	 *
	 * @access	private
	 * @var		object
	 */
	private $module_full_path;
	
	/**
	 * Product information
	 *
	 * @access	private
	 * @var		array
	 */
	private $product_information;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{	
		/* Get Template and Language */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_setup', 'tracker' );
		
		// Root module path
		$this->rootPath	= IPSLib::getAppDir('tracker') . '/modules/';
		
		$this->lang->loadLanguageFile( array( 'admin_setup', 'admin_system', 'admin_tools' ), 'core' );
		
		/* URL Bits */
		$this->form_code    = $this->html->form_code = 'module=modules&amp;section=setup';
		$this->form_code_js = $this->html->form_code_js = 'module=modules&section=setup';
		
		/* Set the path */
		$this->module_full_path = $this->rootPath . $this->request['directory'] . '/';
		
		/* Set up product info from XML file */
		$this->product_information	= $this->fetchXmlAppInformation( $this->request['directory'] );
		
		if( ! $this->module_full_path OR ! $this->product_information['title'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__cannot_init'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&module=modules' );
			return;		
		}
		
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
			default:
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
			case 'tplcache':
				$this->recacheTemplates();
				break;
			case 'finish':
				$this->finish();
				break;
			case 'remove':
				$this->remove();
				break;
		}
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Remove an application
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function remove()
	{
		//-----------------------------------------
		// Got an application?
		//-----------------------------------------
		
		$application = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'tracker_module', 'where' => "directory='" . $this->request['directory'] . "'" ) );
		
		if ( ! $application['module_id'] )
		{
			$this->registry->output->global_message = "This module does not exist";
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&module=modules' );
			return;
		}
				
		//-----------------------------------------
		// Remove Application Caches
		//-----------------------------------------		
		
		$_file = IPSLib::getAppDir( 'tracker' ) . '/modules/' . $application['directory'] . '/extensions/coreVariables.php';
		
		if ( file_exists( $_file ) )
		{
			require( $_file );
			
			if ( is_array( $CACHE ) AND count( $CACHE ) )
			{
				foreach( $CACHE as $key => $data )
				{
					$this->DB->delete( 'cache_store', "cs_key='{$key}'" );
				}
			}
		}
		
		//-----------------------------------------
		// Remove tables
		//-----------------------------------------

		$_file = IPSLib::getAppDir( 'tracker' ) . '/modules/' . $application['directory'] . '/setup/install/sql/' . $application['directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_tables.php';

		if( file_exists( $_file ) )
		{
			require( $_file );

			// Per issue 45, we need to uninstall in reverse order to avoid potential SQL errors with alters.
			$TABLE = array_reverse( $TABLE );

			foreach( $TABLE as $q )
			{
				//-----------------------------------------
				// Capture create tables first
				//-----------------------------------------
				
				preg_match( "/CREATE TABLE (\S+)(\s)?\(/", $q, $match );
				
				if( $match[1] )
				{
					$this->DB->dropTable( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ) );
				}
				else
				{
					//-----------------------------------------
					// Then capture alter tables
					//-----------------------------------------
					
					preg_match( "/ALTER TABLE (\S+)\sADD\s(\S+)\s/i", $q, $match );
					
					if( $match[1] AND $match[2] )
					{
						$this->DB->dropField( preg_replace( '#^' . ipsRegistry::dbFunctions()->getPrefix() . "(\S+)#", "\\1", $match[1] ), $match[2] );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check for uninstall sql
		//-----------------------------------------

		/* Any "extra" configs required for this driver? */
		if( file_exists( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' ) )
		{
			require_once( IPS_ROOT_PATH . 'setup/sql/' . $this->settings['sql_driver'] . '_install.php' );

			$extra_install = new install_extra( $this->registry );
		}
		
		$_file = IPSLib::getAppDir( 'tracker' ) . '/modules/' . $application['directory'] . '/setup/install/sql/' . $application['directory'] . '_' . ipsRegistry::dbFunctions()->getDriverType() . '_uninstall.php';
		
		if( file_exists( $_file ) )
		{
			require( $_file );
			
			if ( is_array( $QUERY ) AND count( $QUERY ) )
			{			
				foreach( $QUERY as $q )
				{
					if ( $extra_install AND method_exists( $extra_install, 'process_query_create' ) )
					{
						 $q = $extra_install->process_query_create( $q );
					}
					
					$this->DB->query( $q );
				}
			}
		}				
		
		//-----------------------------------------
		// Remove Misc Stuff
		//-----------------------------------------		
		
		$this->DB->delete( 'core_sys_lang_words', "word_app='tracker' AND word_pack LIKE '%module_{$application['directory']}%'" );
		$this->DB->delete( 'tracker_field_changes', "module='" . $application['directory'] . "'" );
		
		// Permissions
		$_file = DOC_IPS_ROOT_PATH . '/cache/tracker/coreExtensions.php';
		
		if ( file_exists( $_file ) && ! isset( $_PERM_CONFIG ) )
		{
			require_once( $_file );
			
			if ( is_array( $_PERM_CONFIG ) AND count( $_PERM_CONFIG ) )
			{
				foreach( $_PERM_CONFIG as $key => $data )
				{
					if ( preg_match( '#' . ucfirst($application['directory']) . 'Field#is', $data, $matches ) )
					{
						$this->DB->delete( 'permission_index', "app='tracker' AND perm_type='" . lcfirst($data) . "'" );
					}
				}
			}
		}

		//-----------------------------------------
		// Remove Files
		//-----------------------------------------
		
		/* Languages */
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' ) as $dir )
			{
				if( ! $dir->isDot() && intval( $dir->getFileName() ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/lang_cache/' . $dir->getFileName() . '/' ) as $file )
					{
						if( ! $file->isDot() )
						{
							if( preg_match( "/^(module_{$application['directory']}_)/", $file->getFileName() ) )
							{
								unlink( $file->getPathName() );
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {}
		
		/* Remove Skins */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml = new classXML( $this->settings['gb_char_set'] );
		$xml->load( IPSLib::getAppDir( 'tracker' ) . '/modules/' . $application['directory'] . '/xml/information.xml' );

		if ( is_object( $xml->fetchElements( 'template' ) ) )
		{
			foreach( $xml->fetchElements( 'template' ) as $template )
			{
				$name  = $xml->fetchItem( $template );
				$match = $xml->fetchAttribute( $template, 'match' );
		
				if ( $name )
				{
					$templateGroups[ $name ] = $match;
				}
			}
		}

		if( is_array($templateGroups) AND count($templateGroups) )
		{
			/* Loop through skin directories */
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' ) as $dir )
				{
					if( preg_match( "/^(cacheid_)/", $dir->getFileName() ) )
					{
						foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/skin_cache/' . $dir->getFileName() . '/' ) as $file )
						{
							if( ! $file->isDot() )
							{
								foreach( $templateGroups as $name => $match )
								{
									if( $match == 'contains' )
									{
										if( stristr( $file->getFileName(), $name ) )
										{
											unlink( $file->getPathName() );
										}
									}
									else if( $file->getFileName() == $name . '.php' )
									{
										unlink( $file->getPathName() );
									}
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {}
			
			/* Delete from database */
			foreach( $templateGroups as $name => $match )
			{
				if( $match == 'contains' )
				{
					$this->DB->delete( 'skin_templates', "template_group LIKE '%{$name}%'" );
				}
				else 
				{
					$this->DB->delete( 'skin_templates', "template_group='{$name}'" );
				}
			}
		}
		
		//-----------------------------------------
		// Remove Modules
		//-----------------------------------------

		$f = $this->DB->build(array('select'	=>	'*',
									'from'		=>	array('tracker_field' => 'tf'),
									'where'		=>	'module_id='.$application['module_id']));
		$ff = $this->DB->execute($f);

		while($fff = $this->DB->fetch($ff))
		{
			$this->DB->delete( 'tracker_project_field', "field_id=" . $fff['field_id'] );
			$this->DB->delete( 'tracker_project_metadata', "field_id=" . $fff['field_id'] );
		}
		
		$this->DB->delete( 'tracker_field', "module_id=" . $application['module_id'] );
		$this->DB->delete( 'tracker_module', 'module_id=' . $application['module_id'] );
		
		$this->cache->rebuildCache( 'settings', 'global' );
		
		/* Delete from upgrade */
		$this->DB->delete( 'tracker_module_upgrade_history', "upgrade_module='{$application['directory']}'" );
		
		// Recache tracker
		require_once( IPSLib::getAppDir('tracker') . '/extensions/coreVariables.php' );

		if( is_array( $CACHE ) AND count( $CACHE ) )
		{
			foreach( $CACHE as $key => $cdata )
			{
				$this->cache->rebuildCache( $key, 'tracker' );
			}
		}
		
		// Rebuild modules always from this page (needed for setup)
		$this->registry->tracker->modules()->rebuild();
		$this->registry->tracker->moderators()->rebuild();
		$this->registry->tracker->fields()->rebuild();
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		$this->registry->output->global_message = "Module removed";

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&module=modules' );
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
		
		if ( file_exists( $this->module_full_path . 'xml/replacements.xml' ) )
		{
			$hasSkin	= true;
		}
		
		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->module_full_path . 'xml/root_templates.xml' ) OR
				file_exists( $this->module_full_path . 'xml/lofi_templates.xml' ) OR
				file_exists( $this->module_full_path . 'xml/xmlskin_templates.xml' ) )
			{
				$hasSkin	= true;
			}
		}

		if( !$hasSkin )
		{
			//-----------------------------------------
			// We'll check for any of the default 3
			//-----------------------------------------
			
			if( file_exists( $this->module_full_path . 'xml/root_css.xml' ) OR
				file_exists( $this->module_full_path . 'xml/lofi_css.xml' ) OR
				file_exists( $this->module_full_path . 'xml/xmlskin_css.xml' ) )
			{
				$hasSkin	= true;
			}
		}
		
		if( !$hasSkin )
		{
			$this->showRedirectScreen( $vars['directory'], array( $this->lang->words['redir__no_template_re'] ) , '', $this->getNextURL( 'finish', $vars ) );
			return;
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
			$this->showRedirectScreen( $vars['directory'], array( $this->lang->words['to_recachedset'] . $this->registry->output->allSkins[ $setID ]['set_name'] ), '', $this->getNextURL( 'tplcache&amp;setID=' . $nextID, $vars ) );
		}
		else
		{
			$this->showRedirectScreen( $vars['directory'], array( $this->lang->words['to_recache_done'] ) , '', $this->getNextURL( 'finish', $vars ) );
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
		$data      = $this->product_information;
		$_numbers  = $this->fetchAppVersionNumbers( $vars['directory'] );
		
		/* Grab Data */
		$data['directory']			= $vars['directory'];
		$data['current_version']	= ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']		= $_numbers['latest'][1];
		$data['next_version']		= $_numbers['next'][0];
		
		/* Rebuild modules and modules cache */
		$this->registry->tracker->cache('modules')->rebuild();

		/* Rebuild module specific caches */
		if( is_array( $data['fields'] ) AND count( $data['fields'] ) )
		{
			foreach( $data['fields'] as $k => $v )
			{
				$this->registry->tracker->cache( $k, $vars['directory'] )->rebuild();
			}
		}
		
		// Rebuild modules always from this page (needed for setup)
		$this->registry->tracker->modules()->rebuild();
		$this->registry->tracker->fields()->rebuild();
		$out = $this->registry->tracker->cache('files')->rebuild();
		$this->registry->tracker->moderators()->rebuild();
		$this->registry->tracker->projects()->rebuild();
		$this->registry->tracker->cache('stats')->rebuild();
		
		if ( $out == 'CANNOT_WRITE' )
		{
			$this->registry->output->showError( 'Cannot write to the Tracker cache files, please make sure all files in ' . DOC_IPS_ROOT_PATH . 'cache/tracker/ are writeable (CHMOD 777)' );
		}
		
		/* Loop through and rebuild modules */
		foreach( $this->registry->tracker->modules()->getCache() as $k => $v )
		{
			if ( file_exists( $this->registry->tracker->modules()->getModuleFolder( $v['directory'] ) . '/sources/cache_' . $v['directory'] . '.php' ) )
			{
				$this->registry->tracker->cache($v['directory'],$v['directory'])->rebuild();
			}
		}
		
		/* Show completed screen... */
		$this->registry->output->html .= $this->html->setup_completed_screen( $data, $vars['type'] );
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
		$data		= $this->product_information;
		$_numbers 	= $this->fetchAppVersionNumbers( $vars['directory'] );
		$fields		= $this->fetchXmlAppFields( $vars['directory'] );
		
		/* Grab Data */
		$data['directory']   = $vars['directory'];
		$data['current_version'] = ( $_numbers['current'][1] ) ? $_numbers['current'][1] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Update the module DB */
		if( $vars['type'] == 'install' )
		{
			/* Insert details into the DB */
			$this->DB->insert( 'tracker_module', array( 
																'title'			=> $this->product_information['title'],
																'author'		=> $this->product_information['author'],
																'description'	=> $this->product_information['description'],
																'version'		=> $_numbers['latest'][1],
																'long_version'	=> $_numbers['latest'][0],
																'directory'		=> $vars['directory'],
																'added'			=> time(),
																'protected'		=> 0,
																'enabled'		=> $this->product_information['disabledatinstall'] ? 0 : 1
															)
								);
								
			$moduleID = $this->DB->getInsertId();
			
			$this->DB->insert( 'tracker_module_upgrade_history', array( 
														'upgrade_version_id'	=> $_numbers['latest'][0],
														'upgrade_version_human'	=> $_numbers['latest'][1],
														'upgrade_date'			=> time(),
														'upgrade_notes'			=> '',
														'upgrade_mid'			=> $this->memberData['member_id'],
														'upgrade_module'		=> $vars['directory']
												)	);
			
			/* Insert the modules */
			foreach( $fields as $key => $field )
			{
				$field['field_keyword']	= $key;
				$field['module_id']		= $moduleID;
				$this->DB->insert( 'tracker_field', $field );
			}
		}
		else
		{
			$this->DB->update( 'tracker_module', array( 
															'version'      => $_numbers['current'][1],
															'long_version' => $_numbers['current'][0] 
							), "directory='" . $vars['directory'] . "'" );
			
			$this->DB->insert( 'tracker_module_upgrade_history', array( 
														'upgrade_version_id'	=> $_numbers['latest'][0],
														'upgrade_version_human'	=> $_numbers['latest'][1],
														'upgrade_date'			=> time(),
														'upgrade_notes'			=> '',
														'upgrade_mid'			=> $this->memberData['member_id'],
														'upgrade_module'		=> $vars['directory']
												)	);
												
			/* Update the modules */
			foreach( $fields as $key => $field )
			{
				$installCheck = $this->registry->tracker->fields()->getFieldByKeyword($field['field_keyword']);
				
				if ( ! $installCheck['field_id'] )
				{
					$module	= $this->registry->tracker->modules()->getModule( $vars['directory'] );
					
					$field['field_keyword']	= $key;
					$field['module_id']		= $moduleID;
					
					$this->DB->insert( 'tracker_field', $field );
				}
				else
				{
					$this->DB->update( 'tracker_field', $field, "field_keyword='{$key}'" );
				}
			}
		}
		
		/* Finish? */
		if( $vars['type'] == 'install' OR $vars['version'] == $_numbers['latest'][0] )
		{
			/* Go back and start over with the new version */
			$output[] = 'Fields inserted';

			$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'templates', $vars ) );
		}
		else
		{
			/* Go back and start over with the new version */
			$output[] = sprintf( $this->lang->words['redir__upgraded_to'], $_numbers['current'][1] );
			
			/* Work out the next step */
			$vars['version'] = $_numbers['next'][0];
			
			$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'sql', $vars ) );
		}
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
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );
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
					$lang->imprtFromXML( true, true, true, $vars['directory'] );				
				}
			}
		} catch ( Exception $e ) {}
		
		$output[] = $this->registry->output->global_message ? $this->registry->output->global_message : $this->lang->words['redir__nolanguages'];
		
		/* Clear main msg */
		$this->registry->output->global_message = '';

		/* Show redirect... */
		$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'tplcache', $vars ) );
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
			if( file_exists( $this->module_full_path . 'xml/' . $vars['directory'] . '_' . $skinKey . '_templates.xml' ) )
			{
				$return = $skinFunctions->importTemplateAppXML( $vars['directory'], $skinKey, $skinData['set_id'], TRUE );
				
				$output[] = sprintf( $this->lang->words['redir__templates'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* Then CSS files */
			if ( file_exists( $this->module_full_path . 'xml/' . $vars['directory'] . '_' . $skinKey . '_css.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importCSSAppXML( $vars['directory'], $skinKey, $skinData['set_id'] );
				
				$output[] = sprintf( $this->lang->words['redir__cssfiles'], $return['insertCount'], $return['updateCount'], $skinData['set_name'] );
			}
			
			/* And we can support replacements for good measure */
			if ( file_exists( $this->module_full_path . 'xml/' . $vars['directory'] . '_replacements.xml' ) )
			{
				//-----------------------------------------
				// Install
				//-----------------------------------------
		
				$return = $skinFunctions->importReplacementsXMLArchive( $this->module_full_path . 'xml/' . $vars['directory'] . '_' . $skinKey . '_replacements.xml' );
				
				$output[] = $this->lang->words['redir__replacements'];
			}
		}

		/* Show redirect... */
		$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'languages', $vars ) );
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
		if( file_exists( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' ) )
		{
			print 1; exit;
			/* INIT */
			$new_id = $id + 1;
			$count  = 0;
			
			/* Get the sql file */
			require_once( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ips_DBRegistry::getDriverType() )  . '_sql_' . $id .'.php' );

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
			$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) . '&amp;id=' . $new_id );
		}
		else
		{
			$output[] = $this->lang->words['redir__nomore_sql'];

			/* Show redirect... */
			$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'next_check', $vars ) );
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

		if ( file_exists( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' ) )
		{
			include( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_tables.php' );

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
			if ( file_exists( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' ) )
			{
				include( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_fulltext.php' );
				
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
		if ( file_exists( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' ) )
		{
			$count   = 0;
			
			/* Get the SQL File */
			include( $this->module_full_path . 'setup/install/sql/' . $vars['directory']. '_' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '_inserts.php' );
			
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
		$this->showRedirectScreen( $vars['directory'], $output, $errors, $this->getNextURL( 'sql_steps', $vars ) );
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
		$module_directory = IPSText::alphanumericalClean( $this->request['directory'] );
		$type          = 'upgrade';
		$data          = array();
		$ok            = 1;
		$errors        = array();
		$localfiles    = array( DOC_IPS_ROOT_PATH . 'cache/skin_cache' );
		$info          = array();
		
		/* Init Data */
		$data      = $this->product_information;
		$_numbers  = $this->fetchAppVersionNumbers( $module_directory );
		$_files    = $this->fetchXmlAppWriteableFiles( $module_directory );
		
		/* Grab Data */
		$data['directory']   = $module_directory;
		$data['current_version'] = ( $_numbers['current'][1] ) ? $_numbers['current'][1] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
		
		/* Install, or upgrade? */
		if ( ! $_numbers['current'][0] )
		{
			$type = 'install';
		}
		
		/* Version Check */
		if( $data['current_version'] > 0 AND $data['current_version'] == $data['latest_version'] )
		{
			$this->registry->output->global_message = $this->lang->words['error__up_to_date'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&module=modules' );
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
								"information"
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
		$env['directory']		= $this->request['directory'];
		
		$env['path'] = ( $env['type'] == 'install' ) ? $this->module_full_path . 'setup/install'
											         : $this->module_full_path . 'setup/' . $env['version'];
											         
		// Fields
		$env['fields']			= $this->fetchXmlAppFields( $env['directory'] );

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
		return $this->settings['base_url'] . $this->form_code . '&amp;do=' . $next_action . '&amp;directory=' . $env['directory'] . '&amp;type=' . $env['type'] . '&amp;version=' . $env['version'];
	}
	
	/**
	 * Show the redirect screen
	 *
	 * @access	private
	 * @param	string	$module_directory
	 * @param	string	$output
	 * @param	string	$errors
	 * @param	string	$next_url
	 * @return	void
	 **/
	private function showRedirectScreen( $module_directory, $output, $errors, $next_url )
	{
		/* Init Data */
		$data		= $this->fetchXmlAppInformation( $module_directory );
		$_numbers	= $this->fetchAppVersionNumbers( $module_directory );
		
		/* Grab Data */
		$data['directory']   = $module_directory;
		$data['current_version'] = ( $_numbers['current'][0] ) ? $_numbers['current'][0] : $this->lang->words['cur_version_none'];
		$data['latest_version']  = $_numbers['latest'][1];
		$data['next_version']    = $_numbers['next'][0];
			
		/* Setup Redirect */
		$this->registry->output->html .= $this->html->setup_redirectScreen( $output, $errors, $next_url );
	}
	
	/**
	 * Fetch Apps XML Information File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @param	string		Character set (used for ACP)
	 * @return	array 		..of data
	 */
	private function fetchXmlAppInformation( $app, $charset='' )
	{
		/* INIT */
		$info = array();

		/* Fetch core writeable files */
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( $charset ? $charset : $this->settings['gb_char_set'] );

		try
		{
			if( file_exists( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/information.xml' ) )
			{
				$xml->load( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/information.xml' );
            	
				/* Fetch general information */
				foreach( $xml->fetchElements( 'data' ) as $xmlelement )
				{
					$data = $xml->fetchElementsFromRecord( $xmlelement );
            	
					$info['title']				= $data['title'];
					$info['author']				= $data['author'];
					$info['description']		= $data['description'];
					$info['disabledatinstall']	= ( $data['disabledatinstall'] ) ? 1 : 0;
					$info['directory']			= $app;
				}
            	
				/* Fetch template information */
				foreach( $xml->fetchElements( 'template' ) as $template )
				{
					$name  = $xml->fetchItem( $template );
					$match = $xml->fetchAttribute( $template, 'match' );
            	
					if ( $name )
					{
						$info['templates'][ $name ] = $match;
					}
				}
			}

			return $info;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( IPS_ROOT_PATH . 'applications_addon/other/tracker/modules/' . $app . '/xml/information.xml' );
			return FALSE;
		}
	}
	
	/**
	 * Fetch Apps XML Information File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	public function fetchXmlAppVersions( $app )
	{
		/* INIT */
		$versions = array();

		/* Fetch core writeable files */
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( $this->settings['gb_char_set'] );

		try
		{
			if( !file_exists( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/versions.xml' ) )
			{
				return false;
			}
			
			$xml->load( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/versions.xml' );

			/* Fetch general information */
			foreach( $xml->fetchElements( 'version' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				$versions[ $data['long'] ] = $data['human'];
			}

			ksort( $versions );

			return $versions;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( IPS_ROOT_PATH . 'applications_addon/other/tracker/modules/' . $app . '/xml/versions.xml' );
			return FALSE;
		}
	}
	
	/**
	 * Fetch app DB versions
	 *
     * @access	public		
     * @param	string		Application Directory
     * @return	array
 	 */
	public function fetchDbAppVersions( $app )
	{
		/* INIT */
		$versions = array();

		/* 2.x+? */
		$this->DB->build( array( 'select' => '*',
										 'where'  => 'upgrade_module=\'' . $app . '\'',
								 		 'from'   => 'tracker_module_upgrade_history',
								 		 'order'  => 'upgrade_version_id ASC' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$versions[ $r['upgrade_version_id'] ] = $r['upgrade_version_id'];
		}

		ksort( $versions );

		return $versions;
	}
	
	/**
	 * Fetch Apps XML Writeable File information
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @return	array 		..of data
	 */
	public function fetchXmlAppWriteableFiles( $app )
	{
		/* INIT */
		$info  = array( 'notexist' => array(), 'notwrite' => array(), 'other' => array() );
		$file  = IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/writeablefiles.xml';

		/**
		 * Custom error checker routine...
		 */
		if( file_exists( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/setup/install/installCheck.php' ) )
		{
			require_once( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/setup/install/installCheck.php' );
			$checkerClass	= $app . '_installCheck';
			
			if( class_exists($checkerClass) )
			{
				$checker		= new $checkerClass;
				$info			= $checker->checkForProblems();
			}
		}

		/* Got a file? */
		if ( ! file_exists( $file ) )
		{
			return $info;
		}

		/* Fetch app writeable files */
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( $this->settings['gb_char_set'] );

		try
		{
			$xml->load( $file );

			foreach( $xml->fetchElements( 'file' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				if ( $data['path'] )
				{
					$_path = DOC_IPS_ROOT_PATH . $data['path'];

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
			}

			return $info;
		}
		catch( Exception $error )
		{
			$this->registry->output->addError( $file );
			return FALSE;
		}
	}
	
	/**
	 * Fetch current and next app versions
	 *
     * @access	public		
     * @param	string		Application Directory
     * @return	array 		array( 'current' => array( 000000, '1.0.0 Beta1' ), 'next' => array( 000000, '1.0.0 Beta1' ), 'latest' => array( 000000, '1.0.0 Beta1' ) )
 	 */
	public function fetchAppVersionNumbers( $app )
	{
		$return = array( 'current' => array( 0, '' ),
						 'next'	   => array( 0, '' ),
						 'latest'  => array( 0, '' ) );

		/* Latest version */
		$XMLVersions = $this->fetchXmlAppVersions( $app );
		$tmp         = $XMLVersions;
		
		if( is_array($tmp) AND count($tmp) )
		{
			krsort( $tmp );
	
			foreach( $tmp as $long => $human )
			{
				$return['latest'] = array( $long, $human );
				break;
			}
		}

		/* Current Version */
		$DBVersions  = $this->fetchDbAppVersions( $app );
		$tmp         = $DBVersions;
		$key         = array_pop( $tmp );

		if ( ! $key OR ! count( $DBVersions ) )
		{
			$_version = $this->DB->buildAndFetch( array( 'select' => '*',
														 		 'from'   => 'tracker_module',
														 		 'where'  => 'directory=\'' . $app . '\'' ) );

			$key = intval( $_version['long_version'] );
		}

		$return['current'] = ( $key ) ? array( $key, $XMLVersions[ $key ] ) : array( 0, 'install' );

		/* Next version */
		$nextKey = 0;

		if( is_array($XMLVersions) AND count($XMLVersions) )
		{
			foreach( $XMLVersions as $long => $human )
			{
				if ( $long > $return['current'][0] )
				{
					$nextKey = $long;
					break;
				}
			}
		}

		$return['next'] = array( $nextKey, $XMLVersions[ $nextKey ] );

		return $return;
	}
	
	/**
	 * Fetch Apps XML Modules File
	 *
	 * @access	public
	 * @param	string		Application Directory
	 * @param	string		Charset (post install)
	 * @return	array 		..of data
	 */
	public function fetchXmlAppFields( $app, $charset='' )
	{
		//-----------------------------------------
		// No modules?
		//-----------------------------------------
		
		if( !file_exists( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/fields.xml' ) )
		{
			return array();
		}
		
		/* INIT */
		$fields = array();

		try
		{
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( $this->settings['gb_char_set'] );

			$xml->load( IPSLib::getAppDir('tracker') . '/modules/' . $app . '/xml/fields.xml' );

			/* Fetch info */
			foreach( $xml->fetchElements( 'field' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );
				
				$fields[ $data['keyword'] ] = array( 'title' => $data['title'] );
			}
			
			return $fields;
		}
		catch( Exception $error )
		{
			return FALSE;
		}
	}
}

if ( false === function_exists('lcfirst') )
{ 
    function lcfirst( $str ) 
    {
    	return (string)(strtolower(substr($str,0,1)).substr($str,1));
    } 
}