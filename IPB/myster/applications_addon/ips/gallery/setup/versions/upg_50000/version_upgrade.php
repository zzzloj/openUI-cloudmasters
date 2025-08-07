<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v5.0.5
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Set time out */
		@set_time_limit( 3600 );
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
			case 'convertAlbumsToCategories':
				$this->convertAlbumsToCategories();
			break;

			case 'convertAlbumsTableStepOne':
				$this->convertAlbumsTableStepOne();
			break;

			case 'convertAlbumsTableStepTwo':
				$this->convertAlbumsTableStepTwo();
			break;

			case 'convertAlbumsTableStepThree':
				$this->convertAlbumsTableStepThree();
			break;

			case 'rebuildAlbums':
				$this->rebuildAlbums();
			break;

			case 'rebuildImages':
				$this->rebuildImages();
			break;

			case 'removeOrphanedImages':
				$this->removeOrphanedImages();
			break;

			case 'rebuildCategories':
				$this->rebuildCategories();
			break;

			case 'finish':
				$this->finish();
			break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Convert global albums to categories
	 *
	 * @return	@e void
	 */
	public function convertAlbumsToCategories()
	{
		//-----------------------------------------
		// Add the category id column so we can monitor
		//-----------------------------------------

		$this->DB->addField( 'gallery_albums_main', 'album_category_id', 'int', '0' );

		//-----------------------------------------
		// Does members gallery setting exist?
		//-----------------------------------------

		$setting	= $this->DB->buildAndFetch( array( 'select' => 'conf_id', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='gallery_members_album'" ) );

		if( !$setting['conf_id'] )
		{
			$array = array( 'conf_title'		=> 'Gallery Member&#39;s Album',
							'conf_description'	=> "ID for the member's designed album",
							'conf_group'		=> 0,
							'conf_type'			=> 'input',
							'conf_key'			=> 'gallery_members_album',
							'conf_value'		=> '',
							'conf_default'		=> '',
							'conf_extra'		=> '',
							'conf_evalphp'		=> '',
							'conf_protected'	=> 1,
							'conf_position'		=> 1,
							'conf_start_group'	=> '',
							'conf_add_cache'	=> 1,
							'conf_keywords'		=> '',
						 );

			$this->DB->insert( 'core_sys_conf_settings', $array );
			$this->cache->rebuildCache( 'settings', 'global' );
		}

		//-----------------------------------------
		// Get global albums and loop
		//-----------------------------------------

		$albumCatMap	= array();
		$imagesOnly		= array();
		$position		= 1;
		$options		= null;

		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums_main', 'where' => 'album_is_global=1', 'order' => 'album_position ASC' ) );
		$outer = $this->DB->execute();

		while( $album = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Fix older sort options
			//-----------------------------------------

			$options	= @unserialize( $album['album_sort_options'] );

			if( $options['key'] )
			{
				$options['key']	= ( $options['key'] == 'name') ? 'image_caption' : ( ( $options['key'] == 'idate') ? 'image_date' : ( ( $options['key'] == 'rating') ? 'image_rating' : ( ( $options['key'] == 'comments') ? 'image_comments' : ( ( $options['key'] == 'views') ? 'image_views' : $options['key'] ) ) ) );
				$album['album_sort_options']	= serialize($options);
			}

			$options	= null;

			//-----------------------------------------
			// Insert new category
			//-----------------------------------------

			$category	= array(
								'category_name'				=> $album['album_name'],
								'category_name_seo'			=> $album['album_name_seo'],
								'category_description'		=> $album['album_description'],
								'category_cover_img_id'		=> $album['album_cover_img_id'],
								'category_type'				=> ( $album['album_g_container_only'] == 1 ) ? 1 : 2,
								'category_sort_options'		=> $album['album_sort_options'],
								'category_allow_comments'	=> $album['album_allow_comments'],
								'category_allow_rating'		=> $album['album_allow_rating'],
								'category_approve_img'		=> $album['album_g_approve_img'],
								'category_approve_com'		=> $album['album_g_approve_com'],
								'category_rules'			=> $album['album_g_rules'],
								'category_after_forum_id'	=> $album['album_after_forum_id'],
								'category_watermark'		=> ( !$album['album_watermark'] ) ? 0 : ( ( $album['album_watermark'] == 2 ) ? 2 : 1 ),
								'category_can_tag'			=> $album['album_can_tag'],
								'category_preset_tags'		=> $album['album_preset_tags'],
								'category_position'			=> $position,
								);

			$this->DB->insert( 'gallery_categories', $category );

			$category['category_id']	= $this->DB->getInsertId();

			if( $album['album_g_perms_thumbs'] == 'member' )
			{
				IPSLib::updateSettings( array( 'gallery_members_album' => $category['category_id'] ) );
			}

			//-----------------------------------------
			// Insert permissions
			//-----------------------------------------

			$permissions	= array(
									'app'			=> 'gallery',
									'perm_type'		=> 'categories',
									'perm_type_id'	=> $category['category_id'],
									'perm_view'		=> $album['album_g_perms_view'],
									'perm_2'		=> $album['album_g_perms_images'],
									'perm_3'		=> $album['album_g_perms_comments'],
									'perm_4'		=> $album['album_g_perms_comments'],
									'perm_5'		=> $album['album_g_perms_moderate'],
									);

			$this->DB->insert( 'permission_index', $permissions );

			//-----------------------------------------
			// Update images in this category
			//-----------------------------------------

			$this->DB->update( 'gallery_images', array( 'image_category_id' => $category['category_id'], 'image_album_id' => 0, 'image_parent_permission' => $album['album_g_perms_view'], 'image_privacy' => 0 ), 'image_album_id=' . $album['album_id'] );

			//-----------------------------------------
			// Store mapping
			//-----------------------------------------

			$position++;

			$albumCatMap[ $album['album_id'] ]	= array( 'album' => $album, 'category' => $category );

			if( $category['category_type'] == 2 )
			{
				$imagesOnly[]					= $category['category_id'];
			}
		}

		//-----------------------------------------
		// Fix album data
		//-----------------------------------------

		$foundMembersGallery	= 0;

		foreach( $albumCatMap as $albumId => $data )
		{
			//-----------------------------------------
			// Set subcategory parent association if necessary
			//-----------------------------------------

			if( $data['album']['album_parent_id'] )
			{
				$this->DB->update( 'gallery_categories', array( 'category_parent_id' => $albumCatMap[ $data['album']['album_parent_id'] ]['category']['category_id'] ), 'category_id=' . $data['category']['category_id'] );
			}

			//-----------------------------------------
			// Move our child albums
			//-----------------------------------------

			$this->DB->update( 'gallery_albums_main', array( 'album_category_id' => $data['category']['category_id'], 'album_parent_id' => 0 ), 'album_parent_id=' . $albumId );

			//-----------------------------------------
			// Fix members album cat association
			//-----------------------------------------

			if( $albumId == $this->settings['gallery_members_album'] )
			{
				IPSLib::updateSettings( array( 'gallery_members_album' => $data['category']['category_id'] ) );
				$foundMembersGallery	= $data['category']['category_id'];

				$this->DB->update( 'gallery_categories', array( 'category_type' => 1 ), 'category_id=' . $data['category']['category_id'] );
			}
		}

		//-----------------------------------------
		// If we didn't find a members gallery, make one
		//-----------------------------------------

		if( !$foundMembersGallery )
		{
			$category	= array(
								'category_name'				=> 'Temp global album for root member albums',
								'category_name_seo'			=> 'temp-global-album-for-root-member-albums',
								'category_description'		=> "This is a temporary global album that holds the member albums that didn't have the proper parent album set. This album has NO permissions and is not visible from the public side, please move the albums in the proper location.",
								'category_cover_img_id'		=> 0,
								'category_type'				=> 1,
								'category_sort_options'		=> '',
								'category_allow_comments'	=> 1,
								'category_allow_rating'		=> 1,
								'category_approve_img'		=> 0,
								'category_approve_com'		=> 0,
								'category_rules'			=> '',
								'category_after_forum_id'	=> 0,
								'category_watermark'		=> 0,
								'category_can_tag'			=> 1,
								'category_preset_tags'		=> '',
								'category_position'			=> $position,
								);

			$this->DB->insert( 'gallery_categories', $category );

			$category['category_id']	= $this->DB->getInsertId();
			$foundMembersGallery		= $category['category_id'];
			IPSLib::updateSettings( array( 'gallery_members_album' => $category['category_id'] ) );
		}

		//-----------------------------------------
		// Move any albums in a category with type 2 to members album cat
		//-----------------------------------------

		if( count($imagesOnly) )
		{
			$this->DB->update( 'gallery_albums_main', array( 'album_category_id' => $foundMembersGallery ), 'album_category_id IN(' . implode( ',', $imagesOnly ) . ')' );
		}

		//-----------------------------------------
		// Delete global albums
		//-----------------------------------------

		$this->DB->delete( 'gallery_albums_main', 'album_is_global=1' );

		//-----------------------------------------
		// Gallery object
		//-----------------------------------------

		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$this->gallery = new ipsGallery( $this->registry );

		//-----------------------------------------
		// Rebuild cache (used later)
		//-----------------------------------------

		$this->gallery->helper('categories')->rebuildCatCache();

		//-----------------------------------------
		// Show message and continue
		//-----------------------------------------

		$this->registry->output->addMessage( "Global albums converted to categories..." );

		$this->request['workact'] = 'convertAlbumsTableStepOne';
	}

	/**
	 * Convert albums table (step 1): Add new columns, change existing columns and remove irrelevant columns
	 *
	 * @return	@e void
	 */
	public function convertAlbumsTableStepOne()
	{
		//-----------------------------------------
		// No idea why, but sometimes this index disappears?
		// @link	http://community.invisionpower.com/resources/bugs.html/_/ip-gallery/missing-index-img-id-after-upgrade-r38838
		//-----------------------------------------

		if( !$this->DB->checkForIndex( 'img_id', 'gallery_comments' ) )
		{
			$PRE	= ipsRegistry::dbFunctions()->getPrefix();

			if( $this->DB->checkForIndex( 'comment_img_id', 'gallery_comments' ) )
			{
				$this->DB->query( "ALTER TABLE {$PRE}gallery_comments DROP INDEX comment_img_id" );
			}

			$this->DB->addIndex( 'gallery_comments', 'img_id', 'comment_img_id, comment_post_date' );
		}

		//-----------------------------------------
		// Add new columns
		//-----------------------------------------

		$this->DB->addField( 'gallery_albums_main', 'album_type', 'int', '0' );
		$this->DB->addField( 'gallery_albums_main', 'album_last_x_images', 'TEXT', 'NULL' );

		//-----------------------------------------
		// Change existing columns
		//-----------------------------------------

		$this->DB->changeField( 'gallery_albums_main', 'album_allow_comments', 'album_allow_comments', 'tinyint', '0' );
		$this->DB->changeField( 'gallery_albums_main', 'album_allow_rating', 'album_allow_rating', 'tinyint', '0' );
		$this->DB->changeField( 'gallery_albums_main', 'album_watermark', 'album_watermark', 'tinyint', '0' );

		//-----------------------------------------
		// Delete old columns
		//-----------------------------------------

		$this->DB->dropField( 'gallery_albums_main', 'album_is_global' );
		$this->DB->dropField( 'gallery_albums_main', 'album_is_profile' );
		$this->DB->dropField( 'gallery_albums_main', 'album_cache' );
		$this->DB->dropField( 'gallery_albums_main', 'album_node_level' );
		$this->DB->dropField( 'gallery_albums_main', 'album_node_left' );
		$this->DB->dropField( 'gallery_albums_main', 'album_node_right' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_approve_img' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_approve_com' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_bitwise' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_rules' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_container_only' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_perms_thumbs' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_perms_view' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_perms_images' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_perms_comments' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_perms_moderate' );
		$this->DB->dropField( 'gallery_albums_main', 'album_g_latest_imgs' );
		$this->DB->dropField( 'gallery_albums_main', 'album_detail_default' );
		$this->DB->dropField( 'gallery_albums_main', 'album_child_tree' );
		$this->DB->dropField( 'gallery_albums_main', 'album_parent_tree' );
		$this->DB->dropField( 'gallery_albums_main', 'album_can_tag' );
		$this->DB->dropField( 'gallery_albums_main', 'album_preset_tags' );

		//-----------------------------------------
		// Show message and continue
		//-----------------------------------------

		$this->registry->output->addMessage( "Albums database table updated..." );

		$this->request['workact'] = 'convertAlbumsTableStepTwo';
	}

	/**
	 * Convert albums table (step 2): Convert album data
	 *
	 * @return	@e void
	 */
	public function convertAlbumsTableStepTwo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_id		= ( $this->request['st'] ) ? explode( ';', $this->request['st'] ) : array( 0, 0 );
		$id			= intval( $_id[0] );
		$lastId		= 0;
		$done		= intval( $_id[1] );
		$cycleDone	= 0;

		$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'gallery_albums_main' ) );
		
		//-----------------------------------------
		// Fetch albums that have a parent defined
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'gallery_albums_main',
								 'where'	=> 'album_id > ' . $id,
								 'limit'	=> array( 0, 100 ),
								 'order'	=> 'album_id ASC' ) );
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$cycleDone++;
			$lastId	= $row['album_id'];
			$update	= array();

			//-----------------------------------------
			// Reset watermark
			//-----------------------------------------

			if( $row['album_watermark'] )
			{
				$update['album_watermark']	= 1;
			}

			//-----------------------------------------
			// Reset public/private/friend-only
			//-----------------------------------------

			if( $row['album_is_public'] == 1 )
			{
				$update['album_type']	= 1;
			}
			else if( $row['album_is_public'] == 2 )
			{
				$update['album_type']	= 3;
			}
			else
			{
				$update['album_type']	= 2;
			}

			//-----------------------------------------
			// Get the parent (up to 4 levels deep..)
			//-----------------------------------------

			if( $row['album_parent_id'] )
			{
				$parent	= $this->DB->buildAndFetch( array( 'select' => 'album_id, album_parent_id, album_category_id', 'from' => 'gallery_albums_main', 'where' => 'album_id=' . intval($row['album_parent_id']) ) );

				if( $parent['album_id'] )
				{
					if( $parent['album_category_id'] )
					{
						$update['album_category_id']	= $parent['album_category_id'];
					}
					else if( $parent['album_parent_id'] )
					{
						$_parent	= $this->DB->buildAndFetch( array( 'select' => 'album_id, album_parent_id, album_category_id', 'from' => 'gallery_albums_main', 'where' => 'album_id=' . intval($parent['album_parent_id']) ) );

						if( $_parent['album_id'] )
						{
							if( $_parent['album_category_id'] )
							{
								$update['album_category_id']	= $_parent['album_category_id'];
							}
							else if( $_parent['album_parent_id'] )
							{
								$__parent	= $this->DB->buildAndFetch( array( 'select' => 'album_id, album_parent_id, album_category_id', 'from' => 'gallery_albums_main', 'where' => 'album_id=' . intval($_parent['album_parent_id']) ) );

								if( $__parent['album_id'] )
								{
									if( $__parent['album_category_id'] )
									{
										$update['album_category_id']	= $__parent['album_category_id'];
									}
									else if( $__parent['album_parent_id'] )
									{
										$___parent	= $this->DB->buildAndFetch( array( 'select' => 'album_id, album_parent_id, album_category_id', 'from' => 'gallery_albums_main', 'where' => 'album_id=' . intval($__parent['album_parent_id']) ) );

										if( $___parent['album_category_id'] )
										{
											$update['album_category_id']	= $___parent['album_category_id'];
										}
									}
								}
							}
						}
					}
				}
			}

			//-----------------------------------------
			// If we didn't find cat, move to members albums cat
			//-----------------------------------------

			if( !$update['album_category_id'] )
			{
				$update['album_category_id']	= $row['album_category_id'] ? $row['album_category_id'] : intval($this->settings['gallery_members_album']);
			}

			//-----------------------------------------
			// Save updates
			//-----------------------------------------

			if( count($update) )
			{
				$this->DB->update( 'gallery_albums_main', $update, 'album_id=' . $row['album_id'] );
			}
		}
		
		//-----------------------------------------
		// Got any more? .. redirect
		//-----------------------------------------

		if ( $cycleDone )
		{
			$done += $cycleDone;
			
			$this->registry->output->addMessage( "Albums converted: {$done}/{$total['count']}...." );
			
			$this->request['st']	= $lastId . ';' . $done;

			$this->request['workact'] = 'convertAlbumsTableStepTwo';
		}
		else
		{
			$this->registry->output->addMessage( "All albums converted...." );
			$this->request['workact']	= 'convertAlbumsTableStepThree';
			$this->request['st']		= '';
		}
	}

	/**
	 * Convert albums table (step 3): Remove old columns and rename table
	 *
	 * @return	@e void
	 */
	public function convertAlbumsTableStepThree()
	{
		//-----------------------------------------
		// Move any lingering albums to member album cat
		//-----------------------------------------

		$this->DB->update( 'gallery_albums_main', array( 'album_category_id' => ipsRegistry::$settings['gallery_members_album'] ), 'album_category_id=0' );

		//-----------------------------------------
		// Delete old columns
		//-----------------------------------------

		$this->DB->dropField( 'gallery_albums_main', 'album_parent_id' );
		$this->DB->dropField( 'gallery_albums_main', 'album_is_public' );

		//-----------------------------------------
		// Update indexes
		//-----------------------------------------

		$PRE	= ipsRegistry::dbFunctions()->getPrefix();

		if( $this->DB->checkForIndex( 'album_nodes', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_nodes" );
		}

		if( $this->DB->checkForIndex( 'album_parent_id', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_parent_id" );
		}

		if( $this->DB->checkForIndex( 'album_owner_id', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_owner_id" );
		}

		if( $this->DB->checkForIndex( 'album_count_imgs', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_count_imgs" );
		}

		if( $this->DB->checkForIndex( 'album_has_a_perm', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_has_a_perm" );
		}

		if( $this->DB->checkForIndex( 'album_child_lup', 'gallery_albums_main' ) )
		{
			$this->DB->query( "ALTER TABLE {$PRE}gallery_albums_main DROP INDEX album_child_lup" );
		}

		$this->DB->addIndex( 'gallery_albums_main', 'album_owner_id', 'album_owner_id, album_last_img_date' );
		$this->DB->addIndex( 'gallery_albums_main', 'album_parent_id', 'album_category_id, album_name_seo' );

		//-----------------------------------------
		// Rename the table
		//-----------------------------------------

		$this->DB->query( "RENAME TABLE {$PRE}gallery_albums_main TO {$PRE}gallery_albums" );

		//-----------------------------------------
		// Show message and continue
		//-----------------------------------------

		$this->registry->output->addMessage( "Album updates complete..." );

		$this->request['workact'] = 'removeOrphanedImages';
	}

	/**
	 * Removes orphaned images left by an old delete album bug
	 * 
	 * @return	@e void
	 */
	public function removeOrphanedImages()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}

		$_id		= ( $this->request['st'] ) ? explode( ';', $this->request['st'] ) : array( 0, 0 );
		$id			= intval( $_id[0] );
		$lastId		= 0;
		$done		= intval( $_id[1] );
		$cycleDone	= 0;
		$images		= array();		
		
		//-----------------------------------------
		// Get album ids
		//-----------------------------------------

		$this->DB->build( array( 'select' => 'album_id', 'from' => 'gallery_albums' ) );
		$query = $this->DB->fetchSqlString();
		$this->DB->flushQuery();

		//-----------------------------------------
		// Find images not in any album
		//-----------------------------------------

		$this->DB->allow_sub_select = true;
		$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'gallery_images', 'where' => 'image_category_id=0 AND image_album_id NOT IN (' . $query . ')' ) );
		
		//-----------------------------------------
		// Fetch the images
		//-----------------------------------------

		if ( $total['count'] )
		{
			//-----------------------------------------
			// Gallery object
			//-----------------------------------------

			require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
			$this->gallery = new ipsGallery( $this->registry );

			$this->DB->allow_sub_select = true;
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_images',
									 'where'  => 'image_id > ' . $id . ' AND image_category_id=0 AND image_album_id NOT IN (' . $query . ')',
									 'limit'  => array( 0, 100 ),
									 'order'  => 'image_id ASC' )  );
									
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$cycleDone++;
				$lastId = $row['image_id'];
				
				$row['image_album_id']  = 0; # Reset album ID to 0 to not trigger the album resync as they don't exist anymore
				$images[ $row['image_id'] ] = $row;
			}
			
			$this->gallery->helper('moderate')->deleteImages( $images );
		}
		
		//-----------------------------------------
		// If we have more, cycle through, otherwise continue
		//-----------------------------------------

		if ( $cycleDone )
		{
			$done += $cycleDone;
			
			$this->registry->output->addMessage("Orphaned images deleted: {$done}/{$total['count']}....");
			$this->request['st']		= $lastId . ';' . $done;
			$this->request['workact']	= 'removeOrphanedImages';
		}
		else
		{
			$this->registry->output->addMessage("All orphaned images deleted....");
			$this->request['st']		= '';
			$this->request['workact']	= 'rebuildImages';
		}
	}

	/**
	 * Rebuild images
	 * 
	 * @return	@e void
	 */
	public function rebuildImages()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$_id		= ( $this->request['st'] ) ? explode( ';', $this->request['st'] ) : array( 0, 0 );
		$id			= intval( $_id[0] );
		$lastId		= 0;
		$done		= intval( $_id[1] );
		$cycleDone	= 0;

		$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'gallery_images' ) );

		//-----------------------------------------
		// Gallery object
		//-----------------------------------------

		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$this->gallery = new ipsGallery( $this->registry );

		//-----------------------------------------
		// Fetch the images
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'i.*',
								 'from'		=> array( 'gallery_images' => 'i' ),
								 'where'	=> 'i.image_id > ' . $id,
								 'limit'	=> array( 0, 50 ),
								 'order'  	=> 'i.image_id ASC',
								 'add_join'	=> array(
								 					array(
								 						'select'	=> 'a.*',
								 						'from'		=> array( 'gallery_albums' => 'a' ),
								 						'where'		=> 'a.album_id=i.image_album_id',
								 						'type'		=> 'left',
								 						)
								 					)
						)		);
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$cycleDone++;
			$lastId	= $row['image_id'];

			$this->gallery->helper('image')->resync( $row );
			//$this->gallery->helper('image')->buildSizedCopies( $row );
		}
		
		//-----------------------------------------
		// Got any more? .. redirect
		//-----------------------------------------

		if ( $cycleDone )
		{
			$done += $cycleDone;
			
			$this->registry->output->addMessage( "Resynchronized image data: {$done}/{$total['count']}...." );
			
			$this->request['st']		= $lastId . ';' . $done;
			$this->request['workact']	= 'rebuildImages';
		}
		else
		{
			$this->registry->output->addMessage( "All images resynchronized...." );
			$this->request['st']		= '';
			$this->request['workact']	= 'rebuildAlbums';
		}
	}

	/**
	 * Rebuild albums
	 * 
	 * @return	@e void
	 */
	public function rebuildAlbums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_id		= ( $this->request['st'] ) ? explode( ';', $this->request['st'] ) : array( 0, 0 );
		$id			= intval( $_id[0] );
		$lastId		= 0;
		$done		= intval( $_id[1] );
		$cycleDone	= 0;
		$options	= null;

		$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'gallery_albums' ) );

		//-----------------------------------------
		// Gallery object
		//-----------------------------------------

		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$this->gallery = new ipsGallery( $this->registry );

		//-----------------------------------------
		// Fetch our albums
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'gallery_albums',
								 'where'	=> 'album_id > ' . $id,
								 'limit'	=> array( 0, 100 ),
								 'order'	=> 'album_id ASC' )  );
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$cycleDone++;
			$lastId	= $row['album_id'];

			//-----------------------------------------
			// Fix older sort options
			//-----------------------------------------

			$options	= @unserialize( $row['album_sort_options'] );

			if( $options['key'] )
			{
				$options['key']	= ( $options['key'] == 'name') ? 'image_caption' : ( ( $options['key'] == 'idate') ? 'image_date' : ( ( $options['key'] == 'rating') ? 'image_rating' : ( ( $options['key'] == 'comments') ? 'image_comments' : ( ( $options['key'] == 'views') ? 'image_views' : $options['key'] ) ) ) );
				$row['album_sort_options']	= serialize($options);

				$this->DB->update( 'gallery_albums', array( 'album_sort_options' => $row['album_sort_options'] ), 'album_id=' . $row['album_id'] );
			}

			$options	= null;
			
			$this->gallery->helper('image')->updatePermissionFromParent( $row );
			$this->gallery->helper('albums')->resync( $row );
		}
		
		//-----------------------------------------
		// Got any more? .. redirect
		//-----------------------------------------

		if ( $cycleDone )
		{
			$done += $cycleDone;
			
			$this->registry->output->addMessage( "Album data rebuilt: {$done}/{$total['count']}...." );
			
			$this->request['st']		= $lastId . ';' . $done;
			$this->request['workact']	= 'rebuildAlbums';
		}
		else
		{
			$this->registry->output->addMessage( "All albums rebuilt...." );
			$this->request['workact']	= 'rebuildCategories';
			$this->request['st']		= '';
		}
	}

	/**
	 * Resync categories.  Left for last so albums/images can update appropriately first.
	 *
	 * @return	@e void
	 */
	public function rebuildCategories()
	{
		//-----------------------------------------
		// Gallery object
		//-----------------------------------------

		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$this->gallery = new ipsGallery( $this->registry );

		$this->gallery->helper('categories')->rebuildCategory( 'all' );

		$this->registry->output->addMessage( "Category data rebuilt...." );
		$this->request['workact']	= 'finish';
	}

	/**
	 * Finish up conversion stuff
	 * 
	 * @return	@e void
	 */
	public function finish()
	{
		$this->registry->output->addMessage( "Upgrade completed" );

		$this->request['workact'] = '';
	}
}