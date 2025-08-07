<?php
/**
 * @file		furlRedirect.php 	IP.Gallery FURL redirect plugin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-06-25 20:53:17 -0400 (Mon, 25 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10984 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_gallery
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @var		string
	 */
	private $_type	= '';
	
	/**
	 * Key ID
	 *
	 * @var		int
	 */
	private $_id	= 0;
	
	/**
	 * Constructor
	 *
	 * @param 	object 	Registry
	 * @return 	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 *
	 * @param	string	Type
	 * @param	mixed	Value
	 * @return 	@e void
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	@e void
	 */
	public function setKeyByUri( $uri )
	{
		if( IN_ACP )
		{
			return FALSE;
		}
		
		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		if( $uri == 'app=gallery' )
		{
			$this->setKey( 'app', 'gallery' );
			return TRUE;			
		}
		else
		{
			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );
				
				if ( $k )
				{
					if ( $k == 'image' || ( $k == 'img' && $_REQUEST['module'] != 'post' ) )
					{
						$this->setKey( 'image', intval( $v ) );
						return TRUE;
					}
					
					if ( $k == 'album' && ( empty($_REQUEST['do']) || $_REQUEST['do'] != 'delete' ) )
					{
						$this->setKey( 'album', intval( $v ) );
						return TRUE;
					}

					if ( $k == 'category' && ( empty($_REQUEST['section']) || $_REQUEST['section'] != 'slideshow' ) )
					{
						$this->setKey( 'category', intval( $v ) );
						return TRUE;
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Return the SEO title
	 *
	 * @return	@e string		The SEO friendly name
	 */
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'image';
				return $this->_fetchSeoTitle_image();
			break;
			case 'album';
				return $this->_fetchSeoTitle_album();
			break;
			case 'category';
				return $this->_fetchSeoTitle_category();
			break;
			case 'app':
				return $this->_fetchSeoTitle_app();
			break;
		}
	}
	
	/**
	 * Return the SEO title for an album
	 *
	 * @return	@e string		The SEO friendly name
	 */
	public function _fetchSeoTitle_album()
	{
		/* Query the album */
		$album = $this->DB->buildAndFetch( array( 'select' => 'album_id, album_name, album_name_seo', 'from' => 'gallery_albums', 'where' => "album_id={$this->_id}" ) );

		/* Make sure we have an album */
		if ( $album['album_id'] )
		{
			return $album['album_name_seo'] ? $album['album_name_seo'] : IPSText::makeSeoTitle( $album['album_name'] );
		}
	}

	/**
	 * Return the SEO title for a category
	 *
	 * @return	@e string		The SEO friendly name
	 */
	public function _fetchSeoTitle_category()
	{
		/* Query the category */
		$category	= $this->registry->gallery->helper('categories')->fetchCategory( $this->_id );

		/* Make sure we have a category */
		if ( $category['category_id'] )
		{
			return $category['category_name_seo'] ? $category['category_name_seo'] : IPSText::makeSeoTitle( $category['category_name'] );
		}
	}

	/**
	 * Return the SEO title for an image
	 *
	 * @return	@e string		The SEO friendly name
	 */
	public function _fetchSeoTitle_image()
	{
		/* Query the image */
		$img = $this->DB->buildAndFetch( array( 'select' => 'image_id,image_caption,image_caption_seo', 'from' => 'gallery_images', 'where' => "image_id={$this->_id}" ) );

		/* Make sure we have an image */
		if( $img['image_id'] )
		{
			return $img['image_caption_seo'] ? $img['image_caption_seo'] : IPSText::makeSeoTitle( $img['image_caption'] );
		}
	}

	/**
	* Return the base gallery SEO title
	*
	* @access	public
	* @return	@e string
	*/
	public function _fetchSeoTitle_app()
	{
		$_SEOTEMPLATES = array();
		
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Try to figure out what is used in furlTemplates.php */
			@include( IPSLib::getAppDir( 'gallery' ) . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=gallery']['out'][1] )
			{
				return $_SEOTEMPLATES['app=gallery']['out'][1];
			}
			else
			{
				return 'gallery/';
			}
		}
	}
}