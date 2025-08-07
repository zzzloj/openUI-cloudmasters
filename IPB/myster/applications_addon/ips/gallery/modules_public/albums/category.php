<?php
/**
 * @file		category.php 	Display items in a category
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-05-09 13:25:41 -0400 (Wed, 09 May 2012) $
 * @version		v5.0.5
 * $Revision: 10716 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_gallery_albums_category
 * @brief		Category listing
 */
class public_gallery_albums_category extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Multimod
		//-----------------------------------------

		$this->request['selectediids'] = IPSCookie::get('modiids');

		//-----------------------------------------
		// Get category data
		//-----------------------------------------

		$thisCategory	= intval($this->request['category']);
		$category		= $this->registry->gallery->helper('categories')->fetchCategory( $thisCategory );

		$category['_canApprove']	= $this->registry->gallery->helper('categories')->checkIsModerator( $thisCategory, null, 'mod_can_approve' );
		$category['_canApproveC']	= $this->registry->gallery->helper('categories')->checkIsModerator( $thisCategory, null, 'mod_can_approve_comments' );
		$category['_canModerate']	= $this->registry->gallery->helper('categories')->checkIsModerator( $thisCategory );

		$category['_coverImage']	= $category['category_cover_img_id'] ? $category['category_cover_img_id'] : $category['category_last_img_id'];

		if( !$category['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['gallery_category_not_found'], 107101.1, null, null, 404 );
		}

		if( !$this->registry->gallery->helper('categories')->isViewable( $thisCategory ) )
		{
			$this->registry->output->showError( $this->lang->words['gallery_category_no_perm'], 107101.2, null, null, 403 );
		}

		$this->registry->getClass('output')->checkPermalink( $category['category_name_seo'] );

		$navigation	= $this->registry->gallery->helper('categories')->getNav( $thisCategory );

		//-----------------------------------------
		// Fetch any sub-categories
		//-----------------------------------------

		$category_rows	= array();
		$image_ids		= array( $category['_coverImage'] => $category['_coverImage'] );
		
		if( count( $this->registry->gallery->helper('categories')->fetchCategories( $thisCategory ) ) )
		{
			foreach( $this->registry->gallery->helper('categories')->fetchCategories( $thisCategory ) as $categoryId => $categoryData )
			{
				if( $this->registry->gallery->helper('categories')->isViewable( $categoryId ) )
				{
					//-----------------------------------------
					// INIT
					//-----------------------------------------

					$categoryData['_subcategories']	= array();
					$categoryData['_canApprove']	= $this->registry->gallery->helper('categories')->checkIsModerator( $categoryId, null, 'mod_can_approve' );
					$categoryData['_canApproveC']	= $this->registry->gallery->helper('categories')->checkIsModerator( $categoryId, null, 'mod_can_approve_comments' );
					$categoryData['_canModerate']	= $this->registry->gallery->helper('categories')->checkIsModerator( $categoryId );
					$categoryData['_coverImage']	= $categoryData['category_cover_img_id'] ? $categoryData['category_cover_img_id'] : $categoryData['category_last_img_id'];
					$categoryData['_latestImage']	= $categoryData['category_last_img_id'];
					$categoryData['thumb']			= $this->registry->gallery->helper('image')->makeImageLink( array(), array( 'type' => 'thumb', 'coverImg' => true ) );
					$categoryData['coverUrl']		= '';

					//-----------------------------------------
					// Item marking
					//-----------------------------------------

					$rtime							= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'categoryID' => $categoryData['category_id'] ), 'gallery' );
					$categoryData['_hasUnread']		= ( $categoryData['category_last_img_date'] && $categoryData['category_last_img_date'] > $rtime ) ? 1 : 0;

					//-----------------------------------------
					// Subcategories?
					//-----------------------------------------

					if( count( $this->registry->gallery->helper('categories')->fetchCategories( $categoryId ) ) )
					{
						$sub_links	= array();
						
						foreach( $this->registry->gallery->helper('categories')->fetchCategories( $categoryId ) as $_subCategoryId => $_subCategoryData )
						{
							if( $this->registry->gallery->helper('categories')->isViewable( $_subCategoryId ) )
							{
								$_rtime							= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $subcat_data['cid'] ), 'gallery' );
								$_subCategoryData['_hasUnread']	= ( $_subCategoryData['category_last_img_date'] && $_subCategoryData['category_last_img_date'] > $_rtime ) ? 1 : 0;

								$sub_links[]	= $_subCategoryData;
							}
						}
						
						$categoryData['_subcategories'] = $sub_links;
					}
					
					//-----------------------------------------
					// Store image IDs
					//-----------------------------------------

					$image_ids[ $categoryData['_coverImage'] ]			= $categoryData['_coverImage'];
					$image_ids[ $categoryData['category_last_img_id'] ]	= $categoryData['category_last_img_id'];

					//-----------------------------------------
					// And then store our category rows
					//-----------------------------------------

					$category_rows[]	= $categoryData;
				}
			}
		}

		//-----------------------------------------
		// Load up sub-category images
		//-----------------------------------------

		if( count($image_ids) )
		{
			$_categoryImages	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'imageIds' => $image_ids, 'parseImageOwner' => true, 'orderBy' => false ) );

			if( count($_categoryImages) )
			{
				foreach( $category_rows as $_k => $_categoryRow )
				{
					$category_rows[ $_k ]['_coverImage']	= $_categoryRow['_coverImage'] ? $_categoryImages[ $_categoryRow['_coverImage'] ] : array();
					$category_rows[ $_k ]['_latestImage']	= $_categoryRow['_latestImage'] ? $_categoryImages[ $_categoryRow['_latestImage'] ] : array();

					if( $category_rows[ $_k ]['_coverImage'] )
					{
						$category_rows[ $_k ]['_coverImage']['_isRead']	= $category_rows[ $_k ]['_hasUnread'] ? false : true;
					}

					$category_rows[ $_k ]['thumb']			= $this->registry->gallery->helper('image')->makeImageLink( $category_rows[ $_k ]['_coverImage'], array( 'type' => 'thumb', 'coverImg' => true, 'link-container-type' => 'category' ) );
					$category_rows[ $_k ]['coverUrl']		= $this->registry->gallery->helper('image')->makeImageTag( $category_rows[ $_k ]['_coverImage'], array( 'type' => 'medium', 'link-type' => 'src' ) );
					$category_rows[ $_k ]['_latestThumb']	= $this->registry->gallery->helper('image')->makeImageLink( $category_rows[ $_k ]['_latestImage'], array( 'type' => 'thumb', 'link-type' => 'page' ) );
				}

				if( is_array($_categoryImages[ $category['_coverImage'] ]) AND count($_categoryImages[ $category['_coverImage'] ]) )
				{
					$category['cover']['tag']	= $this->registry->gallery->helper('image')->makeImageLink( array_merge( $_categoryImages[ $category['_coverImage'] ], array( '_isRead' => true, 'image_approved' => 1 ) ), array( 'h1image' => true, 'type' => 'thumb', 'link-type' => 'page' ) );
				}
			}
		}

		//-----------------------------------------
		// Does this category display albums or images?
		//-----------------------------------------

		$categoryAlbums		= array();
		$categoryImages		= array();
		$st					= intval($this->request['st']);
		$perPage			= GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE;
		$pages				= '';

		//-----------------------------------------
		// Is this is the special member's gallery?
		//-----------------------------------------

		if( $category['category_id'] == $this->settings['gallery_members_album'] )
		{
			//-----------------------------------------
			// Did we just click link to view differently?
			//-----------------------------------------

			if( $this->request['showme'] )
			{
				switch( $this->request['showme'] )
				{
					case 'albums':
						$this->settings['gallery_memalbum_display']	= 1;
					break;

					case 'images':
						$this->settings['gallery_memalbum_display']	= 2;
					break;

					case 'members':
						$this->settings['gallery_memalbum_display']	= 3;
					break;
				}

				IPSCookie::set( 'members_gallery', $this->settings['gallery_memalbum_display'] );
			}

			//-----------------------------------------
			// Did we change our pref previously?
			//-----------------------------------------

			else if( IPSCookie::get('members_gallery') )
			{
				$this->settings['gallery_memalbum_display']	= IPSCookie::get('members_gallery');
			}

			//-----------------------------------------
			// How are we viewing?
			//-----------------------------------------

			switch( $this->settings['gallery_memalbum_display'] )
			{
				case 1:
					$category['category_type']	= 1;
				break;

				case 2:
					$category['category_type']	= 2;
				break;

				case 3:
					$category['category_type']	= 4;
				break;
			}
		}

		if( $category['category_type'] == 1 )
		{
			$perPage	= GALLERY_ALBUMS_PER_PAGE;

			//-----------------------------------------
			// Verify filter
			//-----------------------------------------

			$this->request['asort_key']	= ( in_array( $this->request['asort_key'], array( 'name', 'date', 'rated', 'images', 'comments' ) ) ) ? $this->request['asort_key'] : '';

			//-----------------------------------------
			// Fetch albums
			//-----------------------------------------

			$categoryAlbums		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																										'sortKey'			=> $this->request['asort_key'] ? $this->request['asort_key'] : $category['category_sort_options__key'],
																										'sortOrder'			=> $this->request['asort_order'] ? $this->request['asort_order'] : $category['category_sort_options__dir'],
																										'offset'			=> $st,
																										'limit'				=> $perPage,
																										'album_category_id'	=> $category['category_id'],
																										'parseAlbumOwner'	=> true,
																										'getTotalCount'		=> true,
																										'isViewable'		=> true,
																										'notEmpty'			=> true,
																									)		);

			//-----------------------------------------
			// Find any albums?
			//-----------------------------------------

			if( count($categoryAlbums) )
			{
				//-----------------------------------------
				// Extract and load latest images
				//-----------------------------------------

				$categoryAlbums	= $this->registry->gallery->helper('albums')->extractLatestImages( $categoryAlbums );

				//-----------------------------------------
				// Pagination
				//-----------------------------------------

				$pages	= $this->registry->output->generatePagination( array(
																			'totalItems'		=> $this->registry->gallery->helper('albums')->getCount(),
																			'itemsPerPage'		=> $perPage,
																			'currentStartValue'	=> $st,
																			'seoTitle'			=> $category['category_name_seo'],
																			'seoTemplate'		=> 'viewcategory',
																			'baseUrl'			=> "app=gallery&amp;category={$category['category_id']}&amp;sort_key={$this->request['sort_key']}&amp;sort_order={$this->request['sort_order']}&amp;asort_key={$this->request['asort_key']}&amp;asort_order={$this->request['asort_order']}"
																	)		);
			}
		}
		else if( $category['category_type'] == 2 )
		{
			//-----------------------------------------
			// Verify filter
			//-----------------------------------------

			$this->request['sort_key']	= ( in_array( $this->request['sort_key'], array( 'idate', 'views', 'rating', 'caption', 'comments' ) ) ) ? $this->request['sort_key'] : '';

			if( strpos( $category['category_sort_options__key'], 'album_' ) !== false )
			{
				$category['category_sort_options__key']	= 'image_date';
			}

			//-----------------------------------------
			// Fetch images
			//-----------------------------------------

			$categoryImages		= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																															'sortKey'			=> $this->request['sort_key'] ? $this->request['sort_key'] : $category['category_sort_options__key'],
																															'sortOrder'			=> $this->request['sort_order'] ? $this->request['sort_order'] : $category['category_sort_options__dir'],
																															'honorPinned'		=> true,
																															'offset'			=> $st,
																															'limit'				=> $perPage,
																															'albumId'			=> ( $category['category_id'] == $this->settings['gallery_members_album'] ) ? null : 0,
																															'categoryId'		=> $category['category_id'],
																															'getTotalCount'		=> true,
																															'thumbClass'		=> 'galattach',
																															'link-thumbClass'	=> 'galimageview'
																														)		);

			//-----------------------------------------
			// Find any images?
			//-----------------------------------------

			if( count($categoryImages) )
			{
				//-----------------------------------------
				// Pagination
				//-----------------------------------------

				$pages	= $this->registry->output->generatePagination( array(
																			'totalItems'		=> $this->registry->gallery->helper('image')->getCount(),
																			'itemsPerPage'		=> $perPage,
																			'currentStartValue'	=> $st,
																			'seoTitle'			=> $category['category_name_seo'],
																			'seoTemplate'		=> 'viewcategory',
																			'baseUrl'			=> "app=gallery&amp;category={$category['category_id']}&amp;sort_key={$this->request['sort_key']}&amp;sort_order={$this->request['sort_order']}&amp;asort_key={$this->request['asort_key']}&amp;asort_order={$this->request['asort_order']}" 
																	)		);
			}
		}
		else if( $category['category_type'] == 4 )
		{
			//-----------------------------------------
			// How many distinct member ids are there?
			//-----------------------------------------

			$count	= $this->DB->buildAndFetch( array( 'select' => 'count(' . $this->DB->buildDistinct('album_owner_id') . ') as total', 'from' => 'gallery_albums', 'where' => 'album_category_id=' . $category['category_id'] ) );

			//-----------------------------------------
			// Get distinct member ids
			//-----------------------------------------

			$_members			= array();
			$_imagesByMember	= array();

			if( $count['total'] )
			{
				$this->DB->build( array(
										'select'	=> $this->DB->buildDistinct('a.album_owner_id'),
										'from'		=> array( 'gallery_albums' => 'a' ),
										'where'		=> 'a.album_category_id=' . $category['category_id'],
										'order'		=> 'm.members_display_name ASC',
										'limit'		=> array( $st, $perPage ),
										'add_join'	=> array(
															array(
																'from'	=> array( 'members' => 'm' ),
																'where'	=> 'm.member_id=a.album_owner_id',
																'type'	=> 'left',
																)
															)
								)		);
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$_members[]									= $r['album_owner_id'];
					$_imagesByMember[ $r['album_owner_id'] ]	= array();
				}

				//-----------------------------------------
				// Load the members
				//-----------------------------------------

				$_members	= IPSMember::load( $_members );

				//-----------------------------------------
				// Loop over members and store data, get images
				//-----------------------------------------

				foreach( $_members as $_member )
				{
					$_member['_images']	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																																'sortKey'		=> 'image_date',
																																'sortOrder'		=> 'desc',
																																'offset'		=> 0,
																																'limit'			=> 10,
																																'ownerId'		=> $_member['member_id'],
																																'categoryId'	=> $category['category_id'],
																																'getTotalCount'	=> true,
																															)		);

					$_member['_totalImages']	= $this->registry->gallery->helper('image')->getCount();

					foreach( $_member['_images'] as $_image )
					{
						$_member['_latestImage']	= $_image['image_date'];
						break;
					}

					$categoryAlbums[ $_member['members_display_name'] ]	= IPSMember::buildDisplayData( $_member );
				}

				ksort( $categoryAlbums );

				//-----------------------------------------
				// Pagination
				//-----------------------------------------

				$pages	= $this->registry->output->generatePagination( array(
																			'totalItems'		=> $count['total'],
																			'itemsPerPage'		=> $perPage,
																			'currentStartValue'	=> $st,
																			'seoTitle'			=> $category['category_name_seo'],
																			'seoTemplate'		=> 'viewcategory',
																			'baseUrl'			=> "app=gallery&amp;category={$category['category_id']}" 
																	)		);
			}
		}

		//-----------------------------------------
		// Get the like/follow class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow	= classes_like::bootstrap( 'gallery', 'categories' );

		//-----------------------------------------
		// Get the follow data
		//-----------------------------------------

		$follow	= $this->_follow->render( 'summary', $category['category_id'] );

		//-----------------------------------------
		// And....output
		//-----------------------------------------

		$this->registry->getClass('output')->addCanonicalTag( $st ? 'app=gallery&amp;category=' . $category['category_id'] . '&amp;st=' . $st : 'app=gallery&amp;category=' . $category['category_id'], $category['category_name_seo'], 'viewcategory' );
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;category=' .  $category['category_id'], 'publicNoSession', $category['category_name_seo'], 'viewcategory' ) );
		$this->registry->getClass('output')->setTitle( $category['category_name'] . ( $st ? $this->lang->words['page_title_pagination'] : '' ) . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $this->registry->output->getTemplate('gallery_home')->category( $category, $category_rows, $categoryAlbums, $categoryImages, $pages, $follow ) );
		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		
		foreach( $navigation as $nav )
		{
			$this->registry->getClass('output')->addNavigation( $nav[0], $nav[1], $nav[2], 'viewcategory' );
		}
		
		$this->registry->getClass('output')->sendOutput();
	}
}