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
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
 print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
 exit();
}
 
include( IPS_ROOT_PATH . '/applications_addon/other/tracker/extensions/search/engines/sql.php' );

?>