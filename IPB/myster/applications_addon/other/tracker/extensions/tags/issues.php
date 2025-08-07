<?php

/**
* Tracker 2.1.0
*
* Tagging system
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author 		$Author: stoo2000 $
* @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @since		4/24/2010
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_tracker_issues extends classes_tag_abstract
{
	protected $issueCache = array();
		
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
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
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		if ( ! $this->registry->isClassLoaded('app_class_tracker') )
		{
			ipsRegistry::getAppClass( 'tracker' );
		}
		
		parent::init();
	}
	
	/**
	 * Little 'trick' to force preset tags
	 *
	 * @param	string	view to show
	 * @param	array	Where data to show
	 */
	public function render( $what, $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$project = $this->registry->tracker->projects()->getProject( $where['meta_parent_id'] );
		}
		
		if ( ! empty( $project['tag_predefined'] ) )
		{
			/* Turn off open system */
			$this->settings['tags_open_system'] = false;
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$issue = $this->_getIssue( $where['meta_id'] );
		
		return intval( $issue['project_id'] );
	}
	
	/**
	 * Fetches permission data
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( isset( $where['meta_id'] ) )
		{
			$issue = $this->_getIssue( $where['meta_id'] );
			$project = $this->registry->tracker->projects()->getProject( $issue['project_id'] );
		}
		else if ( isset( $where['meta_parent_id'] ) )
		{
			$project = $this->registry->tracker->projects()->getProject( $where['meta_parent_id'] );
		}
		
		// Grab the permissions
		$perms	= $this->registry->tracker->projects()->getPerms( $project['project_id'] );
		
		return $perms['perm_view'];
	}
	
	/**
	 * Basic permission check
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		$issue = array();
		$project = array();
		
		if ( ! empty( $where['meta_id'] ) )
		{
			$issue = $this->_getIssue( $where['meta_id'] );
			$project = $this->registry->tracker->projects()->getProject( $issue['project_id'] );
		}
		else if ( ! empty( $where['meta_parent_id'] ) )
		{
			$project = $this->registry->tracker->projects()->getProject( $where['meta_parent_id'] );
		}
		
		/* Check parent */
		$return = parent::can( $what, $where );
		
		if ( $return !== null )
		{
			return $return;
		}
		
		/* Project disabled */
		if ( $project['disable_tagging'] )
		{
			return false;
		}
		
		// Permissions
		$perms	= $this->registry->tracker->projects()->getPerms( $project['project_id'] );
		
		switch ( $what )
		{
			case 'create':				
				return true;
			break;
			case 'add':
				if ( $this->registry->tracker->perms()->check( 'start', $perms ) )
				{
					return true;
				}
			break;
			case 'edit':
			case 'remove':
				if ( $this->memberData['member_id'] == $issue['starter_id'] )
				{
					return true;
				}
			break;
			case 'prefix':
				return false;
			break;
		}
		
		return false;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		$issue    = $this->_getIssue( $where['meta_id'] );
		
		return ( ! $issue['module_privacy'] ) ? 1 : 0;
	}
	
	/**
	 * Search for tags
	 * @param mixed $tags	Array or string
	 * @param array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 */
	public function search( $tags, $options )
	{
		$ok = array();
		
		/* Fix up project IDs */
		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->_canSearchProject( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->_canSearchProject( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}
		else
		{
			/* Fetch project IDs */
			$ok = $this->registry->tracker->projects()->getSearchableProjects();
		}
		
		$options['meta_parent_id'] = $ok;
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	Array of pre-defined tags or null
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Can set an item as an issue prefix
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e boolean
	 */
	protected function _prefixesEnabled( $where )
	{
		return false;
	}
	
	/**
	 * Check a project for tag searching
	 * 
	 * @param	id		$id		Project ID
	 * @return	@e boolean	True if it can be searched
	 */
	protected function _canSearchProject( $id )
	{
		$data =  $this->registry->tracker->projects()->getProject( $id );
		
		// Project perms
		$perms = $this->registry->tracker->projects()->getPerms( $id );
					
		if ( ! $this->registry->tracker->perms()->check( 'read', $perms ) )
		{
			return false;
		}

		if ( $data['cat_only'] )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Fetch an issue
	 * 
	 * @param	integer		$iid	Issue ID
	 * @return	@e array	Issue data
	 */
	protected function _getIssue( $iid )
	{
		if ( ! isset( $this->issueCache[ $iid ] ) )
		{
			$this->issueCache[ $iid ] = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'tracker_issues',
					'where'		=> 'issue_id=' . intval( $iid )
				)
			);
		}
		
		return $this->issueCache[ $iid ];
	}
}