<?php
/**
 * @file		plugin_ccsdeletedcomments.php 	Moderator control panel plugin: show CCS comments that are hidden
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		1/18/2012
 * $LastChangedDate: 2011-11-07 16:31:54 -0500 (Mon, 07 Nov 2011) $
 * @version		v3.4.5
 * $Revision: 9779 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_ccs_ccsdeletedcomments
 * @brief		Moderator control panel plugin: show CCS comments that are hidden
 */
class plugin_ccs_ccsdeletedcomments
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	
	/**
	 * Database IDs we can moderate
	 *
	 * @var	array
	 */
	protected $databases	= array();

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		//-----------------------------------------
		// Other stuff
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'ccs' );

		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'deleted_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'ccsdeletedcomments';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		$this->databases	= $this->_getDatabases();
		
		if( count($this->databases) )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->canView( $permissions ) )
		{
			return '';
		}
		
		//----------------------------------
		// Get comments pending approval
		//----------------------------------

		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		$fields			= $this->cache->getCache('ccs_fields');
		$results		= array();
		$recordLookup	= array();
		$commentLookup	= array();

		IPSText::getTextClass('bbcode')->parsing_section		= 'ccs_comment';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];

		$this->DB->build( array(
								'select'	=> 'c.*',
								'from'		=> array( 'ccs_database_comments' => 'c' ),
								'where'		=> "c.comment_approved=-1 AND c.comment_database_id IN(" . implode( ',', $this->databases ) . ")",
								'add_join'	=> array(
													array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=c.comment_user',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'm.member_id=pp.pp_member_id',
															'type'		=> 'left',
														),
													)
							)		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['comment_post']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_post'] );

			$results[ $row['comment_id'] ] = IPSMember::buildDisplayData( $row );
			$recordLookup[ $row['comment_database_id'] ][ $row['comment_record_id'] ]	= array( $row['comment_record_id'] );
			$commentLookup[ $row['comment_id'] ]	= $row['comment_record_id'];
		}

		//----------------------------------
		// Get record data
		//----------------------------------

		foreach( $recordLookup as $dbId => $records )
		{
			$_loadIds	= array_keys( $records );

			$this->DB->build( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $dbId ]['database_database'], 'where' => "primary_id_field IN(" . implode( ',', $_loadIds ) . ")" ) );
			$inner	= $this->DB->execute();

			while( $r = $this->DB->fetch($inner) )
			{
				$records[ $r['primary_id_field'] ][1]	= $fieldsClass->getFieldValue( $fields[ $dbId ][ str_replace( 'field_', '', $this->caches['ccs_databases'][ $dbId ]['database_field_title'] ) ], $r, $fields[ $dbId ][ str_replace( 'field_', '', $this->caches['ccs_databases'][ $dbId ]['database_field_title'] ) ]['field_truncate'] );
				$records[ $r['primary_id_field'] ][2]	= $this->registry->ccsFunctions->returnDatabaseUrl( $dbId, 0, $r );
			}

			foreach( $commentLookup as $commentId => $recordId )
			{
				if( $results[ $commentId ]['comment_database_id'] == $dbId )
				{
					$results[ $commentId ]['_title']		= $records[ $recordId ][1];
					$results[ $commentId ]['_url']			= $records[ $recordId ][2];
					$results[ $commentId ]['_databaseName']	= $this->caches['ccs_databases'][ $dbId ]['database_name'];
					$results[ $commentId ]['_databaseId']	= $dbId;
					$results[ $commentId ]['_databaseUrl']	= $this->registry->ccsFunctions->returnDatabaseUrl( $dbId );
				}
			}
		}

		$deleteReasons	= array();

		if ( is_array( $results ) AND count( $results ) )
		{
			$deleteReasons	= IPSDeleteLog::fetchEntries( array_keys($results), 'ccs-records', false );
		}

		if( count($deleteReasons) )
		{
			foreach( $results as $id => $data )
			{
				$results[ $id ]['_deleteData']	= $deleteReasons[ $id ];
			}
		}

		return $this->registry->getClass('output')->getTemplate('ccs_global')->unapprovedComments( $results, 'deleted' );
	}
	
	/**
	 * Get databases we can approve comments from
	 *
	 * @return	@e string
	 */
	protected function _getDatabases()
	{
		$databases	= array();
		$cached		= array();

		foreach( $this->cache->getCache('ccs_databases') as $database )
		{
			if( trim( $database['perm_5'], ' ,' ) )
			{
				$cached[ $database['database_id'] ]	= $database;
			}
		}
		
		if( $this->memberData['g_is_supmod'] )
		{
			$databases = array_keys( $cached );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_delete_comment=1' ) );
			$outer	= $this->DB->execute();

			while( $r = $this->DB->fetch($outer) )
			{
				/* Can we view? */
				if( !$cached[ $r['moderator_database_id'] ]['database_id'] OR !$this->registry->permissions->check( 'view', $cached[ $r['moderator_database_id'] ] ) )
				{
					continue;
				}

				/* Are we a moderator? */
				if( $r['moderator_type'] == 'member' AND $r['moderator_type_id'] == $this->memberData['member_id'] )
				{
					$databases[]	= $r['moderator_database_id'];
				}
				else if( $r['moderator_type'] == 'group' AND IPSMember::isInGroup( $this->memberData, $r['moderator_type_id'] ) )
				{
					$databases[]	= $r['moderator_database_id'];
				}
			}
		}

		return $databases;
	}
}