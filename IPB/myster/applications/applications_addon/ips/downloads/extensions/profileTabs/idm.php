<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Profile Plugin Library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_idm extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @param	array		$member		Member data
	 * @return	@e string	HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'public_downloads' ), 'downloads' );

		//-----------------------------------------
		// Get downloads library and API
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/api_core.php', 'apiCore' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('downloads') . '/sources/api/api_idm.php', 'apiDownloads', 'downloads' );

		//-----------------------------------------
		// Create API Object
		//-----------------------------------------
		
		$idm_api = new $classToLoad();
		$idm_api->init();

		//-----------------------------------------
		// Get images
		//-----------------------------------------
		
		$files = array();
		$files = $idm_api->returnDownloads( $member['member_id'], 10 );
		
		//-----------------------------------------
		// Ready to pull formatted stuff?
		//-----------------------------------------
		
		if( count($files) )
		{
			$data = array();

			foreach( $files as $row )
			{
				$row['navigation']	= array();
				$navigation			= $this->registry->getClass('categories')->getNav( $row['file_cat'] );
				
				foreach( $navigation as $nav )
				{
					$row['navigation'][]	= "<a href='" . $this->registry->getClass('output')->buildSEOUrl( $nav[1], 'public', $nav[2], 'idmshowcat' ) . "' title='{$nav[0]}'>{$nav[0]}</a>";
				}

				$data[] = $row;
			}
			
			$output = $this->registry->getClass('output')->getTemplate('downloads_external')->profileDisplay( $data );
		}
		else
		{
			$output = $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'error_profile_no_files' );
		}
		      
		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$output = $this->registry->output->replaceMacros( $output );

		return $output;
	}
}