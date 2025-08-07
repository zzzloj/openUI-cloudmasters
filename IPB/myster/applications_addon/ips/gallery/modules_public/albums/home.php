<?php
/**
 * @file		home.php 	Home listing
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-08-16 16:47:28 -0400 (Thu, 16 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11225 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_gallery_albums_home
 * @brief		Home listing
 */
class public_gallery_albums_home extends ipsCommand
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
		// Fetch up to 20 featured images
		//-----------------------------------------

		$featuredImages	= $this->registry->gallery->helper('image')->fetchFeatured( 20 );

		//-----------------------------------------
		// Format the featured image data
		//-----------------------------------------

		if( count($featuredImages) )
		{
			foreach( $featuredImages as $imageId => $imageData )
			{
				if ( ! empty( $imageData['image_description'] ) )
				{
					$featuredImages[ $imageId ]['image_description']	= IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripAllTags( $imageData['image_description'] ), 300 );
				}
					
				if ( $imageData['image_id'] )
				{
					$featuredImages[ $imageId ]['tag']					= $this->registry->gallery->helper('image')->makeImageTag( $imageData, array( 'type' => 'max', 'link-type' => 'src' ) );
				}
			}
		}

		//-----------------------------------------
		// Fetch the recent comments
		//-----------------------------------------

		$recentComments	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'getLatestComment' => true, 'retrieveByComments' => true, 'hasComments' => true, 'sortKey' => 'image_last_comment', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 5 ) );

		//-----------------------------------------
		// Fetch the tag cloud
		//-----------------------------------------

    	$classToLoad	= IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'sources/classes/tags/cloud.php', 'classes_tags_cloud' );
		$cloud			= new $classToLoad();

		$cloud->setSkinGroup('gallery_global');
		$cloud->setSkinTemplate('tagCloud');
		$cloud->setApp( 'gallery' );

		$cloudData		= $cloud->getCloudData( array( 'limit' => 50 ) );
		$tagCloud		= count($cloudData['tags']) ? $cloud->render( $cloudData ) : '';

		//-----------------------------------------
		// Fetch the gallery stats
		//-----------------------------------------

		if( !is_array( $this->caches['gallery_stats'] ) OR !count( $this->caches['gallery_stats'] ) )
		{
			$this->cache->getCache('gallery_stats');
		}
		
		$stats	= array(
						'images'		=> $this->caches['gallery_stats']['total_images_visible'],
						'diskspace'		=> $this->caches['gallery_stats']['total_diskspace'],
						'comments'		=> $this->caches['gallery_stats']['total_comments_visible'],
						'albums'		=> $this->caches['gallery_stats']['total_albums']
						);

		//-----------------------------------------
		// What homepage layout?
		//-----------------------------------------

		if( $this->settings['gallery_homepage'] == 'social' )
		{
			$output	= $this->_socialHomepage( $featuredImages, $recentComments, $tagCloud, $stats );
		}
		else
		{
			$output	= $this->_traditionalHomepage( $featuredImages, $recentComments, $tagCloud, $stats );
		}

		//-----------------------------------------
		// If we're here, clear mod cookie
		//-----------------------------------------

		IPSCookie::set('modiids', '', 0);

		//-----------------------------------------
		// And....output
		//-----------------------------------------

		$this->registry->getClass('output')->setTitle( IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $output );
		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), '' );
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Traditional home page approach
	 *
	 * @param	array 	Featured images
	 * @param	array 	Recent comments
	 * @param	string	Tag cloud HTML
	 * @param	array 	Statistics data
	 * @return	@e string
	 */
	protected function _traditionalHomepage( $featuredImages, $recentComments, $tagCloud, $stats )
	{
		//-----------------------------------------
		// Fetch our categories
		//-----------------------------------------

		$category_rows	= array();
		$image_ids		= array();
		
		if( count( $this->registry->gallery->helper('categories')->fetchCategories( 0 ) ) )
		{
			foreach( $this->registry->gallery->helper('categories')->fetchCategories( 0 ) as $categoryId => $categoryData )
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

					$image_ids[ $categoryData['_coverImage'] ]		= $categoryData['_coverImage'];
					$image_ids[ $categoryData['_latestImage'] ]		= $categoryData['_latestImage'];

					//-----------------------------------------
					// And then store our category rows
					//-----------------------------------------

					$category_rows[]	= $categoryData;
				}
			}
		}

		//-----------------------------------------
		// Make sure there's something to see
		//-----------------------------------------

		if( !count($category_rows) )
		{
			$this->registry->output->showError( 'albums_none_to_see', 10774.7, null, null, 403 );
		}

		//-----------------------------------------
		// Load up category images
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
					$category_rows[ $_k ]['_latestThumb']	= $this->registry->gallery->helper('image')->makeImageLink( $category_rows[ $_k ]['_latestImage'], array( 'type' => 'thumb', 'link-type' => 'page', 'id_prefix' => 'latest_' ) );
				}
			}
		}

		//-----------------------------------------
		// Fetch up to 20 recent images
		//-----------------------------------------

		$recentImages		= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																															'unixCutOff'		=> GALLERY_A_YEAR_AGO,
																															'sortKey'			=> 'image_date',
																															'sortOrder'			=> 'desc',
																															'offset'			=> 0,
																															'limit'				=> 20,
																															'thumbClass'		=> 'galattach',
																															'link-thumbClass'	=> 'galimageview'
																					)										);

		return $this->registry->output->getTemplate('gallery_home')->homeTraditional( $category_rows, $featuredImages, $recentImages, $recentComments, $tagCloud, $stats );
	}

	/**
	 * Social home page approach
	 *
	 * @param	array 	Featured images
	 * @param	array 	Recent comments
	 * @param	string	Tag cloud HTML
	 * @param	array 	Statistics data
	 * @return	@e string
	 */
	protected function _socialHomepage( $featuredImages, $recentComments, $tagCloud, $stats )
	{
		//-----------------------------------------
		// Fetch our categories
		//-----------------------------------------

		$category_rows	= $this->registry->gallery->helper('categories')->cat_cache;

		//-----------------------------------------
		// Fetch up to 30 recent albums
		//-----------------------------------------

		$recentAlbums		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																									'isViewable'	=> true,
																									'sortKey'		=> 'album_last_img_date',
																									'sortOrder'		=> 'desc',
																									'offset'		=> 0,
																									'limit'			=> 30,
																					)				);

		//-----------------------------------------
		// Make sure there's something to see
		//-----------------------------------------

		if( !count($category_rows) AND !count($recentAlbums) )
		{
			$this->registry->output->showError( 'albums_none_to_see', 10774.71, null, null, 403 );
		}

		//-----------------------------------------
		// Fetch up to 50 recent images
		//-----------------------------------------

		$recentImages		= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																															'unixCutOff'		=> GALLERY_A_YEAR_AGO,
																															'sortKey'			=> 'image_date',
																															'sortOrder'			=> 'desc',
																															'offset'			=> 0,
																															'limit'				=> 50,
																															'thumbClass'		=> 'galattach',
																															'link-thumbClass'	=> 'galimageview'
																					)										);

		return $this->registry->output->getTemplate('gallery_home')->homeSocial( $category_rows, $featuredImages, $recentImages, $recentAlbums, $recentComments, $tagCloud, $stats );
	}
}