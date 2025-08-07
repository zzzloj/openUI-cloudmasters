<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_form extends class_attach
{
	public $module = 'form';
	
	public function getAttachmentData( $attach_id )
	{
	    if ( !$this->memberData['g_fs_view_logs'] )
	    {
			return FALSE;
		}
        
		$this->DB->build( array( 'select'   => 'a.*', 'from' => array( 'attachments' => 'a' ), 'where'    => "a.attach_rel_module='".$this->module."' AND a.attach_id=".$attach_id,	 'add_join' => array( 0 => array( 'select' => 'l.log_id',  'from'   => array( 'form_logs' => 'l' ),  'where'  => "l.log_id=a.attach_rel_id",	  'type'   => 'left' ) )	)      );
		$attach_sql = $this->DB->execute();		
		$attach     = $this->DB->fetch( $attach_sql );
		
		if ( ! isset( $attach['attach_id'] ) || ! $attach['attach_id'] )
		{
			return FALSE;
		}	
		else
		{
			return $attach;
		}
	}
	
	/**
	 * Check the attachment and make sure its OK to display
	 *
	 * @access	public
	 * @param	array		Array of ids
	 * @param	array 		Array of relationship ids
	 * @return	array 		Attachment data
	 */
	public function renderAttachment( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
	    if ( !$this->memberData['g_fs_view_logs'] )
	    {
			return FALSE;
		}		
		
		$rows  = array();
		$query = '';

		if ( ! is_array( $attach_ids ) OR ! count( $attach_ids ) )
		{
			$attach_ids = array( -2 );
		}		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$query = " OR attach_rel_id IN (-1," . implode( ",", $rel_ids ) . ")";
		}
		
		$this->DB->build( array( 'select'   => '*',	 'from'     => 'attachments', 'where'    => "attach_rel_module='".$this->module."' AND ( attach_id IN(-1,". implode( ",", $attach_ids ) . ") " . $query . " )",	)      );
		$attach_sql = $this->DB->execute();
	
		while( $db_row = $this->DB->fetch( $attach_sql ) )
		{
			$_ok = 1;
			
			if ( $_ok )
			{
				$rows[ $db_row['attach_id'] ] = $db_row;
			}
		}

		return $rows;
	}
	
	/**
	 * Recounts number of attachments for the articles row
	 *
	 * @access	public
	 * @param	string		Post key
	 * @param	integer		Related ID
	 * @param	array 		Arguments for query
	 * @return	array 		Returns count of items found
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
		
	    if ( !$this->memberData['g_fs_allow_attach'] )
	    {
			return 0;
		}		

		$this->DB->build( array( "select" => 'COUNT(*) as cnt',  'from'   => 'attachments',  'where'  => "attach_rel_module='".$this->module."' AND attach_post_key='{$post_key}'") );
		$this->DB->execute();	
		$cnt = $this->DB->fetch();
				
		return array( 'count' => $cnt['cnt'] );
	}
	
	/**
	 * Recounts number of attachments for the articles row
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function attachmentRemovalCleanup( $attachment )
	{
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		$this->DB->build( array( 'select'   => 'log_id', 'from' => 'form_logs', 'where'    => 'log_id='. intval( $attachment['attach_rel_id'] ) ) 	);
		$this->DB->execute();
		$entry = $this->DB->fetch();
	
		if ( isset( $entry['log_id'] ) )
		{
			$this->DB->build( array( "select" => 'count(*) as cnt', 'from'   => 'attachments', 'where'  => "attach_rel_module='form' AND attach_rel_id = ".$entry['log_id'] ) );
			$this->DB->execute();
		
			$cnt = $this->DB->fetch();		
			$count = intval( $cnt['cnt'] );
		}
		
		return TRUE;
	}
	
	/**
	 * Determines if you have permission for bulk attachment removal
	 * Returns TRUE or FALSE
	 * IT really does
	 *
	 * @access	public
	 * @param	array 		Ids to check against
	 * @return	boolean
	 */
	public function canBulkRemove( $attach_rel_ids=array() )
	{
		$ok_to_remove = FALSE;
		
	    if( $this->memberData['g_fs_moderate_logs'] )
	    {
			$ok_to_remove = TRUE;
		}

		return $ok_to_remove;
	}
	
	/**
	 * Determines if you can remove this attachment
	 * Returns TRUE or FALSE
	 * IT really does
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function canRemove( $attachment )
	{
		$ok_to_remove = FALSE;
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
	    if( $this->memberData['g_fs_moderate_logs'] )
	    {
			$ok_to_remove = TRUE;
		}
		
		return $ok_to_remove;
	}
	
	/**
	 * Returns an array of the allowed upload sizes in bytes.
	 * Return 'space_allowed' as -1 to not allow uploads.
	 * Return 'space_allowed' as 0 to allow unlimited uploads
	 * Return 'max_single_upload' as 0 to not set a limit
	 *
	 * @access	public
	 * @param	string		MD5 post key
	 * @param	id			Member ID
	 * @return	array 		[ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	 */
	public function getSpaceAllowance( $post_key='', $member_id='' )
	{
		$max_php_size      = intval( IPSLib::getMaxPostSize() );
		$member_id         = intval( $member_id ? $member_id : $this->memberData['member_id'] );
		$space_left        = 0;
		$space_used        = 0;
		$space_allowed     = 0;
		$max_single_upload = 0;
		
		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------
		
		if ( !$this->memberData['g_fs_allow_attach'] )
		{
			$space_allowed = -1;
		}
		else
		{
			//-----------------------------------------
			// Generate total space allowed
			//-----------------------------------------

			$total_space_allowed = ( $this->memberData['g_attach_per_post'] ? $this->memberData['g_attach_per_post'] : $this->memberData['g_attach_max'] ) * 1024;
			
			//-----------------------------------------
			// Generate space used figure
			//-----------------------------------------
			
			if ( $this->memberData['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------
				
				$_space_used = $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure',
																'from'   => 'attachments',
																'where'  => "attach_post_key='".$post_key."'" ) );

				$space_used    = intval( $_space_used['figure'] );
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------
				
				$_space_used = $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure',
																'from'   => 'attachments',
																'where'  => 'attach_member_id='.$member_id . " AND attach_rel_module IN( 'post', 'msg', 'form' )" ) );

				$space_used    = intval( $_space_used['figure'] );
			}	
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------
		
			if ( $this->memberData['g_attach_max'] > 0 )
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$_g_space_used	= $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure',
																	   'from'   => 'attachments',
																	   'where'  => 'attach_member_id='.$member_id . " AND attach_rel_module IN( 'post', 'msg', 'form' )" ) );

					$g_space_used    = intval( $_g_space_used['figure'] );
					
					if( intval( ( $this->memberData['g_attach_max'] * 1024 ) - $g_space_used ) < 0 )
					{
						$space_used    			= $g_space_used;
						$total_space_allowed	= $this->memberData['g_attach_max'] * 1024;
						
						$space_allowed = ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
						$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
					}
					else
					{
						$space_allowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
						$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
					}
				}
				else
				{
					$space_allowed = ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
			}
			else
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$space_allowed = ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{ 
					# Unlimited
					$space_allowed = 0;
				}
			}
			
			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------
			
			$space_left = $space_allowed ? $space_allowed : 0;
			$space_left = ($space_left < 0) ? -1 : $space_left;
			
			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------
			
			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload = $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload = $max_php_size;
				}
			}
		}

		IPSDebug::fireBug( 'info', array( 'Space left: ' . $space_left ) );
		IPSDebug::fireBug( 'info', array( 'Max PHP size: ' . $max_php_size ) );
		IPSDebug::fireBug( 'info', array( 'Max single file size: ' . $max_single_upload ) );
		
		$return = array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );
		
		return $return;
	}
	
	/**
	 * Returns an array of settings:
	 * 'siu_thumb' = Allow thumbnail creation?
	 * 'siu_height' = Height of the generated thumbnail in pixels
	 * 'siu_width' = Width of the generated thumbnail in pixels
	 * 'upload_dir' = Base upload directory (must be a full path)
	 *
	 * You can omit any of these settings and IPB will use the default
	 * settings (which are the ones entered into the ACP for post thumbnails)
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function getSettings()
	{
		$this->mysettings = array();
		
		return true;
	}

}