<?php
/**
 * @file		mod.php 	IP.Gallery moderation controller
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-12-21 17:44:03 -0500 (Fri, 21 Dec 2012) $
 * @version		v5.0.5
 * $Revision: 11749 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_mod extends ipsCommand
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
		// Check our authorization key
		//-----------------------------------------

		if( $this->request['secure_key'] AND !$this->request['auth_key'] )
		{
			$this->request['auth_key']	= $this->request['secure_key'];
		}

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10790, null, null, 403 );
		}

		//-----------------------------------------
		// Determine what to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'delete':
				$this->_delete();
			break;

			case 'rotate':
				$this->_rotate();
			break;

			case 'move':
				$this->_move();
			break;

			case 'approveToggle':
				$this->_approveToggle();
			break;

			case 'featureToggle':
				$this->_featureToggle();
			break;

			case 'pinToggle':
				$this->_pinToggle();
			break;

			case 'setAsCover':
				$this->_setAsCover();
			break;

			default:
				$this->registry->output->showError( 'no_permission', '10790.action', null, null, 403 );
			break;
		}
	}

	/**
	 * Sets an image as a cover photo for one or more containers
	 *
	 * @return	@e void
	 */
	protected function _setAsCover()
	{
		//-----------------------------------------
		// Get our image
		//-----------------------------------------

		$image				= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );
		$selectedContainers	= IPSLib::cleanIntArray( $this->request['cover_containers'] );

		//-----------------------------------------
		// If we are just a member, we can only set as album cover
		// Otherwise we can set for album and parent categories
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_set_cover_image' ) )
		{
			//-----------------------------------------
			// Are we the owner?
			//-----------------------------------------

			if( !$image['image_album_id'] OR !$this->registry->gallery->helper('albums')->isOwner( $image ) )
			{
				$this->registry->output->showError( 'no_permission', '10790.sc1', null, null, 403 );
			}

			//-----------------------------------------
			// Save for the album
			//-----------------------------------------

			if( in_array( 'a' . $image['image_album_id'], $selectedContainers ) )
			{
				$this->registry->gallery->helper('albums')->save( array( 'album_id' => $image['image_album_id'], 'album_cover_img_id' => $image['image_id'] ) );
			}
			else if( $image['image_id'] == $image['album_cover_img_id'] )
			{
				$this->registry->gallery->helper('albums')->save( array( 'album_id' => $image['image_album_id'], 'album_cover_img_id' => 0 ) );
			}
		}
		else
		{
			//-----------------------------------------
			// Setting for album?
			//-----------------------------------------

			if( $image['image_album_id'] )
			{
				if( in_array( 'a' . $image['image_album_id'], $selectedContainers ) )
				{
					$this->registry->gallery->helper('albums')->save( array( 'album_id' => $image['image_album_id'], 'album_cover_img_id' => $image['image_id'] ) );
				}
				else if( $image['image_id'] == $image['album_cover_img_id'] )
				{
					$this->registry->gallery->helper('albums')->save( array( 'album_id' => $image['image_album_id'], 'album_cover_img_id' => 0 ) );
				}
			}

			//-----------------------------------------
			// Now set categories
			//-----------------------------------------

			if( in_array( $image['image_category_id'], $selectedContainers ) )
			{
				$this->DB->update( 'gallery_categories', array( 'category_cover_img_id' => $image['image_id'] ), 'category_id=' . $image['image_category_id'] );
			}
			else if( $image['image_id'] == $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'], 'category_cover_img_id' ) )
			{
				$this->DB->update( 'gallery_categories', array( 'category_cover_img_id' => 0 ), 'category_id=' . $image['image_category_id'] );
			}

			foreach( $this->registry->gallery->helper('categories')->getParents( $image['image_category_id'] ) as $category )
			{
				if( in_array( $category, $selectedContainers ) )
				{
					$this->DB->update( 'gallery_categories', array( 'category_cover_img_id' => $image['image_id'] ), 'category_id=' . $category );
				}
				else if( $image['image_id'] == $this->registry->gallery->helper('categories')->fetchCategory( $category, 'category_cover_img_id' ) )
				{
					$this->DB->update( 'gallery_categories', array( 'category_cover_img_id' => 0 ), 'category_id=' . $category );
				}
			}

			$this->registry->gallery->helper('categories')->rebuildCatCache();
		}

		//-----------------------------------------
		// Send back
		//-----------------------------------------

		$this->registry->output->redirectScreen( $this->lang->words['cover_image_set'], $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], 'viewimage' );
	}

	/**
	 * Approves the image
	 *
	 * @return	@e void
	 */
	protected function _approveToggle()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		if( $image['image_approved'] == 1 )
		{
			if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_hide' ) )
			{
				$this->registry->output->showError( 'no_permission', '10790.sc1h', null, null, 403 );
			}
		}
		else
		{
			if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_approve' ) )
			{
				$this->registry->output->showError( 'no_permission', '10790.sc1a', null, null, 403 );
			}
		}

		//-----------------------------------------
		// Toggle visibility
		//-----------------------------------------

		$this->registry->gallery->helper('moderate')->toggleVisibility( array( $image['image_id'] ), intval( $this->request['val'] ) );
		
		//-----------------------------------------
		// Where are we going now?
		//-----------------------------------------

		if ( $this->request['modcp'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=gallery&tab=' . $this->request['modcp'] );
		}
		else
		{
			if( $image['image_album_id'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $image['album_id'], $image['album_name_seo'], false, 'viewalbum' );
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $image['category_id'], $image['category_name_seo'], false, 'viewcategory' );
			}
		}
	}

	/**
	 * Features the image
	 *
	 * @return	@e void
	 */
	protected function _featureToggle()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		//-----------------------------------------
		// Verify we're a moderator
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790.ft1', null, null, 403 );
		}

		//-----------------------------------------
		// Feature or unfeature the image
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_feature_flag' => ( $this->request['val'] == 1 ) ? 1 : 0 ), 'image_id=' . $image['image_id'] );

		//-----------------------------------------
		// Where are we going now?
		//-----------------------------------------

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], false, 'viewimage' );
	}

	/**
	 * Pins the image
	 *
	 * @return	@e void
	 */
	protected function _pinToggle()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );
		
		//-----------------------------------------
		// Verify we're a moderator
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790.ft1', null, null, 403 );
		}

		//-----------------------------------------
		// Feature or unfeature the image
		//-----------------------------------------

		$this->DB->update( 'gallery_images', array( 'image_pinned' => ( $this->request['val'] == 1 ) ? 1 : 0 ), 'image_id=' . $image['image_id'] );

		//-----------------------------------------
		// Where are we going now?
		//-----------------------------------------

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], false, 'viewimage' );
	}
	
	/**
	 * Moves the image.
	 *
	 * Rules:
	 * -If you are a moderator with permission to move images in the container the image is currently in, you can move to anywhere you can view
	 * -If you are a member, you can move images between your own albums
	 * -If you are a member, you cannot move images from a category, to a category, or from/to an album you do not own
	 *
	 * @return	@e void
	 */
	protected function _move()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );

		if ( empty( $image['image_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790.m1', null, null, 403 );
		}

		//-----------------------------------------
		// Where do we want to move to?
		//-----------------------------------------

		$moveToAlbum	= intval( $this->request['move_to_album_id'] );
		$movetoCategory	= intval( $this->request['move_to_category_id'] );

		//-----------------------------------------
		// If we are a moderator, we're fine
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_move' ) )
		{
			//-----------------------------------------
			// Otherwise we only have permission if we are moving an image
			// from an album we own and to an album we own
			//-----------------------------------------

			if( !$image['image_album_id'] OR !$moveToAlbum OR !$this->registry->gallery->helper('albums')->isOwner( $image ) OR !$this->registry->gallery->helper('albums')->isOwner( $moveToAlbum ) )
			{
				$this->registry->output->showError( 'no_permission', '10790.m2', null, null, 403 );
			}
		}

		//-----------------------------------------
		// Move the image
		//-----------------------------------------

		$this->registry->gallery->helper('moderate')->moveImages( array( $image['image_id'] ), $moveToAlbum, $movetoCategory );

		//-----------------------------------------
		// Where are we going now?
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $image['album_id'], $image['album_name_seo'], false, 'viewalbum' );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $image['category_id'], $image['category_name_seo'], false, 'viewcategory' );
		}
	}
	
	/**
	 * Removes the specified image, permissions checked
	 *
	 * @return	@e void
	 */
	protected function _delete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageid'] ) );

		if ( empty( $image['image_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790.d1', null, null, 403 );
		}

		//-----------------------------------------
		// Do we have permission to delete?
		//-----------------------------------------

		if( !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_delete' ) )
		{
			if( !$this->registry->gallery->helper('image')->isOwner( $image ) OR !$this->memberData['g_del_own'] )
			{
				$this->registry->output->showError( 'no_permission', '10790.d2', null, null, 403 );
			}
		}

		//-----------------------------------------
		// Delete the image
		//-----------------------------------------

		$this->registry->gallery->helper('moderate')->deleteImages( array( $image['image_id'] => $image ) );
		
		//-----------------------------------------
		// Where are we going now?
		//-----------------------------------------

		if ( $this->request['modcp'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=gallery&tab=' . $this->request['modcp'] );
		}
		else
		{
			if( $image['image_album_id'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $image['album_id'], $image['album_name_seo'], false, 'viewalbum' );
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;category=' . $image['category_id'], $image['category_name_seo'], false, 'viewcategory' );
			}
		}
	}
	
	/**
	 * Rotates an image. Source could be upload table or image table
	 *
	 * @return	@e void
	 */
	protected function _rotate()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( intval( $this->request['imageId'] ) );
		$dir		= trim( $this->request['dir'] );
		$angle		= $dir == 'left' ? 90 : -90;

		//-----------------------------------------
		// Are we the image owner, or a moderator?
		//-----------------------------------------

		if( !$this->registry->gallery->helper('image')->isOwner( $image ) AND !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'] ) )
		{
			$this->registry->output->showError( 'no_permission', '10790.r1', null, null, 403 );
		}

		//-----------------------------------------
		// Rotate and return
		//-----------------------------------------

		if ( $this->registry->gallery->helper('image')->rotateImage( $image, $angle ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], false, 'viewimage' );
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '10790.r2', null, null, 403 );
		}
	}
}