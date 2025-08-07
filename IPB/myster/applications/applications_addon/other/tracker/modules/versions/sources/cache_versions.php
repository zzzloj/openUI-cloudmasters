<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Versions field cache file
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
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
 * Module: Versions
 * Field: Versions
 * Versions field cache controller
 * 
 * @package Tracker
 * @subpackage Module-Versions
 * @since 2.0.0
 */
class tracker_module_versions_cache_versions extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_module_versions';
	/**
	 * The processed versionses cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $versionsCache;

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
		$this->versionsCache = $this->caches[ $this->cacheName ];
	}

	/**
	 * Returns the id for the default versions if one exists
	 *
	 * @return int|null found default versions ID
	 * @access public
	 * @since 2.0.0
	 */
	public function findDefaultID()
	{
		$out = NULL;

		if ( is_array( $this->versionsCache ) && count( $this->versionsCache ) > 0 )
		{
			foreach( $this->versionsCache as $id => $data )
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

		if ( is_array( $this->versionsCache ) && count( $this->versionsCache ) > 0 )
		{
			$out = $this->versionsCache;
		}

		return $out;
	}

	/**
	 * Grab the versionses and generate the cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$versionsCache = array();

		$this->checkForRegistry();

		// Load the versionses in order by position
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_version',
				'order'  => 'position ASC',
			)
		);
		$outer = $this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows($outer) )
		{
			while ( $r = $this->DB->fetch($outer) )
			{
				// Add to cache and initialize counts array
				$versionsCache[ $r['version_id'] ] = $r;
			}
		}
		
		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $versionsCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->versionsCache = $versionsCache;
	}
}

?>