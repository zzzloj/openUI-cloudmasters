<?php

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 11276 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class galleryMemberSync
{
	/**#@+
	 * Registry references
	 *
	 * @var		object
	 */
	public $registry;
	public $DB;
	/**#@-*/
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
	}
	
	/**
	 * This method is run when a member is flagged as a spammer
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onSetAsSpammer( $member )
	{
		if( $member['member_id'] )
		{
			$this->DB->update( 'gallery_images',	array( 'image_approved' => 0 ), "image_member_id={$member['member_id']}" );
			$this->DB->update( 'gallery_comments',	array( 'comment_approved' => 0 ), "comment_author_id={$member['member_id']}" );
			
			$this->_recountImages( $member );
		}
	}
	
	/**
	 * This method is run when a member is un-flagged as a spammer
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 * @todo	[Future] Track what was disabled in onSetAsSpammer() and only undo those
	 */
	public function onUnSetAsSpammer( $member )
	{
		if ( $member['member_id'] )
		{
			$this->DB->update( 'gallery_images',	array( 'image_approved' => 1 ), "image_member_id={$member['member_id']}" );
			$this->DB->update( 'gallery_comments',	array( 'comment_approved' => 1 ), "comment_author_id={$member['member_id']}" );
			
			$this->_recountImages( $member );
		}
	}

	/**
	 * Recount member images
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	private function _recountImages( $member )
	{
		//-----------------------------------------
		// Gallery library
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		$imgs		= array();
		$imgIdQuery	= '';
		$albums		= array();
		$cats		= array();

		//-----------------------------------------
		// Grab image ids from comments
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'DISTINCT(comment_img_id)',
								 'from'		=> 'gallery_comments',
								 'where'	=> 'comment_author_id=' . intval( $member['member_id'] ) ) );
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$imgs[] = $row['comment_img_id'];
		}

		//-----------------------------------------
		// Get the album/cat ids for the comments
		//-----------------------------------------

		if( is_array( $imgs ) && count( $imgs ) )
		{
			$imgIdQuery = ' OR image_id IN( ' . implode( ',', $imgs ) . ' )';
		}

		//-----------------------------------------
		// Grab unique category ids
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'DISTINCT(image_category_id)',
								 'from'		=> 'gallery_images',
								 'where'	=> 'image_member_id=' . intval( $member['member_id'] ) . $imgIdQuery ) );
		
		$this->DB->execute();
		
		
		while( $row = $this->DB->fetch() )
		{
			$cats[]		= $row['image_category_id'];
		}
		
		//-----------------------------------------
		// Grab unique album ids
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'DISTINCT(image_album_id)',
								 'from'		=> 'gallery_images',
								 'where'	=> 'image_album_id > 0 AND image_member_id=' . intval( $member['member_id'] ) . $imgIdQuery ) );
		
		$this->DB->execute();
		
		
		while( $row = $this->DB->fetch() )
		{
			$albums[]	= $row['image_album_id'];
		}
		
		//-----------------------------------------
		// Rebuild necessary caches
		//-----------------------------------------

		if ( is_array( $albums ) && count( $albums ) )
		{
			foreach( $albums as $id )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}
		}

		if ( is_array( $cats ) && count( $cats ) )
		{
			foreach( $cats as $id )
			{
				$this->registry->gallery->helper('categories')->rebuildCategory( $id );
			}
		}

		if( count($albums) OR count($cats) )
		{
			$this->registry->gallery->rebuildStatsCache();
		}
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 */
	public function onDelete( $mids )
	{
		//-----------------------------------------
		// Gallery library
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		//-----------------------------------------
		// Delete albums
		//-----------------------------------------

		$albums	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums', 'where' => 'album_owner_id' . $mids ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$albums[ $r['album_id'] ]	= $r;
		}

		foreach( $albums as $album )
		{
			$this->registry->gallery->helper('albums')->remove( $album );
		}

		//-----------------------------------------
		// Delete images
		//-----------------------------------------

		$images = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_images', 'where' => 'image_member_id' . $mids ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$images[] = $r;
		}
		
		$this->registry->gallery->helper('moderate')->deleteImages( $images );
		
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->DB->update( 'gallery_comments', array( 'comment_author_id' => 0 ), 'comment_author_id' . $mids );
		
		//-----------------------------------------
		// Just delete
		//-----------------------------------------

		$this->DB->delete( 'gallery_bandwidth'	, 'member_id' . $mids );
		$this->DB->delete( 'gallery_ratings'	, 'rate_member_id' . $mids );
		
		//-----------------------------------------
		// Caches
		//-----------------------------------------
		
		$this->registry->gallery->helper('categories')->rebuildCategory( 'all' );
		$this->registry->gallery->rebuildStatsCache();
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 */
	public function onMerge( $member, $member2 )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->DB->update( 'gallery_albums', array( 'album_owner_id' => $member['member_id'] ), 'album_owner_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_bandwidth', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_comments', array( 'comment_author_id' => $member['member_id'], 'comment_author_name' => $member['members_display_name'] ), 'comment_author_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_images', array( 'image_member_id' => $member['member_id'] ), 'image_member_id=' . $member2['member_id'] );
		$this->DB->update( 'gallery_ratings', array( 'rate_member_id' => $member['member_id'] ), 'rate_member_id=' . $member2['member_id'] );
	}

	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{
		//-----------------------------------------
		// Fix comments
		//-----------------------------------------
		
		$this->DB->update( 'gallery_comments', array( 'comment_author_name' => $new_name ), 'comment_author_id=' . $id );
	}
}