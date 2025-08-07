<?php

/**
* Tracker 2.1.0
* 
* Facebook Library file
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

/**
 * Facebook controller class
 * 
 * @package Tracker
 * @since 2.0.0
 */
class tracker_core_facebook extends iptCommand
{
	/**
	 * Contains data which has not be saved to the cache yet
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $temporaryData;

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
		
	}

	/**
	 * Checks to see how many wall posts have not been sent to facebook
	 *
	 * @param void
	 * @return array|null Returns outstanding wall posts
	 * @access private
	 * @since 2.0.0
	 */
	private function getOutstandingWallPosts()
	{
		$out	= NULL;
		
		return $out;
	}

	/**
	 * Publish outstanding wall posts to Facebook
	 *
	 * @param void
	 * @return void
	 * @access public
	 * @since 2.0.0
	 * @todo loop of cache and sending to Facebook
	 */	
	public function publishWall()
	{
		
	}

	/**
	 * Adds to our temporaryData which will eventually save to cache
	 *
	 * @param array	Data to be added to Facebook Wall (comes from a field)
	 * @return void
	 * @access private
	 * @since 2.0.0
	 */	
	public function registerWallPost( array $data = NULL )
	{
		$this->temporaryData = $data;
	}

	/**
	 * Save all our registerWallPost calls to the cache, ready for Facebook
	 *
	 * @param void
	 * @return void
	 * @access private
	 * @since 2.0.0
	 * @todo loop of _temporaryData, saving to cache
	 */
	public function saveTemporyData()
	{
		
	}
}

?>