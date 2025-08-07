<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Config file
 * Last Updated: $Date: 2012-03-08 11:24:47 -0500 (Thu, 08 Mar 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10411 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/* Can search with this app */
$CONFIG['can_search']			= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']	= 1;
$CONFIG['can_vnc_unread_content']        = 0;

/* Can fetch user generated content */
$CONFIG['can_userContent']		= 1;

/* Can fetch followed content */
$CONFIG['can_vnc_filter_by_followed']    = 1;

$CONFIG['contentTypes'] = ( ipsRegistry::$request['do'] == 'user_activity' OR ipsRegistry::$request['do'] == 'viewNewContent' OR ipsRegistry::$request['search_tags'] ) ? array() : array( 'pages' );

/* Gotta load cache, then loop through for databases */
$databases	= ipsRegistry::instance()->cache()->getCache('ccs_databases');
$_taggable	= array();

if( is_array($databases) AND count($databases) )
{
	foreach( $databases as $database )
	{
		if ( ipsRegistry::instance()->permissions->check( 'view', $database ) == TRUE AND $database['database_search'] )
		{
			$CONFIG['contentTypes'][]	= 'database_' . $database['database_id'];
			$_taggable[]				= 'database_' . $database['database_id'];

			if( !$database['database_forum_comments'] AND trim( $database['perm_5'], ' ,' ) )
			{
				$CONFIG['contentTypes'][]	= 'database_' . $database['database_id'] . '_comments';
			}
		}
	}
}

$CONFIG['followContentTypes'] = array();

if( is_array($databases) AND count($databases) )
{
	foreach( $databases as $database )
	{
		if ( ipsRegistry::instance()->permissions->check( 'view', $database ) == TRUE AND $database['database_search'] )
		{
			$CONFIG['followContentTypes'][]	= 'ccs_custom_database_' . $database['database_id'] . '_records';
			
			ipsRegistry::getClass('class_localization')->words['followed_type__' . 'ccs_custom_database_' . $database['database_id'] . '_records' ]	= $database['database_name'];
		}
	}
}

//-----------------------------------------
// Set default
//-----------------------------------------

if ( empty( ipsRegistry::$request['search_app_filters']['ccs']['searchInKey'] ) AND in_array( ipsRegistry::$settings['ccs_default_search'], $CONFIG['contentTypes'] ) )
{
	ipsRegistry::$request['search_app_filters']['ccs']['searchInKey']	= ipsRegistry::$settings['ccs_default_search'];
}
else if( empty( ipsRegistry::$request['search_app_filters']['ccs']['searchInKey'] ) )
{
	ipsRegistry::$request['search_app_filters']['ccs']['searchInKey']	= $CONFIG['contentTypes'][0];
}

//-----------------------------------------
// Tag support
//-----------------------------------------

if ( count($_taggable) AND ( !isset( ipsRegistry::$request['search_app_filters']['ccs']['searchInKey'] ) OR in_array( ipsRegistry::$request['search_app_filters']['ccs']['searchInKey'], $_taggable ) ) )
{
	$CONFIG['can_searchTags']		= 1;
}
else
{
	$CONFIG['can_searchTags']		= 0;
}