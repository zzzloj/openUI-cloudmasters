<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article settings
 * Last Updated: $Date: 2012-01-06 06:20:45 -0500 (Fri, 06 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10095 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_ccs_articles_settings extends ipsCommand
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
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load settings controller
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_lang' ), 'ccs' );
		
		$classToLoad			= IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$this->settingsClass	= new $classToLoad( $this->registry );
		$this->settingsClass->makeRegistryShortcuts( $this->registry );
		$this->settingsClass->html				= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
		$this->settingsClass->form_code			= $this->settingsClass->html->form_code		= 'module=settings&amp;section=settings';
		$this->settingsClass->form_code_js		= $this->settingsClass->html->form_code_js	= 'module=settings&section=settings';
		$this->settingsClass->return_after_save	= $this->settings['base_url'] . '&module=articles&section=settings';

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		$this->request['conf_title_keyword']	= 'ccs_articles';

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		$this->settingsClass->_viewSettings();
				
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->extra_nav	= array();
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}