<?php

/**
* Tracker 2.1.0
*
* AJAX Mark project read
* Last Updated: $Date: 2012-05-28 01:07:41 +0100 (Mon, 28 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1371 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_ajax_markProjectRead extends ipsAjaxCommand
{
	/**
	 * IPS command execution
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Ensure cookies are sent */
		$this->settings['no_print_header'] = 0;

		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch( $this->request['marktype'] )
		{
			default:
			case 'project':
				return $this->markProjectAsRead();
			break;
		}
	}

	/**
	 * Marks the specified forum as read
	 *
	 * @return	@e void
	 */
	public function markProjectAsRead()
	{
		$projectID	= intval( $this->request['pid'] );

		/* Project has children? */
		$children = $this->registry->tracker->projects()->getChildren( $projectID );

		/* Update each child as read */
		if ( count( $children ) != 0 )
		{
			foreach( $children as $v )
			{
				$this->registry->classItemMarking->markRead( array( 'forumID' => $v['project_id'] ) );
			}
		}

		/* Update the main one */
		$this->registry->classItemMarking->markRead( array( 'forumID' => $projectID ) );

		/* Turn off instant updates and write back tmp markers in destructor */
		$this->registry->classItemMarking->disableInstantSave();

		$this->returnJsonArray( array( 'result' => 'success') );
	}
}