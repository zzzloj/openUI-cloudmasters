<?php
/**
 * @file		upload.php 	IP.Gallery temporary uploads library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		4.0.0
 * $LastChangedDate: 2012-11-06 04:19:50 -0500 (Tue, 06 Nov 2012) $
 * @version		v5.0.5
 * $Revision: 11558 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_upload
{
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
	 * Total pending uploads count
	 *
	 * @var	int
	 */
	protected $_uploadsCount	= 0;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Registry shortcuts
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
		// Get tags library
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
	}

	/**
	 * Returns the image count saved from fetchSessionUploadsAsImages
	 *
	 * @see		fetchSessionUploadsAsImages()
	 * @return	@e int
	 */
	public function getCount()
	{
		return $this->_uploadsCount;
	}
	
	/**
	 * Generate a new session key
	 *
	 * @return	@e string	MD5 Hash
	 */
	public function generateSessionKey()
	{
		return md5( microtime(true) . ',' . $this->memberData['member_id'] . ',' . $this->member->ip_address );
	}
	
	/**
	 * Generate a new item key
	 *
	 * @param	array		$data		Item data
	 * @return	@e string	MD5 Hash
	 */
	public function generateItemKey( $data )
	{
		return md5( microtime(true) . ',' . $this->memberData['member_id'] . ',' . $this->member->ip_address . $data['name'] . ',' . $data['size'] );
	}
	
	/**
	 * Fetch diskspace used
	 *
	 * @param	int		$memberId		Member ID
	 * @return	@e int	Bytes
	 */
	public function fetchDiskUsage( $memberId )
	{
		$total	= $this->DB->buildAndFetch( array( 'select' => 'SUM( image_file_size ) as diskspace', 'from' => 'gallery_images', 'where' => 'image_member_id=' . intval( $memberId ) ) );
		$temps	= $this->DB->buildAndFetch( array( 'select' => 'SUM( upload_file_size ) as diskspace', 'from' => 'gallery_images_uploads', 'where' => 'upload_member_id=' . intval( $memberId ) ) );
		  
		return intval( $total['diskspace'] + $temps['diskspace'] );
	}
	
	/**
	 * Fetch number of images in an album from both images and images_upload tables
	 *
	 * @param	int			$albumId		Album ID
	 * @return	@e int
	 */
	public function fetchImageCount( $albumId )
	{
		//-----------------------------------------
		// Get images count
		//-----------------------------------------

		$images	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS total', 'from' => 'gallery_images', 'where' => 'image_album_id=' . intval( $albumId ) ) );
		
		//-----------------------------------------
		// Get temporary uploads count
		//-----------------------------------------

		$upload	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS total', 'from' => 'gallery_images_uploads', 'where' => 'upload_album_id=' . intval( $albumId ) ) );

		//-----------------------------------------
		// Return the sum
		//-----------------------------------------

		return intval($images['total']) + intval($upload['total']);
	}

	/**
	 * Fetch statistics used
	 *
	 * @return	@e array
	 */
	public function fetchStats()
	{
		$stats	= array( 'used' => 0, 'maxItem' => 0, 'left' => 0 );
		
		if ( $this->memberData['member_id'] )
		{
			$stats['used']	= $this->fetchDiskUsage( $this->memberData['member_id'] );
		}
		
		//-----------------------------------------
		// Sort out the upload limit
		//-----------------------------------------

		$stats['maxItem']	= ( $this->memberData['g_max_upload'] > 0 ) ? ( $this->memberData['g_max_upload'] * 1024 ) : intval($this->memberData['g_max_upload']);
		$maxPhp				= IPSLib::getMaxPostSize();
		
		if ( $maxPhp < $stats['maxItem'] or $stats['maxItem'] == -1 )
		{
			$stats['maxItem']	= $maxPhp;
		}
		
		//-----------------------------------------
		// Sort out diskspace usage limits
		//-----------------------------------------

		$stats['maxTotal']	= ( $this->memberData['g_max_diskspace'] > 0 ) ? ( $this->memberData['g_max_diskspace'] * 1024 ) : intval($this->memberData['g_max_diskspace']);
		
		if ( $stats['maxTotal'] != -1 )
		{
			if ( $stats['maxTotal'] < 0 )
			{
				$stats['maxTotal']	= 0;
			}
		}

		if( $stats['maxTotal'] > 0 AND $stats['maxTotal'] - $stats['used'] < 0 )
		{
			$stats['maxItem']	= 0;
		}
		else if ( $stats['maxItem'] >= ($stats['maxTotal'] - $stats['used']) and $stats['maxTotal'] != -1 )
		{
			$stats['maxItem']	= $stats['maxTotal'] - $stats['used'];
		}
		
		//-----------------------------------------
		// Let the mere mortals read it
		//-----------------------------------------

		$stats['maxItemHuman']	= ( $stats['maxItem'] == -1 )	? $this->lang->words['unlimited_ucfirst']	: IPSLib::sizeFormat( $stats['maxItem'], true );
		$stats['maxTotalHuman']	= ( $stats['maxTotal'] == -1 )	? $this->lang->words['unlimited_ucfirst']	: IPSLib::sizeFormat( $stats['maxTotal'], true );

		$stats['left']			= ( $stats['maxTotal'] > 0 ) ? $stats['maxTotal'] - $stats['used'] : $this->lang->words['unlimited_ucfirst'];
		
		return $stats;
	}

	/**
	 * Generic API for editing an image
	 *
	 * @since	4.1
	 * @param	string		Image Data (binary string OR key for $_FILES array)
	 * @param	int			Image ID to edit
	 * @param	array		Options ( if using binary string imageData then 'fileName' must be set ('mypic.jpg' for example) 'name', 'description' should be self explanatory)
	 * @param	int			Member ID (optional, default $this->memberData['member_id'])
	 * @return	@e array	New image data
	 *
	 * @throws
	 * 	@li	NO_IMAGE:			The image could not be found
	 *  @li OUT_OF_DISKSPACE:	Member cannot upload any more files
	 *  @li ALBUM_FULL:			Album has reached admin-defined limit
	 *  @li FAILX:				Upload error #1
	 *  @li BAD_TYPE:			Upload error #2
	 *  @li TOO_BIG:			Upload error #3
	 *  @li FAIL:				Upload error #4
	 *  @li NOT_VALID:			Upload error #5
	 *  @li IMAGE_FILE_NAME_NOT_SET:	Binary image data but $opt['fileName'] not set
	 *  @li COULD_NOT_FETCH_EXTENSION:	Could not determine file extension
	 *  @li IMAGE_NOT_WRITTEN:	Image not written to disk
	 */
	public function editImage( $imageData, $imageId=0, $opts=array(), $memberId=null )
	{
		//-----------------------------------------
		// Get the mapping class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$_mapping	= classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		//-----------------------------------------
		// Fetch image data and verify
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $imageId );

		if ( ! $image['image_id'] )
		{
			throw new Exception( "NO_IMAGE" );
		}

		//-----------------------------------------
		// Album or category?
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
			$category	= array();

			if ( ! $album['album_id'] )
			{
				throw new Exception( "NO_ALBUM" );
			}
		}
		else
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
			$album		= array();
		}
		
		//-----------------------------------------
		// Sort out member
		//-----------------------------------------

		$member	= ( $memberId === null ) ? $this->memberData : IPSMember::load( $memberId, 'all' );

		//-----------------------------------------
		// Check member limits
		//-----------------------------------------

		if ( $member['g_max_diskspace'] != -1 )
		{
			if ( ( $this->fetchDiskUsage( $member['member_id'] ) + $_FILES['FILE_UPLOAD']['size']) > ( $member['g_max_diskspace'] * 1024 ) )
			{
				throw new Exception( 'OUT_OF_DISKSPACE' );
			}
		}

		if ( $image['image_album_id'] AND $member['g_img_album_limit'] != -1 )
		{
			if ( $member['g_img_album_limit'] == 0 || ( $this->fetchImageCount( $album['album_id'] ) + 1 ) > $member['g_img_album_limit'] )
			{
				throw new Exception( 'ALBUM_FULL' );
			}
		}
		
		//-----------------------------------------
		// Sort out some default options
		//-----------------------------------------

		if ( ! isset( $opts['allow_images'] ) )
		{
			$opts['allow_images']	= 1;
		}
		
		if ( ! isset( $opts['allow_media'] ) )
		{
			$opts['allow_media']	= $this->registry->gallery->helper('media')->allow();
		}
		
		//-----------------------------------------
		// Grab upload class and set allowed file extensions
		//-----------------------------------------

		require_once IPS_KERNEL_PATH.'classUpload.php';/*noLibHook*/
		$upload = new classUpload();
		
		if ( $opts['allow_media'] )
		{
			foreach( $this->registry->gallery->helper('media')->allowedExtensions() as $k )
			{
				$upload->allowed_file_ext[]	= $k;
			}
		}

		foreach( $this->registry->gallery->helper('image')->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[]	= $k;
		}
		
		//-----------------------------------------
		// Get directory and container names
		//-----------------------------------------

		$dir			= $this->createDirectoryName( $album['album_id'], $category['category_id'] );
		$containerId	= $album['album_id'] ? $album['album_id'] : $category['category_id'];
		
		//-----------------------------------------
		// This an upload key?
		//-----------------------------------------

		if ( strlen( $imageData ) < 20 && ! empty( $_FILES[ $imageData ] ) )
		{
			//-----------------------------------------
			// Upload the file
			//-----------------------------------------

			$key						= $imageData;
			$fileSize					= $_FILES[ $key ]['size'] ? $_FILES[ $key ]['size'] : 1; // Prevent division by 0 warning
			$upload->upload_form_field	= $key;
			$upload->out_file_dir		= $this->settings['gallery_images_path'] . '/' . $dir;
			$upload->out_file_name		= "gallery_{$member['member_id']}_{$containerId}_" . time() % $fileSize;
			
			$upload->process();
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{		
					case 1:
						throw new Exception( 'FAILX' );
					break;
					
					case 2:
						throw new Exception( 'BAD_TYPE' );
					break;
					
					case 3:
						throw new Exception( 'TOO_BIG' );
					break;
					
					case 4:
						throw new Exception( 'FAIL' );
					break;
					
					default:
						throw new Exception( 'NOT_VALID' );
					break;
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Binary image data, check for filename
			//-----------------------------------------

			if ( empty( $opts['fileName'] ) )
			{
				throw new Exception('IMAGE_FILE_NAME_NOT_SET');
			}
			
			//-----------------------------------------
			// Write the file to disk and verify
			//-----------------------------------------

			$ext		= IPSText::getFileExtension( $opts['fileName'] );
			$fileSize	= IPSLib::strlenToBytes( strlen( $imageData ) );
			
			if ( ! $ext )
			{
				throw new Exception('COULD_NOT_FETCH_EXTENSION');
			}
			
			$saveAs		= "gallery_{$this->memberData['member_id']}_{$containerId}_" . ( time() %  $fileSize ) . '.' . $ext;
			$fileName	= $this->settings['gallery_images_path'] . '/' . $dir . $saveAs;
			
			@file_put_contents( $fileName, $imageData );
			@chmod( $fileName, IPS_FILE_PERMISSION );
			
			if ( ! file_exists( $fileName ) )
			{
				throw new Exception( 'IMAGE_NOT_WRITTEN' );
			}
			
			//-----------------------------------------
			// Set some params for other functions
			//-----------------------------------------

			$upload->saved_upload_name	= $fileName;
			$upload->original_file_name	= $opts['fileName'];
			$upload->parsed_file_name	= $saveAs;
			$upload->file_extension		= $ext;
		}

		//-----------------------------------------
		// EXIF / IPTC data?
		//-----------------------------------------
		
		$meta_data	= array();
		
		if ( $this->member->isMobileApp )
		{
			$meta_data['Camera Model']	= 'iPhone';
		}
		
		$meta_data	= array_merge( $meta_data, $this->registry->gallery->helper('image')->extractExif( $upload->saved_upload_name ) );
		$meta_data	= array_merge( $meta_data, $this->registry->gallery->helper('image')->extractIptc( $upload->saved_upload_name ) );
		
		//-----------------------------------------
		// Is this a media file based on extension?
		//-----------------------------------------
		
		$ext	= '.' . $upload->file_extension;
		$media	= $this->registry->gallery->helper('media')->isAllowedExtension( $ext ) ? 1 : 0;
		
		//-----------------------------------------
		// Check max upload size limits
		//-----------------------------------------

		if ( $media )
		{
			if ( $member['g_movie_size'] != -1 )
			{
				if ( $fileSize > ( $member['g_movie_size'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

					throw new Exception( 'TOO_BIG' );
				}
			}		
		}
		else
		{
			if ( $member['g_max_upload'] != -1 )
			{
				if ( $fileSize > ( $member['g_max_upload'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

					throw new Exception( 'TOO_BIG' );
				}
			}
		}

		//-----------------------------------------
		// Clear cache about the file as we are manipulating it
		//-----------------------------------------

		clearstatcache();

		//-----------------------------------------
		// Grab some data
		//-----------------------------------------

		$ext_file_name	= $upload->parsed_file_name;
		$fileSize		= filesize( $upload->saved_upload_name );
		$itemKey		= $this->generateItemKey( array( 'name' => $upload->original_file_name, 'size' => $fileSize ) );
		$sessionKey		= $this->generateSessionKey();
		$latLon			= array( 0, 0 );
		$geoJson		= '';
		
		//-----------------------------------------
		// Geolocation stuff
		//-----------------------------------------

		if ( ! empty( $meta_data['GPS'] ) )
		{
			$latLon	= $this->registry->gallery->helper('image')->convertExifGpsToLatLon( $meta_data['GPS'] );
			
			if ( is_array( $latLon ) AND ( $latLon[0] !== false ) )
			{
				$geolocdata	= $_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
			}
		}
		
		//-----------------------------------------
		// Build the insert array
		//-----------------------------------------

		$update	= array(
						'upload_file_directory'	=> rtrim( $dir, '/' ),
						'upload_file_orig_name'	=> $upload->original_file_name,
						'upload_file_name'		=> $upload->parsed_file_name,
						'upload_file_size'		=> $fileSize,
						'upload_file_type'		=> $this->registry->gallery->helper('image')->getImageType( $ext_file_name ),
						'upload_title'			=> empty($opts['image_caption']) ? $upload->original_file_name : $opts['image_caption'],
						'upload_description'	=> empty($opts['image_description']) ? '' : $opts['image_description'],
						'upload_copyright'		=> empty($opts['image_copyright']) ? '' : $opts['image_copyright'],
						'upload_data'			=> '',
						'upload_geodata'		=> serialize( array( 'latLon' => $latLon, 'gpsRaw' => $meta_data['GPS'], 'locShort' => $geolocdata['geocache_short'] ) ),
						'upload_exif'			=> serialize( $meta_data )
						);

		//-----------------------------------------
		// Remap to gallery_images table
		//-----------------------------------------

		$update = $this->_remapAsImage( $update );
		
		//-----------------------------------------
		// Sort out GPS flag
		//-----------------------------------------

		if ( isset($opts['image_gps_show']) )
		{
			$update['image_gps_show']	= intval($opts['image_gps_show']);
		}
		
		//-----------------------------------------
		// Delete some things we don't want
		//-----------------------------------------

		foreach( array( 'image_id', 'image_member_id', 'image_album_id', 'image_category_id', 'image_medium_file_name', 'image_original_file_name', 'image_date', 'image_feature_flag' ) as $key )
		{
			unset($update[ $key ]);
		}

		unset($image['image_original_file_name']);
		
		//-----------------------------------------
		// Run data hook
		//-----------------------------------------

		$update['_extraData'] = array( 'album' => $album, 'image' => $image );
		
		IPSLib::doDataHooks( $update, 'galleryEditImage' );
		
		unset($update['_extraData']);
		
		//-----------------------------------------
		// Update gallery images table
		//-----------------------------------------

		$this->DB->update( 'gallery_images', $update, 'image_id=' . $image['image_id'] );

		//-----------------------------------------
		// Update tags
		//-----------------------------------------

		$this->registry->galleryTags->replace( $_POST['ipsTags_' . $image['image_id']], array(	'meta_id'			=> $image['image_id'],
																								'meta_parent_id'	=> $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'],
																								'member_id'			=> $this->memberData['member_id'],
																								'meta_visible'		=> ( $image['image_approved'] == 1 ) ? 1 : 0 ) );

		//-----------------------------------------
		// Rebuild thumbnails
		//-----------------------------------------

		$this->registry->gallery->helper('moderate')->removeImageFiles( $image, false );
		$this->registry->gallery->helper('image')->buildSizedCopies( array_merge( $image, $update ), array( 'destination' => 'images' ) );
		
		//-----------------------------------------
		// Reload image data
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $image['image_id'], true );
		
		//-----------------------------------------
		// Does image need to be rotated?
		//-----------------------------------------

		if ( ! empty( $meta_data['IFD0.Orientation'] ) )
		{
			$angle	= 0;
			
			switch ( $meta_data['IFD0.Orientation'] )
			{
				case 6:
					$angle	= 270;
				break;

				case 8:
					$angle	= 90;
				break;

				case 3:
					$angle	= 180;
				break;
			}
			
			if ( $angle )
			{
				$this->registry->gallery->helper('image')->rotateImage( $image, $angle );
			}
		}

		//-----------------------------------------
		// Return the image
		//-----------------------------------------

		return $image;
	}
	
	/**
	 * Determines directory name and creates if necessary
	 *
	 * @param	int			Album ID
	 * @param	int			Category ID
	 * @return	@e string	Dir name
	 */
	public function createDirectoryName( $albumId=0, $categoryId=0 )
	{
		//-----------------------------------------
		// If safe mode is on, skip
		//-----------------------------------------

		if ( $this->settings['safe_mode_skins'] )
		{
			return '';
		}

		//-----------------------------------------
		// Got an ID?
		//-----------------------------------------

		if( !$albumId AND !$categoryId )
		{
			return '';
		}

		//-----------------------------------------
		// Set some basic details
		//-----------------------------------------

		$dir	= '';
		$name	= $albumId ? 'album_' . intval( $albumId ) : 'category_' . intval( $categoryId );

		//-----------------------------------------
		// Make sure we have a parent gallery/ dir
		//-----------------------------------------

		if ( ! is_dir( $this->settings['gallery_images_path'] . '/gallery' ) )
		{
			if ( @mkdir( $this->settings['gallery_images_path'] . '/gallery', IPS_FOLDER_PERMISSION ) )
			{
				@chmod( $this->settings['gallery_images_path'] . '/gallery', IPS_FOLDER_PERMISSION );
				@touch( $this->settings['gallery_images_path'] . '/gallery/index.html' );
			}
			
			$dir	= 'gallery';
		}
		else
		{
			$dir	= 'gallery';
		}

		//-----------------------------------------
		// Create folder if necessary and possible
		//-----------------------------------------

		if ( ! is_dir( $this->settings['gallery_images_path'] . '/' . $dir . '/' . $name ) )
		{
			if ( @mkdir( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name, IPS_FOLDER_PERMISSION ) )
			{
				@chmod( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name, IPS_FOLDER_PERMISSION );
				@touch( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name . '/index.html' );
			}
			
			$dir .= '/' . $name;
		}
		else
		{
			$dir .= '/' . $name;
		}

		$dir	= ( $dir ) ? $dir . '/' : '';
		
		return $dir;
	}

	/**
	 * Processes the thumbnail for a media thingy
	 *
	 * @param 	string		Upload ID
	 * @return	@e array
	 *
	 * @throws
	 *  @li FAILX:				Upload error #1
	 *  @li BAD_TYPE:			Upload error #2
	 *  @li TOO_BIG:			Upload error #3
	 *  @li FAIL:				Upload error #4
	 *  @li NOT_VALID:			Upload error #5
	 */
	public function mediaThumb( $id )
	{
		//-----------------------------------------
		// Get image
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if ( ! $image['image_id'] )
		{
			return false;
		}

		//-----------------------------------------
		// Album or category?
		//-----------------------------------------

		if( $image['image_album_id'] )
		{
			$albumId	= $image['image_album_id'];
			$categoryId	= 0;
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );
			$category	= array();

			if ( ! $album['album_id'] )
			{
				throw new Exception( "NO_ALBUM" );
			}
		}
		else
		{
			$albumId	= 0;
			$categoryId	= $image['image_category_id'];
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );
			$album		= array();
		}

		$containerId	= $albumId ? $albumId : $categoryId;
		
		//-----------------------------------------
		// Load uploader class
		//-----------------------------------------

		require_once IPS_KERNEL_PATH.'classUpload.php';/*noLibHook*/
		$upload	= new classUpload();
		
		//-----------------------------------------
		// Set allowed image extensions
		//-----------------------------------------

		foreach( $this->registry->gallery->helper('image')->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[]	= $k;
		}
		
		if ( $_FILES['FILE_UPLOAD']['size'] < 1 )
		{
			throw new Exception( 'FAIL' );
		}
		
		//-----------------------------------------
		// Limit upload size to 2MB
		//-----------------------------------------

		if ( $_FILES['FILE_UPLOAD']['size'] > 2048 * 1024 )
		{
			throw new Exception( 'TOO_BIG' );
		}

		$stats	= $this->fetchStats();

		if( $_FILES['FILE_UPLOAD']['size'] > $stats['maxItem'] )
		{
			throw new Exception( 'OUT_OF_DISKSPACE' );
		}
		
		//-----------------------------------------
		// Set directory name
		//-----------------------------------------

		$dir	= $this->createDirectoryName( $albumId, $categoryId );

		//-----------------------------------------
		// Set upload params.  Force size to 1 or higher to prevent division by 0.
		//-----------------------------------------

		$upload->out_file_dir	= $this->settings['gallery_images_path'] . '/' . $dir;
		$upload->out_file_name	= "media_{$this->memberData['member_id']}_{$containerId}_" . time() % ( $_FILES['FILE_UPLOAD']['size'] > 0 ? $_FILES['FILE_UPLOAD']['size'] : 1 );

		//-----------------------------------------
		// Upload and check for errors
		//-----------------------------------------

		$upload->process();
					
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{		
				case 1:
					throw new Exception( 'FAILX' );
				break;
				
				case 2:
					throw new Exception( 'BAD_TYPE' );
				break;
				
				case 3:
					throw new Exception( 'TOO_BIG' );
				break;
				
				case 4:
					throw new Exception( 'FAIL' );
				break;
				
				default:
					throw new Exception( 'NOT_VALID' );
				break;
			}
		}

		//-----------------------------------------
		// Grab some info
		//-----------------------------------------

		$ext_file_name	= $upload->parsed_file_name;
		$fileSize		= filesize( $upload->saved_upload_name );
		 
		//-----------------------------------------
		// Insert data into database
		//-----------------------------------------

		if ( is_numeric( $image['image_id'] ) )
		{
			$this->DB->update( 'gallery_images', array( 'image_thumbnail' => 1, 'image_medium_file_name' => $upload->parsed_file_name, 'image_directory' => $dir ), 'image_id=' . intval( $image['image_id']  ) );
		}
		else
		{
			$this->DB->update( 'gallery_images_uploads', array( 'upload_thumb_name' => 'tn_' . $upload->parsed_file_name, 'upload_medium_name' => $upload->parsed_file_name, 'upload_file_directory' => $dir ), 'upload_key=\'' . $image['image_id'] . "'" );
		}
		
		//-----------------------------------------
		// Get image library
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$img	= ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		//-----------------------------------------
		// Set options
		//-----------------------------------------

		$settings	= array(
							'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
							'image_file'	=> $upload->parsed_file_name,
							'im_path'		=> $this->settings['gallery_im_path'],
							'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
							'jpg_quality'	=> GALLERY_JPG_QUALITY,
							'png_quality'	=> GALLERY_PNG_QUALITY
							);	

		//-----------------------------------------
		// Build medium sized media thumb
		// We use admin settings if available, otherwise we fall back to 640px
		//-----------------------------------------

		if ( $img->init( $settings ) )
		{
			$return	= $img->resizeImage( $this->settings['gallery_medium_width'] ? $this->settings['gallery_medium_width'] : 640, $this->settings['gallery_medium_height'] ? $this->settings['gallery_medium_height'] : 640 );

			$img->writeImage( $this->settings['gallery_images_path'] . '/' . $dir . $upload->parsed_file_name );
		}
		
		unset( $img );
		
		//-----------------------------------------
		// Build media thumb
		//-----------------------------------------

		$this->registry->gallery->helper('media')->buildThumbs( $image['image_id'] );
		
		//-----------------------------------------
		// Return data in the format expected
		//-----------------------------------------

		$ret		= $this->registry->gallery->helper('image')->fetchImage( $image['image_id'], GALLERY_IMAGES_FORCE_LOAD );
		$ret['tag']	= $this->registry->gallery->helper('image')->makeImageTag( $ret );
		$ret['ok']	= 'done';
		
		return $ret;
	}
	
	/**
	 * Moves image to correct dir, adds to tmp upload table, builds thumbs
	 *
	 * @since	4.0
	 * @param	string		Session Key
	 * @param	int			Container ID to upload into (album or category, indicated by $opts['containerType'])
	 * @param	array		Options
	 * @param	int			Member ID (optional, default $this->memberData['member_id'])
	 * @return	@e array
	 *
	 * @throws
	 *  @li OUT_OF_DISKSPACE:	Member cannot upload any more files
	 *  @li ALBUM_FULL:			Album has reached admin-defined limit
	 *  @li FAILX:				Upload error #1
	 *  @li BAD_TYPE:			Upload error #2
	 *  @li TOO_BIG:			Upload error #3
	 *  @li FAIL:				Upload error #4
	 *  @li NOT_VALID:			Upload error #5
	 */
	public function process( $sessionKey='', $containerId=0, $opts=array(), $memberId=null )
	{
		//-----------------------------------------
		// Get mapping class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$_mapping	= classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		//-----------------------------------------
		// Album or category?
		//-----------------------------------------

		if( !$containerId )
		{
			trigger_error( "Container ID missing in gallery_upload::process" );
		}

		if( $opts['containerType'] == 'album' )
		{
			$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $containerId );
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );

			if ( ! $album['album_id'] )
			{
				trigger_error( "Album ID missing in gallery_upload::process" );
			}

			if( !$this->registry->gallery->helper('albums')->isUploadable( $album ) )
			{
				trigger_error( "No permission to upload to album" );
			}
		}
		else
		{
			$category	= $this->registry->gallery->helper('categories')->fetchCategory( $containerId );
			$album		= array( 'album_id' => 0 );

			if ( ! $category['category_id'] )
			{
				trigger_error( "Category ID missing in gallery_upload::process" );
			}

			if( !$this->registry->permissions->check( 'post', $category ) )
			{
				trigger_error( "No permission to upload to category" );
			}
		}
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------

		$member	= ( $memberId === null ) ? $this->memberData : IPSMember::load( $memberId, 'all' );

		//-----------------------------------------
		// Check member diskspace and image restrictions
		//-----------------------------------------

		if ( $member['g_max_diskspace'] != -1 )
		{
			if ( ( $this->fetchDiskUsage( $member['member_id'] ) + $_FILES['FILE_UPLOAD']['size']) > ( $member['g_max_diskspace'] * 1024 ) )
			{
			 	throw new Exception( 'OUT_OF_DISKSPACE' );
			}
		}

		if ( $opts['containerType'] == 'album' AND $member['g_img_album_limit'] != -1 )
		{
			if ( $member['g_img_album_limit'] == 0 || ( $this->fetchImageCount( $album['album_id'] ) + 1 ) > $member['g_img_album_limit'] )
			{
				throw new Exception( 'ALBUM_FULL' );
			}
		}

		//-----------------------------------------
		// Set some defaults
		//-----------------------------------------

		if ( ! isset( $opts['allow_images'] ) )
		{
			$opts['allow_images']	= 1;
		}
		
		if ( ! isset( $opts['allow_media'] ) )
		{
			$opts['allow_media']	= $this->registry->gallery->helper('media')->allow();
		}
		
		//-----------------------------------------
		// Get uploader
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classUpload.php' );/*noLibHook*/
		$upload = new classUpload();

		//-----------------------------------------
		// Set allowed file types
		//-----------------------------------------

		if ( $opts['allow_media'] )
		{
			foreach( $this->registry->gallery->helper('media')->allowedExtensions() as $k )
			{
				$upload->allowed_file_ext[]	= $k;
			}
		}

		foreach( $this->registry->gallery->helper('image')->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[]	= $k;
		}
		
		//-----------------------------------------
		// Get directory name
		//-----------------------------------------

		$dir = $this->createDirectoryName( $album['album_id'], $category['category_id'] );

		//-----------------------------------------
		// Set some more uploader params
		//-----------------------------------------

		$upload->out_file_dir	= $this->settings['gallery_images_path'] . '/' . $dir;
		$upload->out_file_name	= "gallery_{$member['member_id']}_{$containerId}_" . time() % ( $_FILES['FILE_UPLOAD']['size'] > 0 ? $_FILES['FILE_UPLOAD']['size'] : 1 );

		//-----------------------------------------
		// Upload and check errors
		//-----------------------------------------

		$upload->process();
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{		
				case 1:
					throw new Exception( 'FAILX' );
				break;
				
				case 2:
					throw new Exception( 'BAD_TYPE' );
				break;
				
				case 3:
					throw new Exception( 'TOO_BIG' );
				break;
				
				case 4:
					throw new Exception( 'FAIL' );
				break;
				
				default:
					throw new Exception( 'NOT_VALID' );
				break;
			}
		}

		$stats	= $this->fetchStats();

		if( $_FILES['FILE_UPLOAD']['size'] > $stats['maxItem'] )
		{
			throw new Exception( 'OUT_OF_DISKSPACE' );
		}

		//-----------------------------------------
		// EXIF / IPTC data
		//-----------------------------------------
		
		$meta_data	= array_merge( array(), $this->registry->gallery->helper('image')->extractExif( $upload->saved_upload_name ) );
		$meta_data	= array_merge( $meta_data, $this->registry->gallery->helper('image')->extractIptc( $upload->saved_upload_name ) );
		
		//-----------------------------------------
		// Image or media file?
		//-----------------------------------------

		$ext	= '.' . $upload->file_extension;
		$media	= $this->registry->gallery->helper('media')->isAllowedExtension( $ext ) ? 1 : 0;

		//-----------------------------------------
		// Double check restrictions
		//-----------------------------------------

		$fileSize	= filesize( $upload->saved_upload_name );

		if ( $media )
		{
			if ( $member['g_movie_size'] != -1 )
			{
				if ( $fileSize > ( $member['g_movie_size'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

					throw new Exception( 'TOO_BIG' );
				}
			}		
		}
		else
		{
			if ( $member['g_max_upload'] != -1 )
			{
				if ( $fileSize > ( $member['g_max_upload'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

					throw new Exception( 'TOO_BIG' );
				}
			}
		}

		//-----------------------------------------
		// Clear stat cache as we manipulate the file
		//-----------------------------------------

		clearstatcache();

		//-----------------------------------------
		// Set some variables
		//-----------------------------------------

		$ext_file_name	= $upload->parsed_file_name;
		$itemKey		= $this->generateItemKey( array( 'name' => $upload->original_file_name, 'size' => $fileSize ) );
		$latLon			= array( 0, 0 );
		$geoJson		= '';
		
		//-----------------------------------------
		// Get geolocation data
		//-----------------------------------------

		if ( ! empty( $meta_data['GPS'] ) )
		{
			$latLon	= $this->registry->gallery->helper('image')->convertExifGpsToLatLon( $meta_data['GPS'] );
			
			if ( is_array( $latLon ) AND ( $latLon[0] !== false ) )
			{
				$geolocdata	= $_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
			}
		}
		
		//-----------------------------------------
		// Build insert array
		//-----------------------------------------

		$image	= array(
						'upload_key'			=> $itemKey,
						'upload_session'		=> $sessionKey,
						'upload_member_id'		=> $member['member_id'],
						'upload_album_id'		=> $album['album_id'],
						'upload_category_id'	=> $category['category_id'],
						'upload_date'			=> IPS_UNIX_TIME_NOW,
						'upload_file_directory'	=> rtrim( $dir, '/' ),
						'upload_file_orig_name'	=> $upload->original_file_name,
						'upload_file_name'		=> $upload->parsed_file_name,
						'upload_file_size'		=> $fileSize,
						'upload_file_type'		=> $this->registry->gallery->helper('image')->getImageType( $ext_file_name ),
						'upload_title'			=> $upload->original_file_name,
						'upload_description'	=> '',
						'upload_copyright'		=> '',
						'upload_data'			=> '',
						'upload_geodata'		=> serialize( array( 'latLon' => $latLon, 'gpsRaw' => $meta_data['GPS'], 'locShort' => $geolocdata['geocache_short'] ) ),
						'upload_exif'			=> serialize( $meta_data )
						);
		
		//-----------------------------------------
		// Fix unicode issues
		//-----------------------------------------

		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$image['upload_title']	= IPSText::utf8ToEntities( $upload->original_file_name );
		}
		
		//-----------------------------------------
		// Insert into temp uploads table
		//-----------------------------------------

		$this->DB->insert( 'gallery_images_uploads', $image );

		//-----------------------------------------
		// Build thumbnails
		//-----------------------------------------

		$this->registry->gallery->helper('image')->buildSizedCopies( $this->_remapAsImage( $image ), array( 'destination' => 'uploads' ) );
		
		//-----------------------------------------
		// Rotate image if necessary
		//-----------------------------------------

		if ( ! empty( $meta_data['IFD0.Orientation'] ) )
		{
			$angle	= 0;
			
			switch ( $meta_data['IFD0.Orientation'] )
			{
				case 6:
					$angle	= 90;
				break;

				case 8:
					$angle	= 270;
				break;

				case 3:
					$angle	= 180;
				break;
			}
			
			if ( $angle )
			{
				$this->registry->gallery->helper('image')->rotateImage( $this->_remapAsImage( $image ), $angle );
			}
		}

		//-----------------------------------------
		// Return the key
		//-----------------------------------------

		return $itemKey;
	}

	/**
	 * Saves temporary upload data
	 *
	 * @param	array	Array of images indexed by key
	 * @return	@e void
	 */
	public function saveSessionImages( array $images )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ids		= array();
		$uploads	= array();
		
		foreach( $images as $id => $data )
		{
			$ids[]	= "'" . $this->DB->addSlashes( $id ) . "'";
		}

		//-----------------------------------------
		// Got images?
		//-----------------------------------------

		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Get existing data from DB
			//-----------------------------------------

			$this->DB->build( array( 'select' => '*', 'from' => 'gallery_images_uploads', 'where' => 'upload_key IN (' . implode( ',', $ids ) . ')' ) );
			$this->DB->execute();

			while( $im = $this->DB->fetch() )
			{
				$uploads[ $im['upload_key'] ] = $im;
			}

			//-----------------------------------------
			// Loop over the data to save
			//-----------------------------------------

			foreach( $images as $id => $data )
			{
				if ( isset( $data['image_caption']) OR isset( $data['image_masked_file_name'] ) OR isset( $data['image_description'] ) or isset( $data['image_directory'] ) or isset( $data['image_medium_file_name'] ) )
				{
					if ( is_array( $uploads[ $id ] ) )
					{
						if ( ! isset( $data['upload_data'] ) )
						{
							$data['upload_data']	= $uploads[ $id ]['upload_data'];
						}
						
						$data	= array_merge( $uploads[ $id ], $this->_remapAsUpload( $data ) );
					}
					else
					{
						$data	= $this->_remapAsUpload( $data );
					}
				}

				//-----------------------------------------
				// If we have something to save, save it
				//-----------------------------------------

				if ( count( $data ) AND strlen( $id ) == 32 )
				{
					$this->DB->update( 'gallery_images_uploads', $data, 'upload_key=\'' . $this->DB->addSlashes( $id ) . '\'' );
				}
			}
		}
	}
	
	/**
	 * Deletes images
	 *
	 * @param	array	Array of images indexed by key
	 * @return	@e void
	 */
	public function deleteSessionImages( array $images )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$final	= array();
		
		foreach( $images as $id => $data )
		{
			if ( ! is_numeric( $id ) )
			{
				$final[]	= "'" . $this->DB->addSlashes( $id ) . "'";
			}
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		if ( count( $final ) )
		{
			$this->DB->delete( 'gallery_images_uploads', 'upload_key IN (' . implode( ",", $final ) . ")" );
		}
	}
	
	/**
	 * Finish: Publishes the pictures and finalizes the uploads
	 *
	 * @param	string		Session key
	 * @return	@e array
	 */
	public function finish( $sessionKey )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$albums		= array();
		$categories	= array();
		$addedIds	= array();
		$toFollow	= array();

		//-----------------------------------------
		// Fetch the data
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'i.*',
								'from'		=> array( 'gallery_images_uploads' => 'i' ),
								'where'		=> 'i.upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								'order'		=> 'i.upload_date ASC',
								'limit'		=> array( 0, 500 ),
								'add_join'	=> array(
													array(
														'select' => 'a.*',
								 						'from'   => array( 'gallery_albums' => 'a' ),
								 						'where'  => 'i.upload_album_id=a.album_id',
								 						'type'   => 'left'
								 						)
													)
						)		);
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			//-----------------------------------------
			// Remap the temp data to final data
			//-----------------------------------------

			$_i	= $this->_remapAsImage( $row );
			
			unset( $_i['image_id'] );

			$_i['image_notes']	= '';

			//-----------------------------------------
			// INIT var
			//-----------------------------------------

			$thisIsCoverImage = false;
			
			//-----------------------------------------
			// Does image require approval?
			//-----------------------------------------

			$_i['image_approved']	= 1;

			$_category	= $this->registry->gallery->helper('categories')->fetchCategory( $_i['image_category_id'] );

			if( $_category['category_approve_img'] AND 
				!$this->registry->gallery->helper('categories')->checkIsModerator( $_i['image_category_id'], $_i['image_member_id'], 'mod_can_approve' ) AND 
				!in_array( $_category['category_id'], $this->registry->gallery->helper('categories')->member_access['bypass'] )
				)
			{
				$_i['image_approved'] = 0;
			}

			//-----------------------------------------
			// Get image data
			//-----------------------------------------

			$thisIsCoverImage	= 0;
			$autoFollow			= 0;

			if ( IPSLib::isSerialized( $row['upload_data'] ) )
			{
				$_data				= IPSLib::safeUnserialize( $row['upload_data'] );
				$thisIsCoverImage	= ( isset( $_data['_isCover'] ) AND $_data['_isCover'] ) ? 1 : 0;
				$autoFollow			= ( isset( $_data['_follow'] ) AND $_data['_follow'] ) ? 1 : 0;

				if ( isset( $_data['sizes'] ) )
				{
					$_i['image_data']	= serialize( array( 'sizes' => $_data['sizes'] ) );
				}
			}

			//-----------------------------------------
			// Verify category ID is set
			//-----------------------------------------

			if( $_i['image_album_id'] AND !$_i['image_category_id'] )
			{
				$_i['image_category_id']	= $row['album_category_id'];
			}

			//-----------------------------------------
			// Get permission data
			//-----------------------------------------

			$_i['image_privacy']			= $_i['image_album_id'] ? $row['album_type'] : 0;
			$_i['image_parent_permission']	= $this->registry->gallery->helper('categories')->fetchCategory( $_i['image_category_id'], 'perm_view' );

			//-----------------------------------------
			// Pre-add data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $_i, 'galleryPreAddImage' );
				
			//-----------------------------------------
			// Insert
			//-----------------------------------------

			$this->DB->insert( 'gallery_images', $_i );

			$_i['image_id']	= $this->DB->getInsertId();
			$addedIds[]		= $_i['image_id'];

			//-----------------------------------------
			// We following?
			//-----------------------------------------

			if( $autoFollow )
			{
				$toFollow[]	= $_i['image_id'];
			}
				
			//-----------------------------------------
			// Handle tags
			//-----------------------------------------

			if ( ! empty( $_POST['ipsTags_' . $row['upload_key'] ] ) )
			{
				$this->registry->galleryTags->add( $_POST['ipsTags_' . $row['upload_key'] ], array(
																									'meta_id'			=> $_i['image_id'],
																									'meta_parent_id'	=> $_i['image_album_id'] ? $_i['image_album_id'] : $_i['image_category_id'],
																									'member_id'			=> $this->memberData['member_id'],
																									'meta_visible'		=> ( $_i['image_approved'] == 1 ) ? 1 : 0
																						)			);
			}
				
			//-----------------------------------------
			// Mark as read for uploader
			//-----------------------------------------

			$this->registry->classItemMarking->markRead( array( 'albumID' => $_i['image_album_id'], 'categoryID' => $_i['image_category_id'], 'itemID' => $_i['image_id'] ), 'gallery' );
				
			//-----------------------------------------
			// Update geolocation data
			//-----------------------------------------

			$this->registry->gallery->helper('image')->setReverseGeoData( $_i['image_id'] );
				
			//-----------------------------------------
			// Post-add data hook
			//-----------------------------------------

			IPSLib::doDataHooks( $_i, 'galleryPostAddImage' );

			//-----------------------------------------
			// Store container IDs for recaching
			//-----------------------------------------

			if( $_i['image_album_id'] )
			{
				if ( ! isset( $albums[ $_i['image_album_id'] ] ) )
				{
					$albums[ $_i['image_album_id'] ]	= array();
				}

				if ( $thisIsCoverImage )
				{
					$albums[ $_i['image_album_id'] ]	= array( 'album_cover_img_id' => $_i['image_id'] );
				}
			}
			else
			{
				if ( ! isset( $categories[ $_i['image_category_id'] ] ) )
				{
					$categories[ $_i['image_category_id'] ]	= array();
				}

				if ( $thisIsCoverImage )
				{
					$categories[ $_i['image_category_id'] ]	= array( 'category_cover_img_id' => $_i['image_id'] );
				}
			}
		}

		//-----------------------------------------
		// Follow stuff
		//-----------------------------------------

		if( count($toFollow) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'gallery', 'images' );

			foreach( $toFollow as $followId )
			{
				$_like->add( $followId, $this->memberData['member_id'], array( 'like_notify_do' => 1, 'like_notify_meta' => $followId, 'like_notify_freq' => 'immediate' ) );
			}
		}

		//-----------------------------------------
		// Update and resync albums
		//-----------------------------------------

		if ( count( $albums ) )
		{
			$this->registry->gallery->helper('albums')->save( $albums );

			foreach( $albums as $id => $data )
			{
				$this->registry->gallery->helper('albums')->resync( $id );
			}

			//-----------------------------------------
			// Send notifications
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'gallery', 'albums' );
			$_like->sendAlbumNotifications( array_keys( $albums ) );
		}

		//-----------------------------------------
		// Update and resync cats
		//-----------------------------------------

		if( count($categories) )
		{
			foreach( $categories as $catId => $catData )
			{
				if( $catData['category_cover_img_id'] )
				{
					$this->DB->update( 'gallery_categories', $catData, 'category_id=' . $catId );
				}

				$this->registry->gallery->helper('categories')->rebuildCategory( $catId );
			}

			//-----------------------------------------
			// Send notifications
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'gallery', 'categories' );
			$_like->sendCategoryNotifications( array_keys( $categories ) );
		}

		//-----------------------------------------
		// Delete the temporary session
		//-----------------------------------------

		$this->DB->delete( 'gallery_images_uploads', 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'' );
		
		//-----------------------------------------
		// Rebuild statistics
		//-----------------------------------------

		$this->registry->gallery->rebuildStatsCache();

		//-----------------------------------------
		// Return the ids we added
		//-----------------------------------------

		return $addedIds;
	}


	/**
	 * Fetches a single image. NO SECURITY CHECKS ARE PERFORMED
	 *
	 * @param	string		ID key
	 * @return	@e array	Array of data remapped as image
	 */
	public function fetchImage( $id )
	{
		$_img	= $this->DB->buildAndFetch( array(
												'select'	=> 'i.*',
												'from'		=> array( 'gallery_images_uploads' => 'i' ),
												'where'		=> "i.upload_key='" . trim( $this->DB->addSlashes( $id ) ) . "'",
												'add_join'	=> array(
																	array(
																		'select'	=> 'a.*',
																		'from'		=> array( 'gallery_albums' => 'a' ),
																		'where'		=> 'a.album_id=i.upload_album_id',
																		'type'		=> 'left'
																		),
																	array(
																		'select'	=> 'mem.members_display_name',
																		'from'		=> array( 'members' => 'mem' ),
																		'where'		=> 'mem.member_id = i.upload_member_id',
																		'type'		=> 'left'
																		)
																	)
										)		);
																			  
		return ( is_array( $_img ) && count( $_img ) ) ? $this->_remapAsImage( $_img ) : array();
	}
	
	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @param	string		Session key
	 * @param	int			Album ID
	 * @param	int			Category ID
	 * @param	string		Message
	 * @param	int			Is an error (we use an int to prevent JSON ambiguity or type-conversion)
	 * @param	int			Latest ID
	 * @return	@e void
	 */
	public function fetchSessionUploadsAsJson( $sessionKey, $albumId, $categoryId, $msg='', $isError=0, $latestId=0 )
	{
		//-----------------------------------------
		// Start building JSON array
		//-----------------------------------------

		$JSON	= array(
						'sessionKey'	=> $sessionKey,
						'album_id'		=> $albumId,
						'category_id'	=> $categoryId,
						'upload_stats'	=> $this->fetchStats(),
						'msg'			=> $msg,
						'is_error'		=> $isError,
						'insert_id'		=> $latestId ? $latestId : 0
						);

		//-----------------------------------------
		// Fetch the data
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> '*',
								'from'		=> 'gallery_images_uploads',
								'where'		=> 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								'order'		=> 'upload_date ASC',
								'limit'		=> array( 0, 500 )
						)		);
		$this->DB->execute();

		//-----------------------------------------
		// Loop and set results
		//-----------------------------------------

		while( $row = $this->DB->fetch() )
		{
			$row['_isRead']	= 1;
			$thumb			= ( ! $row['upload_thumb_name'] ) ? '' : $this->registry->gallery->helper('image')->makeImageTag( $this->_remapAsImage( $row ), array( 'type' => 'thumb', 'thumbClass' => 'thumb_img' ) );

			$JSON['current_items'][ $row['upload_key'] ]	= array(
																	$row['upload_key']  ,
											 	 					str_replace( array( '[', ']' ), '', $row['upload_file_orig_name'] ),
																	$row['upload_file_size'],
																	1,
																	$thumb,
																	$this->settings['gallery_size_thumb_width'],
																	$this->settings['gallery_size_thumb_height']
																	);
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $JSON;
	}
	
	/**
	 * Fetches all uploads for this 'session' as gallery_images format
	 *
	 * @param	string		Session key
	 * @param	array 		Options, keys: offset, limit, getTotalCount
	 * @return	@e void
	 */
	public function fetchSessionUploadsAsImages( $sessionKey, $opts=array() )
	{
		//-----------------------------------------
		// Asking for the total count?
		//-----------------------------------------

		if( $opts['getTotalCount'] )
		{
			$total	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as uploads', 'from' => 'gallery_images_uploads', 'where' => 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'' ) );

			$this->_uploadsCount	= intval($total['uploads']);
		}

		//-----------------------------------------
		// Fetch the data
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> '*',
								'from'		=> 'gallery_images_uploads',
								'where'		=> 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								'order'		=> 'upload_date ASC',
								'limit'		=> array( intval($opts['offset']), $opts['limit'] ? intval($opts['limit']) : 500 )
						)		);
		$outer	= $this->DB->execute();

		//-----------------------------------------
		// Loop over results
		//-----------------------------------------

		while( $row = $this->DB->fetch( $outer ) )
		{
			$row			= $this->_remapAsImage( $row );
			$row['_isRead']	= 1;
			$row['thumb']	= $this->registry->gallery->helper('image')->makeImageLink( $row, array( 'type' => 'thumb' ) );
			
			$images[ $row['image_id'] ]	= $row;
		}

		return $images;
	}
	
	/**
	 * Removes an uploaded item
	 *
	 * @param	string		Session key
	 * @param	string		Upload key
	 * @return	@e mixed	JSON array of remaining uploads for this session or FALSE
	 */
	public function removeUpload( $sessionKey, $uploadKey )
	{
		//-----------------------------------------
		// Fetch the file
		//-----------------------------------------

		$upload	= $this->DB->buildAndFetch( array(
												'select'	=> '*',
								 		   		'from'		=> 'gallery_images_uploads',
								 		   	    'where'		=> 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\' AND upload_key=\'' . $this->DB->addSlashes( $uploadKey ) .'\''
										)		);

		//-----------------------------------------
		// If we have it, delete it and return
		//-----------------------------------------

		if ( $upload['upload_key'] )
		{
			$this->registry->gallery->helper('moderate')->removeImageFiles( $this->_remapAsImage( $upload ) );

			$this->DB->delete( 'gallery_images_uploads', 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\' AND upload_key=\'' . $this->DB->addSlashes( $uploadKey ) .'\'' );
			
			return $this->fetchSessionUploadsAsJson( $sessionKey, $upload['upload_album_id'], $upload['upload_category_id'], 'upload_removed', 0  );
		}

		//-----------------------------------------
		// Otherwise return false
		//-----------------------------------------

		return false;
	}
	
	/**
	 * Remap a tmp upload row as an image row table
	 *
	 * @param	array		$image		Image data
	 * @return	@e array	Remapped image data
	 */
	public function _remapAsImage( array $image )
	{
		//-----------------------------------------
		// Reset geolocation data
		//-----------------------------------------

		if ( IPSLib::isSerialized( $image['upload_geodata'] ) )
		{
			$upload_geodata	= IPSLib::safeUnserialize( $image['upload_geodata'] );
			
			$latLon			= $upload_geodata['latLon'];
			$gpsRaw			= $upload_geodata['gpsRaw'];
			$locShort		= $upload_geodata['locShort'];
		}
		
		if ( IPSLib::isSerialized( $image['upload_data'] ) )
		{
			$upload_data	= IPSLib::safeUnserialize( $image['upload_data'] );
			$image_gps_show	= intval( $upload_data['image_gps_show'] );
		}

		//-----------------------------------------
		// Return the image data
		//-----------------------------------------

		return array(
					'image_id'					=> $image['upload_key'],
					'image_member_id'			=> $image['upload_member_id'],
					'image_album_id'			=> $image['upload_album_id'],
					'image_category_id'			=> $image['upload_category_id'],
					'image_caption'				=> $image['upload_title'],
					'image_description'			=> $image['upload_description'],
					'image_directory'			=> $image['upload_file_directory'],
					'image_masked_file_name'	=> $image['upload_file_name'],
					'image_medium_file_name'	=> $image['upload_medium_name'],
					'image_original_file_name'	=> $image['upload_file_name_original'],
					'image_file_name'			=> $image['upload_file_orig_name'],
					'image_file_size'			=> $image['upload_file_size'],
					'image_file_type'			=> $image['upload_file_type'],
					'image_approved'			=> 1,
					'image_thumbnail'			=> $image['upload_thumb_name'] ? 1 : 0,
					'image_date'				=> $image['upload_date'],
					'image_metadata'			=> $image['upload_exif'],
					'image_copyright'			=> $image['upload_copyright'],
					'image_feature_flag'		=> $image['upload_feature_flag'],
					'image_gps_show'			=> $image_gps_show,
					'image_gps_raw'				=> serialize( $gpsRaw ),
					'image_gps_lat'				=> $latLon[0],
					'image_gps_lon'				=> $latLon[1],
					'image_loc_short'			=> $locShort,
					'image_data'				=> $image['upload_data'],
					'image_media'				=> $this->registry->gallery->helper('media')->isAllowedExtension( $image['upload_file_orig_name'] ) ? 1 : 0,
					'image_media_thumb'			=> $this->registry->gallery->helper('media')->isAllowedExtension( $image['upload_file_orig_name'] ) ? $image['upload_thumb_name'] : '',
					'image_media_data'			=> ( IPSLib::isSerialized( $image['upload_media_data'] ) ) ? $image['upload_media_data'] : @serialize( $image['upload_media_data'] ),
					'image_caption_seo'			=> IPSText::makeSeoTitle( $image['upload_title'] )
					);
	}
	
	/**
	 * Remap a image row as a tmp upload row table
	 *
	 * @param	array		$image		Image data
	 * @return	@e array	Remapped image data
	 */
	public function _remapAsUpload( array $image )
	{
		//-----------------------------------------
		// Reformat the serialized arrays
		//-----------------------------------------

		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$_data	= IPSLib::safeUnserialize( $image['image_data'] );
		}
		else if ( IPSLib::isSerialized( $image['upload_data'] ) )
		{
			$_data	= IPSLib::safeUnserialize( $image['upload_data'] );
		}
		
		if ( ! is_array( $_data ) )
		{
			$_data = array();
		}

		//-----------------------------------------
		// Format thumbnail name
		//-----------------------------------------

		$_thumbName	= ( $image['image_masked_file_name'] ) ? 'tn_' . $image['image_masked_file_name'] : $image['upload_thumb_name'];
		
		if ( isset( $image['image_media_thumb'] ) OR $this->registry->gallery->helper('media')->isAllowedExtension( $image['image_masked_file_name'] ) )
		{
			$_thumbName	= $image['image_media_thumb'];
		}

		//-----------------------------------------
		// Format the return array..
		//-----------------------------------------

		$arr	= array(
						'upload_member_id'			=> ( $image['image_member_id'] )					? $image['image_member_id']				: $image['upload_member_id'],
						'upload_album_id'			=> ( $image['image_album_id'] )						? $image['image_album_id']				: $image['upload_album_id'],
						'upload_category_id'		=> ( $image['image_category_id'] )					? $image['image_category_id']			: $image['upload_category_id'],
						'upload_title'				=> ( $image['image_caption'] )						? $image['image_caption']				: $image['upload_title'],
						'upload_description'		=> ( $image['image_description'] )					? $image['image_description']			: $image['upload_description'],
						'upload_file_directory'		=> ( $image['image_directory'] )					? $image['image_directory']				: $image['upload_file_directory'],
						'upload_file_name'			=> ( isset( $image['image_masked_file_name'] ) )	? $image['image_masked_file_name']		: $image['upload_file_name'],
						'upload_medium_name'		=> ( isset( $image['image_medium_file_name'] ) )	? $image['image_medium_file_name']		: $image['upload_medium_name'],
						'upload_file_name_original'	=> ( isset( $image['image_original_file_name'] ) )	? $image['image_original_file_name']	: $image['upload_file_name_original'],
						'upload_file_orig_name'		=> ( isset( $image['image_file_name'] ) )			? $image['image_file_name']				: $image['upload_file_orig_name'],
						'upload_file_size'			=> ( isset( $image['image_file_size'] ) )			? $image['image_file_size']				: $image['upload_file_size'],
						'upload_file_type'			=> ( isset( $image['image_file_type'] ) )			? $image['image_file_type']				: $image['upload_file_type'],
						'upload_thumb_name'			=> $_thumbName,
						'upload_date'				=> ( $image['image_date'] )							? $image['image_date']					: $image['upload_date'],
						'upload_exif'				=> ( $image['image_metadata'] )						? $image['image_metadata']				: $image['upload_exif'],
						'upload_copyright'			=> ( $image['image_copyright'] )					? $image['image_copyright']				: $image['upload_copyright'],
						'upload_feature_flag'		=> ( $image['image_feature_flag'] )					? $image['image_feature_flag']			: $image['upload_feature_flag'],
						'upload_media_data'			=> ( IPSLib::isSerialized( $image['image_media_data'] ) )	? $image['image_media_data']	: @serialize( $image['image_media_data'] ),
						'upload_data'				=> ''
						);

		//-----------------------------------------
		// This a cover image
		//-----------------------------------------

		if ( ( isset( $image['_isCover'] ) AND $image['_isCover'] ) OR ( isset( $image['album_cover_img_id'] ) AND $image['album_cover_img_id'] == $image['image_id'] ) )
		{
			$_data['_isCover']	= 1;
		}

		//-----------------------------------------
		// Autofollowing?
		//-----------------------------------------

		if ( ( isset( $image['_follow'] ) AND $image['_follow'] ) )
		{
			$_data['_follow']	= 1;
		}

		//-----------------------------------------
		// Throw in GPS data
		//-----------------------------------------

		if ( isset( $image['image_gps_show'] ) )
		{
			$_data['image_gps_show'] = $image['image_gps_show'];
		}
		
		//-----------------------------------------
		// Add the serialized upload data
		//-----------------------------------------

		$arr['upload_data'] = serialize( $_data );
		
		//-----------------------------------------
		// Remove empty rows and return
		//-----------------------------------------

		foreach( $arr as $k => $v )
		{
			if ( $k AND $v === null)
			{
				unset( $arr[ $k ] );
			}
		}
		
		return $arr;
	}
}