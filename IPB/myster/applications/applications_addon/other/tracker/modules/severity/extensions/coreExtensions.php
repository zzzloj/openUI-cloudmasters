<?php

/**
* Tracker 2.1.0
* 
* Core Extensions Extension
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$_PERM_CONFIG[] = 'SeverityFieldSeverityProject';

class trackerPermMappingSeverityFieldSeverityProject extends tracker_extension_perms_base
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	private
	 * @var		array
	 */
	protected $mapping = array(
		'show'     => 'perm_view',
		'submit'   => 'perm_2',
		'update'   => 'perm_3'
	);

	/**
	 * Mapping of keys to names
	 *
	 * @access	private
	 * @var		array
	 */
	protected $permNames = array(
		'show'     => 'Show Severity Field',
		'submit'   => 'Set in New Issue',
		'update'   => 'Change in a Reply'
	);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	private
	 * @var		array
	 */
	protected $permColors = array(
		'show'     => '#fff0f2',
		'submit'   => '#effff6',
		'update'   => '#edfaff'
	);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->permNames;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->permColors;
	}

	/**
	 * Retrieve the permission items
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		$out = array();
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
		$tracker = new $classToLoad();
		$tracker->execute( ipsRegistry::instance() );

		$projects = $tracker->projects()->getCache();

		foreach( $projects as $r )
		{
			$out[ $r['id'] ] = array(
				'title'     => $r['depthed_name'],
				'perm_view' => $r['perm_view'],
				'perm_2'    => $r['perm_2'],
				'perm_3'    => $r['perm_3'],
				'perm_4'    => $r['perm_4'],
				'perm_5'    => $r['perm_5'],
				'perm_6'    => $r['perm_6'],
				'perm_7'    => $r['perm_7'],
				'restrict'  => $r['cat_only'] == 1 ? 'perm_view' : '',
			);
		}

		return $out;
	}
}

?>