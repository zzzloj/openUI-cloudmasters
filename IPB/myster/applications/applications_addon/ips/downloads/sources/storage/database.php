<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IDM database file storage handling
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class databaseStorageEngine extends storageEngine implements interface_storage
{
	/**
	 * Remove a file
	 *
	 * @access	public
	 * @param	array		Record data
	 * @return	boolean		File removed successfully
	 */	
	public function remove( $record )
	{
		$this->DB->delete( "downloads_filestorage", "storage_id=" . $record['record_db_id'] );
		$this->DB->optimize( "downloads_filestorage" );
		
		return true;
	}

	/**
	 * Stores the uploaded files
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	int			Error number
	 */	
	public function store( $data=array() )
	{
		//-----------------------------------------
		// Get all the temp records
		//-----------------------------------------
		
		$_where	= $this->type == 'file' ? 'files' : 'ss';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'downloads_temp_records', 'where' => "record_type='{$_where}' AND record_post_key='{$data['post_key']}'" ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-----------------------------------------
			// Insert into filestorage
			//-----------------------------------------
			
			$this->DB->insert( "downloads_filestorage", array(  'storage_file'	=> $r['record_type'] == 'ss' ? null : base64_encode( file_get_contents( $this->file_path . "/" . $r['record_location'] ) ),
																'storage_ss'	=> $r['record_type'] == 'ss' ? base64_encode( file_get_contents( $this->image_path . "/" . $r['record_location'] ) ) : null,
																'storage_thumb'	=> null
							)			);
			
			$_insertId	= $this->DB->getInsertId();
			
			//-----------------------------------------
			// Set the new details
			//-----------------------------------------
			
			$this->details[]	= array(
										'record_post_key'		=> $r['record_post_key'],
										'record_file_id'		=> $data['file_id'],
										'record_type'			=> $r['record_type'] == 'ss' ? 'ssupload' : 'upload',
										'record_location'		=> $r['record_location'],
										'record_db_id'			=> $_insertId,
										'record_thumb'			=> '',
										'record_storagetype'	=> $this->settings['idm_filestorage'],
										'record_realname'		=> $r['record_realname'],
										'record_link_type'		=> '',
										'record_mime'			=> $r['record_mime'],
										'record_size'			=> $r['record_size'],
										'record_backup'			=> 0,
										'record_default'		=> ( $r['record_type'] == 'ss' AND $r['record_id'] == $this->primaryScreenshot ) ? 1 : 0,
										);
		}

		return 0;
		
	}
	
	/**
	 * Undo stored files
	 *
	 * @access	public
	 * @return	bool		Rollback complete
	 */	
	public function rollback()
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_record )
			{
				if( $_record['record_db_id'] )
				{
					$this->DB->delete( 'downloads_filestorage', 'storage_id=' . $_record['record_db_id'] );
				}
			}
		}
		
		unset($this->details);
	}
	
	/**
	 * Finalize the storage
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return	boolean
	 */	
	public function commit( $file_id=0 )
	{
		parent::commit( $file_id );
	}

	/**
	 * Destructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __destruct()
	{
		$this->_clearUploadsDirectory();
	}
}