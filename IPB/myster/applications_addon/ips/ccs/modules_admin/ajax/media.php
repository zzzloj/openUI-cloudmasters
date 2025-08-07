<?php
/**
 * @file		media.php 	IP.CCS media AJAX functions
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		24th Nov 2011
 * $LastChangedDate: 2012-01-23 18:00:55 -0500 (Mon, 23 Jan 2012) $
 * @version		v3.4.5
 * $Revision: 10168 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * @class		admin_ccs_ajax_media
 * @brief		IP.CCS media AJAX functions
 */
class admin_ccs_ajax_media extends ipsAjaxCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Icons array(extension -> icon mapping)
	 *
	 * @var		$icons
	 */	
	protected $icons = array( 'music'	=> array( 'wav', 'mp3', 'midi' ),
							  'pdf'		=> array( 'pdf' ),
							  'zip'		=> array( 'zip', 'gz', 'rar', 'bz', 'ace', 'jar' ),
							  'code'	=> array( 'php', 'html', 'htm', 'css', 'js', 'pl', 'cgi', 'xml' ),
							  'image'	=> array( 'gif', 'bmp', 'png', 'jpg', 'jpeg', 'tiff' ),
							  'video'	=> array( 'wmv', 'mov', 'avi', 'flv', 'mpg' )
							 );
	
	/**
	 * Search term string
	 * 
	 * @var		$term
	 */
	protected $term = '';

	/**
	 * Array of search results
	 * 
	 * @var		$results
	 */
	protected $results = array();

	/**
	 * The CCS_MEDIA directory path
	 *
	 * @var		$defaultPath
	 */
	protected $defaultPath = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_mediamanager' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Check the media_path file
		//-----------------------------------------

		if( !is_file( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
		{
			$this->returnJsonError( $this->lang->words['missing_ccs_path'] );
		}
		
		require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/
		
		if( !defined('CCS_MEDIA') OR !CCS_MEDIA )
		{
			$this->returnJsonError( $this->lang->words['no_media_path'] );
		}
		else if( !is_dir(CCS_MEDIA) )
		{
			$this->returnJsonError( $this->lang->words['media_path_bad'] );
		}

		$this->defaultPath = str_replace( '\\', '/', realpath( CCS_MEDIA ) );
		
		if( $this->request['dir'] )
		{
			$this->request['dir']	= str_replace( '&#092;', '\\', urldecode( $this->request['dir'] ) );
		}
		
		if( $this->request['in'] )
		{
			$this->request['in']	= str_replace( '&#092;', '\\', urldecode( $this->request['in'] ) );
		}

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'listdirfiles':
				$this->listFolder( false );
			break;

			case 'listdirall':
				$this->listFolder( true );
			break;

			case 'search':
				$this->searchAllFolders();
			break;
			
			case 'delete':
				$this->deleteFiles();
			break;
			
			case 'move':
				$this->moveFiles();
			break;
			
			case 'new':
				$this->newFolder();
			break;
			
			case 'folderdelete':
				$this->deleteFolder();
			break;

			case 'renamefolder':
				$this->renameFolder();
			break;

			case 'upload':
				$this->uploadFiles();
			break;
		}
		
		exit();
	}

	/**
	 * SWF multi-upload
	 *
	 * @return	@e void
	 */
	public function uploadFiles()
	{
		$path	= $this->_stripPath( $this->request['dir'] );
		$count	= 0;
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
		$upload = new $classToLoad();

		$upload->out_file_dir		= $this->defaultPath . $path;
		$upload->max_file_size		= 1000000000;
		$upload->make_script_safe	= 0;
		$upload->check_file_ext		= false;
		
		$upload_results = array();

		if( isset( $_FILES ) && is_array( $_FILES ) && count( $_FILES ) )
		{
			foreach( $_FILES as $_field_name => $data )
			{
				if( ! $_FILES[ $_field_name ]['size'] )
				{
					continue;
				}

				$upload->process();

				if ( $upload->error_no )
				{
					switch( $upload->error_no )
					{
						case 1:
							$upload_results['error'] = $this->lang->words['upload_error_1'];
						break;
						case 2:
							$upload_results['error'] = $this->lang->words['upload_error_2'];
						break;
						case 3:
							$upload_results['error'] = $this->lang->words['upload_error_3'];
						break;
						case 4:
							$upload_results['error'] = $this->lang->words['upload_error_4'];
						break;
						case 5:
							$upload_results['error'] = $this->lang->words['upload_error_5'];
						break;
					}

					continue;
				}

				$upload_results['success'][ $_field_name ] = true;
				$count++;
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediauploaded'], $count ) );

		$this->returnJsonArray( $upload_results );
	}
	
	/**
	 * Renames a folder
	 *
	 * @return @e void
	 */
	public function renameFolder()
	{
		$path = $this->_stripPath( $this->request['dir'] );

		if( !$path )
		{
			$this->returnJsonError( $this->lang->words['no_folder_passed'] );
		}

		if( !is_dir( $this->defaultPath . $path ) )
		{
			$this->returnJsonError( $this->lang->words['media_invalid_dir'] );
		}

		$newName	= str_replace( '/', '_', $this->request['name'] );

		if( !$newName )
		{
			$this->returnJsonError( $this->lang->words['invalid_new_name'] );
		}

		if( is_dir( $this->defaultPath . $newName ) )
		{
			$this->returnJsonError( $this->lang->words['folder_exists'] );
		}

		if( !@rename( $this->defaultPath . $path, $this->defaultPath . '/' . $newName ) )
		{
			$this->returnJsonError( $this->lang->words['rename_folder_failed'] );	
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediadirrenamed'], $path, $newName ) );

		$this->returnJsonArray( array( 'success' => $this->defaultPath . '/' . $newName ) );
	}

	/**
	 * Deletes a folder
	 * 
	 * @return	@e void
	 */
	public function deleteFolder()
	{
		$path = $this->_stripPath( $this->request['dir'] );

		if( !$path )
		{
			$this->returnJsonError( $this->lang->words['no_folder_passed'] );
		}

		if( !is_dir( $this->defaultPath . $path ) )
		{
			$this->returnJsonError( $this->lang->words['media_invalid_dir'] );
		}

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$fileManagement	= new $classToLoad();
		
		if( $fileManagement->removeDirectory( $this->defaultPath . $path ) )
		{
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediadirdeleted'], $path ) );

			$this->returnJsonArray( array( 'success' => true ) );
		}

		$this->returnJsonError( $this->lang->words['delete_folder_failed'] );
	}
	
	/**
	 * Adds a new folder
	 * 
	 * @return	@e void
	 */
	public function newFolder()
	{
		$path		= $this->_stripPath( urldecode( $this->request['dir'] ) );
		$folder		= str_replace( '/', '_', $this->request['folder_name'] );

		if( !$folder )
		{
			$this->returnJsonError( $this->lang->words['no_folder_passed'] );
		}

		if( is_dir( $this->defaultPath . $path . '/' . $folder ) )
		{
			$this->returnJsonError( $this->lang->words['folder_exists'] );
		}

		if( !@mkdir( $this->defaultPath . $path . '/' . $folder, IPS_FOLDER_PERMISSION ) )
		{
			$this->returnJsonError( $this->lang->words['folder_failed'] );	
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediadiradded'], $path . '/' . $folder ) );

		$this->returnJsonArray( array( 'success' => true ) );
	}	
	
	/**
	 * Move files
	 * 
	 * @return	@e void
	 */
	public function moveFiles()
	{
		if( !count( $this->request['files'] ) || !$this->request['dir'] )
		{
			$this->returnJsonError( $this->lang->words['no_files_passed'] );
		}

		$out = array();
		$dir = $this->_stripPath( $this->request['dir'] ) . '/';

		foreach( $this->request['files'] as $file )
		{
			$old_path = $this->_stripPath( $file );

			$filename	= explode( '/', $file );
			$filename	= array_pop( $filename );

			if( !is_file( $this->defaultPath . $old_path ) || !rename( $this->defaultPath . $old_path, $this->defaultPath . $dir . $filename ) )
			{
				$out['failed'][] = $this->defaultPath . $old_path;
				continue;
			}

			$out['success'][] = $this->defaultPath . $dir . $filename;
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediamoved'], count($out['success']), $dir ) );

		$this->returnJsonArray( $out );
	}
	
	/**
	 * Deletes files
	 * 
	 * @return	@e void
	 */
	public function deleteFiles()
	{
		if( !count( $this->request['files'] ) )
		{
			$this->returnJsonError( $this->lang->words['no_files_passed'] );
		}

		$out = array();

		//-----------------------------------------
		// Attempt to delete files
		//-----------------------------------------

		foreach( $this->request['files'] as $file )
		{
			$file = $this->_stripPath( $file );
		
			if( !is_file( $this->defaultPath . $file ) || !@unlink( $this->defaultPath . $file ) )
			{
				$out['failed'][] = $file;
				continue;
			}

			$out['success'][] = $file;
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediadeleted'], count($out['success']) ) );

		$this->returnJsonArray( $out );
	}
	
	/**
	 * Set up a directory search, and return results
	 * 
	 * @return	@e void
	 */
	public function searchAllFolders()
	{
		$defaultPath	= str_replace( '\\', '/', realpath( CCS_MEDIA ) );

		$term = $this->convertAndMakeSafe( $_POST['term'] );
		$this->term = strtolower( str_replace( "/", '', $term ) );

		if( $this->term === '' )
		{
			$this->returnJsonError( $this->lang->words['no_search_term'] );
		}

		//-----------------------------------------
		// Recursively search folders
		//-----------------------------------------

		$this->checkFolder( $defaultPath );

		if( !count( $this->results ) )
		{
			$this->returnJsonArray( array( 'no_results' => true ) );
		}

		$this->returnJsonArray( $this->results );
	}
	
	/**
	 * Recursively search folders & files for a given name
	 * 
	 * @param	string		$folder		Path to folder to search
	 * @return	@e void
	 */
	public function checkFolder( $folder )
	{
		foreach( new DirectoryIterator( $folder ) as $object )
		{
			//-----------------------------------------
			// Ignore junk
			//-----------------------------------------

			if( $object->isDot() || substr( $object->getFilename(), 0, 1 ) == '.' )
			{
				continue;
			}

			if( $object->isDir() )
			{
				$this->checkFolder( $object->getPath() . '/' . $object->getFilename() );
				continue;
			}

			if( strpos( strtolower( $object->getFilename() ), $this->term ) !== false )
			{
				$result = $this->buildFile( $object );
				$this->results[ $result['fileid'] ] = $result;
			}
		}
	}
	
	/**
	  * Lists the contents of a specified folder
	  *
	  * @param	bool		$include_folders		Include sub-folders, or only list files?
	  * @return	@e array 	Array of items in this folder
	  * @todo	Would be better to use SORT_NATURAL | SORT_FLAG_CASE sort flags, but they're not available until PHP 5.4.  Update ksort calls once we require 5.4 or greater.
	  */
	public function listFolder( $include_folders = false )
	{
		if( !$this->request['dir'] )
		{
			$this->returnJsonError( $this->lang->words['media_invalid_dir'] );
		}

		$path = $this->_stripPath( $this->request['dir'] );
		$items = array( 'folders' => array(), 'files' => array() );

		foreach( new DirectoryIterator( $this->defaultPath . $path ) as $object )
		{
			if( $object->isDot() || substr( $object->getFilename(), 0, 1 ) == '.' )
			{
				continue;
			}

			if( $object->isDir() && $include_folders )
			{
				$items['folders'][ $object->getFilename() ] = array( 
						'path' => $object->getPath(),
						'full_path' => $object->getPath() . '/' . $object->getFilename(),
						'name' => $object->getFilename()
				);

				continue;
			}

			if( $object->isFile() )
			{
				$file_array = $this->buildFile( $object ); 
				$items['files'][ $file_array['name'] . $file_array['fileid'] ]	= $file_array;
			}
		}

		ksort( $items['folders'], SORT_LOCALE_STRING );
		ksort( $items['files'], SORT_LOCALE_STRING );

		if( !$include_folders )
		{
			if( !count( $items['files'] ) )
			{
				$items['files']['no_results'] = true;
			}

			$this->returnJsonArray( $items['files'] );
		}

		//-----------------------------------------
		// Get parent info
		//-----------------------------------------

		if( $this->request['dir'] != '/' )
		{
			$urlBits = explode('/', str_replace( CCS_MEDIA, '', $this->request['dir'] ) );
			array_pop( $urlBits );

			$nameBit = $urlBits[ count($urlBits)-1 ];
			$parentPath = CCS_MEDIA . implode('/', $urlBits);

			$items['parent']['path'] = ( $parentPath == CCS_MEDIA ) ? '/' : $parentPath;
			$items['parent']['name'] = $nameBit;
		}

		$this->returnJsonArray( $items );
	}

	 /**
	  * Builds a file array, ready for returning to the client
	  *
	  * @param	object		$file		A DirectoryIterator object that represents a file
	  * @return	@e array 	An array of processed file information
	  */
	 public function buildFile( $file )
	 {
		$icon		= 'file';
		$bits		= explode( '.', $file->getFilename() );
		$extension	= strtolower( array_pop($bits) );
	
		foreach( $this->icons as $png => $types )
		{
			if( in_array( $extension, $types ) )
			{
				$icon	= $png;
				break;
			}
		}

		$full_path	= str_replace( '\\', '/', $file->getPathname() );

		return array(
						'fileid'		=> md5( $full_path ),
						'type'			=> $icon,
						'last_modified'	=> $file->getMTime(),
						'path'			=> $file->getPath(),
						'url'			=> CCS_MEDIA_URL . str_ireplace( CCS_MEDIA, '', $full_path ),
						'full_path'		=> $full_path,
						'name'			=> $file->getFilename(),
						'size'			=> $file->getSize(),
						'icon'			=> $this->settings['skin_acp_url'] . '/images/ccs/' . $icon . '.png',
						'tag'			=> str_ireplace( CCS_MEDIA, '', $full_path )
					);

	 }

	/**
	 * Strips the default path from the provided full path
	 * 
	 * @return	@e string	The clean path
	 */
	protected function _stripPath( $file )
	{
		$file	= urldecode( $file );
		
		if( !$file OR stripos( $file, $this->defaultPath ) === false )
		{
			return false;
		}

		$file	= str_replace( $this->defaultPath, '', trim( $file ) );
		$file	= str_replace( array( '\\', '&#092;' ), '/', $file );

		return $file;
	}
}