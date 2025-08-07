<?php
/**
 * @file		sizes.php 	IP.Gallery controller: show different image sizes
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-07-10 18:08:06 -0400 (Tue, 10 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11056 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_sizes extends ipsCommand
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
		// Do we have an image?
		//-----------------------------------------

		$imageId	= intval($this->request['image']);
		
		if ( $imageId < 1 )
		{
			$this->registry->output->showError( 'img_not_found', '10740.s1', null, null, 404 );
		}

		//-----------------------------------------
		// Fetch the image
		//-----------------------------------------

		$image		= $this->registry->gallery->helper('image')->fetchImage( $imageId );

		if ( ! $image['image_id'] )
		{
			$this->registry->output->showError( 'img_not_found', '10740.s2', null, null, 404 );
		}

		if ( ! $this->registry->gallery->helper('image')->isViewable( $image ) )
		{
			$this->registry->output->showError( 'img_not_found', '10740.s3', null, null, 403 );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$size		= trim( $this->request['size'] );
		$options	= array();
		
		//-----------------------------------------
		// Determine size
		//-----------------------------------------

		switch( $size )
		{
			case 'square':
				$options	= array( 'type' => 'thumb' , 'link-page' => 'none', 'thumbClass' => 'galattach' );
			break;
			case 'small':
				$options	= array( 'type' => 'small' , 'link-page' => 'none', 'thumbClass' => 'galattach' );
			break;
			case 'medium':
				$options	= array( 'type' => 'medium', 'link-page' => 'none', 'thumbClass' => 'galattach' );
			break;
			case 'large':
				$options	= array( 'type' => 'large' , 'link-page' => 'none', 'thumbClass' => 'galattach' );
			break;
		}

		//-----------------------------------------
		// Check image data sizes
		//-----------------------------------------

		if ( empty( $image['_data']['sizes'] ) )
		{
			$this->registry->gallery->helper('image')->buildSizedCopies( $image );

			$image	= $this->registry->gallery->helper('image')->fetchImage( $imageId );
		}
		
		//-----------------------------------------
		// Build the image tag
		//-----------------------------------------

		$image['tag'] = $this->registry->gallery->helper('image')->makeImageTag( $image, $options );

		//-----------------------------------------
		// Set some keys for the session class
		//-----------------------------------------

		$this->member->sessionClass()->addQueryKey( 'location_2_id', intval( $image['image_album_id'] ) );
		$this->member->sessionClass()->addQueryKey( 'location_3_id', intval( $image['image_category_id'] ) );

		//-----------------------------------------
		// Build HTML output
		//-----------------------------------------

		$output = $this->registry->output->getTemplate('gallery_img')->sizes( $image, $size );

		//-----------------------------------------
		// Set the meta image source to the image tag
		//-----------------------------------------

		$this->settings['meta_imagesrc'] = $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'thumb', 'link-type' => 'src' ) );

		//-----------------------------------------
		// Fetch navigation
		//-----------------------------------------

		$nav	= array( array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );
		$cat	= $this->registry->gallery->helper('categories')->getNav( $image['image_category_id'] );

		foreach( $cat as $_categoryNav )
		{
			$nav[]	= array( $_categoryNav[0], $_categoryNav[1], $_categoryNav[2], 'viewcategory' );
		}

		if( $image['image_album_id'] )
		{
			$_album	= $this->registry->gallery->helper('albums')->fetchAlbum( $image['image_album_id'] );
			$title	= $_album['album_name'];

			$nav[]	= array( $_album['album_name'], 'app=gallery&amp;album=' . $_album['album_id'], $_album['album_name_seo'], 'viewalbum' );
		}
		else
		{
			$title	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'], 'category_name' );
		}

		$nav[] = array( $image['image_caption'], 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], 'viewimage' );
		$nav[] = array( $this->lang->words['size_ucfirst'] . ': ' . $this->lang->words[ $size.'_ucfirst' ] );

		//-----------------------------------------
		// Set output elements
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $output );
		$this->registry->getClass('output')->setTitle( $image['image_caption'] . ' (' . $this->lang->words['size_ucfirst'] . ': ' . $this->lang->words[ $size.'_ucfirst' ] . ') - ' . $title . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );

		foreach( $nav as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}
		
		$this->registry->getClass('output')->addCanonicalTag( 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], 'viewimage' );
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;image=' . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ) );
		$this->registry->output->addMetaTag( 'keywords', $image['image_caption'], TRUE );
		$this->registry->output->addMetaTag( 'description', sprintf( $this->lang->words['gallery_img_meta_description'], $image['image_caption'], $title, $image['image_description'] ), FALSE, 155 );

		//-----------------------------------------
		// Send output
		//-----------------------------------------

		$this->registry->getClass('output')->sendOutput();
	}
}