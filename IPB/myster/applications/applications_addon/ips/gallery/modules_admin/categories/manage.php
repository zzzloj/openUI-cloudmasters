<?php
/**
 * @file		manage.php 	Gallery categories management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-05-22 11:04:13 -0400 (Tue, 22 May 2012) $
 * @version		v5.0.5
 * $Revision: 10780 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_categories_manage
 * @brief		Gallery categories management
 */
class admin_gallery_categories_manage extends ipsCommand
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
		// Init
		//-----------------------------------------

		$this->html	= $this->registry->output->loadTemplate( 'cp_skin_gallery_categories' );

		$this->lang->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		$this->html->form_code		= $this->form_code		= 'module=categories&amp;section=manage&amp;';
		$this->html->form_code_js	= $this->form_code_js	= 'module=categories&section=manage&';

		//-----------------------------------------
		// What to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{		
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_manage' );
				$this->categoryForm('add');
			break;
			
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_manage' );
				$this->categorySave('add');
			break;
			
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_manage' );
				$this->categoryForm('edit');
			break;

			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_manage' );
				$this->categorySave('edit');
			break;

			case 'resynch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_manage' );
				$this->categoryResync();
			break;

			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_delete' );
				$this->categoryDelete();
			break;
			
			case 'empty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_empty' );
				$this->categoryEmpty();
			break;

			case 'modform':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_moderators' );
				$this->moderatorForm('add');
			break;

			case 'editmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_moderators' );
				$this->moderatorForm('edit');
			break;	

			case 'domod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_moderators' );
				$this->moderatorSave('add');
			break;

			case 'doeditmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_moderators' );
				$this->moderatorSave('edit');
			break;

			case 'delmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'category_moderators' );
				$this->moderatorDelete();
			break;
			
			default:
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
	 * Displays the index screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$parent		= intval( $this->request['parent'] );
		$categories	= '';
		
		//-----------------------------------------
		// Navigation
		//-----------------------------------------

		if ( $parent )
		{
			$nav	= $this->registry->gallery->helper('categories')->getNav( $parent, $this->form_code . '&amp;parent=', true );
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				foreach( $nav as $_nav )
				{
					$this->registry->output->extra_nav[] = $_nav;
				}
			}
		}

		//-----------------------------------------
		// Grab the categories
		//-----------------------------------------

		if( count( $this->registry->gallery->helper('categories')->cat_cache[ 0 ] ) )
		{
			$depth_guide	= ( $parent AND $this->registry->gallery->helper('categories')->fetchCategory( $parent ) ) ? $this->registry->gallery->helper('categories')->parent_lookup[ $parent ] : 0;
			
			foreach( $this->registry->gallery->helper('categories')->cat_cache[ $depth_guide ] as $id => $outer_data )
			{
				$tempHtml	= '';
				$modData	= '';
				
				//-----------------------------------------
				// Do we have any subcategories?
				//-----------------------------------------

				if ( is_array( $this->registry->gallery->helper('categories')->cat_cache[ $outer_data['category_id'] ] ) && count( $this->registry->gallery->helper('categories')->cat_cache[ $outer_data['category_id'] ] ) )
				{
					$tempHtml	= $this->html->subCategories( $this->registry->gallery->helper('categories')->cat_cache[ $outer_data['category_id'] ] );
				}
				
				//-----------------------------------------
				// Do we have any moderators?
				//-----------------------------------------

				if ( is_array( $this->registry->gallery->helper('categories')->cat_mods[ $outer_data['category_id'] ] ) && count( $this->registry->gallery->helper('categories')->cat_mods[ $outer_data['category_id'] ] ) )
				{
					$_mods	= array();
					
					foreach( $this->registry->gallery->helper('categories')->cat_mods[ $outer_data['category_id'] ] as $_mid => $data )
					{
						$data['_fullname']		= ( $data['mod_type'] == 'group' ) ? $this->lang->words['fc_group_prefix'] . ' ' . $data['mod_type_name'] : $data['mod_type_name'];
						$data['randId']			= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );
						$data['category_id']	= $outer_data['category_id'];
						
						$_mods[]				= $data;
					}
					
					if( count($_mods) )
					{
						$modData	= $this->html->renderModeratorEntry( $_mods );
					}
				}					

				//-----------------------------------------
				// Generate the HTML for this category
				//-----------------------------------------

				$categories .= $this->html->renderCategory( $tempHtml, $outer_data, $modData );
			}
		}

		//-----------------------------------------
		// Pass our row output to the main template
		//-----------------------------------------

		$this->registry->output->html .= $this->html->mainScreen( $categories );
	}	
	
	/**
	 * Empty a category
	 *
	 * @return	@e void
	 */
	public function categoryEmpty()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$category		= intval($this->request['category']);
		$_category		= $this->registry->gallery->helper('categories')->fetchCategory( $category );
		$doImages		= ( $_category['category_type'] == 2 ) ? 1 : 0;
		$doAlbums		= ( $_category['category_type'] == 1 ) ? 1 : 0;
		$albumsSoFar	= intval($this->request['albumsSoFar']);
		
		//-----------------------------------------
		// Get the image IDs for images in the category
		//-----------------------------------------

		if( $doImages )
		{
			$images  = $this->registry->gallery->helper('image')->fetchImages( null, array( 'categoryId' => $category, 'albumId' => 0 ) );

			//-----------------------------------------
			// Delete any images we found
			//-----------------------------------------

			if ( count( $images ) )
			{
				$this->registry->gallery->helper('moderate')->deleteImages( array_keys( $images ) );

				$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['gallery_adminlog_empty_images'], count($images), $_category['category_name'] ) );
			}
		}

		//-----------------------------------------
		// Are we deleting albums?
		//-----------------------------------------

		if( $doAlbums )
		{
			//-----------------------------------------
			// Grab up to 100 albums in this category
			//-----------------------------------------

			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_category_id' => $category, 'limit' => 100, 'checkForMore' => true ) );
			$more	= $this->registry->gallery->helper('albums')->hasMore();

			//-----------------------------------------
			// Delete those albums
			//-----------------------------------------

			if( count($albums) )
			{
				foreach( $albums as $albumId => $albumData )
				{
					$this->registry->gallery->helper('albums')->remove( $albumData );
				}

				$albumsSoFar	+= count($albums);
			}

			//-----------------------------------------
			// If we have more, redirect back to continue
			//-----------------------------------------

			if( $more )
			{
				$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code . 'do=empty&amp;category=' . $category . '&amp;albumsSoFar=' . $albumsSoFar, $this->lang->words['up_to_100_albums_deleted'], 2, false, true );
			}
			else
			{
				//-----------------------------------------
				// Rebuild caches, log, and return to index screen
				//-----------------------------------------

				$this->registry->gallery->helper('categories')->rebuildCategory( $category );

				if( $albumsSoFar )
				{
					$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['gallery_adminlog_empty_albums'], $albumsSoFar, $_category['category_name'] ) );
				}
				else
				{
					//-----------------------------------------
					// If any albums were deleted, this was already run
					//-----------------------------------------

					$this->registry->gallery->rebuildStatsCache();
				}

				$this->registry->output->setMessage( $this->lang->words['cats_empty_done'] );
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
			}
		}
		else
		{
			//-----------------------------------------
			// Rebuild caches, log, and return to index screen
			//-----------------------------------------

			$this->registry->gallery->helper('categories')->rebuildCategory( $category );
			$this->registry->gallery->rebuildStatsCache();

			$this->registry->output->setMessage( $this->lang->words['cats_empty_done'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}
	
	/**
	 * Deletes a category from the database
	 *
	 * @return	@e void
	 */
	public function categoryDelete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$category		= intval( $this->request['category'] );
		$_category		= $this->registry->gallery->helper('categories')->fetchCategory( $category );
		$moveTo			= intval( $this->request['move_to'] );
		$doDelete		= intval( $this->request['doDelete'] );
		$albumsSoFar	= intval($this->request['albumsSoFar']);
	
		//-----------------------------------------
		// Error checking for images/albums
		//-----------------------------------------

		if( $_category['category_type'] > 0 )
		{
			if( !$doDelete )
			{
				if( !$moveTo )
				{
					$this->registry->output->showError( $this->lang->words['cat_not_found_delete'], 117301.1 );
				}

				if( $moveTo == $category )
				{
					$this->registry->output->showError( $this->lang->words['cat_delete_move_itself'], 117301.2 );
				}

				$_moveTo	= $this->registry->gallery->helper('categories')->fetchCategory( $moveTo );

				if( !$_moveTo['category_id'] )
				{
					$this->registry->output->showError( $this->lang->words['cat_not_found_delete'], 117301.3 );
				}
			}
		}

		//-----------------------------------------
		// Error checking for subcategories
		//-----------------------------------------

		$_children	= $this->registry->gallery->helper('categories')->getChildren( $category );

		if( count( $_children ) )
		{
			$moveCatsTo	= intval($this->request['move_cats_to']);

			if( !$moveCatsTo )
			{
				$this->registry->output->showError( $this->lang->words['cat_not_found_delete'], 117301.4 );
			}

			if( $moveCatsTo == $category )
			{
				$this->registry->output->showError( $this->lang->words['cat_delete_move_itself'], 117301.5 );
			}

			$_moveCatsTo	= $this->registry->gallery->helper('categories')->fetchCategory( $moveCatsTo );

			if( !$_moveCatsTo['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['cat_not_found_delete'], 117301.6 );
			}

			//-----------------------------------------
			// Move children categories now so if albums
			// redirect back this is already done
			//-----------------------------------------

			$this->DB->update( 'gallery_categories', array( 'category_parent_id' => $moveCatsTo ), 'category_parent_id=' . $category );

			$this->registry->gallery->helper('categories')->rebuildCategory( $moveCatsTo );
		}

		//-----------------------------------------
		// Now, handle albums or images
		//-----------------------------------------

		if( $_category['category_type'] == 1 )
		{
			//-----------------------------------------
			// Grab up to 100 albums in this category
			//-----------------------------------------

			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_category_id' => $category, 'limit' => 100, 'checkForMore' => true ) );
			$more	= $this->registry->gallery->helper('albums')->hasMore();

			//-----------------------------------------
			// Handle those albums
			//-----------------------------------------

			if( count($albums) )
			{
				if( $doDelete )
				{
					foreach( $albums as $albumId => $albumData )
					{
						$this->registry->gallery->helper('albums')->remove( $albumData );
					}
				}
				else
				{
					$this->DB->update( 'gallery_albums', array( 'album_category_id' => $moveTo ), 'album_category_id=' . $category );
					$this->DB->update( 'gallery_images', array( 'image_category_id' => $moveTo ), 'image_category_id=' . $category );
				}

				$albumsSoFar	+= count($albums);
			}

			//-----------------------------------------
			// If we have more, redirect back to continue
			//-----------------------------------------

			if( $more )
			{
				$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code . 'do=delete&amp;category=' . $category . '&amp;move_to=' . $moveTo . '&amp;doDelete=' . $doDelete . '&amp;albumsSoFar=' . $albumsSoFar, $this->lang->words['up_to_100_albums_deleted'], 2, false, true );
			}
		}
		else if( $_category['category_type'] == 2 )
		{
			//-----------------------------------------
			// Delete or move images
			//-----------------------------------------

			if( $doDelete )
			{
				$images  = $this->registry->gallery->helper('image')->fetchImages( null, array( 'categoryId' => $category, 'albumId' => 0 ) );

				if ( count( $images ) )
				{
					$this->registry->gallery->helper('moderate')->deleteImages( array_keys( $images ) );
				}
			}
			else
			{
				$this->DB->update( 'gallery_images', array( 'image_category_id' => $moveTo ), 'image_category_id=' . $category );
			}
		}

		//-----------------------------------------
		// Delete the category itself
		//-----------------------------------------

		$this->DB->delete( 'gallery_categories', 'category_id=' . $category );

		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------

		if( $moveTo )
		{
			$this->registry->gallery->helper('categories')->rebuildCategory( $moveTo );
		}
		else
		{
			$this->registry->gallery->helper('categories')->rebuildCatCache();
		}

		$this->registry->gallery->rebuildStatsCache();
		$this->cache->rebuildCache( 'gallery_fattach', 'gallery' );

		//-----------------------------------------
		// Log, set message and redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['category_deleted_gall'], $_category['category_name'] ) );
		$this->registry->output->setMessage( sprintf( $this->lang->words['category_deleted_gall'], $_category['category_name'] ) );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Resynchronize category
	 *
	 * @return	@e void
	 */
	public function categoryResync()
	{
		$this->registry->gallery->helper('categories')->rebuildCategory( intval($this->request['category']) );

		$this->registry->output->setMessage( $this->lang->words['galcat_resynched_succ'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Displays the form to add or edit a category
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function categoryForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$form			= array();
		$rules			= array();

		$container_type	= array(
								array( 0, $this->lang->words['category_mode_none'] ),
								array( 1, $this->lang->words['category_mode_album'] ),
								array( 2, $this->lang->words['category_mode_img'] ),
								);

		$sort_options	= array(
								array( 'image_date'	, $this->lang->words['galcats_sort_idate'] ),
								array( 'image_views'	, $this->lang->words['galcats_sort_views'] ),
								array( 'image_comments', $this->lang->words['galcats_sort_comments'] ),
								array( 'image_rating'	, $this->lang->words['galcats_sort_rating'] ),
								array( 'image_caption' , $this->lang->words['galcats_sort_caption'] ),
								);

		$sort_options_a	= array(
								array( 'album_name' , $this->lang->words['galcats_sort_aname'] ),
								array( 'album_last_img_date'	, $this->lang->words['galcats_sort_adate'] ),
								array( 'album_count_imgs'	, $this->lang->words['galcats_sort_imgcount'] ),
								array( 'album_count_comments', $this->lang->words['galcats_sort_ccount'] ),
								array( 'album_rating_aggregate'	, $this->lang->words['galcats_sort_arate'] ),
								);

		$order_options	= array(
								array( 'ASC' , $this->lang->words['galcats_sort_asc'] ),
						    	array( 'DESC', $this->lang->words['galcats_sort_desc'] )
						    	);

		$wmOptions		= array(
								array( 0, $this->lang->words['wm_dont_watermark'] ),
								array( 1, $this->lang->words['wm_optional_watermark'] ),
								array( 2, $this->lang->words['wm_force_watermark'] ),
								);
		
		if ( $type == 'edit' )
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $this->request['category'] );

			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['cat_not_found_edit'], 117300.1 );
			}

			$order		= @unserialize( $category['category_sort_options'] );
			$rules		= @unserialize( $category['category_rules'] );
		}
		else
		{
			$category	= array( 'category_parent_id' => intval($this->request['p']), 'category_type' => 1, 'category_allow_comments' => 1, 'category_allow_rating' => 1, 'category_can_tag' => 1 );
			$order		= array( 'key' => 'image_date', 'dir' => 'DESC' );
		}
		
		//-----------------------------------------
		// Build form options
		//-----------------------------------------

		$form['_formType']					= ( $type == 'edit' ) ? 'doedit' : 'doadd';
		$form['_formTitle']					= ( $type == 'edit' ) ? sprintf( $this->lang->words['edit_category_title'], $category['category_name'] ) : $this->lang->words['add_category_title'];
		$form['category_name']				= $this->registry->output->formInput( 'category_name', ( ! empty( $this->request['category_name'] ) ) ? $this->request['category_name'] : $category['category_name'] );
		$form['category_description']		= $this->registry->output->formTextarea( 'category_description', $this->request['category_description'] ? $this->request['category_description'] : IPSText::br2nl( $category['category_description'] ) );
		$form['category_parent_id']			= $this->registry->output->formDropdown( 'category_parent_id', $this->registry->gallery->helper('categories')->catJumpList(), ( ! empty( $this->request['category_parent_id'] ) ) ? $this->request['category_parent_id'] : $category['category_parent_id'] );
		$form['category_type']				= $this->registry->output->formDropdown( 'category_type', $container_type, ( ! empty( $this->request['category_type'] ) ) ? $this->request['category_type'] : $category['category_type'] ); 
		
		$form['category_sort_options__key']	= $this->registry->output->formDropdown( 'category_sort_options__key', $sort_options, ( ! empty( $this->request['category_sort_options__key'] ) ) ? $this->request['category_sort_options__key'] : $order['key'] ); 
		$form['category_asort_options__key']	= $this->registry->output->formDropdown( 'category_asort_options__key', $sort_options_a, ( ! empty( $this->request['category_asort_options__key'] ) ) ? $this->request['category_asort_options__key'] : $order['key'] ); 
		$form['category_sort_options__dir']	= $this->registry->output->formDropdown( 'category_sort_options__dir', $order_options, ( ! empty( $this->request['category_sort_options__dir'] ) ) ? $this->request['category_sort_options__dir'] : $order['dir'] );

		$form['category_allow_comments']	= $this->registry->output->formYesNo( 'category_allow_comments', ( ! empty( $this->request['category_allow_comments'] ) )  ? $this->request['category_allow_comments']  : $category['category_allow_comments'] );
		$form['category_allow_rating']		= $this->registry->output->formYesNo( 'category_allow_rating'  , ( ! empty( $this->request['category_allow_rating'] ) ) ? $this->request['category_allow_rating'] : $category['category_allow_rating'] );
		$form['category_approve_img']		= $this->registry->output->formYesNo( 'category_approve_img'   , ( ! empty( $this->request['category_approve_img'] ) ) ? $this->request['category_approve_img'] : $category['category_approve_img'] );
		$form['category_approve_com']		= $this->registry->output->formYesNo( 'category_approve_com'   , ( ! empty( $this->request['category_approve_com'] ) ) ? $this->request['category_approve_com'] : $category['category_approve_com'] );

		$form['category_can_tag']			= $this->registry->output->formYesNo( 'category_can_tag'  , ( ! empty( $this->request['category_can_tag'] ) ) ? $this->request['category_can_tag'] : $category['category_can_tag'] );
		$form['category_preset_tags']		= $this->registry->output->formTextarea( "category_preset_tags", IPSText::br2nl( !empty($_POST['category_preset_tags']) ? $_POST['category_preset_tags'] : $category['category_preset_tags'] ) );

		$form['category_rules__title']		= $this->registry->output->formInput( 'category_rules__title', ( ! empty( $this->request['category_rules__title'] ) ) ? $this->request['category_rules__title'] : $rules['title'], '', 50 );
		$form['category_rules__desc']		= $this->registry->output->formTextarea( 'category_rules__desc', $this->request['category_rules__desc'] ? $this->request['category_rules__desc'] : IPSText::br2nl( $rules['text'] ), 55 );

		$form['category_watermark']			= $this->registry->output->formDropdown( 'category_watermark', $wmOptions, empty($this->request['category_watermark']) ? $category['category_watermark'] : $this->request['category_watermark'] );
		
		//-----------------------------------------
		// Build the 'attach to forum' option
		//-----------------------------------------

		$this->registry->class_forums->strip_invisible = true;
		$this->registry->class_forums->forumsInit();

		$forums = array( 0 => array( 0, $this->lang->words['albums_parent_none'] ) );
		
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
		
		$form['category_after_forum_id']	= $this->registry->output->formDropdown( 'category_after_forum_id', $forums, ( ! empty( $this->request['category_after_forum_id'] ) ) ? $this->request['category_after_forum_id'] : $category['category_after_forum_id'] ); 
		
		//-----------------------------------------
		// Grab the permissions class
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( $this->registry );

		$form['_permissions']				= $permissions->adminPermMatrix( 'categories', $category, 'gallery', '', false );
		
		//-----------------------------------------
		// Show the form
		//-----------------------------------------

		$this->registry->output->html .= $this->html->categoryForm( $category, $form );		
	}	
	
	/**
	 * Saves the new or edited category
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function categorySave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$category	= intval( $this->request['category'] );

		//-----------------------------------------
		// Build the save array
		//-----------------------------------------

		$save	= array(
						'category_name'				=> trim($this->request['category_name']),
						'category_name_seo'			=> IPSText::makeSeoTitle( trim($this->request['category_name']) ),
						'category_description'		=> trim( nl2br( IPSText::stripslashes( $_POST['category_description'] ) ) ),
						'category_parent_id'		=> intval($this->request['category_parent_id']),
						'category_type'				=> intval($this->request['category_type']),
						'category_allow_comments'	=> intval($this->request['category_allow_comments']),
						'category_allow_rating'		=> intval($this->request['category_allow_rating']),
						'category_approve_img'		=> intval($this->request['category_approve_img']),
						'category_approve_com'		=> intval($this->request['category_approve_com']),
						'category_can_tag'			=> intval($this->request['category_can_tag']),
						'category_preset_tags'		=> trim($this->request['category_preset_tags']),
						'category_watermark'		=> intval($this->request['category_watermark']),
						'category_sort_options'		=> serialize( array( 'key' => $this->request['category_type'] == 1 ? $this->request['category_asort_options__key'] : $this->request['category_sort_options__key'], 'dir' => $this->request['category_sort_options__dir'] ) ),
						'category_rules'			=> serialize( array( 'title' => trim($this->request['category_rules__title']), 'text' => trim( nl2br( IPSText::stripslashes( $_POST['category_rules__desc'] ) ) ) ) ),
						'category_after_forum_id'	=> intval($this->request['category_after_forum_id']),
						);

		//-----------------------------------------
		// Check for errors
		//-----------------------------------------

		if( !$save['category_name'] )
		{
			$this->registry->output->setMessage( $this->lang->words['galcat_error_noname'], true );
			
			return $this->categoryForm( $type );
		}

		if ( $type == 'edit' AND $this->request['category_parent_id'] AND $this->request['category_parent_id'] != $this->registry->gallery->helper('categories')->fetchCategory( $category, 'category_parent_id' ) )
		{
			$ids	= $this->registry->gallery->helper('categories')->getChildren( $category );
			$ids[]	= $category;
			
			if ( in_array( $this->request['category_parent_id'], $ids ) )
			{
				$this->registry->output->setMessage( $this->lang->words['galcat_error_invparent'], true );
				
				return $this->categoryForm( $type );
			}
		}

		//-----------------------------------------
		// Handle special members ablums cat
		//-----------------------------------------

		if( $type == 'edit' AND $category == $this->settings['gallery_members_album'] )
		{
			$save['category_type']		= 1;
			$save['category_parent_id']	= 0;

			IPSLib::updateSettings( array( 'gallery_memalbum_display' => $this->request['gallery_memalbum_display'] ) );
		}

		//-----------------------------------------
		// Save
		//-----------------------------------------

		if( $type == 'edit' )
		{
			$this->DB->update( 'gallery_categories', $save, "category_id=" . $category );

			$this->registry->output->setMessage( sprintf( $this->lang->words['galcat_category_edited'], $save['category_name'] ) );
			
			$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['galcat_category_edited'], $save['category_name'] ) );
		}
		else
		{
			foreach( $this->registry->gallery->helper('categories')->fetchCategories() as $_category )
			{
				if( $_category['category_position'] > $save['category_position'] )
				{
					$save['category_position']	= $_category['category_position'] + 1;
				}
			}

			$this->DB->insert( 'gallery_categories', $save );

			$category	= $this->DB->getInsertId();

			$this->registry->output->setMessage( sprintf( $this->lang->words['galcat_category_added'], $save['category_name'] ) );
			
			$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['galcat_category_added'], $save['category_name'] ) );
		}

		//-----------------------------------------
		// Permissions
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], $category, 'categories', 'gallery' );
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------

		$this->registry->gallery->helper('categories')->rebuildCatCache();
		$this->registry->gallery->rebuildStatsCache();
		$this->cache->rebuildCache( 'gallery_fattach', 'gallery' );

		if( $type == 'edit' )
		{
			$this->DB->update( 'gallery_images', array( 'image_parent_permission' => $this->registry->gallery->helper('categories')->fetchCategory( $category, 'perm_view' ) ), 'image_category_id=' . $category );
		}

		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Show the moderator form
	 *
	 * @param	string		$type		Action type [add|edit]
	 * @return	@e void [Outputs to screen]
	 */
	public function moderatorForm( $type='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$parent		= intval( $this->request['category'] );
		$categories	= '';
		$catlist	= $this->registry->gallery->helper('categories')->catJumpList( true );
		$form		= array();
		$mod_cats[]	= $parent;
		
		//-----------------------------------------
		// Navigation
		//-----------------------------------------

		if ( $parent )
		{
			$nav	= $this->registry->gallery->helper('categories')->getNav( $parent, $this->form_code . '&amp;parent=', true );
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				foreach( $nav as $_nav )
				{
					$this->registry->output->extra_nav[] = $_nav;
				}
			}
		}

		//-----------------------------------------
		// Add or edit
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$row	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_moderators', 'where' => 'mod_id=' . intval($this->request['modid']) ) );
			
			if( $row['mod_id'] )
			{
				$thiscats	= explode( ",", $row['mod_categories'] );
				
				if( count($thiscats) )
				{
					foreach( $thiscats as $k => $v )
					{
						$mod_cats[] = $v;
					}
				}
			}
			
			$form['_code']	= 'doeditmod';
		}
		else
		{
			$form['_code']	= 'domod';
		}

		//-----------------------------------------
		// Error checking
		//-----------------------------------------

		if( !$catlist )
		{
			$this->registry->output->showError( $this->lang->words['galcat_nocats_moderators'], 117300.2 );
		}
		
		//-----------------------------------------
		// Generate form data
		//-----------------------------------------

		$dropdown	= array( array( 'member', $this->lang->words['galmod_type_member'] ), array( 'group', $this->lang->words['galmod_type_group'] ) );
		
		$groups		= array();
		
		$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->DB->execute();
		
		while( $g = $this->DB->fetch() )
		{
			$groups[] = array( $g['g_id'], $g['g_title'] );
		}

		$form['mod_type']					= $this->registry->output->formDropdown( "mod_type", $dropdown, $this->request['mod_type'] ? $this->request['mod_type'] : $row['mod_type'] );		
		$form['_modgid']					= $this->registry->output->formDropdown( "_modgid", $groups, $this->request['mod_type_id'] ? $this->request['mod_type_id'] : ( $row['mod_type'] == 'group' ? $row['mod_type_id'] : 0 ) );
		$form['_modmid']					= $this->registry->output->formInput( "_modmid", IPSText::parseCleanValue( $this->request['mod_type_name'] ? $this->request['mod_type_name'] : ( $row['mod_type'] == 'member' ? $row['mod_type_name'] : '' ) ) );
		$form['mod_categories']				= $this->registry->output->formMultiDropdown( "mod_categories[]", $catlist, $this->request['mod_categories'] ? $this->request['mod_categories'] : $mod_cats, "8" );
		$form['mod_can_approve']			= $this->registry->output->formYesNo( "mod_can_approve", $this->request['mod_can_approve'] ? $this->request['mod_can_approve'] : $row['mod_can_approve'] );
		$form['mod_can_edit']				= $this->registry->output->formYesNo( "mod_can_edit", $this->request['mod_can_edit'] ? $this->request['mod_can_edit'] : $row['mod_can_edit'] );
		$form['mod_can_hide']				= $this->registry->output->formYesNo( "mod_can_hide", $this->request['mod_can_hide'] ? $this->request['mod_can_hide'] : $row['mod_can_hide'] );
		$form['mod_can_delete']				= $this->registry->output->formYesNo( "mod_can_delete", $this->request['mod_can_delete'] ? $this->request['mod_can_delete'] : $row['mod_can_delete'] );
		$form['mod_can_approve_comments']	= $this->registry->output->formYesNo( "mod_can_approve_comments", $this->request['mod_can_approve_comments'] ? $this->request['mod_can_approve_comments'] : $row['mod_can_approve_comments'] );
		$form['mod_can_edit_comments']		= $this->registry->output->formYesNo( "mod_can_edit_comments", $this->request['mod_can_edit_comments'] ? $this->request['mod_can_edit_comments'] : $row['mod_can_edit_comments'] );
		$form['mod_can_delete_comments']	= $this->registry->output->formYesNo( "mod_can_delete_comments", $this->request['mod_can_delete_comments'] ? $this->request['mod_can_delete_comments'] : $row['mod_can_delete_comments'] );
		$form['mod_can_move']				= $this->registry->output->formYesNo( "mod_can_move", $this->request['mod_can_move'] ? $this->request['mod_can_move'] : $row['mod_can_move'] );
		$form['mod_set_cover_image']		= $this->registry->output->formYesNo( "mod_set_cover_image", $this->request['mod_set_cover_image'] ? $this->request['mod_set_cover_image'] : $row['mod_set_cover_image'] );

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->moderatorForm( $form );
	}
	
	/**
	 * Save the moderator
	 *
	 * @param	string		$type		Action type [add|edit]
	 * @return	@e void [Outputs to screen]
	 */
	protected function moderatorSave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$moderator = array();
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------

		if( $type == 'edit' && !$this->request['modid'] )
		{
			$this->registry->output->showError( $this->lang->words['galmod_error_notfound'], 117300.3 );
		}
		
		if( $type == 'edit' && $this->request['modid'] )
		{
			$moderator	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_moderators', 'where' => "mod_id=" . intval($this->request['modid']) ) );
			
			if( !$moderator['mod_id'] )
			{
				$this->registry->output->showError( $this->lang->words['galmod_error_notfound'], 117300.4 );
			}
		}		
					
		if( count($this->request['mod_categories']) == 0 )
		{
			$this->registry->output->setError( $this->lang->words['galmod_nocatsfound'] );
			return $this->moderatorForm( $type );
		}
		
		if( $this->request['mod_type'] == 'group' && !$this->request['_modgid'] )
		{
			$this->registry->output->setMessage( $this->lang->words['galmod_nogroup'] );
			return $this->moderatorForm( $type );
		}			
		else if( $this->request['mod_type'] == 'member' && !$this->request['_modmid'] )
		{
			$this->registry->output->setMessage( $this->lang->words['galmod_nomember'] );
			return $this->moderatorForm( $type );
		}
		
		if( $this->request['mod_type'] == 'group' )
		{
			if( !$this->caches['group_cache'][ $this->request['_modgid'] ]['g_id'] )
			{
				$this->registry->output->setMessage( $this->lang->words['galmod_nogroup'] );
				return $this->moderatorForm( $type );
			}

			$_modid		= intval($this->request['_modgid']);
			$_modname	= $this->caches['group_cache'][ $this->request['_modgid'] ]['g_title'];
		}
		else
		{
			$member = IPSMember::load( $this->request['_modmid'], 'core', 'displayname' );
			
			if( !$member['member_id'] )
			{
				$this->registry->output->setMessage( $this->lang->words['galmod_nomember'] );
				return $this->moderatorForm( $type );
			}

			$_modid		= $member['member_id'];
			$_modname	= $member['members_display_name'];
		}

		//-----------------------------------------
		// Build insert/update array
		//-----------------------------------------

		$save	= array(
						'mod_type'					=> $this->request['mod_type'],
						'mod_type_id'				=> $_modid,
						'mod_type_name'				=> $_modname,
						'mod_categories'			=> implode( ',', $this->request['mod_categories'] ),
						'mod_can_approve'			=> intval($this->request['mod_can_approve']),
						'mod_can_edit'				=> intval($this->request['mod_can_edit']),
						'mod_can_hide'				=> intval($this->request['mod_can_hide']),
						'mod_can_delete'			=> intval($this->request['mod_can_delete']),
						'mod_can_approve_comments'	=> intval($this->request['mod_can_approve_comments']),
						'mod_can_edit_comments'		=> intval($this->request['mod_can_edit_comments']),
						'mod_can_delete_comments'	=> intval($this->request['mod_can_delete_comments']),
						'mod_can_move'				=> intval($this->request['mod_can_move']),
						'mod_set_cover_image'		=> intval($this->request['mod_set_cover_image']),
						);

		//-----------------------------------------
		// Insert or update
		//-----------------------------------------

		if( $type == 'add' )
		{
			$this->DB->insert( "gallery_moderators", $save );
		}
		else
		{
			$this->DB->update( "gallery_moderators", $save, "mod_id=" . intval($this->request['modid']) );
		}
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------

		$this->registry->gallery->helper('categories')->rebuildModeratorCache();

		//-----------------------------------------
		// Store admin log and redirect
		//-----------------------------------------

		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['galmod_' . $this->request['mod_type'] . '_addedit'], $_modname ) );
		$this->registry->output->setMessage( sprintf( $this->lang->words['galmod_' . $this->request['mod_type'] . '_addedit'], $_modname ) );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save the moderator
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function moderatorDelete()
	{
		//-----------------------------------------
		// Get moderator record and check
		//-----------------------------------------

		if( !$this->request['modid'] )
		{
			$this->registry->output->showError( $this->lang->words['galmod_error_notfounddel'], 117300.5 );
		}

		$moderator	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_moderators', 'where' => "mod_id=" . intval($this->request['modid']) ) );
		
		if( !$moderator['mod_id'] )
		{
			$this->registry->output->showError( $this->lang->words['galmod_error_notfounddel'], 117300.6 );
		}

		//-----------------------------------------
		// Remove the moderator if appropriate
		//-----------------------------------------

		$cats	= explode( ',', $moderator['mod_categories'] );
		
		if( count($cats) < 2 OR !$this->request['category'] )
		{
			$this->DB->delete( 'gallery_moderators', 'mod_id=' . $moderator['mod_id'] );
		}
		else
		{
			//-----------------------------------------
			// Otherwise just remove this category
			//-----------------------------------------

			$new_cats	= array();
			
			foreach( $cats as $k => $v )
			{
				if( $v != $this->request['category'] )
				{
					$new_cats[] = $v;
				}
			}
			
			if( count($new_cats) > 0 )
			{
				$this->DB->update( 'gallery_moderators', array( 'mod_categories' => implode( ',', $new_cats ) ), 'mod_id=' . $moderator['mod_id'] );
			}
			else
			{
				$this->DB->delete( 'gallery_moderators', 'mod_id=' . $moderator['mod_id'] );
			}
		}	

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->registry->gallery->helper('categories')->rebuildModeratorCache();

		//-----------------------------------------
		// Store admin log and redirect
		//-----------------------------------------

		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['galmod_' . $moderator['mod_type'] . '_deleted'], $moderator['mod_type_name'] ) );
		$this->registry->output->setMessage( sprintf( $this->lang->words['galmod_' . $moderator['mod_type'] . '_deleted'], $moderator['mod_type_name'] ) );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
}