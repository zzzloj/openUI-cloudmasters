<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.Downloads API file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );/*noLibHook*/
}

/**
 * API: IDM
 *
 * This class will pull the last 5 IDM submissions a user has submitted
 *
 * @package		IP.Downloads
 * @author  	Brandon Farber
 * @version		2.1
 * @since		2.2.0
 */
class apiDownloads extends apiCore
{
	/**
	 * Returns an array of download data
	 *
	 * @access	public
	 * @param	integer	Member id
	 * @param	integer	Max number to pull
	 * @param	integer	Pull even if no member id is set
	 * @param	string	Order by
	 * @param	array 	Additional filters (they are added to where clause AS IS)
	 * @return	array	Array of download data
	 */
	public function returnDownloads( $member_id = 0, $limit = 5, $nomember = 0, $order='', $filters=array() )
	{
		/* App installed? */
		if( !IPSLib::appIsInstalled('downloads') )
		{
			return array();
		}
		
		/* No member ID? */
		if( !$member_id AND !$nomember )
		{
			return array();
		}
		
		/* Not online? */
		if( $this->settings['idm_online'] == 0 )
		{
			$offline_access = explode( ",", $this->settings['idm_offline_groups'] );
			
			$my_groups = array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroups_other'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSLib::cleanPermString( $this->memberData['mgroups_other'] ) ) );
			}
			
			$continue = 0;
			
			foreach( $my_groups as $group_id )
			{
				if( in_array( $group_id, $offline_access ) )
				{
					$continue = 1;
					break;
				}
			}
			
			if( $continue == 0 )
			{
				// Offline, and we don't have access
				
				return array();
			}
		}
				
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$files		= array();
		$where		= array();
		
		$member_id	= intval($member_id);
		
		if( $member_id )
		{
			$where[]	= 'file_submitter=' . $member_id;
		}
		
		if( is_array($filters) AND count($filters) )
		{
			$where	= array_merge( $where, $filters );
		}
		
		$order	= $order ? $order : 'file_submitted DESC';

		//-----------------------------------------
		// Load caches - uses external lib if avail
		//-----------------------------------------	
		
		if( !$this->registry->isClassLoaded('categories') )
		{
			define( 'SKIP_ONLINE_CHECK', true );
			ipsRegistry::getAppClass( 'downloads' );
		}
		
		$categories = $this->registry->getClass('categories')->member_access['show'];

		if( !is_array($categories) OR !count($categories) )
		{
			//No category permissions
			
			return array();
		}
		
		$memberIds	= array();
		
		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'downloads_files',
								 'where'	=> ( count($where) ? implode( ' AND ', $where ) . ' AND ' : '' ) . 'file_open=1 AND file_cat IN (' . implode( ',', $categories ) . ')',									 					
								 'order'	=> $order,
								 'limit'	=> array( 0, $limit )
						)		);
										
		$res = $this->DB->execute();
		
		while( $r = $this->DB->fetch($res) )
		{
			$r['_isRead']				= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['file_cat'], 'itemID' => $r['file_id'], 'itemLastUpdate' => $r['file_updated'] ), 'downloads' );
			$r['members_display_name']	= $r['members_display_name'] ? $r['members_display_name'] : $this->lang->words['global_guestname'];
			$r['category_name']			= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ]['cname'];
			$r['cname_furl']			= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ]['cname_furl'];
			$files[ $r['file_id'] ]		= $r;
			
			$memberIds[ $r['file_submitter'] ]	= $r['file_submitter'];
		}
		
		if( count($memberIds) )
		{
			$members	= IPSMember::load( $memberIds );
			
			// Add in guest
			$members[0] = IPSMember::setUpGuest();
			
			foreach( $files as $k => $v )
			{
				$files[ $k ]	= array_merge( $files[ $k ], IPSMember::buildDisplayData( $members[ $v['file_submitter'] ] ) );
			}
		}

		return $files;
	}	
}