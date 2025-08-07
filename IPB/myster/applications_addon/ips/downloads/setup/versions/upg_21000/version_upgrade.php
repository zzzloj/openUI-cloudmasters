<?php
/**
 * @file		version_upgrade.php 	IP.Download Manager version upgrader
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-05-26 13:03:06 -0400 (Thu, 26 May 2011) $
 * @version		v2.5.4
 * $Revision: 8902 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		version_upgrade
 * @brief		IP.Download Manager version upgrader
 */
class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		$_output
	 */
	protected $_output = '';
	
	/**
	 * Fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'files':
			default:
				$this->convertFiles();
				break;
			case 'revisions':
				$this->convertRevisions();
				break;
			case 'furls':
				$this->rebuildFurls();
				break;
			case 'tables':
				$this->fixTables();
				break;
		}
		
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Convert files
	 *
	 * @return	@e void
	 */
	public function convertFiles()
	{
		$st		= intval($this->request['st']);
		$limit	= 100;
		$cnt	= 0;
		
		$this->DB->build( array( 'select'	=> 'f.*',
								 'from'		=> array( 'downloads_files' => 'f' ),
								 'order'	=> 'f.file_id ASC',
								 'add_join'	=> array(
								 					array(
								 						'select'	=> 'f.*',
								 						'from'		=> array( 'downloads_filestorage' => 's' ),
								 						'where'		=> 's.storage_id=f.file_id',
								 						'type'		=> 'left',
								 						),
								 					),
								 'limit'	=> array( $st, $limit ) ) );
								
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$postKey	= md5( uniqid( microtime(), true ) );
			
			//-----------------------------------------
			// File
			//-----------------------------------------
			
			$insert	= array(
							'record_post_key'		=> $postKey,
							'record_file_id'		=> $row['file_id'],
							'record_type'			=> $row['file_url'] ? 'link' : 'upload',
							'record_location'		=> $row['file_url'] ? $row['file_url'] : $row['file_filename'],
							'record_db_id'			=> intval($row['storage_id']),
							'record_thumb'			=> null,
							'record_storagetype'	=> $row['file_storagetype'],
							'record_realname'		=> $row['file_realname'],
							'record_link_type'		=> null,
							'record_mime'			=> $row['file_mime'],
							'record_size'			=> intval($row['file_size']),
							);

			$this->DB->insert( "downloads_files_records", $insert );
			
			//-----------------------------------------
			// Screenshot
			//-----------------------------------------
			
			if( $row['file_ssname'] OR $row['file_ssurl'] )
			{
				$insert	= array(
								'record_post_key'		=> $postKey,
								'record_file_id'		=> $row['file_id'],
								'record_type'			=> $row['file_ssurl'] ? 'sslink' : 'ssupload',
								'record_location'		=> $row['file_ssurl'] ? $row['file_ssurl'] : $row['file_ssname'],
								'record_db_id'			=> intval($row['storage_id']),
								'record_thumb'			=> $row['file_thumb'],
								'record_storagetype'	=> $row['file_storagetype'],
								'record_realname'		=> $this->_getScreenshotFilename( $row ),
								'record_link_type'		=> null,
								'record_mime'			=> $row['file_ssmime'],
								'record_size'			=> $this->_getScreenshotFilesize( $row ),
								);
	
				$this->DB->insert( "downloads_files_records", $insert );
			}
			
			$this->DB->update( "downloads_files", array( 'file_post_key' => $postKey ), 'file_id=' . $row['file_id'] );
			
			$cnt++;

			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$done	= $cnt + $st;
		
		$this->registry->output->addMessage("{$done} files converted so far....");
		
		if( $cnt == $limit )
		{
			$this->request['st']		= $st + $limit;
			$this->request['workact']	= 'files';
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= 'revisions';
		}
	}	
	
	/**
	 * Get the screenshot file name
	 *
	 * @param	array		$row		File data
	 * @return	@e string
	 */
	protected function _getScreenshotFilename( $row )
	{
		if( preg_match( "#^[a-zA-Z0-9]{32}\-(.+)$#", $row['file_ssname'] ) )
		{
			return preg_replace( "#^[a-zA-Z0-9]{32}\-(.+)$#", "\\1", $row['file_ssname'] );
		}
		else
		{
			return preg_replace( "#^\d+\-\d+\-(.+)$#", "\\1", $row['file_ssname'] );
		}
	}
	
	/**
	 * Get the screenshot file size
	 *
	 * @param	array		$row		File data
	 * @return	@e integer
	 */
	protected function _getScreenshotFilesize( $row )
	{
		$size = 0;
		
		if( $row['storage_ss'] )
		{
			$size = strlen( base64_decode($row['storage_ss']) );
		}
		else if( $row['file_ssname'] AND ( $row['file_storagetype'] == 'web' OR $row['file_storagetype'] == 'nonweb' ) )
		{
			$size = @filesize( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['idm_localsspath'] ) . '/' . $row['file_ssname'] );
		}
		
		return intval($size);
	}
	
	/**
	 * Convert revisions
	 *
	 * @return	@e void
	 */
	public function convertRevisions()
	{
		$st		= intval($this->request['st']);
		$limit	= 100;
		$cnt	= 0;
		
		$this->DB->build( array( 'select' => 'b.*',
								 'from'   => array( 'downloads_filebackup' => 'b' ),
								 'order'  => 'b.b_id ASC',
								 'add_join'	=> array(
								 					array( 'select' => 'f.file_id, f.file_post_key', 'from' => array( 'downloads_files' => 'f' ), 'where' => 'f.file_id=b.b_fileid', 'type' => 'left' )
								 					),
								 'limit'  => array( $st, $limit ) ) );
								
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$recordIds	= array();
			
			//-----------------------------------------
			// File
			//-----------------------------------------
			
			$insert	= array(
							'record_post_key'		=> $row['file_post_key'],
							'record_file_id'		=> $row['file_id'],
							'record_type'			=> $row['b_fileurl'] ? 'link' : 'upload',
							'record_location'		=> $row['b_fileurl'] ? $row['b_fileurl'] : $row['b_filename'],
							'record_db_id'			=> 0,		// For real upgrader we'd need to look for any db record ids and populate this, and update db storage id to point here
							'record_thumb'			=> null,
							'record_storagetype'	=> $row['b_storage'],
							'record_realname'		=> $row['b_filereal'],
							'record_link_type'		=> null,
							'record_mime'			=> $row['b_filemime'],
							'record_size'			=> $row['b_filename'] ? intval( @filesize( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['idm_localfilepath'] ) . '/' . $row['b_filename'] ) ) : 0 ,
							'record_backup'			=> 1,
							);

			$this->DB->insert( "downloads_files_records", $insert );
			
			$recordIds[]	= $this->DB->getInsertId();
			
			//-----------------------------------------
			// Screenshot
			//-----------------------------------------
			
			if( $row['b_ssname'] OR $row['b_ssurl'] )
			{
				$insert	= array(
								'record_post_key'		=> $row['file_post_key'],
								'record_file_id'		=> $row['file_id'],
								'record_type'			=> $row['b_ssurl'] ? 'sslink' : 'ssupload',
								'record_location'		=> $row['b_ssurl'] ? $row['b_ssurl'] : $row['b_ssname'],
								'record_db_id'			=> 0,		// For real upgrader we'd need to look for any db record ids and populate this
								'record_thumb'			=> $row['b_thumbname'],
								'record_storagetype'	=> $row['b_storage'],
								'record_realname'		=> null,
								'record_link_type'		=> null,
								'record_mime'			=> $row['b_ssmime'],
								'record_size'			=> $row['b_ssname'] ? intval( @filesize( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['idm_localsspath'] ) . '/' . $row['b_ssname'] ) ) : 0,
								'record_backup'			=> 1,
								);
	
				$this->DB->insert( "downloads_files_records", $insert );
				
				$recordIds[]	= $this->DB->getInsertId();
			}
			
			$this->DB->update( "downloads_filebackup", array( 'b_records' => implode( ',', $recordIds ) ), 'b_id=' . $row['b_id'] );
			
			$cnt++;

			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$done	= $cnt + $st;
		
		$this->registry->output->addMessage("{$done} revisions converted so far....");
		
		if( $cnt == $limit )
		{
			$this->request['st']		= $st + $limit;
			$this->request['workact']	= 'revisions';
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= 'furls';
		}
	}

	/**
	 * Set FURL names
	 *
	 * @return	@e void
	 */
	public function rebuildFurls()
	{
		$st		= intval($this->request['st']);
		$limit	= 100;
		$cnt	= 0;

		$this->DB->build( array( 'select'	=> 'f.file_id, f.file_name, f.file_topicid',
								 'from'		=> array( 'downloads_files' => 'f' ),
								 'order'	=> 'f.file_id ASC',
								 'add_join'	=> array(
								 					array(
								 						'select'	=> 't.title, t.title_seo',
								 						'from'		=> array( 'topics' => 't' ),
								 						'where'		=> 't.tid=f.file_topicid',
								 						'type'		=> 'left',
								 						),
								 					),
								 'limit'	=> array( $st, $limit ) ) );
								
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$furl		= IPSText::makeSeoTitle( $row['file_name'] );
			$topicFurl	= $row['file_topicid'] ? ( $row['title_seo'] ? $row['title_seo'] : IPSText::makeSeoTitle( $row['title'] ) ) : '';
			
			$this->DB->update( "downloads_files", array( 'file_name_furl' => $furl, 'file_topicseoname' => $topicFurl ), 'file_id=' . $row['file_id'] );
			
			$cnt++;

			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$done	= $cnt + $st;
		
		$this->registry->output->addMessage("{$done} friendly urls built so far....");
		
		if( $cnt == $limit )
		{
			$this->request['st']		= $st + $limit;
			$this->request['workact']	= 'furls';
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= 'tables';
			
			//-----------------------------------------
			// Do categories and caches
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => 'cid, cname', 'from' => 'downloads_categories' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$furl	= IPSText::makeSeoTitle( $r['cname'] );
				
				$this->DB->update( 'downloads_categories', array( 'cname_furl' => $furl ), 'cid=' . $r['cid'] );
			}
			
			ipsRegistry::getAppClass( 'downloads' );
			
			$this->registry->getClass('categories')->_rebuildCategoryFileinfo( 'all' );
			$this->registry->getClass('categories')->rebuildCatCache();
			$this->registry->getClass('categories')->rebuildStatsCache();
		}
	}
	
	/**
	 * Fix files and revisions tables
	 *
	 * @return	@e void
	 */
	public function fixTables()
	{
		$this->DB->dropField( "downloads_files", "file_placeholder" );
		$this->DB->dropField( "downloads_files", "file_filename" );
		$this->DB->dropField( "downloads_files", "file_ssname" );
		$this->DB->dropField( "downloads_files", "file_thumb" );
		$this->DB->dropField( "downloads_files", "file_mime" );
		$this->DB->dropField( "downloads_files", "file_ssmime" );
		$this->DB->dropField( "downloads_files", "file_storagetype" );
		$this->DB->dropField( "downloads_files", "file_url" );
		$this->DB->dropField( "downloads_files", "file_ssurl" );
		$this->DB->dropField( "downloads_files", "file_realname" );
		
		$this->DB->dropField( "downloads_filebackup", "b_filename" );
		$this->DB->dropField( "downloads_filebackup", "b_ssname" );
		$this->DB->dropField( "downloads_filebackup", "b_thumbname" );
		$this->DB->dropField( "downloads_filebackup", "b_filemime" );
		$this->DB->dropField( "downloads_filebackup", "b_ssmime" );
		$this->DB->dropField( "downloads_filebackup", "b_storage" );
		$this->DB->dropField( "downloads_filebackup", "b_fileurl" );
		$this->DB->dropField( "downloads_filebackup", "b_ssurl" );
		$this->DB->dropField( "downloads_filebackup", "b_filereal" );
		
		$this->registry->output->addMessage("Files and revisions tables cleaned up....");
		
		/* Redirect ;O */
		$this->request['st'] = 0;
		unset($this->request['workact']);
	}
}