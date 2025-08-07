<?php
/**
 * @file		images.php 	Comment plugin class for Gallery images
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-11-12 11:43:40 -0500 (Mon, 12 Nov 2012) $
 * @version		v5.0.5
 * $Revision: 11592 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_gallery_images extends classes_comments_renderer
{
	/**
	 * Registry reference
	 *
	 * @var		object
	 */
	protected $registry;
	
	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	private $_remap	= array(
							'comment_id'			=> 'comment_id',
							'comment_author_id'		=> 'comment_author_id',
							'comment_author_name'	=> 'comment_author_name',
							'comment_text'			=> 'comment_text',
							'comment_ip_address'	=> 'comment_ip_address',
							'comment_edit_date'		=> 'comment_edit_time',
							'comment_date'			=> 'comment_post_date',
							'comment_approved'		=> 'comment_approved',
							'comment_parent_id'		=> 'comment_img_id'
							);

	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	private $_parentRemap	= array(
									'parent_id'			=> 'image_id',
									'parent_owner_id'	=> 'image_member_id',
									'parent_parent_id'	=> 'image_album_id',
									'parent_title'		=> 'image_caption',
									'parent_seo_title'	=> 'image_caption_seo',
									'parent_date'		=> 'image_date'
									);

	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//-----------------------------------------
		// Create shortcuts
		//-----------------------------------------

		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		if ( ! $this->registry->isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Who am I?
	 * 
	 * @return	@e string
	 */
	public function whoAmI()
	{
		return 'gallery-images';
	}
	
	/**
	 * Who am I?
	 * 
	 * @return	@e string
	 */
	public function seoTemplate()
	{
		return 'viewimage';
	}
	
	/**
	 * Comment table
	 *
	 * @return	@e string
	 */
	public function table()
	{
		return 'gallery_comments';
	}
    
	/**
	 * Fetch parent
	 *
	 * @return	@e array
	 */
	public function fetchParent( $id )
	{
		return $this->registry->gallery->helper('image')->fetchImage( $id );
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	@e array
	 */
	public function settings()
	{
		return array(
					'urls-showParent'	=> "app=gallery&amp;image=%s",
					'urls-report'		=> $this->getReportLibrary()->canReport('gallery') ? "app=core&amp;module=reports&amp;rcom=gallery&amp;commentId=%s&amp;ctyp=comment" : '',
					);
	}

	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	@e array	Array of GENERIC data
	 */
	public function preSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Get image and category data
			//-----------------------------------------

			$parent		= $this->fetchParent( $array['comment_parent_id'] );
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $parent['image_category_id'] );

			//-----------------------------------------
			// Do comments require approval?
			//-----------------------------------------

			if ( $array['comment_approved'] and $category['category_approve_com'] )
			{
				$array['comment_approved']	= $this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'], null, 'mod_can_approve_comments' ) ? 1 : 0;
			}
			
			//-----------------------------------------
			// Call data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $array, 'galleryAddImageComment' );
		}
		else
		{
			//-----------------------------------------
			// Call data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $array, 'galleryEditImageComment' );
		}
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return	@e array	Array of GENERIC data
	 */
	public function postSave( $type, array $array )
	{
		$parent		= $this->fetchParent( $array['comment_parent_id'] );

		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Resync image
			//-----------------------------------------

			$this->registry->gallery->helper('image')->resync( $parent['image_id'] );

			//-----------------------------------------
			// Resync container
			//-----------------------------------------

			if( $parent['image_album_id'] )
			{
				$this->registry->gallery->helper('albums')->resync( $parent['image_album_id'] );
			}
			else
			{
				$this->registry->gallery->helper('categories')->rebuildCategory( $parent['image_category_id'] );
			}
		
			//-----------------------------------------
			// Rebuild stats cache
			//-----------------------------------------

			$this->registry->gallery->rebuildStatsCache();
			
			//-----------------------------------------
			// Call data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $array, 'galleryCommentAddPostSave' );
		}
		else
		{
			//-----------------------------------------
			// Call data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $array, 'galleryCommentEditPostSave' );
		}

		$this->registry->classItemMarking->markRead( array( 'albumID' => $parent['image_album_id'], 'categoryID' => $parent['image_category_id'], 'itemID' => $parent['image_id'] ), 'gallery' );
		
		return $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return	@e void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		//-----------------------------------------
		// Resync image
		//-----------------------------------------

		$parent		= $this->fetchParent( $parentId );

		$this->registry->gallery->helper('image')->resync( $parentId );

		//-----------------------------------------
		// Resync container
		//-----------------------------------------

		if( $parent['image_album_id'] )
		{
			$this->registry->gallery->helper('albums')->resync( $parent['image_album_id'] );
		}
		else
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $parent['image_category_id'] );
		}

		//-----------------------------------------
		// Rebuild stats cache
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Call data hook
		//-----------------------------------------

		$_dataHook	= array(
							'commentIds'	=> $commentIds,
							'parentId'		=> $parentId
							);
		
		IPSLib::doDataHooks( $_dataHook, 'galleryCommentPostDelete' );
	}
	
	/**
	 * Toggles visibility
	 * 
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return	@e void
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
		//-----------------------------------------
		// Resync image
		//-----------------------------------------

		$parent		= $this->fetchParent( $parentId );

		$this->registry->gallery->helper('image')->resync( $parentId );

		//-----------------------------------------
		// Resync container
		//-----------------------------------------

		if( $parent['image_album_id'] )
		{
			$this->registry->gallery->helper('albums')->resync( $parent['image_album_id'] );
		}
		else
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $parent['image_category_id'] );
		}

		//-----------------------------------------
		// Rebuild stats cache
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();
		
		//-----------------------------------------
		// Call data hook
		//-----------------------------------------

		$_dataHook	= array(
							'toggle'		=> $toggle,
							'commentIds'	=> $commentIds,
							'parentId'		=> $parentId
							);
		
		IPSLib::doDataHooks( $_dataHook, 'galleryCommentToggleVisibility' );
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	@e int
	 */
	public function count( $parent )
	{
		//-----------------------------------------
		// Get the image data
		//-----------------------------------------

		if ( is_numeric( $parent ) )
		{
			$parent	= $this->fetchParent( $parent );
		}
		
		//-----------------------------------------
		// Remap the data
		//-----------------------------------------

		$parent	= $this->remapToLocal( $parent, 'parent' );
		$total	= $parent['image_comments'];
		
		//-----------------------------------------
		// Get queued count too if we can moderate
		//-----------------------------------------

		if( $this->registry->gallery->helper('categories')->checkIsModerator( $parent['image_category_id'], null, 'mod_can_approve' ) )
		{
			$total	+= $parent['image_comments_queued'];
		}

		return $total;
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{ 
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$comment	= array();
		
		//-----------------------------------------
		// Check incoming data
		//-----------------------------------------

		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		//-----------------------------------------
		// Fetch image
		//-----------------------------------------

		$parent	= $this->registry->gallery->helper('image')->fetchImage( $array['comment_parent_id'], FALSE, FALSE );

		//-----------------------------------------
		// Reformat comment
		//-----------------------------------------

		if ( $array['comment_id'] )
		{ 
			$comment	= $this->fetchById( $array['comment_id'] );
			$comment	= $this->remapToLocal( is_array($comment) ? $comment : array(), 'comment' );
		}

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		switch( $type )
		{
			case 'view':
				if ( ! $this->registry->gallery->helper('image')->isViewable( $parent ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'edit':
				if ( ! $this->registry->gallery->helper('comments')->canEdit( $comment, $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'add':
				if ( ! $this->registry->gallery->helper('comments')->canComment( $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'delete':
				if ( ! $this->registry->gallery->helper('comments')->canDelete( $comment, $parent, 'image' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'visibility':
			case 'moderate':
				if ( !$this->registry->gallery->helper('categories')->checkIsModerator( $parent['image_category_id'], null, 'mod_can_approve' ) )
				{
					return 'NO_PERMISSION';
				}
			break;
			case 'hide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
			break;
			case 'unhide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
			break;
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------

		return true;
	}

	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	@e array
	 */
	public function remapKeys($type='comment')
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}

	/**
	 * Adjust parameters
	 *
	 * @param	string	Skin template being called
	 * @param	array	Array of parameters to be passed to skin template
	 * @return	array	Skin parameters to be passed to template (array keys MUST be preserved)
	 */
	public function preOutputAdjustment( $template, $params )
	{
		if( $template == 'commentsList' AND IPS_IS_AJAX )
		{
			$params['parent']['_canComment']	= false;
			$params['parent']['_canModerate']	= false;
			$params['data']['canModerate']		= false;

			if( count($params['comments']) )
			{
				foreach( $params['comments'] as $k => $v )
				{
					$params['comments'][ $k ]['comment']['_canEdit']		= false;
					$params['comments'][ $k ]['comment']['_canHide']		= false;
					$params['comments'][ $k ]['comment']['_canUnhide']		= false;
					$params['comments'][ $k ]['comment']['_canApprove']		= false;
					$params['comments'][ $k ]['comment']['_canDelete']		= false;
					$params['comments'][ $k ]['comment']['_canReply']		= false;
				}
			}
		}

		return $params;
	}
}