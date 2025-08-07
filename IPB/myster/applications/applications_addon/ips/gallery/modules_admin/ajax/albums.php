<?php
/**
 * @file		albums.php 	Provides ajax methods to mange albums
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-08-10 14:47:35 -0400 (Fri, 10 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11202 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_ajax_albums
 * @brief		Provides ajax methods to mange albums
 */
class admin_gallery_ajax_albums extends ipsAjaxCommand 
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
		// Load skin templates
		//-----------------------------------------

		$this->html	= ipsRegistry::getClass('output')->loadTemplate('cp_skin_gallery_albums');
		
		//-----------------------------------------
		// Load language files
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'admin_gallery', 'public_gallery' ), 'gallery' );
		
		//-----------------------------------------
		// Switch off
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'deleteDialogue':
				$this->_deleteDialogue();
			break;

			case 'rebuildThumbs':
				$this->_rebuildThumbs();
			break;

			case 'resetPermissions':
				$this->_resetPermissions();
			break;

			case 'resyncAlbums':
				$this->_resyncAlbums();
			break;

			case 'getAlbums':
				$this->_getAlbums();
			break;
		}
	}

	/**
	 * Get the albums that match our search
	 *
	 * @return	@e void
	 */
	public function _getAlbums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$searchType		= trim( $this->request['searchType'] );
		$searchCat		= intval( $this->request['searchCat'] );
		$searchMatch	= trim( $this->request['searchMatch'] );
		$searchSort		= trim( $this->request['searchSort'] );
		$searchDir		= trim( $this->request['searchDir'] );
		$searchText		= trim( $this->request['searchText'] );
		$filters		= array( 'limit' => 150, 'sortOrder' => $searchDir, 'sortKey' => $searchSort );

		if( $searchCat )
		{
			$filters['album_category_id']	= $searchCat;
		}

		//-----------------------------------------
		// Got search text?
		//-----------------------------------------

		if ( $searchText )
		{
			if ( $searchType == 'album' )
			{
				if ( $searchMatch == 'is' )
				{
					$filters['albumNameIs']				= $searchText;
				}
				else
				{
					$filters['albumNameContains']		= $searchText;
				}
			}
			else if ( $searchType == 'parent')
			{
				$filters['album_category_id']			= $searchText;
			}
			else
			{
				if ( $searchMatch == 'is' )
				{
					$filters['albumOwnerNameIs']		= $searchText;
				}
				else
				{
					$filters['albumOwnerNameContains']	= $searchText;
				}
			}
		}

		//-----------------------------------------
		// Get albums
		//-----------------------------------------

		$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( $filters );

		//-----------------------------------------
		// Return the formatted HTML
		//-----------------------------------------

		$this->html->form_code	= 'module=albums&amp;section=manage&amp;';

		$this->returnHtml( $this->html->ajaxAlbums( $albums ) );
	}

	/**
	 * Delete album dialog (gives opportunity to move images)
	 *
	 * @return	@e void
	 */
	public function _deleteDialogue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['albumId'] );
		$data		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );

		//-----------------------------------------
		// Get category options
		//-----------------------------------------

		$categories		= $this->registry->gallery->helper('categories')->catJumpList( true );
		$acceptImages	= $this->registry->gallery->helper('categories')->fetchImageCategories();
		$canAccept		= 0;

		foreach( $categories as $_key => $element )
		{
			if( !in_array( $element[0], $acceptImages ) )
			{
				$categories[ $_key ]['disabled']	= true;
			}
			else
			{
				$canAccept++;
			}
		}

		//-----------------------------------------
		// Get move to options
		//-----------------------------------------

		$data['album_options']	= $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => false, 'album_owner_id' => $data['album_owner_id'], 'skip' => array( $albumId ) ) );
		$data['cat_options']	= $canAccept ? $this->registry->output->formDropdown( 'move_to_category_id', $categories ) : false;

		//-----------------------------------------
		// Return HTML
		//-----------------------------------------

		$this->returnHtml( $this->html->acpDeleteAlbumDialogue( $data ) );
	}

	/**
	 * Rebuilds album permissions.  As albums do not have permissions, it really recaches image permissions within the album.
	 *
	 * @return	@e void
	 */
	public function _resetPermissions()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= ( $this->request['albumId'] == 'all' ) ? 0 : intval( $this->request['albumId'] );
		$json		= array();
		$pergo		= 50;
		$where		= ( $albumId ) ? 'album_id=' . $albumId : '';
		
		//-----------------------------------------
		// Getting options?
		//-----------------------------------------

		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo', 'from' => 'gallery_albums', 'where' => $where ) );
			
			$json['total']	= intval( $count['with_elmo'] );
			$json['pergo']	= $pergo;
		}
		else
		{
			//-----------------------------------------
			// Fetch appropriate albums
			//-----------------------------------------

			$lastId		= intval( $this->request['pb_lastId'] );
			$pb_done	= intval( $this->request['pb_done'] );
			$seen		= 0;
			$_where		= ( $albumId ) ?  $where : 'album_id > ' . $lastId;
			$limit		= ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums', 'where' => $_where, 'limit' => $limit ) );
			$o = $this->DB->execute();
			
			//-----------------------------------------
			// Loop over albums and resync
			//-----------------------------------------

			$albums	= array();

			while( $album = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId	= $album['album_id'];

				$albums[ $album['album_id'] ]	= $album;
			}

			$this->registry->gallery->helper('image')->updatePermissionFromParent( $albums );
			
			//-----------------------------------------
			// Format response
			//-----------------------------------------

			if ( $seen )
			{
				$json	= array( 'status' => 'processing', 'lastId' => $lastId );
			}
			else
			{
				$json	= array( 'status' => 'done', 'lastId' => $lastId );
			}
		}

		//-----------------------------------------
		// Return json
		//-----------------------------------------

		$this->returnJsonArray( $json );
	}
	
	/**
	 * Resyncs albums
	 *
	 * @return	@e void
	 */
	public function _resyncAlbums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= ( $this->request['albumId'] == 'all' ) ? '' : intval( $this->request['albumId'] );
		$json		= array();
		$pergo		= 50;
		$where		= ( $albumId ) ? 'album_id=' . $albumId : '';
		
		//-----------------------------------------
		// Getting options?
		//-----------------------------------------

		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo', 'from' => 'gallery_albums', 'where' => $where ) );
			
			$json['total']	= intval( $count['with_elmo'] );
			$json['pergo']	= $pergo;
		}
		else
		{
			//-----------------------------------------
			// Fetch appropriate albums
			//-----------------------------------------

			$lastId		= intval( $this->request['pb_lastId'] );
			$pb_done	= intval( $this->request['pb_done'] );
			$seen		= 0;
			$_where		= ( $albumId ) ?  $where : 'album_id > ' . $lastId;
			$limit		= ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums', 'where' => $_where, 'limit' => $limit ) );
			$o = $this->DB->execute();
			
			//-----------------------------------------
			// Loop over albums and resync
			//-----------------------------------------

			while( $album = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId	= $album['album_id'];
				
				$this->registry->gallery->helper('albums')->resync( $album['album_id'] );
			}
			
			//-----------------------------------------
			// Format response
			//-----------------------------------------

			if( $seen AND $this->request['return'] == 'okresponse' )
			{
				$json	= array( 'ok' => $this->lang->words['acp_album_resync_done'] );
			}
			else if ( $seen )
			{
				$json	= array( 'status' => 'processing', 'lastId' => $lastId );
			}
			else
			{
				$json	= array( 'status' => 'done', 'lastId' => $lastId );
			}
		}

		//-----------------------------------------
		// Return json
		//-----------------------------------------

		$this->returnJsonArray( $json );
	}
	
	/**
	 * Rebuilds thumbs
	 *
	 * @return	@e void
	 */
	public function _rebuildThumbs()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= ( $this->request['albumId'] == 'all' ) ? '' : intval( $this->request['albumId'] );
		$json		= array();
		$pergo		= 10;
		$where		= ( $albumId ) ? 'image_album_id=' . $albumId : '';
		
		//-----------------------------------------
		// Just retrieving the options?
		//-----------------------------------------

		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo', 'from' => 'gallery_images', 'where' => $where ) );
			
			$json['total']	= intval( $count['with_elmo'] );
			$json['pergo']	= $pergo;
		}
		else
		{
			//-----------------------------------------
			// Grab images to rebuild
			//-----------------------------------------

			$lastId		= intval( $this->request['pb_lastId'] );
			$pb_done	= intval( $this->request['pb_done'] );
			$seen		= 0;
			$_where		= ( $albumId ) ?  $where : 'image_id > ' . $lastId;
			$limit		= ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_images', 'where' => $_where, 'limit' => $limit ) );
			$o = $this->DB->execute();

			//-----------------------------------------
			// Loop over the images
			//-----------------------------------------

			while( $image = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId	= $image['image_id'];

				$this->registry->gallery->helper('image')->resync( $image );

				//-----------------------------------------
				// Rebuild the thumbnails
				//-----------------------------------------

				$this->registry->gallery->helper('image')->buildSizedCopies( $image );
			}
			
			//-----------------------------------------
			// Format return JSON
			//-----------------------------------------

			if ( $seen )
			{
				$json	= array( 'status' => 'processing', 'lastId' => $lastId );
			}
			else
			{
				$json	= array( 'status' => 'done', 'lastId' => $lastId );
			}
			
		}

		//-----------------------------------------
		// Return response
		//-----------------------------------------

		$this->returnJsonArray( $json );
	}
}