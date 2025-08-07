<?php

/**
* Tracker 2.1.0
* 
* Projects Javascript PHP Interface
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


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: Admin
 * Project AJAX processor
 * 
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_ajax_settings extends ipsAjaxCommand 
{

	/**
	 * Skin functions object handle
	 *
	 * @access private
	 * @var object
	 * @since 2.0.0
	 */
	private $skinFunctions;
	/**
	 * HTML Skin object
	 *
	 * @access protected
	 * @var object
	 * @since 2.0.0
	 */
	protected $html;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load the inputs
		//-----------------------------------------
		
		$module = $this->request['component'];
		$file   = $this->request['file'];

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		$className = 'admin_tracker_module_'.$module.'_ajax_'.$file;
		$filePath  = $this->registry->tracker->modules()->getModuleFolder( $module ) . 'ajax/' . $file . '.php';

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		if ( $this->registry->tracker->modules()->moduleIsInstalled( $module, FALSE ) && file_exists( $filePath ) )
		{
			require_once( $filePath );
			
			if ( class_exists( $className ) )
			{
				$ajaxClass = new $className();
				$ajaxClass->execute( $this->registry );
			}
			else
			{
				$this->returnJsonArray( array( 'error' => true, 'message' => 'class_missing' ) );
			}
		}
		else
		{
			$this->returnJsonArray( array( 'error' => true, 'message' => 'module_not_installed' ) );
		}
	}
}