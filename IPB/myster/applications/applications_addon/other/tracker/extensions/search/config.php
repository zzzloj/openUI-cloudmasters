<?php

/**
* Tracker 2.1.0
*
* Last Updated: $Date: 2012-07-07 00:25:41 +0100 (Sat, 07 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1380 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']	      = 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent'] = 1;

/* Can fetch active content with this app */
$CONFIG['can_activeContent']  = 1;

/* Can fetch user generated content */
$CONFIG['can_userContent'] = 1;

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']		= array( 'issues', 'projects' );

?>