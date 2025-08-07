<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS file manager overview
 * Last Updated: $Date: 2011-11-29 17:56:12 -0500 (Tue, 29 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 9910 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_pages_list extends ipsCommand
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
	protected $folders			= array();

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
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=list';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=list';

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
		
		$this->folders[ '/' ]	= time();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders', 'order' => 'folder_path ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->folders[ $r['folder_path'] ]	= $r['last_modified'];
		}
		
		//-----------------------------------------
		// Manually set nav to avoid 3 links to same page
		//-----------------------------------------
		
		$this->registry->getClass('output')->ignoreCoreNav	= true;
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=pages', $this->lang->words['page_and_file_man'] );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
				$this->_mainScreen();
			break;
			
			case 'viewdir':
				$this->_mainScreen( urldecode( $this->request['dir'] ) );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Show main overview screen
	 *
	 * @access	protected
	 * @param	string		Directory path
	 * @return	@e void
	 */
	protected function _mainScreen( $path='' )
	{
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
			$this->registry->output->showError( $this->lang->words['path_defined_bad'], '11CCS129' );
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
				
				$folders[ strtolower($folderPath) ]	= array(
															'last_modified'		=> $time,
															'path'				=> $folderName,
															'full_path'			=> $folderPath,
															'name'				=> $folderName,
															'size'				=> 0,
															'icon'				=> 'folder',
															);
			}
		}

		//-----------------------------------------
		// Get any pages in this folder
		//-----------------------------------------
		
		$shortFolder	= rtrim( $path, '/' );

		$fileIds	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='" . $shortFolder . "'" ) );
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
		// Remove any stale pages
		//-----------------------------------------

		if( $path == $defaultPath )
		{
			$this->DB->delete( 'ccs_page_wizard', "wizard_started < " . ( time() - ( 6 * 60 * 60 ) ) );
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
		// Extra navigation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=viewdir&amp;dir=' . urlencode( $defaultPath ), '/' );
		
		if( $path != $defaultPath )
		{
			//-----------------------------------------
			// Get rid of base path in our current path
			//-----------------------------------------
			
			$navPath	= substr( $path, 1 );

			//-----------------------------------------
			// Get folders
			//-----------------------------------------
			
			$pathBits	= explode( '/', $navPath );

			//-----------------------------------------
			// Get nav...
			//-----------------------------------------
			
			$bitsSoFar	= '';
			
			if( count($pathBits) )
			{
				foreach( $pathBits as $bit )
				{
					$thisPath	= $bitsSoFar . '/' . $bit;
					$bitsSoFar	.= '/' . $bit;
					
					$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=viewdir&amp;dir=' . urlencode( $thisPath ), '/' . $bit );
				}
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->overview( $path, $folders, $files );
	}
}
