<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Severity field cache file
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
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
 * Module: Severity
 * Field: Severity
 * Severity field cache controller
 * 
 * @package Tracker
 * @subpackage Module-Severity
 * @since 2.0.0
 */
class tracker_module_severity_cache_severity extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_module_severity';
	/**
	 * The processed statuses cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $severityCache;

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
		$this->caches =& $this->registry->cache()->fetchCaches();
		$this->cache->getCache( $this->cacheName );
		$this->severityCache = $this->caches[ $this->cacheName ];
	}

	/**
	 * Returns the processed cache
	 *
	 * @return array the cache
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		$out = array();

		if ( is_array( $this->severityCache ) && count( $this->severityCache ) > 0 )
		{
			$out = $this->severityCache;
		}

		return $out;
	}

	/**
	 * Grab the severities and create a fresh cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$severityCache = array();

		// Load the severities
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_severity'
			)
		);
		$this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$severityCache[ 'skin'.$r['skin_id'] ][ $r['severity_id'] ]['background_color'] = $r['background_color'];
				$severityCache[ 'skin'.$r['skin_id'] ][ $r['severity_id'] ]['font_color']       = $r['font_color'];
			}
		}
		else
		{
			foreach( $this->caches['skinsets'] as $k => $v )
			{
				/* Default data */
				$severityCache[ 'skin'.$k ][1]['background_color']	= '#CAECAF';
				$severityCache[ 'skin'.$k ][1]['font_color']		= '#000000';
				$severityCache[ 'skin'.$k ][2]['background_color']	= '#F0F3A4';
				$severityCache[ 'skin'.$k ][2]['font_color']		= '#000000';
				$severityCache[ 'skin'.$k ][3]['background_color']	= '#F5D484';
				$severityCache[ 'skin'.$k ][3]['font_color']		= '#000000';
				$severityCache[ 'skin'.$k ][4]['background_color']	= '#F5B984';
				$severityCache[ 'skin'.$k ][4]['font_color']		= '#000000';
				$severityCache[ 'skin'.$k ][5]['background_color']	= '#B93B3B';
				$severityCache[ 'skin'.$k ][5]['font_color']		= '#FFFFFF';
			
				foreach( $severityCache[ 'skin' . $k ] as $a => $b )
				{
					$save = array( 'background_color' => $b['background_color'], 'font_color' => $b['font_color'], 'skin_id' => $k, 'severity_id' => $a );
					
					$this->DB->insert('tracker_module_severity', $save );
				}
			}
		}

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $severityCache, array( 'array' => 1, 'deletefirst' => 1 ) );
	
		// Loop through files
		foreach( $severityCache as $k => $v )
		{
			$path		= DOC_IPS_ROOT_PATH . 'cache/tracker/' . $k . '_severity.css';
			$content	= '';
			
			foreach( $v as $a => $b )
			{
				$content .= <<<EOF
.severity_{$a} { background-color: {$b['background_color']} !important; color: {$b['font_color']} !important; }

EOF;
			}
			
			if ( ! @file_put_contents( $path, $content ) )
			{
				return 'CANNOT_WRITE';
			}
		}

		// Set in the new cache
		$this->severityCache = $severityCache;
	}
}

?>