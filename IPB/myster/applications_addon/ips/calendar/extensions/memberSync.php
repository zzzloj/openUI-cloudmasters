<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Calendar core extensions
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

class calendarMemberSync
{
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 */
	public function onDelete( $mids )
	{
		if( ipsRegistry::instance()->DB()->checkForTable( 'cal_events' ) )
		{
			ipsRegistry::instance()->DB()->delete( 'cal_events', 'event_member_id' . $mids );
			ipsRegistry::instance()->DB()->delete( 'cal_event_comments', 'comment_mid' . $mids );
			ipsRegistry::instance()->DB()->delete( 'cal_event_ratings', 'rating_member_id' . $mids );
			ipsRegistry::instance()->DB()->delete( 'cal_event_rsvp', 'rsvp_member_id' . $mids );
			ipsRegistry::instance()->DB()->update( 'cal_import_feeds', array( 'feed_member_id' => 0 ), "feed_member_id" . $mids );
		}
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 */
	public function onMerge( $member, $member2 )
	{
		if( ipsRegistry::instance()->DB()->checkForTable( 'cal_events' ) )
		{
			ipsRegistry::instance()->DB()->update( 'cal_events', array( 'event_member_id' => intval($member['member_id']) ), "event_member_id=" . $member2['member_id'] );
			ipsRegistry::instance()->DB()->update( 'cal_event_comments', array( 'comment_mid' => intval($member['member_id']) ), "comment_mid=" . $member2['member_id'] );
			ipsRegistry::instance()->DB()->delete( 'cal_event_ratings', 'rating_member_id=' . $member2['member_id'] );
			ipsRegistry::instance()->DB()->update( 'cal_import_feeds', array( 'feed_member_id' => intval($member['member_id']) ), "feed_member_id=" . $member2['member_id'] );
			
			//-----------------------------------------
			// Only RSVP once per member per event
			//-----------------------------------------
			
			$_existsMemberOne	= array();
			
			ipsRegistry::instance()->DB()->build( array( 'select' => 'rsvp_event_id', 'from' => 'cal_event_rsvp', 'where' => 'rsvp_member_id=' . $member['member_id'] ) );
			ipsRegistry::instance()->DB()->execute();
			
			while( $r = ipsRegistry::instance()->DB()->fetch() )
			{
				$_existsMemberOne[]	= $r['rsvp_event_id'];
			}

			if( count($_existsMemberOne) )
			{
				ipsRegistry::instance()->DB()->update( 'cal_event_rsvp', array( 'rsvp_member_id' => intval($member['member_id']) ), "rsvp_member_id=" . $member2['member_id'] . " AND rsvp_event_id NOT IN(" . implode( ',', $_existsMemberOne ) . ")" );
			}
			else
			{
				ipsRegistry::instance()->DB()->update( 'cal_event_rsvp', array( 'rsvp_member_id' => intval($member['member_id']) ), "rsvp_member_id=" . $member2['member_id'] );
			}
			
			ipsRegistry::instance()->DB()->delete( 'cal_event_rsvp', 'rsvp_member_id=' . $member2['member_id'] );
		}
	}
}