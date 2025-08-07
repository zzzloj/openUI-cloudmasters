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
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class testimonials_plugin
{
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	public $_extra;
	
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->lang		  =  $this->registry->class_localization;
	}
	
	public function displayAdminForm( $plugin_data, &$html )
	{
		/* Nothing special here... */
		return;
	}
	
	public function processAdminForm( &$save_data_array )
	{
		/* ... Or here */
		return;
	}
	
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		$nmc =& $new_members_cache['report_temp']['testemunhos_marker'];

		foreach( $new_reports as $report )
		{
			if( $report['is_new'] == 1 || $report['is_active'] == 1 )
			{
				$nmc['testemunhos'][ $report['exdat1'] ]['info']	= array( 'id' => $report['id'], 'title' => $report['title'], 'com_id' => $report['com_id'] );
			}
			
			if( $report['is_new'] == 1 )
			{
				$nmc['testemunhos'][ $report['exdat1'] ]['gfx']	= 1;
			}
			elseif( $report['is_active'] == 1 )
			{
				$nmc['testemunhos'][ $report['exdat1'] ]['gfx']	= 2;
			}
		}
	}
	
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if ( $this->memberData['g_access_cp'] )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function reportForm( $com_dat )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
		
		$testemunho = intval( $this->request['id'] );
		$comment = intval( $this->request['comment'] );
		
		if ( !$testemunho )
		{
			$this->registry->output->showError( 'reports_no_testemunho' );
		}
		
		$test = $this->DB->buildAndFetch( array( 'select' => 't_title', 'from' => 'testemunhos', 'where' => 't_id=' . intval( $testemunho ) ) );
		
		$ex_form_data = array( 'id' => $testemunho );
		
		if ( $comment )
		{
			$ex_form_data['comment'] = $comment;
			$ex_form_data['title']   = $test['t_title']." (".$this->lang->words['comment']." #{$comment})";
			$url = $this->settings['base_url'] . "app=testimonials&amp;module=testimonials&amp;section=findpost&amp;id=" . $comment;
			$t['t_title'] = $ex_form_data['title'];
		}
		else
		{
			$ex_form_data['title'] = $test['t_title'];
			$url = $this->settings['base_url'] . "app=testimonials&amp;showtestimonial=" . $testemunho;
			$t['t_title'] = $ex_form_data['title'];
		}
		
		$this->lang->words['denunciar_testemunho'] = $comment ? $this->lang->words['denunciar_comentario'] : $this->lang->words['denunciar_testemunho'];
		
		$this->registry->output->setTitle( $this->lang->words['denunciar_testemunho'] );
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], "app=testimonials" );
		$this->registry->output->addNavigation( $this->lang->words['denunciar_testemunho'], '' );
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $t['t_title'], $url, $ex_form_data );
	}
	
	public function giveSectionLinkTitle( $report_row )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_external' ), 'tutorials' );
		
		return array( 'title' => "Testimonials",
					  'url'   => "/index.php?app=testimonials",
					);
	}
	
	public function processReport( $com_dat )
	{
		$testemunho = intval( $this->request['id'] );
		//$comment = intval( $this->request['comment'] );
		$url     = "app=testimonials&showtestimonial=" . $testemunho;
		
		if ( $testemunho < 1 )
		{
			$this->registry->output->showError( 'reports_no_article', '10TUT082' );
		}
		
		$return_data = array();
		$a_url       = str_replace( "&", "&amp;", $url );
		$uid         = md5( $url . '_' . $com_dat['com_id'] );
		$status      = array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> "is_new=1 OR is_complete=1",
						) 	   );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			if ( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			else if ( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if ( $this->DB->getTotalRows() == 0 )
		{
			$built_report_main = array( 'uid'          => $uid,
										'title'        => $this->request['title'],
										'status'       => $status['new'],
										'url'          => '/index.php?' . $a_url,
										'rc_class'     => $com_dat['com_id'],
										'updated_by'   => $this->memberData['member_id'],
										'date_updated' => time(),
										'date_created' => time(),
										'exdat1'       => $testemunho,
										'exdat2'       => $comment,
									  );

			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report	= $this->DB->fetch();
			$rid		= $the_report['id'];

			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'] ), "id='{$rid}'" );
		}
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
				
		$build_report = array( 'rid'           => $rid,
							   'report'        => IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
							   'report_by'     => $this->memberData['member_id'],
							   'date_reported' => time(),
							 );
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$return_data = array( 'REDIRECT_URL' => $a_url,
							  'REPORT_INDEX' => $rid,
							  'SAVED_URL'    => '/index.php?' . $url,
							  'REPORT'       => $build_report['report']
							);
		
		return $return_data;
	}
	
	public function reportRedirect( $report_data )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_reports' ), 'core' );
		
		$this->registry->output->redirectScreen( $this->lang->words['submitting_report'], $this->settings['base_url'] . $report_data['REDIRECT_URL'] );
	}

	public function getNotificationList( $group_ids, $report_data )
	{
		$notify = array();
		
		$this->DB->build( array( 'select'   => 'noti.*',
								 'from'     => array( 'rc_modpref' => 'noti' ),
								 'where'    => 'mem.member_group_id IN(' . $group_ids . ')',
								 'add_join'	=> array( 0 => array( 'select' => 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
																  'from'   => array( 'members' => 'mem' ),
																  'where'  => 'mem.member_id=noti.mem_id', ) ),
						)		);
		$this->DB->execute();
		
		if ( $this->DB->getTotalRows() > 0 )
		{
			while ( $row = $this->DB->fetch() )
			{
				if ( $row['by_pm'] == 1 )
				{
					$notify['PM'][] = $row;
				}
				if ( $row['by_email'] == 1 )
				{
					$notify['EMAIL'][] = $row;
				}
				
				$notify['RSS'][] = $row;
			}	
		}
		
		return $notify;
	}
}