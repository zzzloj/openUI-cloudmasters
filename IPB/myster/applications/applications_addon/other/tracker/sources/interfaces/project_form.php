<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Project form editing interface
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

/**
 * Type: Interface
 * Project form interface to force standards
 *
 * @package Tracker
 * @since 2.0.0
 */
interface tracker_admin_project_form
{
	/**
	 * Returns HTML data in an array structure for popup display
	 *
	 * @return array the tab data
	 * @access public
	 * @since 2.0.0
	 */
	public function getContent();
	/**
	 * Returns javascript for inclusion
	 *
	 * @return string JS data for include
	 * @access public
	 * @since 2.0.0
	 */
	public function getJavascript();
	/**
	 * Returns array containing tab information
	 *
	 * @return array the extra tabs
	 * @access public
	 * @since 2.0.0
	 */
	public function getTabs();
	/**
	 * Runs any save commands on the data presented in the additional tabs
	 *
	 * @param array $data array of data for saving
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function save( $data=array() );
}

abstract class tracker_admin_project_form_main extends iptCommand
{
	var $metadata = array();
	var $project = array();
	
	public function doExecute( ipsRegistry $registry ) {}
	
	public function setupData( $project, $metadata )
	{
		$this->project = $project;
		$this->metadata = $metadata;
	}
}

?>