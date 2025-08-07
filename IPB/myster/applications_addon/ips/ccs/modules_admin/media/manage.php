<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS media manager file operations
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_media_manage extends ipsCommand
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
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Make sure path is defined first...
		//-----------------------------------------
		
		if( !is_file( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
		{
			$this->registry->output->showError( $this->lang->words['missing_ccs_path'], '11CCS114' );
		}
		
		require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/
		
		if( !defined('CCS_MEDIA') OR !CCS_MEDIA )
		{
			$this->registry->output->showError( $this->lang->words['no_media_path'], '11CCS115' );
		}
		else if( !is_dir(CCS_MEDIA) )
		{
			$this->registry->output->showError( $this->lang->words['media_path_bad'], '11CCS116' );
		}
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_mediamanager' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=media&amp;section=manage';
		$this->form_code_js	= $this->html->form_code_js	= 'module=media&section=manage';

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'multi':
				$this->_multiAction();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
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
			$startPoint	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );
			$ignorable	= array();
			$files		= array();
			$folders	= array();

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
				$this->registry->output->showError( $this->lang->words['nothing_to_move'], '11CCS126' );
			}
			
			if( is_array($this->request['pages']) AND count($this->request['pages']) )
			{
				foreach( $this->request['pages'] as $file )
				{
					$folderBits		= explode( '/', urldecode( $file ) );
					$folderPiece	= array_pop($folderBits);
					$startPoint		= implode( '/', $folderBits );

					$files[]		= $file;
				}
			}
			
			$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );
			
			//-----------------------------------------
			// Get folders
			//-----------------------------------------
	
			$folders[ $defaultPath ]	= $defaultPath;

			foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $defaultPath ), RecursiveIteratorIterator::SELF_FIRST ) as $file )
			{
				if ( $file->getFilename() != '.' AND $file->getFilename() != '..' AND $file->isDir() )
				{
					$folders[ strtolower($file->getPathname()) ]	= str_replace( '\\', '/', $file->getPathname() );
				}
			}

			//-----------------------------------------
			// Show form
			//-----------------------------------------
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['media_moving_nav'] );
			
			$this->registry->getClass('output')->html_main .= $this->html->moveToForm( $startPoint, $ignorable, $folders, $files );
			return;
		}
		
		$someFailed	= false;

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
						if( ! $this->_deleteFolder( urldecode($folder), true ) )
						{
							$someFailed	= true;
						}
					break;
				}
			}
		}
		
		if( is_array($this->request['pages']) AND count($this->request['pages']) )
		{
			foreach( $this->request['pages'] as $page )
			{
				switch( $this->request['action'] )
				{
					case 'move':
						$this->_moveFolder( urldecode($page), urldecode($this->request['moveto']), true );
					break;
					
					case 'delete':
						if( !$this->_deleteFile( urldecode($page), true ) )
						{
							$someFailed	= true;
						}
					break;
				}
			}
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_mediamulti'], $this->request['action'], count($this->request['folders']), count($this->request['pages']), $this->database['database_name'] ) );

		switch( $this->request['action'] )
		{
			case 'move':
				$this->registry->output->setMessage( $this->lang->words['objects_moved'] );
			break;
			
			case 'delete':
				if( $someFailed )
				{
					$this->registry->output->setMessage( $this->lang->words['objects_not_deleted'] );
				}
				else
				{
					$this->registry->output->setMessage( $this->lang->words['objects_deleted'] );
				}
			break;
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=media&section=list&do=viewdir&initialDir=' . $this->request['return'] );
	}
	
	/**
	 * Check the path is within our defined path
	 *
	 * @access	protected
	 * @param	string		Path
	 * @return	mixed		True on success, displays error on failure
	 */
	protected function _checkPath( $path )
	{
		$defaultPath	= str_replace( '\\', '/', realpath( CCS_MEDIA ) );

		if( !$path OR stripos( $path, $defaultPath ) === false )
		{
			$this->registry->output->showError( $this->lang->words['path_defined_bad'], '11CCS128' );
		}
		
		return true;
	}
}