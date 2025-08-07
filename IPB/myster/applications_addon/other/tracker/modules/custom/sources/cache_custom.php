<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Custom Fields field cache file
* Last Updated: $Date: 2010-12-18 18:26:47 +0000 (Sat, 18 Dec 2010) $
*
* @author		$Author: krocheck $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Custom Fields
* @link			http://ipbtracker.com
* @version		$Revision: 1146 $
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
 * Module: Custom Fields
 * Field: Custom Fields
 * Custom Fields field cache controller
 * 
 * @package Tracker
 * @subpackage Module-Custom Fields
 * @since 2.0.0
 */
class tracker_module_custom_cache_custom extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_module_custom';
	/**
	 * The processed fields cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $fieldsCache;

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
		$this->fieldsCache = $this->caches[ $this->cacheName ];
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

		if ( is_array( $this->fieldsCache ) && count( $this->fieldsCache ) > 0 )
		{
			foreach( $this->fieldsCache as $id => $data )
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

		if ( is_array( $this->fieldsCache ) && count( $this->fieldsCache ) > 0 )
		{
			$out = $this->fieldsCache;
		}

		return $out;
	}

	/**
	 * Grab the fields and generate the cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$fieldsCache = array();

		$this->checkForRegistry();

		// Load the fields in order by position
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_custom',
				'order'  => 'position ASC'
			)
		);
		$fields = $this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows($fields) )
		{
			while ( $r = $this->DB->fetch($fields) )
			{
				$r['options'] = array();

				// Add to cache
				$fieldsCache[ $r['field_id'] ] = $r;
			}
		}


		// Fetch all dropdown options while we're here.
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_custom_option',
				'order'  => 'position ASC'
			)
		);
		$options = $this->DB->execute();

		while( $r = $this->DB->fetch($options) )
		{
			$fieldsCache[ $r['field_id'] ]['options'][ $r['key_value'] ] = $r;
		}
		
		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $fieldsCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->fieldsCache = $fieldsCache;
	}
}

?>