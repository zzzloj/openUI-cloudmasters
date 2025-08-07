<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Determine which caches to load, and how to recache them
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_LOAD = array(
				'idm_cats'		=> 1,
				'idm_mods'		=> 1,
				);


$valid_reqs = array (
					'index'				=>	array( 'idm_stats' => 1, 'profilefields' => 1, 'ranks' => 1, 'bbcode' => 1, 'badwords' => 1, 'reputation_levels' => 1, 'moderators' => 1, 'emoticons' => 1 ),
					'file'				=>	array( 'idm_cfields' => 1, 'idm_mimetypes' => 1, 'profilefields' => 1, 'ranks' => 1, 'bbcode' => 1, 'badwords' => 1, 'reputation_levels' => 1, 'sharelinks' => 1, 'emoticons' => 1, 'moderators' => 1 ),
					'category'			=>	array( 'idm_mimetypes' => 1, 'emoticons' => 1, 'moderators' => 1 ),
					'ucp'				=>	array( 'bbcode' => 1 ),
					'submit'			=>  array( 'bbcode' => 1, 'idm_cfields' => 1, 'idm_mimetypes' => 1, 'emoticons' => 1, 'badwords' => 1 ),
					'moderate'			=>  array( 'bbcode' => 1, 'idm_mimetypes' => 1, 'emoticons' => 1, 'badwords' => 1 ),
					'search'			=>  array( 'bbcode' => 1, 'emoticons' => 1, 'idm_stats' => 1, 'idm_cfields' => 1 ),
					'download'			=>  array( 'idm_stats' => 1, 'idm_mimetypes' => 1 ),
				 );

$req = ( isset( $valid_reqs[ $_GET['module'] ] ) ? strtolower($_GET['module']) : 'index' );

if( $_GET['showfile'] )
{
	$req	= 'file';
}
else if( $_GET['showcat'] )
{
	$req	= 'category';
}

if ( isset( $valid_reqs[ $req ] ) )
{
	$_LOAD = array_merge( $_LOAD, $valid_reqs[ $req ] );
}
else
{
	$_LOAD = array_merge( $_LOAD, $valid_reqs['index'] );
}

$CACHE['idm_cats']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'downloads' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_categories',
								'recache_function'	=> 'rebuildCatCache' 
							);

$CACHE['idm_mods']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'downloads' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_categories',
								'recache_function'	=> 'rebuildModCache' 
							);
						
$CACHE['idm_cfields']	= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'downloads' ) . '/modules_admin/customize/cfields.php',
								'recache_class'		=> 'admin_downloads_customize_cfields',
								'recache_function'	=> 'rebuildCache' 
							);

$CACHE['idm_stats']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'downloads' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_categories',
								'recache_function'	=> 'rebuildStatsCache' 
							);
						    
$CACHE['idm_mimetypes']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'downloads' ) . '/modules_admin/customize/mimetypes.php',
								'recache_class'		=> 'admin_downloads_customize_mimetypes',
								'recache_function'	=> 'rebuildCache' 
							);


/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

###### Redirect requests... ######

# automodule/com
if ( $_REQUEST['automodule'] == 'downloads' )
{
	$_RESET['app']     = 'downloads';
}

if ( $_REQUEST['autocom'] == 'downloads' )
{
	$_RESET['app']     = 'downloads';
}

# shortcut links
if ( $_REQUEST['showfile'] )
{
	$_RESET['app']		= 'downloads';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'file';
	$_RESET['id']		= intval( $_REQUEST['showfile'] );
}

if ( $_REQUEST['showcat'] )
{
	$_RESET['app']		= 'downloads';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'category';
	$_RESET['id']		= intval( $_REQUEST['showcat'] );
	$_RESET['catid']	= intval( $_REQUEST['showcat'] );
}

if ( $_REQUEST['code'] == 'sst' )
{
	$_RESET['app']		= 'downloads';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'screenshot';
}


# ALL
if ( $_REQUEST['CODE'] or $_REQUEST['code'] )
{
	$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
}


/* Group options */
$_GROUP	= array( 'zero_is_best' => array( 'idm_throttling' ), 'less_is_more' => array( 'idm_wait_period' )  );

