<?php

/**
* Tracker 2.1.0
* 
* Moderator module
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

class public_tracker_moderate_mod extends iptCommand
{
	private $moderator = array();
	private $project   = array();
	private $issue     = array();

	public function doExecute( ipsRegistry $registry )
	{
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError('no_permission');
		}

		/* Initiate projects */
		$this->project = $this->tracker->projects()->getProject( intval($this->request['pid']) );

		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------

		if ( !$this->project['project_id'] )
		{
			$this->registry->output->showError( 'The project you tried to access does not exist!', '20T105' );
		}

		/* Initiate permissions */
		$this->tracker->moderators()->buildModerators( $this->project['project_id'] );

		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'dolock':
				$this->switchLock('lock');
				break;
				
			case 'dounlock':
				$this->switchLock('unlock');
				break;
				
			case 'dodelissue':
				$this->deleteIssue();
				break;
				
			case 'dodelpost':
				$this->deletePost();
				break;
				
			case 'dodelfeed':
				$this->deleteFeedItem();
				break;
				
			case 'domove':
				$this->doMove();
				break;

			case 'prunestart':
				$this->pruneStart();
				break;
				
			case 'prunefinish':
				$this->pruneFinish();
				break;
				
			case 'prunemove':
				$this->pruneMove();
				break;
				
			default:
				$this->registry->output->showError( 'No action was specified, cannot continue!', '20T106' );
				break;
		}
	}

	/**
	* Delete feed item (2.1+)
	*
	*/	
	private function deleteFeedItem()
	{
		$issue_id	= intval( $this->request['issue_id'] );
		$mid		= intval( $this->request['mid'] );
		$date		= intval( $this->request['date'] );
		$nextDate	= $date + 60;
		
		/* A bit of checking */
		if ( ! $date OR ! $issue_id OR ! $mid )
		{
			$this->registry->output->showError('no_permission');
		}
		
		// Check mod perms
		if ( ! $this->member->tracker[ $this->project['project_id'] ]['can_del_posts'] )
		{
			$this->registry->output->showError('no_permission');
		}
		
		$this->DB->delete( 'tracker_field_changes', "issue_id={$issue_id} AND mid={$mid} AND date>={$date} AND date<{$nextDate}" );
		
		// Rebuild caches
		$this->tracker->projects()->update( $this->project['project_id'] );
		$this->tracker->issues()->rebuildIssue( $issue_id );
		
		// Send back
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&amp;showissue=' . $issue_id . '&amp;st=' . $this->request['st'] );
	}

	/**
	* Delete issue
	*
	*/
	private function deleteIssue()
	{
		$issue_id = intval($this->request['iid']);

		//-------------------------------
		// Got an ID? Load the title
		//-------------------------------
		$issue = $this->DB->buildAndFetch(
			array(
				'select' => '*',
				'from'   => 'tracker_issues',
				'where'  => 'issue_id='.$issue_id
			)
		);

		if ( ! $issue['issue_id'] OR $issue['project_id'] != $this->project['project_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you tried to moderate, or it is in the wrong project', '10T101' );
		}

		$cat_id = $issue['cat_id'];

		//-------------------------------
		// Can we manage?
		//-------------------------------

		if ( ! $this->member->tracker[ $this->project['project_id'] ]['can_del_issues'] )
		{
			$this->registry->output->showError( 'You do not have permission to perform that action', '30T101' );
		}
		
		// grab post ids
		$this->DB->build(
			array(
				'select'	=> 'pid',
				'from'		=> 'tracker_posts',
				'where'		=> 'issue_id=' . $issue_id
			)
		);
		$posts = $this->DB->execute();
		
		// Post ID array for attachments
		$post_ids = array();
		
		while( $post = $this->DB->fetch( $posts ) )
		{
			$post_ids[] = $post['pid'];
		}

		//-------------------------------
		// Delete the topic & replies
		//-------------------------------

		$this->DB->delete( 'tracker_issues', 'issue_id='.$issue_id );
		$this->DB->delete( 'tracker_posts',  'issue_id='.$issue_id );
		$this->DB->delete( 'tracker_ratings', 'issue_id='.$issue_id );
		$this->DB->delete( 'tracker_field_changes', 'issue_id='.$issue_id );
		
		//-----------------------------------------
		// Is there an attachment to this post?
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'tracker';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( $post_ids );

		//-------------------------------
		// Update bug count
		//-------------------------------

		$this->tracker->projects()->update( $this->project['project_id'] );

		//-------------------------------
		// Update statistics
		//-------------------------------

		$this->tracker->cache('stats')->rebuild();

		//-------------------------------
		// Log it
		//-------------------------------

		$this->tracker->moderators()->addLog( "Deleted Issue #{$issue_id}", $issue['title'], $this->project['project_id'], $issue_id, 0 );

		//-------------------------------
		// Redirect
		//-------------------------------

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&amp;showproject='.$this->project['project_id'] );
	}

	/**
	* Delete post
	*
	*/
	private function deletePost()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		$issue_id = intval($this->request['iid']);
		$post_id  = intval($this->request['p']);

		//-------------------------------
		// Got an ID? Load the title
		//-------------------------------
		$issue = $this->DB->buildAndFetch(
			array(
				'select' => '*',
				'from'   => 'tracker_issues',
				'where'  => 'issue_id='.$issue_id
			)
		);

		if ( !$issue['issue_id'] OR $issue['project_id'] != $this->project['project_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you tried to moderate, or it is in the wrong project', '10T101' );
		}

		$cat_id = $issue['cat_id'];

		//-------------------------------
		// Can we manage?
		//-------------------------------
		if ( !$this->member->tracker[ $issue['project_id'] ]['can_del_posts'] )
		{
			$this->registry->output->showError( 'You do not have permission to perform that action', '30T101' );
		}

		//-------------------------------
		// Delete the post
		//-------------------------------
		$this->DB->delete( 'tracker_posts', 'pid='.$post_id );
		
		//-----------------------------------------
		// Is there an attachment to this post?
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'tracker';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( array( 0 => $post_id ) );

		//-------------------------------
		// Update reply count
		//-------------------------------
		$this->tracker->issues()->rebuildIssue( $issue_id );

		//-------------------------------
		// Update statistics
		//-------------------------------
		$this->tracker->cache('stats')->rebuild();

		//-------------------------------
		// Update projects
		//-------------------------------
		$this->tracker->projects()->rebuild();

		//-------------------------------
		// Log it
		//-------------------------------
		$this->tracker->moderators()->addLog( "Deleted Post #{$post_id}", $issue['title'], $this->project['project_id'], $issue_id, $post_id );

		//-------------------------------
		// Redirect
		//-------------------------------
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&amp;showissue='.$issue_id.'&amp;st='.$this->request['st'] );
	}

	/**
	* Lock a buf
	*
	*/
	private function switchLock( $type )
	{
		/* Init vars */
		$issueId = intval($this->request['iid']);
		$issue   = array();

		$issue = $this->DB->buildAndFetch(
			array(
				'select' => '*',
				'from'   => 'tracker_issues',
				'where'  => 'issue_id='.$issueId
			)
		);

		if ( !$issue['issue_id'] OR $issue['project_id'] != $this->project['project_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you tried to moderate, or it is in the wrong project', '10T101' );
		}

		$cat_id = $issue['cat_id'];

		//-------------------------------
		// Can we manage?
		//-------------------------------

		if ( !$this->member->tracker[ $issue['project_id'] ]['can_'.$type] )
		{
			$this->registry->output->showError( 'You do not have permission to perform that action', '30T101' );
		}

		//-------------------------------
		// Update lock
		//-------------------------------
		switch ( $type )
		{
			case 'lock':
				if ( $issue['state'] == 'open' )
				{
					$_state = 'closed';
				}
				else
				{
					$this->registry->output->showError( 'This issue is already locked, and therefore there would be no point in trying to lock it again!', '10T106' );
				}
				break;
			case 'unlock':
				if ( $issue['state'] == 'closed' )
				{
					$_state = 'open';
				}
				else
				{
					$this->registry->output->showError( 'This issue is already open, and therefore there would be no point in trying to open it again!', '10T106' );
				}
				break;
		}

		$this->DB->update( 'tracker_issues', array( 'state' => $_state ), 'issue_id='.$issue['issue_id'] );

		$this->DB->insert(
			'tracker_field_changes',
			array(
				'date' => time(),
				'mid'  => $this->member->getProperty('member_id'),
				'issue_id'    => $issue['issue_id'],
				'module'        => "issue_lock",
				'old_value'   => $issue['state'],
				'new_value'   => $_state
			)
		);
		
		//-------------------------------
		// Log it
		//-------------------------------

		switch( $_state )
		{
			case 'closed':
				$this->tracker->moderators()->addLog( "Locked Issue #{$issue_id}", $issue['title'], $this->project['project_id'], $issue['issue_id'], 0 );
				break;
			case 'open':
				$this->tracker->moderators()->addLog( "Unlocked Issue #{$issue_id}", $issue['title'], $this->project['project_id'], $issue['issue_id'], 0 );
				break;
		}

		//-------------------------------
		// Redirect
		//-------------------------------

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&amp;showissue='.$issue['issue_id'].'&amp;st='.$this->request['st'] );
	}

	/*-------------------------------------------------------------------------*/
	// Change bug status
	/*-------------------------------------------------------------------------*/
	/**
	* Change bug status
	*
	*/
	private function doMove()
	{
		/* Init vars */
		$issueId = intval($this->request['iid']);
		$issue   = array();
		$post    = array();

		//-------------------------------
		// Got an ID? Load the title
		//-------------------------------
		$issue = $this->DB->buildAndFetch(
			array(
				'select' => '*',
				'from'   => 'tracker_issues',
				'where'  => 'issue_id='.$issueId
			)
		);

		if ( !$issue['issue_id'] OR $issue['project_id'] != $this->project['project_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you tried to moderate, or it is in the wrong project', '10T101' );
		}

		//-------------------------------
		// Can we manage?
		//-------------------------------
		if ( !$this->member->tracker[ $issue['project_id'] ]['can_move'] )
		{
			$this->registry->output->showError( 'You do not have permission to perform that action', '30T101' );
		}

		//-------------------------------
		// Get the new stuff
		//-------------------------------
		$newProjectId 	= intval($this->request['new_pid']);
		$newProject 	= $this->tracker->projects()->getProject( $newProjectId );

		//-------------------------------
		// Make sure this stuff is valid
		//-------------------------------
		if ( !$newProject['project_id'] )
		{
			$this->registry->output->showError( 'The project you tried to move the issue to does not exist!', '10T107' );
		}

		//-------------------------------
		// Start off array
		//-------------------------------
		$updateIssue = array( 'project_id' => $newProjectId );

		//-------------------------------
		// Add reply?
		//-------------------------------

		# Make an option?
		if ( 1 == 1 )
		{
			if ( $this->project['project_id'] == $newProjectId )
			{
				$this->registry->output->showError( "You can't move the issue in the same project, please select a different project!", '10T108' );
			}
			
			/* Track change in DB */
			$this->DB->insert(
				'tracker_field_changes',
				array(
					'date' 				=> time(),
					'mid'  				=> $this->memberData['member_id'],
					'issue_id'   		=> $issue['issue_id'],
					'module'        	=> "project",
					'old_value'   		=> $this->project['project_id'],
					'new_value'   		=> $newProjectId
				)
			);

			/* Load language file */
			$this->registry->class_localization->loadLanguageFile( array( 'public_issue' ), 'tracker' );

			//-----------------------------------------
			// Update stats cache
			//-----------------------------------------
			$this->tracker->cache('stats')->updatePosts(1);

			/* Update Issue Data */
			$updateIssue['last_poster_id']   = $this->memberData['member_id'];
			$updateIssue['last_poster_name'] = $this->memberData['members_display_name'];
			$updateIssue['last_post']        = time();
			
			// Modules to update?
			// @todo 2.1 - make this into an extension so all modules can update data before issue is moved
			if ( $this->tracker->modules()->moduleIsInstalled('versions') )
			{
				$updateIssue['module_versions_reported_id'] = 0;
				$updateIssue['module_versions_fixed_id']	= 0;
			}
		}

		//-------------------------------
		// Update Issues
		//-------------------------------
		$this->DB->update( 'tracker_issues', $updateIssue, 'issue_id='.$issue['issue_id'] );

		//-------------------------------
		// Log it
		//-------------------------------
		$this->tracker->moderators()->addLog( "Moved Issue #{$issue['issue_id']}", $issue['title'], $newProjectId, $issue['issue_id'], 0 );

		//-------------------------------
		// Rebuild
		//-------------------------------
		$this->tracker->projects()->update( $this->project['project_id'] );
		$this->tracker->projects()->update( $newProjectId );
		
		// Mark it as read, as we've already read it!
		$this->registry->classItemMarking->markRead( array( 'forumID' => $newProjectId, 'itemID' => $issue['issue_id'] ), 'tracker' );

		//-------------------------------
		// Redirect
		//-------------------------------
		$this->registry->output->silentRedirect( $this->settings['base_url'].'app=tracker&amp;showproject='.$this->project['project_id'] );
	}
}

?>