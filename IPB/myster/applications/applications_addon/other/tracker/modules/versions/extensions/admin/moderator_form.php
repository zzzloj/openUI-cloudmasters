<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Moderator Plugin module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class tracker_admin_moderator_form__versions_field_version extends iptCommand implements tracker_admin_moderator_form
{
	public function doExecute( ipsRegistry $registry ) {}

	/**
	 * Returns HTML table content for the page.
	 *
	 * @param array $moderator Moderator data
	 * @return string HTML for the form
	 * @access public
	 * @since 2.0.0
	 */
	public function getDisplayContent( $moderator=array() )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->html = ipsRegistry::getClass('tracker')->modules()->loadTemplate('cp_skin_module_versions_moderator_form', 'versions');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_module_versions' ), 'tracker' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		return $this->html->acp_moderator_form_main_version( $moderator );
	}

	/**
	 * Process the entries for saving and return
	 *
	 * @return array Array of keys => values for saving
	 * @access public
	 * @since 2.0.0
	 */
	public function getForSave()
	{
		$return = array(
			'versions_field_version_show'		=> intval(ipsRegistry::$request['versions_field_version_show']),
			'versions_field_version_submit'		=> intval(ipsRegistry::$request['versions_field_version_submit']),
			'versions_field_version_update'		=> intval(ipsRegistry::$request['versions_field_version_update']),
			'versions_field_version_developer'	=> intval(ipsRegistry::$request['versions_field_version_developer']),
			'versions_field_version_alter'		=> intval(ipsRegistry::$request['versions_field_version_alter'])
		);

		return $return;
	}
}

class tracker_admin_moderator_form__versions_field_fixed_in extends iptCommand implements tracker_admin_moderator_form
{
	public function doExecute( ipsRegistry $registry ) {}

	/**
	 * Returns HTML table content for the page.
	 *
	 * @param array $moderator Moderator data
	 * @return string HTML for the form
	 * @access public
	 * @since 2.0.0
	 */
	public function getDisplayContent( $moderator=array() )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->html = ipsRegistry::getClass('tracker')->modules()->loadTemplate('cp_skin_module_versions_moderator_form', 'versions');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_module_versions' ), 'tracker' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		return $this->html->acp_moderator_form_main_fixed_in( $moderator );
	}

	/**
	 * Process the entries for saving and return
	 *
	 * @return array Array of keys => values for saving
	 * @access public
	 * @since 2.0.0
	 */
	public function getForSave()
	{
		$return = array(
			'versions_field_fixed_in_show'      => intval(ipsRegistry::$request['versions_field_fixed_in_show']),
			'versions_field_fixed_in_submit'    => intval(ipsRegistry::$request['versions_field_fixed_in_submit']),
			'versions_field_fixed_in_update'    => intval(ipsRegistry::$request['versions_field_fixed_in_update']),
			'versions_field_fixed_in_developer' => intval(ipsRegistry::$request['versions_field_fixed_in_developer']),
			'versions_field_fixed_in_report'    => intval(ipsRegistry::$request['versions_field_fixed_in_report'])
		);

		return $return;
	}
}

?>