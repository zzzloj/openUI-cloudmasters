<?php
/**
 * @file		generate.php 	Navigation plugin: downloads
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		3/28/2011
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		v2.5.4
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		navigation_downloads
 * @brief		Generate quick navigation for download manager
 */
class navigation_downloads
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
		
		ipsRegistry::getAppClass( 'downloads' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return IPSLib::getAppTitle( 'downloads' );
	}
	
	/**
	 * Returns navigation data
	 *
	 * @return	array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		$blocks	= array();
		$links	= $this->_getData();
			
		/* Add to blocks */
		$blocks[] = array( 'title' => '', 'links' => $links );
		
		return $blocks;
	}
	
	/**
	 * Fetches IDM category data
	 *
	 * @return	string
	 */
	private function _getData()
	{
		$depth_guide	= 0;
		$links			= array();
		
		if( is_array( $this->registry->categories->cat_cache[0] ) AND count( $this->registry->categories->cat_cache[0] ) )
		{
			foreach( $this->registry->categories->cat_cache[0] as $cats )
			{
				if ( $cats['copen'] AND in_array( $cats['cid'], $this->registry->categories->member_access['show'] ) )
				{
					$links[] = array( 'important' => true, 'depth' => $depth_guide, 'title' => $cats['cname'], 'url' => $this->registry->output->buildSEOUrl( 'app=downloads&amp;showcat=' . $cats['cid'], 'public', $cats['cname_furl'], 'idmshowcat' ) );
					
					if ( isset($this->registry->categories->cat_cache[ $cats['cid'] ]) AND is_array( $this->registry->categories->cat_cache[ $cats['cid'] ] ) )
					{
						$depth_guide++;
						
						foreach( $this->registry->categories->cat_cache[ $cats['cid'] ] as $cats )
						{
							if ( $cats['copen'] AND in_array( $cats['cid'], $this->registry->categories->member_access['show'] ) )
							{
								$links[] = array( 'depth' => $depth_guide, 'title' => $cats['cname'], 'url' => $this->registry->output->buildSEOUrl( 'app=downloads&amp;showcat=' . $cats['cid'], 'public', $cats['cname_furl'], 'idmshowcat' ) );
						
								$links = $this->_getDataRecursively( $cats['cid'], $links, $depth_guide );
							}
						}
						
						$depth_guide--;
					}
				}
			}
		}
		
		return $links;
	}
	
	/**
	 * Internal helper function for _getData()
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	string
	 */
	private function _getDataRecursively( $root_id, $links=array(), $depth_guide=0 )
	{
		if ( isset( $this->registry->categories->cat_cache[ $root_id ] ) AND is_array( $this->registry->categories->cat_cache[ $root_id ] ) )
		{
			$depth_guide++;
			
			foreach( $this->registry->categories->cat_cache[ $root_id ] as $cats )
			{
				if ( $cats['copen'] AND in_array( $cats['cid'], $this->registry->categories->member_access['show'] ) )
				{
					$links[] = array( 'depth' => $depth_guide, 'title' => $cats['cname'], 'url' => $this->registry->output->buildSEOUrl( 'app=downloads&amp;showcat=' . $cats['cid'], 'public', $cats['cname_furl'], 'idmshowcat' ) );
					
					$links = $this->_getDataRecursively( $cats['cid'], $links, $depth_guide );
				}
			}
		}
		
		
		return $links;
	}
}