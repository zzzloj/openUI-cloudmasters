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
 * Files cache controller
 * 
 * @package Tracker
 * @since 2.0.0
 */
class tracker_core_cache_files extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_files';
	/**
	 * The path to the tracker cache folder
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $cachePath;
	/**
	 * The files and their functions
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $files = array(
		'coreExtensions.php'       => 'cacheCoreExtensions',
		'coreVariables.php'        => 'cacheCoreVariables',
		'fieldsNavigation.php'     => 'cacheFieldsNavigation',
		'moderatorKeys.php'        => 'cacheModeratorKeys',
		'tracker_mysql_tables.php' => 'cacheMySQLTables',
	);
	/**
	 * The files cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $filesCache;

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
		$this->filesCache = $this->caches[ $this->cacheName ];

		$this->modules = $this->registry->tracker->modules()->getCache();

		$this->cachePath = DOC_IPS_ROOT_PATH . 'cache/tracker/';
	}

	/**
	 * Creates the full module cache of the coreExtensions.php file
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function cacheCoreExtensions()
	{
		$_date = gmdate( 'r', time() );
		$file = "<?php
/**
 * Core Extensions cache. Do not attempt to modify this file.
 * Please modify the relevant 'coreExtensions.php' file in /tracker/modules/{module}/extensions/coreExtensions.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 */
?>
";
		$path 	= $this->cachePath . 'coreExtensions.php';
		$out  	= $_date;
		$done	= array();

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				// Has this module already been inserted?
				if ( in_array( $mod['directory'], $done ) )
				{
					continue;
				}
				
				$in = $this->registry->tracker->modules()->getModuleFolder( $mod['directory'] ) . 'extensions/coreExtensions.php';

				if ( file_exists( $in ) )
				{
					$file .= @file_get_contents( $in );
				}
				
				// Add to done array
				$done[]	= $mod['directory'];
			}
		}

		if ( ! @file_put_contents( $path, $file ) )
		{
			$out = 'CANNOT_WRITE';
		}

		return $out;
	}

	/**
	 * Creates the full module cache of the coreVariables.php file
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function cacheCoreVariables()
	{
		$_date = gmdate( 'r', time() );
		$file = "<?php
/**
 * Core Variables cache. Do not attempt to modify this file.
 * Please modify the relevant 'coreVariables.php' file in /tracker/modules/{module}/extensions/coreVariables.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 */
?>
";
		$path = $this->cachePath . 'coreVariables.php';
		$out  = $_date;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				$in = $this->registry->tracker->modules()->getModuleFolder( $mod['directory'] ) . 'extensions/coreVariables.php';

				if ( file_exists( $in ) )
				{
					$file .= @file_get_contents( $in );
				}
			}
		}

		if ( ! @file_put_contents( $path, $file ) )
		{
			$out = 'CANNOT_WRITE';
		}

		return $out;
	}

	/**
	 * Creates the full module cache of the fieldsNavigation.php file
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function cacheFieldsNavigation()
	{
		$_date = gmdate( 'r', time() );
		$file = "<?php
/**
 * Fields Navigation cache. Do not attempt to modify this file.
 * Please modify the relevant 'fieldsNavigation.php' file in /tracker/modules/{module}/extensions/fieldsNavigation.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 */
?>
";
		$path = $this->cachePath . 'fieldsNavigation.php';
		$out  = $_date;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				$in = $this->registry->tracker->modules()->getModuleFolder( $mod['directory'] ) . 'extensions/fieldsNavigation.php';

				if ( file_exists( $in ) )
				{
					$file .= @file_get_contents( $in );
				}
			}
		}

		if ( ! @file_put_contents( $path, $file ) )
		{
			$out = 'CANNOT_WRITE';
		}

		return $out;
	}

	/**
	 * Creates the full module cache of the moderatorKeys.php file
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function cacheModeratorKeys()
	{
		$_date = gmdate( 'r', time() );
		$file = "<?php
/**
 * Moderator Keys cache. Do not attempt to modify this file.
 * Please modify the relevant 'moderatorKeys.php' file in /tracker/modules/{module}/extensions/moderatorKeys.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 */
?>
";
		$path = $this->cachePath . 'moderatorKeys.php';
		$out  = $_date;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				$in = $this->registry->tracker->modules()->getModuleFolder( $mod['directory'] ) . 'extensions/moderatorKeys.php';

				if ( file_exists( $in ) )
				{
					$file .= @file_get_contents( $in );
				}
			}
		}

		if ( ! @file_put_contents( $path, $file ) )
		{
			$out = 'CANNOT_WRITE';
		}

		return $out;
	}

	/**
	 * Creates the full module cache of the tracker_mysql_tables.php file
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function cacheMySQLTables()
	{
		$_date = gmdate( 'r', time() );
		$file = "<?php
/**
 * MySQL install cache. Do not attempt to modify this file.
 * Please modify the relevant '_mysql_tables.php' file in /tracker/modules/{module}/setup/install/sql/{module}_mysql_tables.php
 * and rebuild from the Admin CP
 *
 * Written: {$_date}
 */
?>
";
		$path = $this->cachePath . 'tracker_mysql_tables.php';
		$out  = $_date;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				$in = $this->registry->tracker->modules()->getModuleFolder( $mod['directory'] ) . "setup/install/sql/{$mod['directory']}_mysql_tables.php";

				if ( file_exists( $in ) )
				{
					$file .= @file_get_contents( $in );
				}
			}
		}

		if ( ! @file_put_contents( $path, $file ) )
		{
			$out = 'CANNOT_WRITE';
		}

		return $out;
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

		if ( is_array( $this->filesCache ) && count( $this->filesCache ) > 0 )
		{
			$out = $this->filesCache;
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
		$filesCache = array();

		$this->checkForRegistry();

		// Get the counts from the database
		if ( is_array( $this->files ) && count( $this->files ) > 0 )
		{
			foreach( $this->files as $file => $function )
			{
				$filesCache[ $file ] = $this->$function();
				
				if ( $filesCache[ $file ] == 'CANNOT_WRITE' )
				{
					return 'CANNOT_WRITE';
				}
			}
		}

		// Send the updated row to be cached
		$this->cache->setCache( $this->cacheName, $filesCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->filesCache = $filesCache;
	}
}

?>