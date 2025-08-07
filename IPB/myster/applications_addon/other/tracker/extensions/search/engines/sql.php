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

class search_engine_tracker extends search_engine
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );
		
		if ( ! $registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $registry );
	
			$registry->setClass( 'tracker', $this->tracker );
		}
		
		parent::__construct( $registry );
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function search()
	{
		/* INIT */ 
		$count       		= 0;
		$results     		= array();
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$post_search_only   = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows    			= array();
		$count   			= 0;
		$c                  = 0;
		$got     			= 0;
		$sortKey			= '';
		$sortType			= '';
		$cType              = IPSSearchRegistry::get('contextual.type');
		$cId		        = IPSSearchRegistry::get('contextual.id' );
		
		/* Contextual search */
		if ( $cType == 'issue' )
		{
			$content_title_only = false;
			$post_search_only   = true;
			
			IPSSearchRegistry::set('opt.searchTitleOnly', false);
			IPSSearchRegistry::set('opt.onlySearchPosts', true);
			IPSSearchRegistry::set('display.onlyTitles', false);
			IPSSearchRegistry::set('opt.noPostPreview', false);
		}
		
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = ( $content_title_only ) ? 'last_post' : 'post_date';
				$sortType = 'numerical';
			break;
			case 'title':
				$sortKey  = 'title';
				$sortType = 'string';
			break;
			case 'posts':
				$sortKey  = 'posts';
				$sortType = 'numerical';
			break;
		}
				
		/* Search in titles */
		if ( $content_title_only )
		{
			/* Do the search */
			$this->DB->build( array( 
									'select'   => "ti.issue_id as my_issue_id, ti.*",
									'from'	   => 'tracker_issues ti',
	 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only, $order ),
									'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'order'    => 'ti.'.$sortKey . ' ' . $order_dir ) );
		}
		/* Search in posts and titles */
		else
		{
			/* Do the search */
			$this->DB->build( array( 
									'select'   => "p.pid, p.post_date, p.issue_id as my_issue_id",
									'from'	   => array( 'tracker_posts' => 'p' ),
	 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only, $order, null, ( IPSSearchRegistry::get('opt.onlySearchPosts') || IPSSearchRegistry::get('in.search_author') ) ? false : true ),
									'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'order'    => $sortKey . ' ' . $sort_order,
									'add_join' => array( array( 'select' => 'ti.*',
																'from'	 => array( 'tracker_issues' => 'ti' ),
												 				'where'	 => 'ti.issue_id=p.issue_id',
												 				'type'	 => 'left' ) ) ) );
		}

		$DB = $this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows( $DB ) );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}
		
		/* Fetch all to sort */
		while( $r = $this->DB->fetch( $DB ) )
		{
			$_rows[ $r['my_issue_id'] ] = $r;
		}
		
		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $order_dir );
		IPSSearch::$ast = $sortType;
		
		/* Sort */
		if ( count( $_rows ) )
		{
			usort( $_rows, array("IPSSearch", "usort") );
		
			/* Build result array */
			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $r['pid'];
							
				$got++;
				
				/* Done? */
				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}
	
		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform the search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewUserContent( $member )
	{
		$app	=	IPSSearchRegistry::get('in.search_app');
		$where	=	array();
	
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		
		switch( IPSSearchRegistry::get('in.userMode') )
		{
			default:
			case 'all': 
				IPSSearchRegistry::set('opt.searchTitleOnly', true );
				IPSSearchRegistry::set('display.onlyTitles' , true );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;
			case 'title': 
				IPSSearchRegistry::set('opt.searchTitleOnly', true );
				IPSSearchRegistry::set('display.onlyTitles' , true );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
			case 'content': 
				IPSSearchRegistry::set('opt.searchTitleOnly', false );
				IPSSearchRegistry::set('display.onlyTitles' , false );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
		}
		
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage    = IPSSearchRegistry::get('opt.search_per_page');

		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		
		/* Ensure we limit by date */
		$this->settings['search_ucontent_days'] = ( $this->settings['search_ucontent_days'] ) ? $this->settings['search_ucontent_days'] : 365;
		
		/* Get list of good forum IDs */
		$forumIdsOk	= $this->registry->tracker->projects()->getSearchableProjects( $this->memberData['member_id'] );
		$forumIdsOk = ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );

		$topic_where[]	= "ti.project_id IN (" . implode( ",", $forumIdsOk ) . ")";
		
		if ( IPSSearchRegistry::get('in.userMode') != 'title' )
		{
			$where[] = "tp.author_id=" . intval( $member['member_id'] );
		}
		
		if ( $this->settings['search_ucontent_days'] )
		{
			if ( IPSSearchRegistry::get('in.userMode') != 'title' )
			{
				$where[] = "tp.post_date > " . ( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
			}
			
			$topic_where[] = "ti.start_date > " . ( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
		}
		
		/* Exclude some items */
		if ( $this->registry->tracker->modules()->moduleIsInstalled('privacy') )
		{
			$topic_where[] = "ti.module_privacy=0";
		}

		/* Manual fetch if user content all */
		if ( IPSSearchRegistry::get('in.userMode') == 'all' )
		{
			/* init */
			$pids = array();
			
			$this->DB->build( array( 'select'   => 'ti.*',
									 'from'     => 'tracker_issues ti',
									 'where'    => implode( ' AND ', $topic_where ) . " AND ti.starter_id=" . intval( $member['member_id'] ),
									 'order'    => 'ti.last_post DESC',
									 'limit'    => array(0, 1000 ) ) );
			$this->DB->execute();
			
			while( $t = $this->DB->fetch() )
			{
				$pids[ $t['issue_id'] ] = $t['last_post'];
			}
			
			$where = ( array_merge( (array) $where, (array) $topic_where ) );
			$where = implode( " AND ", $where );
			
			/* Now for posts */
			$this->DB->build( array( 'select'   => 'tp.pid',
									 'from'     => array('tracker_posts' => 'tp' ),
									 'where'    => $where,
									 'add_join' => array( array( 'select' => 'ti.*',
									 							 'from'   => array( 'tracker_issues' => 'ti' ),
							 		  							 'where'  => 'tp.issue_id=ti.issue_id',
							 		  							 'type'   => 'left' ) ),
									 'order'    => 'tp.pid DESC',
									 'limit'    => array( 0, 1000 ) ) );
									 
			$this->DB->execute();
			
			while( $t = $this->DB->fetch() )
			{
				$pids[ $t['issue_id'] ] = $t['last_post'];
			}
			
			$count = array( 'count' => count( $pids ) );
			
			if ( $count['count'] )
			{
				arsort( $pids, SORT_NUMERIC );
				$issue_ids = array_slice( array_keys( $pids ), $start, $perPage );
			} 
		}
		else if ( IPSSearchRegistry::get('in.userMode') == 'title' )
		{
			$topic_where[] = "ti.starter_id=" . intval( $member['member_id'] );
			
			$where = ( array_merge( (array) $where, (array) $topic_where ) );
			$where = implode( " AND ", $where );

			/* Fetch the count */
			$count = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(issue_id) as count',
											  		  'from'     => 'tracker_issues ti',
											 		  'where'    => $where ) );
									 
			/* Fetch the count */
			if ( $count['count'] )
			{
				$this->DB->build( array( 'select'   => 'issue_id',
										 'from'     => 'tracker_issues ti',
										 'where'    => $where,
										 'order'    => 'ti.issue_id DESC',
										 'limit'    => array( $start, $perPage ) ) );
										
				$this->DB->execute();
			
				while( $row = $this->DB->fetch( $o ) )
				{
					$issue_ids[ $row['issue_id'] ] = $row['issue_id'];
				}
			}
		}
		else
		{
			$where = ( array_merge( (array) $where, (array) $topic_where ) );
			$where = implode( " AND ", $where );
			
			/* Fetch the count */
			$count = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(issue_id) as count',
											  		  'from'     => array('tracker_issues' => 'ti' ),
											 		  'where'    => $where,
											 		  'add_join' => array( array( 'from'   => array( 'posts' => 'p' ),
											 		  							  'where'  => 'p.topic_id=ti.issue_id',
											 		  							  'type'   => 'left' ) ) ) );
									 
			/* Fetch the count */
			if ( $count['count'] )
			{
				$this->DB->build( array( 'select'   => 'issue_id',
										 'from'     => array('tracker_issues' => 'ti' ),
										 'where'    => $where,
										 'add_join' => array( array( 'select' => 'p.pid',
								 		  							 'from'   => array( 'posts' => 'p' ),
								 		  							 'where'  => 'p.topic_id=ti.issue_id',
								 		  							 'type'   => 'left' ) ),
										 'order'    => 'ti.issue_id DESC',
										 'limit'    => array( $start, $perPage ) ) );
										
				$this->DB->execute();
			
				while( $row = $this->DB->fetch( $o ) )
				{
					$issue_ids[ $row['pid'] ] = $row['pid'];
				}
			}
		}
		
		/* Fix to 1000 results max */
		$count['count'] = ( $count['count'] > 1000 ) ? 1000 : $count['count'];
	
		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $issue_ids );
	}
	
	/**
	 * Perform the viewNewContent search
	 * Forum Version
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewNewContent()
	{
		$rissue_ids      = array();
		$issue_ids       = $this->registry->getClass('classItemMarking')->fetchCookieData( 'tracker', 'items' );
		$oldStamp   = $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'tracker' );
		$check      = IPS_UNIX_TIME_NOW - ( 86400 * 90 );
		$forumIdsOk = array();
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$bvnp       = explode( ',', $this->settings['vnp_block_forums'] );
		$start		= IPSSearchRegistry::get('in.start');
		$perPage    = IPSSearchRegistry::get('opt.search_per_page');
		$seconds	= IPSSearchRegistry::get('in.period_in_seconds');
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchTitleOnly' , true );
		IPSSearchRegistry::set('display.onlyTitles'  , true );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );

		/* Get list of good forum IDs */
		$_forumIdsOk	= $this->registry->tracker->projects()->getSearchableProjects( $this->memberData['member_id'] );

		/* Period filtering */
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$where[]  = "last_post > " .  ( IPS_UNIX_TIME_NOW - $seconds );

			$forumIdsOk = $_forumIdsOk;
		}
		else
		{
			if ( intval( $this->memberData['_cache']['gb_mark__forums'] ) > 0 )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__forums'];
			}

			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}

			/* Older than 3 months.. then limit */
			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}
		
//		if ( ! $this->memberData['bw_vnc_type'] )
//		{
//			$oldStamp = IPSLib::fetchHighestNumber( array( intval( $this->memberData['_cache']['gb_mark__forums'] ), intval( $this->memberData['last_visit'] ) ) );
//			$where[]  = "last_post > " .  $oldStamp;
//
//			$forumIdsOk = $_forumIdsOk;
//		}
//		else
//		{
			foreach( $_forumIdsOk as $id )
			{
				$lMarked    = $this->registry->getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $id ), 'tracker' );
				$fData      = $this->registry->tracker->projects()->getProject( $id );
			
				if ( $fData['last_post'] > $lMarked )
				{
					/* Add to issue_ids */
					$_issue_ids = $this->registry->getClass('classItemMarking')->fetchReadIds( array( 'forumID' => $id ), 'tracker', false );
					
					if ( is_array( $_issue_ids ) )
					{
						foreach( $_issue_ids as $k => $v )
						{
							$issue_ids[ $k ] = $v;
						}
					}
					
					$forumIdsOk[ $id ] = $id;
				}
			}

			/* If no forums, we're done */
			if ( ! count( $forumIdsOk ) )
			{
				/* Return it */
				return array( 'count' => 0, 'resultSet' => array() );
			}
			
			/* Try and limit the issue_idS */
			if ( is_array( $issue_ids ) AND count( $issue_ids ) )
			{
				if ( count( $issue_ids ) > 300 )
				{
					/* Sort by last read date */
					arsort( $issue_ids, SORT_NUMERIC );
					$issue_ids = array_slice( $issue_ids, 0, 300 );
				}
								
				$this->DB->build( array( 'select' => 'issue_id, last_post',
								   		 'from'   => 'tracker_issues',
								   	     'where'  => "issue_id IN (" . implode( ",", array_keys( $issue_ids ) ) . ')' ) );
								   
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					/* Posted in since last read? */
					if ( $row['last_post'] > $issue_ids[ $row['issue_id'] ] )
					{
						unset( $issue_ids[ $row['issue_id'] ] );
					}
				}
				
				if ( count( $issue_ids ) )
				{
					$issue_ids = array_keys( $issue_ids );
				}
			}
			
			/* Based on oldest timestamp */
			$where[] = "last_post > " . $oldStamp;
			
			/* Set read issue_ids */
			if ( count( $issue_ids ) )
			{
				$where[] = "issue_id NOT IN (" . implode( ",", $issue_ids ) . ')';
			}
		}

		$forumIdsOk	= ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
		$where[]	= "project_id IN (" . implode( ",", $forumIdsOk ) . ")";
		
		/* Set up perms */
		if ( $this->registry->tracker->modules()->moduleIsInstalled('privacy') )
		{
			$where[] = "module_privacy=0";
		}

		$where = implode( " AND ", $where );
		
		/* Fetch the count */
		$count = $this->DB->buildAndFetch( array( 'select'   => 'count(*) as count',
										  		  'from'     => 'tracker_issues',
										 		  'where'    => $where ) );
								 
		/* Fetch the count */
		if ( $count['count'] )
		{
			$this->DB->build( array( 'select'   => 'issue_id',
									 'from'     => 'tracker_issues',
									 'where'    => $where,
									 'order'    => 'last_post DESC',
									 'limit'    => array( $start, $perPage ) ) );
									
			$this->DB->execute();
			
			
		
			while( $row = $this->DB->fetch( $o ) )
			{
				$rissue_ids[ $row['issue_id'] ] = $row['issue_id'];
			}
		}
		
		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $rissue_ids );
	}
	
	/**
	 * Perform the search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewActiveContent()
	{
		$seconds = IPSSearchRegistry::get('in.period_in_seconds');
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$where		= array();
		$forumIdsOk	= array();
		$bvnp		= explode( ',', $this->settings['vnp_block_forums'] );
		$start		= IPSSearchRegistry::get('in.start');
		$perPage    = IPSSearchRegistry::get('opt.search_per_page');
		$issue_ids	    = array();
		
		/* Get list of good forum IDs */
		$forumIdsOk	= $this->registry->tracker->projects()->getSearchableProjects( $this->memberData['member_id'], $bvnp );

		$forumIdsOk	= ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
		$where[]	= "ti.project_id IN (" . implode( ",", $forumIdsOk ) . ")";

		/* Generate last post times */
		$where[] = "ti.last_post > " . intval( time() - $seconds );
		
		if ( $this->registry->tracker->modules()->moduleIsInstalled('privacy') )
		{
			$where[] = "ti.module_privacy=0";
		}
		
		$where = implode( " AND ", $where );
		
		/* Fetch the count */
		$count = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(issue_id) as count',
												  'from'     => array( 'tracker_issues' => 'ti' ),
												  'where'    => $where ) );
								
		
		/* Grab */
		$this->DB->build( array( 'select'   => 'issue_id',
								 'from'     => array( 'tracker_issues' => 'ti' ),
								 'where'    => $where,
								 'order'    => 'last_post DESC',
								 'limit'    => array( $start, $perPage ) ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$issue_ids[ $row['issue_id'] ] = $row['issue_id'];
		}
		
		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $issue_ids );
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
		$onlyPosts    = ( $onlyPosts !== null ) ? $onlyPosts : IPSSearchRegistry::get('opt.onlySearchPosts');
		$sort_by      = IPSSearchRegistry::get('in.search_sort_by');
		$sort_order   = IPSSearchRegistry::get('in.search_sort_order');
		$sortKey	  = '';
		$sortType	  = '';
		$cType        = IPSSearchRegistry::get('contextual.type');
		$cId		  = IPSSearchRegistry::get('contextual.id' );
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
		$forumIdsBad = array();
		
		if ( ! empty( ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) AND count( ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) )
		{
			foreach(  ipsRegistry::$request['search_app_filters']['tracker']['projects'] as $forum_id )
			{
				if( $forum_id )
				{
					$data	= $this->registry->tracker->projects()->getProject( $forum_id );
					
					/* Check for sub forums */
					$children = $this->registry->tracker->projects()->getChildren( $forum_id );

					foreach( $children as $kid )
					{
						if( ! in_array( $kid['project_id'], ipsRegistry::$request['search_app_filters']['tracker']['projects'] ) )
						{
							if( ! $this->registry->tracker->projects()->checkPermission( 'read', $kid['project_id'] ) )
							{
								$forumIdsBad[] = $kid['project_id'];
								continue;
							}

							if ( $child['cat_only'] )
							{
								$forumIdsBad[] = $kid['project_id'];
								continue;
							}
							
							$forumIdsOk[] = $kid['project_id'];
						}
					}

					/* Can we read? */
					if ( ! $this->registry->tracker->projects()->checkPermission( 'view', $data['project_id'] ) )
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
				}
			}
		}
		
		if( !count($forumIdsOk) )
		{
			/* Get list of good forum IDs */
			$forumIdsOk = $this->registry->tracker->projects()->getSearchableProjects(0);
		}
		
		/* Add allowed forums */
		if ( $noForums !== true )
		{
			$forumIdsOk = ( count( $forumIdsOk ) ) ? $forumIdsOk : array( 0 => 0 );
			
			/* Contextual */
			if ( $cType == 'project' AND $cId AND in_array( $cId, $forumIdsOk ) )
			{
				$where_clause[] = "ti.project_id=" . $cId;
			}
			else
			{
				$where_clause[] = "ti.project_id IN (" . implode( ",", $forumIdsOk ) . ")";
			}
		}
		
		/* Topic contextual */
		if ( $cType == 'issue' AND $cId )
		{
			$where_clause[] = "ti.issue_id=" . $cId;
		}
		
		if ( $this->registry->tracker->modules()->moduleIsInstalled('privacy') )
		{
			$where_clause[] = "ti.module_privacy=0";
		}
		
		if( $search_term )
		{
			$search_term = str_replace( '&quot;', '"', $search_term );
			
			if( $content_title_only )
			{			
				$where_clause[] = $this->DB->buildSearchStatement( 'ti.title', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
			}
			else
			{
				if ( $onlyPosts )
				{
					$where_clause[] = $this->DB->buildSearchStatement( 'p.post', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
				}
				else
				{
					/* Sorting */
					switch( $sort_by )
					{
						default:
						case 'date':
							$sortKey  = 'last_post';
							$sortType = 'numerical';
						break;
						case 'title':
							$sortKey  = 'title';
							$sortType = 'string';
						break;
						case 'posts':
							$sortKey  = 'posts';
							$sortType = 'numerical';
						break;
						case 'views':
							$sortKey  = 'views';
							$sortType = 'numerical';
						break;
					}

					/* Set vars */
					IPSSearch::$ask = $sortKey;
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = $sortType;
			
					/* Find topic ids that match */
					$issue_ids = array( 0 => 0 );
					$pids = array( 0 => 0 );
					
					$this->DB->build( array( 
											'select'   => "ti.issue_id, ti.last_post, ti.project_id",
											'from'	   => 'tracker_issues ti',
			 								'where'	   => str_replace( 'p.author_id', 'ti.starter_id', $this->_buildWhereStatement( $search_term, true, $order, null ) ),
			 								'order'    => 'ti.' . $sortKey . ' ' . $sort_order,
											'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit')) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$_rows[ $row['issue_id'] ] = $row;
					}
			
					/* Sort */
					if ( count( $_rows ) )
					{
						usort( $_rows, array("IPSSearch", "usort") );
				
						foreach( $_rows as $id => $row )
						{
							$issue_ids[] = $row['issue_id'];
						}
					}
					
					/* Now get the Pids */
					if ( count( $issue_ids ) > 1 )
					{
						$this->DB->build( array('select'  => 'pid',
												'from'	  => 'tracker_posts',
												'where'   => 'issue_id IN ('. implode( ',', $issue_ids ) . ') AND new_issue=1' ) );
						
						$i = $this->DB->execute();
						
						while( $row = $this->DB->fetch() )
						{
							$pids[ $row['pid'] ] = $row['pid'];
						}
					}
					
					/* Set vars */
					IPSSearch::$ask = ( $sortKey == 'last_post' ) ? 'post_date' : $sortKey;
					IPSSearch::$aso = strtolower( $sort_order );
					IPSSearch::$ast = $sortType;
					
					$this->DB->build( array( 
											'select'   => "p.pid",
											'from'	   => array( 'tracker_posts' => 'p' ),
			 								'where'	   => $this->_buildWhereStatement( $search_term, false, $order, true ),
			 								'order'    => IPSSearch::$ask . ' ' . IPSSearch::$aso,
											'limit'    => array(0, IPSSearchRegistry::get('set.hardLimit')),
											'add_join' => array( array( 'select' => 'ti.*',
																		'from'   => array( 'tracker_issues' => 'ti' ),
																		'where'  => 'p.issue_id=ti.issue_id',
																		'type'   => 'left' ) ) ) );
								
					$i = $this->DB->execute();
					
					/* Grab the results */
					while( $row = $this->DB->fetch( $i ) )
					{
						$_prows[ $row['pid'] ] = $row;
					}
			
					/* Sort */
					if ( count( $_prows ) )
					{
						usort( $_prows, array("IPSSearch", "usort") );
						
						foreach( $_prows as $id => $row )
						{
							$pids[ $row['pid'] ] = $row['pid'];
						}
					}
					
					$where_clause[] = '( p.pid IN (' . implode( ',', $pids ) .') )';
				}
			}
		}

		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{ 
			$where_clause[] = $this->DB->buildBetween( $content_title_only ? "ti.last_post" : "p.post_date", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = $content_title_only ? "ti.last_post > {$this->search_begin_timestamp}" : "p.post_date > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = $content_title_only ? "ti.last_post < {$this->search_end_timestamp}" : "p.post_date < {$this->search_end_timestamp}";
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
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id'     ? ( IPSSearchRegistry::get('opt.searchTitleOnly') ? 'ti.starter_id' : 'p.author_id' ) : $column;
		$column = $column == 'content_title' ? 'ti.title'     : $column;
		$column = $column == 'type_id'       ? 'ti.project_id'  : $column;
		
		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 **/
	public function buildFilterSQL( $data )
	{
		/* INIT */
		$return = array();
		
		/* Set up some defaults */
		IPSSearchRegistry::set( 'opt.noPostPreview'  , true );
		IPSSearchRegistry::set( 'opt.onlySearchPosts', false );
		
		/* Make default search type topics */
		if( isset( $data ) && is_array( $data ) && count( $data ) )
		{
			foreach( $data as $field => $_data )
			{
				/* CONTENT ONLY */
				if ( $field == 'noPreview' AND $_data['noPreview'] == 0 )
				{
					IPSSearchRegistry::set( 'opt.noPostPreview', false );
				}

				/* CONTENT ONLY */
				if ( $field == 'contentOnly' AND $_data['contentOnly'] == 1 )
				{
					IPSSearchRegistry::set( 'opt.onlySearchPosts', true );
				}

				/* POST COUNT */
				if ( $field == 'pCount' AND intval( $_data['pCount'] ) > 0 )
				{
					$return[] = array( 'column' => 'ti.posts', 'operator' => '>=', 'value' => intval( $_data['pCount'] ) );
				}
			}

			return $return;
		}
		else
		{
			return '';
		}
	}
}

?>