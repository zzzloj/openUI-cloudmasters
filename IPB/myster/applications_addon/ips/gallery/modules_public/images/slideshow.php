<?php
/**
 * @file		slideshow.php 	Display a slideshow within a category or album
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-08-17 14:43:59 -0400 (Fri, 17 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_slideshow extends ipsCommand
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
		// Get the container and images
		//-----------------------------------------

		if( $this->request['type'] == 'album' )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbum( intval($this->request['typeid']) );
			$images		= $this->registry->gallery->helper('image')->fetchAlbumImages( $album['album_id'], array( 'media' => false, 'offset' => 0, 'limit' => 250, 'sortKey' => $album['album_sort_options__key'], 'sortOrder' => $album['album_sort_options__dir'] ) );
			$url		= $this->registry->output->buildSEOUrl( "app=gallery&amp;album={$album['album_id']}", 'public', $album['album_name_seo'], 'viewalbum' );
			$_title		= $album['album_name'];
		}
		else
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( intval($this->request['typeid']) );
			$images		= $this->registry->gallery->helper('image')->fetchCategoryImages( $category['category_id'], array( 'media' => false, 'offset' => 0, 'limit' => 250, 'sortKey' => $album['album_sort_options__key'], 'sortOrder' => $album['album_sort_options__dir'] ) );
			$url		= $this->registry->output->buildSEOUrl( "app=gallery&amp;category={$category['category_id']}", 'public', $category['category_name_seo'], 'viewcategory' );
			$_title		= $category['category_name'];
		}
		
		//-----------------------------------------
		// Do we have any images?
		//-----------------------------------------

		if ( !is_array($images) || !count($images) )
		{
			$this->registry->output->showError( 'slideshow_no_images', '1-gallery-slideshow-1', null, null, 404 );
		}
		
		//-----------------------------------------
		// INIT some vars
		//-----------------------------------------

		$imageIds	= array();
		$lastID		= 0;
		$memberIds	= array();
		
		foreach( $images as $id => $image )
		{
			//-----------------------------------------
			// Make sure deleted members don't break anything
			//-----------------------------------------

			$image['member_id'] = intval($image['member_id']);
			
			//-----------------------------------------
			// Store the data
			//-----------------------------------------

			$imageIds[]								= $image['image_id'];
			$memberIds[ $image['image_member_id'] ]	= $image['image_member_id']; 
			$lastID									= $image['image_id'];
		}
		
		//-----------------------------------------
		// Remove guests, load members and parse member photo
		//-----------------------------------------

		unset($memberIds[0]);
		$members	= IPSMember::load( $memberIds, 'all' );
		
		foreach( $images as $id => $image )
		{
			$images[ $id ]['_photo']	= IPSMember::buildProfilePhoto( $members[ $image['image_member_id'] ] );
		}
		
		//-----------------------------------------
		// Reset CSS for slideshow
		//-----------------------------------------

		$this->registry->output->clearLoadedCss();
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['css_base_url'] . 'style_css/' . $this->registry->output->skin['_csscacheid'] . '/ipgallery_slideshow.css' );

		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->registry->getClass('output')->setTitle( $this->lang->words['ss_title'] . ' - ' . $_title . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->output->popUpWindow( $this->registry->output->getTemplate('gallery_img')->slideShow( $url, implode( ',', $imageIds ), $images, $lastID ) );
	}
}