<?php

/**
* Tracker 2.1.0
* 
* Core cache controller classes file
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
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
 * Stats cache controller
 * 
 * @package Tracker
 * @since 2.0.0
 */
class tracker_core_cache_stats extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_stats';
	/**
	 * The stats cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $statsCache;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
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
		$this->statsCache = $this->caches[ $this->cacheName ];
	}

	/**
	 * Returns the cache
	 *
	 * @return array the cache
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		$out = array();

		if ( is_array( $this->statsCache ) && count( $this->statsCache ) > 0 )
		{
			$out = $this->statsCache;
		}

		return $out;
	}

	/**
	 * Returns the issue count
	 *
	 * @return int the issue count
	 * @access public
	 * @since 2.0.0
	 */
	public function getIssues()
	{
		$out = 0;

		if ( is_array( $this->statsCache ) && isset( $this->statsCache['issues'] ) )
		{
			$out = $this->statsCache['issues'];
		}

		return $out;
	}

	/**
	 * Returns the post count
	 *
	 * @return int the post count
	 * @access public
	 * @since 2.0.0
	 */
	public function getPosts()
	{
		$out = 0;

		if ( is_array( $this->statsCache ) && isset( $this->statsCache['posts'] ) )
		{
			$out = $this->statsCache['posts'];
		}

		return $out;
	}

	/**
	 * Returns the project count
	 *
	 * @return int the project count
	 * @access public
	 * @since 2.0.0
	 */
	public function getProjects()
	{
		$out = 0;

		if ( is_array( $this->statsCache ) && isset( $this->statsCache['projects'] ) )
		{
			$out = $this->statsCache['projects'];
		}

		return $out;
	}

	/**
	 * Reassign each status's row values and renumber the position from scratch
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$statsCache = array();

		$this->checkForRegistry();

		// Get the counts from the database
		$projects = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt', 'from' => 'tracker_projects' ) );
		$issues   = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt', 'from' => 'tracker_issues'   ) );
		$posts    = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt', 'from' => 'tracker_posts'    ) );

		// Set the new values
		$statsCache['projects'] = $projects['cnt'];
		$statsCache['issues']   = $issues['cnt'];
		$statsCache['posts']    = $posts['cnt'];

		// Send the updated row to be cached
		$this->cache->setCache( $this->cacheName, $statsCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->statsCache = $statsCache;
	}

	/**
	 * Use the parameters to modify the cached stat values
	 *
	 * @param int $projects modifier for project count
	 * @param int $issues modifier for issue count
	 * @param int $posts modifier for post count
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function update( $projects, $issues, $posts )
	{
		$this->statsCache['projects'] += $projects;
		$this->statsCache['issues']   += $issues;
		$this->statsCache['posts']    += $posts;

		$this->cache->setCache( $this->cacheName, $this->statsCache, array( 'array' => 1, 'deletefirst' => 1 ) );
	}

	/**
	 * Shortcut to update only the issue count
	 *
	 * @param int $change modifier for issue count
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function updateIssues( $change )
	{
		$this->update( 0, $change, 0 );
	}

	/**
	 * Shortcut to update only the post count
	 *
	 * @param int $change modifier for post count
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function updatePosts( $change )
	{
		$this->update( 0, 0, $change );
	}

	/**
	 * Shortcut to update only the project count
	 *
	 * @param int $change modifier for project count
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function updateProjects( $change )
	{
		$this->update( $change, 0, 0 );
	}
}

?>