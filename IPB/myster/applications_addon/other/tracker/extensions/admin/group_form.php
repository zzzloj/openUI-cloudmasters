<?php

/**
* Tracker 2.1.0
* 
* Group Plugin module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class admin_group_form__tracker implements admin_group_form
{	
	/**
	* Tab name
	* This can be left blank and the application title will
	* be used
	*
	* @access	public
	* @var		string	Tab name
	*/
	public $tab_name = '';

	
	/**
	* Returns content for the page.
	*
	* @access	public
	* @author	Brandon Farber
	* @param    array 				Group data
	* @param	integer				Number of tabs used so far
	* @return   array 				Array of tabs, content
	*/
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_tracker_group_form', 'tracker');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_groups' ), 'tracker' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		return array( 'tabs' => $this->html->acp_tracker_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_tracker_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}

	/**
	* Process the entries for saving and return
	*
	* @access	public
	* @author	Brandon Farber
	* @return   array 				Array of keys => values for saving
	*/
	public function getForSave()
	{
		$return = array(
			'g_tracker_view_offline'	=> intval(ipsRegistry::$request['g_tracker_view_offline'])
		);

		return $return;
	}
}

?>