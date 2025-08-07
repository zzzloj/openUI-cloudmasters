<?php

/**
* Tracker 2.1.0
* 
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_tracker
{
	/**
	 * Construct
	 *
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
		
		$this->lang->loadLanguageFile( array( 'public_search' ), 'tracker' );
	}

	/**
	 * Return sort drop down
	 *
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array( 'date'  => $this->lang->words['s_search_type_0'],
					    'title' => $this->lang->words['tracker_sort_title'],
					    'posts' => $this->lang->words['tracker_sort_posts'] );

		return $array;
	}

	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @access	public
	 * @return	string	Filter HTML
	 **/
	public function getHtml()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_search' ), 'tracker' );
		$this->checkForRegistry();
		return array( 'title' => ipsRegistry::$applications['tracker']['app_public_title'], 'html' => ipsRegistry::getClass( 'output' )->getTemplate( 'tracker_search' )->trackerAdvancedSearchFilters( ipsRegistry::getClass( 'tracker' )->projects()->makeDropdown( '', 0, FALSE, 'options' ) ) );
	}

	/**
	 * Loads the tracker library if needed
	 *
	 * @return void
	 * @access protected
	 * @since 1.4.40
	 */
	protected function checkForRegistry()
	{
		if ( ! class_exists( 'app_class_tracker' ) )
		{
			$registry = ipsRegistry::instance();

			require_once( IPSLib::getAppDir('tracker') . '/app_class_tracker.php' );
			new app_class_tracker( $registry );
		}
	}
}

?>