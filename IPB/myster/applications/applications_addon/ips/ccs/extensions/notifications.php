<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Content notification options
 * Last Updated: $Date: 2011-03-10 16:23:00 -0500 (Thu, 10 Mar 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		20th January 2010
 * @version		$Rev: 8022 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */
ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );

class ccs_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'ccs_notifications', 'default' => array( 'email' ), 'disabled' => array() ),
							array( 'key' => 'ccs_approval_notifications', 'default' => array( 'email' ), 'disabled' => array(), 'show_callback' => TRUE ),
							);
					
		return $_NOTIFY;
	}

	public function ccs_approval_notifications()
	{
		$memberData = ipsRegistry::member()->fetchMemberData();

		if( $memberData['g_is_supmod'] )
		{
			return true;
		}

		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_database_moderators' ) );
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$moderators[ $r['moderator_type'] ][]	= $r;
		}

		if( is_array($moderators['group']) AND count($moderators['group']) )
		{
			$_myGroups	= array( $memberData['member_group_id'] );
			
			if( $memberData['mgroup_others'] )
			{
				$_others	= explode( ',', IPSText::cleanPermString( $memberData['mgroup_others'] ) );
				$_myGroups	= array_merge( $_myGroups, $_others );
			}
			
			foreach( $moderators['group'] as $_moderator )
			{
				if( in_array( $_moderator['moderator_type_id'], $_myGroups ) )
				{
					if( $_moderator['moderator_approve_record'] )
					{
						return true;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Then individual member mod perms
		//-----------------------------------------
		
		if( is_array($moderators['member']) AND count($moderators['member']) )
		{
			foreach( $moderators['member'] as $_moderator )
			{
				if( $_moderator['moderator_type_id'] == $memberData['member_id'] )
				{
					if( $_moderator['moderator_approve_record'] )
					{
						return true;
					}
				}
			}
		}
	}
}