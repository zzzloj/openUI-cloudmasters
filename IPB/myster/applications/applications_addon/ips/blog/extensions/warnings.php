<?php
/**
 * @file		warnings.php 	Warnings extension for blog
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Original: Mark)
 * @since		-
 * $LastChangedDate: 2013-05-01 17:35:09 -0400 (Wed, 01 May 2013) $
 * @version		v2.6.3
 * $Revision: 12211 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		warnings_blog
 * @brief		Warnings Extension for blog
 */
class warnings_blog
{
	/**
	 * Get Content URL
	 *
	 * @param	array		$warning		Row from members_warn_logs
	 * @return	@e array	array( url => URL to the content the warning came from, title => Title )
	 */
	public function getContentUrl( $warning )
	{	
		$exploded = explode( '-', $warning['wl_content_id2'] );
	
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$comments = classes_comments_bootstrap::controller( $warning['wl_content_id1'] );
		
		$parent = $comments->fetchParent( $exploded[0] );
	
		if( is_null($parent) )
		{
			return null;
		}

		$parent = $comments->remapFromLocal( $parent, 'parent' );

		return array( 'url' => ipsRegistry::getClass('output')->buildUrl( "app=core&module=global&section=comments&fromApp={$warning['wl_content_id1']}&do=findComment&parentId={$exploded[0]}&comment_id={$exploded[1]}" ), 'title' => $parent['parent_title'] );
	}
}