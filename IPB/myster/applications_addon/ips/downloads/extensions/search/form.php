<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Returns HTML for the downloads (optional class, not required)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_downloads
{
	/**
	 * Construct
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_downloads' ), 'downloads' );

		/* Set some params for template */
		if( !$this->request['search_app_filters']['downloads']['searchInKey'] )
		{
			IPSSearchRegistry::set( 'downloads.searchInKey', 'files' );
			$this->request['search_app_filters']['downloads']['searchInKey']	= 'files';
		}
	}
	
	/**
	 * Return sort drop down
	 * 
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array(
						'files'		=> array( 
											'date'		=> $this->lang->words['search_sort_submitted'],
											'update'	=> $this->lang->words['search_sort_updated'],
										    'title'		=> $this->lang->words['search_sort_title'],
										    'views'		=> $this->lang->words['search_sort_views'],
										    'downloads'	=> $this->lang->words['search_sort_downloads'],
										    'rating'	=> $this->lang->words['search_sort_rating'],
					    					),
					    'comments'	=> array(
   											'date'  => $this->lang->words['s_search_type_0'],
					    					)
					);
		
		if( ipsRegistry::$settings['use_fulltext'] )
		{
			$array['files']['relevancy'] = $this->lang->words['search_sort_relevancy']; 
		}

		return $array;
	}

	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 * @return	array
	 */
	public function fetchSortIn()
	{
		if( $this->request['search_tags'] )
		{
			return false;
		}

		$array = array( 
						array( 'files',		$this->lang->words['file_files_search'] ),
					    array( 'comments',	$this->lang->words['file_comments_search'] ) 
					);
		
		return $array;
	}

	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @return	string	Filter HTML
	 */
	public function getHtml()
	{
		/* Make sure class_forums is setup */
		if( ipsRegistry::isClassLoaded('categories') !== TRUE )
		{
			/* Get category class */
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'downloads' ) . "/sources/classes/categories.php", 'class_categories', 'downloads' );
			
			$this->registry->setClass( 'categories', new $classToLoad( $this->registry ) );
			$this->registry->getClass('categories')->normalInit();
			$this->registry->getClass('categories')->setMemberPermissions();
		}
		
		$fields	= null;
		
		if( $this->cache->getCache('idm_cfields') )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('downloads') . '/sources/classes/cfields.php', 'customFields', 'downloads' );
    		$fields				= new $classToLoad( $this->registry );
    		
    		if( strpos( $this->request['do'], '_comp' ) !== false )
    		{
    			$fields->file_data	= $this->request;
    		}
    		
    		$fields->cache_data	= $this->cache->getCache('idm_cfields');
    	
    		$fields->init_data( 'search' );
    		$fields->parseToEdit( true );
   		}

		return array( 'title' => IPSLib::getAppTitle('downloads'), 'html' => ipsRegistry::getClass('output')->getTemplate('downloads_external')->downloadsAdvancedSearchFilters( $this->registry->getClass('categories')->catJumpList( true, 'show' ), $fields ) );
	}
}
