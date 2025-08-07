<?php

/**
* Tracker 2.1.0
* 
* ACP Field Modules file
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Field modules ACP class
 *
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_modules_modules extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var object Skin templates
	 * @access private
	 * @since 2.0.0
	 */
	private $html;

	/**
	 * Shortcut for url
	 *
	 * @var string URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code;

	/**
	 * Shortcut for url (javascript)
	 *
	 * @var string JS URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code_js;

	/**
	 * Main class entry point
	 *
	 * @param object ipsRegistry $registry reference
	 * @return void [Outputs to screen]
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate('cp_skin_modules');
		$this->registry->class_localization->loadLanguageFile( array( 'admin_modules' ) );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code    = $this->html->form_code    = 'module=modules&amp;section=modules';
		$this->form_code_js = $this->html->form_code_js = 'module=modules&section=modules';

		//-----------------------------------------
		// What to do...
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_fields_manage' );
				$this->form( 'edit' );
				break;
			case 'toggle':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_fields_manage' );
				$this->toggleEnabled();
				break;
			default:
				$this->request['do'] = 'overview';
				$this->listModules();
				break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	public function listModules()
	{
		/* Get modules from folder */
		$modules = $this->_getModules();
		
		/* Check which are installed, and which aren't */
		$installedModules 	= array();
		$availableModules 	= array();
		
		/* Check each module */
		foreach ( $modules as $module )
		{
			if ( $this->registry->tracker->modules()->moduleIsInstalled( $module, FALSE ) )
			{
				$m = $this->registry->tracker->modules()->getModule($module);

				/* Loosely sort modules by enabled/disabled */
				if( $m['enabled'] )
				{
					array_unshift($installedModules, $m);
				}
				else
				{
					array_push($installedModules, $m);
				}
			}
			else
			{
				$availableModules[] = $module;
			}
		}
		
		/* Get information about uninstalled modules direct from files */
		foreach( $availableModules as $id => $module )
		{
			$folder = IPSLib::getAppDir('tracker') . '/modules/' . $module . '/';
			
			/* XML Files? */
			if ( ! file_exists( $folder . 'xml/information.xml' ) )
			{
				unset( $availableModules[$id] );
				continue;
			}
			
			/* Load the content of the file */
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( IPS_DOC_CHAR_SET );

			$xml->loadXML( file_get_contents($folder . 'xml/information.xml') );
			
			foreach( $xml->fetchElements('data') as $data )
			{
				$data	= $xml->fetchElementsFromRecord( $data );
			}
			
			/* Save to our array */
			$availableModules[$id] = $data;
		}
		
		/* Output page */
		$this->registry->output->html .= $this->html->module_splash( $installedModules, $availableModules );
	}
	
	private function _getModules()
	{
		$_modules = array();
		
		/* Iterate through the folder */
		$iterator = new DirectoryIterator( IPSLib::getAppDir('tracker') . '/modules/' );
		
		foreach( $iterator as $item )
		{
			$fileName = $item->getFileName();
			
			/* Check it doesn't start with a dot */
			if ( $fileName[0] == '.' )
			{
				continue;
			}
			
			/* Is it a folder? If so, add it to our array */
			if ( $item->isDir() )
			{
				$_modules[] = $item->getFileName();
			}
		}
		
		/* Return the data to the running script */
		return $_modules;
	}
	
	private function toggleEnabled()
	{
		if ( ! $this->request['module_directory'] )
		{
			$this->globalMessage = 'We could not find the module you wanted to modify!';
			$this->listModules();
			return;
		}
		
		/* Is it installed? */
		if ( !$this->registry->tracker->modules()->moduleIsInstalled( $this->request['module_directory'], FALSE ) )
		{
			$this->globalMessage = 'We could not find the module you wanted to modify!';
			$this->listModules();
			return;
		}
		
		$module = $this->registry->tracker->modules()->getModule($this->request['module_directory']);
		
		/* What do we do? */
		if ( $module['enabled'] )
		{
			$this->DB->update( 'tracker_module', array( 'enabled' => 0 ), "directory='{$module['directory']}'" );
			$this->registry->tracker->modules()->rebuild();
			$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );
		}
		
		$this->DB->update( 'tracker_module', array( 'enabled' => 1 ), "directory='{$module['directory']}'" );
		$this->registry->tracker->modules()->rebuild();
		$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->form_code );
	}
}

?>