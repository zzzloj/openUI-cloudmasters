<?php
/**
 * @file		rss.php 	Display category or album RSS feed
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-12-03 21:50:28 -0500 (Mon, 03 Dec 2012) $
 * @version		v5.0.5
 * $Revision: 11675 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_rss extends ipsCommand
{
	/**
	 * RSS class
	 *
	 * @var	object
	 */
	private $rss;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get RSS object
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$this->rss				= new $classToLoad();
		$this->rss->doc_type	= ipsRegistry::$settings['gb_char_set'];

		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch( $this->request['type'] )
		{
			case 'album':
				$this->_albumRss( intval($this->request['typeid']) );
			break;

			case 'category':
				$this->_categoryRss( intval($this->request['typeid']) );
			break;
		}
		
		//-----------------------------------------
		// Build RSS document
		//-----------------------------------------

		$this->rss->createRssDocument();

		$this->rss->rss_document	= $this->registry->output->replaceMacros( $this->rss->rss_document );
		
		if ( ! $this->rss->rss_document )
		{
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
			{
				header("HTTP/1.0 503 Service Temporarily Unavailable");
			}
			else
			{
				header("HTTP/1.1 503 Service Temporarily Unavailable");
			}
			
			print $this->lang->words['rssappoffline'];
			exit();
		}
		
		//-----------------------------------------
		// Then output
		//-----------------------------------------
		
		@header( 'Content-Type: text/xml; charset=' . IPS_DOC_CHAR_SET );
		@header( 'Expires: ' . gmstrftime( '%c', ( time() + 3600 ) ) . ' GMT' );
		@header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		@header( 'Pragma: public' );
		print $this->rss->rss_document;
		exit();
	}

	/**
	 * Category RSS feed
	 * 
	 * @param	int			$categoryId
	 * @return	@e void
	 */
	protected function _categoryRss( $categoryId )
	{
		//-----------------------------------------
		// Get category
		//-----------------------------------------

		$category	= $this->registry->gallery->helper('categories')->fetchCategory( $categoryId );
		
		if ( $category['category_id'] )
		{
			//-----------------------------------------
			// Build channel
			//-----------------------------------------

			$channelId	= $this->rss->createNewChannel( array(
															'title'			=> $category['category_name'],
															'link'			=> $this->registry->output->buildSEOUrl( "app=gallery&amp;category=" . $category['category_id'], 'publicNoSession', $category['category_name_seo'], 'viewcategory' ),
			 												'description'	=> sprintf( $this->lang->words['rss_feed_description'], $category['category_name'] ),
			 												'pubDate'		=> $this->rss->formatDate( time() ),
			 												'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
			 												'generator'		=> 'IP.Gallery'
			 										)		);
			
			//-----------------------------------------
			// Get last 25 images and add to channel
			//-----------------------------------------

			$categoryIds	= array_merge( array( $categoryId ), $this->registry->gallery->helper('categories')->getChildren( $categoryId ) );

			$recents	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'categoryId' => $categoryIds, 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			
			foreach( $recents as $id => $image )
			{
				$this->rss->addItemToChannel( $channelId , array(
																'title'				=> $image['image_caption'],
																'link'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ),
																'description'		=> $this->registry->gallery->helper('image')->makeImageLink( $image, array( 'type' => 'small', 'link-type' => 'page' ) ) . "<br />" . IPSText::getTextClass('bbcode')->preDisplayParse( $image['image_description'] ),
																'pubDate'			=> $this->rss->formatDate( $image['image_date'] ),
																'guid'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ) 
											)					);
			}
		}
	}
	
	/**
	 * Album RSS feed
	 * 
	 * @param	int			$albumId
	 * @return	@e void
	 */
	protected function _albumRss( $albumId )
	{
		//-----------------------------------------
		// Get album
		//-----------------------------------------

		$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		if ( $album['album_id'] )
		{
			//-----------------------------------------
			// Build channel
			//-----------------------------------------

			$channelId	= $this->rss->createNewChannel( array(
															'title'			=> $album['album_name'],
															'link'			=> $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $album['album_id'], 'publicNoSession', $album['album_name_seo'], 'viewalbum' ),
			 												'description'	=> sprintf( $this->lang->words['rss_feed_description'], $album['album_name'] ),
			 												'pubDate'		=> $this->rss->formatDate( time() ),
			 												'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
			 												'generator'		=> 'IP.Gallery'
			 										)		);
			
			//-----------------------------------------
			// Get last 25 images and add to channel
			//-----------------------------------------

			$recents	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array( 'albumId' => $album['album_id'], 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			
			foreach( $recents as $id => $image )
			{
				$this->rss->addItemToChannel( $channelId , array(
																'title'				=> $image['image_caption'],
																'link'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ),
																'description'		=> $this->registry->gallery->helper('image')->makeImageLink( $image, array( 'type' => 'small', 'link-type' => 'page' ) ) . "<br />" . IPSText::getTextClass('bbcode')->preDisplayParse( $image['image_description'] ),
																'pubDate'			=> $this->rss->formatDate( $image['image_date'] ),
																'guid'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ) 
											)					);
			}
		}
	}
}