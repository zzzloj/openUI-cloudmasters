<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS promote to article functionality
 * Last Updated: $Date: 2011-08-12 11:44:48 -0400 (Fri, 12 Aug 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		11th February 2010
 * @version		$Revision: 9390 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class promoteArticle
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
	}
	
	/**
	 * Check if we should show the promote to article button 
	 *	and return it if so.
	 *
	 * @access	public
	 * @param	int		Post id
	 * @return	string
	 */
	public function getPostHook( $pid )
	{
		//-----------------------------------------
		// Get our groups
		//-----------------------------------------
		
		$myGroups	= array( $this->memberData['member_group_id'] );
		$secondary	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
		$secondary	= explode( ',', $secondary );
		
		if( count($secondary) )
		{
			$myGroups	= array_merge( $myGroups, $secondary );
		}
				
		//-----------------------------------------
		// Are we online, or can we access offline?
		//-----------------------------------------
		
		if( !$this->settings['ccs_online'] )
		{
			$show		= false;
			
			if( $this->settings['ccs_offline_groups'] )
			{
				$groups		= explode( ',', $this->settings['ccs_offline_groups'] );
				
				foreach( $myGroups as $groupId )
				{
					if( in_array( $groupId, $groups ) )
					{
						$show	= true;
						break;
					}
				}
			}
			
			if( !$show )
			{
				return '';
			}
		}
		
		//-----------------------------------------
		// Allowing post promotion?
		//-----------------------------------------
		
		if( !$this->settings['ccs_promote'] )
		{
			return '';
		}
		
		//-----------------------------------------
		// Are we allowed to copy or move?
		//-----------------------------------------
		
		$_copy	= explode( ',', $this->settings['ccs_promote_g_copy'] );
		$_move	= explode( ',', $this->settings['ccs_promote_g_move'] );
		$_allow	= false;
		
		foreach( $myGroups as $groupId )
		{
			if ( !$groupId )
			{
				continue;
			}
			
			if( in_array( $groupId, $_copy ) )
			{
				$_allow	= true;
				break;
			}

			if( in_array( $groupId, $_move ) )
			{
				$_allow	= true;
				break;
			}
		}
				
		if( !$_allow )
		{
			return '';
		}

		//-----------------------------------------
		// Online, promotion enabled, and we can move or copy...
		// show button now
		//-----------------------------------------
		
		return $this->registry->output->getTemplate('ccs_global')->promoteToArticle( $pid );
	}
}