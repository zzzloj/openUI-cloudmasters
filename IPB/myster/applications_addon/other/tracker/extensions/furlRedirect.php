<?php

/**
* Tracker 2.1.0
*
* FURL Redirects
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author 		$Author: stoo2000 $
* @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @since		4/24/2010
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_IPB' ) )
{
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
    exit();
}

class furlRedirect_tracker
{
	/**
	 * Key type
	 *
	 * @var			string
	 * @access	private
	 */
	private $_type;

	/**
	 * Key ID
	 *
	 * @var			integer
	 * @access	private
	 */
	private $_id;

	/**
	 * Constructor - hook us up with the registry
	 *
	 * @param		object		$registry		ipsRegistry instance
	 * @access	public
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
		
		if ( ! $registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $registry );
	
			$registry->setClass( 'tracker', $this->tracker );
		}
		
		$this->tracker	= $registry->getClass('tracker');
	}

	/**
	 * Set the key ID
	 *
	 * @param		string		$name		Name of key to set
	 * @param		integer		$value	Value of key ID to set
	 * @return	void
	 * @access	public
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}

	/**
	 * Set up the key by URI
	 *
	 * @param		string		$uri		URI to set up from
	 * @return	boolean		True on match
	 * @access	public
	 */
	public function setKeyByUri( $uri )
	{
		if ( IN_ACP )
		{
			return false;
		}

		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}

		if ( $uri == 'app=tracker' )
		{
			$this->setKey( 'app', 'tracker' );
			return true;
		}
		else
		{
			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );

				if ( $k )
				{
					if ( $k == 'showproject' )
					{
						$this->setKey( 'project', intval( $v ) );
						return TRUE;
					}
					else if ( $k == 'showissue' )
					{
						$this->setKey( 'issue', intval( $v ) );
						return TRUE;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Return the SEO title
	 *
	 * @param		void
	 * @return	string		The SEO friendly name
	 * @access	public
	 */
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			case 'app':
				return 'tracker';
				break;
				
			case 'project':
				return $this->_fetchProjectSeoTitle();
				break;
			
			case 'issue':
				return $this->_fetchIssueSeoTitle();
				break;
		}

		return false;
	}
	
	/**
	 * Return the project's SEO title
	 *
	 * @param		void
	 * @return	string	SEO title
	 * @access	private
	 */
	private function _fetchProjectSeoTitle()
	{
		// Are we in filters?
		if ( isset( $_GET['changefilter'] ) )
		{
			return false;
		}
		
		$project = $this->tracker->projects()->getProject( intval( $this->_id ) );
		
		if ( $project['project_id'] )
		{
			if ( ! $this->tracker->projects()->checkPermission( 'show', $project['project_id'] ) )
			{
				return false;
			}
			
			return IPSText::makeSeoTitle( $project['title'] );
		}
		
		return false;
	}
	
	/**
	 * Return the issue's SEO title
	 *
	 * @param		void
	 * @return	string	SEO title
	 * @access	private
	 */
	private function _fetchIssueSeoTitle()
	{
		$issue = $this->DB->buildAndFetch( array( 'select' => 'issue_id, title',
																							'from'   => 'tracker_issues',
																							'where'  => 'issue_id = ' . intval( $this->_id ) ) );

		if ( $issue['issue_id'] )
		{
			return IPSText::makeSeoTitle( $issue['title'] );
		}
		
		return false;
	}
}

?>