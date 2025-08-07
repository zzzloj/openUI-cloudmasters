<?php
/**
 * @file		overview.php 	Blog Overview
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		27th January 2004
 * $LastChangedDate: 2012-09-19 15:49:50 -0400 (Wed, 19 Sep 2012) $
 * @version		v2.6.3
 * $Revision: 11351 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_blog_overview_overview
 * @brief		Blog Overview
 */
class admin_blog_overview_overview extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ) );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=overview&amp;section=overview&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=overview&section=overview&';

		switch ( $this->request['do'] )
		{
			case 'settings':
				$this->showSettings();
			break;
			
			default:
				$this->indexScreen();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Blog Overview
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		/* INIT */
		$approve_rows   = array();
		
		/* Query Status */
		$this->DB->buildFromCache( 'blog_stats', array(), 'sql_blog_admin_queries' );
		$this->DB->execute();
		$stats = $this->DB->fetch();
		
		// We also want to show pending requests right here to be approved/declined
		if( $this->settings['blog_themes_custom'] )
		{
			$this->DB->build( array(    'select'	=> 'b.blog_id, b.member_id, b.blog_name, b.blog_theme_custom',
										'from'		=> array( 'blog_blogs' => 'b' ),
										'where'		=> "b.blog_theme_approved=0 AND b.blog_theme_custom IS NOT NULL AND b.blog_theme_custom != ''",
										'order'		=> 'b.blog_name asc',
										'limit'     => array(0, 10 ),
										'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
																	 'from'		=> array( 'members' => 'm' ),
																	 'where'	=> 'm.member_id=b.member_id',
																	 'type'		=> 'left' ) ) ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				$r['_suspicious'] = ( IPSText::getTextClass('bbcode')->xssHtmlClean( $r['blog_theme_custom'] ) != $r['blog_theme_custom'] ) ? true : false;
				$approve_rows[] = $r;
			}
		}

		/* Output */
		$this->registry->output->html .= $this->html->blogOverview( $stats, $approve_rows );
	}
	
	/**
	 * Load and display the application settings
	 * 
	 * @return	@e void
	 */
	protected function showSettings()
	{
		$this->html->form_code    = $this->form_code	= 'module=overview&amp;section=overview&amp;do=settings';
		$this->html->form_code_js = $this->form_code_js	= 'module=overview&section=overview&do=settings';
		
		/* Get settings library */
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core').'/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$settings    = new $classToLoad();
		$settings->makeRegistryShortcuts( $this->registry );
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );		
		$settings->form_code	= $settings->html->form_code    = 'app=core&amp;module=settings&amp;section=settings&amp;';
		$settings->form_code_js	= $settings->html->form_code_js = 'app=core&module=settings&section=settings&';

		$this->request['conf_title_keyword'] = 'blog';
		$settings->return_after_save         = $this->settings['base_url'] . $this->form_code;
		$settings->_viewSettings();
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();		

		$this->form_code	= 'module=overview&section=overview&do=settings';
		$this->form_code_js	= 'module=overview&section=overview&do=settings';
	}
}