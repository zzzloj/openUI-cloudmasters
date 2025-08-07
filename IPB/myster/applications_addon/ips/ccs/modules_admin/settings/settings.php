<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS manage settings
 * Last Updated: $Date: 2012-03-01 17:15:20 -0500 (Thu, 01 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10386 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_ccs_settings_settings extends ipsCommand
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
		// Downloading htaccess file?
		//-----------------------------------------
		
		if( $this->request['do'] == 'download' )
		{
			$this->_downloadHtaccess();
		}
		
		//-----------------------------------------
		// Load settings controller
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_lang' ), 'ccs' );
		
		$classToLoad				= IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$this->settingsClass		= new $classToLoad( $this->registry );
		$this->settingsClass->makeRegistryShortcuts( $this->registry );
		$this->settingsClass->html				= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
		$this->settingsClass->form_code			= $this->settingsClass->html->form_code		= 'module=settings&amp;section=settings';
		$this->settingsClass->form_code_js		= $this->settingsClass->html->form_code_js	= 'module=settings&section=settings';
		$this->settingsClass->return_after_save	= $this->settings['base_url'] . '&module=settings';

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		if( $this->request['do'] == 'advanced' )
		{
			$this->registry->output->setMessage( $this->lang->words['advanced_settings_help'], true );
			
			$this->request['conf_title_keyword']	= 'ccs_advanced';
			
			$this->settingsClass->return_after_save .= "&do=advanced";
		}
		else
		{
			$this->request['conf_title_keyword']	= 'ccs';
		}

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		$this->settingsClass->_viewSettings();
		
		//-----------------------------------------
		// Add download button
		//-----------------------------------------
		
		if( $this->request['do'] == 'advanced' AND $this->settings['ccs_root_url'] )
		{
			$html = $this->registry->output->loadTemplate( 'cp_skin_filemanager' );
			
			$this->registry->getClass('output')->html	= preg_replace( "/(<div class='section_title'>(\s+?)<h2>.+?<\/h2>)/is", "\\1" . $html->downloadHtaccess(), $this->registry->getClass('output')->html );
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->extra_nav	= array();
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Download the .htaccess file
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _downloadHtaccess()
	{
		$_urls	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );
		$_url	= $_urls[ $this->request['index'] ];
		
		$_parse	= parse_url( $_url );
		$_root	= preg_replace( "#/$#", "", $_parse['path'] );
		$_root	= str_replace( $this->settings['ccs_root_filename'], '', $_root );
		$_root	= $_root ? $_root : '/';
		$_path	= str_replace( '//', '/', $_root . '/' . $this->settings['ccs_root_filename'] );
		
		$htaccess	= <<<EOF
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$_root}
RewriteCond %{REQUEST_FILENAME} !.*\.(jpeg|jpg|gif|png|ico)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$_path} [L]
</IfModule>
EOF;
		
		$this->registry->output->showDownload( $htaccess, '.htaccess', '', 0 );
		
		exit();
	}
}