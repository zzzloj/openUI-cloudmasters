<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Member property updater (AJAX)
 * Last Updated: $Date: 2011-04-19 12:42:57 -0400 (Tue, 19 Apr 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Revision: 8391 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_member_form__downloads implements admin_member_form
{	
	/**
	* Tab name
	* This can be left blank and the application title will
	* be used
	*
	* @var		string		Tab name
	*/
	public $tab_name = "";

	
	/**
	 * Returns sidebar links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the sidebar for this block.
	 *
	 * The links must have 'section=xxxxx&module=xxxxx[&do=xxxxxx]'. The rest of the URL
	 * is added automatically.
	 *
	 * The image must be a full URL or blank to use a default image.
	 *
	 * Use the format:
	 * $array[] = array( 'img' => '', 'url' => '', 'title' => '' );
	 *
	 * @author	Brandon Farber
	 * @param	array 			Member data
	 * @return	array 			Array of links
	 */
	public function getSidebarLinks( $member=array() )
	{
	
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );

		$array = array();
				
		$array[] = array( 'img'   => '', 
						  'url'   => 'section=stats&amp;module=index&amp;do=report&amp;viewmember=' . $member['member_id'],
						  'title' => ipsRegistry::getClass('class_localization')->words['m_downloadsreport'] );

		return $array;
	}

	/**
	* Returns content for the page.
	*
	* @author	Matt Mecham
	* @param    array 				Member data
	* @return   array 				Array of tabs, content
	*/
	public function getDisplayContent( $member=array(), $tabsUsed=5 )
	{
		return array();
	}
	
	/**
	* Process the entries for saving and return
	*
	* @author	Brandon Farber
	* @return   array 				Multi-dimensional array (core, extendedProfile) for saving
	*/
	public function getForSave()
	{
		return array();
	}
}