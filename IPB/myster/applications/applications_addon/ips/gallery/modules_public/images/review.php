<?php
/**
 * @file		review.php 	Allows uploader to review and update images before saving during new uploads
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2013-02-15 12:08:49 -0500 (Fri, 15 Feb 2013) $
 * @version		v5.0.5
 * $Revision: 11996 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_review extends ipsCommand
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
		// Get the tags class
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		//-----------------------------------------
		// Determine what to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			default:
			case 'show':
				$this->_showItems();
			break;
			case 'process':
				$this->_process();
			break;
		}
	}

	/**
	 * Show items to review. Can be from an upload session or can be from an existing album or category.
	 *
	 * @return	@e void
	 */
	protected function _showItems()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sessionKey		= trim( $this->request['sessionKey'] );
		$album_id		= intval( $this->request['album_id'] );
		$start			= intval( $this->request['start'] );
		$perPage		= 40;
		$images			= array();
		$type			= null;
		$firstId		= 0;
		$coverSet		= 0;
		$editors		= array();
		$album			= array();
		$category		= array();
		
		//-----------------------------------------
		// Get the editor class
		//-----------------------------------------

		$editorClass	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $editorClass();
		
		//-----------------------------------------
		// If it's an album, get data and make sure we have permission
		//-----------------------------------------

		if ( !empty( $album_id ) )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $album_id );
			
			if ( $sessionKey )
			{
				if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-3a', null, null, 403 );
				}
			}
			else
			{
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-3b', null, null, 403 );
				}
			}
		}

		//-----------------------------------------
		// Get our images
		//-----------------------------------------

		if ( $sessionKey )
		{
			$images	= $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey, array( 'offset' => $start, 'limit' => $perPage, 'getTotalCount' => true ) );
			$total	= $this->registry->gallery->helper('upload')->getCount();
			$type	= 'uploads';
		}
		else if ( $album_id )
		{
			$images	= $this->registry->gallery->helper('image')->fetchAlbumImages( $album_id, array( 'sortOrder' => 'asc', 'getTotalCount' => true, 'offset' => $start, 'limit' => $perPage ) );
			$total	= $this->registry->gallery->helper('image')->getCount();
			$type	= 'album';
			
			//-----------------------------------------
			// Sort out some details
			//-----------------------------------------

			$album['album_description']	= IPSText::br2nl($album['album_description']);
			$album['_canWatermark']		= $this->registry->gallery->helper('albums')->canWatermark( $album );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-show-1', null, null, 403 );
		}

		if ( $album['album_cover_img_id'] )
		{
			$album['_hasCoverSet']		= 'elsewhere';
		}

		if ( $category['category_cover_img_id'] )
		{
			$category['_hasCoverSet']	= 'elsewhere';
		}

		//-----------------------------------------
		// Loop over the images and set some data
		//-----------------------------------------

		if ( is_array( $images ) AND count( $images ) )
		{
			foreach( $images as $id => $data )
			{
				//-----------------------------------------
				// Get the container data if it's missing
				//-----------------------------------------

				if ( empty( $album['album_id'] ) AND $data['image_album_id'] )
				{
					$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $data['image_album_id'] );
				}
				else if( empty( $category['category_id'] ) AND $data['image_category_id'] )
				{
					$category	= $this->registry->gallery->helper('categories')->fetchCategory( $data['image_category_id'] );
				}

				//-----------------------------------------
				// If we're coming from 'uploads', set a caption if we can
				//-----------------------------------------

				if ( $type == 'uploads' AND IPSLib::isSerialized( $data['image_metadata'] ) )
				{
					$_data = IPSLib::safeUnserialize( $data['image_metadata'] );
					
					if ( ! empty( $_data['IPTC.ObjectName'] ) )
					{
						$images[ $id ]['image_caption']	= $_data['IPTC.ObjectName'];
					}
				}

				//-----------------------------------------
				// Get the tag box
				//-----------------------------------------

				if ( $type == 'uploads' )
				{
					$where = array( 'fake_meta_id'   => $id,
									'meta_parent_id' => $data['image_album_id'] ? $album['album_id'] : $category['category_id'],
									'member_id'		 => $this->memberData['member_id'],
									'existing_tags'	 => explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) ),
									'_imageData'	 => $data );
					
					if ( $this->registry->galleryTags->can( 'add', $where ) )
					{
						$images[ $id ]['_tagBox'] = $this->registry->galleryTags->render( 'entryBox', $where );
					}
				}
				else
				{
					$where = array( 'meta_id'		 => $images[ $id ]['image_id'],
								    'meta_parent_id' => $data['image_album_id'] ? $album['album_id'] : $category['category_id'],
								    'member_id'	     => $this->memberData['member_id'],
								    '_imageData'	 => $data );
	
					if ( $_REQUEST['ipsTags_' . $id] )
					{
						$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags_' . $id] ) );
					}
				
					if ( $this->registry->galleryTags->can( 'edit', $where ) )
					{
						$images[ $id ]['_tagBox'] = $this->registry->galleryTags->render('entryBox', $where);
					}
				}

				//-----------------------------------------
				// Set up some other values
				//-----------------------------------------

				$images[ $id ]['_title']	= ( $type == 'uploads' ) ? $this->_smrtTitle( $images[ $id ]['image_caption'] ) : $images[ $id ]['image_caption'];
				$images[ $id ]['_isMedia']	= $this->registry->gallery->helper('media')->isAllowedExtension( $images[ $id ]['image_masked_file_name'] ) ? 1 : 0;
				
				//-----------------------------------------
				// Get the editor
				// Have to set content explicitly to ensure old descriptions are wiped
				// @link http://community.invisionpower.com/tracker/issue-37084-blank-descriptions-are-filled-in
				//-----------------------------------------

				$editor->setContent( $images[ $id ]['image_description'] );
				$images[ $id ]['editor']	= $editor->show( 'description_' . $id, array( 'type' => 'mini', 'minimize' => TRUE ) );
				
				//-----------------------------------------
				// Is this a cover image?
				//-----------------------------------------

				if ( $album['album_cover_img_id'] AND $album['album_cover_img_id'] == $id )
				{
					$images[ $id ]['_cover']	= 1;
					$album['_hasCoverSet']		= 'inline';
				}
				else if ( $category['category_cover_img_id'] AND $category['category_cover_img_id'] == $id )
				{
					$images[ $id ]['_cover']	= 1;
					$category['_hasCoverSet']	= 'inline';
				}
			}
		}

		//-----------------------------------------
		// Get cover images
		//-----------------------------------------

		if ( $album['album_id'] AND $album['_hasCoverSet'] == 'elsewhere' )
		{
			$album['cover']				= $this->registry->gallery->helper('image')->fetchImage( $album['album_cover_img_id'], false, false );
			$album['cover']['tag']		= $this->registry->gallery->helper('image')->makeImageLink( $album['cover'], array( 'h1image' => true ) );
		}
		else if( $category['category_id'] AND $category['_hasCoverSet'] == 'elsewhere' )
		{
			$category['cover']			= $this->registry->gallery->helper('image')->fetchImage( $category['category_cover_img_id'], false, false );
			$category['cover']['tag']	= $this->registry->gallery->helper('image')->makeImageLink( $category['cover'], array( 'h1image' => true ) );
		}

		//-----------------------------------------
		// Extra stuff if we are editing an album
		//-----------------------------------------

		if( $type == 'album' )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );

			//-----------------------------------------
			// Grab presets
			//-----------------------------------------

			$presets						= $this->cache->getCache('gallery_album_defaults');
			$presets['album_sort_options']	= IPSLib::safeUnserialize($presets['album_sort_options']);

			//-----------------------------------------
			// Create data array
			//-----------------------------------------

			$album['_catOptions']		= $this->registry->gallery->helper('categories')->catJumpList( true, 'images' );
			$album['_acceptAlbums']		= $this->registry->gallery->helper('categories')->fetchAlbumCategories();
			$album['_catDefaults']		= $presets;
		}

		//-----------------------------------------
		// Double check container
		//-----------------------------------------

		if ( $type == 'album' && empty( $album['album_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-show-2', null, null, 403 );
		}

		//-----------------------------------------
		// Start output
		//-----------------------------------------

		$this->registry->getClass('output')->setTitle( $this->lang->words['review_title_' . $type ] );

		//-----------------------------------------
		// Navigation elements
		//-----------------------------------------

		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );

		if ( $type == 'album' )
		{
			foreach( $this->registry->gallery->helper('categories')->getNav( $album['album_category_id'] ) as $nav )
			{
				$this->registry->getClass('output')->addNavigation( $nav[0], $nav[1], $nav[2], 'viewcategory' );
			}

			$this->registry->getClass('output')->addNavigation( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		}

		$this->registry->getClass('output')->addNavigation( $this->lang->words['review_title_' . $type ], '' );
		
		//-----------------------------------------
		// Add HTML and print
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $this->registry->output->getTemplate('gallery_post')->review( $images, $album, $category, $type, array( 'sessionKey' => $sessionKey, 'start' => $start, 'perPage' => $perPage, 'total' => $total ) ) );
		$this->registry->getClass('output')->sendOutput();	
	}

	/**
	 * Process media.  Can be from an upload session or can be from an existing album
	 *
	 * @return	@e void
	 */
	protected function _process()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-key1', null, null, 403 );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sessionKey		= trim( $this->request['sessionKey'] );
		$album_id		= intval( $this->request['album_id'] );
		$category_id	= intval( $this->request['category_id'] );
		$type			= trim( $this->request['type'] );
		$start			= intval( $this->request['start'] );
		$perPage		= 40;
		$images			= array();
		$toDelete		= array();
		$album			= array();
		$category		= array();

		//-----------------------------------------
		// If it's an album, get data and make sure we have permission
		//-----------------------------------------

		if ( !empty( $album_id ) )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $album_id );
			
			if ( $sessionKey )
			{
				if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-5a', null, null, 403 );
				}
			}
			else
			{
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $album ) && ! $this->registry->gallery->helper('albums')->canModerate( $album ) )
				{
					$this->registry->output->showError( 'no_permission', '1-gallery-review-show-5b', null, null, 403 );
				}
			}
		}

		//-----------------------------------------
		// Is this an image upload session?
		//-----------------------------------------

		if ( $sessionKey )
		{
			$Db_images	= $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey, array( 'offset' => $start, 'limit' => $perPage ) );
			$type		= 'uploads';
		}

		//-----------------------------------------
		// Or are we editing an album?
		//-----------------------------------------

		elseif ( $album_id )
		{
			$_update	= array(
								'album_name'			=> trim( $this->request['album_name'] ),
								'album_description'		=> trim( $this->request['album_description'] ),
								'album_category_id'		=> intval( $this->request['album_category_id'] ),
								'album_type'			=> intval( $this->request['album_type'] ),
								'album_sort_options'	=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
								'album_watermark'		=> intval( $this->request['album_watermark'] ),
								'album_allow_comments'	=> intval($this->request['album_allow_comments']),
								'album_allow_rating'	=> intval($this->request['album_allow_rating']),
								);

			if( !$_update['album_name'] )
			{
				$_update['album_name']	= $this->lang->words['gallery_untitled_album'];
			}

			//-----------------------------------------
			// Make sure we have a valid parent
			//-----------------------------------------

			if ( !$_update['album_category_id'] OR !in_array( $_update['album_category_id'], $this->registry->gallery->helper('categories')->fetchAlbumCategories() ) )
			{
				$this->registry->output->showError( 'parent_zero_not_global', '1-gallery-review-process-4' );
			}

			//-----------------------------------------
			// Did admin enforce certain options?
			//-----------------------------------------

			$_update	= $this->registry->gallery->helper('albums')->forceAdminPresets( $_update );

			//-----------------------------------------
			// Get images
			//-----------------------------------------

			$Db_images	= $this->registry->gallery->helper('image')->fetchAlbumImages( $album_id, array( 'sortOrder' => 'asc', 'offset' => $start, 'limit' => $perPage ) );
			$type		= 'album';

			//-----------------------------------------
			// Save the album
			//-----------------------------------------

			$this->registry->gallery->helper('albums')->save( array( $album_id => $_update ) );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '1-gallery-review-process-3', null, null, 403 );
		}	

		//-----------------------------------------
		// Grab our known image ids
		//-----------------------------------------

		if ( is_array($this->request['imageIds']) && count($this->request['imageIds']) )
		{
			//-----------------------------------------
			// Set up bbcode parsing
			//-----------------------------------------

			IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
			IPSText::getTextClass('bbcode')->parse_html		 = 0;
			IPSText::getTextClass('bbcode')->parse_nl2br	 = 1;
			IPSText::getTextClass('bbcode')->parse_bbcode	 = 1;
			IPSText::getTextClass('bbcode')->parsing_section = 'gallery';
			
			$editorClass	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor			= new $editorClass();
			
			//-----------------------------------------
			// Loop over the images
			//-----------------------------------------

			foreach( $this->request['imageIds'] as $key => $data )
			{
				//-----------------------------------------
				// We deleting this image?
				//-----------------------------------------

				if ( ! empty( $this->request['delete'][ $key ] ) )
				{
					$toDelete[ $key ] = $Db_images[ $key ];
				}
				else
				{
					//-----------------------------------------
					// Set up our insert data array
					//-----------------------------------------

					$_isCover	= ( $this->request['makeCover'] == $key ) ? 1 : 0;
					$_follow	= ( ! empty( $this->request['follow'][ $key ] ) ) ? 1 : 0;
					$_caption	= trim( IPSText::stripslashes( $this->request['title'][ $key ] ) );
					
					if ( ! $_caption )
					{
						$this->registry->output->showError( $this->lang->words['gerror_no_title'], '1-gallery-review-process-2', null, null, 403 );
					}

					//-----------------------------------------
					// Check if we're ok with tags
					//-----------------------------------------

					$where	= array( 'meta_parent_id'	=> $album_id ? $album_id : $category_id,
									 'member_id'		=> $this->memberData['member_id'],
									 'fake_meta_id'		=> $key,
									);

					if ( $this->registry->galleryTags->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags_' . $key ] ) OR $this->settings['tags_min'] ) )
					{
						$this->registry->galleryTags->checkAdd( $_POST['ipsTags_' . $key ], array(
																					'meta_parent_id'	=> $album_id ? $album_id : $category_id,
																					'member_id'			=> $this->memberData['member_id'],
																					'meta_visible'		=> 1,
																					'fake_meta_id'		=> $key,
																				)
														);

						if ( $this->registry->galleryTags->getErrorMsg() )
						{
							$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
							$this->registry->output->showError( $this->registry->galleryTags->getFormattedError(), '1-gallery-review-process-2a', null, null, 403 );
						}
					}

					$images[ $key ]	= array(
											'image_description'		=> IPSText::getTextClass('bbcode')->preDbParse( $editor->process( $_POST[ 'description_' . $key ] ) ),
											'image_caption'			=> $_caption,
											'image_caption_seo'		=> IPSText::makeSeoTitle( $_caption ),
											'image_album_id'		=> $album_id,
											'image_category_id'		=> $category_id,
											'_isCover'				=> $_isCover,
											'_follow'				=> $_follow,
											'image_gps_show'		=> intval( $this->request['locationAllow'][ $key ] ),
											'image_copyright'		=> trim( $this->request['copyright'][ $key ] ) );
				}
			}
		}
		elseif ( $sessionKey )
		{
			$this->registry->output->showError( $this->lang->words['gerror_no_items'], '1-gallery-review-process-1', null, null, 403 );
		}

		//-----------------------------------------
		// Got any images to delete?
		//-----------------------------------------

		if ( count( $toDelete ) )
		{
			foreach( $toDelete as $image )
			{
				//-----------------------------------------
				// Do we have permission to delete?
				//-----------------------------------------

				if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_delete' ) )
				{
					if( !$this->registry->gallery->helper('image')->isOwner( $image ) OR !$this->memberData['g_del_own'] )
					{
						$this->registry->output->showError( 'no_permission', '10790.rd2', null, null, 403 );
					}
				}
			}

			$this->registry->gallery->helper('image')->delete( $toDelete );
		}
		
		//-----------------------------------------
		// Now save the images
		//-----------------------------------------

		if ( count( $images ) )
		{
			$this->registry->gallery->helper('image')->save( $images );
		}

		//-----------------------------------------
		// Is this paginated and we need to go to another page?
		//-----------------------------------------

		if( $this->request['previousPage'] )
		{
			$this->request['start']	= $this->request['start'] - $perPage;
			return $this->_showItems();
		}
		else if( $this->request['nextPage'] )
		{
			$this->request['start']	= $this->request['start'] + $perPage;
			return $this->_showItems();
		}
		
		//-----------------------------------------
		// Finish upload session
		//-----------------------------------------

		if ( $sessionKey )
		{
			$images		= $this->registry->gallery->helper('upload')->finish( $sessionKey );
			$firstImage	= $this->registry->gallery->helper('image')->fetchImage( array_pop( $images ), true, false );

			if( !$firstImage['image_approved'] )
			{
				if( $album_id )
				{
					$this->registry->output->redirectScreen( $this->lang->words['gal_redirect_mod_album'], $this->settings['base_url'] . 'app=gallery&amp;album=' . $album_id, $album['album_name_seo'], 'viewalbum' );
				}
				else
				{
					$this->registry->output->redirectScreen( $this->lang->words['gal_redirect_mod_album'], $this->settings['base_url'] . 'app=gallery&amp;category=' . $category_id, $this->registry->gallery->helper('categories')->fetchCategory( $category_id, 'category_name_seo' ), 'viewcategory' );
				}
			}
		}
		
		//-----------------------------------------
		// Redirect to category or album
		//-----------------------------------------

		if( $album_id )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $album_id, $album['album_name_seo'], false, 'viewalbum' );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $category_id, $this->registry->gallery->helper('categories')->fetchCategory( $category_id, 'category_name_seo' ), false, 'viewcategory' );
		}
	}
	
	/**
	 * Create a suggested image caption based on the file name
	 *
	 * @param	string
	 * @return	@e string
	 */
	protected function _smrtTitle( $text )
	{
		//-----------------------------------------
		// Make sure this is a filename
		//-----------------------------------------

		if ( ! preg_match( '#^(.*)\.\S{2,4}$#', $text ) )
		{
			return htmlspecialchars( $text );
		}
		
		//-----------------------------------------
		// Get rid of file extension
		//-----------------------------------------

		$text	= preg_replace( '#^(.*)\.\S{2,4}$#', "\\1", $text );
		
		//-----------------------------------------
		// Convert certain chars to spaces
		//-----------------------------------------

		$text	= str_replace( array( '-', '_', '+', '%20' ), ' ', $text );

		//-----------------------------------------
		// Upper-case the words
		//-----------------------------------------

		$_t	= explode( ' ', $text );
		$_f	= array();
		
		foreach( $_t as $w )
		{
			if ( strlen( $w ) > 3 )
			{
				$_f[] = $w;
			}
			else
			{
				$_f[] = ucfirst( $w );
			}
		}

		//-----------------------------------------
		// Return the suggested title
		//-----------------------------------------

		return implode( ' ', $_f );
	}
}