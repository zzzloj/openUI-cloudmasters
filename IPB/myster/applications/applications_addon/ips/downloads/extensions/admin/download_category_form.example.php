<?php
/**
 * @file		group_form.php 	Downloads category editing form
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ (Terabyte)
 * @since		4th March 2011
 * $LastChangedDate: 2011-04-26 14:39:00 -0400 (Tue, 26 Apr 2011) $
 * @version		v2.5.4
 * $Revision: 8482 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_download_category_form__APPDIRECTORY
 * @brief		Downloads group editing form
 *
 */
class admin_download_category_form__downloads implements admin_download_category_form
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
	 * @author	Terabyte
	 * @param	array		$category	Download category data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $category=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_idm_download_category_form', 'downloads');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		#ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'LANGUAGE_FILE' ), 'APPDIRECTORY' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------

		return array( 'tabs' => $this->html->acp_downloads_category_form_tabs( $category, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_downloads_category_form_main( $category, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @author	Terabyte
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		return array( 'example_yesno'		=> intval( ipsRegistry::$request['example_yesno'] ),
					  'example_input'		=> trim( ipsRegistry::$request['example_input'] ),
					  'example_textarea'	=> trim( nl2br( ipsRegistry::$request['example_textarea'] ) )
					 );
	}
	
	/**
	 * Post-process the entries for saving
	 *
	 * @author	Terabyte
	 * @param	integer		$categoryId		Category ID
	 * @return	@e void
	 */
	public function postSave( $categoryId )
	{
		// Your code here if you need to run some post process code
	}
}