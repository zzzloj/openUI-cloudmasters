<?php
/**
 * @file		version_upgrade.php 	IP.Download Manager version upgrader
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		1st December 2009
 * $LastChangedDate: 2011-05-26 13:03:06 -0400 (Thu, 26 May 2011) $
 * @version		v2.5.4
 * $Revision: 8902 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		version_upgrade
 * @brief		IP.Download Manager version upgrader
 */
class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		$_output
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
		
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// Remove column
		//--------------------------------

		if( $this->DB->checkForField( 'file_meta', 'downloads_files' ) )
		{
			$this->DB->dropField( 'downloads_files', 'file_meta' );
		}
		
		//-----------------------------------------
		// Add indexes, but only if they don't exist.
		// Can't use DB driver method as it does not have an option for fulltext
		//-----------------------------------------
		
		if( !$this->DB->checkForIndex( 'file_name', 'downloads_files' ) )
		{
			$this->DB->addFulltextIndex( "downloads_files", "file_name" );
		}
		
		if( !$this->DB->checkForIndex( 'file_desc', 'downloads_files' ) )
		{
			$this->DB->addFulltextIndex( "downloads_files", "file_desc" );
		}
		
		$members		= array();
		$memberIds		= array();
		$realMembers	= array();
		
		//-----------------------------------------
		// Convert favorites
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'downloads_favorites' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$memberIds[ $r['fmid'] ]				= $r['fmid'];
			$members[ $r['ffid'] ][ $r['fmid'] ]	= array(
															'like_rel_id'		=> $r['ffid'],
															'like_member_id'	=> $r['fmid'],
															'like_notify_do'	=> 0,
															);
		}
		
		//-----------------------------------------
		// Convert watched files
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'file_id, file_sub_mems', 'from' => 'downloads_files', 'where' => "file_sub_mems != '' AND file_sub_mems " . $this->DB->buildIsNull(false) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$_subscriptions	= explode( ',', IPSText::cleanPermString( $r['file_sub_mems'] ) );
			
			if( count($_subscriptions) )
			{
				foreach( $_subscriptions as $mid )
				{
					$memberIds[ $mid ]				= $mid;
					
					if( isset($members[ $r['file_id'] ][ $mid ]) )
					{
						$members[ $r['file_id'] ][ $mid ]['like_notify_do']	= 1;
					}
					else
					{
						$members[ $r['file_id'] ][ $mid ]	= array(
																	'like_rel_id'		=> $r['file_id'],
																	'like_member_id'	=> $mid,
																	'like_notify_do'	=> 1,
																	);
					}
				}
			}
		}
		
		$realMembers	= IPSMember::load( $memberIds, 'core' );
		
		//-----------------------------------------
		// Create new like records
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like = classes_like::bootstrap( 'downloads', 'files' );
		
		if( count($members) )
		{
			foreach( $members as $file => $_members )
			{
				foreach( $_members as $member )
				{
					if( !$member['like_member_id'] OR !array_key_exists( $member['like_member_id'], $realMembers ) )
					{
						continue;
					}

					$_like->add(
								$member['like_rel_id'],
								$member['like_member_id'],
								array(
									'like_notify_do'	=> $member['like_notify_do'],
									'like_notify_freq'	=> 'immediate',
									),
								false
								);
				}
			}
		}
		
		$this->DB->dropTable( "downloads_favorites" );
		$this->DB->dropField( "downloads_files", "file_sub_mems" );
		
		if( !$this->DB->checkForField( "file_nexus", "downloads_files" ) )
		{
			$this->DB->addField( "downloads_files", "file_nexus", "text" );
		}

		//-----------------------------------------
		// Continue with upgrade
		//-----------------------------------------
		
		return true;
	}
}
