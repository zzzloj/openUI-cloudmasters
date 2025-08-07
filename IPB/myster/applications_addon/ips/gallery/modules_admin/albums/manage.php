<?php
/**
 * @file		manage.php 	Gallery albums management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2013-01-08 20:50:54 -0500 (Tue, 08 Jan 2013) $
 * @version		v5.0.5
 * $Revision: 11798 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_albums_manage
 * @brief		Gallery albums management
 */
class admin_gallery_albums_manage extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		string
	 */
	public $form_code		= '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		string
	 */
	public $form_code_js	= '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get skin templates
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate( 'cp_skin_gallery_albums' );
		
		//-----------------------------------------
		// Get language files
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'admin_gallery', 'public_gallery' ), 'gallery' );
		
		//-----------------------------------------
		// Set URL bits
		//-----------------------------------------

		$this->html->form_code		= $this->form_code		= 'module=albums&amp;section=manage&amp;';
		$this->html->form_code_js	= $this->form_code_js	= 'module=albums&section=manage&';

		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'defaults':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->configureDefaults();
			break;

			case 'saveDefaults':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->saveDefaults();
			break;

			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_add' );
				$this->albumForm('add');
			break;
			
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_add' );
				$this->albumSave('add');
			break;
			
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->albumForm('edit');
			break;

			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->albumSave('edit');
			break;
			
			case 'deleteAlbum':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_delete' );
				$this->albumDelete();
			break;
			
			case 'emptyAlbum':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_empty' );
				$this->emptyAlbum();
			break;
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->indexScreen();
			break;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Configure the album defaults
	 *
	 * @return	@e void
	 */
	public function configureDefaults()
	{
		//-----------------------------------------
		// Get form fields (we won't use them all)
		//-----------------------------------------

		$defaults	= $this->setAlbumDefaults();
		$form		= $this->_buildFormFields( $defaults );

		$form['album_type_edit']		= $defaults['album_type_edit'] ? 'checked="checked"' : '';
		$form['album_sort_edit']		= $defaults['album_sort_edit'] ? 'checked="checked"' : '';
		$form['album_watermark_edit']	= $defaults['album_watermark_edit'] ? 'checked="checked"' : '';
		$form['album_comments_edit']	= $defaults['album_comments_edit'] ? 'checked="checked"' : '';
		$form['album_ratings_edit']		= $defaults['album_ratings_edit'] ? 'checked="checked"' : '';

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html	.= $this->html->defaults( $form );
	}

	/**
	 * Save the album defaults
	 *
	 * @return	@e void
	 */
	public function saveDefaults()
	{
		$defaults	= array(
							'album_type'			=> $this->request['album_type'],
							'album_watermark'		=> $this->request['album_watermark'],
							'album_allow_comments'	=> $this->request['album_allow_comments'],
							'album_allow_rating'	=> $this->request['album_allow_rating'],
							'album_sort_options'	=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
							'album_type_edit'		=> intval($this->request['album_type_edit']),
							'album_sort_edit'		=> intval($this->request['album_sort_edit']),
							'album_watermark_edit'	=> intval($this->request['album_watermark_edit']),
							'album_comments_edit'	=> intval($this->request['album_comments_edit']),
							'album_ratings_edit'	=> intval($this->request['album_ratings_edit']),
							);

		$this->cache->setCache( 'gallery_album_defaults', $defaults, array( 'array' => 1 ) );

		$this->registry->output->setMessage( $this->lang->words['defaults_saved_alb'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=defaults' );
	}

	/**
	 * Set album defaults and return.  Used when recached in the ACP or if the album defaults cache is empty/missing.
	 *
	 * @return	@e array
	 */
	public function setAlbumDefaults()
	{
		$defaults	= $this->cache->getCache('gallery_album_defaults');

		if( !is_array($defaults) OR !count($defaults) )
		{
			$defaults	= array(
								'album_type'			=> 1,
								'album_watermark'		=> 1,
								'album_allow_comments'	=> 1,
								'album_allow_rating'	=> 1,
								'album_sort_options'	=> serialize( array( 'key' => 'image_date', 'dir' => 'DESC' ) ),
								'album_type_edit'		=> 1,
								'album_sort_edit'		=> 1,
								'album_watermark_edit'	=> 1,
								'album_comments_edit'	=> 1,
								'album_ratings_edit'	=> 1,
								);
		}

		$this->cache->setCache( 'gallery_album_defaults', $defaults, array( 'array' => 1 ) );

		return $defaults;
	}

	/**
	 * Displays the index screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$start		= intval( $this->request['st'] );
		$perPage	= 50;

		//-----------------------------------------
		// Get albums
		//-----------------------------------------

		$albums	= $this->html->ajaxAlbums( $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'limit' => $perPage, 'offset' => $start, 'sortKey' => 'name', 'sortOrder' => 'asc', 'getTotalCount' => true ) ) );

		//-----------------------------------------
		// Pagination
		//-----------------------------------------

		$pages	= $this->registry->output->generatePagination( array(
																	'totalItems'		=> $this->registry->gallery->helper('albums')->getCount(),
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . $this->form_code
															)		);

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html	.= $this->html->albums( $albums, $pages );
	}	
	
	/**
	 * Empty an album
	 *
	 * @return	@e void
	 */
	public function emptyAlbum()
	{
		//-----------------------------------------
		// Fetch the images to delete
		//-----------------------------------------

		$images	= $this->registry->gallery->helper('image')->fetchImages( null, array( 'albumId' => intval( $this->request['albumId'] ) ) );

		//-----------------------------------------
		// Delete the images
		//-----------------------------------------

		if ( count( $images ) )
		{
			$this->registry->gallery->helper('moderate')->deleteImages( array_keys( $images ) );
		}
		
		//-----------------------------------------
		// Bounce back to index
		//-----------------------------------------

		$this->registry->output->setMessage( $this->lang->words['albums_empty_done'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Deletes an album from the database
	 *
	 * @return	@e void
	 */
	public function albumDelete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId			= intval( $this->request['albumId'] );
		$move_to_album_id	= intval( $this->request['move_to_album_id'] );
		$move_to_cat_id		= intval( $this->request['move_to_category_id'] );
		$moveToAlbum		= array();
		$moveToCategory		= array();
		$doDelete			= intval( $this->request['doDelete'] );
		$album				= $this->registry->gallery->helper('albums')->fetchAlbum( $albumId );
	
		//-----------------------------------------
		// Are we moving the images?
		//-----------------------------------------

		if ( $move_to_album_id && $doDelete == 0 )
		{
			$moveToAlbum	= $this->registry->gallery->helper('albums')->fetchAlbum( $move_to_album_id );
		}

		if ( $move_to_cat_id && $doDelete == -1 )
		{
			$moveToCategory	= $this->registry->gallery->helper('categories')->fetchCategory( $move_to_cat_id );
		}
		
		//-----------------------------------------
		// Delete the album - caches rebuilt in API call
		//-----------------------------------------

		$result	= $this->registry->gallery->helper('moderate')->deleteAlbum( $albumId, ( $doDelete == 0 ) ? $moveToAlbum : 0, ( $doDelete == -1 ) ? $moveToCategory : 0 );
		
		//-----------------------------------------
		// Bounce back to index
		//-----------------------------------------

		$this->registry->output->setMessage( $this->lang->words['albums_removed_msg'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Build form fields. Abstracted as albumForm() and configureDefaults() both require similar code.
	 *
	 * @param	array 		Default data
	 * @return	@e array 	Form fields
	 */
	protected function _buildFormFields( $album )
	{
		//-----------------------------------------
		// Build some dropdown arrays
		//-----------------------------------------

		$dd_is_public	= array(
								array( 1, $this->lang->words['albums_create_public'] ),
								array( 2, $this->lang->words['album_owner_only'] ),
								array( 3, $this->lang->words['album_owner_friends_only'] )
								);

		$sort_options	= array(
								array( 'image_date'		, $this->lang->words['album_sort_idate'] ),
						    	array( 'image_views'	, $this->lang->words['album_sort_views'] ),
						    	array( 'image_comments'	, $this->lang->words['album_sort_comments'] ),
						    	array( 'image_rating'	, $this->lang->words['album_sort_rating'] ),
						    	array( 'image_caption'	, $this->lang->words['album_sort_name'] )
								);

		$order_options	= array(
								array( 'ASC' , $this->lang->words['album_sort_asc'] ),
								array( 'DESC', $this->lang->words['album_sort_desc'] )
								);

		$order		= ( IPSLib::isSerialized( $album['album_sort_options'] ) ) ? unserialize( $album['album_sort_options'] ) : array();

		//-----------------------------------------
		// Get categories, disable cats that don't accept albums
		//-----------------------------------------

		$categories		= $this->registry->gallery->helper('categories')->catJumpList( true );
		$acceptAlbums	= $this->registry->gallery->helper('categories')->fetchAlbumCategories();

		foreach( $categories as $_key => $element )
		{
			if( !in_array( $element[0], $acceptAlbums ) )
			{
				$categories[ $_key ]['disabled']	= true;
			}
		}
		
		//-----------------------------------------
		// Start building form elements
		//-----------------------------------------

		$form								= array();
		$form['album_name']					= $this->registry->output->formInput( 'album_name', ( ! empty( $this->request['album_name'] ) ) ? $this->request['album_name'] : $album['album_name'] );
		$form['album_description']			= $this->registry->output->formTextarea( 'album_description', $this->request['album_description'] ? $this->request['album_description'] : IPSText::br2nl( $album['album_description'] ) );
		$form['album_category_id']			= $this->registry->output->formDropdown( 'album_category_id', $categories, ( ! empty( $this->request['album_category_id'] ) ) ? $this->request['album_category_id'] : $album['album_category_id'] );
		$form['album_type']					= $this->registry->output->formDropdown( 'album_type', $dd_is_public, ( ! empty( $this->request['album_type'] ) ) ? $this->request['album_type'] : $album['album_type'] ); 
		$form['album_owner_id__name']		= $this->registry->output->formInput( 'album_owner_id__name', $owner['members_display_name'], 'album_owner_autocomplete' );
		$form['album_sort_options__key']	= $this->registry->output->formDropdown( 'album_sort_options__key', $sort_options, ( ! empty( $this->request['album_sort_options__key'] ) ) ? $this->request['album_sort_options__key'] : $order['key'] ); 
		$form['album_sort_options__dir']	= $this->registry->output->formDropdown( 'album_sort_options__dir', $order_options, ( ! empty( $this->request['album_sort_options__dir'] ) ) ? $this->request['album_sort_options__dir'] : $order['dir'] ); 
		$form['album_allow_comments']		= $this->registry->output->formYesNo( 'album_allow_comments'  , ( ! empty( $this->request['album_allow_comments'] ) ) ? $this->request['album_allow_comments'] : $album['album_allow_comments'] );
		$form['album_allow_rating']			= $this->registry->output->formYesNo( 'album_allow_rating'  , ( ! empty( $this->request['album_allow_rating'] ) ) ? $this->request['album_allow_rating'] : $album['album_allow_rating'] );
		$form['album_watermark']			= $this->registry->output->formYesNo( 'album_watermark', empty($this->request['album_watermark']) ? $album['album_watermark'] : $this->request['album_watermark'] );

		//-----------------------------------------
		// Build attach-to-forum options
		//-----------------------------------------

		$this->registry->class_forums->strip_invisible	= true;
		$this->registry->class_forums->forumsInit();

		$forums	= array( 0 => array( 0, $this->lang->words['albums_parent_none'] ) );
		
		if ( is_array( $this->registry->class_forums->forum_cache['root'] ) and count( $this->registry->class_forums->forum_cache['root'] ) )
		{
			foreach( $this->registry->class_forums->forum_cache['root'] as $id => $data )
			{
				$catName = $data['name'];
				
				if ( is_array( $this->registry->class_forums->forum_cache[ $id ] ) and count( $this->registry->class_forums->forum_cache[ $id ] ) )
				{
					foreach( $this->registry->class_forums->forum_cache[ $id ] as $_id => $_data )
					{
						$forums[]	= array( $_id, '[' . $catName . '] ' . $_data['name'] );
					}
				}
			}
		}
		
		$form['album_after_forum_id']	= $this->registry->output->formDropdown( 'album_after_forum_id', $forums, ( ! empty( $this->request['album_after_forum_id'] ) ) ? $this->request['album_after_forum_id'] : $album['album_after_forum_id'] ); 

		//-----------------------------------------
		// Return the form fields
		//-----------------------------------------

		return $form;
	}
	
	/**
	 * Displays the add|edit album form
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function albumForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['albumId'] );

		//-----------------------------------------
		// Grab data
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
			$owner		= IPSMember::load( $album['album_owner_id'] );
		}
		else
		{
			$album		= array_merge( array(
								'album_category_id'		=> $this->registry->gallery->helper('albums')->getMembersAlbumId(),
								'album_owner_id'		=> $this->memberData['member_id'],
								), $this->setAlbumDefaults() );
			$owner		= $this->memberData;
		}

		//-----------------------------------------
		// Build form fields
		//-----------------------------------------

		$form				= $this->_buildFormFields( $album );
		$form['button']		= ( $type == 'edit' ) ? $this->lang->words['albums_link_edit'] : $this->lang->words['albums_add_button'];
		$form['formcode']	= ( $type == 'edit' ) ? 'doedit' : 'doadd';
		$form['title']		= ( $type == 'edit' ) ? sprintf( $this->lang->words['cats_edit_page_title'], $album['album_name'] ) : $this->lang->words['cats_add_page_title'];

		//-----------------------------------------
		// Pass to form
		//-----------------------------------------

		$this->registry->output->html .= $this->html->albumForm( $album, $form );		
	}	
	
	/**
	 * Saves the album
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function albumSave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albumId	= intval( $this->request['albumId'] );
		$album		= array();
		$owner		= array();
		$ownerName	= trim( $this->request['album_owner_id__name'] );
		
		//-----------------------------------------
		// Fetch the album
		//-----------------------------------------

		if( $type == 'edit' )
		{
			$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		}
		
		//-----------------------------------------
		// Build save array
		//-----------------------------------------

		$save	= array(
						'album_name'				=> trim( $this->request['album_name'] ),
						'album_description'			=> trim( $this->request['album_description'] ),
						'album_category_id'			=> intval( $this->request['album_category_id'] ),
						'album_type'				=> intval( $this->request['album_type'] ),
						'album_allow_comments'		=> intval( $this->request['album_allow_comments'] ),
						'album_allow_rating'		=> intval( $this->request['album_allow_rating'] ),
						'album_sort_options'		=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
						'album_after_forum_id'		=> intval( $this->request['album_after_forum_id'] ),
						'album_watermark'			=> intval( $this->request['album_watermark'] ),
						'album_position'			=> IPS_UNIX_TIME_NOW,	// Not used at this time
						);

		//-----------------------------------------
		// Verify we have a category and that it accepts albums
		//-----------------------------------------

		if( !$save['album_category_id'] OR !in_array( $save['album_category_id'], $this->registry->gallery->helper('categories')->fetchAlbumCategories() ) )
		{
			$this->registry->output->global_error	= $this->lang->words['error_no_parent_album'];
			return $this->albumForm( $type );
		}

		//-----------------------------------------
		// Check owner
		//-----------------------------------------

		$owner	= ( $type == 'edit' && ! $ownerName ) ? IPSMember::load( $album['album_owner_id'], 'all' ) : IPSMember::load( $ownerName, 'all', 'displayname' );
			
		if ( $owner['member_id'] )
		{
			$save['album_owner_id']	= $owner['member_id'];
		}
		else
		{
			$this->registry->output->global_error	= $this->lang->words['error_no_user'];
			return $this->albumForm( $type );
		}

		//-----------------------------------------
		// Only public albums outside of member gallery
		//-----------------------------------------

		if( $save['album_category_id'] != $this->settings['gallery_members_album'] )
		{
			$save['album_type']	= 1;
		}

		//-----------------------------------------
		// Save the album
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			if( ! $album['album_id'] )
			{
				$this->registry->output->showError( $this->lang->words['albums_edit_noid'], 1172 );
			}

			$this->registry->gallery->helper('albums')->save( array( $albumId => $save ) );
		}
		else
		{
			try
			{
				$newAlbum	= $this->registry->gallery->helper('moderate')->createAlbum( $save );
				$albumId	= $newAlbum['album_id'];
			}
			catch( Exception $e )
			{
				$this->registry->output->showError( $this->lang->words['exception_' . $e->getMessage() ], '1172x1' );
			}
		}
	
		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->cacheAttachToForum();
		
		//-----------------------------------------
		// Bounce back
		//-----------------------------------------

		$this->registry->output->setMessage( $this->lang->words['albums_edit_msg'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Recache album id > forum id stuff
	 * 
	 * @return	@e void
	 */
	public function cacheAttachToForum()
	{
		//-----------------------------------------
		// Get our Gallery object
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cache	= array();
		
		//-----------------------------------------
		// Get albums
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'album_id, album_after_forum_id',
								'from'		=> 'gallery_albums',
								'where'		=> 'album_after_forum_id > 0'
						)		);
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$cache[ $row['album_after_forum_id'] ]['albums'][]	= $row['album_id'];
		}

		//-----------------------------------------
		// Get categories
		//-----------------------------------------

		foreach( $this->registry->gallery->helper('categories')->fetchCategories() as $id => $category )
		{
			if( $category['category_after_forum_id'] )
			{
				$cache[ $category['category_after_forum_id'] ]['categories'][]	= $id;
			}
		}

		//-----------------------------------------
		// Update cache
		//-----------------------------------------

		$this->cache->setCache( 'gallery_fattach', $cache, array( 'array' => 1, 'donow' => 0 ) );
	}
}