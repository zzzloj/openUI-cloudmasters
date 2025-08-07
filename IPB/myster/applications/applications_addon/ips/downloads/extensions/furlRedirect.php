<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_downloads
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_type = '';
	
	/**
	 * Key ID
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_id = 0;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 * <code>furlRedirect_forums::setKey( 'topic', 12 );</code>
	 *
	 * @access	public
	 * @param	string	Type
	 * @param	mixed	Value
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @access	public
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
		
		if( $uri == 'app=downloads' )
		{
			$this->setKey( 'app', 'downloads' );
			return TRUE;			
		}
		else
		{
			$_section	= '';

			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );

				if ( $k )
				{
					if ( $k == 'showcat' )
					{
						$this->setKey( 'cat', intval( $v ) );
						return TRUE;
					}
					else if ( $k == 'showfile' )
					{
						$this->setKey( 'file', intval( $v ) );
						return TRUE;
					}
					else if( $k == 'section' )
					{
						$_section	= $v;
					}
					else if( $k == 'id' AND $_section == 'file' )
					{
						$this->setKey( 'file', intval( $v ) );
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
	 * @access	public
	 * @return	string		The SEO friendly name
	 */
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'app':
				return $this->_fetchSeoTitle_app();
			break;
			case 'cat':
				return $this->_fetchSeoTitle_cat();
			break;
			case 'file':
				return $this->_fetchSeoTitle_file();
			break;
		}
	}

	/**
	 * Return the base idm SEO title
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_app()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Try to figure out what is used in furlTemplates.php */
			$_SEOTEMPLATES = array();
			@include( IPSLib::getAppDir( 'downloads' ) . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=downloads']['out'][1] )
			{
				return $_SEOTEMPLATES['app=downloads']['out'][1];
			}
			else
			{
				return 'downloads/';
			}
		}
	}
	
	/**
	 * Return the IDM seo title for cat
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_cat()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the cat */
			ipsRegistry::getAppClass( 'downloads' );
			$cat	= $this->registry->categories->cat_lookup[ $this->_id ];
	
			/* Make sure we have an image */
			if( $cat['cid'] )
			{
				return $cat['cname_furl'] ? $cat['cname_furl'] : IPSText::makeSeoTitle( $cat['cname'] );
			}
		}
	}
	
	/**
	 * Return the IDM seo title for file
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_file()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the file */
			$file	= $this->DB->buildAndFetch( array( 'select' => 'file_id, file_name, file_name_furl', 'from' => 'downloads_files', 'where' => "file_id={$this->_id}" ) );
	
			/* Make sure we have an image */
			if( $file['file_id'] )
			{
				return $file['file_name_furl'] ? $file['file_name_furl'] : IPSText::makeSeoTitle( $file['file_name'] );
			}
		}
	}
}