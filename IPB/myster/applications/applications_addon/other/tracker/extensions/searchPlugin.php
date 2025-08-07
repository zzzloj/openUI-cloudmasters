<?php

/**
* Tracker 2.1.0
* 
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class searchTrackerPlugin implements iSearchIndexPlugin
{
	/**
	 * Database object
	 *
	 * @access	private
	 * @var		object
	 */			
	private $DB;
	
	/**
	 * Date range restriction start
	 *
	 * @access	private
	 * @var		integer
	 */		
	private $search_begin_timestamp = 0;
	
	/**
	 * Date range restriction end
	 *
	 * @access	private
	 * @var		integer
	 */		
	private $search_end_timestamp   = 0;

	/**
	 * Array of conditions for this search
	 *
	 * @access	private
	 * @var		array
	 */		
	private $whereConditions        = array();

	/**
	 * Remove self from search results
	 *
	 * @access	public
	 * @var		boolean
	 */		
	public $removeSelf				= false;
	
	/**
	 * Do not join posts table on
	 *
	 * @access	public
	 * @var		boolean
	 */		
	public $onlyTitles			= false;
	
	/**
	 * Only search posts
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $onlyPosts			= false;
	
	/**
	 * Don't show post preview
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $noPostPreview		= false;
	
	/**
	 * Searching by/for author content
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $searchAuthor		= false;
	
	
	/**
	 * Holds search results
	 *
	 * @access	private
	 * @var		array
	 */
	private	$_results			= array();
	
	/**
	 * Holds DB resource
	 *
	 * @access	private
	 * @var		object
	 */
	private	$_resultsDbResource	= null;

	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @param	object	ipsRegistry $registry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->DB     = $registry->DB();
		$this->member = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		
		if ( ! $this->registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $this->registry );
	
			$this->registry->setClass( 'tracker', $this->tracker );
		}
	}
	
	/**
	 * Get whether or not we're showing as forum or not
	 *
	 * @param	public
	 * @return	bool
	 */
	public function getShowAsForum()
	{
		return ( $this->memberData['bw_forum_result_type'] ) ? FALSE : TRUE;
	}
	
	/**
	 * Custom search for view new content. Better optimized: Count
	 *
	 * @access	public
	 * @return	int
	 */
	public function viewNewPosts_count()
	{
		/* fetch the where statement */
		$where = $this->_viewNewPosts_where();
		
		/* Fetch the count */
		$this->DB->build( array( 'select' => 'count(*) as total_results',
								 'from'   => 'tracker_issues ti',
								 'where'  => $where ) );
								
		$this->DB->execute();
		
		$c = $this->DB->fetch();
		
		return intval( $c['total_results'] );
	}
	
	/**
	 * Custom search for view new content. Better optimized: Fetch
	 *
	 * @access	public
	 * @param	array  		Limit array( x, rows );
	 * @return	int
	 */
	public function viewNewPosts_fetch( $limit=array() )
	{
		/* fetch the where statement */
		$where   = $this->_viewNewPosts_where();
		$entries = array();
		
		/* Fetch the count */
		$this->DB->build( array( 'select'   => 'ti.*',
								 'from'     => array( 'tracker_issues' => 'ti' ),
								 'where'    => $where,
								 'add_join' => array( array( 'select' => 'tfp.*',
															 'from'   => array( 'tracker_posts' => 'tfp' ),
															 'where'  => 'tfp.issue_id=ti.issue_id AND tfp.new_issue=1',
															 'type'   => 'left' ),
													  array( 'select' => 'm.members_display_name, m.members_seo_name, m.member_id',
													 		 'from'   => array( 'members' => 'm' ),
													         'where'  => 'm.member_id=p.author_id',
													         'type'   => 'left' ) ),
								 'order'    => 'ti.last_post DESC',
								 'limit'    => $limit ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( is_array( $this->registry->tracker->projects()->projects[ $row['project_id'] ] ) )
			{
				$row = array_merge( $this->registry->tracker->projects()->projects[ $row['project_id'] ], $row );
			}
			
			/* Reassign stuff to match the search_index */
			$entries[] = $this->formatFieldsForIndex( $row );
		}
		
		return $entries;
	}
	
	/**
	 * Builds a where statement for get new posts
	 *
	 * @access	private
	 * @return	int
	 */
	private function _viewNewPosts_where()
	{
		/* Loop through the forums and build a list of forums we're allowed access to */
		$where       = array();
		$forumIdsOk  = array();
		$forumIdsBad = array();

		foreach( $this->registry->tracker->projects()->projects as $id => $data )
		{
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'read', $data ) )
			{
				$forumIdsBad[] = $id;
				continue;
			}
			
			if ( $data['cat_only'] )
			{
				$forumIdsBad[] = $id;
				continue;
			}
			
			$forumIdsOk[] = $id;
		}
		
		/* Add allowed forums */
		$where[] = "ti.project_id IN (" . implode( ",", $forumIdsOk ) . ")";
		
		/* Generate last post times */
		if ( ! $this->memberData['bw_vnc_type'] )
		{
			$where[] = "ti.last_post > " . IPSLib::fetchHighestNumber( array( intval( $this->memberData['_cache']['gb_mark__forums'] ), intval( $this->memberData['last_visit'] ) ) );
		}
		else
		{
			$_or = array();
			
			foreach( $this->registry->tracker->projects()->projects as $forumId => $forumData )
			{
				$lastMarked	= $this->registry->getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $forumId ), 'tracker' );

				$readItems	= $this->registry->getClass('classItemMarking')->fetchReadIds( array( 'forumID' => $forumId ), 'tracker' );
				$readItems	= ( is_array( $readItems ) AND count( $readItems ) ) ? $readItems : array();

				if( count($readItems) )
				{
					$_or[] = "(ti.project_id={$forumId} AND ti.issue_id NOT IN(" . implode( ",", $readItems ) . ") AND ti.last_post > " . intval($lastMarked) . ")";
				}
				else
				{
					$_or[] = "(ti.project_id={$forumId} AND ti.last_post > " . intval($lastMarked) . ")";
				}
			}
			
			if ( count( $_or ) )
			{
				$where[] = '(' . implode( " OR ", $_or ) . ')';
			}
		}
		
		/* Add in last bits */
		$where[] = "ti.private=0";
		
		return implode( " AND ", $where );
	}
	
	/**
	 * Performs search and returns an array of results
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @param	array	$limit_clause	The erray should be array( begin, end )
	 * @param	string	$sort_by		Column to sort by
	 * @param	string	$group_by		Column to group by
	 * @param	bool	$content_title_only	Only search title records
	 * @return	array
	 */	
	public function getSearchResults( $search_term, $limit_clause, $sort_by, $group_by='', $content_title_only=false, $sort_order='' )
	{
		/* Got our resource? */
		if ( $this->_resultsDbResource === null )
		{
			return array();
		}

		/* Build result array */
		$rows    = array();
		$members = array();
		$count   = 0;
		$got     = 0;
		
		while( $r = $this->DB->fetch( $this->_resultsDbResource ) )
		{
			$count++;
			
			if ( $limit_clause[0] AND $limit_clause[0] >= $count )
			{
				continue;
			}
			
			/* Got author but no member data? */
			if ( ! empty( $r['author_id'] ) AND empty( $r['members_display_name'] ) )
			{
				$members[ $r['author_id'] ] = $r['author_id'];
			}
			
			/* Reassign stuff to match the search_index */
			$rows[ $got ] = $this->formatFieldsForIndex( $r );
			$got++;
			
			/* Done? */
			if ( $limit_clause[1] AND $got >= $limit_clause[1] )
			{
				break;
			}
		}
		
		/* Need to load members? */
		if ( count( $members ) )
		{
			$mems = IPSMember::load( $members, 'all' );
			
			foreach( $rows as $id => $r )
			{
				if ( ! empty( $r['author_id'] ) AND isset( $mems[ $r['author_id'] ] ) )
				{
					unset( $mems[ $r['author_id'] ]['posts'] );
					unset( $mems[ $r['author_id'] ]['last_post'] );
					$rows[ $id ] = array_merge( $rows[ $id ], $mems[ $r['author_id'] ] );
				}
			}
		}
		
		return $rows;
	}
	
	/**
	 * Performs live search and returns an array of results
	 * NOT AVAILABLE IN BASIC SEARCH
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @return	array
	 */		
	public function getLiveSearchResults( $search_term )
	{	
		return array();
	}		
	
	/**
	 * Returns the total number of results the search will return
	 *
	 * @access	public
	 * @param	string	$search_term		Search term
	 * @param	string	$group_by			Column to group by
	 * @param	bool	$content_title_only	Only search title records
	 * @return	integer
	 */	
	public function getSearchCount( $search_term, $group_by='', $content_title_only=false, $limit=array(), $sort_by='', $sort_order='' )
	{
		/* Reset */
		$this->_results           = array();
		$this->_resultsDbResource = '';
		
		/* Limit to 1000 items, store the data in PHP. Quite simples */
		$order_dir = ( $sort_order == 'asc' ) ? 'asc' : 'desc';

		/* Sort By */
		if( in_array( $sort_by, array( 'date', 'relevance' ) ) )
		{
			$order = $sort_by == 'date' ? ( $content_title_only ? 'ti.last_post ' . $order_dir : 'tfp.post_date ' . $order_dir ) :  ( ipsRegistry::$settings['use_fulltext'] ? 'ranking ' . $order_dir : ( $content_title_only ? 'ti.last_post ' . $order_dir : 'tfp.post_date ' . $order_dir ) );
		}
		else
		{
			$order = ipsRegistry::$settings['use_fulltext'] ? 'ranking DESC' : ( $content_title_only ? 'ti.last_post' : 'tfp.post_date DESC' );
		}

		/* If there is no search term, we need to force search by updated */
		if( ! $search_term )
		{
			$order = $content_title_only ? 'ti.last_post ' . $order_dir : 'tfp.post_date ' . $order_dir;
		}
		
		if ( $this->noPostPreview )
		{
			$group_by = 'tfp.issue_id';
		}
		else
		{
			if( $group_by )
			{	
				$group_by = 'tfp.' . $group_by;	
			}
			else if( $this->onlyTitles )
			{
				$group_by = 'tfp.issue_id';
			}
		}
		
		/* Search in titles */
		if( $content_title_only )
		{
			/* Ranking */
			$ranking_select = ( $search_term AND ipsRegistry::$settings['use_fulltext'] AND $sort_by == 'relevance' ) ? ", " . $this->DB->buildSearchStatement( 'ti.title', $search_term, true, true ) : '';

			/* Do the search */
			$this->DB->build( array( 
									'select'   => "ti.*{$ranking_select}",
									'from'	   => array( 'tracker_issues' => 'ti' ),
	 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only, $order ),
									'limit'    => array(0, 200),
									'order'    => $order,
									'add_join' => array(
														array(
																'select' => 'tfp.*',
																'from'	 => array( 'tracker_posts' => 'tfp' ),
												 				'where'	 => 'tfp.issue_id=ti.issue_id AND tfp.new_issue=1',
												 				'type'	 => 'left',
															),
														array(
																'select' => 'm.members_display_name, m.members_seo_name, m.member_id',
																'from'   => array( 'members' => 'm' ),
																'where'  => "m.member_id=p.author_id",
																'type'   => 'left',
															),
														array(
																'select' => 'tp.project_id as forum_id',
																'from'	 => array( 'tracker_projects' => 'tp' ),
												 				'where'	 => 'tp.projec_id=ti.project_id',
												 				'type'	 => 'left',
															),
														)
								)		);
		}
		/* Search in posts and titles */
		else
		{
			/* Ranking */
			$ranking_select = ( $search_term AND ipsRegistry::$settings['use_fulltext'] AND $sort_by == 'relevance' ) ? ", " . $this->DB->buildSearchStatement( 'tfp.post', $search_term, true, true ) : '';

			/* Do the search */
			$this->DB->build( array( 
									'select'   => "tfp.*" . $ranking_select,
									'from'	   => array( 'tracker_posts' => 'tfp' ),
	 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only, $order, null, ( $this->onlyPosts || $this->searchAuthor ) ? false : true ),
									'limit'    => array(0, 200),
									'order'    => $order,
									'group'    => $group_by,
									'add_join' => array(
														array(
																'select' => 'ti.*',
																'from'	 => array( 'tracker_issues' => 'ti' ),
												 				'where'	 => 'ti.issue_id=tfp.issue_id',
												 				'type'	 => 'left',
															) )
								)	);
		}

		$this->_resultsDbResource = $this->DB->execute();

		/* Fetch count */
		return intval( $this->DB->getAffectedRows( $this->_resultsDbResource ) );
	}
	
	/**
	 * Restrict the date range that the search is performed on
	 *
	 * @access	public
	 * @param	int		$begin	Start timestamp
	 * @param	int		[$end]	End timestamp
	 * @return	void
	 */
	public function setDateRange( $begin, $end=0 )
	{
		$this->search_begin_timestamp = $begin;
		$this->search_end_timestamp   = $end;
	}
	
	/**
	 * mySQL function for adding special search conditions
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @param	string	$operator	Operation to perform for this condition, ex: =, <>, IN, NOT IN
	 * @param	mixed	$value		Value to check with
	 * @param	string	$comp		Comparison type
	 * @return	void
	 */
	public function setCondition( $column, $operator, $value, $comp='AND' )
	{
		/* Remap */
		$column = $column == 'member_id'     ? 'starter_id' : $column;
		$column = $column == 'content_title' ? 'title'      : $column;
		
		if( $column == 'app' OR $column == 'type_id' )
		{
			return;
		}
		
		/* Build the condition based on operator */
		switch( strtoupper( $operator ) )
		{
			case 'IN':
			case 'NOT IN':
				$this->whereConditions[$comp][] = "ti.{$column} {$operator} ( {$value} )";
			break;
			
			default:
				$this->whereConditions[$comp][] = "ti.{$column} {$operator} {$value}";
			break;
		}
		
	}
	
	/**
	 * Allows you to specify multiple conditions that are chained together
	 *
	 * @access	public
	 * @param	array	$conditions	Array of conditions, each element has 3 keys: column, operator, value, see the setCondition function for information on each
	 * @param	string	$inner_comp	Comparison operator to use inside the chain
	 * @param	string	$outer_comp	Comparison operator to use outside the chain
	 * @return	void
	 */
	public function setMultiConditions( $conditions, $inner_comp='OR', $outer_comp='AND' )
	{
		/* Loop through the conditions to build the statement */
		$_temp = array();
		
		foreach( $conditions as $r )
		{
			/* REMAP */
			if( $r['column'] == 'type_id' )
			{
				continue;
			}
			
			if( $r['column'] == 'app' )
			{
				continue;
			}
			
			switch( strtoupper( $r['operator'] ) )
			{
				case 'IN':
				case 'NOT IN':
					$_temp[] = "ti.{$r['column']} {$r['operator']} ( {$r['value']} )";
				break;
				
				default:
					$_temp[] = "ti.{$r['column']} {$r['operator']} {$r['value']}";
				break;
			}
		}

		$this->whereConditions[$outer_comp][] = '( ' . implode( $_temp, ' ' . $inner_comp . ' ' ) . ' ) ';
	}		
	
	/**
	 * Set search conditions for "View unread content"
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUnreadConditions()
	{
		$forum_conditions	= array();
		
		foreach( $this->registry->tracker->projects()->projects as $forumId => $forumData )
		{
			$lastMarked	= ipsRegistry::getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $forumId ), 'tracker' );

			$readItems	= ipsRegistry::getClass('classItemMarking')->fetchReadIds( array( 'forumID' => $forumId ), 'tracker' );
			$readItems	= ( is_array( $readItems ) AND count( $readItems ) ) ? $readItems : array();
			
			if( count($readItems) )
			{
				$this->whereConditions['OR'][] = "(ti.project_id={$forumId} AND ti.issue_id NOT IN(" . implode( ",", $readItems ) . ") AND ti.last_post > " . intval($lastMarked) . ")";
			}
			else
			{
				$this->whereConditions['OR'][] = "(ti.project_id={$forumId} AND ti.last_post > " . intval($lastMarked) . ")";
			}
		}
	}
	
	/**
	 * Builds the where portion of a search string
	 *
	 * @access	private
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @param	string	$order				Order by data
	 * @param	bool	$onlyPosts			Enforce posts only
	 * @param	bool	$noForums			Don't check forums that posts are in
	 * @return	string
	 **/
	private function _buildWhereStatement( $search_term, $content_title_only=false, $order='', $onlyPosts=null, $noForums=false )
	{		
		/* INI */
		$where_clause = array();
		$onlyPosts    = ( $onlyPosts !== null ) ? $onlyPosts : $this->onlyPosts;
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
		$forumIdsBad = array();
		
		if ( ! empty( ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) AND count( ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) )
		{
			foreach(  ipsRegistry::$request['search_app_filters']['tracker']['projects'] as $forum_id )
			{
				if( $forum_id )
				{
					$data	= $this->registry->tracker->projects()->projects[ $forum_id ];
					
					/* Can we read? */
					if ( ! $this->registry->permissions->check( 'read', $data, 'tracker' ) )
					{
						$forumIdsBad[] = $forum_id;
						continue;
					}
					
					if ( $data['cat_only'] )
					{
						$forumIdsBad[] = $forum_id;
						continue;
					}
				
					$forumIdsOk[] = $forum_id;
					
					$children = $this->registry->tracker->projects()->getChildren( $forum_id );
				
					foreach( $children as $kid )
					{
						if( ! in_array( $kid, ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) )
						{
							$forumIdsOk[] = $kid;
						}
					}
				}
			}
		}
		
		if( !count($forumIdsOk) )
		{
			foreach( $this->registry->tracker->projects()->projects as $id => $data )
			{
				/* Can we read? */
				if ( ! $this->registry->permissions->check( 'read', $data ) )
				{
					$forumIdsBad[] = $id;
					continue;
				}
				
				if ( $data['cat_only'] )
				{
					$forumIdsBad[] = $id;
					continue;
				}
				
				$forumIdsOk[] = $id;
			}
		}
		
		/* Add allowed forums */
		if ( count( $forumIdsOk )  AND $noForums !== true )
		{
			$where_clause[] = "ti.project_id IN (" . implode( ",", $forumIdsOk ) . ")";
		}
		
		/* Exclude some items */
		if( ! $this->member->tracker['is_super'] )
		{
			$author			= $content_title_only ? "ti.starter_id=" . $this->memberData['member_id'] : "tfp.author_id=" . $this->memberData['member_id'];
			
			/* No hidden topics, or topics in password protected forums */
			$where_clause[] = 'ti.private=0';
		}
		
		if( $search_term )
		{
			if( $content_title_only )
			{			
				$where_clause[] = $this->DB->buildSearchStatement( 'ti.title', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
			}
			else
			{
				if ( $onlyPosts )
				{
					$where_clause[] = $this->DB->buildSearchStatement( 'tfp.post', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
				}
				else
				{
					/* Find topic ids that match */
					$tids = array( 0 => 0 );
					$pids = array( 0 => 0 );
					
					/* Determine ranking */
					$tr = ( $search_term AND ipsRegistry::$settings['use_fulltext'] AND strstr( $order, 'ranking' ) ) ? ", " . $this->DB->buildSearchStatement( 'ti.title', $search_term, true, true ) : '';
					$pr = ( $search_term AND ipsRegistry::$settings['use_fulltext'] AND strstr( $order, 'ranking' ) ) ? ", " . $this->DB->buildSearchStatement( 'ti.title', $search_term, true, true ) : '';
					
					$this->DB->build( array( 
											'select'   => "ti.issue_id, ti.last_post, ti.project_id" . $tr,
											'from'	   => 'tracker_issues ti',
			 								'where'	   => str_replace( 'tfp.author_id', 'ti.starter_id', $this->_buildWhereStatement( $search_term, true, $order, null ) ),
											'limit'    => array(0, 200),
											'order'    => str_replace( 'tfp.post_date', 'ti.last_post', $order ) ) );
								
					$i = $this->DB->execute();
					
					while( $row = $this->DB->fetch( $i ) )
					{
						$tids[] = $row['tid'];
					}
					
					/* Now get the Pids */
					if ( count( $tids ) > 1 )
					{
						$this->DB->build( array(
												'select'  => 'pid',
												'from'	  => 'tracker_posts',
												'where'   => 'issue_id IN ('. implode( ',', $tids ) . ') AND new_issue=1' ) );
						
						$i = $this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							$pids[ $row['pid'] ] = $row['pid'];
						}
					}
					
					$this->DB->build( array( 
											'select'   => "tfp.pid" . $pr,
											'from'	   => array( 'tracker_posts' => 'tfp' ),
			 								'where'	   => $this->_buildWhereStatement( $search_term, false, $order, true ),
											'limit'    => array(0, 200),
											'order'    => $order,
											'add_join' => array( array( 'select' => 'ti.private, ti.project_id',
																		'from'   => array( 'tracker_issues' => 'ti' ),
																		'where'  => 'tfp.issue_id=ti.issue_id',
																		'type'   => 'left' ) ) ) );
								
					$i = $this->DB->execute();
					
					while( $row = $this->DB->fetch( $i ) )
					{
						$pids[ $row['pid'] ] = $row['pid'];
					}
					
					$where_clause[] = '( tfp.pid IN (' . implode( ',', $pids ) .') )';
				}
			}
		}
		
		/* Limit by forum */
		$type      = ipsRegistry::$request['type'];
		$type_id   = intval( ipsRegistry::$request['type_id'] );

		if( $type && $type_id )
		{
			$where_clause[] = "ti.project_id={$type_id}";
		}
		
		/* Limit by topic */
		$type_2    = ipsRegistry::$request['type_2'];
		$type_id_2 = intval( ipsRegistry::$request['type_id_2'] );
		
		if( $type_2 && $type_id_2 )
		{
			$where_clause[] = $content_title_only ? "ti.issue_id={$type_id_2}" : "tfp.issue_id={$type_id_2}";
		}

		/* Date Restrict */
		
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[] = $this->DB->buildBetween( $content_title_only ? "ti.last_post" : "tfp.post_date", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = $content_title_only ? "ti.last_post > {$this->search_begin_timestamp}" : "tfp.post_date > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = $content_title_only ? "ti.last_post < {$this->search_end_timestamp}" : "tfp.post_date < {$this->search_end_timestamp}";
			}
		}
		
		/* Add in AND where conditions */
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause = array_merge( $where_clause, $this->whereConditions['AND'] );
		}
		
		/* ADD in OR where conditions */
		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[] = '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}
	
	/**
	 * Reassigns fields in a way the index exepcts
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function formatFieldsForIndex( $r )
	{
		$r['app']					= 'tracker';
		$r['content']				= $r['post'];
		$r['content_title']			= $r['title'];
		$r['updated']				= $r['post_date'];
		$r['lastupdate']			= $r['last_post'];
		$r['type_2']				= 'issue';
		$r['type_id_2']				= $r['issue_id'];
		$r['misc']					= $r['pid'];

		return $r;
	}
	
	/**
	 * This function grabs the actual results for display
	 *
	 * @param  array  $ids
	 * @return query result
	 **/
	public function getResultsForSphinx( $ids )
	{
		if( ipsRegistry::$request['content_title_only'] == 1 )
		{
			$this->DB->build( array( 
									'select'   => "ti.*",
									'from'	   => array( 'tracker_issues' => 'ti' ),
		 							'where'	   => 'ti.issue_id IN( ' . implode( ',', $ids ) . ')',
		 							'order'    => 'ti.last_post DESC',
									'add_join' => array(
														array(
																'select'	=> 'tfp.*',
																'from'		=> array( 'tracker_posts' => 'tfp' ),
												 				'where'		=> 'tfp.issue_id=ti.issue_id AND tfp.new_issue',
												 				'type'		=> 'left',
															),
														array(
																'from'		=> array( 'tracker_projects' => 'tp' ),
												 				'where'		=> 'tp.project_id=ti.project_id',
												 				'type'		=> 'left',
															),
														array(
																'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
																'from'		=> array( 'members' => 'm' ),
												 				'where'		=> 'm.member_id=p.author_id',
												 				'type'		=> 'left',
															),
														)													
								)	);
		}
		else
		{
			$this->DB->build( array( 
									'select'   => "tfp.*",
									'from'	   => array( 'tracker_posts' => 'tfp' ),
		 							'where'	   => 'tfp.pid IN( ' . implode( ',', $ids ) . ')',
		 							'order'    => 'tfp.post_date DESC',
									'add_join' => array(
														array(
																'select'	=> 'ti.*',
																'from'		=> array( 'tracker_issues' => 'ti' ),
												 				'where'		=> 'ti.issue_id=tfp.issue_id',
												 				'type'		=> 'left',
															),
														array(
																'from'		=> array( 'tracker_projects' => 'tp' ),
												 				'where'		=> 'tp.project_id=ti.project_id',
												 				'type'		=> 'left',
															),
														array(
																'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
																'from'		=> array( 'members' => 'm' ),
												 				'where'		=> 'm.member_id=p.author_id',
												 				'type'		=> 'left',
															),
														)													
								)	);
		}

		return $this->DB->execute();
	}
	
	/**
	 * Gets the name of the field this search index uses for dates.... >_<
	 *
	 * @return string
	 **/
	public function getDateField()
	{
		if( ipsRegistry::$request['content_title_only'] == 1 )
		{
			return 'last_post';
		}
		else
		{
			return 'post_date';
		}
	}
	
	/**
	 * Gets field names for other conditions
	 *
	 * @param	string	Column
	 * @return	string
	 **/
	public function getConditionField( $column )
	{
		switch( $column )
		{
			case 'member_id':
				return 'author_id';
			break;
		}

		return $column;
	}
}