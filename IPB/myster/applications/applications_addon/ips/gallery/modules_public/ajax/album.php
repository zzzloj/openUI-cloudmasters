<?php
/**
 * @file		album.php 	Album AJAX methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-10-09 20:48:10 -0400 (Tue, 09 Oct 2012) $
 * @version		v5.0.5
 * $Revision: 11431 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_album extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'fetchAlbumJson':
				$this->_fetchAlbumJson();
			break;

			case 'newAlbumDialogue':
				$this->_newAlbumDialogue();
			break;

			case 'newAlbumSubmit':
				$this->_newAlbumSubmit();
			break;

			case 'deleteDialogue':
				$this->_deleteDialogue();
			break;

			case 'moderate':
				$this->_moderate();
			break;

			case 'albumAutocomplete':
				$this->_albumAutocomplete();
			break;
        }
    }

	/**
	 * Fetches the album data as JSON
	 *
	 * @return	@e void
	 */
	public function _fetchAlbumJson()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['album_id'] );
		$album		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_id' => $albumId ) );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->returnJsonArray( $album[ $albumId ]['album_id'] ? $album[ $albumId ] : array() );
	}
	
	/**
	 * Album Auto Complete
	 *
	 * @return	@e void
	 */
	public function _albumAutocomplete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$search	= IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['search'], 0 ), true );
		$search	= IPSText::convertCharsets( $search, 'utf-8', IPS_DOC_CHAR_SET );
		$return	= array();

		//-----------------------------------------
		// Got a search term?
		//-----------------------------------------

		if ( IPSText::mbstrlen( $search ) < 3 )
		{
			$this->returnJsonError( 'requestTooShort' );
		}

		//-----------------------------------------
		// Fetch any albums that match
		//-----------------------------------------

		$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'albumNameContains' => $search ) );

		//-----------------------------------------
		// Find any albums?
		//-----------------------------------------

		if ( ! count( $albums ) )
		{
			$this->returnJsonArray( array( ) );
		}
		
		//-----------------------------------------
		// Return the albums
		//-----------------------------------------

		foreach( $albums as $id => $album )
		{
			$return[ $id ]	= array(
									'name'		=> $album['album_name'],
									'showas'	=> '<strong>' . $album['album_name'] . '</strong>',
									'img'		=> $this->registry->gallery->inlineResize( $album['thumb'], 30, 30 ),
									'img_w'		=> $album['album_count_imgs'],
									'img_h'		=> '',
									);
		}

		$this->returnJsonArray( $return );
	}

	/**
	 * Moderate images in an album
	 *
	 * @return	@e void
	 */
	public function _moderate()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['albumId'] );
		$categoryId	= intval( $this->request['categoryId'] );

		if( $albumId )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );
		}
		else
		{
			$album		= array();
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $categoryId );
		}

		//-----------------------------------------
		// Multimod
		//-----------------------------------------

		$this->request['selectediids'] = IPSCookie::get('modiids');

		$imageIds	= IPSLib::cleanIntArray( explode( ',', $this->request['selectediids'] ) );
		$toAlbumId	= intval( $this->request['toAlbumId'] );
		$toCategory	= intval( $this->request['toCategoryId'] );

		IPSCookie::set('modiids', '', 0);

		//-----------------------------------------
		// Got any images?
		//-----------------------------------------

		if ( !count($imageIds) )
		{
			$this->returnJsonError( $this->lang->words['album_modaction_noimages'] );
		}
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch ( $this->request['modact'] )
		{
			case 'hide':
			case 'approve':
				//-----------------------------------------
				// Can we approve/hide in the category?
				//-----------------------------------------

				if ( ! $this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'], null, ( $this->request['modact'] == 'approve' ) ? 'mod_can_approve' : 'mod_can_hide' ) )
				{
					$this->returnJsonError('no_permission');
				}

				$this->registry->gallery->helper('moderate')->toggleVisibility( $imageIds, ( ( $this->request['modact'] == 'approve' ) ? true : false ) );
			break;

			case 'delete':
				//-----------------------------------------
				// Can we delete our own images, or are we a moderator who can delete?
				//-----------------------------------------

				if ( !$this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'], null, 'mod_can_delete' ) )
				{
					if( !$this->memberData['g_del_own'] )
					{
						$this->returnJsonError('no_permission');
					}
					else
					{
						$_images	= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => $imageIds, 'orderBy' => false ) );

						foreach( $_images as $_image )
						{
							if( !$this->registry->gallery->helper('image')->isOwner( $_image ) )
							{
								$this->returnJsonError('no_permission');
							}
						}
					}
				}

				$this->registry->gallery->helper('moderate')->deleteImages( $imageIds );
			break;
			case 'move':
				//-----------------------------------------
				// Are we a moderator that can move?
				//-----------------------------------------

				if ( !$this->registry->gallery->helper('categories')->checkIsModerator( $category['category_id'], null, 'mod_can_move' ) )
				{
					//-----------------------------------------
					// No?  Do we own both albums then?
					//-----------------------------------------

					if( !$albumId OR !$this->registry->gallery->helper('albums')->isOwner( $albumId ) OR !$toAlbumId OR !$this->registry->gallery->helper('albums')->isOwner( $toAlbumId ) )
					{
						$this->returnJsonError('no_permission');
					}
				}

				$this->registry->gallery->helper('moderate')->moveImages( $imageIds, $toAlbumId, $toCategory );
			break;
		}

		//-----------------------------------------
		// Return a status code
		//-----------------------------------------

		$this->returnJsonArray( array( 'done' => 1 ) );
	}
	
	/**
	 * Show the delete dialog box for an album
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
		$ownerId	= $data['album_owner_id'];
		
		//-----------------------------------------
		// If there are images, create a move-to dropdown
		//-----------------------------------------

		if ( empty($data['album_count_imgs']) && empty($data['album_count_imgs_hidden']) )
		{
			$data['options']		= false;
			$data['_catOptions']	= false;
			$data['_acceptImages']	= false;
		}
		else
		{
			$data['options']		= $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => 1, 'memberData' => array( 'member_id' => $ownerId ), 'skip' => array( $albumId ) ) );
			$data['_catOptions']	= $this->registry->gallery->helper('categories')->catJumpList( false, 'images', array(), $this->lang->words['cs_select'] );
			$data['_acceptImages']	= $this->registry->gallery->helper('categories')->fetchImageCategories();
		}

		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------

		$this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->deleteAlbumDialogue( $data ) );
	}
	
	/**
	 * Save a new album
	 *
	 * @return	@e void
	 */
	public function _newAlbumSubmit()
	{
		//-----------------------------------------
		// Can we create an album
		//-----------------------------------------

		if ( !$this->registry->gallery->helper('albums')->canCreate( null, true, $this->request['album_type'], $this->request['album_category_id'] ) )
		{
			$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
		}
		
		//-----------------------------------------
		// Fix name and description (charsets)
		//-----------------------------------------

    	//$name	= IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['album_name'], 0 ), true );
		$name	= IPSText::convertCharsets( $this->request['album_name'], 'utf-8', IPS_DOC_CHAR_SET );
		
    	//$desc	= IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['album_description'], 0 ), true );
		$desc	= IPSText::convertCharsets( $this->request['album_description'], 'utf-8', IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Build an insert array
		//-----------------------------------------

		$album = array( 'album_name'			=> $name,
						'album_description'		=> $desc,
						'album_category_id'		=> intval($this->request['album_category_id']),
						'album_owner_id'		=> $this->memberData['member_id'],
						'album_type'			=> intval($this->request['album_type']),
						'album_sort_options'	=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
						'album_watermark'		=> intval( $this->request['album_watermark'] ),
						'album_allow_comments'	=> intval($this->request['album_allow_comments']),
						'album_allow_rating'	=> intval($this->request['album_allow_rating']),
						);

		//-----------------------------------------
		// Some quick error checking
		//-----------------------------------------

		if( $album['album_type'] < 1 OR $album['album_type'] > 3 )
		{
			$this->returnJsonError( $this->lang->words['invalid_album_type'] );
		}

		if( !$album['album_category_id'] OR !in_array( $album['album_category_id'], $this->registry->gallery->helper('categories')->fetchAlbumCategories() ) )
		{
			$this->returnJsonError( $this->lang->words['invalid_album_category'] );
		}

		//-----------------------------------------
		// Did admin enforce certain options?
		//-----------------------------------------

		$album	= $this->registry->gallery->helper('albums')->forceAdminPresets( $album );
		
		//-----------------------------------------
		// Save the album
		//-----------------------------------------

		try 
		{
			$album = $this->registry->gallery->helper('moderate')->createAlbum( $album );
		
			$this->returnJsonArray( array( 'album' => $album ) );
		}
		catch ( Exception $e )
		{
			$msg = $e->getMessage();
			
			switch( $msg )
			{
				case 'BAD_PARENT_CATEGORY';
					$msg = $this->lang->words['parent_zero_not_global'];
				break;
			}
			
			$this->returnJsonError( $msg );
		}
	}
	
	/**
	 * Show the dialgo to create a new album
	 *
	 * @return	@e void
	 */
	public function _newAlbumDialogue()
	{
		//-----------------------------------------
		// Check if we can create at all
		//-----------------------------------------

		if ( !$this->registry->gallery->helper('albums')->canCreate() )
		{
			$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
		}
		
		//-----------------------------------------
		// Grab presets
		//-----------------------------------------

		$presets						= $this->cache->getCache('gallery_album_defaults');
		$presets['album_sort_options']	= unserialize($presets['album_sort_options']);

		//-----------------------------------------
		// Create data array
		//-----------------------------------------

		$data	= array(
						'_catOptions'		=> $this->registry->gallery->helper('categories')->catJumpList( true, 'images' ),
						'_acceptAlbums'		=> $this->registry->gallery->helper('categories')->fetchAlbumCategories(),
						'_catDefaults'		=> $presets,
						'_privateAllowed'	=> $this->registry->gallery->helper('albums')->canCreate( null, true, 2 ),
						'_foAllowed'		=> $this->registry->gallery->helper('albums')->canCreate( null, true, 3 ),
						);

		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------

		$this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->newAlbumDialogue( $data ) );
	}
}