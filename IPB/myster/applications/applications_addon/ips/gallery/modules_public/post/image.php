<?php
/**
 * @file		image.php 	Controller to manage uploading images
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * $LastChangedDate: 2013-01-03 08:33:22 -0500 (Thu, 03 Jan 2013) $
 * @version		v5.0.5
 * $Revision: 11777 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_post_image extends ipsCommand
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
		// INIT
		//-----------------------------------------

		$image		= array();
		$album		= array();
		$category	= array();
		
		//-----------------------------------------
		// Load additional language file(s)
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
		
		//-----------------------------------------
		// Editing an image?  Check our access
		//-----------------------------------------

		if ( $this->request['img'] )
		{
			$image	= $this->registry->gallery->helper('image')->validateAccess( intval($this->request['img']) );

			$this->request['album_id']		= $image['image_album_id'];
			$this->request['category_id']	= $image['image_category_id'];
		}
		
		//-----------------------------------------
		// Fetch the requested album
		//-----------------------------------------

		if ( $this->request['album_id'] )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbum( intval($this->request['album_id']) );

			$this->request['category_id']	= $album['album_category_id'];
		}

		//-----------------------------------------
		// Fetch the requested category
		//-----------------------------------------

		if ( $this->request['category_id'] )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( intval($this->request['category_id']) );

			if( !$this->registry->gallery->helper('categories')->isViewable( $category['category_id'] ) )
			{
				$this->registry->output->showError( 'gallery_404', '107141.up1', null, null, 404 );
			}
		}

		//-----------------------------------------
		// Get the tagging library
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}

		//-----------------------------------------
		// Force a special upload domain?
		//-----------------------------------------

		$this->settings['_upload_url']	= $this->settings['base_url'];
		
		//-----------------------------------------
		// And off we go
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'form':
			default:
				$this->_addForm( $album, $category );
			break;

			case 'editImage':
				$this->_editForm( $image, $album, $category );
			break;

			case 'editImageSave':
				$this->_editSave( $image, $album, $category );
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Shows the add new image form
	 *
	 * @param	array	Album data
	 * @param	array 	Category data
	 * @return	@e void
	 */
	protected function _addForm( $album=array(), $category=array() )
	{
		//-----------------------------------------
		// Quick check if we can upload
		//-----------------------------------------

		if ( ! $this->registry->gallery->getCanUpload() )
		{
			$this->registry->output->showError( 'no_img_post', 10762, null, null, 403 );
		}

		//-----------------------------------------
		// Do we have any temporary uploads? If so,
		// give user ability to clear or finish.
		//-----------------------------------------

		if( $this->memberData['member_id'] )
		{
			//-----------------------------------------
			// Delete our temp uploads so that we can continue
			//-----------------------------------------

			if( $this->request['continue'] )
			{
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'gallery_images_uploads',
										 'where'  => 'upload_member_id=' . $this->memberData['member_id'],
								 )		);

				$outer = $this->DB->execute();

				while( $row = $this->DB->fetch( $outer ) )
				{
					$row	= $this->registry->gallery->helper('upload')->_remapAsImage( $row );
					
					$this->registry->gallery->helper('moderate')->removeImageFiles( $row );
				}

				$this->DB->delete( 'gallery_images_uploads', 'upload_member_id=' . $this->memberData['member_id'] );
			}
			else
			{
				/* There could be more than one, but we don't care - all images should have the same session key with this protection in place */
				$temp	= $this->DB->buildAndfetch( array( 'select' => '*', 'from' => 'gallery_images_uploads', 'where' => 'upload_member_id=' . $this->memberData['member_id'] ) );

				if( $temp['upload_key'] )
				{
					$this->registry->getClass('output')->setTitle( $this->lang->words['temp_uploads_unfinished'] . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
					$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
					$this->registry->getClass('output')->addNavigation( $this->lang->words['temp_uploads_unfinished'], '' );

					$this->registry->getClass('output')->addContent( $this->registry->output->getTemplate('gallery_post')->finishPreviousSession( $temp['upload_session']) );
					return;
				}
			}
		}

		//-----------------------------------------
		// If this is an album, check if we can upload into it
		//-----------------------------------------

		if ( count($album) AND !$this->registry->gallery->helper('albums')->isUploadable( $album ) )
		{
			$album	= array();
		}
		else if( !$album['album_id'] AND count($category) AND !$this->registry->permissions->check( 'post', $category ) )
		{
			$category	= array();
		}

		if( !in_array( $category['category_id'], $this->registry->gallery->helper('categories')->fetchImageCategories() ) )
		{
			unset($category);
		}

		//-----------------------------------------
		// Get data for form
		//-----------------------------------------

		$sessionKey	= $this->registry->gallery->helper('upload')->generateSessionKey();
		$stats		= $this->registry->gallery->helper('upload')->fetchStats();
		$extensions	= implode( ', ', array_merge( $this->registry->gallery->helper('image')->allowedExtensions(), ( $this->memberData['g_movies'] ? $this->registry->gallery->helper('media')->allowedExtensions() : array() ) ) );

		if( $stats['maxItem'] < 1 )
		{
			$this->registry->output->showError( 'gal_no_more_uploads', '10762.u1', null, null, 403 );
		}

		//-----------------------------------------
		// Get categories
		//-----------------------------------------

		$categories	= array();
		$covers		= array();

		foreach( $this->registry->gallery->helper('categories')->catJumpList( true, 'post' ) as $_category )
		{
			$myCategory								= $this->registry->gallery->helper('categories')->fetchCategory( $_category[0] );
			$myCategory['category_name']			= $_category[1];
			$myCategory['_acceptImages']			= ( in_array( $_category[0], $this->registry->gallery->helper('categories')->fetchImageCategories() ) );
			$myCategory['_coverImage']				= $myCategory['category_cover_img_id'] ? $myCategory['category_cover_img_id'] : $myCategory['category_last_img_id'];
			$covers[ $myCategory['_coverImage'] ]	= $myCategory['_coverImage'];

			$categories[]	= $myCategory;
		}

		if( count($covers) )
		{
			$_categoryImages	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'imageIds' => $covers, 'parseImageOwner' => false, 'orderBy' => false ) );

			if( count($_categoryImages) )
			{
				foreach( $categories as $_k => $_categoryRow )
				{
					$categories[ $_k ]['_coverImage']	= $_categoryRow['_coverImage'] ? $_categoryImages[ $_categoryRow['_coverImage'] ] : array();
					$categories[ $_k ]['thumb']			= $this->registry->gallery->helper('image')->makeImageLink( $categories[ $_k ]['_coverImage'], array( 'type' => 'thumb', 'coverImg' => true, 'link-container-type' => 'category' ) );
				}
			}
		}

		//-----------------------------------------
		// Set output data
		//-----------------------------------------

		$lang 	= $this->request['media'] ? "nav_submit_media" : "nav_submit_post";
		$string	= ( $album['album_id'] || $category['category_id'] ) ? sprintf( $this->lang->words[ $lang ], ( $album['album_id'] ? $album['album_name'] : $category['category_name'] ) ) : $this->lang->words['nav_generic_submit'];

		$this->registry->getClass('output')->setTitle( $string . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );

		if ( !empty($album['album_id']) OR !empty($category['category_id']) )
		{
			$this->_buildNavigationBar( $string, $album, $category );
		}
		else
		{
			$this->registry->getClass('output')->addNavigation( $this->lang->words['upload_media'], '' );
		}

		$this->registry->getClass('output')->addContent( $this->registry->output->getTemplate('gallery_post')->uploadForm( $sessionKey, $album, $category, $categories, $stats, $extensions ) );
	}

	/**
	 * Build nav bar
	 *
	 * @param	string	String to use for current entry
	 * @param	array 	Array of album data for current entry
	 * @param	array 	Array of category data for current entry
	 * @return	@e void
	 */
	protected function _buildNavigationBar( $text, $album=array(), $category=array() )
	{
		//-----------------------------------------
		// Get parents
		//-----------------------------------------

		if( $album['album_id'] )
		{
			$_parents	= $this->registry->gallery->helper('categories')->getNav( $album['album_category_id'] );
		}
		else if( $category['catgory_id'] )
		{
			$_parents	= $this->registry->gallery->helper('categories')->getNav( $category['catgory_id'] );
		}
		else
		{
			return;
		}

		//-----------------------------------------
		// Add parent nav bar entries
		//-----------------------------------------

		if( count($_parents) )
		{
			foreach( $_parents as $_parent )
			{
				$this->registry->getClass('output')->addNavigation( $_parent[0], $_parent[1], $_parent[2], 'viewcategory' );
			}
		}

		//-----------------------------------------
		// Add our current nav bar entry
		//-----------------------------------------

		if( $album['album_id'] )
		{
			$this->registry->getClass('output')->addNavigation( $text, "app=gallery&amp;album={$album['album_id']}", $album['album_name_seo'], 'viewalbum' );
		}
		else if( $category['catgory_id'] )
		{
			$this->registry->getClass('output')->addNavigation( $text, "app=gallery&amp;category={$category['category_id']}", $category['category_name_seo'], 'viewcategory' );
		}
	}

	/**
	 * Shows the edit image form
	 *
	 * @param	array		$image		Image data
	 * @param	array		$album		Album data
	 * @param	array		$category	Category data
	 * @param	array		$errors		Errors found
	 * @param	string		$preview	Description preview
	 * @return	@e void
	 */
	protected function _editForm( $image, $album=array(), $category=array(), $errors=array(), $preview='' )
	{
		//-----------------------------------------
		// Check we have an image
		//-----------------------------------------

		if ( !$image['image_id'] )
		{
			$this->registry->output->showError( 'error_img_not_found', 10764.1, null, null, 403 );
		}

		//-----------------------------------------
		// Verify we can edit
		//-----------------------------------------

		if ( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_edit' ) AND ( !$this->registry->gallery->helper('image')->isOwner( $image ) OR !$this->memberData['g_edit_own'] ) )
		{
			$this->registry->output->showError( 'no_permission', 10764.2, null, null, 403 );
		}

		//-----------------------------------------
		// Previewing?
		//-----------------------------------------

		$image['_image_caption']		= isset($this->request['image_caption']) ? trim($this->request['image_caption']) : $image['image_caption'];
		$image['image_description']		= isset($this->request['image_description']) ? $_POST['image_description'] : $image['image_description'];
		$image['image_copyright']		= isset($this->request['image_copyright']) ? trim($this->request['image_copyright']) : $image['image_copyright'];
		$image['image_gps_show']		= isset($this->request['image_gps_show']) ? intval($this->request['image_gps_show']) : $image['image_gps_show'];
		
		//-----------------------------------------
		// Get editor
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $classToLoad();
		$editor->setAllowBbcode( true );
		$editor->setAllowSmilies( true );
		$editor->setAllowHtml( false );

		$image['editor']	= $editor->show( 'image_description', array(), $image['image_description'] );

		//-----------------------------------------
		// Get tags
		//-----------------------------------------

		$where	= array(
						'meta_id'			=> $image['image_id'],
						'meta_parent_id'	=> $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'],
						'member_id'			=> $this->memberData['member_id']
						);
	
		if ( $_REQUEST['ipsTags_' . $image['image_id']] )
		{
			$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags_' . $image['image_id']] ) );
		}
	
		if ( $this->registry->galleryTags->can( 'edit', $where ) )
		{
			$image['_tagBox']	= $this->registry->galleryTags->render( 'entryBox', $where );
		}

		//-----------------------------------------
		// Set output data
		//-----------------------------------------

		$this->registry->output->setTitle( sprintf( $this->lang->words['editing_image'], $image['image_caption'] ) . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addNavigation( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		$this->_buildNavigationBar( $album['album_id'] ? $album['album_name'] : $category['category_name'], $album, $category );
		$this->registry->output->addNavigation( $image['image_caption'], "app=gallery&amp;image={$image['image_id']}", $image['image_caption_seo'], 'viewimage' );
		$this->registry->output->addNavigation( $this->lang->words['edit_post'], '' );

		$this->registry->output->addContent( $this->registry->output->getTemplate('gallery_post')->editImageForm( $image, $album, $category, $errors, $preview ) );
	}
	
	/**
	 * Saves an edited image
	 *
	 * @param	array		$image		Image data
	 * @param	array		$album		Album data
	 * @param	array		$category	Category data
	 * @return	@e void
	 */
	protected function _editSave( $image, $album=array(), $category=array() )
	{
		//-----------------------------------------
		// Check we have an image
		//-----------------------------------------

		if ( !$image['image_id'] )
		{
			$this->registry->output->showError( 'error_img_not_found', 10764.1, null, null, 403 );
		}

		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10764.11, null, null, 403 );
		}

		//-----------------------------------------
		// Verify we can edit
		//-----------------------------------------

		if ( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_edit' ) AND ( !$this->registry->gallery->helper('image')->isOwner( $image ) OR !$this->memberData['g_edit_own'] ) )
		{
			$this->registry->output->showError( 'no_permission', 10764.2, null, null, 403 );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$errors		= array();
		$caption	= trim($this->request['image_caption']);
		
		//-----------------------------------------
		// Got a caption?
		//-----------------------------------------

		if( !$caption )
		{
			$errors[] = $this->lang->words['gerror_no_title'];
		}
		
		if ( count($errors) && !isset($this->request['preview']) )
		{
			return $this->_editForm( $image, $album, $category, $errors );
		}

		//-----------------------------------------
		// Parse description
		//-----------------------------------------

		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'gallery';
		
		$editorClass	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $editorClass();
			
		$description	= $editor->process( $_POST['image_description'] );
		$description	= IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $description ) );

		//-----------------------------------------
		// We previewing?
		//-----------------------------------------

		if ( isset($this->request['preview']) )
		{
			return $this->_editForm( $image, $album, $category, $errors, $description );
		}
		
		//-----------------------------------------
		// Build update array
		//-----------------------------------------

		$update	= array(
						'image_caption'			=> $caption,
						'image_description'		=> $description,
						'image_gps_show'		=> intval($this->request['image_gps_show']),
						'image_copyright'		=> trim($this->request['image_copyright'])
						);
		
		//-----------------------------------------
		// If we're not uploading a new image, just update DB
		//-----------------------------------------

		if ( !isset($_FILES['newImage']['name']) || empty($_FILES['newImage']['name']) || empty($_FILES['newImage']['size']) || $_FILES['newImage']['name'] == "none" AND
		 !isset($_FILES['FILE_UPLOAD']['name']) || empty($_FILES['FILE_UPLOAD']['name']) || empty($_FILES['FILE_UPLOAD']['size']) || $_FILES['FILE_UPLOAD']['name'] == "none" )
		{
			$update['image_caption_seo']	= IPSText::makeSeoTitle($caption);
			
			$this->DB->update( 'gallery_images', $update, 'image_id=' . $image['image_id'] );
			
			$this->registry->galleryTags->replace( $_POST['ipsTags_' . $image['image_id']], array(	'meta_id'			=> $image['image_id'],
																									'meta_parent_id'	=> $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'],
																									'member_id'			=> $this->memberData['member_id'],
																									'meta_visible'		=> ( $image['image_approved'] == 1 ) ? 1 : 0 ) );
				
			$image = array_merge( $image, $update );
		}

		//-----------------------------------------
		// Is it JUST the media thumb?
		//-----------------------------------------

		else if( !isset($_FILES['newImage']['name']) || empty($_FILES['newImage']['name']) || empty($_FILES['newImage']['size']) || $_FILES['newImage']['name'] == "none" AND
		 isset($_FILES['FILE_UPLOAD']['name']) AND !empty($_FILES['FILE_UPLOAD']['name']) AND !empty($_FILES['FILE_UPLOAD']['size']) AND $_FILES['FILE_UPLOAD']['name'] != "none" )
		{
			try
			{
				$return	= $this->registry->gallery->helper('upload')->mediaThumb( $image['image_id'] );
			}
			catch( Exception $e ){}

			$update['image_caption_seo']	= IPSText::makeSeoTitle($caption);
			
			$this->DB->update( 'gallery_images', $update, 'image_id=' . $image['image_id'] );
			
			$this->registry->galleryTags->replace( $_POST['ipsTags_' . $image['image_id']], array(	'meta_id'			=> $image['image_id'],
																									'meta_parent_id'	=> $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'],
																									'member_id'			=> $this->memberData['member_id'],
																									'meta_visible'		=> ( $image['image_approved'] == 1 ) ? 1 : 0 ) );
				
			$image = array_merge( $image, $update );
		}

		//-----------------------------------------
		// Otherwise we need to call the uploader object
		//-----------------------------------------

		else
		{
			try
			{
				$image	= $this->registry->gallery->helper('upload')->editImage( 'newImage', $image['image_id'], $update, $image['image_member_id'] );
			}
			catch( Exception $e )
			{
				$this->registry->output->showError( $this->lang->words[ $e->getMessage() ] ? $this->lang->words[ $e->getMessage() ] : $e->getMessage(), '107141.up99', null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], false, 'viewimage' );
	}
}