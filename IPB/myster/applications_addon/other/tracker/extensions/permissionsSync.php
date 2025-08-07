<?php

/**
* Tracker 2.1.0
* 
* Permissions cache update after save
* Last Updated: $Date: 2012-05-08 10:44:16 -0400 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class trackerPermissionsSync
{
	protected $registry;

	public function __construct($registry)
	{
		$this->registry = $registry;
	}

	public function updatePermissions()
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/app_class_tracker.php", 'app_class_tracker', 'tracker' );
		$app_class_tracker = new $classToLoad( $this->registry );

		$this->registry->tracker->projects()->rebuild();
	}
}
