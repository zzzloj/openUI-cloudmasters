<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Upgrade Class
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 * 
 * @version		$Rev: 10721 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_blog_20000
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
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
		$notices[] = "You will need to run the 'Resynchronize Blogs' tool, found in the 'Tools -> Rebuild' section of the IP.Blog ACP area, in order to complete the upgrade.";
		
		return $notices;
	}
}