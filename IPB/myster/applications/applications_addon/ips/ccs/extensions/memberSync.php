<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Core extensions
 * Last Updated: $Date: 2011-05-17 22:08:18 -0400 (Tue, 17 May 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 8811 $
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
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8811 $ 
 */
class ccsMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 * @todo 	[Future] Handle file voters
	 */
	public function onDelete( $mids )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$databases	= ipsRegistry::instance()->cache()->getCache('ccs_databases');
		
		if( is_array($databases) AND count($databases) )
		{
			foreach( $databases as $database )
			{
				$this->registry->DB()->update( $database['database_database'], array( 'member_id' => 0 ), 'member_id' . $mids );
			}
		}
		
		$this->registry->DB()->update( 'ccs_database_categories', array( 'category_last_record_member' => 0, 'category_last_record_seo_name' => '' ), 'category_last_record_member' . $mids );
		$this->registry->DB()->update( 'ccs_database_comments', array( 'comment_user' => 0 ), 'comment_user' . $mids );
		$this->registry->DB()->update( 'ccs_database_modqueue', array( 'mod_poster' => 0 ), 'mod_poster' . $mids );
		$this->registry->DB()->update( 'ccs_database_ratings', array( 'rating_user_id' => 0 ), 'rating_user_id' . $mids );
		$this->registry->DB()->update( 'ccs_database_revisions', array( 'revision_member_id' => 0 ), 'revision_member_id' . $mids );
		$this->registry->DB()->update( 'ccs_revisions', array( 'revision_member' => 0 ), 'revision_member' . $mids );
		
		//-----------------------------------------
		// Just delete
		//-----------------------------------------
		
		$this->registry->DB()->delete( 'ccs_database_moderators', "moderator_type='member' AND moderator_type_id" . $mids );
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 * @todo 	[Future] Handle file voters
	 */
	public function onMerge( $member, $member2 )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$databases	= ipsRegistry::instance()->cache()->getCache('ccs_databases');
		
		if( is_array($databases) AND count($databases) )
		{
			foreach( $databases as $database )
			{
				$this->registry->DB()->update( $database['database_database'], array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
			}
		}
		
		$this->registry->DB()->update( 'ccs_database_categories', array( 'category_last_record_member' => $member['member_id'], 'category_last_record_seo_name' => $member['members_seo_name'] ), 'category_last_record_member=' . $member2['member_id'] );
		$this->registry->DB()->update( 'ccs_database_comments', array( 'comment_user' => $member['member_id'] ), 'comment_user=' . $member2['member_id'] );
		$this->registry->DB()->update( 'ccs_database_modqueue', array( 'mod_poster' => $member['member_id'] ), 'mod_poster=' . $member2['member_id'] );
		$this->registry->DB()->update( 'ccs_database_ratings', array( 'rating_user_id' => $member['member_id'] ), 'rating_user_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'ccs_database_revisions', array( 'revision_member_id' => $member['member_id'] ), 'revision_member_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'ccs_revisions', array( 'revision_member' => $member['member_id'] ), 'revision_member=' . $member2['member_id'] );
		
		//-----------------------------------------
		// Just delete
		//-----------------------------------------
		
		$this->registry->DB()->delete( 'ccs_database_moderators', "moderator_type='member' AND moderator_type_id=" . $member2['member_id'] );
	}

	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{
		$_member	= IPSMember::load( $id );
		
		$this->registry->DB()->update( 'ccs_database_categories', array( 'category_last_record_name' => $new_name, 'category_last_record_seo_name' => $_member['members_seo_name'] ), 'category_last_record_member=' . $id );
	}
}