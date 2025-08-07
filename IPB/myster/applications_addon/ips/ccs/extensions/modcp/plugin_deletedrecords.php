<?php
/**
 * @file		plugin_deletedrecords.php 	Moderator control panel plugin: show CCS records that are hidden
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
 * @class		plugin_ccs_deletedrecords
 * @brief		Moderator control panel plugin: show CCS records that are hidden
 */
class plugin_ccs_deletedrecords
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
		return 'deletedrecords';
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
	 * Retrieve the database info we are checking
	 *
	 * @return	@e bool
	 */
	protected function getDatabase()
	{
		if( $this->request['database'] AND in_array( $this->request['database'], $this->databases ) )
		{
			return $this->caches['ccs_databases'][ $this->request['database'] ];
		}
		else
		{
			$_db	= $this->databases[0];

			return $this->caches['ccs_databases'][ $_db ];
		}
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
		// Get records pending approval
		//----------------------------------
		
		$database		= $this->getDatabase();
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		$fields			= $this->cache->getCache('ccs_fields');
		$results		= array();

		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $database['database_database'] => 'r' ),
								'where'		=> "r.record_approved=-1",
								'add_join'	=> array(
													array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=r.member_id',
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
			$row['_title']	= $fieldsClass->getFieldValue( $fields[ $database['database_id'] ][ str_replace( 'field_', '', $database['database_field_title'] ) ], $row, $fields[ $database['database_id'] ][ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] );
			$row['_desc']	= $fieldsClass->getFieldValue( $fields[ $database['database_id'] ][ str_replace( 'field_', '', $database['database_field_content'] ) ], $row, $fields[ $database['database_id'] ][ str_replace( 'field_', '', $database['database_field_content'] ) ]['field_truncate'] );
			$row['_isRead']	= $this->registry->classItemMarking->isRead( array( 'catID' => $row['category_id'], 'itemID' => $row['primary_id_field'], 'itemLastUpdate' => $row['record_updated'] ), 'ccs' );
			
			$results[] = IPSMember::buildDisplayData( $row );
		}
		
		return $this->registry->getClass('output')->getTemplate('ccs_global')->modcpRecords( $results, $database, $this->databases, 'deleted' );
	}
	
	/**
	 * Get databases we can approve content from
	 *
	 * @return	@e string
	 */
	protected function _getDatabases()
	{
		$databases	= array();
		$cached		= $this->cache->getCache('ccs_databases');
		
		if( $this->memberData['g_is_supmod'] )
		{
			$databases = array_keys( $cached );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_delete_record=1' ) );
			$outer	= $this->DB->execute();

			while( $r = $this->DB->fetch($outer) )
			{
				/* Can we view? */
				if( !$this->registry->permissions->check( 'view', $cached[ $r['moderator_database_id'] ] ) )
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