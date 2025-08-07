<?php
/**
* Tracker 2.1.0
* 
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PortalPlugIn
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

/*
 * Kinda hackish, but didn't want to modify members_public_profile. Need to register language for pp_tab_issues
 */
$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'tracker' );

/**
* Plug in name (Default tab name)
*/
$CONFIG['plugin_name']        = 'Issues';

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_issues';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'issues';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = ( ipsRegistry::$settings['tracker_profile_tab'] && ipsRegistry::$settings['tracker_is_online'] ) ? 1 : 0;

/**
* Order
*/
$CONFIG['plugin_order'] = 8;

?>