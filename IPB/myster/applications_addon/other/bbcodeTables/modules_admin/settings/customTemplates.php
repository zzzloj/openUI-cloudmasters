<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 * customTables.php
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_bbcodeTables_settings_customTemplates extends ipsCommand
{

	protected $form_code;
	protected $form_code_js;
	protected $settingsClass;

	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code	= 'app=core&amp;module=settings&amp;section=settings';
		$this->form_code_js	= 'app=core&amp;module=settings&section=settings';

		//-------------------------------
		// Grab, init and load settings
		//-------------------------------

		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$settings		= new $classToLoad( $this->registry );
		$settings->makeRegistryShortcuts( $this->registry );

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );

		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
		$settings->form_code	= $settings->html->form_code    = 'app=core&amp;module=settings&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'app=core&amp;module=settings&section=settings';

		$this->request['conf_title_keyword'] = 'bbcodeTables_Custom';
		$settings->return_after_save         = $this->settings['base_url'] . $this->form_code;
		$settings->_viewSettings();

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();


	}
}