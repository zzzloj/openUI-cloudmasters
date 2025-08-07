<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IDM userCP pages
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

class usercpForms_downloads extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string
	 */
	public $tab_name						= '';
	
	/**
	 * Default area code
	 *
	 * @access	public
	 * @var		string
	 */
	public $defaultAreaCode					= 'myfiles';
	
	/**
	 * OK Message
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @access	public
	 * @var		string
	 */
	public $ok_message						= '';
	
	/**
	 * Hide 'save' button and form elements
	 * Useful if you have custom output that doesn't
	 * need to use it
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $hide_form_and_save_button		= true;
	
	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @access	public
	 * @var		int
	 */
	public $uploadFormMax					= 0;

	/**
	 * Flag to indicate compatibility
	 * 
	 * @var		int
	 */
 	public $version	= 32;	
	
	/**
	 * Initiate this module
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function init()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['st'] = intval( $this->request['st'] );
		
		//-----------------------------------------
		// Load our class file
		//-----------------------------------------
		
		if( $this->request['tab'] == 'downloads' )
		{
			ipsRegistry::getAppClass( 'downloads' );
		}
	}
	
	/**
	 * Return links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array 		array of links
	 */
	public function getLinks()
	{
		$array = array();

		$array[] = array( 'url'		=> 'area=mydownloads',
						  'title'	=> $this->lang->words['ucp_manage_downloads'],
						  'area'	=> 'mydownloads',
						  'active'	=> $this->request['tab'] == 'downloads' ? 1 : 0 );

		return $array;
	}
	
	
	/**
	 * Run custom event
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string				Current 'area' variable (area=xxxx from the URL)
	 * @return	mixed				html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$html = '';
		
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_downloads_cats_created', 1080, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_downloads_permissions', 1081, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $html;
	}

	/**
	 * UserCP Form Show
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string	Current area as defined by 'get_links'
	 * @param	array 	Errors
	 * @return	string	Processed HTML
	 */
	public function showForm( $current_area, $errors=array() )
	{
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_downloads_cats_created', 1082, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_downloads_permissions', 1083, null, null, 403 );
			}
		}

		$orderby 	= $this->request['order'] && in_array( $this->request['order'], 
							array( 'file_submitted', 'file_updated', 'file_downloads', 'file_rating', 'file_views', 'file_name' ) ) ?
							$this->request['order'] : 'file_name';

		$ordertype 	= $this->request['ascdesc'] && in_array( $this->request['ascdesc'],
							array( 'asc', 'desc' ) ) ? $this->request['ascdesc'] : 'asc';

		$st			= intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		
		$records	= array();
							
		$this->request['ascdesc'] =  $ordertype == 'asc' ? 'desc' : 'asc' ;
		
		$cnt = $this->DB->buildAndFetch( array (	'select'	=> 'count(*) as num',
									  				'from'		=> array( 'downloads_downloads' => 'df' ),
									  				'where'		=> "df.dmid={$this->memberData['member_id']} AND f.file_id IS NOT NULL",
									  				'add_join'	=> array(
									  									array(
									  										'from'	=> array( 'downloads_files' => 'f' ),
									  										'where'	=> 'f.file_id=df.dfid',
									  										'type'	=> 'left',
									  										)
									  									)
												)		);

		$page_links	= $this->registry->output->generatePagination( array(	'totalItems'		=> $cnt['num'],
																   			'itemsPerPage'		=> 20,
																   			'currentStartValue'	=> $st,
																   			'baseUrl'			=> "app=core&amp;module=usercp&amp;tab=downloads&amp;area=mydownloads&amp;order={$orderby}&amp;ascdesc={$ordertype}",
																  )	  	 );		

		$this->DB->build( array ( 'select'	=> 'f.*',
						  				'from'		=> array( 'downloads_downloads' => 'd' ),
						  				'where'		=> "d.dmid={$this->memberData['member_id']} AND f.file_id IS NOT NULL",
						  				'order'		=> 'f.' . $orderby . " " . $ordertype,
						  				'limit'		=> array( $st, 20 ),
						  				'add_join'	=> array(
						  									array(
						  										'select'	=> 'd.*',
						  										'from'		=> array( 'downloads_files' => 'f' ),
						  										'where'		=> 'd.dfid=f.file_id',
						  										'type'		=> 'left'
						  										)
						  									)
								)		);
		$files = $this->DB->execute();
		
		while( $file = $this->DB->fetch($files) )
		{
			$records[] = $file;
		}

       	return $this->registry->getClass('output')->getTemplate('downloads_external')->myDownloads( $page_links, $records );
	}

	/**
	 * UserCP Form Check
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string	Current area as defined by 'get_links'
	 * @return	string	Processed HTML
	 */
	public function saveForm( $current_area )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
		
		return '';
	}
}