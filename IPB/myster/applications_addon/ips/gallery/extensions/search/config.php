<?php
/**
 * @file		config.php 	IP.Gallery search configuration file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-07-11 17:58:49 -0400 (Wed, 11 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11065 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']					= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']			= 1;

/* Can further filter VNC by removing non-followed 'categories' */
$CONFIG['can_vnc_filter_by_followed']	= 1;
$CONFIG['can_vnc_unread_content']		= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent']				= 1;

/* Can search tags */
if ( !isset( ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'] ) OR ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'] == 'images' )
{
	$CONFIG['can_searchTags']		= 1;
}
else
{
	$CONFIG['can_searchTags']		= 0;
}

/* Content types, put the default one first */
$CONFIG['contentTypes']					= array( 'images', 'albums', 'comments' );

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']			= array( 'images', 'albums' );