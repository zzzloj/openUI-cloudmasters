<?php
/**
 * @file		group_form.php 	Downloads group editing form
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-11-07 16:31:54 -0500 (Mon, 07 Nov 2011) $
 * @version		v2.5.4
 * $Revision: 9779 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_group_form__downloads
 * @brief		Downloads group editing form
 *
 */
class admin_group_form__downloads implements admin_group_form
{	
	/**
	 * Tab name (leave it blank to use the default application title)
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_idm_group_form', 'downloads');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------

		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		$return = array(
						'idm_restrictions'	=> serialize( 
							array(
								'enabled'		=> intval( ipsRegistry::$request['enabled'] ),
								'limit_sim'		=> intval( ipsRegistry::$request['limit_sim'] ),
								'min_posts'		=> intval( ipsRegistry::$request['min_posts'] ),
								'posts_per_dl'	=> intval( ipsRegistry::$request['posts_per_dl'] ),
								'daily_bw'		=> intval( ipsRegistry::$request['daily_bw'] ),
								'weekly_bw'		=> intval( ipsRegistry::$request['weekly_bw'] ),
								'monthly_bw'	=> intval( ipsRegistry::$request['monthly_bw'] ),
								'daily_dl'		=> intval( ipsRegistry::$request['daily_dl'] ),
								'weekly_dl'		=> intval( ipsRegistry::$request['weekly_dl'] ),
								'monthly_dl'	=> intval( ipsRegistry::$request['monthly_dl'] ),
							)	
						 ),
						 'idm_add_paid'			=> intval( ipsRegistry::$request['idm_add_paid'] ),
						 'idm_bypass_paid'		=> intval( ipsRegistry::$request['idm_bypass_paid'] ),
						 'idm_report_files'		=> intval( ipsRegistry::$request['idm_report_files'] ),
						 'idm_view_downloads'	=> intval( ipsRegistry::$request['idm_view_downloads'] ),
						 'idm_bypass_revision'	=> intval( ipsRegistry::$request['idm_bypass_revision'] ),
						 'idm_throttling'		=> intval( ipsRegistry::$request['idm_throttling'] ) > 0 ? intval( ipsRegistry::$request['idm_throttling'] ) : 0,
						 'idm_wait_period'		=> intval( ipsRegistry::$request['idm_wait_period'] ) > 0 ? intval( ipsRegistry::$request['idm_wait_period'] ) : 0,
						);

		return $return;
	}
}