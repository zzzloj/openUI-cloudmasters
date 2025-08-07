<?php

/**
* Tracker 2.1.0
* 
* Issue Timeline module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicModules
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_projects_timeline extends iptCommand
{
	protected $data	= array();
	protected $days = array();

	public function doExecute( ipsRegistry $registry )
	{
		/* Languages */
		$this->registry->class_localization->loadLanguageFile( array( 'public_timeline' ), 'tracker' );
		
		/* Get the issue we our attempting to load */
		$issueData				= $this->getIssue( intval( $this->request['issueId'] ) );
		$issueData['name_seo']	= IPSText::makeSeoTitle( $issueData['title'] );
		
		/* Other information */
		$projectData	= $this->tracker->projects()->getProject( $issueData['project_id'] );
		
		// Fields
		$this->tracker->fields()->initialize( $projectData['project_id'] );
		$this->tracker->fields()->setIssue( $issueData );
		
		// Private issue
		if ( $issueData['module_privacy'] && ! $this->tracker->fields()->checkPermission( $projectData, 'privacy', 'show' ) )
		{
			$this->registry->output->showError( 'We could not find the issue you were attempting to view' );
		}
		
		/* Add initial navigation */
		$this->tracker->addGlobalNavigation();
		$this->tracker->projects()->createBreadcrumb( $projectData['project_id'] );
		$this->registry->output->addNavigation( $issueData['title'], "app=tracker&showissue={$issueData['issue_id']}" );
		
		/* Page Title */
		$this->tracker->pageTitle =  'Viewing timeline for: ' . $issueData['title'] . ' - ' . IPSLib::getAppTitle( 'tracker' );

		/* Output */
		$this->tracker->output .= $this->registry->output->getTemplate('tracker_timeline')->showTimeline( $this->data, $issueData, $this->days );

		/* Send output */
		$this->tracker->sendOutput();
	}
	
	private function getIssue( $issueId )
	{
		if ( ! $issueId )
		{
			$this->registry->output->showError( 'We could not find an issue to display data for', '10T114' );
		}
		
		$issue = $this->DB->buildAndFetch(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_issues',
				'where'		=> 'issue_id=' . $issueId
			)
		);
		
		if ( ! $issue['issue_id'] )
		{
			$this->registry->output->showError( 'We could not find an issue to display data for', '10T114' );
		}
		
		$start	= $this->request['st'] ? intval( $this->request['st'] ) : 0;
		$count	= 0;
		$currentDay	= '';
		
		/* Time zones */
		$offset	= $this->registry->getClass('class_localization')->getTimeOffset();
		
		$this->DB->build(
			array(
				'select'	=> 'fc.*',
				'from'		=> array( 'tracker_field_changes' => 'fc' ),
				'add_join'	=> array(
					0		=> array(
						'select'	=> 'm.members_display_name,m.member_id,m.members_seo_name',
						'from'		=> array( 'members' => 'm' ),
						'where'		=> 'fc.mid=m.member_id',
						'type'		=> 'left'
					)
				),
				'where'		=> 'fc.issue_id=' . $issueId,
				'order'		=> 'fc.date DESC'
			)
		);
		$data = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $data ) )
		{
			while( $row = $this->DB->fetch( $data ) )
			{
				$count++;
				
				/* Fixed 0 for fixed in: None */
				if ( $row['module'] == 'fixed_in' && $row['new_value'] == '0' )
				{
					$row['field_new_value'] = "None";
				}
				
				$row['timelineStamp']		= $row['date'];
				$row['timelineEntryDate']	= $this->getHour( $row['date'] + $offset );
				
				$this->data[ $row['date'] . '-' . $count ] = $row;
			}
		}
		
		/* Replies */
		$this->DB->build(
			array(
				'select'	=> 'tp.*',
				'from'		=> array( 'tracker_posts' => 'tp' ),
				'add_join'	=> array(
					0		=> array(
						'select'	=> 'm.members_display_name,m.member_id,m.members_seo_name',
						'from'		=> array( 'members' => 'm' ),
						'where'		=> 'tp.author_id=m.member_id',
						'type'		=> 'left'
					)
				),
				'where'		=> 'tp.new_issue=0 AND tp.issue_id=' . $issueId,
				'order'		=> 'tp.post_date DESC'
			)
		);
		$posts = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $posts ) )
		{
			while( $row = $this->DB->fetch( $posts ) )
			{
				$count++;
				
				/* Remove auto post stuff */
				$row['post']				= preg_replace( "#Updating (.+?) to:(.+?)(<br \/>|$)#is", '', $row['post'] );
				$row['post']				= preg_replace( "#Issue fixed in:(.+?)(<br \/>|$)#is", '', $row['post'] );

				/* Handle attachment */
				$row['post']				= preg_replace( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", '-\-attach-/-', $row['post'] );
				
				/* Remove any whitespace */
				$row['new_post']			= trim( $row['post'] );
							
				/* Go on with the formatting */
				$row['field_type']			= 'post';
				
				/* Only attachment? */
				if ( strstr( $row['post'], '-\-attach-/-' ) )
				{
					$row['module']		= 'attach';
				}

				/* Date stamps */
				$row['timelineStamp']		= $row['post_date'];
				$row['timelineEntryDate']	= $this->getHour( $row['post_date'] + $offset );
				
				/* After this has been done, do we actually have a post anymore? */
				if ( ( $row['new_post'] != '' && $row['module'] != 'attach' ) || $row['module'] == 'attach' )
				{
					$this->data[ $row['post_date'] . '-' . $count ] = $row;
				}
			}
		}
		
		$count++;
		$this->data[ $issue['start_date'] . '-' . $count ] = array(
			'module'				=> 'issue_started',
			'timelineStamp'			=> $issue['start_date'],
			'timelineEntryDate'		=> $this->getHour( $issue['start_date'] + $offset ),
			'members_display_name'	=> $issue['starter_name'],
			'member_id'				=> $issue['starter_id']
		);
		
		krsort( $this->data );
		
		/* Only want to show 25! */
		$this->data = array_splice( $this->data, $start, 25 );
		
		foreach( $this->data as $k => $v )
		{
			if ( $currentDay != $this->getDay( $v['timelineStamp'] ) )
			{
				if ( $currentDay != '' )
				{
					$this->data[$k]['timelineEndDay'] = true;
				}
				
				$currentDay = $this->getDay( $v['timelineStamp'] );
				
				$this->data[$k]['timelineNewDay']	= true;
				$this->data[$k]['timelineDay']		= $currentDay;
			}
			
			if ( ! isset( $this->days[ $currentDay ] ) )
			{
				$this->days[ $currentDay ] = 0;
			}
			
			$this->days[ $currentDay ] = $this->days[ $currentDay ] + 1;
		}
		
		/* Pagination */
		$postCount = $this->DB->buildAndFetch(
			array(
				'select'	=> 'COUNT(*) as posts',
				'from'		=> 'tracker_posts',
				'where'		=> 'new_issue=0 AND issue_id=' . $issueId
			)
		);
		
		$entryCount = $this->DB->buildAndFetch(
			array(
				'select'	=> 'COUNT(*) as entries',
				'from'		=> 'tracker_field_changes',
				'where'		=> 'issue_id=' . $issueId
			)
		);
		
		$issue['SHOW_PAGES'] = $this->registry->output->generatePagination(
			array(
				'totalItems'        => ( $postCount['posts'] + $entryCount['entries'] ),
				'itemsPerPage'      => 25,
				'currentStartValue' => $start,
				'baseUrl'           => 'app=tracker&module=projects&section=timeline&issueId=' . $issue['issue_id'],
			)
		);
		
		return $issue;
	}
	
	private function getDay( $timestamp='' )
	{
		return date( 'l F d, Y', $timestamp );
	}
	
	private function getHour( $timestamp='' )
	{
		return date( 'g:ia', $timestamp );
	}
}

?>