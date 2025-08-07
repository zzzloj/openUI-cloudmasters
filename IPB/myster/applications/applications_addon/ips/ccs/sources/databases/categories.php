<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * <pre>
 * Invision Power Services
 * IP.CCS categories class
 * Last Updated: $Date: 2012-01-31 15:33:40 -0500 (Tue, 31 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10223 $
 */
 
class ccs_categories
{
	/**
	 * Cache of categories, structured by parent id
	 *
	 * @access	public
	 * @var		array
	 */
	public $catcache		= array();

	/**
	 * Cache of categories, mapped by id
	 *
	 * @access	public
	 * @var		array
	 */
	public $categories		= array();
	
	/**
	 * Hide categories you don't have access to
	 *
	 * @access	public
	 * @var		bool
	 */
	public $hide			= true;
	
	/**
	 * Has some categories (regardless of view perms)
	 *
	 * @access	public
	 * @var		bool
	 */
	public $hasCategories	= false;
	
	/**
	 * Cache of database data
	 *
	 * @access	public
	 * @var		array
	 */
	public $database		= array();
	
	/**
	 * Depth guide
	 *
	 * @access	public
	 * @var		string
	 */
	public $depth_guide		= "--";

	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	protected $memberData;
	protected $caches;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Grab all categories and format into a proper array
	 *
	 * @access	public
	 * @param	array 		Database info
	 * @return	@e void
	 */
	public function init( $database )
	{
		/* Query category data */
		$categories = $this->getCategories( $database['database_id'] );

		$this->catcache		= array();
		$this->categories	= array();
		$this->database		= $database;
		
		$hide_parents		= ',';

		foreach( $categories as $cat )
		{
			/* Automatically fix missing category furl names */
			if( !$cat['category_furl_name'] )
			{
				$cat	= $this->fixCategoryFurl( $cat, $categories );
			}

			/* Set the parent id for root categories */
			if( $cat['category_parent_id'] < 1 )
			{
				$cat['category_parent_id'] = 0;
			}
			
			if( $this->hide AND $cat['category_has_perms'] )
			{
				/* Don't show any children of hidden parents */
				if( strstr( $hide_parents, ',' . $cat['category_parent_id'] . ',' ) )
				{
					$hide_parents .= $cat['category_id'] . ',';
					continue;
				}
				
				/* Don't show categories that we do not have view permissions for */
				if ( $this->registry->permissions->check( 'view', $cat ) != TRUE )
				{
					$hide_parents .= $cat['category_id'] . ',';
					continue;
				}
			}

			/* Store the category arrays */
			$this->catcache[ $cat['category_parent_id'] ][ $cat['category_id'] ] = $cat;
			$this->categories[ $cat['category_id'] ] = $this->catcache[ $cat['category_parent_id'] ][ $cat['category_id'] ];
		}

		/* Now apply stats - calcCategory requires catcache to already be set */
		foreach( $this->categories as $k => $cat )
		{
			$cat	= $this->calcCategory( $cat['category_id'], $cat );
			
			/* Update category arrays */
			$this->catcache[ $cat['category_parent_id'] ][ $cat['category_id'] ] = $cat;
			$this->categories[ $cat['category_id'] ] = $this->catcache[ $cat['category_parent_id'] ][ $cat['category_id'] ];
		}
	}
	
	/**
	 * Reset the "root" to be a specific category
	 *
	 * @access	public
	 * @param	int			Category id
	 * @return	bool
	 */
	public function resetRootCategory( $category )
	{
		if( !isset( $this->categories[ $category ] ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// First, change $this->categories
		//-----------------------------------------
		
		$_parentId	= $this->categories[ $category ]['category_parent_id'];
		
		$this->categories[ $category ]['category_parent_id']	= 0;
		
		//-----------------------------------------
		// Then fix $this->catcache
		//-----------------------------------------
		
		unset($this->catcache[ $_parentId ][ $category ]);
		
		$this->catcache[ 0 ][ $category ] = $this->categories[ $category ];
		
		//-----------------------------------------
		// And clear all other categories...
		//-----------------------------------------
		
		$_children	= $this->getChildren( $category );
		
		foreach( $this->categories as $_catId => $_catData )
		{
			if( $_catId != $category AND !in_array( $_catId, $_children ) )
			{
				unset( $this->categories[ $_catId ] );
			}
		}
		
		foreach( $this->catcache[0] as $_catId => $_catData )
		{
			if( $_catId != $category )
			{
				unset( $this->catcache[0][ $_catId ] );
			}
		}
		
		foreach( $this->catcache as $_catParent => $_catId )
		{
			if( $_catParent > 0 AND $_catParent != $category AND !in_array( $_catParent, $_children ) )
			{
				unset( $this->catcache[ $_catParent ] );
			}
		}
		
		return true;
	}
	
	/**
	 * Get category url (handles furls)
	 *
	 * @access	public
	 * @param	string	Base URL
	 * @param	int		Category id
	 * @param	bool	Prepare URL to add more params
	 * @return	string	Category url
	 */
	public function getCategoryUrl( $baseUrl, $id, $more=false )
	{
		$category	= $this->getCategory( $id );
		
		if( $category[ '_categoryUrl_' . md5($baseUrl . $more) ] AND !$this->registry->ccsFunctions->fetchFreshUrl )
		{
			return $category[ '_categoryUrl_' . md5($baseUrl . $more) ];
		}
		
		if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
		{
			$category[ '_categoryUrl_' . md5($baseUrl . $more) ]	= $baseUrl . 'category=' . $category['category_id'] . ( $more ? '&amp;' : '' );
			
			return $baseUrl . 'category=' . $category['category_id'] . ( $more ? '&amp;' : '' );
		}
		else
		{
			if( $category['category_parent_id'] )
			{
				$_url		= substr( $baseUrl, 0, -1 ) . '/' . DATABASE_FURL_MARKER . '/';
				$_url		= str_replace( '//' . DATABASE_FURL_MARKER . '/', '/' . DATABASE_FURL_MARKER . '/', $_url );
				
				$parents	= array_reverse( $this->getParents( $id ) );
				
				foreach( $parents as $_parent )
				{
					$_parentCat	= $this->getCategory( $_parent );
					
					$_url		.= $_parentCat['category_furl_name'] . '/';
				}
				
				$_url		.= $category['category_furl_name'] . '/' . ( $more ? '?' : '' );
				
				$category[ '_categoryUrl_' . md5($baseUrl . $more) ]	= $_url;
				
				return $_url;
			}
			else
			{
				$category[ '_categoryUrl_' . md5($baseUrl . $more) ]	= ( substr( $baseUrl, 0, -1 ) . '/' . DATABASE_FURL_MARKER . '/' . $category['category_furl_name'] . '/' . ( $more ? '?' : '' ) );
				
				return ( str_replace( '//' . DATABASE_FURL_MARKER . '/', '/' . DATABASE_FURL_MARKER . '/', substr( $baseUrl, 0, -1 ) . '/' . DATABASE_FURL_MARKER . '/' . $category['category_furl_name'] . '/' . ( $more ? '?' : '' ) ) );
			}
		}
	}
	
	/**
	 * Returns a list of all categories
	 *
	 * @access	public
	 * @param	int			Database ID
	 * @return	array
	 */
	public function getCategories( $database=0 )
	{
		if( !$database )
		{
			return array();
		}
		
		/* Get the cats */			
		$this->DB->build( array(
								'select'	=> 'c.*, c.category_id as catid',
								'from'		=> array( 'ccs_database_categories' => 'c' ), 
								'where'		=> 'c.category_database_id=' . $database,
								'add_join'	=> array(
													array(
														'select'	=> 'r.*',
														'from'		=> array( 'ccs_custom_database_' . $database => 'r' ),
														'where'		=> 'r.primary_id_field=c.category_last_record_id AND r.record_approved=1',
														'type'		=> 'left'
														),
													array(
														'select' => 'p.*',
														'from'   => array( 'permission_index' => 'p' ),
														'where'  => "p.perm_type='categories' AND p.app='ccs' AND p.perm_type_id=c.category_id",
														'type'   => 'left',
														),
													$this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => 'c.category_id' ), 'ccs' ),
													)
						)		);
		$q = $this->DB->execute();
		
		/* Loop through and build an array of cats */
		$categories		= array();
		$tempCats		= array();
		
		while( $f = $this->DB->fetch( $q ) )
		{
			$f['category_id']	= $f['catid'];
			$f					= $this->registry->classItemMarking->setFromSqlJoin( $f, 'ccs' );

			$f['_category_records']					= $f['category_records'];
			$f['_category_records_queued']			= $f['category_records_queued'];
			$f['_category_record_comments']			= $f['category_record_comments'];
			$f['_category_record_comments_queued']	= $f['category_record_comments_queued'];

			$tempCats[ $f['category_parent_id'] . '.' . $f['category_position'] . '.' . $f['category_id'] ] = $f;
		}

		/* Sort in PHP */
		$tempCats = IPSLib::knatsort( $tempCats );
		
		foreach( $tempCats as $posData => $f )
		{
			$f		= array_merge( $f, $this->registry->permissions->parse( $f ) );

			$categories[ $f['category_id'] ] = $f;
		}
		
		if( count($categories) )
		{
			$this->hasCategories	= true;
		}

		return $categories;
	}

	/**
	 * Fetch category data
	 *
	 * @access	public
	 * @param	int			Category ID
	 * @return	array 		Category Data
	 */
	public function getCategory( $id )
	{
		return $this->categories[ $id ];
	}

	/**
	 * Get category parents
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 */
	public function getParents( $root_id, $ids=array() )
	{
		if ( $this->categories[ $root_id ]['category_parent_id'] )
		{
			$ids[] = $this->categories[ $root_id ]['category_parent_id'];
			
			// Stop endless loop setting cat as it's own parent?
			if ( in_array( $root_id, $ids ) )
			{
				//return $ids;
			}
			
			$ids = $this->getParents( $this->categories[ $root_id ]['category_parent_id'], $ids );
		}
	
		return $ids;
	}

	/**
	 * Get category children
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 */
	public function getChildren( $root_id, $ids=array() )
	{
		if ( isset( $this->catcache[ $root_id ]) AND is_array( $this->catcache[ $root_id ] ) )
		{
			foreach( $this->catcache[ $root_id ] as $data )
			{
				$ids[] = $data['category_id'];
				
				$ids = $this->getChildren( $data['category_id'], $ids );
			}
		}
		
		return $ids;
	}
	
	/**
	 * Gets cumulative record count
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$category
	 * @param	bool	$done_pass
	 * @return	array
	 */
	public function calcCategory( $root_id, $category=array(), $done_pass=0 )
	{
		//-----------------------------------------
		// Markers
		//-----------------------------------------

		if( !$this->registry->isClassLoaded('classItemMarking') )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php', 'classItemMarking' );
			$this->registry->setClass( 'classItemMarking', new $classToLoad( $this->registry ) );
		}

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'catID' => $category['category_id'], 'itemLastUpdate' => $category['category_last_record_date'], 'databaseID' => $category['category_database_id'] ), 'ccs' );

		if( !isset($category['_has_unread']) )
		{
			$category['_has_unread'] = ( $category['category_last_record_date'] && $category['category_last_record_date'] > $rtime ) ? 1 : 0;
		}

		if ( isset( $this->catcache[ $root_id ]) AND is_array( $this->catcache[ $root_id ] ) )
		{
			foreach( $this->catcache[ $root_id ] as $data )
			{
				if ( $data['category_last_record_date'] > $category['category_last_record_date'] )
				{
					$category['category_last_record_id']		= $data['category_last_record_id'];
					$category['category_last_record_date']		= $data['category_last_record_date'];
					$category['category_last_record_member']	= $data['category_last_record_member'];
					$category['category_last_record_name']		= $data['category_last_record_name'];
					$category['category_last_record_seo_name']	= $data['category_last_record_seo_name'];
					$category['category_last_record_cat']		= $data['category_id'];
					
					foreach( array_keys($data) as $key )
					{
						//if( !preg_match( "/^category_(.+?)$/", $key ) AND !preg_match( "/^perm.+?/", $key ) )
						if( strpos( $key, "category_" ) !== 0 AND strpos( $key, "perm" ) !== 0 AND substr( $key, 0, 1 ) !== '_' )
						{
							$category[ $key ]	= $data[ $key ];
						}
					}
					
					$category['_has_unread']					= 0;
				}
				
				//-----------------------------------------
				// Markers.  We never set false from inside loop.
				//-----------------------------------------
				
				$rtime					= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'catID' => $data['category_id'], 'itemLastUpdate' => $data['category_last_record_date'], 'databaseID' => $data['category_database_id'] ), 'ccs' );
				$data['_has_unread']	= 0;
				
				if( $data['category_last_record_date'] && $data['category_last_record_date'] > $rtime )
				{
					$category['_has_unread']		= 1;
					$data['_has_unread']			= 1;
				}

				//-----------------------------------------
				// Records, comments
				//-----------------------------------------
				
				$category['category_records']					+= $data['category_records'];
				$category['category_records_queued']			+= $data['category_records_queued'];
				$category['category_record_comments']			+= $data['category_record_comments'];
				$category['category_record_comments_queued']	+= $data['category_record_comments_queued'];

				if ( ! $done_pass )
				{
					$category['subcats'][ $data['category_id'] ] = array( $data['category_id'], $data['category_name'], intval($data['_has_unread']) );
				}
				
				$category = $this->calcCategory( $data['category_id'], $category, 1 );
			}
		}

		//-----------------------------------------
		// Sort out image
		//-----------------------------------------

        $category['image']	= $category['_has_unread'] ? "f_icon" : "f_icon_read";

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $category;
	}
	
	/**
	 * Create breadcrumbs
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	string	$url
	 * @return	array
	 */
	public function getBreadcrumbs($root_id, $url='' )
	{
		if( !$url )
		{
			return false;
		}
		
		$nav_array[] = array( $this->categories[$root_id]['category_name'], $this->getCategoryUrl( $url, $root_id ) );
	
		$ids = $this->getParents( $root_id );
		
		if ( is_array($ids) and count($ids) )
		{
			foreach( $ids as $id )
			{
				$data = $this->categories[$id];
				
				$nav_array[] = array( $data['category_name'], $this->getCategoryUrl( $url, $data['category_id'] ) );
			}
		}
	
		return array_reverse( $nav_array );
	}
	
	/**
	 * Get categories in a single-level array with depth information.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getCategoriesWithDepth()
	{
		$_return	= array();

		if( is_array( $this->catcache[0] ) AND count( $this->catcache[0] ) )
		{
			foreach( $this->catcache[0] as $data )
			{
				$depth_guide	= 0;
				$_return[]		= array(
										'category'		=> $data,
										'level'			=> $depth_guide,
										);

				if ( isset($this->catcache[ $data['category_id'] ]) AND is_array( $this->catcache[ $data['category_id'] ] ) )
				{
					$depth_guide	= 1;
					
					foreach( $this->catcache[ $data['category_id'] ] as $data )
					{
						$_return[]		= array(
												'category'		=> $data,
												'level'			=> $depth_guide,
												);

						$_return = $this->_getCategoriesWithDepth( $data['category_id'], $_return, $depth_guide );
					}
					
					$depth_guide	= 0;
				}
			}
		}
		
		return $_return;
	}
	
	/**
	 * Get categories in a single-level array with depth information.
	 *
	 * @access	public
	 * @return	array
	 */
	public function _getCategoriesWithDepth( $catid, $_return, $depth_guide )
	{
		if( is_array( $this->catcache[$catid] ) AND count( $this->catcache[$catid] ) )
		{
			$depth_guide	= $depth_guide + 1;
			
			foreach( $this->catcache[$catid] as $data )
			{
				$_return[]		= array(
										'category'		=> $data,
										'level'			=> $depth_guide,
										);

				if ( isset($this->catcache[ $data['category_id'] ]) AND is_array( $this->catcache[ $data['category_id'] ] ) )
				{
					foreach( $this->catcache[ $data['category_id'] ] as $_data )
					{
						$_return[]		= array(
												'category'		=> $_data,
												'level'			=> $depth_guide,
												);
												
						$_return = $this->_getCategoriesWithDepth( $_data['category_id'], $_return, $depth_guide );
					}
				}
			}
		}
		
		return $_return;
	}
	
	/**
	 * Get a jump menu
	 *
	 * @access	public
	 * @param	array 	Category ids to omit
	 * @param	string	Permission key to check against
	 * @return	string
	 */
	public function getSelectMenu( $omit=array(), $permissionKey='' )
	{
		$jump_string = "";
		
		if( is_array( $this->catcache[0] ) AND count( $this->catcache[0] ) )
		{
			foreach( $this->catcache[0] as $data )
			{
				if( in_array( $data['category_id'], $omit ) )
				{
					continue;
				}

				$selected	= '';
				
				if( $this->request['category'] AND is_string($this->request['category']) AND $this->request['category'] == $data['category_id'] )
				{
					$selected = ' selected="selected"';
				}
				else if( $this->request['category'] AND is_array($this->request['category']) AND in_array( $data['category_id'], $this->request['category'] ) )
				{
					$selected = ' selected="selected"';
				}
				
				if( $permissionKey AND $data['category_has_perms'] AND $this->registry->permissions->check( $permissionKey, $data ) != TRUE )
				{
					$selected = ' disabled="disabled"';
				}

				$jump_string .= "<option value='{$data['category_id']}'" . $selected . ">" . $data['category_name']."</option>\n";
					
				$depth_guide = $this->depth_guide;
					
				if ( isset($this->catcache[ $data['category_id'] ]) AND is_array( $this->catcache[ $data['category_id'] ] ) )
				{
					foreach( $this->catcache[ $data['category_id'] ] as $data )
					{
						$selected = "";
						
						if ( $this->request['category'] AND is_string($this->request['category']) AND $this->request['category'] == $data['category_id'])
						{
							$selected = ' selected="selected"';
						}
						else if( $this->request['category'] AND is_array($this->request['category']) AND in_array( $data['category_id'], $this->request['category'] ) )
						{
							$selected = ' selected="selected"';
						}

						if( $permissionKey AND $data['category_has_perms'] AND $this->registry->permissions->check( $permissionKey, $data ) != TRUE )
						{
							$selected = ' disabled="disabled"';
						}
						
						$jump_string .= "<option value='{$data['category_id']}'" . $selected . ">&nbsp;&nbsp;&#0124;" . $depth_guide . " " . $data['category_name'] . "</option>\n";
						
						$jump_string = $this->_getSelectMenu( $data['category_id'], $jump_string, $depth_guide . $this->depth_guide, $omit, $permissionKey );
					}
				}
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Internal helper function for getSelectMenu
	 *
	 * @access	protected
	 * @param	integer	$root_id
	 * @param	string	$jump_string
	 * @param	string	$depth_guide
	 * @param	array 	$omit
	 * @param	string	Permission key to check against
	 * @return	string
	 */
	protected function _getSelectMenu( $root_id, $jump_string="", $depth_guide="", $omit=array(), $permissionKey='' )
	{
		if ( isset($this->catcache[ $root_id ]) AND is_array( $this->catcache[ $root_id ] ) )
		{
			foreach( $this->catcache[ $root_id ] as $data )
			{
				if( in_array( $data['category_id'], $omit ) )
				{
					continue;
				}

				$selected = "";
								
				if( $this->request['category'] AND is_string($this->request['category']) AND $this->request['category'] == $data['category_id'] )
				{
					$selected = ' selected="selected"';
				}
				else if( $this->request['category'] AND is_array($this->request['category']) AND in_array( $data['category_id'], $this->request['category'] ) )
				{
					$selected = ' selected="selected"';
				}

				if( $permissionKey AND $data['category_has_perms'] AND $this->registry->permissions->check( $permissionKey, $data ) != TRUE )
				{
					$selected = ' disabled="disabled"';
				}
					
				$jump_string .= "<option value='{$data['category_id']}'" . $selected . ">&nbsp;&nbsp;&#0124;" . $depth_guide . " " . $data['category_name'] . "</option>\n";
				
				$jump_string = $this->_getSelectMenu( $data['category_id'], $jump_string, $depth_guide . $this->depth_guide );
			}
		}

		return $jump_string;
	}

	/**
	 * Locate the top parent id of any category
	 *
	 * @access	public
	 * @param	int		Category ID
	 * @return	int		Category ID (root cat ID)
	 */
	public function fetchTopParentID( $id )
	{
		$ids = $this->getParents( $id );
	
		return array_pop( $ids );
	}

	/**
	 * Recache a category
	 *
	 * @access	public
	 * @param	int		Category ID
	 * @return	@e void
	 */
	public function recache( $category=0 )
	{
		$_categories	= array();
		$category		= intval($category);
		
		if( !$category )
		{
			$_categories	= array_keys($this->categories);
		}
		else
		{
			$_categories[]	= $category;
		}

		foreach( $_categories as $_cat )
		{
			$_category	= $this->categories[ $_cat ];
			
			if( !$_category['category_database_id'] )
			{
				continue;
			}

			$_update	= array(
								'category_records'					=> 0,
								'category_records_queued'			=> 0,
								'category_record_comments'			=> 0,
								'category_record_comments_queued'	=> 0,
								'category_last_record_id'			=> 0,
								'category_last_record_date'			=> 0,
								'category_last_record_member'		=> 0,
								'category_last_record_name'			=> '',
								'category_last_record_seo_name'		=> '',
								'category_rss_cache'				=> null,
								'category_rss_cached'				=> 0,
								);
	
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
			
			$count		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_custom_database_' . $_category['category_database_id'], 'where' => 'record_approved=0 AND category_id=' . $_cat ) );
			
			$_update['category_records_queued']			= intval($count['total']);
			
			$approved	= $this->DB->buildAndFetch( array( 'select' => 'SUM(record_comments) as total', 'from' => 'ccs_custom_database_' . $_category['category_database_id'], 'where' => "category_id=" . $_cat ) );
			$unapproved	= $this->DB->buildAndFetch( array( 'select' => 'SUM(record_comments_queued) as total', 'from' => 'ccs_custom_database_' . $_category['category_database_id'], 'where' => "category_id=" . $_cat ) );
			
			$_update['category_record_comments']		= intval($approved['total']);
			$_update['category_record_comments_queued']	= intval($unapproved['total']);

			$this->DB->update( 'ccs_database_categories', $_update, 'category_id=' . $_cat );
			
			$this->categories[ $_cat ]	= array_merge( $this->categories[ $_cat ], $_update );
		}

		$this->DB->update( 'ccs_databases', array( 'database_rss_cache' => null, 'database_rss_cached' => 0 ) );
		$this->cache->rebuildCache( 'rss_output_cache', 'global' );
		
		return;
	}
	
	/**
	 * Automatically sets a category furl name
	 *
	 * @access	public
	 * @param	array 		Category data
	 * @param	array 		All categories
	 * @return	array 		New category data
	 */
	public function fixCategoryFurl( $cat, $categories )
	{
		//-----------------------------------------
		// Already got?
		//-----------------------------------------
		
		if( $cat['category_furl_name'] )
		{
			return $cat;
		}
		
		//-----------------------------------------
		// Try normal routine on category name
		//-----------------------------------------
		
		$furl	= IPSText::makeSeoTitle( $cat['category_name'] );
		
		foreach( $categories as $_category )
		{
			//-----------------------------------------
			// If it's a duplicate, append random string
			//-----------------------------------------
			
			if( $furl == $_category['category_furl_name'] )
			{
				$furl	.= substr( md5( uniqid( microtime(), true ) ), 0, 5 );
			}
		}
		
		//-----------------------------------------
		// Update and return
		//-----------------------------------------
		
		$this->DB->update( 'ccs_database_categories', array( 'category_furl_name' => $furl ), 'category_id=' . $cat['category_id'] );
		
		$cat['category_furl_name']	= $furl;
		
		return $cat;
	}
}