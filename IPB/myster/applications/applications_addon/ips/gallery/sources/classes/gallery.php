<?php
/**
 * @file		gallery.php 	IP.Gallery helper library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-11-21 19:20:44 -0500 (Wed, 21 Nov 2012) $
 * @version		v5.0.5
 * $Revision: 11630 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class ipsGallery 
{
	/**
	 * Classes array
	 *
	 * @param	array
	 */
	protected $_classes		= array();
	
	/**
	 * Classes array
	 *
	 * @param	array
	 */
	public $thumbSizes		= array();
	
	/**
	 * User can upload flag
	 *
	 * @param	boolean
	 */
	private $_userCanUpload	= false;
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set shortcuts
		//-----------------------------------------

		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		//-----------------------------------------
		// Initialize categories class
		// Check for table to prevent errors during upgrader
		//-----------------------------------------

		if( $this->DB->checkForTable('gallery_categories') )
		{
			$this->helper('categories')->init();
		}
		
		//-----------------------------------------
		// Set some constants
		//-----------------------------------------

		define( 'GALLERY_MEDIA_FORCE_NO_FLASH_PLAYER', 1 );
		define( 'GALLERY_IMAGE_BYPASS_PERMS', true );
		define( 'GALLERY_PNG_QUALITY', $this->settings['gallery_image_quality_png'] );
		define( 'GALLERY_JPG_QUALITY', $this->settings['gallery_image_quality_jpg'] );
		
		if ( ! defined('IPS_MAPPING_SERVICE') )
		{
			define( 'IPS_MAPPING_SERVICE', 'bing' );
			define( 'BING_API_KEY', ( ! empty( $this->settings['map_bing_api_key'] ) ) ? $this->settings['map_bing_api_key'] : false );
		}
		
		//-----------------------------------------
		// Clean member permissions array
		//-----------------------------------------

		foreach( $this->member->perm_id_array as $k => $v )
		{
			if ( empty( $v ) )
			{
				unset( $this->member->perm_id_array[ $k ] );
			}
		}

		//-----------------------------------------
		// Determine if user can upload images
		//-----------------------------------------

		if ( defined( 'IPS_IS_UPGRADER' ) AND IPS_IS_UPGRADER )
		{
			$this->setCanUpload( true );
		}
		else
		{
			$this->setCanUpload();
		}
		
		//-----------------------------------------
		// Set thumbnail settings
		//-----------------------------------------

		$this->thumbSizes = array( 'large'  => '100',
								   'medium' => '75',
								   'small'  => '50',
								   'tiny'   => '30',
								   'teeny'  => '24', #The naming is clearly getting silly
								   'icon'   => '16' );
	}
	
	/**
	 * Set whether the current member can upload / add images
	 *
	 * @param	boolean		$force	Force a value
	 * @return	@e void
	 */
	public function setCanUpload( $force=null )
	{
		if ( $force !== null )
		{
			$this->_userCanUpload = $force;
		}
		else
		{
			$this->_userCanUpload = false;
			
			if ( $this->memberData['member_id'] )
			{
				$perms = explode( ':', $this->memberData['gallery_perms'] );
				
				if ( empty($perms[1]) )
				{
					$this->_userCanUpload = false;
					return;
				}
			}
			
			if ( $this->memberData['g_max_diskspace'] == 0 )
			{
				$this->_userCanUpload = false;
			}
			else
			{
				$this->_userCanUpload = true;

				if ( $this->memberData['g_album_limit'] == 0 || $this->memberData['g_img_album_limit'] == 0 )
				{
					//-----------------------------------------
					// To potentially save a query, we'll try categories first
					//-----------------------------------------

					if( $this->helper('categories')->canUpload() )
					{
						$this->_userCanUpload	= true;
					}
					else if( $this->memberData['member_id'] )
					{
						$_albums				= $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'gallery_albums', 'where' => 'album_owner_id=' . intval($this->memberData['member_id']) ) );
						$this->_userCanUpload	= intval($_albums['count']) ? true : false;
					}
				}
			}
		}
	}
	
	/**
	 * Get whether the current member can upload / add images
	 *
	 * @return	@e bool
	 */
	public function getCanUpload()
	{
		return $this->_userCanUpload;
	}
	
	/**
	 * Resets the user's has gallery flag
	 * 
	 * @param	int		$memberId	Member ID
	 * @return	@e void
	 */
	public function resetHasGallery( $memberId )
	{
		if ( $memberId )
		{
			IPSMember::save( $memberId, array( 'core' => array( 'has_gallery' => ( count( $this->helper('albums')->fetchAlbumsByOwner( $memberId ) ) ? 1 : 0 ) ) ) );
		}
	}
	
	/**
	 * Auto load classes fo'sho
	 *
	 * @param	string		$name	Class Name
	 * @param	mixed		Any arguments
	 * @return	@e object
	 */
	public function helper( $name )
	{
		if ( isset( $this->_classes[ $name ] ) && is_object( $this->_classes[ $name ] ) )
		{
			return $this->_classes[ $name ];
		}
		else
		{
			//-----------------------------------------
			// If object not stored in registry, store a copy
			//-----------------------------------------

			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$this->registry->setClass( 'gallery', $this );
			}
			
			$_fn	= IPSLib::getAppDir('gallery') . '/sources/classes/gallery/' . $name . '.php';
			
			if ( is_file( $_fn ) )
			{
				$classToLoad				= IPSLib::loadLibrary( $_fn, 'gallery_' . $name, 'gallery' );
				$this->_classes[ $name ]	= new $classToLoad( $this->registry );
				
				return $this->_classes[ $name ];
			}
			else
			{
				trigger_error( 'Cannot locate class in /sources/classes/gallery/' . $name . '.php' );
			}
		}
	}
	
	/**
	 * Check global access
	 *
	 * @return	@e mixed	Boolean true, or outputs an error
	 */
	public function checkGlobalAccess()
	{
		$showError	= false;
		
		//-----------------------------------------
		// Permission check
		//-----------------------------------------

		if ( ! $this->memberData['g_gallery_use'] )
		{
			$showError	= true;
		}
		elseif ( $this->memberData['member_id'] )
		{
			$perms	= explode( ':', $this->memberData['gallery_perms'] );
			
			if ( empty($perms[0]) )
			{
				$showError	= true;
			}
		}
		
        if ( $showError )
        {
        	if ( IPS_IS_AJAX )
        	{
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
				$ajax			= new $classToLoad();
				
				if( $this->request['do'] == 'fetchAjaxView' )
				{
					$ajax->returnHtml( $this->registry->output->getTemplate('gallery_global')->bbCodeImageNoPermission() );
				}

        		$ajax->returnString( 'nopermission' );
        	}
        	else
        	{
				//-----------------------------------------
				// Set a few things manually since we're stopping execution early
				//-----------------------------------------

				$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
				$this->registry->member()->finalizePublicMember();
	        	
	            $this->registry->output->showError( 'no_permission', 107187, null, null, 403 );
        	}
        }
        
		//-----------------------------------------
		// Gallery offline?
		//-----------------------------------------

		if( $this->settings['gallery_offline'] )
		{
			if ( $this->memberData['g_access_offline'] )
			{
				$warn_desc			= str_replace( '<#MSG#>', $this->settings['gallery_offline_text'], $this->lang->words['warn_offline_desc'] );
				$offline_warning	= $this->registry->output->getTemplate('gallery_global')->general_warning( array( 'title' => $this->lang->words['warn_offline_title'], 'body' => $warn_desc ) );
				
				$this->registry->output->addContent( $offline_warning );
			}
			else
			{
	        	if ( IPS_IS_AJAX )
	        	{
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
					$ajax			= new $classToLoad();
					
	        		$ajax->returnString( 'nopermission' );
	        	}
	        	else
	        	{
					//-----------------------------------------
					// Set a few things manually since we're stopping execution early
					//-----------------------------------------

					$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
					$this->registry->member()->finalizePublicMember();
		        	
		            $this->registry->output->showError( $this->settings['gallery_offline_text'], 107188, null, null, 403 );
	        	}
			}
		}
		
		return true;
	}
		
	/**
	 * Rebuilds gallery statistics
	 *
	 * @return	@e void
	 */
	public function rebuildStatsCache()
	{
		//-----------------------------------------
		// Views and diskspace
		//-----------------------------------------

		$totals					= $this->DB->buildAndFetch( array( 'select' => 'SUM(image_file_size) as diskspace, SUM(image_views) as views', 'from' => 'gallery_images' ) );
		
		//-----------------------------------------
		// Comment and image counts
		// Album counts automatically roll up into cats
		//-----------------------------------------

		$albums					= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as total', 'from' => 'gallery_albums' ) );

		$categories				= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as total, SUM(category_count_comments) AS comments_visible, SUM(category_count_comments_hidden) AS comments_hidden,' .
																				'SUM(category_count_imgs) AS images_visible, SUM(category_count_imgs_hidden) AS images_hidden',
																'from'	=> 'gallery_categories' ) );

		$cache = array( 'total_images_visible'		=> intval( $categories['images_visible'] ),
						'total_images_hidden'		=> intval( $categories['images_hidden'] ),
						'_total_diskspace'			=> intval($totals['diskspace']),
						'total_diskspace'			=> IPSLib::sizeFormat( empty($totals['diskspace']) ? 0 : $totals['diskspace'] ),
						'total_views'				=> intval( $totals['views'] ),
						'total_comments_visible'	=> intval( $categories['comments_visible'] ),
						'total_comments_hidden'		=> intval( $categories['comments_hidden'] ),
						'total_albums'				=> $albums['total'],
						'total_categories'			=> $categories['total'] );
		
		//-----------------------------------------
		// Data hook
		//-----------------------------------------

		IPSLib::doDataHooks( $cache, 'galleryRebuildStatsCache' );

		//-----------------------------------------
		// Save the cache
		//-----------------------------------------

		$this->cache->setCache( 'gallery_stats', $cache, array( 'array' => 1 ) );
	}

	/**
	 * Inline resizes thumbnails from one thumb size to another
	 *
	 * @param	string		<img tag
	 * @param	int			Width
	 * @param	int			Height
	 * @param	string		Class name to add
	 * @return	@e string
	 */
	public function inlineResize( $tag, $width, $height, $class='' )
	{			
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$height = ( $height ) ? $height : $width;	
			
		if ( ! is_numeric( $width ) )
		{
			$width = intval( $this->thumbSizes[ str_replace( 'thumb_', '', $width ) ] );
		}
		
		if ( ! is_numeric( $height ) )
		{
			$height = intval( $this->thumbSizes[ str_replace( 'thumb_', '', $height ) ] );
		}	
		
		//-----------------------------------------
		// Quick resize
		//-----------------------------------------

		if ( strstr( $tag, "width='" ) )
		{
			$tag = preg_replace( "#width='([^']+?)'#", "width='" . $width . "'", $tag );
		}
		else
		{
			$tag = str_replace( "<img", "<img width='{$width}'", $tag );
		}
		
		if ( strstr( $tag, "height='" ) )
		{
			$tag = preg_replace( "#height='([^']+?)'#", "height='" . $height . "'", $tag );
		}
		else
		{
			$tag = str_replace( "<img", "<img height='{$height}'", $tag );
		}
		
		if ( $class && strstr( $tag, "galattach") )
		{
			$tag = str_replace( "class='galattach", "class='galattach " . $class . " ", $tag );
		}
		
		if ( strstr( $tag, "cover_img___xx___") )
		{
			$tag = str_ireplace( "cover_img___xx___", "cover_img_" . $width, $tag );
		}
		
		return $tag;
	}
}