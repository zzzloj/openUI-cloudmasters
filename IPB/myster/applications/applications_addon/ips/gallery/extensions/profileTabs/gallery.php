<?php
/**
 * @file		gallery.php 	Profile tab plugin for Gallery
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-07-24 14:40:56 -0400 (Tue, 24 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11118 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_gallery extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	@e string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// Can we use gallery?
		//-----------------------------------------

		if( ! $this->memberData['g_gallery_use'] )
		{
			return $this->lang->words['err_no_posts_to_show'];
		}
		
		//-----------------------------------------
		// Load language file
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		//-----------------------------------------
		// Get gallery object
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Get 5 of our albums
		//-----------------------------------------

		$albums		= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																							'getTotalCount'		=> true,
																							'album_owner_id'	=> $member['member_id'],
																							'isViewable'		=> true,
																							'sortKey'			=> 'date',
																							'sortOrder'			=> 'desc',
																							'limit'				=> 5,
																							'checkForMore'		=> true,
																							'parseAlbumOwner'	=> true,
																							'offset'			=> 0
																						)		);
		
		$hasMore	= $this->registry->gallery->helper('albums')->hasMore();

		//-----------------------------------------
		// Extract and load latest images
		//-----------------------------------------

		$albums		= $this->registry->gallery->helper('albums')->extractLatestImages( $albums );

		//-----------------------------------------
		// Get 30 of our images
		//-----------------------------------------

		$images		= $this->registry->gallery->helper('image')->fetchMembersImages( $member['member_id'], array(
																												'sortKey'			=> 'date',
																												'sortOrder'			=> 'desc',
																												'getTags'			=> true,
																												'parseImageOwner'	=> true,
																												'limit'				=> 30
																												)
																					);

		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------

		return $this->registry->getClass('output')->getTemplate('gallery_external')->profileBlock( $member, $albums, $images, $hasMore );
	}
}