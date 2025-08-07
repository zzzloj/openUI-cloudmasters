<?php
/**
 * @file		generate.php 	Navigation plugin: ccs
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		3/28/2011
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		v3.4.5
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		navigation_ccs
 * @brief		Generate quick navigation for IP.Content
 */
class navigation_ccs
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	

	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct() 
	{
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			=  $this->registry->class_localization;
		
		ipsRegistry::getAppClass( 'ccs' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return IPSLib::getAppTitle( 'ccs' );
	}
	
	/**
	 * Returns navigation data
	 *
	 * @return	@e array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		$blocks	= array();
		$links	= $this->_getPages();
	
		/* Add to blocks */
		$blocks[] = array( 'title' => IPSLib::getAppTitle( 'ccs' ), 'links' => $links );
		
		$cache	= $this->cache->getCache('ccs_databases');
		
		foreach( $cache as $database )
		{
			$blocks	= $this->_getDatabase( $database, $blocks );
		}
		
		return $blocks;
	}
	
	/**
	 * Fetches pages you can view in IP.Content
	 *
	 * @return	@e array
	 */
	private function _getPages()
	{
		$links	= array();

		$_query	= array(
						"page_content_type='page'",
						//"page_type IN('bbcode','html')",
						"page_quicknav=1",
						$this->DB->buildRegexp( 'page_view_perms', $this->member->perm_id_array )
						);

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => implode( $_query, ' AND ' ), 'order' => 'page_name' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			$links[] = array( 'important' => true, 'depth' => 0, 'title' => $r['page_name'], 'url' => $this->registry->ccsFunctions->returnPageUrl( $r ) );
		}
		
		return $links;
	}
	
	/**
	 * Fetches database data
	 *
	 * @param	array 	Database data
	 * @param	array	Existing blocks
	 * @return	@e array
	 */
	private function _getDatabase( $database, $blocks )
	{
		if ( $this->registry->permissions->check( 'view', $database ) != TRUE )
		{
			return $blocks;
		}
		
		$categories	= $this->registry->ccsFunctions->getCategoriesClass( $database );
		
		/* We could return the database, but that should be covered by the existing 'Pages' link */
		if( !count($categories) )
		{
			return $blocks;
		}
		
		$depth_guide	= 0;
		$links			= array();
		
		if( is_array( $categories->catcache[0] ) AND count( $categories->catcache[0] ) )
		{
			foreach( $categories->catcache[0] as $category )
			{
				if ( !$category['category_has_perms'] OR ( $category['category_has_perms'] AND $this->registry->permissions->check( 'view', $category ) == TRUE ) )
				{
					$links[] = array( 'important' => true, 'depth' => $depth_guide, 'title' => $category['category_name'], 'url' => $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], $category['category_id'] ) );
					
					if ( isset($categories->catcache[ $category['category_id'] ]) AND is_array( $categories->catcache[ $category['category_id'] ] ) )
					{
						$depth_guide++;
						
						foreach( $categories->catcache[ $category['category_id'] ] as $category )
						{
							$links[] = array( 'depth' => $depth_guide, 'title' => $category['category_name'], 'url' => $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], $category['category_id'] ) );
					
							$links = $this->_getDataRecursively( $category['category_id'], $links, $depth_guide );			
						}
						
						$depth_guide--;
					}
				}
			}
		}
		
		if( count($links) )
		{
			$blocks[]	= array( 'title' => $database['database_name'], 'links' => $links );
		}
		
		return $blocks;
	}
	
	/**
	 * Internal helper function for _getData()
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	@e array
	 */
	private function _getDataRecursively( $root_id, $links=array(), $depth_guide=0 )
	{
		$categories	= $this->registry->ccsFunctions->getCategoriesClass( $database );
		
		if ( isset( $categories->catcache[ $root_id ] ) AND is_array( $categories->catcache[ $root_id ] ) )
		{
			$depth_guide++;
			
			foreach( $categories->catcache[ $root_id ] as $category )
			{
				$links[] = array( 'depth' => $depth_guide, 'title' => $category['category_name'], 'url' => $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], $category['category_id'] ) );
				
				$links = $this->_getDataRecursively( $category['category_id'], $links, $depth_guide );
			}
		}

		return $links;
	}
}