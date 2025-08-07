<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS list AJAX functions
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		14th Jan 2010
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_list extends ipsAjaxCommand
{
	/**
	 * Folders
	 *
	 * @access	protected
	 * @var		array 			Folders
	 */	
	protected $folders			= array();
	
	/**
	 * HTML library
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;
	
	/**
	 * Temporary stored output
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_filemanager' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Get existing folders
		//-----------------------------------------
		
		$this->folders[ '/' ]	= time();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders', 'order' => 'folder_path ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->folders[ $r['folder_path'] ]	= $r['last_modified'];
		}
		
		switch( $this->request['do'] )
		{
			case 'addFolder':
				$this->addFolder();
			break;
			
			case 'renameFolder':
				$this->renameFolder();
			break;
			
			case 'emptyFolder':
				$this->emptyFolder();
			break;
			
			case 'deleteFolder':
				$this->deleteFolder();
			break;
			
			case 'deletePage':
				$this->deletePage();
			break;
			
			case 'search':
				$this->doSearch();
			break;
			
			default:
				$this->getDirectory();
			break;
		}
	}
	
	/**
	 * Get contents of a directory
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function getDirectory() 
	{
		//-----------------------------------------
		// The folder we want to show stuff from
		//-----------------------------------------

		$path	= urldecode( $this->request['dir'] );
		
		//-----------------------------------------
		// Init some vars
		//-----------------------------------------

		$defaultPath	= '/';
		$path			= $path ? $path : $defaultPath;
		$files			= array();
		$folders		= array();
		$dotdot			= array();

		if( !$path OR !isset( $this->folders[ $path ] ) )
		{
			$this->returnHtml('');
		}
		
		//-----------------------------------------
		// Get ../
		//-----------------------------------------
		
		if( $path != $defaultPath )
		{
			$pathBits	= explode( '/', $path );
			array_pop( $pathBits );
			$full_path	= implode( '/', $pathBits );
			
			//-----------------------------------------
			// Got our folders
			//-----------------------------------------
			
			$dotdot[]	= array(
							'last_modified'		=> $this->folders[ $full_path ],
							'path'				=> '..',
							'full_path'			=> $full_path,
							'name'				=> '..',
							'size'				=> 0,
							);
		}
		
		//-----------------------------------------
		// Get folders
		//-----------------------------------------

		$ourFolderBits	= $path == '/' ? array( '/' ) : explode( '/', $path );
		array_pop( $ourFolderBits );

		foreach( $this->folders as $folderPath => $time )
		{
			$folderBits	= explode( '/', $folderPath );
			array_pop( $folderBits );
			
			if( count($folderBits) != count($ourFolderBits) + 1 )
			{
				continue;
			}
			
			if( strpos( $folderPath, str_replace( '//', '/', $path . '/' ) ) === 0 AND $folderPath != $path )
			{
				$folderBits	= explode( '/', $folderPath );
				$folderName	= array_pop( $folderBits );
				
				$this->output	.= $this->html->showFolder( array(
															'last_modified'		=> $time,
															'path'				=> $folderName,
															'full_path'			=> $folderPath,
															'name'				=> $folderName,
															'size'				=> 0,
															'icon'				=> 'folder',
														)	);
			}
		}

		//-----------------------------------------
		// Get any pages in this folder
		//-----------------------------------------
		
		$shortFolder	= rtrim( $path, '/' );

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='" . $shortFolder . "'", 'order' => 'page_name' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$fileIds[ $r['page_id'] ]	= 0;

			$files[ strtolower($r['page_seo_name']) ]	= array(
																'last_modified'		=> $r['page_last_edited'],
																'path'				=> $r['page_folder'],
																'full_path'			=> $r['page_folder'] . '/' . $r['page_seo_name'],
																'name'				=> $r['page_seo_name'],
																'size'				=> strlen($r['page_content']),
																'icon'				=> $r['page_content_type'],
																'page_id'			=> $r['page_id'],
																'is_page'			=> true,
																'revisions'			=> 0,
														);
		}

		//-----------------------------------------
		// Get revision counts
		//-----------------------------------------
		
		if( count($fileIds) )
		{
			$this->DB->build( array( 'select' => 'count(*) as total, revision_type_id', 'from' => 'ccs_revisions', 'where' => "revision_type_id IN(" . implode( ',', array_keys($fileIds) ) . ") AND revision_type='page'", 'group' => 'revision_type_id' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$fileIds[ $r['revision_type_id'] ]	= $r['total'];
			}
			
			foreach( $files as $_k => $_v )
			{
				if( $fileIds[ $_v['page_id'] ] )
				{
					$files[ $_k ]['revisions']	= $fileIds[ $_v['page_id'] ];
				}
			}
		}

		//-----------------------------------------
		// Sort in a "natural" order
		//-----------------------------------------
		
		ksort( $folders, SORT_STRING );
		ksort( $files, SORT_STRING );

		//-----------------------------------------
		// Add in dotdot if it's there
		//-----------------------------------------
		
		$folders	= array_merge( $dotdot, $folders );

		//-----------------------------------------
		// Now output
		//-----------------------------------------
		
		foreach( $files as $file )
		{
			$this->output	.= $this->html->showFile( $file );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->returnHtml( $this->output );
	}
	
	/**
	 * Add a new folder via AJAX
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function addFolder()
	{
		$path		= urldecode($this->request['parent']);
		$folder		= str_replace( '/', '_', $this->request['folder_name'] );
		$newPath	= str_replace( '//', '/', $path . '/' . $folder );

		//-----------------------------------------
		// Make sure this folder doesn't already exist
		//-----------------------------------------

		if( isset( $this->folders[ $newPath ] ) OR $newPath == DATABASE_FURL_MARKER )
		{
			$this->returnString('alreadyexists');
		}
		
		//-----------------------------------------
		// Create the directory
		//-----------------------------------------
		
		$this->DB->insert( 'ccs_folders', array( 'folder_path' => $newPath, 'last_modified' => time() ) );
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_folderadded'], $newPath ) );

		//-----------------------------------------
		// Return HTML
		//-----------------------------------------
		
		$folderBits	= explode( '/', $newPath );
		$folderName	= array_pop( $folderBits );
			
		$this->returnHtml( $this->html->showFolder( array(
														'last_modified'		=> time(),
														'path'				=> $folderName,
														'full_path'			=> $newPath,
														'name'				=> $folderName,
														'size'				=> 0,
														'icon'				=> 'folder',
													)	)	);
	}
	
	/**
	 * Rename folder via AJAX
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function renameFolder()
	{
	}
	
	/**
	 * Empty folder via AJAX
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function emptyFolder()
	{
		$path	= urldecode( $this->request['dir'] );
		
		//-----------------------------------------
		// Empty files and folders
		//-----------------------------------------

		$_pages	= array();
		
		$this->DB->build( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_folder LIKE '{$path}'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_pages[]	= $r['page_id'];
		}
		
		if( count($_pages) )
		{
			$this->DB->delete( 'ccs_pages', "page_id IN(" . implode( ',', $_pages ) . ")" );
			$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id IN(" . implode( ',', $_pages ) . ")" );
		}
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $path . '/' ) === 0 AND $folder != $path )
			{
				$this->DB->delete( 'ccs_folders', "folder_path LIKE '{$folder}'" );

				$_pages	= array();
				
				$this->DB->build( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_folder LIKE '{$folder}'" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$_pages[]	= $r['page_id'];
				}
				
				if( count($_pages) )
				{
					$this->DB->delete( 'ccs_pages', "page_id IN(" . implode( ',', $_pages ) . ")" );
					$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id IN(" . implode( ',', $_pages ) . ")" );
				}
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_folderemptied'], $path ) );

		$this->returnString('ok');
	}
	
	/**
	 * Delete folder via AJAX
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function deleteFolder()
	{
		$path	= urldecode( $this->request['dir'] );
		
		//-----------------------------------------
		// Delete files and folders
		//-----------------------------------------

		$this->DB->delete( 'ccs_folders', "folder_path LIKE '{$path}'" );
		
		$_pages	= array();
		
		$this->DB->build( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_folder LIKE '{$path}'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_pages[]	= $r['page_id'];
		}
		
		if( count($_pages) )
		{
			$this->DB->delete( 'ccs_pages', "page_id IN(" . implode( ',', $_pages ) . ")" );
			$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id IN(" . implode( ',', $_pages ) . ")" );
		}

		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $path . '/' ) === 0 AND $folder != $path )
			{
				$this->DB->delete( 'ccs_folders', "folder_path LIKE '{$folder}'" );

				$_pages	= array();
				
				$this->DB->build( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_folder LIKE '{$folder}'" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$_pages[]	= $r['page_id'];
				}
				
				if( count($_pages) )
				{
					$this->DB->delete( 'ccs_pages', "page_id IN(" . implode( ',', $_pages ) . ")" );
					$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id IN(" . implode( ',', $_pages ) . ")" );
				}
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_folderdeleted'], $path ) );

		$this->returnString('ok');
	}
	
	/**
	 * Delete page via AJAX
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function deletePage()
	{
		$id	= intval($this->request['page']);
		
		$page	= $this->DB->buildAndFetch( array( 'select' => 'page_name', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );

		$this->DB->delete( 'ccs_pages', 'page_id=' . $id );
		$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id=" . $id );
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pagedeleted'], $page['page_name'] ) );

		$this->returnString('ok');
	}
	
	/**
	 * Perform an AJAX search for file or folder
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function doSearch()
	{
		$term	= $this->convertAndMakeSafe( $_POST['term'] );

		$results	= array();
		
		/*foreach( $this->folders as $path => $time )
		{
			$tmp = explode( '/', $path );			
			if( stripos( $tmp[ count($tmp)-1 ], $term ) !== false )
			{
				$results[]	= array( 'type' => 'folder', 'folder' => $path, 'folder_hl' => preg_replace( "#({$term})#i", "<strong class='search'>\\1</strong>", $path ), 'lastmodified' => $time );
			}
		}*/

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "(page_name LIKE '%{$term}%' OR page_seo_name LIKE '%{$term}%')" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['page_name_hl']			= preg_replace( "#({$term})#i", "<strong class='search'>\\1</strong>", $r['page_name'] );
			$r['page_seo_name_hl']		= preg_replace( "#({$term})#i", "<strong class='search'>\\1</strong>", $r['page_seo_name'] );
			$r['page_folder_hl']		= preg_replace( "#({$term})#i", "<strong class='search'>\\1</strong>", $r['page_folder'] );
			
			$results[]	= array_merge( array( 'type' => 'page' ), $r );
		}
		
		if( !count( $results ) )
		{
			$this->returnJsonArray( array() );
		}
		else
		{
			$this->returnJsonArray( $results );
		}
	}
}
