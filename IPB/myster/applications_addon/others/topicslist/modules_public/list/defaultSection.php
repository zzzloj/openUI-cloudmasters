<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Very simply returns the default section if one is not
* passed in the URL
*/

$DEFAULT_SECTION = 'home';