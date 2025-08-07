<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS media manager overview
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

class admin_ccs_media_list extends ipsCommand
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
	 * Icons
	 *
	 * @access	protected
	 * @var		array 			Extension -> icon mapping
	 */	
	protected $icons				= array(
										'music'		=> array( 'wav', 'mp3', 'midi' ),
										'movie'		=> array( 'swf', 'wmv', 'avi', 'mpg', 'mpeg' ),
										'image'		=> array( 'gif', 'bmp', 'png', 'jpg', 'jpeg', 'tiff' ),
										'pdf'		=> array( 'pdf', ),
										'zip'		=> array( 'zip', 'gz', 'rar', 'bz', 'ace', 'jar' ),
										'code'		=> array( 'php', 'html', 'htm', 'css', 'js', 'pl', 'cgi', 'xml' ),
										);

	protected $folders;

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
			$this->registry->output->showError( $this->lang->words['missing_ccs_path'], '11CCS110' );
		}
		
		require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/
		
		if( !defined('CCS_MEDIA') OR !CCS_MEDIA )
		{
			$this->registry->output->showError( $this->lang->words['no_media_path'], '11CCS111' );
		}
		else if( !is_dir(CCS_MEDIA) )
		{
			$this->registry->output->showError( $this->lang->words['media_path_bad'], '11CCS112' );
		}
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_mediamanager' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=media&amp;section=list';
		$this->form_code_js	= $this->html->form_code_js	= 'module=media&section=list';

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
			case 'viewdir':
			default:
				$this->_mainScreen();
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
	 protected function _mainScreen()
	 {
	 	//-----------------------------------------
		// Get folders
		//-----------------------------------------

	 	$defaultPath	= str_replace( '\\', '/', realpath( CCS_MEDIA ) );

	 	$folders = array( 'root' => array(
	 										'path' => $defaultPath,
	 										'subfolders' => $this->_getFolderListing( $defaultPath )
	 									)
	 					);

	 	//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->overview( $folders, str_replace( '&#092;', '/', urldecode( $this->request['initialDir'] ) ) );
	}

	/**
	 * Recursively reads folders to build a folder tree
	 *
	 * @param 	string 	The path to check
	 * @return 	mixed 	False if no folders found; array of folders otherwise
	 * @todo	Would be better to use SORT_NATURAL | SORT_FLAG_CASE sort flags, but they're not available until PHP 5.4.  Update ksort calls once we require 5.4 or greater.
	 */
	protected function _getFolderListing( $defaultPath )
	{
		$tmpArray = array();

		foreach( new DirectoryIterator( $defaultPath ) as $object )
		{
			if( $object->isDot() || substr( $object->getFilename(), 0, 1 ) == '.' )
			{
				continue;
			}

			if( $object->isDir() )
			{
				$tmpArray[ $object->getFilename() ]	= array(
															'path' => $object->getPath() . '/' . $object->getFilename(),
															'subfolders' => $this->_getFolderListing( $object->getPath() . '/' . $object->getFilename() )
															);
			}
		}

		if( !count( $tmpArray ) )
		{
			return false;
		}

		ksort( $tmpArray, SORT_LOCALE_STRING );

		return $tmpArray;
	}
}