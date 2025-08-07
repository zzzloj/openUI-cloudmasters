<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_blog
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
	*/
	function __construct( ipsRegistry $registry )
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
		$return = FALSE;
		
		if( IN_ACP )
		{
			return FALSE;
		}
		
		$uri = str_replace( '&amp;', '&', $uri );
		
		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		if( $uri == 'app=blog' )
		{
			$this->setKey( 'app', 'blog' );
			return TRUE;			
		}
		else
		{
			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );
				
				if ( $k )
				{
					if ( $k == 'showentry' )
					{
						$this->setKey( 'showentry', intval( $v ) );
						$return = TRUE;
						break; # Break here if we've found an entry value
					}
					else if( $k == 'blogid' )
					{
						$this->setKey( 'showblog', intval( $v ) );
						$return = TRUE;
						# Don't break here as we might have 'showentry' later on
					}
				}
			}
		}
		
		return $return;
	}
	
	/**
	* Return the SEO title
	*
	* @access	public
	* @return	string		The SEO friendly name
	*/
	public function fetchSeoTitle()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			switch ( $this->_type )
			{
				default:
					return FALSE;
				break;
				case 'showblog';
					return $this->_fetchSeoTitle_blog();
				break;
				case 'showentry';
					return $this->_fetchSeoTitle_entry();
				break;
				case 'app':
					return $this->_fetchSeoTitle_app();
				break;
			}
		}
		
		return;
	}
	
	public function _fetchSeoTitle_blog()
	{
		if( $_GET['module'] != 'display' )
		{
			return;	
		}
		
		/* Query the image */
		$blog = $this->DB->buildAndFetch( array( 'select' => 'blog_id,blog_name,blog_seo_name', 'from' => 'blog_blogs', 'where' => "blog_id={$this->_id}" ) );

		/* Make sure we have an image */
		if( $blog['blog_id'] )
		{
			return $blog['blog_seo_name'] ? $blog['blog_seo_name'] : IPSText::makeSeoTitle( $blog['blog_name'] );
		}
	}
	
	public function _fetchSeoTitle_entry()
	{
		/* Query the image */
		$entry = $this->DB->buildAndFetch( array( 'select' => 'entry_id,entry_name,entry_name_seo', 'from' => 'blog_entries', 'where' => "entry_id={$this->_id}" ) );

		/* Make sure we have an image */
		if( $entry['entry_id'] )
		{
			return $entry['entry_name_seo'] ? $entry['entry_name_seo'] : IPSText::makeSeoTitle( $entry['entry_name'] );
		}
	}
	
	/**
	* Return the base blog SEO title
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
			@include( IPSLib::getAppDir('blog') . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=blog']['out'][1] )
			{
				return $_SEOTEMPLATES['app=blog']['out'][1];
			}
			else
			{
				return 'blogs/';
			}
		}
	}
}