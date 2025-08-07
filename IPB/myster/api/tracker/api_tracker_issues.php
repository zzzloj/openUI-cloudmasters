<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* API Files
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	APIs
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );
}

class apiTrackerIssues extends apiCore
{
	/**
	 * Constructor.  Calls parent init() method
	 *
	 * @access		public
	 * @return		void
	 */
	public function __construct()
	{
		/* Set up registry */
		$this->init();
		
		/* Load Tracker libraries */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
		$this->tracker = new $classToLoad();
		$this->tracker->execute( $this->registry );
		
		$this->registry->setClass( 'tracker', $this->tracker );
	}
	
	
	/**
	 * This method will do all the work needed to insert an issue into a project,
	 * and will also update the stats for Tracker, making sure everything is
	 * synchronised. 
	 *
	 * @access		public
	 * @param		integer		Project ID to insert issue into
	 * @param		mixed		A full array of the issue data
	 * @param		mixed		A full array of the post data
	 * @return		mixed		Returns the inserted issue and post IDs
	 */	
	public function insertIssue( $projectID=0, $issue_data=array(), $post_data=array() )
	{
		/* We can't create an issue without a project ID, otherwise it just gets lost in the void, not good! */
		if ( ! $projectID )
		{
			throw new Exception( 'NO_PROJECT_ID' );
		}
		
		$projectID	= intval( $projectID );

		/* Double check project id */
		if ( ! $issue_data['project_id'] )
		{
			$issue_data['project_id'] = $projectID;
		}
		
		/* Do our database magic */		
		$this->DB->insert( 'tracker_issues', $issue_data );
		$issueID	= $this->DB->getInsertId();
		
		$post_data['issue_id'] = $issueID;
		
		$this->DB->insert( 'tracker_posts', $post_data );
		$postID		= $this->DB->getInsertId();
		
		/* Update issue to reflect first post */
		$this->DB->update( 'tracker_issues', array( 'firstpost' => $postID ), 'issue_id=' . $issueID );
	
		// $this->registry->tracker->projects()->rebuild();
		$this->registry->tracker->projects()->update( $projectID );
		
		/* Update stats */
		$this->registry->tracker->cache('stats')->update(0, 1, 1);
		
		/* Return post and issue ID */
		return array( 'post_id' => $postID, 'issue_id' => $issueID );
	}
	
	public function addReplyToIssue( $issueId=0, $reply_data=array() )
	{
		return $this->DB->getInsertId();
	}
}

?>