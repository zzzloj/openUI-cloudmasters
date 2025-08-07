<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS file manager file operations
 * Last Updated: $Date: 2012-02-02 11:59:30 -0500 (Thu, 02 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10236 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_pages_manage extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	protected
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	protected
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Skin object
	 *
	 * @access	protected
	 * @var		object			Skin templates
	 */	
	protected $html;
	
	/**
	 * Folders
	 *
	 * @access	protected
	 * @var		array 			Folders
	 */	
	protected $folders			= array( '/' );

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
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=manage';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=manage';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// Get existing folders
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders', 'order' => 'folder_path ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->folders[]	= $r['folder_path'];
		}

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'multi':
				$this->_multiAction();
			break;
			
			case 'deleteFolder':
				$this->_deleteFolder( urldecode( $this->request['dir'] ) );
			break;
			
			case 'emptyFolder':
				$this->_emptyFolder( urldecode( $this->request['dir'] ) );
			break;

			case 'doCreateFolder':
				$this->_doCreateFolder();
			break;
			
			case 'doRenameFolder':
				$this->_doRenameFolder();
			break;
			
			case 'editFolder':
				$this->_directoryForm( 'edit' );
			break;
			
			case 'createFolder':
			default:
				$this->_directoryForm( 'add' );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Create a directory
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _doCreateFolder()
	{
		$path		= urldecode($this->request['parent']);
		$folder		= str_replace( '/', '_', $this->request['folder_name'] );
		$newPath	= str_replace( '//', '/', $path . '/' . $folder );

		//-----------------------------------------
		// Make sure this folder doesn't already exist
		//-----------------------------------------
		
		if( in_array( $newPath, $this->folders ) OR $newPath == DATABASE_FURL_MARKER )
		{
			$this->registry->output->showError( $this->lang->words['createfolder_exists'], '11CCS130' );
		}
		
		if ( strpos( $newPath, '&' ) !== FALSE )
		{
			$this->registry->output->showError( $this->lang->words['createfolder_amp'], '11CCS135' );
		}
		
		//-----------------------------------------
		// Create the directory
		//-----------------------------------------
		
		$this->DB->insert( 'ccs_folders', array( 'folder_path' => $newPath, 'last_modified' => time() ) );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pfolderadded'], $newPath ) );
		
		$this->registry->output->setMessage( $this->lang->words['media_folder_created'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . $this->request['parent'] );
	}
	
	/**
	 * Rename a directory
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _doRenameFolder()
	{
		$current	= urldecode($this->request['current']);
		$newName	= str_replace( '/', '_', $this->request['folder_name'] );

		//-----------------------------------------
		// Make sure this folder exists
		//-----------------------------------------

		if( !in_array( $current, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['editfolder_not_exists'], '11CCS131' );
		}
		
		//-----------------------------------------
		// Sort out the proper path stuff
		//-----------------------------------------
		
		$paths			= explode( '/', $current );
		$existing		= array_pop( $paths );
		$pathNoFolder	= implode( '/', $paths );
		$newFolder		= $pathNoFolder . '/' . $newName;

		//-----------------------------------------
		// Make sure new folder does not exists
		//-----------------------------------------

		if( in_array( $newFolder, $this->folders ) OR $newFolder == DATABASE_FURL_MARKER )
		{
			$this->registry->output->showError( $this->lang->words['renamefolder_failed'], '11CCS132' );
		}
		
		$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolder, 'last_modified' => time() ), "folder_path='{$current}'" );
		
		//-----------------------------------------
		// Remember FURL?
		//-----------------------------------------
		
		$this->rememberFolderPages( $current );

		$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolder ), "page_folder='{$current}'" );
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $current . '/' ) === 0 AND $folder != $current )
			{
				$newFolderBit	= str_replace( $current, $newFolder, $folder );
				
				$this->rememberFolderPages( $folder );

				$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolderBit ), "folder_path='{$folder}'" );
				$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolderBit ), "page_folder='{$folder}'" );
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pfolderrenamed'], $current, $newFolder ) );
		
		$this->registry->output->setMessage( $this->lang->words['media_rename_success'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode($pathNoFolder) );
	}
	
	/**
	 * Create/edit a directory form
	 *
	 * @access	protected
	 * @param	string		Add/edit
	 * @return	@e void
	 */
	protected function _directoryForm( $type )
	{
		$this->registry->ccsAcpFunctions->addNavigation( $this->request['dir'] );
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['renaming_a_folder'] );
		
		$this->registry->getClass('output')->html_main .= $this->html->directoryForm( $type );
	}

	/**
	 * Perform action on multiple files/folders
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _multiAction()
	{
		if( $this->request['action'] == 'move' AND !$this->request['moveto'] )
		{
			$startPoint	= '/';
			$ignorable	= array();
			$pages		= array();

			if( is_array($this->request['folders']) AND count($this->request['folders']) )
			{
				foreach( $this->request['folders'] as $folder )
				{
					$folderBits		= explode( '/', urldecode( $folder ) );
					$folderPiece	= array_pop($folderBits);
					$startPoint		= implode( '/', $folderBits );
					
					$ignorable[]	= urldecode( $folder );
				}
			}
			else if( !is_array($this->request['pages']) AND !count($this->request['pages']) )
			{
				$this->registry->output->showError( $this->lang->words['nothing_to_move'], '11CCS133' );
			}
			
			if( is_array($this->request['pages']) AND count($this->request['pages']) )
			{
				$this->DB->build( array( 'select' => 'page_id, page_seo_name, page_folder, page_omit_filename', 'from' => 'ccs_pages', 'where' => "page_id IN(" . implode( ',', $this->request['pages'] ) . ")" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$ignorable[]	= $r['page_folder'] ? $r['page_folder'] : '/';
					$pages[]		= $r;
				}
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['move_files'] );

			$this->registry->getClass('output')->html_main .= $this->html->moveToForm( $startPoint, $ignorable, $this->folders, $pages );
			return;
		}
		
		$this->request['moveto']	= $this->request['moveto'] == '/' ? '' : $this->request['moveto'];
		$actionCount					= 0;

		if( is_array($this->request['folders']) AND count($this->request['folders']) )
		{
			foreach( $this->request['folders'] as $folder )
			{
				switch( $this->request['action'] )
				{
					case 'move':
						$this->_moveFolder( urldecode($folder), urldecode($this->request['moveto']), true );
					break;
					
					case 'delete':
						$this->_deleteFolder( urldecode($folder), true );
					break;
				}

				$actionCount++;
			}
		}
		
		if( is_array($this->request['pages']) AND count($this->request['pages']) )
		{
			foreach( $this->request['pages'] as $page )
			{
				switch( $this->request['action'] )
				{
					case 'move':
						$_thePage	= $this->DB->buildAndFetch( array( 'select' => 'page_id, page_seo_name, page_folder, page_omit_filename', 'from' => 'ccs_pages', 'where' => 'page_id=' . intval($page) ) );
						$url		= $this->registry->ccsFunctions->returnPageUrl( $_thePage );
						$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => $url, 'memory_type' => 'page', 'memory_type_id' => $_thePage['page_id'] ) );
						
						$this->DB->update( 'ccs_pages', array( 'page_folder' => urldecode($this->request['moveto']) ), 'page_id=' . intval($page) );
					break;
					
					case 'delete':
						$this->DB->delete( 'ccs_pages', 'page_id=' . intval($page) );
						$this->DB->delete( 'ccs_revisions', "revision_type='page' AND revision_type_id=" . intval($page) );
						$this->DB->delete( 'ccs_slug_memory', "memory_type='page' AND memory_type_id=" . intval($page) );
					break;
				}

				$actionCount++;
			}
		}

		if( !$actionCount )
		{
			$this->registry->output->showError( $this->lang->words['nothing_to_move'], '11CCS133.1' );
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pmultiaction'], $this->request['action'], count($this->request['folders']), count($this->request['pages']) ) );

		switch( $this->request['action'] )
		{
			case 'move':
				$this->registry->output->setMessage( $this->lang->words['objects_moved'] );
			break;
			
			case 'delete':
				$this->registry->output->setMessage( $this->lang->words['objects_deleted'] );
			break;
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . $this->request['return'] );
	}
	
	/**
	 * Move a file or folder
	 *
	 * @access	protected
	 * @param	string		Path (could be file or folder)
	 * @param	string		New folder
	 * @param	bool		Return instead of output
	 * @return	@e void
	 */
	protected function _moveFolder( $path='', $newPath='', $return=true )
	{
		//-----------------------------------------
		// Sort some path schtuff
		//-----------------------------------------

		$pathBits	= explode( '/', $path );
		$newFolder	= array_pop( $pathBits );
		
		//-----------------------------------------
		// Rename folder
		//-----------------------------------------
		
		if( in_array( $newPath . '/' . $newFolder, $this->folders ) )
		{
			$this->registry->output->showError( $this->lang->words['moveitem_failed'], '11CCS134' );
		}

		$this->rememberFolderPages( $path );
		
		$this->DB->update( 'ccs_folders', array( 'folder_path' => $newPath . '/' . $newFolder ), "folder_path='{$path}'" );
		$this->DB->update( 'ccs_pages', array( 'page_folder' => $newPath . '/' . $newFolder ), "page_folder='{$path}'" );
		
		//-----------------------------------------
		// Get subfolders
		//-----------------------------------------
		
		foreach( $this->folders as $folder )
		{
			if( strpos( $folder, $path ) === 0 AND $folder != $path )
			{
				$newFolderBit	= str_replace( $path, $newPath . '/' . $newFolder, $folder );
				
				$this->rememberFolderPages( $folder );
				
				$this->DB->update( 'ccs_folders', array( 'folder_path' => $newFolderBit ), "folder_path='{$folder}'" );
				$this->DB->update( 'ccs_pages', array( 'page_folder' => $newFolderBit ), "page_folder='{$folder}'" );
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pfoldermoved'], $path, $newPath . '/' . $newFolder ) );
		
		$this->registry->output->setMessage( $this->lang->words['objects_moved'] );
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		if( !$return )
		{
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( $newPath ) );
		}
	}
	
	/**
	 * Empty a folder
	 *
	 * @access	protected
	 * @param	string		Path
	 * @param	bool		Return instead of output
	 * @return	@e void
	 */
	protected function _emptyFolder( $path='', $return=false )
	{
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
			$this->DB->delete( 'ccs_slug_memory', "memory_type='page' AND memory_type_id IN(" . implode( ',', $_pages ) . ")" );
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
					$this->DB->delete( 'ccs_slug_memory', "memory_type='page' AND memory_type_id IN(" . implode( ',', $_pages ) . ")" );
				}
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pfolderemptied'], $path ) );

		if( $return )
		{
			return true;
		}

		$this->registry->output->setMessage( $this->lang->words['folder_emptied'] );
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		$pathBits	= explode( '/', $folder );
		array_pop( $pathBits );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
	}
	
	/**
	 * Delete a folder
	 *
	 * @access	protected
	 * @param	string		Path
	 * @param	bool		Return instead of output
	 * @return	@e void
	 */
	protected function _deleteFolder( $path='', $return=false )
	{
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
			$this->DB->delete( 'ccs_slug_memory', "memory_type='page' AND memory_type_id IN(" . implode( ',', $_pages ) . ")" );
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
					$this->DB->delete( 'ccs_slug_memory', "memory_type='page' AND memory_type_id IN(" . implode( ',', $_pages ) . ")" );
				}
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pfolderdeleted'], $path ) );
		
		if( $return )
		{
			return true;
		}

		$this->registry->output->setMessage( $this->lang->words['folder_removed'] );
		
		//-----------------------------------------
		// Send back to the folder
		//-----------------------------------------
		
		$pathBits	= explode( '/', $folder );
		array_pop( $pathBits );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode( implode( '/', $pathBits ) ) );
	}
	
	/**
	 * Remember pages in a given folder
	 * 
	 * @param	string	Folder
	 * @return	@e void
	 */
 	protected function rememberFolderPages( $folder )
 	{
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='{$folder}'" ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			$url	= $this->registry->ccsFunctions->returnPageUrl( $r );

			$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => $url, 'memory_type' => 'page', 'memory_type_id' => $r['page_id'] ) );
		}
 	}
}
