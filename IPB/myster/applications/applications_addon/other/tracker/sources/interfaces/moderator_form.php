<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Moderator for editing interface
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
 * Moderator form interface to force standards
 *
 * @package Tracker
 * @since 2.0.0
 */
interface tracker_admin_moderator_form
{
	/**
	 * Returns HTML table content for the page.
	 *
	 * @param array $moderator Moderator data
	 * @return string HTML for the form
	 * @access public
	 * @since 2.0.0
	 */
	public function getDisplayContent();

	/**
	 * Process the entries for saving and return
	 *
	 * @return array Array of keys => values for saving
	 * @access public
	 * @since 2.0.0
	 */
	public function getForSave();
}

?>