<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']	      = 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']			= 1;
$CONFIG['can_vnc_unread_content']		= 1;
$CONFIG['can_vnc_filter_by_followed']	= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent'] = 1;