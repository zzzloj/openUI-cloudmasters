<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-05-27 04:02:29 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicModules
* @link			http://ipbtracker.com
* @version		$Revision: 1367 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_projects_list extends iptCommand
{
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output = '';

	/**
	* Stored temporary page title
	*
	* @access	protected
	* @var 		string 				Page title
	*/
	protected $pageTitle = '';

	/**
	* DB query start value
	*
	* @access	protected
	* @var 		integer
	*/
	protected $first = 0;

	/**
	* DB query limit value
	*
	* @access	protected
	* @var 		integer
	*/
	protected $max_results = 20;

	/**
	* DB query sort key
	*
	* @access	protected
	* @var 		string
	*/
	protected $sort_key = '';

	/**
	* DB sort order
	*
	* @access	protected
	* @var 		string
	*/
	protected $sort_order = 'desc';

	/**
	* DB filtering
	*
	* @access	protected
	* @var 		string
	*/
	protected $filter = 'ALL';

	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @return	void		[Outputs to screen]
	*/	
	public function doExecute( ipsRegistry $registry )
	{
		/* Add initial navigation */
		$this->tracker->addGlobalNavigation();

		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_project' ) );

		if ( ! isset( $this->request['showproject'] ) )
		{
			$this->request['showproject'] = '';
		}
		
		// last five issues
		$userLastFive = $this->getLastFive();

		/* Online Users */
		$stats_html      	= '';
		$sub_stats_html  	= '';
		$sub_stats_html		.= $this->tracker->generateActiveUsers( 'overview', -1 );

		/* Show stats */
		$totals				= $this->showTotals();
		$stats				= array(
			'activeUsers'		=> $sub_stats_html,
			'totals'			=> $totals
		);

		/* Page Title */
		$this->tracker->pageTitle = IPSLib::getAppTitle( 'tracker' ) . ' - ' . $this->settings['board_name'];

		$this->tracker->output .= $this->registry->output->getTemplate('tracker_projects')->trackerIndexTemplate( $this->printProject(), $stats, $userLastFive );

		/* Send output */
		$this->tracker->sendOutput();
	}
	
	private function getLastFive()
	{
		// Guests can't use this
		if ( ! $this->memberData['member_id'] )
		{
			return array();
		}
		
		// Continue
		$projects = $this->tracker->projects()->getSearchableProjects( $this->memberData['member_id'] );
		
		if ( ! $projects )
		{
			return array();
		}
		
		$data = array();
		
		// Add in our member joins
		$tags	= array(
			array(
				'select'	=> 'm.members_display_name as i_starter_name, m.members_seo_name as i_starter_name_seo',
				'from'		=> array( 'members' => 'm' ),
				'where'		=> 'm.member_id=ti.starter_id',
				'type'		=> 'left'
			),
			
			array(
				'select'	=> 'mm.members_display_name as i_last_poster_name, mm.members_seo_name as i_last_poster_name_seo',
				'from'		=> array( 'members' => 'mm' ),
				'where'		=> 'mm.member_id=ti.last_poster_id',
				'type'		=> 'left'
			)
		);
		
		$this->DB->build(
			array(
				'select'	=> 'ti.*',
				'from'		=> array( 'tracker_issues' => 'ti' ),
				'add_join'	=> $tags,
				'where'		=> 'ti.starter_id=' . $this->memberData['member_id'] . ' AND ti.project_id IN ( ' . implode( ',', $projects ) . ' )',
				'limit'		=> array( 0, 5 ),
				'order'		=> 'ti.last_post DESC'
			)
		);
		$out = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $out ) > 0 )
		{
			while( $issue = $this->DB->fetch( $out ) )
			{
				// Project for issue
				$project = $this->tracker->projects()->getProject( $issue['project_id'] );				
				
				// Fields
				$this->tracker->fields()->initialize( $issue['project_id'] );
				
				// Add in fields
				$issue	= $this->tracker->issues()->parseRow( $issue, $project );
				$data[] = $issue;
			}
		}
		
		// return back to the skin
		return $data;
	}

	protected function projectsNewPosts( $project_data )
	{
		$children = $this->tracker->projects()->getChildren( $project_data['project_id'] );

		if ( isset( $project_data['status'] ) AND $project_data['status'] == 'readonly' )
		{
			return 'f_locked';
		}

		$sub = 0;

		if ( isset( $children ) AND is_array( $children ) and count( $children ) )
		{
			$sub = 1;
		}

		$pid = $project_data['project_id'];

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $pid ), 'tracker' );

		# Children? - seems to fix bug #22319: Marking subcat read marks parent read
		# Removed 26 May 2012 - AFAICT this code does absolutely nothing. -Ryan
		// if ( is_array( $children ) )
		// {
		// 	foreach ( $children as $child )
		// 	{
		// 		$parent = $this->tracker->projects()->getChildren( $project_id );
				
		// 		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $child['project_id'] ), 'tracker' );
		// 		$child['_has_unread'] = 0;
				
		// 		if( $child['last_post'] && $child['last_post']['date time'] > $rtime )
		// 		{
		// 			$forum_data['_has_unread']		= 1;
		// 			$data['_has_unread']            = 1;
		// 		}
		// 	}
		// }

		if ( $sub == 0 )
		{
			$sub_cat_img = '';
		}
		else
		{
			$sub_cat_img = '_cat';
		}
		
		// return $project_data;
		
		return ( $project_data['last_post']['datetime'] && $project_data['last_post']['datetime'] > $rtime ) ? 'f' . $sub_cat_img . '_unread' : 'f' . $sub_cat_img . '_read';
	}
	
	public function printProject( $project_id='root' )
	{
		$html  = '';
		$count = 0;
		
		// If we're not in root project, grab last time we read it!
		if ( $project_id!='root' )
		{
			$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $pid ), 'tracker' );
		}

		if( is_array( $this->tracker->projects()->getChildren( $project_id ) ) && count( $this->tracker->projects()->getChildren( $project_id ) ) > 0 )
		{
			$projects = array();
			
			foreach ( $temp = $this->tracker->projects()->getChildren( $project_id ) as $id => $data )
			{
				if ( $data['cat_only'] && ! count($this->tracker->projects()->getChildren( $id ) ) > 0 )
				{
					continue;
				}
				
				// Do we have permission?
				if ( ! $this->tracker->projects()->checkPermission('show', $id) )
				{
					continue;
				}

				$data = $this->parseRow( $data, $owner_info, $owners );

				// Assign to our main array
				$projects[] = $data;
				$count++;
			}
		}

		return array( $count, $project_id, $projects );
	}
	
	public function showTotals()
	{
		if ( $this->settings['tracker_show_active'] )
		{
			$projects = $this->tracker->cache('stats')->getProjects();
			$issues   = $this->tracker->cache('stats')->getIssues();
			$posts    = $this->tracker->cache('stats')->getPosts();

			$projects = $this->registry->getClass('class_localization')->formatNumber( $projects );		
			$issues   = $this->registry->getClass('class_localization')->formatNumber( $issues );
			$posts    = $this->registry->getClass('class_localization')->formatNumber( $posts );

			return array( 'projects' => $projects, 'issues' => $issues, 'posts' => $posts );
		}

		return array();
	}
	
	protected function parseRow( $row=array() )
	{
		$row				= $this->tracker->projects()->getCalcChildren( $row['project_id'], $row );
		$row['_total']		= $this->tracker->projects()->getIssueCount( $row['project_id'] );
		$row['_replies']	= $this->tracker->projects()->getPostCount( $row['project_id'] );

		/* Moderators */
		$this->tracker->moderators()->buildModerators($row['project_id']);
		
		if ( $this->settings['tracker_showsubprojects'] )
		{
			if ( is_array( $this->tracker->projects()->getChildren( $row['project_id'] ) ) && count( $this->tracker->projects()->getChildren( $row['project_id'] ) ) > 0 )
			{
				foreach ( $this->tracker->projects()->getChildren( $row['project_id'] ) as $s => $d )
				{
					if ( ! $this->tracker->projects()->checkPermission( 'show', $d['project_id'] ) )
					{
						continue;
					}
					
					$d['name_seo'] = IPSText::makeSeoTitle( $d['title'] );
					$row['project_sub_projects'][ $s ] = $d;
					$newP = $this->projectsNewPosts( $d );
					$row['project_sub_projects'][ $s ]['project_new_items'] = ($newP == 'f_unread' OR $newP == 'f_cat_unread') ? 1 : 0;
				}
			}
		}
		
		$row['last_text'] = isset( $this->lang->words['bt_view_last_post_by'] ) ? $this->lang->words['bt_view_last_post_by'] : '';

		// Can we see last post?
		if ( $this->tracker->projects()->checkPermission( 'show', $row['last_post']['project_id'] ) )
		{
			// We need to make sure the issue title isn't too long
			$row['last_post']['title'] = strip_tags( $row['last_post']['title'] );
			$row['last_post']['title'] = str_replace( "&#33;" , "!", $row['last_post']['title'] );
			$row['last_post']['title'] = str_replace( "&quot;", '"', $row['last_post']['title'] );
		}
		else if ( $row['last_post']['issue_id'] )
		{
			$row['last_post']['protected']	= 1;
		}
		
		return $row;
	}
}
