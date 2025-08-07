<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_testimonials_overview_overview extends ipsCommand
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
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{

		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_overview' );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{			
			case 'settings':
				$this->_testemunhosSettings();
			break;
				
			case 'overview':
				default:
				$this->home();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/*-------------------------------------------------------------------------*/
	// Settings
	/*-------------------------------------------------------------------------*/

	public function _testemunhosSettings()
	{
	   
		require_once( IPSLib::getAppDir( 'core' ).'/modules_admin/settings/settings.php' );
		$settings = new admin_core_settings_settings();
		$settings->makeRegistryShortcuts( $this->registry );
		
		$this->lang->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );		
		$settings->form_code	= $settings->html->form_code    = 'module=settings&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=settings&section=settings';

		$this->request['conf_title_keyword'] = 'testimonials';
		$settings->return_after_save         = $this->settings['base_url'].$this->form_code.'&do=settings';
		$settings->_viewSettings();	
        
        
	}
	
	/*-------------------------------------------------------------------------*/
	// Home
	/*-------------------------------------------------------------------------*/

	public function home()
	{
		$cache = $this->cache->getCache( 'testemunhos' );

		/* Upgrade history */
		$this->DB->build( array( 'select' => 'upgrade_version_id, upgrade_version_human, upgrade_date',
								 'from'   => 'upgrade_history',
								 'where'  => "upgrade_app='testimonials'",
								 'order'  => 'upgrade_version_id DESC',
								 'limit'  => array( 0, 4 )
						 )		);
   		$this->DB->execute();
		
   		while ( $row = $this->DB->fetch() )
   		{
   			$row['_date'] = $this->registry->getClass('class_localization')->formatTime( $row['upgrade_date'], 'SHORT' );
   			$data['upgrade'][] = $row;
   		}
		
		$this->registry->output->html           .= $this->html->testemunhosOverviewIndex( $cache, $data );
	}
}