<?php

/**
* Tracker 2.1.0
* 
* Attachment extension handler
* Last Updated: $Date: 2012-12-16 12:38:12 +0000 (Sun, 16 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Extensions
* @link			http://ipbtracker.com
* @version		$Revision: 1398 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_tracker extends class_attach
{
	/**
	* Module type
	* @var	string
	*/
	public $module = 'tracker';
	
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		/* Load and init forums */
		if( ipsRegistry::isClassLoaded('tracker') !== TRUE )
		{
			try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
				$this->tracker = new $classToLoad();
				$this->tracker->execute( $this->registry );
				
				$this->registry->setClass( 'tracker', $this->tracker );
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
	}

	/**
	* get_settings
	*
	* Returns an array of settings:
	* 'siu_thumb' = Allow thumbnail creation?
	* 'siu_height' = Height of the generated thumbnail in pixels
	* 'siu_width' = Width of the generated thumbnail in pixels
	* 'upload_dir' = Base upload directory (must be a full path)
	*
	* You can omit any of these settings and IPB will use the default
	* settings (which are the ones entered into the ACP for post thumbnails)
	*
	* @return boolean
	*/
	public function getSettings()
	{
		$this->mysettings = array();

		return true;
	}

	/**
	* Checks the attachment and checks for download / show perms
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	public function getAttachmentData( $attach_id )
	{
		$_ok = 0;

		if( !$attach_id )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'   => 'a.*',
				'from'     => array( 'attachments' => 'a' ),
				'where'    => "a.attach_rel_module='".$this->module."' AND a.attach_id=".$attach_id,
				'add_join' => array(
					0 => array(
						'select' => 'p.pid, p.issue_id',
						'from'   => array( 'tracker_posts' => 'p' ),
						'where'  => "p.pid=a.attach_rel_id",
						'type'   => 'left'
					),
					1 => array(
						'select' => 't.issue_id',
						'from'   => array( 'tracker_issues' => 't' ),
						'where'  => "t.issue_id=p.issue_id",
						'type'   => 'left'
					),
					2 => array(
						'select' => 'pj.*',
						'from'   => array( 'tracker_projects' => 'pj' ),
						'where'  => "pj.project_id = t.project_id",
						'type'   => "left"
					)
				)
			)
		);

		$attach_sql = $this->DB->execute();
		$attach     = $this->DB->fetch( $attach_sql );

		//-----------------------------------------
		// Check..
		//-----------------------------------------

		if ( ! isset( $attach['pid'] ) OR empty( $attach['pid'] ) )
		{
			if( $attach['attach_member_id'] != $this->memberData['member_id'] )
			{
				return FALSE;
			}
		}

		//-----------------------------------------
		// TheWalrus inspired fix for previewing
		// the post and clicking the attachment...
		//-----------------------------------------

		if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->memberData['member_id'] )
		{
			$_ok = 1;
		}
		else
		{
			if ( ! $attach['project_id'] )
			{
				//-----------------------------------------
				// TheWalrus inspired fix for previewing
				// the post and clicking the attachment...
				//-----------------------------------------

				if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->memberData['member_id'] )
				{
					# We're ok.
				}
				else
				{
					return FALSE;
				}
			}

			if ( $this->registry->tracker->projects()->checkPermission( 'read', $attach['project_id'] ) === FALSE )
			{
				return FALSE;
			}

			if ( $this->registry->tracker->projects()->checkPermission( 'download', $attach['project_id'] ) === FALSE )
			{
				return FALSE;
			}

			//-----------------------------------------
			// Still here?
			//-----------------------------------------

			$_ok = 1;
		}

		//-----------------------------------------
		// Ok?
		//-----------------------------------------

		if ( $_ok )
		{
			return $attach;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	* Check the attachment and make sure its OK to display
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	public function renderAttachment( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$rows       = array();
		$query_bits = array();
		$query      = '';
		$match      = 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			$query_bits[] = "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}

		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$query_bits[] = "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			$match = 1;
		}

		if ( $attach_post_key )
		{
			$query_bits[] = "attach_post_key='".$this->DB->addSlashes( $attach_post_key )."'";
			$match = 2;
		}

		if( !count($query_bits) )
		{
			$query = "attach_id IN (-1)";
		}
		else
		{
			$query = implode( " OR ", $query_bits );
		}

		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'   => '*',
				'from'     => 'attachments',
				'where'    => "attach_rel_module='".$this->module."' AND ( " . $query . " )",
			)
		);
		$attach_sql = $this->DB->execute();

		//-----------------------------------------
		// Loop through and filter off naughty ids
		//-----------------------------------------

		while( $db_row = $this->DB->fetch( $attach_sql ) )
		{
			$_ok = 1;

			if ( $match == 1 )
			{
				if ( ! in_array( $db_row['attach_rel_id'], $rel_ids ) )
				{
					$_ok = 0;
				}
			}
			else if ( $match == 2 )
			{
				if ( $db_row['attach_post_key'] != $attach_post_key )
				{
					$_ok = 0;
				}
			}

			//-----------------------------------------
			// Ok?
			//-----------------------------------------

			if ( $_ok )
			{
				$rows[ $db_row['attach_id'] ] = $db_row;
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $rows;
	}

	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	public function postUploadProcess( $post_key, $rel_id, $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cnt = array( 'cnt' => 0 );

		//-----------------------------------------
		// Check..
		//-----------------------------------------

		if ( ! $post_key )
		{
			return 0;
		}

		$this->DB->build(
			array(
				'select' => 'COUNT(*) as cnt',
				'from'   => 'attachments',
				'where'  => "attach_post_key='{$post_key}'"
			)
		);
		$this->DB->execute();

		$cnt = $this->DB->fetch();

		if ( $cnt['cnt'] )
		{
			$this->DB->buildAndFetch(
				array(
					'update' => 'tracker_issues',
					'set'    => "hasattach=hasattach+" . $cnt['cnt'],
					'where'  => "issue_id=" . intval( $args['issue_id'] )
				)
			);
		}

		return array( 'count' => $cnt['cnt'] );
	}

	/*-------------------------------------------------------------------------*/
	// Remove attachment clean up
	/*-------------------------------------------------------------------------*/
	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	public function attachmentRemovalCleanup( $attachment )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}

		$this->DB->build(
			array(
				'select'   => 'p.pid',
				'from'     => array( 'tracker_posts' => 'p' ),
				'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
				'add_join' => array(
					0 => array(
						'select' => 't.project_id, t.issue_id',
						'from'   => array( 'tracker_issues' => 't' ),
						'where'  => 't.issue_id=p.issue_id',
						'type'   => 'inner'
					)
				)
			)
		);
		$this->DB->execute();

		$topic = $this->DB->fetch();

		if ( isset( $topic['issue_id'] ) )
		{
			//-----------------------------------------
			// GET PIDS
			//-----------------------------------------

			$pids  = array();
			$count = 0;

			$this->DB->build(
				array(
					'select' => 'pid',
					'from'   => 'tracker_posts',
					'where'  => "issue_id=". $topic['issue_id']
				)
			);
			$this->DB->execute();

			while ( $p = $this->DB->fetch() )
			{
				$pids[] = $p['pid'];
			}

			//-----------------------------------------
			// GET ATTACHMENT COUNT
			//-----------------------------------------

			if ( count( $pids ) )
			{
				$this->DB->build(
					array(
						"select" => 'count(*) as cnt',
						'from'   => 'attachments',
						'where'  => "attach_rel_module='tracker' AND attach_rel_id IN(".implode(",",$pids).")"
					)
				);
				$this->DB->execute();

				$cnt = $this->DB->fetch();

				$count = intval( $cnt['cnt'] );
			}

			$this->DB->build( array( 'update' => 'tracker_issues', 'set' => "hasattach=". $count , 'where' => "issue_id=".$topic['issue_id'] ) );
			$this->DB->execute();
		}

		return TRUE;
	}

	/**
	* Bulk remove attachment perms check
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	public function canBulkRemove( $attach_rel_ids=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ok_to_remove = FALSE;

		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------

		if ( $this->memberData['g_access_cp'] )
		{
			$ok_to_remove = TRUE;
		}

		return $ok_to_remove;
	}

	/**
	* Remove attachment perms check
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	public function canRemove( $attachment )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$ok_to_remove = FALSE;

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------

		if ( $this->memberData['member_id'] == $attachment['attach_member_id'] )
		{
			$ok_to_remove = TRUE;
		}
		else if ( $this->member->tracker['can_edit_posts'] )
		{
			$ok_to_remove = TRUE;
		}

		return $ok_to_remove;
	}

	/**
	* get_space_allowance
	*
	* Returns an array of the allowed upload sizes in bytes.
	* Return 'space_allowed' as -1 to not allow uploads.
	* Return 'space_allowed' as 0 to allow unlimited uploads
	* Return 'max_single_upload' as 0 to not set a limit
	*
	* @param	id	    Member ID
	* @param	string  MD5 post key
	* @return	array [ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	*/
	public function getSpaceAllowance( $postKey='', $memberId='' )
	{
		$maxPhpSize			= IPSLib::getMaxPostSize();
		$memberId			= intval( $memberId ? $memberId : $this->memberData['member_id'] );
		$projectId			= intval( $this->request['forum_id'] ? $this->request['forum_id'] : $this->request['project_id'] );
		$projectId			= intval( !$projectId && isset($this->request['pid']) ? $this->request['pid'] : $projectId );
		$spaceLeft			= 0;
		$spaceUsed			= 0;
		$spaceAllowed		= 0;
		$maxSingleUpload	= 0;
		$spaceCalculated	= 0;

		if ( $postKey )
		{
			//-----------------------------------------
			// Check to make sure we're not attempting
			// to upload to another's post...
			//-----------------------------------------

			if ( ! $this->memberData['g_is_supmod'] AND !$this->memberData['is_mod'] )
			{
				$post = $this->DB->buildAndFetch(
					array(
						'select' => '*',
						'from'   => 'tracker_posts',
						'where'  => "post_key='".$postKey."'"
					)
				);

				if ( $post['post_key'] AND ( $post['author_id'] != $memberId ) )
				{
					$spaceAllowed    = -1;
					$spaceCalculated = 1;
				}
			}
		}

		//-----------------------------------------
		// Generate total space allowed
		//-----------------------------------------

		$totalSpaceAllowed = ( $this->memberData['g_attach_per_post'] ? $this->memberData['g_attach_per_post'] : $this->memberData['g_attach_max'] ) * 1024;

		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------

		if ( ! $memberId OR ! $projectId )
		{
			$spaceAllowed = -1;
		}

		if ( $this->registry->tracker->projects()->checkPermission( 'upload', $projectId ) !== TRUE )
		{
			$spaceAllowed = -1;
		}
		else if ( ! $spaceCalculated )
		{
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------

			if ( $this->memberData['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------

				$_spaceUsed = $this->DB->buildAndFetch( array(
																'select' => 'SUM(attach_filesize) as figure',
																'from'   => 'attachments',
																'where'  => "attach_post_key='{$postKey}'"
														) );

				$spaceUsed = $_spaceUsed['figure'] ? $_spaceUsed['figure'] : 0;
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------

				$_spaceUsed = $this->DB->buildAndFetch( array(
																'select' => 'SUM(attach_filesize) as figure',
																'from'   => 'attachments',
																'where'  => "attach_member_id={$memberId} AND attach_rel_module = 'tracker'"
														)	);

				$spaceUsed    = $_spaceUsed['figure'] ? $_spaceUsed['figure'] : 0;
			}

			if ( $this->memberData['g_attach_max'] > 0 )
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$_gSpaceUsed	= $this->DB->buildAndFetch( array(
																		'select' => 'SUM(attach_filesize) as figure',
																		'from'   => 'attachments',
																		'where'  => "attach_member_id={$memberId} AND attach_rel_module = 'tracker'"
															)	 );

					$gSpaceUsed    = $_gSpaceUsed['figure'] ? $_gSpaceUsed['figure'] : 0;

					if( ( $this->memberData['g_attach_max'] * 1024 ) - $gSpaceUsed < 0 )
					{
						$spaceUsed    			= $gSpaceUsed;
						$totalSpaceAllowed	= $this->memberData['g_attach_max'] * 1024;

						$spaceAllowed = ( $this->memberData['g_attach_max'] * 1024 ) - $spaceUsed;
						$spaceAllowed = $spaceAllowed < 0 ? -1 : $spaceAllowed;
					}
					else
					{
						$spaceAllowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $spaceUsed;
						$spaceAllowed = $spaceAllowed < 0 ? -1 : $spaceAllowed;
					}
				}
				else
				{
					$spaceAllowed = ( $this->memberData['g_attach_max'] * 1024 ) - $spaceUsed;
					$spaceAllowed = $spaceAllowed < 0 ? -1 : $spaceAllowed;
				}
			}
			else
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$spaceAllowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $spaceUsed;
					$spaceAllowed = $spaceAllowed < 0 ? -1 : $spaceAllowed;
				}
				else
				{
					# Unlimited
					$spaceAllowed = 0;
				}
			}

			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------

			$spaceLeft = $spaceAllowed ? $spaceAllowed : 0;
			$spaceLeft = ($spaceLeft < 0) ? -1 : $spaceLeft;

			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------

			if ( ! $maxSingleUpload )
			{
				if ( $spaceLeft > 0 AND $spaceLeft < $maxPhpSize )
				{
					$maxSingleUpload = $spaceLeft;
				}
				else if ( $maxPhpSize )
				{
					$maxSingleUpload = $maxPhpSize;
				}
			}
		}

		IPSDebug::fireBug( 'info', array( 'Space left: ' . $spaceLeft ) );
		IPSDebug::fireBug( 'info', array( 'Max PHP size: ' . $maxPhpSize ) );
		IPSDebug::fireBug( 'info', array( 'Max single file size: ' . $maxSingleUpload ) );

		$return = array( 'space_used' => $spaceUsed, 'space_left' => $spaceLeft, 'space_allowed' => $spaceAllowed, 'max_single_upload' => $maxSingleUpload, 'total_space_allowed' => $totalSpaceAllowed );

		return $return;
	}
}

?>