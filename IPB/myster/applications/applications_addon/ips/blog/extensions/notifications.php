<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Define the core notification types
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */
ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );

class blog_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'new_entry', 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'notify_newtopic' ),
							);/*noLibHook*/
							
		return $_NOTIFY;
	}
}

