<?php
/**
 * @file		rssOutput.php 	Gallery global RSS output
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since	July 11 2012
 * $LastChangedDate: 2012-06-07 17:59:27 -0400 (Thu, 07 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10892 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		rss_output_gallery
 * @brief		Gallery global RSS output
 */
class rss_output_gallery
{
	/**
	 * Expiration date
	 *
	 * @var		integer			Expiration timestamp
	 */
	protected $expires			= 0;
	
	/**
	 * RSS object
	 *
	 * @var		object
	 */
	public $class_rss;

	/**
	 * Grab the RSS links
	 *
	 * @return	@e array
	 */
	public function getRssLinks()
	{
		$return	= array();

		if( !IPSLib::appIsInstalled('gallery') OR ( defined('IPS_IS_INSTALLER') AND IPS_IS_INSTALLER ) OR !ipsRegistry::$settings['gallery_rss_enabled'] )
		{
			return $return;
		}

		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_gallery' ), 'gallery' );

		$return[] = array( 'title' => ipsRegistry::getClass('class_localization')->words['gallery_global_rsstitle'], 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=gallery", true, 'section=rss' ) );

	    return $return;
	}
	
	/**
	 * Grab the RSS document content and return it
	 *
	 * @return	@e string
	 */
	public function returnRSSDocument()
	{
		//--------------------------------------------
		// Get language strings
		//--------------------------------------------

		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_gallery' ), 'gallery' );

		//-----------------------------------------
		// Get our Gallery object
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			ipsRegistry::setClass( 'gallery', new $classToLoad( ipsRegistry::instance() ) );
		}

		//--------------------------------------------
		// Get RSS library
		//--------------------------------------------
		
		$classToLoad				= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$this->class_rss			= new $classToLoad();
		$this->class_rss->doc_type	= ipsRegistry::$settings['gb_char_set'];
		
		if( !ipsRegistry::$settings['gallery_rss_enabled'] )
		{
			return $this->_returnError( $this->lang->words['rss_disabled'] );
		}
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 0;
        
		$channel_id = $this->class_rss->createNewChannel( array( 'title'		=> ipsRegistry::getClass('class_localization')->words['gallery_global_rsstitle'],
																 'link'			=> ipsRegistry::getClass('output')->buildSEOUrl( "app=gallery", 'publicNoSession', 'false', 'app=gallery' ),
																 'pubDate'		=> $this->class_rss->formatDate( time() ),
																 'ttl'			=> 30 * 60,
																 'description'	=> ipsRegistry::getClass('class_localization')->words['gallery_rss_desc']
													)      );

		$recents	= ipsRegistry::getClass('gallery')->helper('image')->fetchImages( 0, array( 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
		
		foreach( $recents as $id => $image )
		{
			$this->class_rss->addItemToChannel( $channel_id , array(
															'title'				=> $image['image_caption'],
															'link'				=> ipsRegistry::getClass('output')->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ),
															'description'		=> ipsRegistry::getClass('gallery')->helper('image')->makeImageLink( $image, array( 'type' => 'small', 'link-type' => 'page' ) ) . "<br />" . IPSText::getTextClass('bbcode')->preDisplayParse( $image['image_description'] ),
															'pubDate'			=> $this->class_rss->formatDate( $image['image_date'] ),
															'guid'				=> ipsRegistry::getClass('output')->buildSEOUrl( "app=gallery&amp;image=" . $image['image_id'], 'publicNoSession', $image['image_caption_seo'], 'viewimage' ) 
										)					);
		}

		$this->class_rss->createRssDocument();
		
		$this->class_rss->rss_document = ipsRegistry::getClass('output')->replaceMacros( $this->class_rss->rss_document );

		return $this->class_rss->rss_document;
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @return	@e integer
	 */
	public function grabExpiryDate()
	{
		// Generated on the fly, so just return expiry of one hour
		return time() + 3600;
	}
	
	/**
	 * Return an error document
	 *
	 * @access	protected
	 * @param	string			Error message
	 * @return	string			XML error document for RSS request
	 */
	protected function _returnError( $error='' )
	{
		$channel_id = $this->class_rss->createNewChannel( array( 
															'title'			=> ipsRegistry::getClass('class_localization')->words['rss_disabled'],
															'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=downloads", 'false', 'app=downloads' ),
				 											'description'	=> ipsRegistry::getClass('class_localization')->words['rss_disabled'],
				 											'pubDate'		=> $this->class_rss->formatDate( time() ),
				 											'webMaster'		=> ipsRegistry::$settings['email_in'] . " (" . ipsRegistry::$settings['board_name'] . ")",
				 											'generator'		=> 'IP.Gallery'
				 										)		);

		$this->class_rss->addItemToChannel( $channel_id, array( 
														'title'			=> ipsRegistry::getClass('class_localization')->words['rss_error_message'],
			 										    'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=gallery", 'false', 'app=gallery' ),
			 										    'description'	=> $error,
			 										    'pubDate'		=> $this->class_rss->formatDate( time() ),
			 										    'guid'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=gallery&error=1", 'false', 'app=gallery' ) ) );

		//-----------------------------------------
		// Do the output
		//-----------------------------------------

		$this->class_rss->createRssDocument();

		return $this->class_rss->rss_document;
	}
}