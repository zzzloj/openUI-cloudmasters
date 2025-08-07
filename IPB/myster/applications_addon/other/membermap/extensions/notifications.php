<?php

/**
 * User Notifications
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */

class membermap_notifications
{
	public function getConfiguration()
	{
		ipsRegistry::instance()->class_localization->loadLanguageFile( array( 'public_notifications' ), 'membermap' );
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'membermap_add_location', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_map_add' ),
							array( 'key' => 'membermap_update_location', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_map_update' )
							);

		return $_NOTIFY;
	}
}