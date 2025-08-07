<?php

/**
* Tracker 2.1.0
* 
* Mark Projects as Read module
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

class public_tracker_projects_markProjectsRead extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
		// CSRF Key
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'You do not have permission to carry out this action' );
		}
		
		// Mark the whole tracker read
		if ( $this->request['markWholeTracker'] == 1 )
		{
			$this->registry->classItemMarking->markAppAsRead( 'tracker' );
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=tracker' );
		}
		
		if ( ! $this->request['pid'] )
		{
			if ( $this->request['fromProject'] )
			{
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=tracker&showproject=' . $this->request['fromProject'] );
			}
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=tracker' );
		}
		
		$projectID	= intval( $this->request['pid'] );
		
		/* Project has children? */
		$children = $this->registry->tracker->projects()->getChildren( $projectID );

		/* Update each child as read */
		if ( count( $children ) != 0 )
		{
			foreach( $children as $k => $v )
			{
				$this->registry->classItemMarking->markRead( array( 'forumID' => $v['project_id'] ) );
			}
		}
		
		/* Update the main one */
		$this->registry->classItemMarking->markRead( array( 'forumID' => $projectID ) );
		
		/* Redirect back to where we came from */
		if ( $this->request['fromProject'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=tracker&showproject=' . $this->request['fromProject'] );
		}
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=tracker' );		
	}
}

?>