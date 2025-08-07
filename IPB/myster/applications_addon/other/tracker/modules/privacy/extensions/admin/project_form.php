<?php

/**
* Tracker 2.1.0
* 
* Project Plugin module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Privacy
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class tracker_admin_project_form__privacy_field_privacy extends tracker_admin_project_form_main implements tracker_admin_project_form
{
	/**
	 * Returns HTML data in an array structure for popup display
	 *
	 * @return array the tab data
	 * @access public
	 * @since 2.0.0
	 */
	public function getContent() { return array(); }
	/**
	 * Returns javascript for inclusion
	 *
	 * @return string JS data for include
	 * @access public
	 * @since 2.0.0
	 */
	public function getJavascript() { return NULL; }
	/**
	 * Returns array containing tab information
	 *
	 * @return array the extra tabs
	 * @access public
	 * @since 2.0.0
	 */
	public function getTabs() { return array(); }
	/**
	 * Runs any save commands on the data presented in the additional tabs
	 *
	 * @param array $data array of data for saving
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function save( $data=array() ) {}
}

?>