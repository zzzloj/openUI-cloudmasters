<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v5.0.5
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2012-08-22 15:24:14 -0400 (Wed, 22 Aug 2012) $
 * </pre>
 * 
 * @author		Matt Mecham <matt@invisionpower.com>
 * @version		$Rev: 11259 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_gallery_50000
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
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @access	public
	 * @return	@e string	 HTML block
	 */
	public function preInstallOptionsForm()
	{
		return '<div>Please be aware that you will need to rebuild your IP.Gallery images using the tools available in the Admin CP following the upgrade.</div><br /><br /><div>Please be aware that any albums placed within global albums that allowed both album and image submissions will be moved to the Member Albums category.</div>';
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @access	public
	 * @return	@e array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
		/* Return */
		return array( 'abc' => '123' );
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @access	public
	 * @return	@e array	 Array of notices
	 */
	public function postInstallNotices()
	{
		return array( "We recommend that you log in to the ACP and visit Other Apps -&gt; Gallery -&gt; Category Manager, click the Tools menu button, and choose to 'Rebuild All Images' following the upgrade to IP.Gallery 5.0<br />" );
	}
}