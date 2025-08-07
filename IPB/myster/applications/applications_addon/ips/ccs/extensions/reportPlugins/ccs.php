<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Report Center :: Content plugin
 * Last Updated: $LastChangedDate: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ccs_plugin
{
	/**#@+
	 * Registry objects
	 *
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Holds extra data for the plugin
	 *
	 * @var		array			Data specific to the plugin
	 */
	public $_extra;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang		= $this->registry->class_localization;
		
		//-----------------------------------------
		// Load up ccsFunctions
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'ccs' );
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	string		HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		return '';
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @param	array 		Plugin data for save
	 * @return	string		Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		return true;
	}
	
	/**
	 * Get report permissions
	 *
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		else
		{
			//-----------------------------------------
			// Check for a database moderator
			//-----------------------------------------
		
			$moderators	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$moderators[]	= $r;
			}
			
			if ( ! is_array( $moderators ) )
			{
				return false;
			}
			
			$database_ids = array();

			foreach( $moderators as $mod )
			{
				if( $this->memberData['member_id'] AND $mod['moderator_type'] == 'member' AND $mod['moderator_type_id'] == $this->memberData['member_id'] )
				{
					$database_ids['exdat1'][ $mod['moderator_database_id'] ]	= $mod['moderator_database_id'];
				}
				elseif( $mod['moderator_type'] == 'group' && in_array( $mod['moderator_type_id'], $group_ids ) == true )
				{
					$database_ids['exdat1'][ $mod['moderator_database_id'] ]	= $mod['moderator_database_id'];
				}
			}

			if( count( $database_ids ) )
			{
				$to_return = $database_ids;

				return true;
			}
			else
			{
				return false;
			}
		}
	}
	
	/**
	 * Show the report form for this module
	 *
	 * @param 	array 		Application data
	 * @return	string		HTML form information
	 */
	public function reportForm( $com_dat )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		$database	= intval($this->request['database']);
		$record		= intval($this->request['record']);
		$comment	= intval($this->request['comment_id']);
		
		if( ! $record )
		{
			$this->registry->output->showError( 'reports_no_record', 108700.1, false, null, 404 );
		}

		//-----------------------------------------
		// Load the record
		//-----------------------------------------

	 	$databases	= $this->cache->getCache('ccs_databases');
	 	
	 	if( count($databases) )
	 	{
	 		$_database	= $this->caches['ccs_databases'][ $database ];
	 		
	 		if( $_database['database_database'] AND $this->registry->permissions->check( 'view', $_database ) == true AND
			 	$this->registry->permissions->check( 'show', $_database ) == true )
	 		{
				$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $_database['database_database'], 'where' => 'primary_id_field=' . $record ) );
				
				if( $_record['category_id'] )
				{
					$category	= $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_record['category_id'] ];

					if( !$category['category_id'] )
					{
						$_record	= array();
					}
					else if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
					{
						$_record	= array();
					}
					else
					{
						$_parents	= $this->registry->ccsFunctions->getCategoriesClass( $_database )->getParents( $_record['category_id'] );
						
						if( count($_parents) )
						{
							foreach( $_parents as $_parent )
							{
								if ( !$this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ]['category_id'] OR ( $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ] ) != TRUE ) )
								{
									$_record	= array();
								}
							}
						}
					}
				}
	 		}
	 	}
	 	
		//-----------------------------------------
		// Do we have the record?
		//-----------------------------------------
		
	 	if( !$_record['primary_id_field'] )
	 	{
	 		$this->registry->output->showError( 'reports_no_record', 108700.2, false, null, 404 );
	 	}
	 	
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $database );
		
		//-----------------------------------------
		// Get title
		//-----------------------------------------
		
	 	$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
	 	$fields			= array();
	 	
		if( is_array($this->cache->getCache('ccs_fields')) AND is_array($this->caches['ccs_fields'][ $_database['database_id'] ]) AND count($this->caches['ccs_fields'][ $_database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $_database['database_id'] ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
			}
		}
	 	
	 	$title	= $fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $_database['database_field_title'] ) ], $_record, $fields[ str_replace( 'field_', '', $_database['database_field_title'] ) ]['field_truncate'] );

		//-----------------------------------------
		// Format data for the form
		//-----------------------------------------
		
		$ex_form_data = array(
								'database'		=> $database,
								'record'		=> $record,
								'comment_id'	=> $comment,
								'ctyp'			=> 'ccs',
								'title'			=> $comment ? sprintf( $this->lang->words['rc_comment_pre'], $title ) : $title,
							);
		
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database, 0, $_record, $comment );
		
		$this->registry->output->setTitle( $comment ? $this->lang->words['report_record_pagec'] : $this->lang->words['report_record_page'] );
		$this->registry->output->addNavigation( $comment ? sprintf( $this->lang->words['rc_comment_pre'], $title ) : $title, $url, '', '', 'none' );
		$this->registry->output->addNavigation( $comment ? $this->lang->words['report_record_pagec'] : $this->lang->words['report_record_page'], '' );
		
		$this->lang->words['report_basic_title']		= $this->lang->words['report_record_title'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_record_title'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_record_msg'];

		return $this->registry->getClass('reportLibrary')->showReportForm( $comment ? sprintf( $this->lang->words['rc_comment_pre'], $title ) : $title, $url, $ex_form_data );
	}

	/**
	 * Get section and link
	 *
	 * @param 	array 		Report data
	 * @return	array 		Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
	 	$databases	= $this->cache->getCache('ccs_databases');
	 	
	 	if( count($databases) )
	 	{
	 		$_database	= $this->caches['ccs_databases'][ intval($report_row['exdat1']) ];
		}

		return array(
					'title'			=> $_database['database_name'],
					'url'			=> $this->registry->ccsFunctions->returnDatabaseUrl( intval($report_row['exdat1']), 0,  intval($report_row['exdat2']), intval($report_row['exdat3']) ),
					);
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @param 	array 		Report data
	 * @return	array 		Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		$database	= intval($this->request['database']);
		$record		= intval($this->request['record']);
		$comment	= intval($this->request['comment_id']);
		
		if( ! $record )
		{
			$this->registry->output->showError( 'reports_no_record', 108700.3, false, null, 404 );
		}

		//-----------------------------------------
		// Load the record
		//-----------------------------------------

	 	$databases	= $this->cache->getCache('ccs_databases');
	 	
	 	if( count($databases) )
	 	{
	 		$_database	= $this->caches['ccs_databases'][ $database ];
	 		
	 		if( $_database['database_database'] AND $this->registry->permissions->check( 'view', $_database ) == true AND
			 	$this->registry->permissions->check( 'show', $_database ) == true )
	 		{
				$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $_database['database_database'], 'where' => 'primary_id_field=' . $record ) );
				
				if( $_record['category_id'] )
				{
					$category	= $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_record['category_id'] ];

					if( !$category['category_id'] )
					{
						$_record	= array();
					}
					else if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
					{
						$_record	= array();
					}
					else
					{
						$_parents	= $this->registry->ccsFunctions->getCategoriesClass( $_database )->getParents( $_record['category_id'] );
						
						if( count($_parents) )
						{
							foreach( $_parents as $_parent )
							{
								if ( !$this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ]['category_id'] OR ( $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->registry->ccsFunctions->getCategoriesClass( $_database )->categories[ $_parent ] ) != TRUE ) )
								{
									$_record	= array();
								}
							}
						}
					}
				}
	 		}
	 	}
	 	
		//-----------------------------------------
		// Do we have the record?
		//-----------------------------------------
		
	 	if( !$_record['primary_id_field'] )
	 	{
	 		$this->registry->output->showError( 'reports_no_record', 108700.4, false, null, 404 );
	 	}
	 	
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $database );
		
		//-----------------------------------------
		// Get URL and title
		//-----------------------------------------
		
	 	$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
	 	$fields			= array();
	 	
		if( is_array($this->cache->getCache('ccs_fields')) AND is_array($this->caches['ccs_fields'][ $_database['database_id'] ]) AND count($this->caches['ccs_fields'][ $_database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $_database['database_id'] ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
			}
		}
	 	
	 	$title	= $fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $_database['database_field_title'] ) ], $_record, $fields[ str_replace( 'field_', '', $_database['database_field_title'] ) ]['field_truncate'] );
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database, 0, $_record, $comment );
		
		//-----------------------------------------
		// Start formatting RC info
		//-----------------------------------------
		
		$return_data	= array();
		$uid			= md5(  'ccs_' . $database . $record . $comment . '_' . $com_dat['com_id'] );
		$status			= array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> "is_new=1 OR is_complete=1",
						) 		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->request['title'],
										'status'		=> $status['new'],
										'url'			=> $url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> IPS_UNIX_TIME_NOW,
										'date_created'	=> IPS_UNIX_TIME_NOW,
										'exdat1'		=> $database,
										'exdat2'		=> $record,
										'exdat3'		=> $comment
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
		
		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> IPS_UNIX_TIME_NOW,
							);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$return_data = array( 
							'REDIRECT_URL'	=> $url,
							'REPORT_INDEX'	=> $rid,
							'SAVED_URL'		=> $url,
							'REPORT'		=> $build_report['report'],
							'SEOTITLE'		=> '',
							'TEMPLATE'		=> '',
							'DATABASE_ID'	=> $database,
							);
		
		return $return_data;
	}

	/**
	 * Accepts an array of data from rc_reports_index and returns an array formatted nearly identical to processReport()
	 *
	 * @param 	array 		Report data
	 * @return	array 		Formatted report data
	 */
	public function formatReportData( $report_data )
	{
		return array(
					'REDIRECT_URL'	=> $this->registry->ccsFunctions->returnDatabaseUrl( $report_data['exdata1'], 0, $report_data['exdata2'], $report_data['exdata3'] ),
					'REPORT_INDEX'	=> $report_data['id'],
					'SAVED_URL'		=> $this->registry->ccsFunctions->returnDatabaseUrl( $report_data['exdata1'], 0, $report_data['exdata2'], $report_data['exdata3'] ),
					'REPORT'		=> '',
					'SEOTITLE'		=> '',
					'TEMPLATE'		=> '',
					'DATABASE_ID'	=> $report_data['exdata1'],
					);
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @param 	array 		Report data
	 * @return	@e void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'],  $report_data['REDIRECT_URL'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @param 	string 		Group ids
	 * @param 	array 		Report data
	 * @return	array 		Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		//-----------------------------------------
		// Build where for secondary member groups
		//-----------------------------------------
		
		$secondaryWhere	= array();
		
		if( is_array($group_ids) AND count($group_ids) )
		{
			foreach( $group_ids as $group_id )
			{
				$secondaryWhere[]	= "m.mgroup_others LIKE '%,{$group_id},%'";
			}
		}
		
		$this->DB->build( array(
								'select'	=> 'm.member_id as real_member_id, m.members_display_name as name, m.language, m.members_disable_pm, m.email, m.member_group_id',
								'from'		=> array( 'members' => 'm' ),
								'where'		=> "(m.member_group_id IN(" . $group_ids . ") " . ( count($secondaryWhere) ? "OR " . implode( ' OR ', $secondaryWhere ) : '' ) . ") AND (g.g_is_supmod=1 OR g.g_access_cp=1 OR moderator.moderator_database_id='{$report_data['DATABASE_ID']}')",
								'add_join'	=> array(
													array(
														'select'	=> 'moderator.moderator_type, moderator.moderator_type_id',
														'from'		=> array( 'ccs_database_moderators' => 'moderator' ),
														'where'		=> "( moderator.moderator_type='member' AND moderator.moderator_type_id=m.member_id ) OR ( moderator.moderator_type='group' AND moderator.moderator_type_id=m.member_group_id )",
														),
													array(
														'from'		=> array( 'groups' => 'g' ),
														'where'		=> 'g.g_id=m.member_group_id',
														'type'		=> 'left',
														),
													)
							)		);
		$this->DB->execute();

		$notify		= array();

		if ( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				$r['member_id']	= $r['real_member_id'];
				
				$notify[ $r['member_id'] ] = $r;
			}
		}

		return $notify;
	}
}