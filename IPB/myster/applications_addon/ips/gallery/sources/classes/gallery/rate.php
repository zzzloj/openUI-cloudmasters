<?php
/**
 * @file		rate.php 	Gallery rating class
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-08-27 19:35:36 -0400 (Mon, 27 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11287 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		gallery_rate
 * @brief		Gallery rating class
 */
class gallery_rate
{
	/**
	 * Can rate flag
	 *
	 * @var		bool
	 */
	public $can_rate		= false;
	
	/**
	 * Stored error message
	 *
	 * @var		string
	 */
	private $errorMessage;
	
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
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */	
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Return error message
	 *
	 * @return	@e string
	 */
	public function getError()
	{
		return $this->errorMessage;
	}
	
	/**
	 * Add ALBUM rate
	 *
	 * @param	integer	$rating
	 * @param	integer	[$albumId]
	 * @param	bool	Return the album data too
	 * @return	@e mixed	Array of data, or false
	 */	
	public function rateAlbum( $rating, $albumId, $returnData=false )
	{
		//-----------------------------------------
		// Get album and INIT
		//-----------------------------------------

		$album	= $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		$save	= array();
		
		//-----------------------------------------
		// Can we rate
		//-----------------------------------------

		if ( $this->canRate( $album['album_id'], null, 'album' ) != true )
		{
			$this->errorMessage = 'no_permission';
			return false;
		}
		
		//-----------------------------------------
		// Verify rating is valid
		//-----------------------------------------

		if ( $rating > 5 OR $rating <= 0 )
		{
			$this->errorMessage = 'invalid_rating';
			return false;
		}
		
		//-----------------------------------------
		// Check if we've already rated
		//-----------------------------------------

		$myVote = $this->getMyRating( $albumId, 'album' );
		
		if ( $myVote !== false && ! empty( $myVote['rate_id'] ) )
		{
			//-----------------------------------------
			// Already rated - update
			//-----------------------------------------

			$this->DB->delete( 'gallery_ratings', 'rate_id=' . intval( $myVote['rate_id'] ) );
			
			//-----------------------------------------
			// Tweak numbers
			//-----------------------------------------

			$album['album_rating_total']	= $album['album_rating_total'] - $myVote['rate_rate'];
			$album['album_rating_count']--;
		}		

		//-----------------------------------------
		// Insert the rating
		//-----------------------------------------

		$this->DB->insert( 'gallery_ratings', array( 'rate_member_id'	=> $this->memberData['member_id'],
													 'rate_type_id'		=> $albumId,
													 'rate_type'		=> 'album',
													 'rate_date'		=> time(),
													 'rate_rate'		=> $rating ) );
		
		//-----------------------------------------
		// Update the album
		//-----------------------------------------

		$save['album_rating_total']		= $album['album_rating_total'] + $rating;
		$save['album_rating_count']		= $album['album_rating_count'] + 1;
		$save['album_rating_aggregate']	= round( $save['album_rating_total'] / $save['album_rating_count'] );
		
		$this->DB->update( "gallery_albums", $save, 'album_id=' . $album['album_id'] );

		//-----------------------------------------
		// Format the return array and return
		//-----------------------------------------

		$return	= array( 'total' => $save['album_rating_total'], 'aggregate' => $save['album_rating_aggregate'], 'count' => $save['album_rating_count'] );

		if ( $returnData )
		{
			$return['albumData']	= $album;
		}
		
		return $return;
	}
	
	/**
	 * Add IMAGE rate
	 *
	 * @param	integer	$rating
	 * @param	integer	[$imageId]
	 * @param	bool	Return the image data too
	 * @return	@e mixed	Array of data, or false
	 */	
	public function rateImage( $rating, $imageId, $returnData=false )
	{
		//-----------------------------------------
		// Fetch image and INIT
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $imageId );
		$save	= array();
		
		//-----------------------------------------
		// Can we rate?
		//-----------------------------------------

		if ( $this->canRate( $image['image_album_id'] ? $image['image_album_id'] : $image['image_category_id'], null, $image['image_album_id'] ? 'album' : 'category' ) != true )
		{
			$this->errorMessage = 'no_permission';
			return false;
		}

		//-----------------------------------------
		// Make sure rating is valid
		//-----------------------------------------

		if ( $rating > 5 OR $rating <= 0 )
		{
			$this->errorMessage = 'invalid_rating';
			return false;
		}
		
		//-----------------------------------------
		// Have we already rated?
		//-----------------------------------------

		$myVote = $this->getMyRating( $imageId, 'image' );
	
		if ( $myVote !== false && ! empty( $myVote['rate_id'] ) )
		{
			//-----------------------------------------
			// Already rated - update
			//-----------------------------------------

			$this->DB->delete( 'gallery_ratings', 'rate_id=' . intval( $myVote['rate_id'] ) );
			
			//-----------------------------------------
			// Tweak numbers
			//-----------------------------------------

			$image['image_ratings_total']	= $image['image_ratings_total'] - $myVote['rate_rate'];
			$image['image_ratings_count']--;
		}			
	
		//-----------------------------------------
		// Insert rating
		//-----------------------------------------

		$this->DB->insert( 'gallery_ratings', array( 'rate_member_id'	=> $this->memberData['member_id'],
													 'rate_type_id'		=> $imageId,
													 'rate_type'		=> 'image',
													 'rate_date'		=> time(),
													 'rate_rate'		=> $rating ) );
		
		//-----------------------------------------
		// Update the image
		//-----------------------------------------

		$save['image_ratings_total']	= $image['image_ratings_total'] + $rating;
		$save['image_ratings_count']	= $image['image_ratings_count'] + 1;
		$save['image_rating']			= round( $save['image_ratings_total'] / $save['image_ratings_count'] );
		
		$this->DB->update( "gallery_images", $save, 'image_id=' . $image['image_id'] );
		
		//-----------------------------------------
		// Formate the return array and return
		//-----------------------------------------

		$return = array( 'total' => $save['image_ratings_total'], 'aggregate' => $save['image_rating'], 'count' => $save['image_ratings_count'] );

		if ( $returnData )
		{
			$return['imageData'] = $image;
		}
		
		return $return;
	}
	
	/**
	 * Fetch the rating we gave it
	 *
	 * @param	integer	Type ID
	 * @param	string	Type
	 * @param	mixed	Member ID, array of member data, or null for current member
	 * @return	@e mixed	Rate array or false
	 */		
	public function getMyRating( $foreignId, $where='image', $memberId=null )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$member	= ( is_array( $memberId ) ) ? $memberId : ( $memberId === null ? $this->memberData : IPSMember::load( $memberId, 'all' ) );
		$where	= ( $where == 'album' ) ? 'album' : 'image';
		
		if ( ! $member['member_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Get and return the rating
		//-----------------------------------------

		$myRate	= $this->DB->buildAndFetch( array(
												'select'	=> '*',
												'from'		=> 'gallery_ratings',
												'where'		=> 'rate_type_id=' . intval($foreignId) . ' AND rate_member_id=' . intval( $member['member_id'] ) . " AND rate_type='{$where}'" ) );
		
		return ( $myRate['rate_rate'] ) ? $myRate : false;
	}
	
	/**
	 * Get table joins
	 * 
	 * @param	string	$joinField
	 * @param	string	$where
	 * @param	int		$memberId
	 * @return	@e array
	 */
	public function getTableJoins( $joinField, $where, $memberId )
	{
		$where	= ( $where == 'album' ) ? 'album' : 'image';
		
		if ( ! $memberId )
		{
			return false;
		}
		
		return array( 'select' => 'rating.rate_rate, rating.rate_date',
					  'from'   => array( 'gallery_ratings' => 'rating' ),
					  'where'  => "rating.rate_type_id={$joinField} AND rating.rate_member_id=" . intval( $memberId ) . " AND rating.rate_type='{$where}'"
					 );
	}
	
	/**
	 * Can we rate?
	 * 
	 * @param	int		$typeId		Category or album ID
	 * @param	int		$memberId	Defaults to current member
	 * @param	string	$where		What type of container (category or album)
	 * @return	@e bool
	 */
	public function canRate( $typeId, $memberId=null, $where='album' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------

		$member	= ( is_array( $memberId ) ) ? $memberId : ( $memberId === null ? $this->memberData : IPSMember::load( $memberId, 'all' ) );

		if( $where == 'album' )
		{
			$album		= ( is_numeric( $typeId ) ) ? $this->registry->gallery->helper('albums')->fetchAlbumsById( $typeId ) : $typeId;
		}
		else
		{
			$category	= ( is_numeric( $typeId ) ) ? $this->registry->gallery->helper('categories')->fetchCategory( $typeId ) : $typeId;
		}
		
		//-----------------------------------------
		// Is member allowed to rate?
		//-----------------------------------------

		if ( ! $member['member_id'] )
		{
			return false;
		}
		
		if ( ! $member['g_topic_rate_setting'] )
		{
			return false;
		}

		if( $where == 'album' )
		{
			if( !$album['album_allow_rating'] )
			{
				return false;
			}

			if( $member['member_id'] == $album['album_owner_id'] )
			{
				return false;
			}
		}
		else
		{
			if( !$category['category_allow_rating'] )
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Does container allow rating?
		//-----------------------------------------

		switch( $where )
		{
			case 'album':
				return $this->registry->gallery->helper('categories')->member_access['rate'][ $album['album_category_id'] ];
			break;

			case 'category':
				return $this->registry->gallery->helper('categories')->member_access['rate'][ $category['category_id'] ];
			break;
		}

		return true;
	}

	/**
	 * Does the container allow ratings? Helps to distinguish between feature enabled and permission disabled.
	 * 
	 * @param	int		$typeId		Category or album ID
	 * @param	int		$memberId	Defaults to current member
	 * @param	string	$where		What type of container (category or album)
	 * @return	@e bool
	 */
	public function containerCanRate( $typeId, $memberId=null, $where='album' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------

		$member	= ( is_array( $memberId ) ) ? $memberId : ( $memberId === null ? $this->memberData : IPSMember::load( $memberId, 'all' ) );

		if( $where == 'album' )
		{
			$album		= ( is_numeric( $typeId ) ) ? $this->registry->gallery->helper('albums')->fetchAlbumsById( $typeId ) : $typeId;
		}
		else
		{
			$category	= ( is_numeric( $typeId ) ) ? $this->registry->gallery->helper('categories')->fetchCategory( $typeId ) : $typeId;
		}

		if( $where == 'album' )
		{
			if( !$album['album_allow_rating'] )
			{
				return false;
			}
		}
		else
		{
			if( !$category['category_allow_rating'] )
			{
				return false;
			}
		}

		return true;
	}
}
