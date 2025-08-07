<?php
/**
 * @file		gallery.conf.php 	Profile tab plugin configuration for Gallery
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		20th February 2002
 * $LastChangedDate: 2012-06-25 20:53:17 -0400 (Mon, 25 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10984 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Plug in name (Default tab name)
*/
$CONFIG['plugin_name']        = "Gallery";

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_gallery';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'gallery';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = IPSLib::appIsInstalled('gallery') ? 1 : 0;

/**
* Order: CANNOT USE 1
*/
$CONFIG['plugin_order'] = 6;