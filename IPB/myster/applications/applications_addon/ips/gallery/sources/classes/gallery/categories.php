<?php
/**
 * @file		categories.php 	IP.Gallery category library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		21st May 2012
 * $LastChangedDate: 2012-05-09 13:25:41 -0400 (Wed, 09 May 2012) $
 * @version		v5.0.5
 * $Revision: 10716 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_categories
{
	/**
	 * Category cache array
	 *
	 * @var		array
	 */
	public $cat_cache			= array();

	/**
	 * Direct id => data mapping
	 *
	 * @var		array
	 */
	public $cat_lookup			= array();

	/**
	 * Parent/child relationship array
	 *
	 * @var		array
	 */
	public $parent_lookup		= array();

	/**
	 * Library initialized
	 *
	 * @var		boolean
	 */
	protected $init				= false;
	
	/**
	 * Stats correctly merged
	 *
	 * @var		boolean
	 */
	protected $statsMerged		= false;

	/**
	 * Library initialization failed
	 *
	 * @var		boolean
	 */
	protected $init_failed		= false;

	/**
	 * Categories member can access
	 *
	 * @var		array
	 */
	public $member_access		= array();

	/**
	 * Member moderators
	 *
	 * @var		array
	 */
	public $mem_mods			= array();

	/**
	 * Category moderators
	 *
	 * @var		array
	 */
	public $cat_mods			= array();

	/**
	 * Group moderators
	 *
	 * @var		array
	 */
	public $group_mods			= array();
	
	/**#@+
	 * Registry objects
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
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Main initialization
	 *
	 * @return	@e void
	 */
	public function init()
	{
		if ( ! $this->init )
		{
			//-----------------------------------------
			// Have cache data?
			//-----------------------------------------
			
			if ( ! is_array( $this->cache->getCache( 'gallery_categories' ) ) )
			{
				$this->init_failed	= true;
				$this->fullInit( true );
			}
			
			//-----------------------------------------
			// If yes, set our data store
			//-----------------------------------------
			
			else
			{
				foreach( $this->cache->getCache( 'gallery_categories' ) as $parentid => $cid )
				{
					foreach( $cid as $catid => $info )
					{
						$this->cat_cache[ $parentid ][ $catid ]			= $info;
						$this->subcat_lookup[ $parentid ][] 			= $catid;
						$this->parent_lookup[ $catid ] 					= $info['category_parent_id'];
						$this->cat_lookup[ $catid ]						= $info;
					}
				}
			}
		}
		
		//-----------------------------------------
		// No data store, full init
		//-----------------------------------------

		if( empty( $this->cat_cache ) )
		{
			$this->init_failed	= true;
			$this->fullInit( true );
		}
		
		//-----------------------------------------
		// Build moderators
		//-----------------------------------------
				
		$this->buildModerators( true );
		
		$this->init	= true;
		
		//-----------------------------------------
		// Merge stats as needed
		//-----------------------------------------
		
		$this->mergeStats();
	}

	/**
	 * Full initialization
	 *
	 * @param	bool	Skip merge stats call
	 * @return	@e void
	 */
	public function fullInit( $skipMerge=false )
	{
		if ( ! $this->init )
		{
			$this->cat_lookup	= array();

			//-----------------------------------------
			// Build data store from DB
			//-----------------------------------------
			
			$this->DB->build( array( 
									'select'	=> 'c.*',
									'from'		=> array( 'gallery_categories' => 'c' ),
									'order'		=> 'c.category_parent_id, c.category_position',
									'add_join'	=> array(
														array(
																'select' => 'p.*',
																'from'   => array( 'permission_index' => 'p' ),
																'where'  => "p.perm_type='categories' AND p.perm_type_id=c.category_id AND p.app='gallery'",
																'type'   => 'left',
															)
										)
							)	);
			$this->DB->execute();
			
			$cache	= array();
			
			while( $cat = $this->DB->fetch() )
			{
				$cat['category_sort_options__key']	= 'image_date';
				$cat['category_sort_options__dir']	= 'DESC';

				if ( IPSLib::isSerialized( $cat['category_sort_options'] ) )
				{
					$order	= unserialize( $cat['category_sort_options'] );

					$order['key']	= ( $order['key'] == 'name') ? 'image_caption' : ( ( $order['key'] == 'idate') ? 'image_date' : ( ( $order['key'] == 'rating') ? 'image_rating' : ( ( $order['key'] == 'comments') ? 'image_comments' : ( ( $order['key'] == 'views') ? 'image_views' : $order['key'] ) ) ) );
					
					$cat['category_sort_options__key']	= empty( $order['key'] ) ? $cat['category_sort_options__key'] : $order['key'];
					$cat['category_sort_options__dir']	= empty( $order['dir'] ) ? $cat['category_sort_options__dir'] : $order['dir'];
				}

				if ( IPSLib::isSerialized( $cat['category_rules'] ) )
				{
					$rules	= unserialize( $cat['category_rules'] );
					
					$cat['category_rules__title']	= !empty( $rules['title'] ) ? $rules['title'] : '';
					$cat['category_rules__text']	= !empty( $rules['text'] ) ? $rules['text'] : '';
				}

				$cache[ $cat['category_parent_id'] ][ $cat['category_id'] ] = $cat;
				
				$this->cat_cache[ $cat['category_parent_id'] ][ $cat['category_id'] ]	= $cat;
				$this->subcat_lookup[ $cat['category_parent_id'] ][] 					= $cat['category_id'];
				$this->parent_lookup[ $cat['category_id'] ] 							= $cat['category_parent_id'];
				$this->cat_lookup[ $cat['category_id'] ]								= $cat;
			}
			
			//-----------------------------------------
			// Set the "cache"
			//-----------------------------------------
			
			$this->cache->updateCacheWithoutSaving( 'gallery_categories', $cache );
			
			//-----------------------------------------
			// And fix the real cache if normal init failed
			//-----------------------------------------
			
			if( $this->init_failed )
			{
				$this->cache->setCache( 'gallery_categories', $cache, array( 'array' => 1 ) );
				$this->init_failed	= false;
			}
		}
		
		//-----------------------------------------
		// Build moderators
		//-----------------------------------------
		
		$this->buildModerators( false );
		
		$this->init	= true;
		
		//-----------------------------------------
		// Merge stats as needed
		//-----------------------------------------
		
		if( !$skipMerge )
		{
			$this->mergeStats();
		}
	}

	/**
	 * Return a category
	 *
	 * @param	int			Category ID
	 * @param	string		Specific array key to return
	 * @return	@e mixed	Category array, or mixed if $key is supplied
	 */
	public function fetchCategory( $categoryId, $key='' )
	{
		return $key ? $this->cat_lookup[ $categoryId ][ $key ] : $this->cat_lookup[ $categoryId ];
	}

	/**
	 * Return all categories
	 *
	 * @param	int		Parent ID (if not supplied, all categories returned)
	 * @return	@e array
	 */
	public function fetchCategories( $parentId=null )
	{
		if( !is_array($this->cat_lookup) )
		{
			$this->init();
		}

		return ( $parentId !== null ) ? $this->cat_cache[ $parentId ] : $this->cat_lookup;
	}
	
	/**
	 * Merge stats from children categories into their parents
	 *
	 * @return	@e void
	 */
	protected function mergeStats()
	{
		if( $this->statsMerged )
		{
			return;
		}
		
		$this->setMemberPermissions();
		
		foreach( $this->cat_lookup as $id => $cat )
		{
			$children	= $this->getChildren( $id );

			if( count($children) )
			{
				foreach( $children as $_child )
				{
					if( in_array( $_child, $this->member_access['images'] ) )
					{
						$_childCat	= $this->cat_lookup[ $_child ];
	
						$cat['category_count_imgs']				+= $_childCat['category_count_imgs'];
						$cat['category_count_comments']			+= $_childCat['category_count_comments'];
						$cat['category_count_imgs_hidden']		+= $_childCat['category_count_imgs_hidden'];
						$cat['category_count_comments_hidden']	+= $_childCat['category_count_comments_hidden'];
						$cat['category_public_albums']			+= $_childCat['category_public_albums'];
						$cat['category_nonpublic_albums']		+= $_childCat['category_nonpublic_albums'];
						
						if( $_childCat['category_last_img_date'] > $cat['category_last_img_date'] )
						{
							$cat['category_last_img_date']		= $_childCat['category_last_img_date'];
							$cat['category_last_img_id']		= $_childCat['category_last_img_id'];
						}
					}
				}
				
				$this->cat_lookup[ $id ]								= $cat;
				$this->cat_cache[ $cat['category_parent_id'] ][ $id ]	= $cat;
			}
		}
		
		$this->statsMerged	= true;
	}
	
	/**
	 * Build moderators array
	 *
	 * @param	boolean		Try Cache
	 * @return	@e void
	 */
	public function buildModerators( $try_cache=true )
	{
		$use	= array();
		$loaded	= false;
		
		//-----------------------------------------
		// Trying cache?
		//-----------------------------------------

		if( $try_cache )
		{
			if( $this->cache->exists('gallery_moderators') )
			{
				$use	= $this->cache->getCache('gallery_moderators');
				$loaded	= true;
			}
		}

		//-----------------------------------------
		// Pull from DB?
		//-----------------------------------------
		
		if( !$loaded )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_moderators' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$use[] = $v;
			}
		}
		
		//-----------------------------------------
		// Loop and store
		//-----------------------------------------
		
		foreach( $use as $v )
		{
			if( $v['mod_type'] == 'group' )
			{
				$this->group_mods[ $v['mod_type_id'] ][]	= $v;
			}
			else
			{
				$this->mem_mods[ $v['mod_type_id'] ][]		= $v;
			}
			
			$cats = explode( ",", $v['mod_categories'] );
			
			if( count($cats) )
			{
				foreach( $cats as $l )
				{
					$key	= ( $v['mod_type'] == 'group' ) ? "g" . $v['mod_type_id'] : "m" . $v['mod_type_id'];

					$this->cat_mods[ $l ][ $key ]	= $v;
				}
			}
		}
	}

	/**
	 * Check if we are a moderator of a category
	 *
	 * @param	int			Category ID
	 * @param	mixed		Member ID, or Member data array, or null for current member
	 * @param	string		Moderator permission to check
	 * @return 	@e boolean
	 */
	public function checkIsModerator( $category, $member=null, $type=null )
    {
		//-----------------------------------------
		// Need a category ID
		//-----------------------------------------

	    if( !$category )
	    {
		    return false;
	    }

		//-----------------------------------------
		// If we are in the ACP, return true
		//-----------------------------------------
		
		if ( IN_ACP )
		{
			return true;
		}

		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		$memberData	= array();	

		if ( $member !== null )
		{
			if ( is_numeric( $member ) )
			{
				$memberData	= IPSMember::load( $member );
			}
			else
			{
				$memberData	= $member;
			}
		}
		else
		{
			$memberData	= $this->memberData;
		}
		
		if ( ! $memberData['member_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Supermods are gold
		//-----------------------------------------
		
		if( $memberData['g_is_supmod'] )
		{
			return true;
		}

		//-----------------------------------------
		// Put our groups together
		//-----------------------------------------

		$groups		= array( 'g' . $memberData['member_group_id'] );
		
		if( $memberData['mgroup_others'] )
		{
			foreach( explode( ',', $memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = 'g' . $omg;
			}
		}

		//-----------------------------------------
		// And check
		//-----------------------------------------

		if( is_array( $this->cat_mods[ $category ] ) AND count( $this->cat_mods[ $category ] ) )
		{
			foreach( $this->cat_mods[ $category ] as $k => $v )
			{
				if( $k == "m" . $memberData['member_id'] )
				{
					//-----------------------------------------
					// Specific permission, or any?
					//-----------------------------------------

					if( $type !== null )
					{
						if( $v[ $type ] )
						{
							return true;
						}
					}
					else
					{
						return true;
					}
				}
				else if( in_array( $k, $groups ) )
				{
					//-----------------------------------------
					// Specific permission, or any?
					//-----------------------------------------

					if( $type !== null )
					{
						if( $v[ $type ] )
						{
							return true;
						}
					}
					else
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Determine if we can view a category
	 *
	 * @param	int		Category ID
	 * @return	@e bool
	 */
	public function isViewable( $category )
	{
		return in_array( $category, $this->member_access['images'] );
	}

	/**
	 * Determine if we can upload to a category
	 *
	 * @param	int		Category ID
	 * @return	@e bool
	 */
	public function isUploadable( $category )
	{
		return in_array( $category, $this->member_access['post'] );
	}

	/**
	 * Get parents of a category
	 *
	 * @param	integer		Category id
	 * @param	array 		Parent ids
	 * @return	@e array 	Parent ids
	 */
	public function getParents( $catid, $parent_ids=array() )
	{
		if( is_array($this->cat_lookup[ $catid ]) )
		{
			if( $this->parent_lookup[ $catid ] > 0 )
			{
				$parent_ids		= $this->getParents( $this->parent_lookup[ $catid ], $parent_ids );
				$parent_ids[]	= $this->parent_lookup[ $catid ];
			}
		}

		return array_unique($parent_ids);
	}
	
	/**
	 * Get children ids of a category
	 *
	 * @param	integer		Category id
	 * @param	array 		Children ids
	 * @return	@e array 	Children ids
	 */
	public function getChildren( $catid, $child_ids=array() )
	{
		$final_ids	= array();

		if ( isset($this->cat_cache[ $catid ]) AND is_array( $this->cat_cache[ $catid ] ) )
		{
			$final_ids = array_merge( $child_ids, $this->subcat_lookup[ $catid ]);

			foreach( $this->cat_cache[ $catid ] as $id => $data )
			{
				$subchild_ids = $this->getChildren( $data['category_id'], $final_ids );
				
				if( is_array($subchild_ids) AND count($subchild_ids) )
				{
					$final_ids = array_unique (array_merge( $final_ids, $subchild_ids ) );
				}
			}
		}
		
		return $final_ids;
	}	
	
	/**
	 * Rebuild category cache
	 *
	 * @return	@e void
	 */
	public function rebuildCatCache()
	{
		$cache	= array();
		
		$this->DB->build( array( 
								'select'	=> 'c.*',
								'from'		=> array( 'gallery_categories' => 'c' ),
								'order'		=> 'c.category_parent_id, c.category_position',
								'add_join'	=> array(
													array(
															'select' => 'p.*',
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.perm_type='categories' AND p.perm_type_id=c.category_id AND p.app='gallery'",
															'type'   => 'left',
														)
									)
						)	);
		$this->DB->execute();
		
		while( $cat = $this->DB->fetch() )
		{
			$cat['category_sort_options__key']	= 'image_date';
			$cat['category_sort_options__dir']	= 'DESC';

			if ( IPSLib::isSerialized( $cat['category_sort_options'] ) )
			{
				$order	= unserialize( $cat['category_sort_options'] );

				$order['key']	= ( $order['key'] == 'name') ? 'image_caption' : ( ( $order['key'] == 'idate') ? 'image_date' : ( ( $order['key'] == 'rating') ? 'image_rating' : ( ( $order['key'] == 'comments') ? 'image_comments' : ( ( $order['key'] == 'views') ? 'image_views' : $order['key'] ) ) ) );
				
				$cat['category_sort_options__key']	= empty( $order['key'] ) ? $cat['category_sort_options__key'] : $order['key'];
				$cat['category_sort_options__dir']	= empty( $order['dir'] ) ? $cat['category_sort_options__dir'] : $order['dir'];
			}

			if ( IPSLib::isSerialized( $cat['category_rules'] ) )
			{
				$rules	= unserialize( $cat['category_rules'] );

				$cat['category_rules__title']	= !empty( $rules['title'] ) ? $rules['title'] : '';
				$cat['category_rules__text']	= !empty( $rules['text'] ) ? $rules['text'] : '';
			}

			$cache[ $cat['category_parent_id'] ][ $cat['category_id'] ] = $cat;
		}

		$this->cache->setCache( 'gallery_categories', $cache, array( 'array' => 1, 'donow' => 1 ) );
		
		//-----------------------------------------
		// Re-initialize
		//-----------------------------------------
		
		$this->init	= false;
		$this->init();		
	}
	
	/**
	 * Rebuild cached category information
	 *
	 * @param	mixed		Category id or 'all'
	 * @return	@e boolean 	Successful
	 */
	public function rebuildCategory( $catid='all' )
	{
		//-----------------------------------------
		// Not rebuilding all categories?
		//-----------------------------------------
		
		if( $catid != 'all' )
		{
			if( $catid == 0 )
			{
				return false;
			}
			
			//-----------------------------------------
			// Rebuild the category
			//-----------------------------------------
		
			$this->_rebuildCategoryInfo( $catid );
 		}
 		else
 		{
			//-----------------------------------------
			// Rebuild every category
			//-----------------------------------------
			
	 		foreach( $this->cat_lookup as $catid => $catdata )
	 		{
		 		$this->_rebuildCategoryInfo( $catid );
			}
		}
 		
		//-----------------------------------------
		// And do the cache..
		//-----------------------------------------
			
 		$this->rebuildCatCache();

 		return TRUE;
	}
	
	/**
	 * Rebuild a specific category
	 *
	 * @param	int			Category id
	 * @return	@e boolean 	Successful
	 */
	public function _rebuildCategoryInfo( $catid=0 )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		if( $catid == 0 )
		{
			return false;
		}

		$stats_array	= array(
							'category_count_imgs'				=> 0,
							'category_count_comments'			=> 0,
							'category_count_imgs_hidden'		=> 0,
							'category_count_comments_hidden'	=> 0,
							'category_last_img_id'				=> 0,
							'category_last_img_date'			=> 0,
							'category_public_albums'			=> 0,
							'category_nonpublic_albums'			=> 0,
							);

		//-----------------------------------------
		// Album counts
		//-----------------------------------------
		
		$stats	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as approved_albums',
													'from'	=> 'gallery_albums',
													'where'	=> 'album_category_id=' . $catid . ' AND album_type=1'
										  )		 );
		
		$stats_array['category_public_albums'] 			= intval($stats['approved_albums']);

		$stats	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as hidden_albums',
													'from'	=> 'gallery_albums',
													'where'	=> 'album_category_id=' . $catid . ' AND album_type > 1'
										  )		 );
		
		$stats_array['category_nonpublic_albums'] 		= intval($stats['hidden_albums']);

		//-----------------------------------------
		// Approved image stats
		//-----------------------------------------
		
		$stats	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(image_id) as images, SUM(image_comments) as comments_visible, SUM(image_comments_queued) as comments_hidden',
													'from'	=> 'gallery_images',
													'where'	=> 'image_category_id=' . $catid . ' AND image_approved=1'
										  )		 );
		
		$stats_array['category_count_imgs'] 			= intval($stats['images']);
		$stats_array['category_count_comments'] 		= intval($stats['comments_visible']);
		$stats_array['category_count_comments_hidden'] 	= intval($stats['comments_hidden']);

		//-----------------------------------------
		// Pending image stats
		//-----------------------------------------

		$stats	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(image_id) as images, SUM(image_comments) as comments_visible, SUM(image_comments_queued) as comments_hidden',
													'from'	=> 'gallery_images',
													'where'	=> 'image_category_id=' . $catid . ' AND image_approved=0'
										  )		 );
		
		$stats_array['category_count_imgs_hidden'] 		= intval($stats['images']);
		$stats_array['category_count_comments_hidden'] 	= intval( $stats_array['category_count_comments_hidden'] + $stats['comments_visible'] + $stats['comments_hidden'] ); // No comments are visible if image isn't visible

		//-----------------------------------------
		// And the latest image
		//-----------------------------------------
		
		if ( $stats_array['category_count_imgs'] )
		{
			$stats	= $this->DB->buildAndFetch( array( 'select'		=> 'image_id, image_date',
														'from'		=> 'gallery_images',
														'where'		=> 'image_category_id=' . $catid . ' AND image_approved=1 AND image_privacy IN(0,1)',
														'order'		=> 'image_date DESC',
														'limit'		=> array( 1 ),
											  )		 );
			
			$stats_array['category_last_img_id'] 		= $stats['image_id'];
			$stats_array['category_last_img_date'] 		= $stats['image_date'];
		}

		//-----------------------------------------
		// Update the category
		//-----------------------------------------

 		$this->DB->update( 'gallery_categories', $stats_array, 'category_id=' . $catid );
 		
 		return true;
 	}

	/**
	 * Rebuild the moderator cache
	 *
	 * @return	@e void
	 */
	public function rebuildModeratorCache()
	{
		$cache	= array();
		
		$this->DB->build( array(
								'select'	=> '*',
								'from'		=> 'gallery_moderators',
				   		)		);
		$this->DB->execute();
		
		while( $mod = $this->DB->fetch() )
		{
			$cache[]	= $mod;
		}
		
		$this->cache->setCache( 'gallery_moderators', $cache, array( 'array' => 1, 'donow' => 1 ) );		
	}
		
	/**
	 * Retrieve the navigation bar
	 *
	 * @param	integer		Current category id
	 * @param	string		Query string to use in URL
	 * @param	boolean		Currently in ACP
	 * @return	@e array 	Navigation entries
	 */
	public function getNav( $catid, $querybit='app=gallery&amp;category=', $acp=false )
	{
		if( $acp )
		{
			$nav_array[]	= array( $this->registry->output->buildSEOUrl( $this->settings['base_url'] . $querybit . $catid, $this->cat_lookup[ $this->cat_lookup[ $catid ]['category_parent_id'] ]['category_name_seo'] ), $this->cat_lookup[ $this->cat_lookup[ $catid ]['category_parent_id'] ]['category_name'] );
		}
		else
		{
			$nav_array[]	= array( 0 => $this->cat_lookup[ $catid ]['category_name'], 1 => $querybit . $catid, 2 => $this->cat_lookup[ $catid ]['category_name_seo'] );
		}
		
		$parent_ids	= $this->getParents( $catid );
		
		if ( is_array($parent_ids) and count($parent_ids) )
		{
			$parent_ids	= array_reverse($parent_ids);
			
			foreach( $parent_ids as $id )
			{
				if( $id > 0 )
				{
					if( $acp )
					{
						$nav_array[]	= array( $this->registry->output->buildSEOUrl( $this->settings['base_url'] . $querybit . $this->cat_lookup[ $id ]['category_id'], $this->cat_lookup[ $id ]['category_name_seo'] ), $this->cat_lookup[ $this->cat_lookup[ $id ]['category_parent_id'] ]['category_name'] );	
					}
					else
					{
						$nav_array[]	= array( 0 => $this->cat_lookup[ $id ]['category_name'], 1 => $querybit . $this->cat_lookup[ $id ]['category_id'], $this->cat_lookup[ $id ]['category_name_seo']  );
					}
				}
			}
		}

		return array_reverse( $nav_array );
	}

	/**
	 * Retrieve an array of categories that accept albums.  Used when building dropdowns to disable those that don't.
	 *
	 * @return	@e array
	 */
	public function fetchAlbumCategories()
	{
		$_catIds	= array();

		foreach( $this->cat_lookup as $id => $_cat )
		{
			if( $_cat['category_type'] == 1 )
			{
				$_catIds[]	= $id;
			}
		}

		return $_catIds;
	}

	/**
	 * Retrieve an array of categories that accept images.  Used when building dropdowns to disable those that don't.
	 *
	 * @return	@e array
	 */
	public function fetchImageCategories()
	{
		$_catIds	= array();

		foreach( $this->cat_lookup as $id => $_cat )
		{
			if( $_cat['category_type'] == 2 )
			{
				$_catIds[]	= $id;
			}
		}

		return $_catIds;
	}
	
	/**
	 * Category dropdown/multi-select list generation. 
	 * Does not return the select HTML tag, just the options
	 *
	 * @param	boolean		Add a "root category" option
	 * @param 	string		Which permissions key to check
	 * @param	array 		Selected options
	 * @param	string		Text for '0' option
	 * @return	@e array 	Categories dropdown/multiselect options
	 */
	public function catJumpList( $restrict=false, $live='none', $sel=array(), $zeroOption='(Root Category)' )
	{
		if ( !$restrict )
		{	
			$jump_array[]	= array( '0', $zeroOption );
		}
		else
		{
			$jump_array		= array();
		}

		if( count( $this->cat_cache[0] ) > 0 )
		{
			foreach( $this->cat_cache[0] as $id => $cat_data )
			{
				$disabled	= "";
				
				if( $live != 'none' )
				{
					if( ! in_array( $cat_data['category_id'], $this->member_access[ $live ] ) )
					{
						if( in_array( $cat_data['category_id'], $this->member_access['images'] ) )
						{
							$disabled	= " disabled='disabled'";
						}
						else
						{
							continue;
						}
					}

					if( is_array($sel) AND count($sel) )
					{
						if( in_array( $cat_data['category_id'], $sel ) && !$disabled )
						{
							$disabled	= " selected='selected'";
						}
					}
					else if( $this->request['c'] == $cat_data['category_id'] && !$disabled )
					{
						$disabled	= " selected='selected'";
					}
				}
					
				$jump_array[]	= array( $cat_data['category_id'], $cat_data['category_name'], $disabled );
			
				$depth_guide	= " -- ";
			
				if ( is_array( $this->cat_cache[ $cat_data['category_id'] ] ) )
				{
					foreach( $this->cat_cache[ $cat_data['category_id'] ] as $id => $cat_data )
					{
						$disabled	= "";
						
						if( $live != 'none' )
						{
							if( ! in_array( $cat_data['category_id'], $this->member_access[ $live ] ) )
							{
								if( in_array( $cat_data['category_id'], $this->member_access['images'] ) )
								{
									$disabled	= " disabled='disabled'";
								}
								else
								{
									continue;
								}
							}
							
							if( is_array($sel) AND count($sel) )
							{
								if( in_array( $cat_data['category_id'], $sel ) && !$disabled )
								{
									$disabled	= " selected='selected'";
								}
							}
							else if( $this->request['c'] == $cat_data['category_id'] && !$disabled )
							{
								$disabled	= " selected='selected'";
							}
						}

						$jump_array[]	= array( $cat_data['category_id'], $depth_guide.$cat_data['category_name'], $disabled );
						$jump_array		= $this->_internalCatJumpList( $cat_data['category_id'], $jump_array, $depth_guide . "--", $live, $sel );
					}
				}
			}
		}
		
		return $jump_array;
	}
	
	/**
	 * Category dropdown/multi-select list generation. 
	 * Does not return the select HTML tag, just the options
	 *
	 * @param	integer		Category id to start at
	 * @param 	array		Currently stored entries
	 * @param	string		Depth guide
	 * @param	string		Permission key to check
	 * @param	array 		Selected options
	 * @return	@e array 	Categories dropdown/multiselect options
	 */
	protected function _internalCatJumpList( $root_id, $jump_array=array(), $depth_guide="", $live='none', $sel=array() )
	{
		if ( is_array( $this->cat_cache[ $root_id ] ) )
		{
			foreach( $this->cat_cache[ $root_id ] as $id => $cat_data )
			{
				$disabled	= "";
				
				if( $live != 'none' )
				{
					if( ! in_array( $cat_data['category_id'], $this->member_access[ $live ] ) )
					{
						if( in_array( $cat_data['category_id'], $this->member_access['images'] ) )
						{
							$disabled	= " disabled='disabled'";
						}
						else
						{
							continue;
						}
					}
					
					if( is_array($sel) AND count($sel) )
					{
						if( in_array( $cat_data['category_id'], $sel ) && !$disabled )
						{
							$disabled	= " selected='selected'";
						}
					}
					else if( $this->request['c'] == $cat_data['category_id'] && !$disabled )
					{
						$disabled	= " selected='selected'";
					}					
				}
								
				$jump_array[]	= array( $cat_data['category_id'], $depth_guide . $cat_data['category_name'], $disabled );
				$jump_array		= $this->_internalCatJumpList( $cat_data['category_id'], $jump_array, $depth_guide . "--", $live, $sel );
			}
		}
		
		return $jump_array;
	}

	/**
	 * Sort out the member's permissions
	 *
	 * @param	integer		Member id to check (defaults to viewing member)
	 * @return	@e array 	Member permissions
	 */
	public function setMemberPermissions( $memid="" )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$no_update = 0;
		
		$member_perms	= array(
								'images' 		=> array(),
								'post' 			=> array(),
								'comments'		=> array(),
								'rate'			=> array(),
								'bypass'		=> array(),
								);

		$member_masks	= array();

		//-----------------------------------------
		// Get data for current member
		//-----------------------------------------

		if( !$memid )
		{
			if( $this->memberData['org_perm_id'] )
			{
				$member_masks	= explode( ",", IPSText::cleanPermString( $this->memberData['org_perm_id'] ) );
			}
			else
			{
				if( strpos( $this->memberData['g_perm_id'], "," ) )
				{
					$member_masks	= explode( ",", $this->memberData['g_perm_id'] );
				}
				else
				{
					$member_masks[]	= $this->memberData['g_perm_id'];
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Get data for a specific user
			//-----------------------------------------

			$no_update	= true;
			
			$member		= $this->DB->buildAndFetch( array( 'select'	=> 'member_group_id, org_perm_id, mgroup_others',
															'from'	=> 'members',
															'where'	=> 'member_id=' . intval($memid),
													)		);

			if( !$member['org_perm_id'] )
			{
				$checkGroups[] = $member['member_group_id'];
				
				if( $member['mgroup_others'] )
				{
					$checkGroups	= array_merge( $checkGroups, explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) ) );
				}
				
				foreach( $this->caches['group_cache'] as $gid => $masks )
				{
					if( in_array( $gid, $checkGroups ) )
					{
						$these_masks = array();

						if( strpos( $masks['g_perm_id'], "," ) )
						{
							$these_masks	= explode( ",", IPSText::cleanPermString( $masks['g_perm_id'] ) );
							$member_masks	= array_merge( $member_masks, $these_masks );
						}
						else
						{
							$member_masks[]	= $masks['g_perm_id'];
						}
					}
				}
			}
			else
			{
				$member_masks	= explode( ",", IPSText::cleanPermString( $member['org_perm_id']) );
			}
		}

		//-----------------------------------------
		// Load permission mapping class
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'gallery' ) . '/extensions/coreExtensions.php', 'galleryPermMappingCategories', 'gallery' );
		$permissions	= new $classToLoad();
		$mapping		= $permissions->getMapping();
		
		//-----------------------------------------
		// Loop over categories and set what we can do
		//-----------------------------------------

		foreach( $this->cat_lookup as $cid => $cinfo )
		{
			foreach( $mapping as $k => $v )
			{
				if( $cinfo[ $v ] == '*' )
				{
					$member_perms[ $k ][ $cid ]		= $cid;
				}
				else if( $cinfo[ $v ] )
				{
					$forum_masks	= explode( ",", IPSText::cleanPermString( $cinfo[ $v ] ) );
					
					foreach( $forum_masks as $mask_id )
					{
						if( in_array( $mask_id, $member_masks ) )
						{
							$member_perms[ $k ][ $cid ]		= $cid;
							break;
						}
					}
				}
			}

			//-----------------------------------------
			// If category does not allow direct image submissions,
			// just shut off the 'post image' permission
			//-----------------------------------------

			if ( $cinfo['category_type'] != 2 )
			{
				unset( $member_perms['post'][ $cinfo['category_id'] ] );
			}
		}
		
		foreach( $member_perms as $k => $v )
		{
			if( is_array( $member_perms[$k] ) )
			{
				$member_perms[$k] = array_unique($member_perms[$k]);
			}
		}
		
		if( !$no_update )
		{
			$this->member_access	= $member_perms;

			return $member_perms;
		}
		else
		{
			return $member_perms;
		}
	}

	/**
	 * Can user upload (anywhere)?
	 *
	 * @return	@e bool
	 */
	public function canUpload()
	{
		return ( count($this->member_access['post']) ) ? true : false;
	}
}

