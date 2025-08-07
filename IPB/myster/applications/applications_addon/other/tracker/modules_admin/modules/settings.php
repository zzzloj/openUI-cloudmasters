<?php

/**
* Tracker 2.1.0
* 
* Status module
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * Type: ACP Page
 * Wrapper for module ACP pages
 *
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_modules_settings extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param ipsRegistry $registry ipsRegistry reference
	 * @return void [Outputs to screen]
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load the inputs
		//-----------------------------------------
		
		$module = $this->request['component'];
		$type   = $this->request['type'];
		$file   = $this->request['file'];

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		$className = 'admin_tracker_module_'.$module.'_'.$type.'_'.$file;
		$filePath  = $this->registry->tracker->modules()->getModuleFolder( $module ) . $type . 's_admin/' . $file . '.php';

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		if ( $this->registry->tracker->modules()->moduleIsInstalled( $module, FALSE ) && file_exists( $filePath ) )
		{
			require_once( $filePath );
			
			if ( class_exists( $className ) )
			{
				$settingsClass = new $className();
				$settingsClass->execute( $this->registry );
			}
			else
			{
				// Error
				echo "bad1";
			}
		}
		else
		{
			// Error
			echo "bad2";
		}
	}
}

?>