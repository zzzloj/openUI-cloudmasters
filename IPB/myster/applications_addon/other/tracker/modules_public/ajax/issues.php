<?php

/**
* Tracker 2.1.0
* 
* Issue Javascript PHP Interface
* Last Updated: $Date: 2011-06-13 20:38:08 +0100 (Mon, 13 Jun 2011) $
*
* @author		$Author: AlexHobbs $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1282 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * score: PUBLIC
 * Issue AJAX processor
 * 
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class public_tracker_ajax_issues extends ipsAjaxCommand 
{
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
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_project' ) );
		
		switch( $this->request['do'] )
		{
			case 'reproSave':
				$this->reproSave();
				break;
				
			case 'changeField':
				$this->changeField();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}
	
	private function changeField()
	{
		$type 		= $this->request['type'];
		$issue_id	= intval( $this->request['issue_id'] );
		
		if ( $type == 'dropdown' && $this->request['field'] && $issue_id )
		{
			$new_value	= intval( $this->request['new_value'] );
			$old_value	= intval( $this->request['old_value'] );
			
			// Check we are legit
			if ( $new_value != $this->request['new_value'] OR ! isset($this->request['new_value']) OR $old_value != $this->request['old_value'] OR ! isset($this->request['old_value']) )
			{
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
			}
		}
		else if ( $type == 'textbox' && $this->request['field'] && $issue_id )
		{
		
		}
		else
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
		}
		
		// Grab the issue
		$issue = $this->DB->buildAndFetch(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_issues',
				'where'		=> "issue_id=" . $issue_id
			)
		);
		
		// Grab associated project
		$project = $this->registry->tracker->projects()->getProject( $issue['project_id'] );

		// Check we had them
		if ( ! $issue['issue_id'] OR ! $project['project_id'] )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
		}
		
		// Load in fields library
		$field = $this->registry->tracker->fields()->field( $this->request['field'] );
		
		// Loop through fields
		foreach( $project['fields'] as $k => $v )
		{
			$cache = $this->registry->tracker->fields()->getField($k);
			
			if ( $cache['field_keyword'] == $this->request['field'] )
			{
				$meta = $v;
			}
		}
		
		// Send the permissions over
		$field->initialize( $project, $meta );

		// Check we have permission, and new and old aren't the same
		if ( ! $field->checkPermission('update') OR $new_value == $old_value )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
		}
		
		// Bug fix, javascript won't retain value, so we do it ourselves
		$this->request[ $field->returnFormName('form') ] = $new_value;
		
		// We have permission, so let's do it.
		$field->compileFieldChange($issue);
		
		// Grab what we changed
		$changed	= $field->setFieldUpdate($issue);
		
		foreach( $changed as $k => $v )
		{
			$issue[$k] = $new_value;
		}
		
		// Save to database
		$this->registry->tracker->fields()->commitFieldChangeRecords();
		
		if ( $field->returnFormName('db') )
		{
			$this->DB->update( 'tracker_issues',
				array(
					$field->returnFormName('db')	=> $new_value,
					'last_poster_id'				=> $this->memberData['member_id'],
					'last_poster_name'				=> $this->memberData['members_display_name'],
					'last_post'						=> IPS_UNIX_TIME_NOW,
					'last_poster_name_seo'			=> $this->memberData['members_seo_name']
				),
				'issue_id=' . $issue_id
			);
		}
		
		// Mark as read
		$this->registry->classItemMarking->markRead( array( 'forumID' => $issue['project_id'], 'itemID' => $issue['issue_id'] ), 'tracker' );
		
		// Update project cache
		$this->registry->tracker->projects()->update( $issue['project_id'] );
		
		// Return NEW data
		$data = $field->display( $issue, 'issue', 4 );
		
		$this->returnJsonArray($data);
	}
	
	private function reproSave()
	{
		if ( ! intval( $this->request['score'] ) OR ( $this->request['score'] != 1 && $this->request['score'] != -1 ) OR ! isset( $this->request['starter_id'] ) )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
		}
		
		// Same member?
		if ( $this->memberData['member_id'] == intval( $this->request['starter_id'] ) )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'You cannot rate your own issue'));
		}
		
		$score = intval( $this->request['score'] );
		
		// Have we voted before?
		$votedBefore = $this->DB->buildAndFetch(
			array(
				'select'	=> 'rating_id, score',
				'from'		=> 'tracker_ratings',
				'where'		=> "type='issue' AND member_id=" . $this->memberData['member_id'] . " AND issue_id=" . intval( $this->request['iid'] )
			)
		);
		
		if ( $votedBefore['rating_id'] )
		{
			// Check that we can vote again
			if ( $votedBefore['score'] == $score )
			{
				// Remove old vote
				$this->DB->delete( 'tracker_ratings', 'rating_id=' . $votedBefore['rating_id'] );
				
				$weHaveRemoved = 1;
			}
			else
			{
				$weHaveVotedBefore = 1;
				
				$this->DB->update( 'tracker_ratings',
					array(
						'score'	=> $score
					),
					"rating_id=" . $votedBefore['rating_id']
				);
			}
		}
		else
		{
			$this->DB->insert( 'tracker_ratings',
				array(
					'member_id'	=> $this->memberData['member_id'],
					'issue_id'	=> intval( $this->request['iid'] ),
					'type'		=> 'issue',
					'score'		=> $score
				)
			);
		}
		
		// Get current counts
		$issue = $this->DB->buildAndFetch( 
			array(
				'select'	=> 'repro_up, repro_down',
				'from'		=> 'tracker_issues',
				'where'		=> 'issue_id=' . intval( $this->request['iid'] )
			)
		);
		
		// We've done this before
		if ( $weHaveVotedBefore == 1 )
		{
			if ( $score == -1 )
			{
				// We're voting minus, take one off plus
				$repro_up 	= $issue['repro_up'] - 1;
				$repro_down	= $issue['repro_down'] + 1;
			}
			else
			{
				// We're voting plus, take one off minus
				$repro_up 	= $issue['repro_up'] + 1;
				$repro_down	= $issue['repro_down'] - 1;			
			}
		}
		else if ( $weHaveRemoved == 1 )
		{
			if ( $score == -1 )
			{
				$repro_up 	= $issue['repro_up'];
				$repro_down	= $issue['repro_down'] - 1;
			}
			else
			{
				$repro_up	= $issue['repro_up'] - 1;
				$repro_down	= $issue['repro_down'];
			}
		}
		else
		{
			if ( $score == -1 )
			{
				// We're voting minus, DONT take one off plus
				$repro_up 	= $issue['repro_up'];
				$repro_down	= $issue['repro_down'] + 1;
			}
			else
			{
				// We're voting plus, DONT take one off minus
				$repro_up 	= $issue['repro_up'] + 1;
				$repro_down	= $issue['repro_down'];			
			}		
		}
		
		// Now update the main issue
		$this->DB->update( 'tracker_issues',
			array(
				'repro_up'		=> $repro_up,
				'repro_down'	=> $repro_down
			),
			'issue_id=' . intval( $this->request['iid'] )
		);
		
		// Algebra!
		$ratio = 100 / ( ( $repro_up + $repro_down ) > 0 ? ( $repro_up + $repro_down ) : 1 );
		
		$return = array(
			'upCount'	=> $repro_up,
			'downCount'	=> $repro_down,
			'up'		=> $ratio * $repro_up,
			'down'		=> $ratio * $repro_down
		);
		
				
		// Both zero
		if ( $repro_up == 0 && $repro_down == 0 )
		{
			$return['up']	= 50;
			$return['down']	= 50;
		}
		
		// Send back to javascript
		$this->returnJsonArray( $return );
	}
}