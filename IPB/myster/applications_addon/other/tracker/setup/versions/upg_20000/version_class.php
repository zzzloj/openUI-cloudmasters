<?php

/**
 * Invision Power Services
 * IP.Board v2.1.0 RC 2
 * Upgrade Class
 * 
 * @version		$Rev: 1369 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @link		http://www.invisionpower.com
 * @package		Invision Power Board
 */ 

class version_class_tracker_20000
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry = $registry;
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @access	public
	 * @return	string	 HTML block
	 */
	public function preInstallOptionsForm()
	{	
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @access	public
	 * @return	array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @access	public
	 * @return	array	 Array of notices
	 */
	public function postInstallNotices()
	{
		$notices[] = "You will need to visit your Tracker Admin CP and set up the permissions for Versions/Statuses etc. in each project";
		
		return $notices;
	}
}