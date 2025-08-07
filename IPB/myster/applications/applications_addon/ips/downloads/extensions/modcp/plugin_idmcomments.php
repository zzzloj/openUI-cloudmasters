<?php
/**
 * Invision Power Services
 * IP.Downloads - Unapproved Comments Extension
 * Last Updated: $Date: 2011-10-21 06:20:03 -0400 (Fri, 21 Oct 2011) $
 *
 * @author 		$Author: bfarber $ (Orginal: bfarber)
 * @copyright	© 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		5th October 2011
 * @version		$Revision: 9660 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_downloads_idmcomments
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
		
		ipsRegistry::getAppClass( 'downloads' );
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
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'idmcomments';
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
		// Get Comments Pending Approval
		//----------------------------------
		
		IPSText::getTextClass('bbcode')->parsing_section		= 'idm_submit';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];

		$limiter	= $this->_cats == '*' ? '' : " AND f.file_cat IN({$this->_cats})";

		$this->DB->build( array(
								'select'	=> 'c.*',
								'from'		=> array( 'downloads_comments' => 'c' ),
								'where'		=> "c.comment_open=0" . $limiter,
								'add_join'	=> array(
													array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=c.comment_mid',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'm.member_id=pp.pp_member_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'f.*',
															'from'		=> array( 'downloads_files' => 'f' ),
															'where'		=> 'f.file_id=c.comment_fid',
															'type'		=> 'left',
														),
													)
							)		);
		$e = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $e ) )
		{
			$row['comment_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_text'] );
			
			$row = $row['member_id'] ? IPSMember::buildDisplayData( $row ) : array_merge( $row, IPSMember::setUpGuest(), IPSMember::buildNoPhoto( 0 ) );
			
			$results[] = $row;
		}
						
		return $this->registry->getClass('output')->getTemplate('downloads_external')->unapprovedComments( $results );
	}

	/**
	 * Get categories we can approve files in
	 *
	 * @return	@e string
	 */
	protected function _getCats()
	{
		$appcats = '';
		
		if( $this->memberData['g_is_supmod'] )
		{
			$appcats = '*';
		}
		else
		{
			if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['modcancomments'] )
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
						if( $v['modcancomments'] )
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