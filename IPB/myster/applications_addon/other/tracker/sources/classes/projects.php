<?php

/**
* Tracker 2.1.0
*
* Projects controller class file
* Last Updated: $Date: 2013-03-01 17:59:36 +0000 (Fri, 01 Mar 2013) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1408 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}
// Have to load the cache if it isn't already
if ( ! class_exists('tracker_cache') )
{
	require_once( IPSLib::getAppDir('tracker') . '/sources/classes/library.php' );
}

/**
 * Type: Library
 * Projects controller class
 *
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_projects extends iptCommand
{
	/**
	 * Projects cache controller reference
	 *
	 * @access protected
	 * @var tracker_core_cache_projects
	 * @since 2.0.0
	 */
	protected $cache;

	/**
	 * The building block for indented dropdown menus
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $depthGuide = '--';

	/**
	 * Initial function.  Called by execute function in ipsCommand
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache = $this->tracker->cache('projects');
	}

	/**
	 * Alias for createPermShortcuts
	 *
	 * @param int $projectID The project ID that needs permissions
	 * @return void result is merged in to $this->member
	 * @access public
	 * @since 2.0.0
	 */
	public function buildPermissions( $projectID )
	{
		return $this->createPermShortcuts( $projectID );
	}

	/**
	 * Creates breadcrumb lines for both public and admin applications
	 *
	 * @param int $projectID the project that's at the end of the breadcrumb
	 * @param string $app default is public, admin needs to be set for ACP crumbs
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function createBreadcrumb( $projectID, $app='public' )
	{
		$projects = $this->cache->getBreadcrumbs( $projectID );

		if ( is_array( $projects ) && count( $projects ) > 0 )
		{
			foreach( $projects as $project )
			{
				if ( $app == 'admin' )
				{
					$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}&amp;module=projects&amp;section=projects&amp;parent={$project['project_id']}", $project['title'] );
				}
				else
				{
					$this->registry->output->addNavigation( $project['title'], 'app=tracker&showproject='.$project['project_id'], $project['title_seo'], 'showproject' );
				}
			}
		}
	}

	/**
	 * Check to see if the current member has proper permissions to a project
	 *
	 * @param string $type Type of permission to check
	 * @param int $projectID Project ID to check
	 * @return boolean of the result
	 * @since 1.0.0
	 */
	public function checkPermission( $type, $projectID, $otherMasks=array() )
	{
		$projectID = intval($projectID);
		$out = FALSE;

		if ( $this->cache->isProject( $projectID ) )
		{
			$out = $this->tracker->perms()->check( $type, $this->cache->getPerms( $projectID ), $otherMasks );
		}

		return $out;
	}

	/**
	 * Creates permission shortcuts in the member object for the project ID passed
	 *
	 * @param int $projectID The project ID that custom fields are needed for
	 * @return void result is merged in to $this->member
	 * @access public
	 * @since 1.1.0
	 */
	public function createPermShortcuts( $projectID )
	{
		// If there isn't a tracker array in member yet, create one
		if ( ! is_array( $this->member->tracker ) )
		{
			$this->member->tracker = array();
		}

		// Only build the permissions if the project exists
		if ( $this->cache->isProject( $projectID ) )
		{
			$tracker = array();

			// Check permissions
			$tracker['show_perms']     = $this->tracker->perms()->check( 'show',     $this->cache->getPerms( $projectID ) );
			$tracker['read_perms']     = $this->tracker->perms()->check( 'read',     $this->cache->getPerms( $projectID ) );
			$tracker['start_perms']    = $this->tracker->perms()->check( 'start',    $this->cache->getPerms( $projectID ) );
			$tracker['reply_perms']    = $this->tracker->perms()->check( 'reply',    $this->cache->getPerms( $projectID ) );
			$tracker['upload_perms']   = $this->tracker->perms()->check( 'upload',   $this->cache->getPerms( $projectID ) );
			$tracker['download_perms'] = $this->tracker->perms()->check( 'download', $this->cache->getPerms( $projectID ) );

			// Merge temp array into member
			$this->member->tracker = array_merge( $this->member->tracker, $tracker );
		}
	}

	/**
	 * Returns a the array of projects from the cache
	 *
	 * @return array the projects
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		return $this->cache->getCache();
	}

	/**
	 * Gets cumulative posts/topics - sets new post marker and last topic id
	 *
	 * @param	integer	$root_id
	 * @param	array 	$project_data
	 * @param	bool	$done_pass
	 * @return	array
	 */
	public function getCalcChildren( $root_id, $project_data=array(), $done_pass=0 )
	{
		//-----------------------------------------
		// Markers
		//-----------------------------------------

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $project_data['project_id'] ), 'tracker' );

		if( !isset($project_data['_has_unread']) )
		{
			$project_data['_has_unread'] = ( $project_data['last_post'] && $project_data['last_post']['datetime'] > $rtime ) ? 1 : 0;
		}

		$children = $this->cache->getChildren( $root_id );

		if ( isset( $children ) )
		{
			foreach( $children as $data )
			{
				if ( $data['last_post']['datetime'] > $project_data['last_post']['datetime'] AND ! $data['hide_last_info'] )
				{
					$project_data['last_post']			= $data['last_post'];
					$project_data['pid']				= $data['project_id'];
					$project_data['last_id']			= $data['last_id'];
					$project_data['last_title']			= $data['last_title'];
					$project_data['seo_last_title']		= $data['seo_last_title'];
					$project_data['last_poster_id']		= $data['last_poster_id'];
					$project_data['last_poster_name']	= $data['last_poster_name'];
					$project_data['seo_last_name']		= $data['seo_last_name'];
					$project_data['_has_unread']        = $project_data['_has_unread'];
				}

				//-----------------------------------------
				// Markers.  We never set false from inside loop.
				//-----------------------------------------

				$rtime	             = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $data['project_id'] ), 'tracker' );
				$data['_has_unread'] = 0;

				if( $data['last_post'] && $data['last_post']['datetime'] > $rtime )
				{
					$project_data['_has_unread']		= 1;
					$data['_has_unread']            = 1;
				}

				if ( ! $done_pass )
				{
					$project_data['subprojects'][ $data['id'] ] = array($data['id'], $data['name'], $data['name_seo'], intval( $data['_has_unread']  ), 0 );
				}

				$project_data = $this->getCalcChildren( $data['project_id'], $project_data, 1 );
			}
		}

		return $project_data;
	}

	/**
	 * Returns an array of projects from the cache
	 *
	 * @param int $projectID the project ID of the parent
	 * @return array the projects
	 * @access public
	 * @since 2.0.0
	 */
	public function getChildren( $projectID )
	{
		return $this->cache->getChildren( $projectID );
	}

	/**
	 * Returns whether or not this project has more unread issues
	 *
	 * @param int $projectID the project ID of current project
	 * @return bool
	 * @access public
	 * @since 2.1.0
	 */
	public function getHasUnread( $projectId )
	{
		$latestStamp	= intval( $this->registry->classItemMarking->fetchTimeLastMarked( array('forumID' => $projectId ), 'tracker' ) );
		$projectData	= $this->getProject( $projectId );
		$lastPost		= $projectData['last_post']['datetime'];

		return ( $latestStamp < $lastPost ) ? true : false;
	}

	/**
	 * Recurrsive issue counter
	 *
	 * @param int $projectID the project to count issues in
	 * @param bool $permsCheck Optional: disable 'show' perms check by settings FALSE
	 * @return int issue count
	 * @access public
	 * @since 1.2.0
	 */
	public function getIssueCount( $projectID, $permsCheck = TRUE )
	{
		$out = 0;

		if ( $this->cache->isProject( $projectID ) && ( $permsCheck || $this->checkPermission( 'show', $projectID ) ) )
		{
			$project   = $this->cache->getProject( $projectID );

			if ( is_array( $this->cache->getChildren( $projectID ) ) AND count( $this->cache->getChildren( $projectID ) ) > 0 )
			{
				foreach( $temp = $this->cache->getChildren( $projectID ) as $childID => $childData )
				{
					$out += $this->getIssueCount( $childID, $permsCheck );
				}
			}

			$out += $project['num_issues'];
		}

		return $out;
	}

	/**
	 * Recurrsively find the latest post in a project
	 *
	 * @param int $projectID the project to find the last past in
	 * @param bool $permsCheck Optional: disable 'show' perms check by settings FALSE
	 * @return array last post data
	 * @access public
	 * @since 1.2.0
	 */
	public function getLastPost( $projectID, $permsCheck = TRUE )
	{
		$out = array();

		if ( $this->cache->isProject( $projectID ) && ( $permsCheck || $this->checkPermission( 'show', $projectID ) ) )
		{
			$childPost = array();
			$project   = $this->cache->getProject( $projectID );

			if ( is_array( $this->cache->getChildren( $projectID ) ) AND count( $this->cache->getChildren( $projectID ) ) > 0 )
			{
				foreach( $temp = $this->cache->getChildren( $projectID ) as $childID => $childData )
				{
					$childPost[] = $this->getLastPost( $childID, $permsCheck );
				}
			}

			if ( is_array( $childPost ) && count( $childPost ) > 0 )
			{
				$dateTime = $project['last_post']['datetime'];

				foreach( $childPost as $k => $v )
				{
					if ( $v['datetime'] > $dateTime )
					{
						$out = $v;
						$dateTime = $v['datetime'];
					}
				}

				// Still no data?
				if ( ! $out['datetime'] )
				{
					$out = $project['last_post'];
				}
			}
			else
			{
				$out = $project['last_post'];
			}
		}

		return $out;
	}

	/**
	 * Shortcut for cache::getPerms()
	 *
	 * @param string $id project ID
	 * @return array of the result
	 * @since 1.0.0
	 */
	public function getPerms( $projectID )
	{
		$out = FALSE;

		if ( $this->cache->isProject( $projectID ) )
		{
			$out = $this->cache->getPerms( $projectID );
		}

		return $out;
	}

	/**
	 * Recurrsive post counter
	 *
	 * @param int $projectID the project to count issues in
	 * @param bool $permsCheck Optional: disable 'show' perms check by settings FALSE
	 * @return int issue count
	 * @access public
	 * @since 1.2.0
	 */
	public function getPostCount( $projectID, $permsCheck = TRUE )
	{
		$out = 0;

		if ( $this->cache->isProject( $projectID ) && ( $permsCheck || $this->checkPermission( 'show', $projectID ) ) )
		{
			$project   = $this->cache->getProject( $projectID );

			if ( is_array( $this->cache->getChildren( $projectID ) ) AND count( $this->cache->getChildren( $projectID ) ) > 0 )
			{
				foreach( $temp = $this->cache->getChildren( $projectID ) as $childID => $childData )
				{
					$out += $this->getPostCount( $childID, $permsCheck );
				}
			}

			$out += $project['num_posts'];
		}

		return $out;
	}

	/**
	 * Returns a project from the cache
	 *
	 * @param int $projectID the project ID requested
	 * @return array the project
	 * @access public
	 * @since 2.0.0
	 */
	public function getProject( $projectID )
	{
		return $this->cache->getProject( $projectID );
	}

	/**
	 * Returns an array of searchable projects
	 *
	 * @param int $memberID Person who is attempting to search
	 * @return array the projects
	 * @access public
	 * @since 2.0.0
	 */
	public function getSearchableProjects( $memberID )
	{
		return $this->cache->getSearchableProjects( $memberID );
	}

	/**
	 * Checks that a project exists
	 *
	 * @param int $projectID the project ID requested
	 * @return bool existence
	 * @access public
	 * @since 2.0.0
	 */
	public function isProject( $projectID )
	{
		return $this->cache->isProject( $projectID );
	}

	/**
	 * Make dropdown of projects for ACP
	 *
	 * @param boolean $showRoot Optional: defaults TRUE; will show 'Root' as a top level option with 0 key
	 * @return array of projects for dropdown
	 * @access public
	 * @since 1.2.0
	 */
	public function makeAdminDropdown( $showRoot=TRUE )
	{
		$depthGuide = '';
		$projects   = array();

		if ( $showRoot )
		{
			$projects[] = array( '0', 'Root' );
			$depthGuide = $this->depthGuide;
		}

		$projects = $this->makeAdminDropdownInternal( $projects, 'root', $depthGuide, $this->depthGuide);

		return $projects;
	}

	/**
	 * Recurrsion method for building project admin dropdowns with depth (if enabled)
	 *
	 * @param array $projects the dropdown array
	 * @param int $rootID the project ID to build children from
	 * @param boolean $depth Optional: defaults ''; the current depth
	 * @param boolean $depthGuide Optional: defaults ''; the depth level guide
	 * @return array of projects for dropdown
	 * @access private
	 * @since 1.2.0
	 */
	private function makeAdminDropdownInternal( $projects, $rootID, $depth='', $depthGuide='' )
	{
		if ( is_array( $projects ) && is_array( $this->cache->getChildren( $rootID ) ) && count( $this->cache->getChildren( $rootID ) ) > 0 )
		{
			foreach( $temp = $this->cache->getChildren( $rootID ) as $id => $data )
			{
				$level      = strlen( $depth ) > 0 ? "&nbsp;&nbsp;&#0124;{$depth} " : '';
				$projects[] = array( $id, $level . $data['title'] );

				$projects = $this->makeAdminDropdownInternal( $projects, $id, $depth . $depthGuide, $depthGuide );
			}
		}

		return $projects;
	}

	/**
	 * Make dropdown of projects
	 *
	 * @param string $selectName Optional: defaults 'project_id'; the name to be given to the select (DROPDOWN ONLY)
	 * @param int $selected Optional: defaults 0; the ID of the status that should be selected
	 * @param boolean $allowAll Optional: defaults FALSE; shows the 'ALL' option in the DD
	 * @param string $returnType Optional: default 'drop; drop|array|options
	 * @param boolean $depth Optional: defaults TRUE; enables the depth indentations for sub-projects
	 * @param boolean $disable Optional: defaults TRUE; disables projects that are cat only
	 * @return string|array output based on $returnType parameter
	 * @access public
	 * @since 1.0.0
	 */
	public function makeDropdown( $selectName='project_id', $selected=0, $allowAll=FALSE, $returnType='drop', $depth=TRUE, $disable=TRUE )
	{
		$depthGuide = '';
		$projects   = array();
		$out        = NULL;

		if ( $depth )
		{
			$depthGuide = $this->depthGuide;
		}

		if ( $allowAll )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : '';
			$projects['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_global_project_dd_all']}</option>";
		}

		$projects = $this->makeDropdownInternal( $projects, 'root', '', $depthGuide, $selected, $disable );

		switch( $returnType )
		{
			case 'drop':    $out = $this->registry->getClass('output')->getTemplate('tracker_global')->dropdown_wrapper( $selectName, implode("\n", $projects) );
				break;
			case 'array':   $out = $projects;
				break;
			case 'options': $out = implode("\n", $projects);
				break;
		}

		return $out;
	}

	/**
	 * Recurrsion method for building project dropdowns with depth (if enabled)
	 *
	 * @param array $projects the dropdown array
	 * @param int $rootID the project ID to build children from
	 * @param boolean $depth Optional: defaults ''; the current depth
	 * @param boolean $depthGuide Optional: defaults ''; the depth level guide
	 * @param int $selected Optional: defaults 0; the ID of the status that should be selected
	 * @param boolean $disable Optional: defaults TRUE; disables projects that are cat only
	 * @return array of projects for dropdown
	 * @access private
	 * @since 1.2.0
	 */
	private function makeDropdownInternal( $projects, $rootID, $depth='', $depthGuide='', $selected=0, $disable=TRUE )
	{
		if ( is_array( $projects ) && is_array( $this->cache->getChildren( $rootID ) ) && count( $this->cache->getChildren( $rootID ) ) > 0 )
		{
			foreach( $temp = $this->cache->getChildren( $rootID ) as $id => $data )
			{
				if ( $this->checkPermission( 'show', $id ) )
				{
					$level = strlen( $depth ) > 0 ? "&nbsp;&nbsp;&#0124;{$depth} " : '';

					$sel = ( $selected == $data['project_id'] )? "selected='selected'" : "" ;
					$sel = ( $disable  && $data['cat_only'] )  ? "disabled"            : $sel;
					$projects[$id] = "<option value='{$id}' {$sel}>{$level}{$data['title']}</option>";

					$projects = $this->makeDropdownInternal( $projects, $id, $depth . $depthGuide, $depthGuide, $selected, $disable );
				}
			}
		}

		return $projects;
	}

	// Name is deceptive... this also fetches and builds the actual issue list.
	public function makeFilters( $project )
	{
		// Unset pagination
		if ( $this->request['changefilter'] )
		{
			$this->request['st'] = 0;
		}

		//-----------------------------------------
		// Read issues
		//-----------------------------------------

		$First = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;

		$cookie_temp = IPSCookie::get( "tracker_" . $this->project['project_id'] );

		if ( ! is_array( $cookie_temp ) || ! ( count( $cookie_temp ) > 0 ) )
		{
			$cookie_temp = array();
		}

		$prune_value = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request['prune_day']) ? $this->request['prune_day'] : NULL,
				2 => isset($cookie_temp['prune_day'])   ? $cookie_temp['prune_day']   : NULL,
				4 => '100'
			)
		);

		$sort_key    = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request['sort_key']) ? $this->request['sort_key'] : NULL,
				2 => isset($cookie_temp['sort_key'])   ? $cookie_temp['sort_key']   : NULL,
				4 => 'last_post'
			)
		);

		$sort_by     = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request['sort_by']) ? $this->request['sort_by'] : NULL,
				2 => isset($cookie_temp['sort_by'])   ? $cookie_temp['sort_by']   : NULL,
				4 => 'Z-A'
			)
		);

		$issuefilter  = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request['issuefilter']) ? $this->request['issuefilter'] : NULL,
				2 => isset($cookie_temp['issuefilter'])   ? $cookie_temp['issuefilter']   : NULL,
				4 => 'all'
			)
		);

		/* FIELDS-NEW */
		$this->tracker->fields()->projectFilterSetup( $cookie_temp );

		if( isset( $this->request['remember'] ) && $this->request['remember'] )
		{
			if( isset( $this->request['prune_day'] ) && $this->request['prune_day'] )
			{
				$cookie_temp['prune_day'] = $this->request['prune_day'];
			}

			if( isset( $this->request['sort_key'] ) && $this->request['sort_key'] )
			{
				$cookie_temp['sort_key'] = $this->request['sort_key'];
			}

			if( isset( $this->request['sort_by'] ) && $this->request['sort_by'] )
			{
				$cookie_temp['sort_by'] = $this->request['sort_by'];
			}

			if( isset( $this->request['issuefilter'] ) && $this->request['issuefilter'] )
			{
				$cookie_temp['issuefilter'] = $this->request['issuefilter'];
			}

			/* FIELDS-NEW */
			$cookie_temp = $this->tracker->fields()->getProjectFilterCookie( $cookie_temp );

			IPSCookie::set( "tracker_" . $project['project_id'], serialize( $cookie_temp ) );
			unset( $cookie_temp );
		}

		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------

		$Prune = $prune_value != 100 ? (time() - ($prune_value * 60 * 60 * 24)) : 0;

		$sort_keys   =  array(
			'last_post'        => 'sort_by_date',
			'last_poster_name' => 'sort_by_last_poster',
			'title'            => 'sort_by_issue',
			'starter_name'     => 'sort_by_poster',
			'start_date'       => 'sort_by_start',
			'hasattach'        => 'sort_by_attach',
			'posts'            => 'sort_by_replies',
		);

		if ( $this->tracker->fields()->checkPermission( $project, 'severity', 'show' ) )
		{
			$sort_keys['module_severity_id'] = 'sort_by_severity';
		}

		$prune_by_day = array(
			'1'    => 'show_today',
			'5'    => 'show_5_days',
			'7'    => 'show_7_days',
			'10'   => 'show_10_days',
			'15'   => 'show_15_days',
			'20'   => 'show_20_days',
			'25'   => 'show_25_days',
			'30'   => 'show_30_days',
			'60'   => 'show_60_days',
			'90'   => 'show_90_days',
			'100'  => 'show_all',
		);

		$sort_by_keys = array(
			'Z-A'  => 'descending_order',
			'A-Z'  => 'ascending_order',
		);

		$filter_keys  = array(
			'all'    => 'issuefilter_all',
			'open'   => 'issuefilter_open',
			'hot'    => 'issuefilter_hot',
			'locked' => 'issuefilter_locked',
		);

		if ( $this->memberData['member_id'] )
		{
			$filter_keys['istarted'] = 'issuefilter_istarted';
			$filter_keys['ireplied'] = 'issuefilter_ireplied';
		}

		/* FIELDS-NEW */
		if ( ( ! isset( $filter_keys[ $issuefilter ] )) || ( ! isset( $sort_keys[ $sort_key ] ) ) || ( ! isset( $prune_by_day[ $prune_value ] ) ) || ( ! isset( $sort_by_keys[ $sort_by ] ) ) || $this->tracker->fields()->validateFilterInput() )
		{
			$this->registry->output->showError( $this->lang->words[ 'bad_filter' ], '20T112' );
		}

		/* FIELDS-NEW */
		$this->tracker->fields()->addNavigation( $this );

		$r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';

		//-----------------------------------------
		// Additional queries?
		//-----------------------------------------

		$add_query_array = array();
		$add_query       = "";

		switch( $issuefilter )
		{
			case 'all':
				break;
			case 'open':
				$add_query_array[] = "t.state='open'";
				break;
			case 'hot':
				$add_query_array[] = "t.state='open' AND t.posts + 1 >= ".intval($this->settings['tracker_hot_issue']);
				break;
			case 'locked':
				$add_query_array[] = "t.state='closed'";
				break;
			default:
				break;
		}

		if ( $issuefilter == 'istarted' )
		{
			$add_query_array[] = "t.starter_id='".$this->memberData['member_id']."'";
		}

		/* FIELDS-NEW */
		$add_query_array = $this->tracker->fields()->getProjectFilterQueryAdds( $add_query_array );

		if ( count( $add_query_array ) )
		{
			$add_query = ' AND '. implode( ' AND ', $add_query_array );
		}

		//-----------------------------------------
		// Query the database to see how many issues there are in the project
		//-----------------------------------------

		if ( $issuefilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking issues we've replied to?
			//-----------------------------------------

			if ( $Prune )
			{
				$prune_filter = " AND (t.last_post > {$Prune})";
			}
			else
			{
				$prune_filter = "";
			}

			/* Get data from DB */
			$this->DB->build(
				array(
					'select'   => 'COUNT(p.issue_id) as max',
					'from'     => array( 'tracker_issues' => 't' ),
					'add_join' => array(
						array(
							'from'   => array( 'tracker_posts' => 'p' ),
							'where'  => 'p.issue_id=t.issue_id',
							'type'   => 'left'
						)
					),
					'where'    => "t.project_id={$project['project_id']} AND p.author_id={$this->memberData['member_id']} AND p.new_issue=0{$prune_filter}" . $add_query
				)
			);
			$q = $this->DB->execute();

			$total_possible = $this->DB->fetch($q);
		}
		else if ( $add_query || $Prune )
		{
			$this->DB->build(
				array(
					'select' => 'COUNT(*) as max',
					'from'   => 'tracker_issues t',
					'where'  => "t.project_id=".$project['project_id']." AND (t.last_post > {$Prune})" . $add_query
				)
			);
			$this->DB->execute();

			$total_possible = $this->DB->fetch();
		}
		else
		{
			$total_possible['max'] = $project['num_issues'];
			$Prune = 0;
		}

		$single_page_forum = isset( $this->lang->words['single_page_forum'] ) ? $this->lang->words['single_page_forum'] : '';

		//-----------------------------------------
		// Generate the project page span links
		//-----------------------------------------

		$extra = $this->tracker->fields()->getProjectFilterURLBits();

		$project['SHOW_PAGES'] = $this->registry->output->generatePagination(
			array(
				'totalItems'        => $total_possible['max'],
				'itemsPerPage'      => ( $this->settings['tracker_bugs_perpage'] ? $this->settings['tracker_bugs_perpage'] : 25 ),
				'currentStartValue' => intval( $this->request['st'] ),
				'baseUrl'           => "app=tracker&amp;showproject=".$project['project_id']."&amp;prune_day={$prune_value}&amp;sort_by={$sort_by}&amp;sort_key={$sort_key}&amp;issuefilter={$issuefilter}{$extra}",
				/*'seoTitle'			=> $project['title_seo'],
				'seoTemplate'		=> 'showproject'*/
			)
		);

		$total_issues_printed = 0;

		$issue_array = array();
		$issue_ids   = array();
		$issue_sort  = "";

		//-----------------------------------------
		// Cut off?
		//-----------------------------------------

		$parse_dots = 1;

		if ( $Prune )
		{
			$query = "t.project_id=".$project['project_id']." AND (t.last_post > {$Prune})";
		}
		else
		{
			$query = "t.project_id=".$project['project_id'];
		}

		$loopy 	= "";
		$tags	= array(
			array(
				'select'	=> 'm.members_display_name as i_starter_name, m.members_seo_name as i_starter_name_seo',
				'from'		=> array( 'members' => 'm' ),
				'where'		=> 'm.member_id=t.starter_id',
				'type'		=> 'left'
			),

			array(
				'select'	=> 'mm.members_display_name as i_last_poster_name, mm.members_seo_name as i_last_poster_name_seo',
				'from'		=> array( 'members' => 'mm' ),
				'where'		=> 'mm.member_id=t.last_poster_id',
				'type'		=> 'left'
			)
		);

		$tags[] = $this->registry->trackerTags->getCacheJoin( array( 'meta_id_field' => 't.issue_id' ) );

		if ( $issuefilter == 'ireplied' )
		{
			$parse_dots = 0;

			$tags[] = 	array(
				'from'   => array( 'tracker_posts' => 'p' ),
				'where'  => 'p.issue_id=t.issue_id AND p.author_id='.$this->memberData['member_id'],
				'type'   => 'left'
			);

			/* Get data from DB */
			$this->DB->build(
				array(
					'select'   => 'DISTINCT(p.author_id), t.*',
					'from'     => array( 'tracker_issues' => 't' ),
					'where'    => "{$query} AND p.new_issue=0" . $add_query,
					'order'	=> $issue_sort.' t.'.$sort_key.' '.$r_sort_by,
					'add_join' => $tags,
					'limit'  => array( $First, intval( $this->settings['tracker_bugs_perpage'] ) )
				)
			);
			$loopy = $this->DB->execute();
		}
		else
		{
			$this->DB->build(
				array(
					'select'   => 't.*',
					'from'     => array( 'tracker_issues' => 't' ),
					'where'    => $query . $add_query,
					'order'    => $issue_sort.' t.'.$sort_key .' '. $r_sort_by,
					'limit'    => array( $First, intval( $this->settings['tracker_bugs_perpage'] ) ),
					'add_join' => $tags
				)
			);
			$loopy = $this->DB->execute();
		}

		while ( $t = $this->DB->fetch( $loopy ) )
		{
			$issue_array[ $t['issue_id'] ] = $t;
			$issue_ids[   $t['issue_id'] ] = $t['issue_id'];
		}

		ksort( $issue_ids );

		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------

		$issues        = $this->tracker->issues()->checkUserPosted( $issue_ids, 1, $issue_array );
		$printedIssues = array();

		//-----------------------------------------
		// Show meh the issues!
		//-----------------------------------------

		foreach( $issues as $issue )
		{
			$printedIssues[ $issue['issue_id'] ] 			= $this->tracker->issues()->parseRow( $issue, $project );

			// For Brandon, *sigh*
			$printedIssues[ $issue['issue_id'] ]['extra']	= $extra;

			$total_issues_printed++;
		}

		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$issuefilter]))
		//-----------------------------------------

		$sort_by_html   = "";
		$sort_key_html  = "";
		$prune_day_html = "";
		$filter_html    = "";

		foreach ($sort_by_keys as $k => $v)
		{
			$sort_by_html   .= $k == $sort_by     ? "<option value='$k' selected='selected'>" . $this->lang->words[ 'bt_project_' . $sort_by_keys[ $k ] ] . "</option>\n"
			                                      : "<option value='$k'>"                     . $this->lang->words[ 'bt_project_' . $sort_by_keys[ $k ] ] . "</option>\n";
		}

		foreach ($sort_keys as  $k => $v)
		{
			$sort_key_html  .= $k == $sort_key    ? "<option value='$k' selected='selected'>" . $this->lang->words[ 'bt_project_' . $sort_keys[ $k ] ]    . "</option>\n"
			                                      : "<option value='$k'>"                     . $this->lang->words[ 'bt_project_' . $sort_keys[ $k ] ]    . "</option>\n";
		}

		foreach ($prune_by_day as  $k => $v)
		{
			$prune_day_html .= $k == $prune_value ? "<option value='$k' selected='selected'>" . $this->lang->words[ 'bt_project_' . $prune_by_day[ $k ] ] . "</option>\n"
			                                      : "<option value='$k'>"                     . $this->lang->words[ 'bt_project_' . $prune_by_day[ $k ] ] . "</option>\n";
		}

		foreach ($filter_keys as  $k => $v)
		{
			$filter_html    .= $k == $issuefilter ? "<option value='$k' selected='selected'>" . $this->lang->words[ 'bt_' . $filter_keys[ $k ] ]  . "</option>\n"
			                                      : "<option value='$k'>"                     . $this->lang->words[ 'bt_' . $filter_keys[ $k ] ]  . "</option>\n";
		}

		$this->tracker->show['sort_by']      = $sort_key_html;
		$this->tracker->show['sort_order']   = $sort_by_html;
		$this->tracker->show['sort_prune']   = $prune_day_html;
		$this->tracker->show['issue_filter'] = $filter_html;

		/* FIELDS-NEW */
		$this->tracker->show['custom'] = $this->tracker->fields()->getProjectFilterDropdowns();

		// Return data
		return array( 'project' => $project, 'issues' => $printedIssues );
	}


	/**
	 * Returns the cache of fields for a particular project
	 *
	 * @return array
	 * @access public
	 * @since 2.0.0
	 */
	public function projectFields($project)
	{
		$records 	= array();
		$recordIds	= array();
		$position	= 0;

		if ( ! isset( $project['project_id'] ) )
		{
			return array();
		}

		$this->DB->build(
			array(
				'select'	=> 'pf.*',
				'from'		=> array( 'tracker_project_field' => 'pf'),
				'where'		=> 'pf.project_id=' . $project['project_id'],
				'add_join'	=> array(
					array(
						'select'	=> 'f.module_id',
						'from'		=> array('tracker_field' => 'f'),
						'where'		=> 'f.field_id=pf.field_id',
						'type'		=> 'left'
					),
					array(
						'select'	=> 'm.enabled as module_enabled',
						'from'		=> array('tracker_module' => 'm'),
						'where'		=> 'f.module_id=m.module_id',
						'type'		=> 'left'
					)
				),
				'order'		=> 'pf.position ASC'
			)
		);
		$this->DB->execute();

		if ( $this->DB->getTotalRows() )
		{
			while ( $field = $this->DB->fetch() )
			{
				$position++;
				$recordIds[] = $field['field_id'];
				$records[$field['field_id']] = $field;

				// Set the custom one
				if ( $field['field_keyword'] == 'custom' )
				{
					$this->customField = $field;
				}
			}
		}

		$newCache	= array();
		$cache 		= $this->tracker->fields()->getCache();

		foreach( $cache as $k => $field )
		{
			if ( $field['field_keyword'] != 'custom' )
			{
				// Load in the custom field
				if ( $field['custom'] )
				{
					$records[ $field['field_id'] ] = $this->customField;
				}

				if ( !in_array( $field['field_id'], $recordIds ) )
				{
					$reload = 1;
					$position++;

					$this->DB->insert( 'tracker_project_field',
						array(
							'field_id'		=> $field['field_id'],
							'project_id'	=> $project['project_id'],
							'position'		=> $position,
							'custom'		=> intval($field['custom'])
						)
					);
				}
				else
				{
					// Load in the custom field
					if ( $field['custom'] )
					{
						$records[ $field['field_id'] ] = $this->customField;
					}

					if ( !$records[$field['field_id']]['module_enabled'] )
					{
						continue;
					}

					$newCache[$records[$field['field_id']]['position']]				= $field;
					$newCache[$records[$field['field_id']]['position']]['record']	= $records[$field['field_id']];
				}
			}
		}

		if ( $reload )
		{
			return $this->projectFields($project);
		}

		ksort( $newCache );
		return $newCache;
	}

	/**
	 * Rebuild the cache fresh using the values from the database
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$this->cache->rebuild();
	}

	/**
	 * Update an individual projects cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function update( $projectID )
	{
		$this->cache->update($projectID);
	}
}

/**
 * Type: Cache
 * Projects cache controller
 *
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_cache_projects extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_projects';
	/**
	 * The building block for indented dropdown menus
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	public $depthGuide = '--';
	/**
	 * The processed projects cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $projectCache;
	/**
	 * The processed projects from the database with each parent as a key
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $projectParentArray;

	/**
	 * Initial function.  Called by execute function in ipsCommand
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache->getCache( $this->cacheName );
		$this->projectCache = $this->caches[ $this->cacheName ];

		$this->processForApplication();
	}

	/**
	 * Gets an ordered array of the projects to create a breadcrumb for a project
	 *
	 * @param int $projectID the project ID to check on
	 * @return array the ordered parents and project
	 * @access public
	 * @since 2.0.0
	 */
	public function getBreadcrumbs( $projectID )
	{
		$active = array();
		$crumbs = array();
		$out    = array();

		if ( $this->isProject( $projectID ) )
		{
			$active   = $this->projectCache[ $projectID ];
			$crumbs[] = $active;

			while ( $active['parent_id'] != 0 )
			{
				$active   = $this->projectCache[ $active['parent_id'] ];
				$crumbs[]	= $active;
			}

			$crumbs = array_reverse($crumbs);

			foreach( $crumbs as $k => $v )
			{
				$out[] = $crumbs[ $k ];
			}
		}

		return $out;
	}

	/**
	 * Returns the processed cache
	 *
	 * @return array the cache with post-DB processing
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		$out = array();

		if ( is_array( $this->projectCache ) && count( $this->projectCache ) > 0 )
		{
			$out = $this->projectCache;
		}

		return $out;
	}

	/**
	 * Gets the array of projects that are children to the project ID provided
	 *
	 * @param int $projectID the project ID
	 * @return array the children
	 * @access public
	 * @since 2.0.0
	 */
	public function getChildren( $projectID )
	{
		$out = array();

		if ( ( $projectID == 'root' || $this->isProject( $projectID ) ) && is_array( $this->projectParentArray ) && isset( $this->projectParentArray[ $projectID ] ) && is_array( $this->projectParentArray[ $projectID ] ) && count( $this->projectParentArray[ $projectID ] ) > 0 )
		{
			$out = $this->projectParentArray[ $projectID ];
		}

		return $out;
	}

	/**
	 * Gets the permissions row for the specified project
	 *
	 * @param int $projectID the project ID to check on
	 * @return array the permissions row
	 * @access public
	 * @since 2.0.0
	 */
	public function getPerms( $projectID )
	{
		$out = array();

		if ( $this->isProject( $projectID ) && isset( $this->projectCache[ $projectID ]['perms'] ) && is_array( $this->projectCache[ $projectID ]['perms'] ) )
		{
			$out = $this->projectCache[ $projectID ]['perms'];
		}

		return $out;
	}

	/**
	 * Get the requested project
	 *
	 * @param int $projectID the project ID to check on
	 * @return array if project exists
	 * @access public
	 * @since 2.0.0
	 */
	public function getProject( $projectID )
	{
		$out = NULL;

		if ( is_array( $this->projectCache ) && isset( $this->projectCache[ $projectID ] ) && is_array( $this->projectCache[ $projectID ] ) )
		{
			$out = $this->projectCache[ $projectID ];
		}

		return $out;
	}

	/**
	 * Returns a project from the cache
	 *
	 * @param int $projectID the project ID requested
	 * @return array the project
	 * @access public
	 * @since 2.0.0
	 */
	public function getSearchableProjects( $memberID )
	{
		$allowed = array();

		if ( is_array( $this->projectCache ) && count( $this->projectCache ) )
		{
			foreach( $this->projectCache as $k => $v )
			{
				if ( $this->registry->tracker->perms()->check( 'show', $this->getPerms( $v['project_id'] ) ) )
				{
					$allowed[] = $v['project_id'];
				}
			}
		}

		return $allowed;
	}

	/**
	 * Runs a couple checks to make sure we have a valid project ID
	 *
	 * @param int $projectID the project ID to check on
	 * @return boolean TRUE if project exists
	 * @access public
	 * @since 2.0.0
	 */
	public function isProject( $projectID )
	{
		$out = FALSE;

		// Make sure we're getting a number through here
		if ( intval( $projectID ) != $projectID )
		{
			return $out;
		}

		if ( is_array( $this->projectCache ) && isset( $this->projectCache[ $projectID ] ) && is_array( $this->projectCache[ $projectID ] ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	/**
	 * Processes the cache to create the structured parent array
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	protected function processForApplication()
	{
		$this->projectParentArray = array();

		if ( is_array( $this->projectCache ) && count( $this->projectCache ) > 0 )
		{
			foreach( $this->projectCache as $id => $data )
			{
				if ( $data['parent_id'] < 1 )
				{
					$data['parent_id'] = 'root';
				}

				$this->projectParentArray[ $data['parent_id'] ][ $id ] = $data;
			}
		}
	}

	/**
	 * Rebuild the cache fresh using the values from the database
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$projectCache = array();

		$this->checkForRegistry();

		// If the array is empty then get the projects from the database
		$this->DB->build(
			array(
				'select'    => '*',
				'from'      => 'tracker_projects',
				'order'     => 'parent_id, position'
			)
		);
		$this->DB->execute();

		// Load the local cache from the database
		if ( $this->DB->getTotalRows() > 0 )
		{
			while ( $r = $this->DB->fetch() )
			{
				$projectCache[ $r['project_id'] ]                  = $r;
				// Setup arrays for other information
				$projectCache[ $r['project_id'] ]['num_issues']    = 0;
				$projectCache[ $r['project_id'] ]['num_posts']     = 0;
				$projectCache[ $r['project_id'] ]['depthed_title'] = $r['title'];
				$projectCache[ $r['project_id'] ]['title_seo']	   = IPSText::makeSeoTitle( $r['title'] );
				$projectCache[ $r['project_id'] ]['fields']        = array();
				$projectCache[ $r['project_id'] ]['last_post']     = array();
				$projectCache[ $r['project_id'] ]['perms']         = array();
			}
		}

		// No need to process permissions and fields if there aren't any projects
		if ( count( $projectCache ) > 0 )
		{
			// Create depthed titles for some uses
			foreach( $projectCache as $id => $r )
			{
				// Versions
				if ( $this->registry->tracker->modules()->moduleIsInstalled( 'versions', TRUE ) )
				{
					$this->registry->tracker->fields()->initialize( $id, FALSE, TRUE );

					// Grab version permissions
					$perms = $this->registry->tracker->fields()->field('version', false, true)->getPerms();

					$projectCache[ $id ]['alter_versions'] = $perms['perm_5'];
				}

				// Statuses
				if ( $this->registry->tracker->modules()->moduleIsInstalled( 'status', FALSE ) )
				{
					$privacy  = '';
					if ( $this->registry->tracker->modules()->moduleIsInstalled( 'privacy', TRUE ) )
					{
						$privacy 	= " AND module_privacy=0";
					}
					$serial = array( 'status' => array() );

					$this->DB->build(
						array(
							'select' => 'module_status_id, COUNT(*) AS count',
							'from'   => 'tracker_issues',
							'where'  => 'project_id = ' . $id.$privacy,
							'group'  => 'module_status_id'
						)
					);
					$status = $this->DB->execute();

					$count = 0;

					if ( $this->DB->getTotalRows( $status ) )
					{
						while ( $row = $this->DB->fetch( $status ) )
						{
							$serial['status'][ $row['module_status_id'] ] = intval( $row['count'] );
							$count += $row['count'];
						}
					}

					// Add it to project
					$projectCache[ $id ]['serial_data'] = serialize( $serial );

					// Reset
					$count = 0;
				}

				// Start with the project and work backwards
				$current = $r;

				// Looping through the parents to build the depth
				while( $current['parent_id'] != 0 )
				{
					// Find the next parent in the chain
					$next = $projectCache[ $current['parent_id'] ];

					// Create the left edge (last)
					if ( $next['parent_id'] == 0 )
					{
						$projectCache[ $id ]['depthed_title'] = "&nbsp;&nbsp;&#0124;{$this->depthGuide}" . $projectCache[ $id ]['depthed_title'];
					}
					// This is the first, so a space is needed
					else if ( $current['project_id'] == $id )
					{
						$projectCache[ $id ]['depthed_title'] = "{$this->depthGuide} " . $projectCache[ $id ]['depthed_title'];
					}
					// Regular depth add
					else
					{
						$projectCache[ $id ]['depthed_title'] = $this->depthGuide . $projectCache[ $id ]['depthed_title'];
					}

					// Swap in the next parent as the new current
					$current = $next;
				}
			}

			// Grabs issue count
			$this->DB->build(
				array(
					'select'   => 'project_id, count(issue_id) AS num_issues, sum(posts) AS num_posts',
					'from'     => 'tracker_issues',
					'group'    => "project_id"
				)
			);
			$this->DB->execute();

			// Load in the issue counts
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $r = $this->DB->fetch() )
				{
					if ( isset( $projectCache[ $r['project_id'] ] ) && is_array( $projectCache[ $r['project_id'] ] ) )
					{
						$projectCache[ $r['project_id'] ]['num_issues'] = $r['num_issues'];
						$projectCache[ $r['project_id'] ]['num_posts']  = $r['num_posts'];
					}
				}
			}

			// Perms array for database data
			$perms = array();

			// Grabs permissions from the IPS systems
			$this->DB->build(
				array(
					'select'   => '*',
					'from'     => 'permission_index',
					'where'    => "app='tracker'"
				)
			);
			$this->DB->execute();

			// Load in the various permissions
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $r = $this->DB->fetch() )
				{
					$perms[ $r['perm_type'] ][ $r['perm_type_id'] ] = $r;
				}
			}

			// Loop through project permissions
			// $cache[] ->
			//     [*projects_id*] ->
			//         ['perms'] ->
			//             [] -> permission DB row for use by IPS perms class
			if ( is_array( $perms['project'] ) && count( $perms['project'] ) > 0 )
			{
				foreach( $perms['project'] as $id => $r )
				{
					if ( isset( $projectCache[ $r['perm_type_id'] ] ) && is_array( $projectCache[ $r['perm_type_id'] ] ) )
					{
						$projectCache[ $r['perm_type_id'] ]['perms'] = $r;
					}
				}
			}

			// Grabs the custom field orders
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> 'tracker_project_field',
					'order'		=> 'project_id, position',
					'where'		=> 'enabled=1'
				)
			);
			$this->DB->execute();

			// Array to track projects with custom orders
			$customPIDs = array();

			// Loop through them to load the shell structure
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $r = $this->DB->fetch() )
				{
					if ( isset( $projectCache[ $r['project_id'] ] ) && is_array( $projectCache[ $r['project_id'] ] ) )
					{
						// Set custom order flag for use later
						$customPIDs[ $r['project_id'] ] = true;

						$projectCache[ $r['project_id'] ]['fields'][ $r['field_id'] ] = array();
					}
				}
			}

			// Grabs the custom field metadata
			$this->DB->build(
				array(
					'select'   => '*',
					'from'     => 'tracker_project_metadata',
					'order'    => 'project_id, field_id'
				)
			);
			$this->DB->execute();

			// Load the custom field metadata
			if ( $this->DB->getTotalRows() > 0 )
			{
				while ( $r = $this->DB->fetch() )
				{
					// Only load metadata for active fields in the project
					if ( isset( $projectCache[ $r['project_id'] ]['fields'][ $r['field_id'] ] ) && is_array( $projectCache[ $r['project_id'] ]['fields'][ $r['field_id'] ] ) )
					{
						// Drop in the custom meta value
						$projectCache[ $r['project_id'] ]['fields'][ $r['field_id'] ][ $r['meta_key'] ] = $r['meta_value'];
					}
				}
			}

			// Get last post data
			foreach( $projectCache as $id => $r )
			{
				$privacy 		= '';
				$selectPrivacy 	= '';

				// Privacy
				if ( $this->registry->tracker->modules()->moduleIsInstalled( 'privacy', TRUE ) )
				{
					$field = $this->registry->tracker->fields()->getFieldByKeyword('privacy');

					if ( isset($r['fields'][ $field['field_id'] ]) )
					{
						$selectPrivacy	= ', module_privacy';
						$privacy 		= " AND module_privacy=0";
					}
				}

				// Grabs last post information for the project
				$r = $this->DB->buildAndFetch(
					array(
						'select'    => 'project_id, issue_id, title, title_seo, last_post AS datetime, last_poster_id AS poster_id, last_poster_name AS poster_name, last_poster_name_seo as poster_seo_name' . $selectPrivacy,
						'from'      => 'tracker_issues',
						'where'     => "project_id={$id}{$privacy}",
						'order'     => 'last_post DESC',
						'limit'     => array( 0, 1 )
					)
				);

				if ( is_array( $r ) && isset( $projectCache[ $r['project_id'] ] ) && is_array( $projectCache[ $r['project_id'] ] ) )
				{
					$projectCache[ $r['project_id'] ]['last_post'] = $r;

					// Real member?
					if ( $r['poster_id'] )
					{
						$projectCache[ $r['project_id'] ]['last_post']['user']	= IPSMember::buildProfilePhoto( $r['poster_id'] );
					}
					else
					{
						// They are a guest, oh noes, we'll error out!
						// Credit to @MadMikeyB for being 'helpful'
						$projectCache[ $r['project_id'] ]['last_post']['user']	= IPSMember::buildDisplayData(
							array(
								'member_id'	=> 0,
								'members_display_name'	=> $r['poster_name'] ? $this->settings['guest_name_pre'] . $r['poster_name'] . $this->settings['guest_name_suf'] : $this->lang->words['global_guestname']
							)
						);
					}

					/* Strippin' crap */
					$projectCache[ $r['project_id'] ]['last_post']['user'] = array('pp_small_photo' => $projectCache[ $r['project_id'] ]['last_post']['user']['pp_small_photo']);
				}
			}

			// Separate out the various permissions
			$templatePerms = array();
			$projectPerms  = array();

			// Loop through the base perm type
			foreach( $perms as $type => $data )
			{
				$type = preg_replace( '#(.+?)Field#is', '', $type );;

				// Find template types
				if ( substr( $type, -8, 8 ) == 'Template' )
				{
					$id = substr( $type, 0, strlen( $type ) - 8 );

					// Get the field
					$field = $this->registry->tracker->fields()->getFieldByName( $id );

					// If the field exists, store the perms in the array
					if ( is_array( $field ) && isset( $field['field_id'] ) && $field['field_id'] > 0 )
					{
						$templatePerms[ $field['field_id'] ] = $data;
					}
				}
				// Find project types
				else if ( substr( $type, -7, 7 ) == 'Project' )
				{
					$id = strtolower( substr( $type, 0, strlen( $type ) - 7 ) );

					// Get the field
					$field = $this->registry->tracker->fields()->getFieldByName( $id );

					// If the field exists, store the perms in the array
					if ( is_array( $field ) && isset( $field['field_id'] ) && $field['field_id'] > 0 )
					{
						$projectPerms[ $field['field_id'] ] = $data;
					}
				}
			}

			//Now loop through the projects and fields to load the perms into the cache
			foreach ( $projectCache as $id => $p )
			{
				if ( isset( $p['fields'] ) && is_array( $p['fields'] ) && count( $p['fields'] ) > 0 )
				{
					// Loop through each field in the project
					foreach( $p['fields'] as $fid => $fdata )
					{
						// Check for project-specific permission first
						if ( isset( $projectPerms[ $fid ] ) && isset( $projectPerms[ $fid ][ $id ] ) )
						{
							$projectCache[ $id ]['fields'][ $fid ]['perms'] = $projectPerms[ $fid ][ $id ];
						}
						// Then it must be in the template permissions
						else if ( $p['template_id'] > 0 && isset( $templateCache[ $fid ] ) && isset( $templateCache[ $fid ][ $p['template_id'] ] ) )
						{
							$projectCache[ $id ]['fields'][ $fid ]['perms'] = $templateCache[ $fid ][ $p['template_id'] ];
						}
					}
				}
			}
		}

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $projectCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->projectCache = $projectCache;
		$this->processForApplication();
	}

	/**
	 * Rebuilds a single project in place
	 *
	 * @param int $projectID the project ID to be updated
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function update( $projectID )
	{
		$project = array();

		$this->checkForRegistry();

		// Get the project from the database
		$project = $this->DB->buildAndFetch(
			array(
				'select'    => '*',
				'from'      => 'tracker_projects',
				'where'     => "project_id = {$projectID}",
				'order'     => 'parent_id, position',
			)
		);

		// Load the local cache from the database
		if ( is_array( $project ) && count( $project ) > 0 )
		{
			// Setup arrays for other information
			$project['num_issues']    = 0;
			$project['num_posts']     = 0;
			$project['depthed_title'] = $project['title'];
			$project['title_seo']	  = IPSText::makeSeoTitle( $project['title'] );
			$project['fields']        = array();
			$project['last_post']     = array();
			$project['perms']         = array();

			// Versions
			if ( $this->registry->tracker->modules()->moduleIsInstalled( 'versions', TRUE ) )
			{
				$this->registry->tracker->fields()->initialize( $projectID, FALSE, TRUE );

				// Grab version permissions
				$perms = $this->registry->tracker->fields()->field('version', false, true)->getPerms();

				$project['alter_versions'] = $perms['perm_5'];
			}


			// Statuses
			if ( $this->registry->tracker->modules()->moduleIsInstalled( 'status', FALSE ) )
			{
				$serial = array( 'status' => array() );

				$this->DB->build(
					array(
						'select' => 'module_status_id, COUNT(*) AS count',
						'from'   => 'tracker_issues',
						'where'  => 'project_id = ' . $projectID,
						'group'  => 'module_status_id'
					)
				);
				$status = $this->DB->execute();

				$count = 0;

				if ( $this->DB->getTotalRows( $status ) )
				{
					while ( $row = $this->DB->fetch( $status ) )
					{
						$serial['status'][ $row['module_status_id'] ] = intval( $row['count'] );
						$count += $row['count'];
					}
				}

				// Add it to project
				$project['serial_data'] = serialize( $serial );

				// Reset
				$count = 0;
			}

			// Start with the project and work backwards
			$current      = $project;
			$projectCache = $this->projectCache;

			// Looping through the parents to build the depth
			while( $current['parent_id'] != 0 )
			{
				// Find the next parent in the chain
				$next = $projectCache[ $current['parent_id'] ];

				// Create the left edge (last)
				if ( $next['parent_id'] == 0 )
				{
					$project['depthed_title'] = "&nbsp;&nbsp;&#0124;{$this->depthGuide}" . $project['depthed_title'];
				}
				// This is the first, so a space is needed
				else if ( $current['project_id'] == $id )
				{
					$project['depthed_title'] = "{$this->depthGuide} " . $project['depthed_title'];
				}
				// Regular depth add
				else
				{
					$project['depthed_title'] = $this->depthGuide . $project['depthed_title'];
				}

				// Swap in the next parent as the new current
				$current = $next;
			}

			// Grabs issue count
			$issueCount = $this->DB->buildAndFetch(
				array(
					'select'   => 'count(issue_id) AS num_issues, sum(posts) AS num_posts',
					'from'     => 'tracker_issues',
					'where'    => "project_id = {$projectID}"
				)
			);
			$this->DB->execute();

			// Load in the issue count
			if ( is_array( $issueCount ) && isset( $issueCount['num_issues'] ) )
			{
				$project['num_issues'] = $issueCount['num_issues'];
				$project['num_posts']  = $issueCount['num_posts'];
			}

			// Perms array for database data
			$perms = array();

			// Grabs permissions from the IPS systems
			$this->DB->build(
				array(
					'select'   => '*',
					'where'    => "app='tracker'",
					'from'     => 'permission_index'
				)
			);
			$this->DB->execute();

			// Load in the various permissions
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $r = $this->DB->fetch() )
				{
					$perms[ $r['perm_type'] ][ $r['perm_type_id'] ] = $r;
				}
			}

			// Find the perms for this project
			if ( is_array( $perms['project'] ) && count( $perms['project'] ) > 0 )
			{
				foreach( $perms['project'] as $id => $r )
				{
					if ( $r['perm_type_id'] == $projectID )
					{
						$project['perms'] = $r;
					}
				}
			}

			// Grabs the custom field orders
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> 'tracker_project_field',
					'order'		=> 'project_id, position',
					'where'		=> 'enabled=1'
				)
			);
			$this->DB->execute();

			// Array to track projects with custom orders
			$custom = FALSE;

			// Loop through them to load the shell structure
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $r = $this->DB->fetch() )
				{
					if ( $r['project_id'] == $projectID )
					{
						// Set custom order flag for use later
						$custom = TRUE;

						$project['fields'][ $r['field_id'] ] = array();
					}
				}
			}

			// Grabs the custom field metadata
			$this->DB->build(
				array(
					'select'   => '*',
					'from'     => 'tracker_project_metadata',
					'order'    => 'project_id, field_id',
					'where'    => "project_id = {$projectID}"
				)
			);
			$this->DB->execute();

			// Load the custom field metadata
			if ( $this->DB->getTotalRows() > 0 )
			{
				while ( $r = $this->DB->fetch() )
				{
					// Only load metadata for active fields in the project
					if ( isset( $project['fields'][ $r['field_id'] ] ) && is_array( $project['fields'][ $r['field_id'] ] ) )
					{
						// Drop in the custom meta value
						$project['fields'][ $r['field_id'] ][ $r['meta_key'] ] = $r['meta_value'];
					}
				}
			}

			$privacy 		= '';
			$selectPrivacy 	= '';

			// Privacy
			if ( $this->registry->tracker->modules()->moduleIsInstalled( 'privacy', TRUE ) )
			{
				$field = $this->registry->tracker->fields()->getFieldByKeyword('privacy');

				if ( isset($project['fields'][ $field['field_id'] ]) )
				{
					$selectPrivacy	= ', module_privacy';
					$privacy 		= " AND module_privacy=0";
				}
			}

			// Grabs last post information for the projects
			$lastPost = $this->DB->buildAndFetch(
				array(
					'select'    => 'project_id, issue_id, title, title_seo, last_post AS datetime, last_poster_id AS poster_id, last_poster_name AS poster_name, last_poster_name_seo as poster_seo_name' . $selectPrivacy,
					'from'      => 'tracker_issues',
					'where'     => "project_id = {$projectID}{$privacy}",
					'order'     => 'last_post DESC',
					'limit'     => array( 0, 1 )
				)
			);

			// Add the post to the project
			if ( is_array( $lastPost ) && count( $lastPost ) > 0 )
			{
				$project['last_post'] 			= $lastPost;

				if ( $lastPost['poster_id'] )
				{
					$project['last_post']['user']	= IPSMember::buildProfilePhoto( $lastPost['poster_id'] );
				}
				else
				{
					// They are a guest, oh noes, we'll error out!
					// Credit to @MadMikeyB for being 'helpful'
					$project['last_post']['user']	= IPSMember::buildDisplayData(
						array(
							'member_id'	=> 0,
							'members_display_name'	=> $lastPost['poster_name'] ? $this->settings['guest_name_pre'] . $lastPost['poster_name'] . $this->settings['guest_name_suf'] : $this->lang->words['global_guestname']
						)
					);
				}

				/* Strippin' crap */
				$project['last_post']['user'] = array('pp_small_photo' => $project['last_post']['user']['pp_small_photo']);
			}

			// Separate out the various permissions
			$templatePerms = array();
			$projectPerms  = array();

			// Loop through the base perm type
			foreach( $perms as $type => $data )
			{
				$type = preg_replace( '#(.+?)Field#is', '', $type );;

				// Find template types
				if ( substr( $type, -8, 8 ) == 'Template' )
				{
					$id = substr( $type, 0, strlen( $type ) - 8 );

					// Get the field
					$field = $this->registry->tracker->fields()->getFieldByName( $id );

					// If the field exists, store the perms in the array
					if ( is_array( $field ) && isset( $field['field_id'] ) && $field['field_id'] > 0 )
					{
						$templatePerms[ $field['field_id'] ] = $data;
					}
				}
				// Find project types
				else if ( substr( $type, -7, 7 ) == 'Project' )
				{
					$id = strtolower( substr( $type, 0, strlen( $type ) - 7 ) );

					// Get the field
					$field = $this->registry->tracker->fields()->getFieldByName( $id );

					// If the field exists, store the perms in the array
					if ( is_array( $field ) && isset( $field['field_id'] ) && $field['field_id'] > 0 )
					{
						$projectPerms[ $field['field_id'] ] = $data;
					}
				}
			}

			//Now loop through the projects and fields to load the perms into the cache
			if ( isset( $project['fields'] ) && is_array( $project['fields'] ) && count( $project['fields'] ) > 0 )
			{
				// Loop through each field in the project
				foreach( $project['fields'] as $fid => $fdata )
				{
					// Check for project-specific permission first
					if ( isset( $projectPerms[ $fid ] ) && isset( $projectPerms[ $fid ][ $project['project_id'] ] ) )
					{
						$project['fields'][ $fid ]['perms'] = $projectPerms[ $fid ][ $project['project_id'] ];
					}
					// Then it must be in the template permissions
					else if ( $p['template_id'] > 0 && isset( $templateCache[ $fid ] ) && isset( $templateCache[ $fid ][ $p['template_id'] ] ) )
					{
						$project['fields'][ $fid ]['perms'] = $templateCache[ $fid ][ $p['template_id'] ];
					}
				}
			}
		}

		// Update this cache entry
		$this->projectCache[ $projectID ] = $project;

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $this->projectCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Post-process the database tinyints back into boolean natives
		$this->processForApplication();
	}

	/**
	 * Grab the latest last post and issue count data for a project in the cache
	 *
	 * @param int $projectID the project ID to be updated
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function updateDynamicData( $projectID )
	{
		if ( isset( $this->projectCache[ $projectID ] ) && is_array( $this->projectCache[ $projectID ] ) && count( $this->projectCache[ $projectID ] ) > 0 )
		{
			// Get the project
			$project = $this->projectCache[ $projectID ];

			// Grabs last post information for the projects
			$lastPost = $this->DB->buildAndFetch(
				array(
					'select'    => 'issue_id, title, last_post AS datetime, last_poster_id AS poster_id, last_poster_name AS poster_name',
					'from'      => 'tracker_issues',
					'where'     => "project_id = {$projectID}",
					'order'     => 'last_post DESC',
					'limit'     => array( 0, 1 )
				)
			);

			// Add the post to the project
			if ( is_array( $lastPost ) && count( $lastPost ) > 0 )
			{
				$project['last_post'] = $lastPost;
			}

			// Grabs issue count
			$issueCount = $this->DB->buildAndFetch(
				array(
					'select'   => 'count(issue_id) AS num_issues, sum(posts) AS num_posts',
					'from'     => 'tracker_issues',
					'where'    => "project_id = {$projectID}"
				)
			);
			$this->DB->execute();

			// Load in the issue count
			if ( is_array( $issueCount ) && isset( $issueCount['num_issues'] ) )
			{
				$project['num_issues'] = $issueCount['num_issues'];
				$project['num_posts']  = $issueCount['num_posts'];
			}

			// Update this cache entry
			$this->projectCache[ $projectID ] = $project;

			// Save the updated cache to the database
			$this->cache->setCache( $this->cacheName, $this->projectCache, array( 'array' => 1, 'deletefirst' => 1 ) );

			// Post-process the database tinyints back into boolean natives
			$this->processForApplication();
		}
	}
}

?>