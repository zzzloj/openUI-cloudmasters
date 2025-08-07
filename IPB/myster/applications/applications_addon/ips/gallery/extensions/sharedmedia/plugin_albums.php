<?php
/**
 * @file		plugin_albums.php 	Shared media plugin: gallery albums
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		3/10/2011
 * $LastChangedDate: 2012-12-18 19:08:30 -0500 (Tue, 18 Dec 2012) $
 * @version		v5.0.5
 * $Revision: 11733 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_gallery_albums
 * @brief		Provide ability to share gallery albums via editor
 */
class plugin_gallery_albums
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTab()
	{
		if( $this->memberData['member_id'] AND $this->registry->gallery->helper('albums')->canCreate() )
		{
			return $this->lang->words['sharedmedia_galalbums'];
		}
	}
	
	/**
	 * Return the HTML to display the tab
	 *
	 * @return	@e string
	 */
	public function showTab( $string )
	{
		//-----------------------------------------
		// Are we a member?
		//-----------------------------------------

		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		//-----------------------------------------
		// INIT vars
		//-----------------------------------------
		
		$st		= intval($this->request['st']);
		$each	= 30;
		$rows	= array();

		//-----------------------------------------
		// Get albums and count
		//-----------------------------------------

		$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByOwner( $this->memberData['member_id'], array(
																														'sortKey'				=> 'album_last_img_date',
																														'sortOrder'				=> 'desc',
																														'limit'					=> $each,
																														'offset'				=> $st,
																														'isViewable'			=> true,
																														'getTotalCount'			=> true,
																														'albumNameContains'		=> $string,
																														'getLatestCoverImage'	=> true,
																														)
																				);
		$count	= $this->registry->gallery->helper('albums')->getCount();

		//-----------------------------------------
		// Get pages
		//-----------------------------------------

		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count,
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'seoTitle'			=> '',
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'ajaxLoad'			=> 'mymedia_content',
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=gallery&amp;tabplugin=albums&amp;search=" . urlencode($string) )	);

		//-----------------------------------------
		// Format response
		//-----------------------------------------

		foreach( $albums as $id => $r )
		{
			$rows[]	= array(
							'image'		=> $this->registry->gallery->helper('image')->makeImageTag( $r, array( 'type' => 'thumb', 'coverImg' => true, 'link-type' => 'src' ) ),
							'width'		=> 0,
							'height'	=> 0,
							'title'		=> IPSText::truncate( $r['album_name'], 25 ),
							'desc'		=> IPSText::truncate( strip_tags( IPSText::stripAttachTag( IPSText::getTextClass('bbcode')->stripAllTags( $r['album_description'] ) ), '<br>' ), 100 ),
							'insert'	=> "gallery:albums:" . $r['album_id'],
							);
		}
		
		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'gallery', 'albums' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$albumId		Album ID to show
	 * @return	@e string
	 */
	public function getOutput( $albumId=0 )
	{
		$albumId	= intval($albumId);
		
		if( !$albumId )
		{
			return '';
		}

		$album			= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_id' => $albumId, 'getLatestCoverImage' => true, 'skipImageParsing' => true ) );
		$album			= array_pop( $album );
		$album['thumb']	= $this->registry->gallery->helper('image')->makeImageTag( $album, array( 'type' => 'thumb', 'coverImg' => true, 'thumbClass' => 'sharedmedia_screenshot' ) );
		$album['thumb']	= $this->registry->gallery->inlineResize( $album['thumb'], 'thumb_medium', 'thumb_medium' );

		return $this->registry->output->getTemplate('gallery_global')->bbCodeAlbum( $album );
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$albumId	Album ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $albumId )
	{
		$albumId	= intval($albumId);
		
		if( !$albumId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		if( $this->memberData['member_id'] AND $album['album_owner_id'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}