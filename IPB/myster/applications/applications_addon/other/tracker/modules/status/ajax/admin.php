<?php

/**
* Tracker 2.1.0
*	- IPS Community Project Developers
*		- Javascript written by Alex Hobbs
* 
* Status Admin AJAX-PHP Interface
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2009 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Status
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: AJAX
 * Module: Status
 * Field: Status
 * Status field admin AJAX processor
 * 
 * @package Tracker
 * @subpackage Module-Status
 * @since 2.0.0
 */
class admin_tracker_module_status_ajax_admin extends ipsAjaxCommand 
{
	/**
	 * Initial function.  Called by execute function in iptCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'recache':
				$this->recache();
				break;
			case 'reorder':
				$this->reorder();
				break;
			case 'delete':
				$this->delete();
				break;
			case 'edit':
				$this->save('edit');
				break;
			case 'add':
				$this->save('add');
				break;
			case 'default':
				$this->setDefault();
				break;
			case 'load':
				$this->load();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}

	/**
	 * Deletes a status and moves issues to a different status
	 *
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */
	private function delete()
	{
		if ( ! $this->request['id'] || intval( $this->request['id'] ) != $this->request['id'] || ! $this->request['move'] || intval( $this->request['move'] ) != $this->request['move'] )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}

		/* Move existing issues */
		$this->DB->update( 'tracker_issues', array( 'module_status_id' => $this->request['move'] ), 'module_status_id=' . $this->request['id'] );

		/* Delete status */
		$this->DB->delete( 'tracker_module_status', 'status_id=' . $this->request['id'] );

		/* Caches */
		$this->registry->tracker->cache('status','status')->rebuild();
		$this->registry->tracker->projects()->rebuild();

		$this->returnJsonArray( array( 'result' => 'success' ) );
	}

	/**
	 * Generates the delete screen
	 *
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */
	private function load()
	{
		if ( ! $this->request['id'] || intval( $this->request['id'] ) != $this->request['id'] )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}

		$status = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'tracker_module_status', 'where' => 'status_id=' . $this->request['id'] ) );
		
		$status['reply_text'] = IPSText::getTextClass('bbcode')->preEditParse( $status['reply_text'] );

		if ( $this->request['dropdown'] )
		{
			$status['options'] = '';

			$this->DB->build( array( 'select' => 'status_id, title', 'from' => 'tracker_module_status', 'where' => 'status_id!=' . $this->request['id'] ) );
			$this->DB->execute();

			if ( $this->DB->getTotalRows() )
			{
				while( $s = $this->DB->fetch() )
				{
					$status['options'] .= "<option value='{$s['status_id']}'>{$s['title']}</option>";
				}
			}

			/* Existing issues? */
			$issue = $this->DB->buildAndFetch( array( 'select' => 'module_status_id', 'from' => 'tracker_issues', 'where' => 'module_status_id=' . $this->request['id'] ) );

			if ( $issue['module_status_id'] )
			{
				$status['text'] = 'All existing issues categorised under this status will need moving, please select a status to move these issues into.';
			}
			else
			{
				$status['text'] = 'Please confirm that you wish to delete this status.';
				$status['type'] = 'no_issues';
			}
		}

		$this->returnJsonArray( $status );
	}

	/**
	 * Calls for the status cache to be rebuilt
	 *
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */
	private function recache()
	{
		$this->registry->tracker->cache('status','status')->rebuild();
		$this->returnJsonArray( array( 'result'=>'success' ) );
	}

	/**
	 * Saves the order of the statuses
	 *
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */
	private function reorder()
	{
		if ( is_array( $this->request['status'] ) && count( $this->request['status'] ) > 0 )
		{
			foreach( $this->request['status'] as $position => $id )
			{
				$this->DB->update( 'tracker_module_status', array( 'position' => $position ), 'status_id=' . $id );
			}

			$this->returnJsonArray( array( 'result'=>'success' ) );
		}
		else
		{
			$this->returnJsonArray( array( 'error'=>'true' ) );
		}
	}

	/**
	 * Saves a status
	 *
	 * @param string [add|edit] the type of save
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */
	private function save( $type )
	{
		$data = json_decode( $_POST['data'], TRUE );

		/* Check we have required data */
		if ( ! is_array( $data ) || ! isset( $data['title'] ) || ! isset( $data['allow_new'] ) || ! isset( $data['closed'] ) )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}

		/* Check we don't have conflicting data */
		if ( $data['closed'] == 1 && $data['allow_new'] == 1 )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}

		/* Check no tampering */
		if ( intval( $data['closed'] ) != $data['closed'] || intval( $data['allow_new'] ) != $data['allow_new'] )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}

		if ( $type == 'edit' )
		{
			$statusID     = intval( $data['id'] );
			$status = $this->DB->buildAndFetch( array( 'select' => 'status_id', 'from' => 'tracker_module_status', 'where' => 'status_id=' . $statusID ) );

			/* Does it exist? */
			if ( ! $status['status_id'] )
			{
				$this->returnJsonArray( array( 'error' => 'true' ) );
			}
		}
		
		$data['reply_text'] = IPSText::getTextClass('bbcode')->preDbParse( $_POST['Post'] );

		if ( $type == 'edit' )
		{
			/* Update data */
			unset( $data['id'] );
			$this->DB->update( 'tracker_module_status', $data, 'status_id=' . $statusID );
		}
		else if ( $type == 'add' )
		{
			/* First status? */
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'tracker_module_status' ) );

			if ( !$count['count'] )
			{
				$default = 1;
			}
			else
			{
				$default = 0;
			}

			/* Insert into database */
			$this->DB->insert(
				'tracker_module_status',
				array(
					'title'        => $data['title'],
					'allow_new'    => intval( $data['allow_new'] ),
					'closed'       => intval( $data['closed'] ),
					'position'     => intval( $count['count']+1 ),
					'reply_text'   => $data['reply_text'],
					'default_open' => $default
				)
			);

			$statusID = $this->DB->getInsertId();
		}

		$autoreply = 'No';
		$closed    = 'No';

		if ( $data['reply_text'] != '' )
		{
			$autoreply = "<span style='color:green;'>Yes</span>";
		}
		
		if ( $data['closed'] )
		{
			$closed    = "<span style='color:green;'>Yes</span>";
		}

		/* Recache */
		$this->registry->tracker->cache('status','status')->rebuild();
		$this->returnJsonArray( array( 'statusID' => $statusID, 'autoreply' => $autoreply, 'closed' => $closed ) );
	}

	/**
	 * Sets the default status
	 *
	 * @return void [JSON array output]
	 * @access private
	 * @since 2.0.0
	 */	
	private function setDefault()
	{
		if ( ! $this->request['id'] || intval( $this->request['id'] ) != $this->request['id'] )
		{
			$this->returnJsonArray( array( 'error'=>'true' ) );
		}
		
		$this->DB->update( 'tracker_module_status', array( 'default_open' => 0 ), 'default_open=1' );
		$this->DB->update( 'tracker_module_status', array( 'default_open' => 1 ), 'status_id=' . $this->request['id'] );
		
		$this->registry->tracker->cache('status','status')->rebuild();
		$this->returnJsonArray( array( 'result'=>'success' ) );
	}
}

?>