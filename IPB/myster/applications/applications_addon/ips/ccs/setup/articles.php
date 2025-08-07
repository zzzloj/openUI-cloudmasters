<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class setup_articles
{
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
		
		$this->createDatabases();
	}
	
	/**
	 * We need to create the article database
	 *
	 * @return	array
	 */
	public function createDatabases()
	{
		/* Get Data */
		$data			= $this->getDatabaseData();
		$_fieldIds		= array();
		$_catIds		= array();
		$_databaseId	= 0;
		
		//-----------------------------------------
		// If a new install, set database search properly
		//-----------------------------------------
		
		if( !defined('CCS_UPGRADE') OR !CCS_UPGRADE )
		{
			$data['databases']['articles']['database_search']		= 1;
			$data['databases']['media']['database_search']			= 1;
			
			$data['fields']['articles'][1]['field_topic_format']	= '{value}';
			$data['fields']['media'][2]['field_topic_format']		= '{value}';
		}

		//-----------------------------------------
		// Articles
		//-----------------------------------------
		
		/* Insert DB, need to fix: database_database, database_field_title, database_field_content */
		$this->DB->insert( "ccs_databases", $data['databases']['articles'] );

		$_databaseId	= $this->DB->getInsertId();
		$driver         = $this->registry->dbFunctions()->getDriverType();
		
		/* Now the fields */
		foreach( $data['fields']['articles'] as $field )
		{
			$field['field_database_id']	= $_databaseId;
			
			$this->DB->insert( "ccs_database_fields", $field );
			
			$_fieldId	= $this->DB->getInsertId();
			$_fieldIds[ $field['field_key'] ]	= 'field_' . $_fieldId;
		}

		/* Update database */
		$_dbUpdate	= array(
							'database_database'			=> "ccs_custom_database_" . $_databaseId,
							'database_field_title'		=> $_fieldIds['article_title'],
							'database_field_content'	=> $_fieldIds['article_body'],
							);

		$this->DB->update( "ccs_databases", $_dbUpdate, "database_id=" . $_databaseId );
		
		$data['databases']['articles']['database_id']				= $_databaseId;
		$data['databases']['articles']['database_database']			= "ccs_custom_database_" . $_databaseId;
		$data['databases']['articles']['database_field_title']		= $_fieldIds['article_title'];
		$data['databases']['articles']['database_field_content']	= $_fieldIds['article_body'];

		/* Try to figure out "smart" permissions */
		$_normal	= array();

		$this->DB->build( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_is_supmod=1 OR g_access_cp=1 OR g_edit_profile=1" ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$_normal[ $r['g_id'] ]	= $r['g_id'];
		}

		$_normal	= count($_normal) ? ',' . implode( ',', $_normal ) . ',' : '';
		
		/* Permission index */
		$_permissions	= array(
								'app'				=> 'ccs',
								'perm_type'			=> 'databases',
								'perm_type_id'		=> $_databaseId,
								'perm_view'			=> $_normal,
								'perm_2'			=> $_normal,
								'perm_3'			=> $_normal,
								'perm_4'			=> $_normal,
								'perm_5'			=> $_normal,
								'perm_6'			=> $_normal,
								);

		$this->DB->insert( "permission_index", $_permissions );
		
		/* Now the categories */
		foreach( $data['categories']['articles'] as $category )
		{
			$category['category_database_id']	= $_databaseId;
			
			/* Fix parent id */
			if( $category['category_parent_id'] )
			{
				$category['category_parent_id']	= $_catIds[ $category['category_parent_id'] ];
			}
			
			$this->DB->insert( "ccs_database_categories", $category );
			
			/* Store parent id */
			$_catId	= $this->DB->getInsertId();
			$_catIds[ '__' . $category['category_furl_name'] . '__' ]	= $_catId;
		}
		
		/* Get driver-specific create table file */
		require_once( IPS_ROOT_PATH . '/applications_addon/ips/ccs/setup/articles_' . $driver . '.php' );/*noLibHook*/
		$_className	= "articleTables_" . $driver;
		
		$_tableCreator	= new $_className( $this->registry );
		$_tableCreator->createTable( $_databaseId, $_fieldIds );
		
		/* Populate the table */
		foreach( $data['records']['articles'] as $record )
		{
			$record['category_id']	= $_catIds[ $record['category_id'] ];
			$newRecord				= array();
			
			foreach( $record as $k => $v )
			{
				$k	= str_replace( "_field_title", $_fieldIds['article_title'], $k );
				$k	= str_replace( "_field_body", $_fieldIds['article_body'], $k );
				$k	= str_replace( "_field_date", $_fieldIds['article_date'], $k );
				$k	= str_replace( "_field_homepage", $_fieldIds['article_homepage'], $k );
				$k	= str_replace( "_field_comments", $_fieldIds['article_comments'], $k );
				$k	= str_replace( "_field_image", $_fieldIds['article_image'], $k );
				$k	= str_replace( "_field_expiry", $_fieldIds['article_expiry'], $k );
				
				$newRecord[ $k ]	= $v;
			}

			$this->DB->insert( "ccs_custom_database_{$_databaseId}", $newRecord );
		}

		//-----------------------------------------
		// Media
		//-----------------------------------------
		
		/* Do media database if this is new install */
		if( !defined('CCS_UPGRADE') OR !CCS_UPGRADE )
		{
			$_fieldIds	= array();
			
			/* Insert DB, need to fix: database_database, database_field_title, database_field_content */
			$this->DB->insert( "ccs_databases", $data['databases']['media'] );
			
			$_databaseId	= $this->DB->getInsertId();
			
			/* Now the fields */
			foreach( $data['fields']['media'] as $field )
			{
				$field['field_database_id']	= $_databaseId;
				
				$this->DB->insert( "ccs_database_fields", $field );
				
				$_fieldId	= $this->DB->getInsertId();
				$_fieldIds[ $field['field_key'] ]	= 'field_' . $_fieldId;
			}
			
			/* Update database */
			$_dbUpdate	= array(
								'database_database'			=> "ccs_custom_database_" . $_databaseId,
								'database_field_title'		=> $_fieldIds['video_title'],
								'database_field_content'	=> $_fieldIds['video_description'],
								);
	
			$this->DB->update( "ccs_databases", $_dbUpdate, "database_id=" . $_databaseId );
			
			$data['databases']['media']['database_id']				= $_databaseId;
			$data['databases']['media']['database_database']		= "ccs_custom_database_" . $_databaseId;
			$data['databases']['media']['database_field_title']		= $_fieldIds['video_title'];
			$data['databases']['media']['database_field_content']	= $_fieldIds['video_description'];

			/* Try to figure out "smart" permissions */
			$_normal	= array();

			$this->DB->build( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_is_supmod=1 OR g_access_cp=1 OR g_edit_profile=1" ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				$_normal[ $r['g_id'] ]	= $r['g_id'];
			}

			$_normal	= count($_normal) ? ',' . implode( ',', $_normal ) . ',' : '';
			
			/* Permission index */
			$_permissions	= array(
									'app'				=> 'ccs',
									'perm_type'			=> 'databases',
									'perm_type_id'		=> $_databaseId,
									'perm_view'			=> $_normal,
									'perm_2'			=> $_normal,
									'perm_3'			=> $_normal,
									'perm_4'			=> $_normal,
									'perm_5'			=> $_normal,
									'perm_6'			=> $_normal,
									);
	
			$this->DB->insert( "permission_index", $_permissions );
			
			/* Now the categories */
			foreach( $data['categories']['media'] as $category )
			{
				$category['category_database_id']	= $_databaseId;
				
				/* Fix parent id */
				if( $category['category_parent_id'] )
				{
					$category['category_parent_id']	= $_catIds[ $category['category_parent_id'] ];
				}
				
				$this->DB->insert( "ccs_database_categories", $category );
				
				/* Store parent id */
				$_catId	= $this->DB->getInsertId();
				$_catIds[ '__' . $category['category_furl_name'] . '__' ]	= $_catId;
			}
			
			/* Create table */
			$_tableCreator->createTable( $_databaseId, $_fieldIds );
			
			/* Populate the table */
			foreach( $data['records']['media'] as $record )
			{
				$record['category_id']	= $_catIds[ $record['category_id'] ];
				$newRecord				= array();
				
				foreach( $record as $k => $v )
				{
					$k	= str_replace( "_field_title", $_fieldIds['video_title'], $k );
					$k	= str_replace( "_field_ytid", $_fieldIds['video_id'], $k );
					$k	= str_replace( "_field_description", $_fieldIds['video_description'], $k );
					$k	= str_replace( "_field_image", $_fieldIds['video_thumb'], $k );
					$k	= str_replace( "_field_length", $_fieldIds['video_length'], $k );
					
					$newRecord[ $k ]	= $v;
				}
				
				$this->DB->insert( "ccs_custom_database_{$_databaseId}", $newRecord );
			}
		}
		
		//-----------------------------------------
		// Caches
		//-----------------------------------------
		
		/* Rebuild category data */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/categories.php', 'ccs_categories', 'ccs' );
		$catsLib	 = new $classToLoad( $this->registry );
		$catsLib->init( $data['databases']['articles'] );
		$this->recacheCategories( $catsLib );
		
		/* Do media database if this is new install */
		if( !defined('CCS_UPGRADE') OR !CCS_UPGRADE )
		{
			$catsLib->init( $data['databases']['media'] );
			$this->recacheCategories( $catsLib );
		}
		
		/* And rebuild caches */
		/* Caches already automatically rebuilt later in upgrade routine */
		//$this->cache->rebuildCache( 'ccs_databases' );
		//$this->cache->rebuildCache( 'ccs_fields' );

		/* Create or update front page cache */
		$templates	= $this->getTemplates();
		
		$cache		= array(
							'categories'		=> '*',
							'limit'				=> 10,
							'sort'				=> 'record_updated',
							'order'				=> 'desc',
							'pinned'			=> 1,
							'pagination'		=> 1,
							'template'			=> $templates['frontpage_blog_format'],
							);

		$this->cache->setCache( 'ccs_frontpage', $cache, array( 'array' => 1 ) );
	}

	/**
	 * Recache categories in a database
	 *
	 * @note	Copied from categories class to prevent issues with changing fields in newer versions
	 * @link	http://community.invisionpower.com/resources/bugs.html/_/ip-content/pre-20-upgrades-r41180
	 * @param	object	Category object
	 * @return	@e void
	 */
	public function recacheCategories( $catsLib )
	{
		$_categories	= array_keys( $catsLib->categories );

		foreach( $_categories as $_cat )
		{
			$_category	= $catsLib->categories[ $_cat ];
			
			if( !$_category['category_database_id'] )
			{
				continue;
			}

			$_update	= array(
								'category_records'					=> 0,
								'category_last_record_id'			=> 0,
								'category_last_record_date'			=> 0,
								'category_last_record_member'		=> 0,
								'category_last_record_name'			=> '',
								'category_last_record_seo_name'		=> '',
								);

			if( $this->DB->checkForField( 'category_rss_cache', 'ccs_database_categories' ) )
			{
				$_update['category_rss_cache']	= null;
			}

			if( $this->DB->checkForField( 'category_rss_cached', 'ccs_database_categories' ) )
			{
				$_update['category_rss_cached']	= 0;
			}
	
			$latest		= $this->DB->buildAndFetch( array(
														'select'	=> 'r.*',
														'from'		=> array( 'ccs_custom_database_' . $_category['category_database_id'] => 'r' ),
														'where'		=> 'r.record_approved=1 AND r.category_id=' . $_cat,
														'order'		=> 'r.record_updated DESC',
														'limit'		=> array( 0, 1 ),
														'add_join'	=> array(
																			array(
																				'select'	=> 'm.*',
																				'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=r.member_id',
																				'type'		=> 'left',
																				)
																			)
												)		);
	
			$_update['category_last_record_id']			= intval($latest['primary_id_field']);
			$_update['category_last_record_date']		= intval($latest['record_updated']);
			$_update['category_last_record_member']		= intval($latest['member_id']);
			$_update['category_last_record_name']		= $latest['members_display_name'];
			$_update['category_last_record_seo_name']	= $latest['members_seo_name'];
			
			$count		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_custom_database_' . $_category['category_database_id'], 'where' => 'record_approved=1 AND category_id=' . $_cat ) );
			
			$_update['category_records']				= intval($count['total']);

			$this->DB->update( 'ccs_database_categories', $_update, 'category_id=' . $_cat );
			
			$catsLib->categories[ $_cat ]	= array_merge( $catsLib->categories[ $_cat ], $_update );
		}

		$this->DB->update( 'ccs_databases', array( 'database_rss_cache' => null, 'database_rss_cached' => 0 ) );
		
		return;
	}
	
	/**
	 * Define the data for the databases to be inserted
	 *
	 * @return	array
	 */
	public function getDatabaseData()
	{
		/* Get templates */
		$templates	= $this->getTemplates();
		$member		= $this->getMember();
		
		/**
		 * Define fields
		 */
		$fields			= array(
								/* Articles */
								'articles' => array(
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Title",
										'field_description'			=> '',
										'field_key'					=> 'article_title',
										'field_type'				=> "input",
										'field_required'			=> 1,
										'field_user_editable'		=> 1,
										'field_position'			=> 1,
										'field_max_length'			=> 500,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 50,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Teaser Paragraph",
										'field_description'			=> '',
										'field_key'					=> 'teaser_paragraph',
										'field_type'				=> "editor",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 2,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										'field_topic_format'		=> '{value}<br /><br />',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Body",
										'field_description'			=> '',
										'field_key'					=> 'article_body',
										'field_type'				=> "editor",
										'field_required'			=> 1,
										'field_user_editable'		=> 1,
										'field_position'			=> 3,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										'field_topic_format'		=> '{value}',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Publish Date",
										'field_description'			=> '',
										'field_key'					=> 'article_date',
										'field_type'				=> "date",
										'field_required'			=> 1,
										'field_user_editable'		=> 1,
										'field_position'			=> 3,
										'field_max_length'			=> 0,
										'field_extra'				=> 'short',
										'field_html'				=> 0,
										'field_is_numeric'			=> 1,
										'field_truncate'			=> 0,
										'field_default_value'		=> 'Now',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Expiry Date",
										'field_description'			=> 'After this date, the article will only display in the archives',
										'field_key'					=> 'article_expiry',
										'field_type'				=> "date",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 4,
										'field_max_length'			=> 0,
										'field_extra'				=> 'short',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 0,
										'field_display_display'		=> 0,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Comment Cutoff",
										'field_description'			=> 'After this date, comments will no longer be allowed',
										'field_key'					=> 'article_cutoff',
										'field_type'				=> "date",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 5,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 0,
										'field_display_display'		=> 0,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Show on front page",
										'field_description'			=> '',
										'field_key'					=> 'article_homepage',
										'field_type'				=> "checkbox",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 6,
										'field_max_length'			=> 0,
										'field_extra'				=> '1=Yes',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '1',
										'field_display_listing'		=> 0,
										'field_display_display'		=> 0,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Allow Comments?",
										'field_description'			=> '',
										'field_key'					=> 'article_comments',
										'field_type'				=> "radio",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 7,
										'field_max_length'			=> 0,
										'field_extra'				=> "1=Yes\n0=No",
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '1',
										'field_display_listing'		=> 0,
										'field_display_display'		=> 0,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Article Image",
										'field_description'			=> '',
										'field_key'					=> 'article_image',
										'field_type'				=> "upload",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 8,
										'field_max_length'			=> 0,
										'field_extra'				=> 'gif,jpg,jpeg,png',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									),

								/* Media */
								'media' => array(
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Video Title",
										'field_description'			=> 'The main title of this video',
										'field_key'					=> 'video_title',
										'field_type'				=> "input",
										'field_required'			=> 1,
										'field_user_editable'		=> 1,
										'field_position'			=> 1,
										'field_max_length'			=> 80,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "YouTube ID",
										'field_description'			=> 'YouTube video ID',
										'field_key'					=> 'video_id',
										'field_type'				=> "input",
										'field_required'			=> 1,
										'field_user_editable'		=> 1,
										'field_position'			=> 2,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Description",
										'field_description'			=> 'Short description of this video',
										'field_key'					=> 'video_description',
										'field_type'				=> "textarea",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 3,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Thumbnail",
										'field_description'			=> 'Image to represent this video in the system',
										'field_key'					=> 'video_thumb',
										'field_type'				=> "upload",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 4,
										'field_max_length'			=> 0,
										'field_extra'				=> 'gif,jpg,jpeg,png',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									array(
										'field_database_id'			=> 0,				// Fix this
										'field_name'				=> "Length",
										'field_description'			=> 'Length of this video',
										'field_key'					=> 'video_length',
										'field_type'				=> "input",
										'field_required'			=> 0,
										'field_user_editable'		=> 1,
										'field_position'			=> 5,
										'field_max_length'			=> 0,
										'field_extra'				=> '',
										'field_html'				=> 0,
										'field_is_numeric'			=> 0,
										'field_truncate'			=> 0,
										'field_default_value'		=> '',
										'field_display_listing'		=> 1,
										'field_display_display'		=> 1,
										'field_format_opts'			=> '',
										'field_validator'			=> '',
										),
									),
								);

		/**
		 * Define databases
		 */
		$databases		= array(
								/* Articles */
								'articles' => array(
									'database_name'					=> "Articles",
									'database_key'					=> "articles9283759273592",	// Random to prevent conflict
									'database_database'				=> "",						// Fix this
									'database_description'			=> "Manage articles",
									'database_field_count'			=> count($fields['articles']),
									'database_record_count'			=> 8,
									'database_template_categories'	=> $templates['article_categories'],
									'database_template_listing'		=> $templates['article_archives'],
									'database_template_display'		=> $templates['article_view'],
									'database_all_editable'			=> 0,
									'database_revisions'			=> 1,
									'database_field_title'			=> "field_",				// Fix this
									'database_field_sort'			=> "record_updated",
									'database_field_direction'		=> "desc",
									'database_field_perpage'		=> 25,
									'database_comment_approve'		=> 0,
									'database_record_approve'		=> 1,
									'database_rss'					=> 0,
									'database_rss_cache'			=> null,
									'database_field_content'		=> "field_",				// Fix this
									'database_lang_sl'				=> "article",
									'database_lang_pl'				=> "articles",
									'database_lang_su'				=> "Article",
									'database_lang_pu'				=> "Articles",
									'database_comment_bump'			=> 0,
									'database_is_articles'			=> 1,
									'database_featured_article'		=> 0,
									'database_forum_record'			=> 0,
									'database_forum_comments'		=> 0,
									'database_forum_delete'			=> 0,
									'database_forum_forum'			=> 0,
									'database_forum_prefix'			=> '',
									'database_forum_suffix'			=> '',
									),

									
								/* Media */
								'media' => array(
									'database_name'					=> "Media Demo",
									'database_key'					=> "media_demo",
									'database_database'				=> "",						// Fix this
									'database_description'			=> "A media section, displaying videos. A demonstration of what is possible using the IP.Content database system.",
									'database_field_count'			=> count($fields['media']),
									'database_record_count'			=> 3,
									'database_template_categories'	=> $templates['media_categories'],
									'database_template_listing'		=> $templates['media_listing'],
									'database_template_display'		=> $templates['media_display'],
									'database_all_editable'			=> 0,
									'database_revisions'			=> 0,
									'database_field_title'			=> "field_",				// Fix this
									'database_field_sort'			=> "record_updated",
									'database_field_direction'		=> "desc",
									'database_field_perpage'		=> 25,
									'database_comment_approve'		=> 1,
									'database_record_approve'		=> 0,
									'database_rss'					=> 0,
									'database_rss_cache'			=> null,
									'database_field_content'		=> "field_",				// Fix this
									'database_lang_sl'				=> "video",
									'database_lang_pl'				=> "videos",
									'database_lang_su'				=> "Video",
									'database_lang_pu'				=> "Videos",
									'database_comment_bump'			=> 0,
									'database_is_articles'			=> 0,
									'database_featured_article'		=> 0,
									'database_forum_record'			=> 0,
									'database_forum_comments'		=> 0,
									'database_forum_delete'			=> 0,
									'database_forum_forum'			=> 0,
									'database_forum_prefix'			=> '',
									'database_forum_suffix'			=> '',
									),
								);

		/**
		 * Define the categories
		 */
		$categories		= array(
								/* Articles */
								'articles'	=> array(
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Articles",
										'category_parent_id'			=> 0,
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Articles describing how to use the new Articles module of IP.Content",
										'category_position'				=> 1,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "articles",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Forum Integration",
										'category_parent_id'			=> '__articles__',
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Learn how to use the new forum integration features",
										'category_position'				=> 2,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "forum",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Frontpage",
										'category_parent_id'			=> '__articles__',
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Learn how to leverage the frontpage setup in IP.Content",
										'category_position'				=> 3,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "frontpage",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Pages",
										'category_parent_id'			=> 0,
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Page management articles",
										'category_position'				=> 4,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "pages",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Miscellaneous",
										'category_parent_id'			=> 0,
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Articles describing how to use various other sections of IP.Content",
										'category_position'				=> 5,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "misc",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Databases",
										'category_parent_id'			=> '__misc__',
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Database related articles for IP.Content",
										'category_position'				=> 6,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "databases",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Templates",
										'category_parent_id'			=> '__misc__',
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Configure your templates to get the most out of IP.Content",
										'category_position'				=> 7,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "templates",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Media",
										'category_parent_id'			=> '__misc__',
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Manage your multimedia with IP.Content",
										'category_position'				=> 8,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "media",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> $templates['frontpage_blog_format'],
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									),
									
								/* Media */
								'media'	=> array(
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "New Features",
										'category_parent_id'			=> 0,
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Check out how to use some of the latest features of IP.Content",
										'category_position'				=> 1,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "new",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> 0,
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									array(
										'category_database_id'			=> 0,				// Fix this
										'category_name'					=> "Other",
										'category_parent_id'			=> 0,
										'category_last_record_id'		=> 0,
										'category_last_record_date'		=> 0,
										'category_last_record_member'	=> 0,
										'category_last_record_name'		=> '',
										'category_last_record_seo_name'	=> '',
										'category_description'			=> "Other videos you may be interested in",
										'category_position'				=> 2,
										'category_records'				=> 0,
										'category_show_records'			=> 1,
										'category_has_perms'			=> 0,
										'category_rss'					=> 20,
										'category_rss_cache'			=> null,
										'category_furl_name'			=> "other",
										'category_meta_keywords'		=> '',
										'category_meta_description'		=> '',
										'category_template'				=> 0,
										'category_forum_override'		=> 0,
										'category_forum_record'			=> 0,
										'category_forum_comments'		=> 0,
										'category_forum_delete'			=> 0,
										'category_forum_forum'			=> 0,
										'category_forum_prefix'			=> '',
										'category_forum_suffix'			=> '',
										),
									),
								);

		/* Records */
		$records		= array(
								/* Articles */
								'articles'	=> array(
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '259487fad808714985558fe5d59a51e3',
										'category_id'			=> '__forum__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'promoting-posts-to-articles',
										'_field_title'			=> "Promoting Posts to Articles",
										'_field_body'			=> "IP.Content allows you to promote posts from your forums to articles in the IP.Content Articles database.<br />
<br />
The administrator can configure the specifics of this feature in the ACP under My Apps -&gt; IP.Content -&gt; Promote Article Settings.  You can turn the system on and off, control which groups can copy and move posts to the articles section, and specify a few other details for the feature.  A new hook is included with IP.Content which adds a button to each post labeled &quot;Promote to Article&quot;.  This button only shows up if you have permission to use the feature based on the ACP configuration.<br />
<br />
When clicked, the button will take you to a new form where you can formalize the details of the new article.  You can tweak the text and title, upload an image, and specify other pertinent details.  If you are able to both move and copy posts to the articles section, you will also be asked which type of promotion you wish to use.  Upon submitting the form, IP.Content handles the rest.<br />
<br />
This new feature can be used to showcase important content otherwise hidden in your forums by pushing this content to your frontpage.  It is then up to you whether you want a copy made in the articles section (leaving the original post in tact), whether you want to actually move the post to the articles section, and whether you want any cross-linking left in place.  With such powerful options, we are sure you will find many uses for this great tool available in IP.Content.",
										'_field_date'			=> '1268694000',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "2d39a5ac26bb53702ae33132e7ae5b4e.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> 'c9be451b983a9595b775a1db471fb7e3',
										'category_id'			=> '__frontpage__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'what-is-a-frontpage',
										'_field_title'			=> "What Is A Frontpage?",
										'_field_body'			=> "IP.Content 2.0 uses the term &quot;frontpage&quot; to refer to both the homepage of the Articles module, and the landing page of each individual category.  We have introduced this new navigational structure to better allow you to showcase content, while presenting it in a standardized format that your users will be able to understand and jump into without assistance.<br />
<br />
Firstly, you will now be able to define &quot;frontpage&quot; templates in the ACP for the Articles module.  IP.Content will ship with 3 defaults:<br />
 [list]<br />
[*][b]1x2x2 Layout[/b]<br />
This layout will display articles in a traditional &quot;news&quot; style layout.<br />
[*][b]Blog format[/b]<br />
This format will display articles in a blog-style format.<br />
[*][b]Single column[/b]<br />
This layout will force articles to display in a single column, one per row.<br />
[/list]<br />
You can use one or more of these frontpage layouts, or you can create your own.  Experiment with displaying articles in different formats on your homepage to determine which layout your users like best.<br />
<br />
Articles must be set to &quot;Show on front page&quot; in order for them to display on the homepage frontpage.<br />
<br />
In addition to the homepage frontpage, each category has it&#39;s own frontpage.  The category frontpage functions identically to the homepage frontpage, except for two important factors:<br />
 [list]<br />
[*]Only records from within that category (and it&#39;s subcategories) will be displayed<br />
[*]The &quot;Show on front page&quot; setting is not honored for the category frontpage<br />
[/list]<br />
You will be able to easily review and manage the articles set to display on the frontpage from a new section of the ACP labeled &quot;Frontpage Manager&quot;.  We feel that this new area of the articles section will help showcase important articles and increase user interaction with your articles section.",
										'_field_date'			=> '1268780400',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "9a9fa3438b80e405b838b7c7227785c8.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> 'fdba36f3e898e8149622704f4c7ee6ce',
										'category_id'			=> '__media__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'media-management',
										'_field_title'			=> "Media Management",
										'_field_body'			=> "The media module in the IP.Content ACP section allows you to quickly and easily manage multimedia files you may need to use with IP.Content.  While you can certainly upload your files through FTP, or link to offsite files, you may find it easier to upload the files using the media section of the ACP, and then copy the links for use within pages, templates, and blocks.  Media files uploaded through the IP.Content Media Manager are also easily inserted using the Template Tag helper window available when editing pages, blocks and templates with just a single click.<br />
<br />
From within the media module, you can create folders, upload files, move files and folders, rename files and folders, and delete files and folders.  When viewing a listing of files you will see a preview (if the file can be previewed), and selecting a file will present some other pertinent details.  You can also right click on the file and use your browser&#39;s &quot;Copy Link&quot; option to quickly get the link to the file.<br />
<br />
This tool can be a timesaver when you simply need to upload an image quickly for use within a page, block or template.  The media folder is defined in the media_path.php file in your forum root directory, giving you the freedom to move and organize your paths as needed.",
										'_field_date'			=> '1268780400',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "4453d2bd25df7d318936bb300ef94203.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> 'd367083d21baf5bac9eeffff0b703843',
										'category_id'			=> '__pages__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'page-management',
										'_field_title'			=> "Page Management",
										'_field_body'			=> "The page management module interface has many powerful tools built in, designed to help you work with pages and folders in IP.Content efficiently.<br />
<br />
Firstly, the folder navigation utilizes AJAX to load the folder contents inline without requiring you to visit a new page to view the contents of the folder.<br />
<br />
We have also updated some common management features to utilize AJAX to help facilitate management of your pages.  Actions like clearing folders and deleting folders, for instance, will now occur without page refreshes, making your managerial activities flow smoother and quicker.<br />
<br />
Additionally, the interface as a whole has been updated to provide a nicer, smoother feel for the page management areas.  In practice, we found that many administrators spend the majority of their time setting up and utilizing IP.Content in the page management areas, so we wanted to update the user interface to make this experience as easy and enjoyable as possible.  Minor details like confirmation dialogs have been updated to bring everything together for a more consistent feel.<br />
<br />
A new filter bar, utilizing AJAX to retrieve the results without the need for a page refresh, has also been added to the page management area.  You can begin typing in the name of a page and a live-search action will occur in the background, showing you the results of your search as you type.  If you have many pages and many folders (and many pages within those many folders), you will find that using the filter bar to locate your pages can dramatically speed up your navigation of IP.Content within the page management areas of the ACP.<br />
<br />
Overall, we&#39;ve modernized the IP.Content page management area of the ACP, polishing up the little details, in an effort to make your experience all the more pleasant.",
										'_field_date'			=> '1268694000',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "01f350478adfbe32287d8e1bbbaa78d1.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '169cbc593b8a742bebe36fd26ffdedd4',
										'category_id'			=> '__templates__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'template-management',
										'_field_title'			=> "Template Management",
										'_field_body'			=> "In the IP.Content ACP, you will notice that there are 3 separate template sections of the ACP:<br />
 [list]<br />
[*][b]Block Templates[/b]<br />
[*][b]Page Templates[/b]<br />
[*][b]Database Templates[/b]<br />
[*][b]Article Templates[/b]<br />
[/list]<br />
Within each templates section, you can create containers to group your templates into logical groupings.  For instance, you may wish to create a grouping for each database you create, and then place the database templates appropriately into the container representing the database itself.  Or you may wish to create multiple front page templates, and group them all together in the article templates area.  You can use containers for whatever purposes you may have, or not at all: it&#39;s up to you&#33;<br />
<br />
Templates can be reordered by dragging and dropping the rows up and down, and they can be moved from one container to another via drag n drop as well.  <br />
<br />
Certain meta data about the templates are stored when you create new database or article templates, allowing IP.Content to tailor other areas of the ACP to help you out.  For example, the software stores the template type when you create a new template.  This allows us to show only &quot;category listing&quot; templates in the &quot;category listing template&quot; selection dropdowns.  Similarly, the template tag help panel can automatically know which template type you are editing without you having to specify.<br />
<br />
Properly making use of templates can help you push out pages on your site in a uniform manner quickly and easily, and without having to &quot;reinvent the wheel&quot; each time a new page is ready to be published.",
										'_field_date'			=> '1268694000',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "18283c56b6a7e9de70581ed1c5554381.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '8b277b0e2c676c18ffe5810aacae92fa',
										'category_id'			=> '__forum__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'store-comments-in-forum',
										'_field_title'			=> "Store Comments In Forum",
										'_field_body'			=> "With IP.Content articles and custom databases you can mirror a topic to the forums when a new article or database record is submitted.  In doing so, IP.Content can also utilize that automatically-generated topic as the comment &quot;storage&quot; for the article or record.  When a comment is submitted to the article, the comment is actually stored as a reply to the topic.  Similarly, replies made directly to the topic in the forum also show up as comments for the record.<br />
<br />
This functionality can be enabled at a per-database and per-category level.  You can specify separate forums for each category in your article section, for instance, or you can turn off forum commenting for a specific category, while enabling it for all others.<br />
<br />
A few additional configuration options, such as allowing you to automatically remove the topic when the record is removed, and specifying a prefix and/or suffix for the topic title so that your users can more easily identify that such topics were stemmed from the articles section help round out the feature, giving you better control over how these automatically posted topics are handled.<br />
<br />
The forum cross-posting capabilities allow the administrator to better tie in articles with the forums, giving you better opportunities to expose your content to a wider audience.  Additionally, forum management of comments provides for easier maintenance and stronger managerial options of the comments, utilizing IP.Board&#39;s powerful, proven feature set.",
										'_field_date'			=> '1268780400',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "024a5a7b7d7495bd655e688c9b8ed57c.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> 'f990eb16268cb7b4d305fe2960ce96ca',
										'category_id'			=> '__databases__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'sharing-links',
										'_field_title'			=> "Sharing links",
										'_field_body'			=> "IP.Board 3.1 introduces a new feature that is available for any application to make use of: [url=http://community.invisionpower.com/index.php?app=blog&amp;blogid=1174&amp;showentry=4162']sharing links[/url].  IP.Content makes use of this feature in the custom databases (and articles) modules to allow you to more easily expose your content to a wider audience.<br />
<br />
Along with supporting sharing of your content with third party services such as Facebook and Twitter, you can now also send an article via email, print the article, and download the article easily by clicking the appropriate icon under the article body.  The additional printing and downloading features allow the content to be shared, online as well as offline.<br />
<br />
Within the articles module specifically, the article image that you upload when posting the article (optionally) will automatically be flagged for use with Facebook when someone uses Facebook to share the link.  This ensures that the correct image is the one Facebook displays to other users.  Similarly, we pull out an appropriate extract of textual content for Facebook to use as well.  If the user is logged in to Twitter or Facebook, sharing the content becomes even easier, not requiring you to even leave the site.<br />
<br />
We hope that by providing tools to make it easier to share content on your site, your content will be exposed to a wider audience, bringing you more traffic and making your content more easily and readily available to the world.",
										'_field_date'			=> '1268780400',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "3eacb41863f20759407ea5587ea8c3bc.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '33910ca4a412925080cd64dc61734be6',
										'category_id'			=> '__templates__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'template-variables-help',
										'_field_title'			=> "Template Variables Help",
										'_field_body'			=> "When you edit content in IP.Content, whether it be blocks, templates or pages, there are many built in tags that you will need to or want to utilize in order to generate the content appropriately.  Blocks have variables containing data that may be of use to your users.  Page templates have variables that perform important functions, such as inserting the page title or marking where the page content should be displayed.  It is nearly impossible to simply remember every variable that can be utilized in your pages and templates.<br />
<br />
IP.Content features a template tag help panel that you can use to alleviate this problem.  The panel can be minimized if you don&#39;t need it (and your preference is remembered so you won&#39;t have to minimize it each time you load a new template to edit).  The panel is tabbed, providing you with various tag options based on the specific content you are editing.  Database templates will show you the database tags you will need to use, while blocks will show you the variables being passed into the block template.  You are able navigate some of the tabs when necessary in order to better determine the appropriate variables for the specific area you are editing.<br />
<br />
A small icon is shown next to each tag, and clicking this icon will insert it into your templates automatically wherever the cursor is blinking.  You need not manually copy and paste the tag - simply click to insert&#33;<br />
<br />
Some tags will have additional information or perhaps a relevant example of the data it represents.  These tags will have an arrow indicator next to them to let you know that you can click on the arrow to view further details about that specific tag.<br />
<br />
This panel is always available and dynamically adjusts to the type of content you are editing.  It is but one small feature available in IP.Content designed to help you build your site the way you want, as efficiently as possible.",
										'_field_date'			=> '1268784000',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "ccf9e6113c134dafd5ebe33b58a1479e.png",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '5352dc0d094b5c1494e001e404aa0cf9',
										'category_id'			=> '__templates__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'navigation-menu',
										'_field_title'			=> "Navigation Menu",
										'_field_body'			=> "IP.Content 2.3 includes a new feature designed to help you build your forums and website out the way you want to: control over your primary navigational menu.<br />
<br />
The primary navigation menu is the the &quot;tab&quot; bar across the top of the page that includes links to each major section of your site.  IP.Board builds this automatically based on the applications you have installed, but when you use IP.Content, you will likely find yourself creating new pages that you wish you could quickly link to in that same bar.  It is possible to edit your skin templates manually to accomplish this, but this presents a few problems:[list]<br />
[*]Your template edits may need to be reverted when upgrading in the future in order to inherit updates in the skin in future releases.<br />
[*]You will have to determine the logic necessary to show the tab as &quot;lit up&quot; when someone is viewing that page (and to ensure other tabs do not appear to be lit up).<br />
[*]It is inconvenient repeating this process each time a new page is created.<br />
[/list]<br />
This is no longer a problem with IP.Content 2.3.  You can now visit the &quot;Navigation Menu&quot; page available under the Settings module in the IP.Content ACP area and build tabs through an easy to use interface.  You can control what order your tabs display in, and even put them before or in between default application tabs.  You can control almost every aspect of the tab from the title, the textual tooltip, any additional non-default attributes (for instance, including a javascript click handler that will log the click in an analytics program) and more.  You can even create submenus that will display on click or on hover, including many links underneath one tab.<br />
<br />
You can also modify many aspects of your default application tabs as well, going beyond what IP.Board offers by default.  For instance, using this tool you can add additional attributes to your application tabs, change the title, and modify the textual tooltip shown when a user hovers over the tab.<br />
<br />
The best part about this new feature - IP.Content automatically figures out which tab to light up without any extra work on your part&#33;<br />
<br />
We hope this new feature in IP.Content 2.3 helps you better control your site the way you want it to be.",
										'_field_date'			=> '1268784000',
										'_field_homepage'		=> ',1,',
										'_field_comments'		=> 1,
										'_field_expiry'			=> 0,
										'_field_image'			=> "802d7349f4e726497c3c07172c71b778.png",
										),
									),
									
								/* Media */
								'media'		=> array(
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '6bdf2e08d61f80097f6380746d13d904',
										'category_id'			=> '__new__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'promote-to-article',
										'record_static_furl'	=> 'promote-article',
										'_field_title'			=> "Promote to Article",
										'_field_ytid'			=> "uzxNFpG7ems",
										'_field_description'	=> "Learn how to use the new &quot;Promote to Article&quot; feature to copy a post to the articles section.",
										'_field_image'			=> "90000532aa1cc479ab9039f6fb3e168f.png",
										'_field_length'			=> "1:14",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> '58dcd38dcf6393e2bf47a9ebccded85a',
										'category_id'			=> '__new__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'article-management',
										'record_static_furl'	=> 'articles',
										'_field_title'			=> "Article Management",
										'_field_ytid'			=> "AznH-MXILXg",
										'_field_description'	=> "This video shows off some of the user interface you can expect to see in the article management area of the ACP.",
										'_field_image'			=> "fc04831cf5b06052b2c04930feecaed9.png",
										'_field_length'			=> "0:54",
										),
									array(
										'member_id'				=> $member['member_id'],
										'record_saved'			=> time(),
										'record_updated'		=> time(),
										'post_key'				=> 'dad19507a140862322082fdc46df1835',
										'category_id'			=> '__other__',
										'record_approved'		=> 1,
										'record_dynamic_furl'	=> 'latest-topics',
										'record_static_furl'	=> 'latest-topics',
										'_field_title'			=> "Latest Topics",
										'_field_ytid'			=> "YXTPDMDHz4I",
										'_field_description'	=> "This video shows how to create a latest topics block, showing the full post, and then adding that block to a new page.",
										'_field_image'			=> "5de8a6e992ef6124306ea1c5d480bb39.png",
										'_field_length'			=> "1:53",
										),
									),
								);

		return array( 'databases' => $databases, 'categories' => $categories, 'fields' => $fields, 'records' => $records );
	}	
	
	/**
	 * Get the templates
	 *
	 * @return	array
	 */
	public function getTemplates()
	{
		static $templates	= array();
		
		if( count($templates) )
		{
			return $templates;
		}
		
		$this->DB->build( array( 'select' => 'template_id,template_key', 'from' => 'ccs_page_templates' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates[ $r['template_key'] ]	= $r['template_id'];
		}
		
		return $templates;
	}
	
	/**
	 * Get member data for the first root admin we can find
	 *
	 * @return	array
	 */
	public function getMember()
	{
		$member	= $this->DB->buildAndFetch( array( 'select' => 'member_id,members_display_name', 'from' => 'members', 'where' => 'member_group_id=' . $this->settings['admin_group'], 'limit' => array( 0, 1 ) ) );
		
		if( !count($member) )
		{
			$member	= $this->DB->buildAndFetch( array( 'select' => 'member_id,members_display_name', 'from' => 'members', 'where' => 'member_id=1' ) );
		}
		
		if( !count($member) )
		{
			$member	= array( 'member_id' => 1, 'members_display_name' => 'Admin' );
		}

		return $member;
	}
}