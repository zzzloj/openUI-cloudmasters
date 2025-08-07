<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Status field cache file
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Status
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}
// Have to load the cache if it isn't already
if ( ! class_exists('tracker_cache') )
{
	require_once( IPSLib::getAppDir('tracker') . '/sources/classes/library.php' );
}

/**
 * Type: Source
 * Module: Status
 * Field: Status
 * Status field cache controller
 * 
 * @package Tracker
 * @subpackage Module-Status
 * @since 2.0.0
 */
class tracker_module_status_cache_status extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_module_status';
	/**
	 * The processed statuses cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $statusCache;

	/**
	 * Initial function.  Called by execute function in iptCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache->getCache( $this->cacheName );
		$this->statusCache = $this->caches[ $this->cacheName ];
	}

	/**
	 * Returns the id for the default status if one exists
	 *
	 * @return int|null found default status ID
	 * @access public
	 * @since 2.0.0
	 */
	public function findDefaultID()
	{
		$out = NULL;

		if ( is_array( $this->statusCache ) && count( $this->statusCache ) > 0 )
		{
			foreach( $this->statusCache as $id => $data )
			{
				if ( $data['default'] )
				{
					$out = $id;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Returns the processed cache
	 *
	 * @return array the cache with post-DB processing
	 * @access public
	 * @since 2.0.0
	 */
	 public function getCache()
	{
		$out = array();

		if ( is_array( $this->statusCache ) && count( $this->statusCache ) > 0 )
		{
			$out = $this->statusCache;
		}

		return $out;
	}

	/**
	 * Grab the statuses and generate the cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$statusCache = array();

		$this->checkForRegistry();

		// Load the statuses in order by position
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_status',
				'order'  => 'position ASC',
			)
		);
		$out = $this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows( $out ) )
		{
			while ( $r = $this->DB->fetch( $out ) )
			{
				// Add to cache and initialize counts array
				$id = $r['status_id'];

				$statusCache[ $id ] = $r;
			}
		}
		
		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $statusCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->statusCache = $statusCache;
	}
}

?>