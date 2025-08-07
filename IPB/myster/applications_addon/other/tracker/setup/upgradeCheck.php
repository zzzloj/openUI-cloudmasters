<?php
/**
* Tracker 2.1.0
*
* Upgrade Check
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

class tracker_upgradeCheck
{
	/**
	 * Check we can upgrade
	 *
	 * @return	mixed	Boolean true or error message
	 */
	public function checkForProblems()
	{
		/*
		 * Annoyingly we need to include a file in this directory,
		 * but if it exists, IPBoard will show it as an option to install
		 * So users will have to remove the files everytime they upgrade
		 */
		if ( is_dir( IPS_ROOT_PATH . 'applications_addon/ips/tracker/' ) )
		{
			return "An older copy of Tracker's files still exist, please remove the " . IPS_ROOT_PATH . "applications_addon/ips/tracker/ folder before continuing.";
		}

		return TRUE;
	}
}