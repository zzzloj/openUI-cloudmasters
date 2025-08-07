<?php
/**
 * @file		albumSelector.php 	AJAX handler for the album selector
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-06-27 17:53:27 -0400 (Wed, 27 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10996 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_albumSelector extends ipsAjaxCommand
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
			default:
			case 'albumSelector':
				$this->_albumSelectorSplash();
			break;

			case 'albumSelectorPane':
				$this->_albumSelectorPane();
			break;

			case 'select':
				$this->_select();
			break;
		}
	}
	
	/**
	 * Item has been selected
	 *
	 * @return	@e void
	 */
	public function _select()
	{
		//-----------------------------------------
		// Selecting an album?
		//-----------------------------------------

		$albumId	= intval( $this->request['album_id'] );
		
		if ( $albumId )
		{
			$album					= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
			$album['status']		= 'ok';
			$album['showWatermark']	= $this->registry->gallery->helper('albums')->canWatermark( $albumId ) ? 1 : 0;
			
			//-----------------------------------------
			// Caching this album
			//-----------------------------------------

			$cache	= explode( ',', ipsMember::getFromMemberCache( $this->memberData, 'gallery_recentSelects' ) );
			
			array_unshift( $cache, $albumId );
			
			$cache	= array_slice( array_unique( $cache ), 0, 25 );
			
			ipsMember::setToMemberCache( $this->memberData, array( 'gallery_recentSelects' => implode(',', $cache ) ) );
			
			return $this->returnJsonArray( $album );
		}
		else
		{
			return $this->returnJsonArray( array( 'status' => 'ok', 'album_id' => 0, 'album_name' => $this->lang->words['as_root'] ) );
		}
	}
	
	/**
	 * Album pane
	 *
	 * @param	array 		Inline params
	 * @return	@e void
	 */
	public function _albumSelectorPane( $inline=array() )
	{
		//-----------------------------------------
		// Remap inline params
		//-----------------------------------------

		if ( count( $inline ) )
		{
			foreach( $inline as $k => $v )
			{
				$this->request[ $k ] = $v;
			}
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albums		= array();
		$type		= trim( $this->request['type'] );
		$albums		= trim( $this->request['albums'] );
		$albumId	= intval( $this->request['album_id'] );
		$memberId	= intval( $this->request['member_id'] );
		$moderate	= intval( $this->request['moderate'] );
		$filters	= array();
		$albumData	= array();
		$modAction	= '';
		
		//-----------------------------------------
		// Determine filters
		//-----------------------------------------

		switch( $type )
		{
			default:
			case 'upload':
			case 'moveImage':
			case 'moveImages':
				$filters['isUploadable']	= 1;
			break;

			case 'edit':
			case 'editAlbum':
				$filters['isCreatable']		= 1;
				$filters['skip']			= array( $albumId );
			break;

			case 'createMembersAlbum':
			case 'createAlbum':
			case 'create':
				$filters['isCreatable']		= 1;
			break;
		}
	
		//-----------------------------------------
		// Ignore permissions in the ACP
		//-----------------------------------------

		if ( IN_ACP )
		{
			$filters['bypassPermissionChecks']	= true;
		}
		
		//-----------------------------------------
		// Load our album if necessary
		//-----------------------------------------

		if ( $albumId )
		{
			$albumData	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		}
		
		//-----------------------------------------
		// Are we moderating?
		//-----------------------------------------

		if( $moderate )
		{
			$memberId	= $memberId ? $memberId : $albumData['album_owner_id'];
		}

		if ( $moderate || IN_ACP )
		{
			$filters['moderatingData']	= array(
												'action'	=> $type,
												'owner_id'	=> $memberId ? $memberId : $albumData['album_owner_id'],
												'moderator'	=> $this->memberData,
												'album_id'	=> $albumData['album_id']
												);
		}
		
		//-----------------------------------------
		// Fetch the albums
		//-----------------------------------------

		if ( $albums == 'recent' )
		{
			$albumIds	= explode( ',', ipsMember::getFromMemberCache( $this->memberData, 'gallery_recentSelects' ) );
			$albums		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'album_id' => $albumIds, 'sortKey' => 'name', 'sortOrder' => 'asc' ) ) );
		}
		else if ( $albums == 'search' )
		{
			//-----------------------------------------
			// Clean up some of the request vars
			//-----------------------------------------

			$searchText		= trim( $this->request['searchText'] );
			$searchType		= trim( $this->request['searchType'] );
			$searchMatch	= trim( $this->request['searchMatch'] );
			$searchDir		= trim( $this->request['searchDir'] );
			$searchSort		= trim( $this->request['searchSort'] );
			
			//-----------------------------------------
			// Set some filters
			//-----------------------------------------

			$filters['sortOrder']	= $searchDir;
			$filters['sortKey']		= $searchSort;
			$filters['limit']		= 200;

			//-----------------------------------------
			// Got something to search for?
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
			// Get search results
			//-----------------------------------------
			
			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( $filters );
		}
		else if ( $albums == 'othermember' OR $moderate )
		{
			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'sortKey' => 'date', 'sortOrder' => 'desc', 'album_owner_id' => $memberId ) ) );
		}
		else
		{
			if ( in_array( $type, array( 'upload', 'createMembersAlbum', 'createAlbum', 'create' ) ) )
			{
				$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'sortKey' => 'date', 'sortOrder' => 'desc', 'album_owner_id' => $this->memberData['member_id'] ) ) );
			}
		}

		//-----------------------------------------
		// Get the HTML
		//-----------------------------------------

		$html	= $this->registry->output->getTemplate('gallery_albums')->albumSelectorPanel( $albums, $filters, $albumData );
		
		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------

		if ( count( $inline) )
		{
			return $html;
		}
		
		$this->returnHtml( $html );
	}
	
	/**
	 * Album selector splash page.  Defaults to 'Your Albums', so load all of our albums please.
	 *
	 * @return	@e void
	 */
	public function _albumSelectorSplash()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['album_id'] );
		$albumData	= array();
		
		//-----------------------------------------
		// Have an album id to load?
		//-----------------------------------------

		if ( $albumId )
		{
			$albumData	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		}

		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------

		$this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->albumSelector( $this->_albumSelectorPane( array( 'albums' => 'member' ) ), $albumData ) );
	}
}