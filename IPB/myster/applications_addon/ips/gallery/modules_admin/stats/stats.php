<?php
/**
 * @file		stats.php 	Gallery statistics methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2013-04-11 18:03:45 -0400 (Thu, 11 Apr 2013) $
 * @version		v5.0.5
 * $Revision: 12168 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_stats_stats
 * @brief		Gallery statistics methods
 */
class admin_gallery_stats_stats extends ipsCommand 
{
	/**
	 * Skin object shortcut
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		string
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		string
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
		//---------------------------------------
		// Init
		//---------------------------------------

		$this->html	= $this->registry->output->loadTemplate( 'cp_skin_gallery_statistics' );

		$this->lang->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		$this->html->form_code		= $this->form_code    = 'module=stats&amp;section=stats&amp;';
		$this->html->form_code_js	= $this->form_code_js = 'module=stats&section=stats&';
		
		//---------------------------------------
		// What to do
		//---------------------------------------

		switch( $this->request['do'] )
		{
			case 'get_chart':
				$this->_getChart();
			break;
			
			case 'domemsrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_members' );
				
				if( empty($this->request['viewuser']) )
				{
					$this->doMemberSearch();
				}
				else
				{
					$this->viewMemberReport( $this->request['viewuser'] );
				}
			break;
			
			case 'dogroupsrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_groups' );
				
				if( empty($this->request['viewgroup']) )
				{
					$this->doGroupSearch();
				}
				else
				{
					$this->viewGroupReport( $this->request['viewgroup'] );
				}
			break;
			
			case 'dofilesrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_files' );
				
				if( empty($this->request['viewfile']) )
				{
					$this->doFileSearch();
				}
				else
				{
					$this->viewFileReport( $this->request['viewfile'] );
				}
			break;
			
			case 'domemact':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_files' );
				$this->doMemberAction();
			break;

			case 'dofileact':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_memberalter' );
				$this->doFileAction();
			break;

			default:
				$this->indexScreen();
			break;
		}
		
		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Main stats display screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		//---------------------------------------
		// Overall Stats
		//---------------------------------------
		
		$stats		= $this->DB->buildAndFetch( array( 'select' => 'SUM( image_file_size ) as total_size, COUNT( image_file_size ) as total_uploads', 'from' => 'gallery_images' ) );
		$overall	= array();
		
		$overall['total_diskspace']	= IPSLib::sizeFormat( empty($stats['total_size']) ? 0 : $stats['total_size'] );
		$overall['total_uploads']	= $this->lang->formatNumber( $stats['total_uploads'] );

		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$time_cutoff	= time() - ( $this->settings['gallery_bandwidth_period'] * 3600 );
			
			$more_stats		= $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth', 'where' => 'bdate > '.$time_cutoff ) );
			$stats			= array_merge( $stats, $more_stats );

			unset( $more_stats, $time_cutoff );
			
			$overall['total_transfer']	= IPSLib::sizeFormat( empty($stats['total_transfer']) ? 0 : $stats['total_transfer'] );
			$overall['total_views']		= $this->lang->formatNumber( $stats['total_viewed'] );
		}
		
		$stats['total_transfer']	= $stats['total_transfer'] ? intval($stats['total_transfer']) : 1;
		
		//---------------------------------------
		// Group Stats
		//---------------------------------------

		$groups_disk	= array();

		$this->DB->build( array(
								'select'	=> 'g.g_title, g.g_id',
								'from'		=> array( 'groups' => 'g' ),
								'group'		=> 'm.member_group_id',
								'order'		=> 'diskspace DESC',
								'add_join'	=> array(
													array(
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_group_id=g.g_id',
														'type'		=> 'inner'
														),
													 array(
													 	'select'	=> 'SUM( i.image_file_size ) as diskspace, COUNT( i.image_file_size ) as uploads',
														'from'		=> array( 'gallery_images' => 'i' ),
														'where'		=> 'i.image_member_id=m.member_id',
														'type'		=> 'inner' 
														)
													 )
						)		);
		$this->DB->execute();

		while( $i = $this->DB->fetch() )
		{
		 	$i['dp_percent']	= round( $i['diskspace']	/ $stats['total_size']		, 2 ) * 100;
		 	$i['up_percent']	= round( $i['uploads']		/ $stats['total_uploads']	, 2 ) * 100;
			$i['diskspace']		= IPSLib::sizeFormat( empty($i['diskspace']) ? 0 : $i['diskspace'] );
			
			$groups_disk[]		= $i;
		}

		//---------------------------------------
		// Diskspace By Member
		//---------------------------------------

		$users_disk	= array();

		$this->DB->build( array(
								'select'	=> 'm.members_display_name, m.member_id AS mid',
								'from'		=> array( 'members' => 'm' ),
								'group'		=> 'm.member_id, m.members_display_name',
								'order'		=> 'diskspace DESC',
								'limit'		=> array( 0, 5 ),
								'add_join'	=> array(
													array(
														'select'	=> 'SUM( i.image_file_size ) as diskspace, COUNT( i.image_file_size ) as uploads',
														'from'		=> array( 'gallery_images' => 'i' ),
														'where'		=> 'i.image_member_id=m.member_id',
														'type'		=> 'inner' 
														)
													)
						)		);
		$this->DB->execute();

		while( $i = $this->DB->fetch() )
		{
		 	$i['dp_percent']	= round( $i['diskspace'] 	/ $stats['total_size']		, 2 ) * 100;
		 	$i['up_percent']	= round( $i['uploads'] 		/ $stats['total_uploads']	, 2 ) * 100;
			$i['diskspace']		= IPSLib::sizeFormat( empty($i['diskspace']) ? 0 : $i['diskspace'] );
			
			$users_disk[]		= $i;
		}

		//---------------------------------------
		// Bandwidth Stats
		//---------------------------------------

		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			//---------------------------------------
			// Bandwidth By Group
			//---------------------------------------

			$groups_bandwidth	= array();

			$this->DB->build( array(
									'select'	=> 'g.g_title, g.g_id',
									'from'		=> array( 'groups' => 'g' ),
									'group'		=> 'g.g_id',
									'order'		=> 'transfer DESC',
									'add_join'	=> array(
														array(
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_group_id=g.g_id',
															'type'		=> 'inner'
															),
														 array(
														 	'select'	=> 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total',
															'from'		=> array( 'gallery_bandwidth' => 'b' ),
															'where'		=> 'b.member_id=m.member_id',
															'type'		=> 'inner'
															)
														 )
							 )		);
			$this->DB->execute();

			while( $i = $this->DB->fetch() )
			{
				$i['dp_percent']	= $stats['total_transfer']	? round( $i['transfer']	/ $stats['total_transfer']	, 2 ) * 100 : 0;
				$i['up_percent']	= $stats['total_viewed'] 	? round( $i['total'] 	/ $stats['total_viewed']	, 2 ) * 100 : 0;
				$i['transfer']		= IPSLib::sizeFormat( empty($i['transfer']) ? 0 : $i['transfer'] );
				
				$groups_bandwidth[]	= $i;
			}

			//---------------------------------------
			// Bandwidth By Member
			//---------------------------------------

			$users_bandwidth	= array();

			$this->DB->build( array(
									'select'	=> 'm.members_display_name, m.member_id',
									'from'		=> array( 'members' => 'm' ),
									'group'		=> 'm.member_id, m.members_display_name',
									'order'		=> 'transfer DESC',
									'limit'		=> array( 0, 5 ),
									'add_join'	=> array(
														array(
															'select'	=> 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total',
															'from'		=> array( 'gallery_bandwidth' => 'b' ),
															'where'		=> 'b.member_id=m.member_id',
															'type'		=> 'inner'
															)
														) 
							 )		);
			$this->DB->execute();

		 	while( $i = $this->DB->fetch() )
		 	{
			 	$i['dp_percent']	= round( $i['transfer'] / $stats['total_transfer'], 2 ) * 100;
			 	$i['up_percent']	= $stats['total_viewed'] ? round( $i['total'] / $stats['total_viewed'], 2 ) * 100 : 0;
				$i['transfer']		= IPSLib::sizeFormat( empty($i['transfer']) ? 0 : $i['transfer'] );
				
				$users_bandwidth[]	= $i;
		 	}

			//---------------------------------------
			// Bandwidth By File
			//---------------------------------------

		 	$files_bandwidth	= array();

			$this->DB->build( array(
									'select'	=> 'i.image_file_name, i.image_id',
									'from'		=> array( 'gallery_images' => 'i' ),
									'group'		=> 'b.file_name',
									'order'		=> 'transfer DESC',
									'limit'		=> array( 0, 5 ),
									'add_join'	=> array(
														array(
															'select'	=> 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total, b.file_name AS m_file_name',
															'from'		=> array( 'gallery_bandwidth' => 'b' ),
															'where'		=> 'b.file_name=i.image_masked_file_name',
															'type'		=> 'left'
															)
														)
							 )		);
			$this->DB->execute();  

		 	while( $i = $this->DB->fetch() )
			{
			 	if( substr( $i['m_file_name'], 0, 3 ) == 'tn_' )
			 	{
					$i['image_file_name'] = 'tn_' . $i['image_file_name'];
			 	}
			 
			 	$i['dp_percent']	= round( $i['transfer']	/ $stats['total_transfer']	, 2 ) * 100;
			 	$i['up_percent']	= $stats['total_viewed'] ? round( $i['total'] 	/ $stats['total_viewed']	, 2 ) * 100 : 0;
				$i['transfer']		= IPSLib::sizeFormat( empty($i['transfer']) ? 0 : $i['transfer'] );

				$files_bandwidth[]	= $i;
		 	}
		}
		
		//---------------------------------------
		// Output stats
		//---------------------------------------

		$this->registry->output->html .= $this->html->statsOverview( $overall, $groups_disk, $users_disk, $groups_bandwidth, $users_bandwidth, $files_bandwidth );
	}
	
	/**
	 * Perform a Member Search
	 *
	 * @return	@e void
	 */
	public function doMemberSearch()
	{
		//---------------------------------------
		// Do we have a search term?
		//---------------------------------------

		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11737 );
		}
		
		//---------------------------------------
		// Perform search
		//---------------------------------------

		$search_term	= $this->DB->addSlashes( strtolower($this->request['search_term']) );

		$this->DB->build( array('select' => 'member_id, members_display_name', 
								'from'   => 'members',
								'order'  => 'members_display_name ASC',
								'where'  => "members_l_username LIKE '%" . $search_term . "%' OR members_l_display_name LIKE '%" . $search_term . "%'" 
						 )		);
		$this->DB->execute();

		$result_cnt	= $this->DB->getTotalRows();

		//---------------------------------------
		// If only one result, go there now
		//---------------------------------------

		if( $result_cnt == 1 )
		{
		 	$i	= $this->DB->fetch();

		 	return $this->viewMemberReport( $i['member_id'] ); 
		}
		else
		{
			//---------------------------------------
			// Loop over results
			//---------------------------------------

			$rows	= array();

			while( $i = $this->DB->fetch() )
			{
				$rows[]	= array( 'url' => "{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$i['member_id']}", 'name' => $i['members_display_name'] );
			}
			
			//---------------------------------------
			// Output
			//---------------------------------------

			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows );
		}
	}

	/**
	 * View Results From a Member Search
	 *
	 * @param	integer		$mid		Member ID
	 * @return	@e void
	 */
	public function viewMemberReport( $mid )
	{
		//---------------------------------------
		// Load member data
		//---------------------------------------

		$member	= $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, gallery_perms',
												   'from'   => 'members',
												   'where'  => 'member_id=' . intval( $mid ) 
										   )	  );

		if( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['stats_no_member_found'], 11738 );
		}
		
		//---------------------------------------
		// Some global and member stats
		//---------------------------------------

		$stats	= $this->DB->buildAndFetch( array( 'select' => 'SUM( image_file_size ) as total_size, AVG( image_file_size ) as total_avg_size, COUNT( image_file_size ) as total_uploads', 'from' => 'gallery_images' ) );
		$stats	= array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth' ) ) );
		$stats	= array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( image_file_size ) as user_size, AVG( image_file_size ) as user_avg_size, COUNT( image_file_size ) as user_uploads', 'from' => 'gallery_images', 'where'  => 'image_member_id=' . $mid ) ) );
		$stats	= array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as user_transfer, COUNT( bsize ) as user_viewed', 'from' => 'gallery_bandwidth', 'where' => 'member_id=' . $mid ) ) );
		
		//---------------------------------------
		// Format the stats
		//---------------------------------------

		$stats['dp_percent']		= $stats['total_size']		? ( round( $stats['user_size'] 		/ $stats['total_size']		, 2 ) * 100 ).'%' : '0%';
		$stats['up_percent']		= $stats['total_uploads']	? ( round( $stats['user_uploads'] 	/ $stats['total_uploads']	, 2 ) * 100 ).'%' : '0%';
		$stats['user_size']			= IPSLib::sizeFormat( empty($stats['user_size']) ? 0 : $stats['user_size'] );
		$stats['total_avg_size']	= IPSLib::sizeFormat( empty($stats['total_avg_size']) ? 0 : $stats['total_avg_size'] );
		$stats['user_avg_size']		= IPSLib::sizeFormat( empty($stats['user_avg_size']) ? 0 : $stats['user_avg_size'] );
		$stats['bw']				= array();

		//---------------------------------------
		// Detailed bandwidth logs?
		//---------------------------------------

		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$stats['bw']['title']		= sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] );
			$stats['bw']['list_title']	= sprintf( $this->lang->words['stats_top_views_bandwidth'], $this->settings['gallery_bandwidth_period'] );
			$stats['bw']['tr_percent']	= $stats['total_transfer'] 	? ( round( $stats['user_transfer'] 	/ $stats['total_transfer']	, 2 ) * 100 ).'%' : '0%';
			$stats['bw']['vi_percent']	= $stats['total_viewed']	? ( round( $stats['user_viewed'] 	/ $stats['total_viewed']	, 2 ) * 100 ).'%' : '0%';
			$stats['user_transfer']		= IPSLib::sizeFormat( empty($stats['user_transfer']) ? 0 : $stats['user_transfer'] );
			$stats['bw']['rows']		= array();

			//---------------------------------------
			// Get some additional bandwidth data
			//---------------------------------------

			$this->DB->build( array('select'   => 'i.image_file_name, i.image_id',
									'from'     => array( 'gallery_images' => 'i' ),
									'where'    => "b.member_id={$mid}",
									'group'    => 'b.file_name',
									'order'    => 'transfer DESC',
									'limit'    => array( 0, 5 ),
									'add_join' => array(
														array(
															'select'	=> "SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total, b.file_name AS m_file_name",
															'from'		=> array( 'gallery_bandwidth' => 'b' ),
															'where'		=> 'b.file_name=i.image_masked_file_name',
															'type'		=> 'left'
															)
														)
							 )		);
			$this->DB->execute();

		 	while( $i = $this->DB->fetch() )
		 	{
			 	$i['dp_percent']	= $stats['user_transfer'] 	? round( $i['transfer']	/ $stats['user_transfer']	, 2 ) * 100 : 0;
			 	$i['up_percent']	= $stats['user_viewed']		? round( $i['total'] 	/ $stats['user_viewed']		, 2 ) * 100	: 0;
			 	$i['transfer']		= IPSLib::sizeFormat( empty($i['transfer']) ? 0 : $i['transfer'] );

			 	if( substr( $i['m_file_name'], 0, 3 ) == 'tn_' )
			 	{
					$i['image_file_name'] = 'tn_' . $i['image_file_name'];
			 	}

			 	$rows[]	= $i;
		 	}
	 	}
		
		//---------------------------------------
		// Comment and rating info
		//---------------------------------------

		$comments	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS comments', 'from' => 'gallery_comments', 'where' => 'comment_author_id=' . $mid ) );
		$comments	= intval($comments['comments']);

		$rate		= $this->DB->buildAndFetch( array(
													'select' => 'COUNT(rate_rate) AS total_rates, AVG(rate_rate) AS avg_rate', 
													'from'   => 'gallery_ratings', 
													'where'  => 'rate_member_id=' . $mid 
											)		);
										
		$rate['avg_rate']	= round( $rate['avg_rate'], 2 );

		//---------------------------------------
		// Permissions
		//---------------------------------------

		$perms						= explode( ":", $member['gallery_perms'] );
		$stats['remove_gallery']	= $this->registry->output->formYesNo( 'remove_gallery', ( $perms[0] == 1 ) ? 0 : 1 );
		$stats['remove_uploading']	= $this->registry->output->formYesNo( 'remove_uploading', ( $perms[1] == 1 ) ? 0 : 1 );
		
		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html .= $this->html->memberFileReport( $mid, $stats, $comments, $rate, sprintf( $this->lang->words['stats_mem_result_page_title'], $member['members_display_name'] ) );
	}
		
	/**
	 * Take Action Against Member
	 *
	 * @return	@e void
	 */
	public function doMemberAction()
	{
		$this->request['mid']	= intval($this->request['mid']);
		
		$view		= ( $this->request['remove_gallery'] == 1 ) ? 0 : 1;
		$upload		= ( $this->request['remove_uploading'] == 1 ) ? 0 : 1;
		
		IPSMember::save( $this->request['mid'], array( 'core' => array( 'gallery_perms' => $view . ':' . $upload ) ) );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['stats_mem_action_log'], $this->request['mid'], $perms ) );
		
		//---------------------------------------
		// Redirect
		//---------------------------------------

		$this->registry->output->setMessage( $this->lang->words['stats_mem_action_msg'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=domemsrch&amp;viewuser=' . $this->request['mid'] );
	}

	/**
	 * Perform Group Search
	 *
	 * @return	@e void
	 */
	public function doGroupSearch()
	{
		//---------------------------------------
		// Check search term
		//---------------------------------------

		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11739 );
		}
		
		//---------------------------------------
		// Run search
		//---------------------------------------

		$this->DB->build( array('select' => 'g_id, g_title',
								'from'   => 'groups',
								'where'  => $this->DB->buildLower('g_title') . " LIKE '%" . $this->DB->addSlashes( strtolower($this->request['search_term']) ) . "%'" 
						 )		);
		$this->DB->execute();
		
		//---------------------------------------
		// Get results count
		//---------------------------------------

		$result_cnt	= $this->DB->getTotalRows();

		//---------------------------------------
		// If we have one result, go straight there
		//---------------------------------------

		if( $result_cnt == 1 )
		{
		 	$i	= $this->DB->fetch();

		 	return $this->viewGroupReport( $i['g_id'] );
		}
		else
		{
			//---------------------------------------
			// Loop over results
			//---------------------------------------

			$rows	= array();

			while( $i = $this->DB->fetch() )
			{
				$rows[]	= array( 'url' => "{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$i['g_id']}", 'name' => $i['g_title'] );
			}
			
			//---------------------------------------
			// Output
			//---------------------------------------

			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows );
		}
	}

	/**
	 * View Results From Group Search
	 *
	 * @param	integer		$gid		Group ID
	 * @return	@e void
	 */
	public function viewGroupReport( $gid )
	{
		//---------------------------------------
		// Global image stats
		//---------------------------------------

		$stats	= $this->DB->buildAndFetch( array( 'select' => 'SUM( image_file_size ) as total_size, AVG( image_file_size ) as total_avg_size, COUNT( image_file_size ) as total_uploads', 'from' => 'gallery_images' ) );
		
		//---------------------------------------
		// Global bandwidth stats
		//---------------------------------------

		$stats	= array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth' ) ) );
							
		//---------------------------------------
		// Get diskspace usage by group
		//---------------------------------------

		$this->DB->build( array(
								'select'	=> 'g.g_title, g.g_id',
								'from'		=> array( 'groups' => 'g' ),
								'where'		=> "m.member_group_id={$gid}",
								'group'		=> 'm.member_group_id',
								'add_join'	=> array(
													array(
														'from'	=> array( 'members' => 'm' ),
														'where'	=> 'm.member_group_id=g.g_id',
														'type'		=> 'inner'
														),
													 array(
													 	'select'	=> 'COUNT( i.image_file_size ) as group_uploads, SUM( i.image_file_size ) as group_size, AVG( i.image_file_size ) as group_avg_size',
														'from'		=> array( 'gallery_images' => 'i' ),
														'where'		=> 'i.image_member_id=m.member_id',
														'type'		=> 'inner'
														)
													 )
						 )		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() )
		{
			$row = $this->DB->fetch();
			
			$stats = array_merge( $stats, ( is_array($row) AND count($row) ) ? $row : array() );
		}
		
		//---------------------------------------
		// Get bandwidth usage by group
		//---------------------------------------

		$this->DB->build( array(
								'select'	=> 'g.g_title, g.g_id',
								'from'		=> array( 'groups' => 'g' ),
								'where'		=> "m.member_group_id={$gid}",
								'group'		=> 'm.member_group_id',
								'add_join'	=> array(
													array(
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_group_id=g.g_id',
														'type'		=> 'inner'
														),
													 array(
													 	'select'	=> "SUM( b.bsize ) as group_transfer, COUNT( b.bsize ) as group_viewed",
														'from'		=> array( 'gallery_bandwidth' => 'b' ),
														'where'		=> 'b.member_id=m.member_id',
														'type'		=> 'inner'
														)
													 )
						 )		);
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() )
		{
			$stats = array_merge( $stats, $this->DB->fetch() );
		}
		
		//---------------------------------------
		// Detailed bandwidth stats
		//---------------------------------------

		$bw	= array();
		
		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$bw['title']				= sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] );
		 	$bw['tr_percent']			= $stats['total_transfer']		? ( round( $stats['group_transfer'] / $stats['total_transfer']	, 2 ) * 100 ) . '%' : '0%';
		 	$bw['vi_percent']			= $stats['total_viewed']		? ( round( $stats['group_viewed'] 	/ $stats['total_viewed']	, 2 ) * 100 ) . '%' : '0%';
			$stats['group_transfer']	= IPSLib::sizeFormat( empty($stats['group_transfer']) ? 0 : $stats['group_transfer'] );
		 	$stats['group_viewed']		= intval( $stats['group_viewed'] );
		}
		
		//---------------------------------------
		// Comment statistics
		//---------------------------------------

		$tmp	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as total_comments',
												'from'		=> array( 'groups' => 'g' ),
												'where'		=> "m.member_group_id={$gid}",
												'group'		=> 'm.member_group_id',
												'add_join'	=> array(
																	array(
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_group_id=g.g_id',
																		'type'		=> 'left'
																		),
																	 array(
																	 	'from'		=> array( 'gallery_comments' => 'c' ),
																		'where'		=> 'c.comment_author_id=m.member_id',
																		'type'		=> 'left'
																		)
																	 )
										 )		);

		$comments = intval($tmp['total_comments']);

		//---------------------------------------
		// Rating statistics
		//---------------------------------------

		$rate	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(r.rate_rate) AS total_rates, AVG(r.rate_rate) AS avg_rate',
												'from'		=> array( 'groups' => 'g' ),
												'where'		=> "m.member_group_id={$gid}",
												'group'		=> 'm.member_group_id',
												'add_join'	=> array(
																	array(
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_group_id=g.g_id',
																		'type'		=> 'left'
																		),
																	 array(
																	 	'from'		=> array( 'gallery_ratings' => 'r' ),
																		'where'		=> 'r.rate_member_id=m.member_id',
																		'type'		=> 'left'
																		)
																	 ) 
										)		);
		
		//---------------------------------------
		// Format stats
		//---------------------------------------

		$rate['total_rates']		= intval( $rate['total_rates'] );
		$rate['avg_rate']			= round( $rate['avg_rate'], 2 );
		$stats['group_size']		= IPSLib::sizeFormat( empty($stats['group_size']) ? 0 : $stats['group_size'] );
		$stats['dp_percent']		= $stats['total_size'] 		? ( round( $stats['group_size'] 	/ $stats['total_size']		, 2 ) * 100 ) . '%' : '0%';
		$stats['up_percent']		= $stats['total_uploads'] 	? ( round( $stats['group_uploads'] 	/ $stats['total_uploads']	, 2 ) * 100 ) . '%' : '0%';
		$stats['group_uploads']		= intval( $stats['group_uploads'] );
		$stats['total_avg_size']	= IPSLib::sizeFormat( empty($stats['total_avg_size']) ? 0 : $stats['total_avg_size'] );
		$stats['group_avg_size']	= IPSLib::sizeFormat( empty($stats['group_avg_size']) ? 0 : $stats['group_avg_size'] );
		
		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html .= $this->html->groupFileReport( $stats, $bw, $comments, $rate, sprintf( $this->lang->words['stats_group_result_title'], $this->caches['group_cache'][ $gid ]['g_title'] ) );
	}

	/**
	 * Perform File Search
	 *
	 * @return	@e void
	 */
	public function doFileSearch()
	{
		//---------------------------------------
		// Check search term
		//---------------------------------------

		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11740 );
		}
		
		$search_term = $this->DB->addSlashes( strtolower($this->request['search_term']) );
		
		//---------------------------------------
		// Perform search
		//---------------------------------------

		$this->DB->build( array('select' => '*',
								'from'   => 'gallery_images',
								'where'  => $this->DB->buildLower('image_caption') . " LIKE '%{$search_term}%' OR " . $this->DB->buildLower('image_file_name') . " LIKE '%{$search_term}%'" 
						 )		);
		$outer = $this->DB->execute();
		
		//---------------------------------------
		// Get results count
		//---------------------------------------

		$result_cnt	= $this->DB->getTotalRows();
		
		//---------------------------------------
		// If we have one result, go straight there
		//---------------------------------------

		if( $result_cnt == 1 )
		{
		 	$i	= $this->DB->fetch( $outer );

		 	return $this->viewFileReport( $i['image_id'] );
		}
		else
		{
			//---------------------------------------
			// Get results
			//---------------------------------------

			$rows	= array();

			while( $i = $this->DB->fetch( $outer ) )
			{
				$rows[] = array( 'url' => "{$this->settings['base_url']}{$this->form_code}do=dofilesrch&amp;viewfile={$i['image_id']}", 'name' => "<br /><strong>{$i['image_caption']}</strong><br /><em>{$i['image_file_name']}</em>", 'thumb' => $this->registry->gallery->helper('image')->makeImageLink( $i, array( 'type' => 'thumb' ) ) );
			}

			//---------------------------------------
			// Output
			//---------------------------------------

			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows, 'stats_file_report_title' );
		}
	}
	
	/**
	 *  View Results of a File Search
	 *
	 * @param	integer		File ID
	 * @return	@e void
	 */
	public function viewFileReport( $fid )
	{
		//---------------------------------------
		// Get file
		//---------------------------------------

		$fid	= intval( $fid );
		
		$file	= $this->DB->buildAndFetch( array(
												'select'	=> 'i.*',
												'from'		=> array( 'gallery_images' => 'i' ),
												'where'		=> 'i.image_id=' . $fid,
												'add_join'	=> array(
																		array(	'select'	=> 'm.member_id as mid, m.members_display_name',
																				'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=i.image_member_id',
																				'type'		=> 'left',
																			),
																		array(	'select'	=> 'a.album_name',
																				'from'		=> array( 'gallery_albums' => 'a' ),
																				'where'		=> 'a.album_id=i.image_album_id',
																				'type'		=> 'left',
																			),
																		)
										)	);

		//---------------------------------------
		// Format some of the data
		//---------------------------------------

		$file['image_approved']		= $file['image_approved'] ? $this->lang->words['gbl_yes'] : $this->lang->words['gbl_no'];
		$file['image_file_size']	= IPSLib::sizeFormat( empty($file['image_file_size']) ? 0 : $file['image_file_size'] );
		$file['image_thumbnail']	= $file['image_thumbnail'] ? $this->lang->words['gbl_yes'] : $this->lang->words['gbl_no'];
		$file['image_date']			= $this->registry->class_localization->getDate( $file['image_date'], 'LONG' );

		if( $file['image_album_id'] )
		{
		 	$file['container']	= $file['album_name'];
		 	$file['local_name']	= $this->lang->words['stats_file_album'];
		}
		else
		{
		 	$file['container']	= '<i>' . $this->lang->words['stats_file_unk'] . '</i>';
		 	$file['local_name']	= $this->lang->words['stats_file_unk'];
		}
		
		//---------------------------------------
		// Rating data
		//---------------------------------------

		$rate	= $this->DB->buildAndFetch( array(
												'select'	=> 'AVG( rate_rate ) AS avg_rate, SUM( rate_rate ) AS total_rate',
												'from'		=> 'gallery_ratings',
												'where'		=> "rate_type='image' AND rate_type_id={$file['image_id']}" 
										)		);
										
		$rate['total_rate']	= intval( $rate['total_rate'] );
		$rate['avg_rate']	= $rate['avg_rate'] ? round( $rate['avg_rate'], 2 ) : 0;

		//---------------------------------------
		// Bandwidth data
		//---------------------------------------

		$bw_stats	= array();
		
		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$bandwidth	= $this->DB->buildAndFetch( array(
														'select'	=> 'COUNT( * ) AS views, SUM( bsize ) AS transfer',
														'from'		=> 'gallery_bandwidth',
														'where'		=> "file_name='{$file['image_masked_file_name']}'"
												  )		);
			
			$bw_stats	= array(
								'title'		=> sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] ),
								'views'		=> intval( $bandwidth['views'] ),
								'transfer'	=> IPSLib::sizeFormat( empty($bandwidth['transfer']) ? 0 : $bandwidth['transfer'] )
							  );
		}
		
		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html .= $this->html->statFileReport( $this->registry->gallery->helper('image')->makeImageTag( $file, array( 'type' => 'thumb' ) ), $file, $rate, $bw_stats );		
	}

	/**
	 * Take Action Against a File
	 *
	 * @return	@e void
	 */
	public function doFileAction()
	{
		$this->request['fid']	= intval($this->request['fid']);
		
		//---------------------------------------
		// Change owner?
		//---------------------------------------

		if( $this->request['new_owner'] )
		{
			$member	= $this->DB->buildAndFetch( array( 
													'select'	=> 'member_id', 
													'from'		=> 'members',
													'where'		=> "members_l_display_name='" . $this->DB->addSlashes( strtolower($this->request['new_owner']) ) . "'"
											)	);

			if( ! $member['member_id'] )
			{
				$this->registry->output->showError( $this->lang->words['stats_no_member_found'], 11741 );
			}

			$this->DB->update( 'gallery_images', array( 'image_member_id' => $member['member_id'] ), 'image_id=' . $this->request['fid'] );
		}
		
		//---------------------------------------
		// Delete ratings?
		//---------------------------------------

		if( $this->request['clear_rating'] )
		{
		 	$this->DB->delete( 'gallery_ratings', "rate_type='image' AND rate_type_id=" . $this->request['fid'] );
		 	$this->DB->update( 'gallery_images', array( 'image_ratings_total' => 0, 'image_ratings_count' => 0, 'image_rating' => 0 ), 'image_id=' . $this->request['fid'] );
		}

		//---------------------------------------
		// Clear bandwidth
		//---------------------------------------

		if( $this->request['clear_bandwidth'] )
		{
		 	$i	= $this->DB->buildAndFetch( array(
		 										'select'	=> 'image_masked_file_name',
												'from'		=> 'gallery_images',
												'where'		=> 'image_id=' . $this->request['fid'] 
										)	);

		 	$this->DB->delete( 'gallery_bandwidth', "file_name IN( '{$i['imagemasked_file_name']}', 'tn_{$i['imagemasked_file_name']}' )" );
		}

		//---------------------------------------
		// Redirect
		//---------------------------------------

		$this->registry->output->setMessage( $this->lang->words['stats_actions_taken'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . 'do=dofilesrch&amp;viewfile=' . $this->request['fid'] );
	}
	
	/**
	 *  Load pie chart
	 *
	 * @return	@e void
	 */
	private function _getChart()
	{	
		$chart_data	= array();
		$labels		= array();

		$this->DB->build( array( 
								'select' 	=> '*', 
								'from'		=> 'gallery_bandwidth', 
								'where' 	=> 'bdate > ' . ( time() - ( $this->settings['gallery_bandwidth_period'] * 3600 ) ),
								'order' 	=> 'bdate ASC'
						)	);
		$this->DB->execute();
		
		while( $i = $this->DB->fetch() )
		{
		 	$t_data	= strftime( "%A", $i['bdate'] );
		 
		 	$chart_data[ $t_data ]	+= round( ( $i['bsize'] / 1024 ), 2 );
		 	$labels[ $t_data ]		= $t_data . ' (' . $chart_data[$t_data] . ' kb)';
		}
		
		//-----------------------------------------
		// If no images, don't show chart
		//-----------------------------------------
		
		if( !count($labels) )
		{
			header( "Content-Type: image/gif" );
			print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
			exit;
		}

		//---------------------------------------
		// Output the chart
		//---------------------------------------

		require_once( IPS_KERNEL_PATH . '/classGraph.php' );/*noLibHook*/
		$graph							= new classGraph();
		$graph->options['title']		= sprintf( $this->lang->words['stats_chart_bw_usage'], $this->settings['gallery_bandwidth_period'] );
		$graph->options['width']		= 650;
		$graph->options['height']		= 400;
		$graph->options['style3D']		= 1;
		$graph->options['font']			= IPS_PUBLIC_PATH . 'style_captcha/captcha_fonts/DejaVuSans.ttf';
		$graph->options['charttype']	= 'Pie';
		
		$graph->addLabels( $labels );
		$graph->addSeries( 'test', $chart_data );

		$graph->display();
		exit;
	}
}