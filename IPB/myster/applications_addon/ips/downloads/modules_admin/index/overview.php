<?php
/**
 * @file		overview.php 	IP.Download Manager Overview
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		1st April 2004
 * $LastChangedDate: 2011-04-20 13:07:30 -0400 (Wed, 20 Apr 2011) $
 * @version		v2.5.4
 * $Revision: 8411 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_downloads_index_overview
 * @brief		IP.Download Manager Overview
 */
class admin_downloads_index_overview extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_overview' );
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=index&amp;section=overview&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=index&section=overview&';
		
		$data	= array( 'overview' => array() );

		//-----------------------------------------
		// Get primary data
		//-----------------------------------------
		
		$disk  = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_downloads) as total_downloads, SUM(file_views) as total_views, COUNT(*) as total_files',
												  'from'   => 'downloads_files'
										  )		 );

		$disk1 = $this->DB->buildAndFetch( array( 'select' => 'SUM(record_size) as total_size',
												  'from'   => 'downloads_files_records'
										  )		 );

		$data['overview']['total_size']			= IPSLib::sizeFormat( $disk1['total_size'] ? $disk1['total_size'] : 0 );
		$data['overview']['total_files']		= intval($disk['total_files']);
		$data['overview']['total_downloads']	= intval($disk['total_downloads']);
		$data['overview']['total_views']		= intval($disk['total_views']);

		if( $this->settings['idm_logalldownloads'] )
		{
			$bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(dsize) as total_bw', 'from' => 'downloads_downloads' ) );
			
			$data['overview']['total_bw']		= IPSLib::sizeFormat( $bw['total_bw'] ? $bw['total_bw'] : 0 );
			
			$st_time = mktime( 0, 0, 0, date( "n" ), 1, date("Y") );
			
			$cur_bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(dsize) as this_bw', 'from' => 'downloads_downloads', 'where' => "dtime > {$st_time}" ) );
			
			$data['overview']['this_bw']		= IPSLib::sizeFormat( $cur_bw['this_bw'] ? $cur_bw['this_bw'] : 0 );
		}
		else
		{
			$bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_downloads*file_size) as total_bw', 'from' => "downloads_files" ) );
			
			$data['overview']['total_bw']		= IPSLib::sizeFormat( $bw['total_bw'] ? $bw['total_bw'] : 0 );
			$data['overview']['this_bw']		= $this->lang->words['o_notavail'];
		}
		
		$largest = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_size', 
													'from'	=> 'downloads_files',
													'order'	=> 'file_size DESC',
													'limit'	=> array( 0, 1 ) 
											)		);
													
		$views = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_views', 
												  'from'	=> 'downloads_files',
												  'order'	=> 'file_views DESC',
												  'limit'	=> array( 0, 1 )
										  )		 );
												
		$downloads = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_downloads', 
													  'from'	=> 'downloads_files',
													  'order'	=> 'file_downloads DESC',
													  'limit'	=> array( 0, 1 )
											  )		 );

		$data['overview']['largest_file_size']	= IPSLib::sizeFormat( $largest['file_size'] ? $largest['file_size'] : 0 );
		$data['overview']['largest_file_name']	= $largest['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=downloads&amp;showfile={$largest['file_id']}'>{$largest['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['overview']['views_file_views']	= intval($views['file_views']);
		$data['overview']['views_file_name']	= $views['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=downloads&showfile={$views['file_id']}'>{$views['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['overview']['dls_file_downloads']	= intval($downloads['file_downloads']);
		$data['overview']['dls_file_name']		= $downloads['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=downloads&showfile={$downloads['file_id']}' target='_blank'>{$downloads['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['reports']['file']				= $this->registry->output->formInput( 'file', $this->request['file'] );
		$data['reports']['member']				= $this->registry->output->formInput( 'member', $this->request['member'], '', 0, 'text', "autocomplete='off'" );
		
		$latest		= array();
		$pending	= array();
		$broken		= array();

		//-----------------------------------------
		// Latest files
		//-----------------------------------------
		
		$this->DB->build( array('select'	=> 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted',
								'from'		=> array( 'downloads_files' => 'f' ),
								'order'		=> 'f.file_submitted DESC',
								'limit'		=> array( 0, 5 ),
								'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
															 'from'		=> array( 'members' => 'm' ),
															 'where'	=> 'm.member_id=f.file_submitter',
															 'type'		=> 'left' ) )
						)		);
													
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->getClass('class_localization')->getDate( $row['file_submitted'], 'SHORT' );
			
			$latest[]			= $row;
		}
		
		//-----------------------------------------
		// Pending files
		//-----------------------------------------
				
		$this->DB->build( array( 'select' => 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted', 
								 'from' 	=> array( 'downloads_files' => 'f' ), 
								 'where' 	=> 'f.file_open=0', 
								 'order' 	=> 'f.file_submitted ASC',
								 'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
								 							 'from'		=> array( 'members' => 'm' ),
								 							 'where'	=> 'm.member_id=f.file_submitter',
								 							 'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}' target='_blank'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->getClass('class_localization')->getDate( $row['file_submitted'], 'SHORT' );

			$pending[]			= $row;
		}
		
		//-----------------------------------------
		// Broken files (poor file id 59...)
		//-----------------------------------------
		
		$this->DB->build( array( 'select' 	=> 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted', 
								 'from' 	=> array( 'downloads_files' => 'f' ), 
								 'where' 	=> 'f.file_broken=1', 
								 'order' 	=> 'f.file_name ASC',
								 'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
								 							 'from'		=> array( 'members' => 'm' ),
								 							 'where'	=> 'm.member_id=f.file_submitter',
															 'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}' target='_blank'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->class_localization->getDate( $row['file_submitted'], 'SHORT' );

			$broken[]			= $row;
		}

		$this->registry->output->html .= $this->html->overviewSplash( $data, $latest, $pending, $broken );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}