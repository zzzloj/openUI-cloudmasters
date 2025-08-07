<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * View file downloaders
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_downloads_display_downloaders extends ipsCommand
{
	/**
	 * IPS command execution
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id			= intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_downloads'], 108997 );
		}
		
		//-----------------------------------------
		// Get file info
		//-----------------------------------------
		
		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_downloads'], 108996 );
		}
		
		//-----------------------------------------
		// Make sure we can view
		//-----------------------------------------
		
		if( !$this->settings['idm_logalldownloads'] OR ( !$this->memberData['idm_view_downloads'] && ! ( $this->settings['submitter_view_dl'] && $this->memberData['member_id'] == $file['file_submitter']  ) ) )
		{
			$this->registry->output->showError( $this->lang->words['cannot_view_downloads'], 108998 );
		}

		//-----------------------------------------
		// Verify we can access
		//-----------------------------------------

		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 108995 );
			}
			else
			{
				$this->registry->output->showError( 'no_permitted_categories', 108994 );
			}
		}

		//-----------------------------------------
		// Get data for pagelinks
		//-----------------------------------------

		$st			= intval($this->request['st']);
		$perpage	= 20;
		$count		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(' . $this->DB->buildDistinct('dmid') . ') as total', 'from' => 'downloads_downloads', 'where' => 'dfid=' . $id ) );
		
		$pagelinks	= $this->registry->output->generatePagination( array(
																			'totalItems'		=> $count['total'],
																			'itemsPerPage'		=> $perpage,
																			'currentStartValue'	=> $st,
																			'baseUrl'			=> "app=downloads&amp;module=display&amp;section=downloaders&amp;id=" . $file['file_id'],
																	)		);
		
		//-----------------------------------------
		// Get distinct member ids who have downloaded
		//-----------------------------------------
		
		$member_ids	= array();
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct('dmid') . ' as member_id, dtime', 'from' => 'downloads_downloads', 'where' => 'dfid=' . $id, 'order' => 'dtime DESC', 'limit' => array( $st, $perpage ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$member_dls[ $r['member_id'] ]	= $r;
		}
		
		if( count($member_dls) )
		{
			$finalMembers	= array();
			$members		= IPSMember::load( array_keys( $member_dls ), 'all' );
			
			foreach( $members as $mid => $member )
			{
				$member	= IPSMember::buildDisplayData( $member );
				$member['_last_download'] = $member_dls[ $mid ]['dtime'];
				
				$finalMembers[ $mid ]	= $member;
			}
		}
		
		foreach( $this->registry->getClass('categories')->getNav( $file['file_cat'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'idmshowcat' );
		}
		
		$this->registry->output->addNavigation( $file['file_name'], 'app=downloads&amp;showfile='.$file['file_id'], $file['file_name_furl'], 'idmshowfile' );
		$this->registry->output->addNavigation( $this->lang->words['view_all_downloaders'] );

        $this->registry->output->setTitle(  sprintf( $this->lang->words['downloaders_pt'], $file['file_name'] ) . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->registry->output->getTemplate('downloads_external')->fileDownloaders( $file, $finalMembers, false, $pagelinks ) );
		$this->registry->output->sendOutput();
	}
}