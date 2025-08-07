<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_stats extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
		/* Setup output & library shortcuts */
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		
		/* Check view permissions */
		$this->library->checkPermissions();

		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos' ) );
		
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
		$this->registry->output->addNavigation( $this->lang->words['adv_stats'], "" );
		$this->library->pageTitle .= $this->lang->words['adv_stats'];
		
		/* Init */
		$tables = array();

		/* Init */
		$rows = array();
		$rank = 0;
		
		/* Set the data for this stats table */
		$data = array( 'title' => $this->lang->words['stats_newest'],
					   'stat'  => $this->lang->words['date_added'],
					   'none'  => $this->lang->words['stats_noadded'],
					 );
		
		/* Query */
		$this->DB->build( array( 'select'   => 't.t_id, t.t_title, t.t_title_seo, t.t_date, t_member_id',
								 'from'     => array( 'testemunhos' => 't' ),
								 'where'    => "t.t_approved = 1 AND t_date > 0",
								 'add_join' => array( 0 => array( 'select' => 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
																  'from'   => array( 'members' => 'm' ),
																  'where'  => 'm.member_id=t.t_member_id',
																  'type'   => 'left' ) ),
								 'order'    => 't.t_date DESC',
								 'limit'    => array( 0, $this->settings['testemunhos_top'] ),
						)	   );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$rows[] = array( 'rank'              => ++$rank,
							 't_id'       		 => $row['t_id'],
							 't_title'     		 => $row['t_title'],
							 't_title_seo' 		 => $row['t_title_seo'],
							 't_member_id'       => $row['t_member_id'],
							 't_member_name'     => $row['members_display_name'],
							 't_member_name_seo' => $row['members_seo_name'],
							 'member_group_id'   => $row['member_group_id'],
							 'stat'              => $row['t_date'],
							 'stat_type'         => 'date',
						   );
		}
		
		/* Add this to our array */
		$tables[] = array( 'data'      => $data,
						   'rows'      => $rows,
						   'new_row'   => 1,
						   'add_space' => 0,
						   'wide'      => 1,
						 );

		/* Mais Comentados */
		/* Init */
		$rows = array();
		$rank = 0;
		
		/* Set the data for this stats table */
		$data = array( 'title' => $this->lang->words['stats_morecomm'],
					   'stat'  => $this->lang->words['stats_comm'],
					   'none'  => $this->lang->words['stats_noadded'],
					 );
		
		/* Query */
		$this->DB->build( array( 'select'   => 't.t_id, t.t_title, t.t_title_seo, t.t_date, t_member_id, t_comments',
								 'from'     => array( 'testemunhos' => 't' ),
								 'where'    => "t.t_approved = 1 AND t_comments > 0",
								 'add_join' => array( 0 => array( 'select' => 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
																  'from'   => array( 'members' => 'm' ),
																  'where'  => 'm.member_id=t.t_member_id',
																  'type'   => 'left' ) ),
								 'order'    => 't.t_comments DESC',
								 'limit'    => array( 0, $this->settings['testemunhos_top'] ),
						)	   );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$rows[] = array( 'rank'              => ++$rank,
							 't_id'       		 => $row['t_id'],
							 't_title'     		 => $row['t_title'],
							 't_title_seo' 		 => $row['t_title_seo'],
							 't_member_id'       => $row['t_member_id'],
							 't_member_name'     => $row['members_display_name'],
							 't_member_name_seo' => $row['members_seo_name'],
							 'member_group_id'   => $row['member_group_id'],
							 'stat'              => $row['t_comments'],
							 'stat_type'         => 'comm',
						   );
		}
		
		/* Add this to our array */
		$tables[] = array( 'data'      => $data,
						   'rows'      => $rows,
						   'new_row'   => 1,
						   'add_space' => 0,
						   'wide'      => 1,
						 );

		/* Init */
		$rows = array();
		$rank = 0;
		
		/* Set the data for this stats table */
		$data = array( 'title' => $this->lang->words['stats_mostviewed'],
					   'stat'  => $this->lang->words['views'],
					   'none'  => $this->lang->words['stats_noadded'],
					 );
		
		/* Query */
		$this->DB->build( array( 'select'   => 't.t_id, t.t_title, t.t_title_seo, t.t_views, t_member_id',
								 'from'     => array( 'testemunhos' => 't' ),
								 'where'    => "t.t_approved = 1 AND t_views > 0",
								 'add_join' => array( 0 => array( 'select' => 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
																  'from'   => array( 'members' => 'm' ),
																  'where'  => 'm.member_id=t.t_member_id',
																  'type'   => 'left' ) ),
								 'order'    => 't.t_views DESC',
								 'limit'    => array( 0, $this->settings['testemunhos_top'] ),
						)	   );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$rows[] = array( 'rank'              => ++$rank,
							 't_id'       		 => $row['t_id'],
							 't_title'     		 => $row['t_title'],
							 't_title_seo' 		 => $row['t_title_seo'],
							 't_member_id'       => $row['t_member_id'],
							 't_member_name'     => $row['members_display_name'],
							 't_member_name_seo' => $row['members_seo_name'],
							 'member_group_id'   => $row['member_group_id'],
							 'stat'              => $row['t_views'],
							 'stat_type'         => 'int',
						   );
		}
		
		/* Add this to our array */
		$tables[] = array( 'data'      => $data,
						   'rows'      => $rows,
						   'new_row'   => 1,
						   'add_space' => 0,
						   'wide'      => 1,
						 );
		
		/* Output */
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->testemunhosStats( $tables );
		
		$this->library->sendOutput();
	}
}