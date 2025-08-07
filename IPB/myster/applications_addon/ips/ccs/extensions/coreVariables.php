<?php
/**
 * <pre>
 * Invision Power Services
 * Determine which caches to load, and how to recache them
 * Last Updated: $Date: 2011-12-21 20:29:11 -0500 (Wed, 21 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10056 $
 */

$_LOAD = array( 'bbcode'			=> 1,
				'emoticons'			=> 1,
				'profilefields'		=> 1,
				'ranks'				=> 1,
				'badwords'			=> 1, 
				'attachtypes'		=> 1,
				'reputation_levels'	=> 1,
				'ccs_databases'		=> 1,
				'ccs_fields'		=> 1,
				'ccs_frontpage'		=> 1, 
				'moderators'		=> 1,
				'sharelinks'		=> 1
				);

$CACHE['ccs_databases']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'ccs' ) . '/modules_admin/databases/databases.php',
								'recache_class'		=> 'admin_ccs_databases_databases',
								'recache_function'	=> 'rebuildCache' 
							);

$CACHE['ccs_fields']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'ccs' ) . '/modules_admin/databases/fields.php',
								'recache_class'		=> 'admin_ccs_databases_fields',
								'recache_function'	=> 'rebuildCache' 
							);

$CACHE['ccs_menu']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'ccs' ) . '/modules_admin/settings/menu.php',
								'recache_class'		=> 'admin_ccs_settings_menu',
								'recache_function'	=> 'recacheMenu' 
							);

/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET		= array();
$_RESETACP	= array();

if( IN_ACP )
{
	if( $_GET['do'] == 'preview' AND $_GET['module'] == 'blocks' AND strpos( $_SERVER['HTTP_REFERER'], 'do=preview' ) !== false )
	{
		$_RESETACP['do']				= '';
		$_RESETACP['wizard_session']	= '';
		$_RESETACP['section']			= '';
	}
}
