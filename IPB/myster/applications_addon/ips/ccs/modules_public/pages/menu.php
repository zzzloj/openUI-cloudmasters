<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS menu preview feature
 * Last Updated: $Date: 2011-12-22 17:48:31 -0500 (Thu, 22 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10063 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class public_ccs_pages_menu extends ipsCommand
{
	/**
	 * Temp output
	 *
	 * @var		string
	 */
	protected $output		= '';

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
				$this->_previewHook();
			break;
		}
	}

	/**
	 * Preview the hook (shown inside an iframe)
	 * 
	 * @return	@e void
	 * @note	We output to the screen directly
	 */
 	protected function _previewHook()
 	{
 		$applications							= $this->registry->output->outputFormatClass->core_fetchApplicationData();

		if( isset($applications['ccs']) )
		{
			$applications['ccs']['app_link']		= $this->registry->ccsFunctions->returnPageUrl( array( 'page_seo_name' => ipsRegistry::$settings['ccs_default_page'], 'page_id' => 0 ) );
			$applications['ccs']['app_seotitle']	= '';		// Has to be empty, or IP.Board tries to run its FURL routines causing index.php?//page/...
			$applications['ccs']['app_template']	= 'app=ccs';
			$applications['ccs']['app_base']		= 'none';
		}
		
 		$nav	= $this->registry->output->getTemplate('ccs_global')->primary_navigation( $this->caches['ccs_menu'], $applications );
		
		$this->registry->output->popUpWindow( $this->registry->output->getTemplate('ccs_global')->primary_navigation_preview( $nav ) );
 	}
}
