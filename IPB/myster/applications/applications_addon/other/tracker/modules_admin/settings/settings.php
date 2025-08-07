<?php

/**
 * Tracker 2.1.0
 *
 * Last Updated: $Date: 2012-05-10 05:06:08 +0100 (Thu, 10 May 2012) $
 *
 * @author 		$Author: RyanH $
 * @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
 *
 * @package		IP.CCS
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 1364 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_tracker_settings_settings extends ipsCommand
{
	/**
	 * Settings gateway
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $settingsClass;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load settings controller
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		$classToLoad							= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$this->settingsClass					= new $classToLoad( $this->registry );
		$this->settingsClass->makeRegistryShortcuts( $this->registry );

		$this->settingsClass->html				= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
		$this->settingsClass->form_code			= $this->settingsClass->html->form_code		= 'module=settings&amp;section=settings';
		$this->settingsClass->form_code_js		= $this->settingsClass->html->form_code_js	= 'module=settings&section=settings';
		$this->settingsClass->return_after_save	= $this->settings['base_url'] . 'module=settings&amp;do='.$this->request['do'];

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		$this->request['conf_title_keyword']	= 'tracker_'.$this->request['do'];

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		$this->settingsClass->_viewSettings();
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}