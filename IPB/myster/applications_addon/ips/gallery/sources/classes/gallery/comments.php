<?php
/**
 * @file		comments.php 	IP.Gallery comments library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-08-29 16:48:42 -0400 (Wed, 29 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11302 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_comments
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/	
	
	/**
	 * Constructor
	 *
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $type='images' )
	{
		//-----------------------------------------
		// Set registry objects
		//-----------------------------------------

		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Fetch a comment by ID (no perm checks)
	 *
	 * @param	int			$id			Comment ID
	 * @param	string		$type		Comment type
	 * @param	boolean		$force		Force to reload a comment from the DB instead of using the cache
	 * @return	@e array	Comment data
	 */
	public function fetchById( $id, $type='image', $force=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id	= intval($id);
		
		static $commentsCache	= array();
		
		//-----------------------------------------
		// Fetch if necessary
		//-----------------------------------------

		if ( !isset($commentsCache[ $type ][ $id ]) || $force === true )
		{
			$commentsCache[ $type ][ $id ] = array();
			
			if ( $type == 'image' )
			{
				$commentsCache[ $type ][ $id ]	= $this->DB->buildAndFetch( array(
																				'select'	=> '*',
																 		 		'from'		=> 'gallery_comments',
																 				'where'		=> 'comment_id=' . $id
																		  )		 );
			}
		}

		return $commentsCache[ $type ][ $id ];
	}

	/**
	 * Can comment on this image
	 *
	 * @param	array		Parent (e.g. image) Data 
	 * @param	string		Comment type
	 * @return	@e bool
	 */
	public function canComment( $parent, $type='image' )
	{
		//-----------------------------------------
		// Commenting on images?
		//-----------------------------------------

		if ( $type == 'image' )
		{
			//-----------------------------------------
			// Stored in an album or a category?
			//-----------------------------------------

			if( $parent['image_album_id'] )
			{
				$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent['image_album_id'] );
				$category	= $this->registry->gallery->helper('categories')->fetchCategory( $parent['image_category_id'] );

				//-----------------------------------------
				// Check some album options
				//-----------------------------------------

				if ( ! $this->registry->gallery->helper('albums')->isViewable( $album ) )
				{
					return false;
				}

				if ( ! $album['album_allow_comments'] )
				{
					return false;
				}
			}
			else
			{
				$category	= $this->registry->gallery->helper('categories')->fetchCategory( $parent['image_category_id'] );
			}
			
			//-----------------------------------------
			// Can we view category?
			//-----------------------------------------

			if( ! $this->registry->gallery->helper('categories')->isViewable( $category['category_id'] ) )
			{
				return false;
			}

			//-----------------------------------------
			// Can we comment in category
			//-----------------------------------------
			
			return ( in_array( $category['category_id'], $this->registry->gallery->helper('categories')->member_access['comments'] ) ) ? true : false;
		}
	}
	
	/**
	 * Returns edit button status
	 *
	 * @param	array		Comment
	 * @param	array		Image
	 * @param	string		Comment type
	 * @return	@e boolean
	 */
	public function canDelete( $comment, $parent, $type='image' )
	{
		if ( $type == 'image' )
		{
			//-----------------------------------------
			// INIT and basic checks
			//-----------------------------------------

			if ( ! $this->memberData['member_id'] )
			{
				return false;
			}
			
			if ( $this->memberData['g_is_supmod'] )
			{
				return true;
			}
			
			//-----------------------------------------
			// Can we delete our own comments?
			//-----------------------------------------

			if ( $comment['comment_author_id'] == $this->memberData['member_id'] && $this->memberData['g_del_own'] )
			{
				return true;
			}

			//-----------------------------------------
			// Are we a moderator of the category?  This
			// covers album too, as mods cascade.
			//-----------------------------------------

			if ( $this->registry->gallery->helper('categories')->checkIsModerator( $parent['image_category_id'], $this->memberData, 'mod_can_delete_comments' ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns edit button status
	 *
	 * @param	array		Comment
	 * @param	array		Image
	 * @param	string		Comment type
	 * @return	@e boolean
	 */
	public function canEdit( $comment, $parent, $type='image' )
	{
		//-----------------------------------------
		// INIT and basic checks
		//-----------------------------------------

		if ( ! $this->memberData['member_id'] )
		{
			return false;
		}
		
		if ( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		
		if ( $type == 'image' )
		{
			//-----------------------------------------
			// Can we edit our own comments?
			//-----------------------------------------

			if ( $comment['comment_author_id'] == $this->memberData['member_id'] && $this->memberData['g_edit_own'] )
			{
				//-----------------------------------------
				// Time limit?
				//-----------------------------------------

				if ( $this->memberData['g_edit_cutoff'] > 0 )
				{
					if ( $comment['comment_post_date'] < ( time() - ( intval( $this->memberData['g_edit_cutoff'] ) * 60 ) ) )
					{
						return false;
					}
				}

				return true;
			}

			//-----------------------------------------
			// Are we a moderator of the category?  This
			// covers album too, as mods cascade.
			//-----------------------------------------

			if ( $this->registry->gallery->helper('categories')->checkIsModerator( $parent['image_category_id'], $this->memberData, 'mod_can_edit_comments' ) )
			{
				return true;
			}
		}
		
		return false;
	}
}