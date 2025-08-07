<?php

/**
* Tracker 2.1.0
*
* Notifications setup
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author 		$Author: stoo2000 $
* @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @since		4/24/2010
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */

class tracker_notifications
{
	public function getConfiguration()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_tracker' ), 'tracker' );
		
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'new_issues', 'default' => array( 'email', 'inline' ), 'disabled' => array(), 'icon' => 'notify_newtopic' ),
							array( 'key' => 'issue_reply', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newtopic' )
					);
		return $_NOTIFY;
	}
}