<?php
/**
 * @file		usercpForms.php 	IP.Gallery User CP integration
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-07-09 19:54:15 -0400 (Mon, 09 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11048 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercpForms_gallery extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name (defaults to application title when blank)
	 *
	 * @var		string
	 */
	public $tab_name	= '';
	
	/**
	 * OK Message. 
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @var		string
	 */
	public $ok_message	= '';
	
	/**
	 * Hide 'save' button and form elements. 
	 * Useful if you have custom output that doesn't
	 * need to use it
	 *
	 * @var		bool
	 */
	public $hide_form_and_save_button	= true;
	
	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @var		int
	 */
	public $uploadFormMax	= 0;	
	
	/**
	 * Default area code
	 *
	 * @var		string
	 */
	public $defaultAreaCode	= 'albums';

	/**
	 * Flag to indicate compatibility
	 * 
	 * @var		int
	 */
 	public $version			= 32;

	/**
	 * Initiate this module
	 *
	 * @return	@e void
	 */
	public function init()
	{
		//-----------------------------------------
		// Only load our stuff if we're viewing Gallery tab
		//-----------------------------------------

		if( $this->request['tab'] == 'gallery' )
		{
			$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );

			if ( !$this->registry->isClassLoaded('gallery') )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
		}
	}
	
	/**
	 * Return links for this tab.
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @return	@e array	Array of menu links
	 */
	public function getLinks()
	{
		$array	= array();

		if( $this->memberData['g_create_albums'] OR $this->memberData['g_create_albums_private'] OR $this->memberData['g_create_albums_fo'] )
		{
			$array[]	= array(
								'url'		=> 'area=albums',
								'title'		=> $this->lang->words['your_albums'],
								'area'		=> 'albums',
								'active'	=> $this->request['tab'] == 'gallery' && $this->request['area'] == 'albums' ? 1 : 0
								);
		}

		return $array;
	}
	
	/**
	 * Run custom event.
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @param	string		$currentArea		Current 'area' variable (area=xxxx from the URL)
	 * @return	@e mixed	html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		// Nothing
	}
	
	/**
	 * UserCP Form Show
	 *
	 * @param	string		$current_area		Current area as defined by 'get_links'
	 * @param	array		$errors				Errors found
	 * @return	@e string	Processed HTML
	 */
	public function showForm( $current_area, $errors=array() )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------

		switch( $current_area )
		{
			default:
			case 'albums':
				return $this->showAlbumListing();
			break;
		}
	}
	
	/**
	 * Builds list of albums
	 *
	 * @return	@e string
	 */
	public function showAlbumListing()
	{
		//-----------------------------------------
		// Return a list of our albums
		//-----------------------------------------

		return $this->registry->output->getTemplate('gallery_external')->userCpAlbumIndexView( $this->registry->gallery->helper('albums')->fetchAlbumsByOwner( $this->memberData['member_id'] ) );
	}
	
	/**
	 * UserCP Form Check
	 *
	 * @param	string		$current_area		Current area as defined by 'get_links'
	 * @return	@e string	Processed HTML
	 */
	public function saveForm( $current_area )
	{
		// Nothing
	}
}