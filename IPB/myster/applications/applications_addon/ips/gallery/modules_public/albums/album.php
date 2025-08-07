<?php
/**
 * @file		album.php 	IP.Gallery album view and management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * $LastChangedDate: 2013-02-14 19:17:38 -0500 (Thu, 14 Feb 2013) $
 * @version		v5.0.5
 * $Revision: 11992 $
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_album extends ipsCommand
{
	/**
	 * Follow class
	 * 
	 * @var	object
	 */
	protected $_follow;

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get our album ID
		//-----------------------------------------

		$albumId	= intval( $this->request['album'] );
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_showAlbum( $albumId );
			break;

			case 'delete':
				$this->_delete( $albumId );
			break;
		}
	}

	/**
	 * Display an album
	 * 
	 * @param	int			$albumId
	 * @return 	@e void
	 */
	protected function _showAlbum( $albumId )
	{
		//-----------------------------------------
		// Multimod
		//-----------------------------------------

		$this->request['selectediids'] = IPSCookie::get('modiids');

		//-----------------------------------------
		// Fetch the album
		//-----------------------------------------

		$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		//-----------------------------------------
		// Verify we can view
		//-----------------------------------------

		$this->registry->gallery->helper('albums')->isViewable( $album, true );

		//-----------------------------------------
		// Check the permalink
		//-----------------------------------------

		$this->registry->getClass('output')->checkPermalink( $album['album_name_seo'] );
		
		//-----------------------------------------
		// INIT some vars
		//-----------------------------------------

		$start	= intval( $this->request['st'] );
		$cover	= array();
		
		//-----------------------------------------
		// Store some album data
		//-----------------------------------------

		$album['_canEdit']		= $this->registry->gallery->helper('albums')->canEdit( $album );
		$album['_canDelete']	= $this->registry->gallery->helper('albums')->canDelete( $album );
		$album['_canModerate']	= $this->registry->gallery->helper('albums')->canModerate( $album );

		$album['_totalViewableImages']	= intval($album['album_count_imgs']);
		
		if ( $this->registry->gallery->helper('categories')->checkIsModerator( $album['album_category_id'], null, 'mod_can_approve' ) )
		{
			$album['_totalViewableImages']	+= intval($album['album_count_imgs_hidden']);
		}

		//-----------------------------------------
		// Get the like/follow class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow	= classes_like::bootstrap( 'gallery', 'albums' );

		//-----------------------------------------
		// Get the follow data
		//-----------------------------------------

		$follow	= $this->_follow->render( 'summary', $album['album_id'] );
		
		//-----------------------------------------
		// Get the owner data
		//-----------------------------------------

		$_owner	= IPSMember::load( $album['album_owner_id'] );

		if ( empty($_owner['member_id']) )
		{
			$_owner	= IPSMember::setUpGuest();
		}
		
		$album['owner']	= IPSMember::buildProfilePhoto( $_owner );

		//-----------------------------------------
		// Fetch up to 5 more recent albums
		//-----------------------------------------

		$recentAlbums		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																									'isViewable'		=> true,
																									'sortKey'			=> 'album_last_img_date',
																									'sortOrder'			=> 'desc',
																									'offset'			=> 0,
																									'limit'				=> 5,
																									'album_owner_id'	=> $_owner['member_id'],
																									'skip'				=> $album['album_id'],
																					)				);

		//-----------------------------------------
		// Get x per page images
		//-----------------------------------------

		$images	= $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'], array( 'parseDescription' => true, 'limit' => GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE, 'offset' => $start, 'sortKey' => isset( $this->request['sort_key'] ) ? $this->request['sort_key'] : $album['album_sort_options__key'], 'sortOrder' => isset( $this->request['sort_order'] ) ? $this->request['sort_order'] : $album['album_sort_options__dir'] ) );
		
		//-----------------------------------------
		// Get pagination
		//-----------------------------------------

		$album['_pages']	= $this->registry->output->generatePagination( array(
																				'totalItems'		=> $album['_totalViewableImages'],
																				'itemsPerPage'		=> GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
																				'currentStartValue'	=> $start,
																				'seoTitle'			=> $album['album_name_seo'],
																				'seoTemplate'		=> 'viewalbum',
																				'baseUrl'			=> "app=gallery&amp;album={$album['album_id']}&amp;sort_key={$this->request['sort_key']}&amp;sort_order={$this->request['sort_order']}"
																		)		);

		//-----------------------------------------
		// Cover image
		//-----------------------------------------

		$cover			= $this->registry->gallery->helper('image')->fetchImage( $album['album_cover_img_id'] ? $album['album_cover_img_id'] : $album['album_last_img_id'], false, false );
		$cover['tag']	= $this->registry->gallery->helper('image')->makeImageLink( array_merge( $cover, array( '_isRead' => true, 'image_approved' => 1 ) ), array( 'h1image' => true, 'type' => 'thumb', 'link-type' => 'page' ) );

		//-----------------------------------------
		// Set SEO properties
		//-----------------------------------------

		$this->registry->getClass('output')->addCanonicalTag( ( $start ) ? 'app=gallery&amp;album=' . $album['album_id'] . '&st=' . $start : 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;album=' .  $album['album_id'], 'publicNoSession', $album['album_name_seo'], 'viewalbum' ) );

		$this->settings['meta_imagesrc']	= $this->registry->gallery->helper('image')->makeImageTag( $cover, array( 'type' => 'thumb', 'link-type' => 'src' ) );

		$this->registry->output->addMetaTag( 'keywords', $album['album_name'] . ' ' . $album['album_description'], true );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", $album['album_name'] . ' ' . $album['album_description'] ) ), false, 155 );

		//-----------------------------------------
		// Set page title
		//-----------------------------------------

		$this->registry->getClass('output')->setTitle( $album['album_name'] . ( $start ? $this->lang->words['page_title_pagination'] : '' )  . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );

		//-----------------------------------------
		// Set navigation
		//-----------------------------------------

		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );

		foreach( $this->registry->gallery->helper('categories')->getNav( $album['album_category_id'] ) as $nav )
		{
			$this->registry->getClass('output')->addNavigation( $nav[0], $nav[1], $nav[2], 'viewcategory' );
		}

		$this->registry->getClass('output')->addNavigation( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $this->registry->output->getTemplate('gallery_albums')->albumView( $cover, $images, $album, $follow, $recentAlbums ) );
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Deletes an album
	 * 
	 * @param	int			$albumId
	 * @return 	@e void
	 */
	protected function _delete( $albumId )
	{
		//-----------------------------------------
		// Check security key
		//-----------------------------------------

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->output->showError( 'no_permission', '1-albums-albums-delete-0', null, null, 403 );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$move_to_album_id		= intval( $this->request['move_to_album_id'] );
		$move_to_category_id	= intval( $this->request['move_to_category_id'] );
		$moveToAlbum			= array();
		$moveToCategory			= array();
		$doDelete				= intval( $this->request['doDelete'] );
		$album					= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		//-----------------------------------------
		// Do we have permission to delete this album?
		//-----------------------------------------

		if ( ! $this->registry->gallery->helper('albums')->canDelete( $album ) )
		{
			$this->registry->output->showError( $this->lang->words['4_move_fail'], '1-albums-albums-delete-1', null, null, 403 );
		}
				
		//-----------------------------------------
		// Are we moving the images?
		//-----------------------------------------

		if ( $move_to_album_id && ! $doDelete )
		{
			$moveToAlbum	= $this->registry->gallery->helper('albums')->fetchAlbum( $move_to_album_id );
			
			if ( ! $this->registry->gallery->helper('albums')->isUploadable( $moveToAlbum ) )
			{
				$this->registry->output->showError( $this->lang->words['4_move_fail'], '1-albums-albums-delete-2', null, null, 403 );
			}
		}

		if ( $move_to_category_id && ! $doDelete )
		{
			$moveToCategory	= $this->registry->gallery->helper('categories')->fetchCategory( $move_to_category_id );
			
			if ( !in_array( $move_to_category_id, $this->registry->gallery->helper('categories')->fetchImageCategories() ) OR !$this->registry->gallery->helper('categories')->isUploadable( $move_to_category_id ) )
			{
				$this->registry->output->showError( $this->lang->words['4_move_fail'], '1-albums-albums-delete-c', null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Delete this album
		//-----------------------------------------

		$result	= $this->registry->gallery->helper('moderate')->deleteAlbum( $albumId, $moveToAlbum, $moveToCategory );
		
		//-----------------------------------------
		// If we moved images to somewhere, go there, otherwise go to parent category
		//-----------------------------------------

		if ( $moveToAlbum['album_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $moveToAlbum['album_id'], $moveToAlbum['album_name_seo'], false, 'viewalbum' );
		}
		else if ( $moveToCategory['category_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $moveToCategory['category_id'], $moveToCategory['category_name_seo'], false, 'viewcategory' );
		}
		else
		{
			$parent	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );

			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $parent['category_id'], $parent['category_name_seo'], false, 'viewcategory' );
		}
	}
}