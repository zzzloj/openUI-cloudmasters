<?php
/**
 * @file		user.php 	View the user-submissions page
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2013-04-11 18:03:45 -0400 (Thu, 11 Apr 2013) $
 * @version		v5.0.5
 * $Revision: 12168 $
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_user extends ipsCommand
{
	/**
	 * Like class
	 *
	 * @var		object
	 */
	protected $_like;

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Determine which user we're viewing
		//-----------------------------------------

		if ( !empty($this->request['member_id']) )
		{
			$memberId = $this->request['member_id'];
		}
		elseif ( !empty($this->request['user']) )
		{
			$memberId = $this->request['user'];
		}
		else
		{
			//-----------------------------------------
			// Fall back to the viewing user
			//-----------------------------------------

			$memberId = $this->memberData['member_id'];
		}
		
		//-----------------------------------------
		// Parse the user details
		//-----------------------------------------

		$owner	= IPSMember::buildProfilePhoto( IPSMember::load( $memberId ) );
		
		if ( ! $owner['member_id'] )
		{
			$this->registry->getClass('output')->showError( $this->lang->words['user_album_no_member'], '107400.u1', null, null, 404 );
		}

		//-----------------------------------------
		// Start some output details
		//-----------------------------------------

		$data	= array(
						'owner'		=> $owner,
						);

		//-----------------------------------------
		// Fetch the user's albums
		//-----------------------------------------

		if( !in_array( $this->request['asort_key'], array( 'name', 'date', 'rated', 'images' ) ) )
		{
			$this->request['asort_key']	= '';
		}

		$albumSt	= intval( $this->request['albumSt'] );
		$albums		= $this->registry->gallery->helper('albums')->fetchAlbumsByOwner( $owner['member_id'], array( 
																											'sortKey'			=> $this->request['asort_key'] ? $this->request['asort_key'] : 'name',
																											'sortOrder'			=> $this->request['asort_order'] ? $this->request['asort_order'] : 'asc',
																											'offset'			=> $albumSt,
																											'limit'				=> GALLERY_ALBUMS_PER_PAGE,
																											'getTotalCount'		=> true,
																											'parseAlbumOwner'	=> true,
																											'isViewable'		=> true,
																					)							);
		$albumCount	= $this->registry->gallery->helper('albums')->getCount();

		//-----------------------------------------
		// Extract and load latest images
		//-----------------------------------------

		$albums		= $this->registry->gallery->helper('albums')->extractLatestImages( $albums );

		$data['_albumPages']	= $this->registry->output->generatePagination( array(
																					'totalItems'		=> $albumCount,
																					'itemsPerPage'		=> GALLERY_ALBUMS_PER_PAGE,
																					'currentStartValue'	=> $albumSt,
																					'seoTitle'			=> $owner['members_seo_name'],
																					'seoTemplate'		=> 'useralbum',
																					'baseUrl'			=> 'app=gallery&amp;user=' . $owner['member_id'] ,
																					'startValueKey'		=> 'albumSt',
																			)		);

		//-----------------------------------------
		// Fetch the user's non-album images
		//-----------------------------------------

		if( !in_array( $this->request['sort_key'], array( 'date', 'views', 'rating', 'caption' ) ) )
		{
			$this->request['sort_key']	= '';
		}

		$imageSt	= intval( $this->request['st'] );
		$images		= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																													'sortKey'			=> $this->request['sort_key'] ? $this->request['sort_key'] : 'date',
																													'sortOrder'			=> $this->request['sort_order'] ? $this->request['sort_order'] : 'desc',
																													'honorPinned'		=> false,
																													'offset'			=> $imageSt,
																													'limit'				=> GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
																													//'albumId'			=> 0,
																													'getTotalCount'		=> true,
																													'ownerId'			=> $owner['member_id'],
																													'thumbClass'		=> 'galattach',
																													'link-thumbClass'	=> 'galimageview'
																			)										);
		$imageCount	= $this->registry->gallery->helper('image')->getCount();

		$data['_imagePages']	= $this->registry->output->generatePagination( array(
																					'totalItems'		=> $imageCount,
																					'itemsPerPage'		=> GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
																					'currentStartValue'	=> $imageSt,
																					'seoTitle'			=> $owner['members_seo_name'],
																					'seoTemplate'		=> 'useralbum',
																					'baseUrl'			=> 'app=gallery&amp;user=' . $owner['member_id'],
																			)		);

		//-----------------------------------------
		// Build output
		//-----------------------------------------

		$output	= $this->registry->output->getTemplate('gallery_albums')->userGalleryView( $data, $images, $albums );

		$this->registry->getClass('output')->addCanonicalTag( 'app=gallery&amp;user=' . $owner['member_id'], $owner['members_display_name'], 'useralbum' );
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;user=' . $owner['member_id'], 'publicNoSession', $owner['members_display_name'], 'useralbum' ) );
		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		$this->registry->getClass('output')->addNavigation( sprintf( $this->lang->words['member_x_albums'], $owner['members_display_name'] ), '' );
		$this->registry->getClass('output')->setTitle( sprintf( $this->lang->words['member_x_albums'], $owner['members_display_name'] ) . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $output );
		$this->registry->getClass('output')->sendOutput();
	}
}