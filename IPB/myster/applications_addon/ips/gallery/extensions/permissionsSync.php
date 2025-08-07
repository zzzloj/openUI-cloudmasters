<?php
/**
 * @file		permissionsSync.php 	Update Gallery caches when permissions are updated from central manager
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		7/19/2012
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		v5.0.5
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		galleryPermissionsSync
 * @brief		Recaches categories when permissions are updated
 */
class galleryPermissionsSync
{
	/**#@++
	 * Registry shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $cache;
	protected $DB;
	/*#@--*/

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->cache		= $registry->cache();
		$this->DB			= $registry->DB();
	}
	
	/**
	 * Callback when permissions have been updated
	 *
	 * @return	@e string
	 */
	public function updatePermissions()
	{
		$this->cache->rebuildCache( 'gallery_categories', 'gallery' );

		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$gallery = new ipsGallery( $this->registry );

		foreach( $gallery->helper('categories')->fetchCategories() as $category )
		{
			$this->DB->update( 'gallery_images', array( 'image_parent_permission' => $category['perm_view'] ), 'image_category_id=' . $category['category_id'] );
		}
	}
}