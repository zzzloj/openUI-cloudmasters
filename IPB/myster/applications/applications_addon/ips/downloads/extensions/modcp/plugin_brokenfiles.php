<?php
/**
 * @file		plugin_brokenfiles.php 	Moderator control panel plugin: show IDM files reported broken
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		2/23/2011
 * $LastChangedDate: 2011-11-07 16:31:54 -0500 (Mon, 07 Nov 2011) $
 * @version		v2.5.4
 * $Revision: 9779 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_downloads_brokenfiles
 * @brief		Moderator control panel plugin: show IDM files reported broken
 */
class plugin_downloads_brokenfiles
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
	 * Cat permissions
	 *
	 * @var	string
	 */
	protected $_cats	= '';

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
		
		ipsRegistry::getAppClass( 'downloads' );
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'brokefiles';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'brokenfiles';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		$this->_cats	= $this->_getCats();
		
		if( $this->_cats )
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
		// Get Files Pending Approval
		//----------------------------------
		
		$limiter	= $this->_cats == '*' ? '' : " AND f.file_cat IN({$this->_cats})";
		$results	= array();

		$this->DB->build( array(
									'select'	=> 'f.*',
									'from'		=> array( 'downloads_files' => 'f' ),
									'where'		=> "f.file_broken=1" . $limiter,
									'add_join'	=> array(
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=f.file_submitter',
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
			$row['_isRead']	= $this->registry->classItemMarking->isRead( array( 'forumID' => $row['file_cat'], 'itemID' => $row['file_id'], 'itemLastUpdate' => $row['file_updated'] ), 'downloads' );
			
			$results[] = IPSMember::buildDisplayData( $row );
		}
		
		return $this->registry->getClass('output')->getTemplate('downloads_other')->moderatorPanel( 'broken', $results );
	}
	
	/**
	 * Get categories we can approve files in
	 *
	 * @return	@e string
	 */
	protected function _getCats()
	{
		$appcats	= '';

		if( $this->memberData['g_is_supmod'] )
		{
			$appcats 	= '*';
		}
		else
		{
			if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['modcats'];
							}
							else
							{
								$appcats = $v['modcats'];
							}
						}
					}
				}
			}
			else if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['modcats'];
							}
							else
							{
								$appcats = $v['modcats'];
							}
						}
					}
				}
			}
		}
		
		return $appcats;
	}
}