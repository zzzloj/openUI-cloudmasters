<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v5.0.5
 * Define the core notification types
 * Last Updated: $Date: 2012-06-15 18:18:40 -0400 (Fri, 15 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10935 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}



class gallery_notifications
{
	public function getConfiguration()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'new_image'  , 'default' => array( 'email' ), 'disabled' => array(), 'show_callback' => false, 'icon' => 'notify_profilecomment' ),
							);
		return $_NOTIFY;
	}
}