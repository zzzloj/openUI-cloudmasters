<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Define the core notification types
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Notification types
 */

ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_downloads' ), 'downloads' );

class downloads_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'updated_file', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newfile' ),
							array( 'key' => 'new_file', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newfile' ),
							array( 'key' => 'file_approved', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_fileapproved' ),
							array( 'key' => 'file_broken', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => TRUE, 'icon' => 'notify_diskwarn' ),
							array( 'key' => 'file_mybroken', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_diskwarn' ),
							array( 'key' => 'file_pending', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => TRUE, 'icon' => 'notify_diskwarn' ),
							);
		
		return $_NOTIFY;
	}
	
	public function file_broken()
	{
		ipsRegistry::getAppClass( 'downloads' );
		$this->registry	= ipsRegistry::instance();
		
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
							$appcats = $v['modcats'];
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
							$appcats = $v['modcats'];
						}
					}
				}
			}
		}
		
		if( $appcats )
		{
			return true;
		}
		
		return false;
	}

	public function file_pending()
	{
		ipsRegistry::getAppClass( 'downloads' );
		$this->registry	= ipsRegistry::instance();
		
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
						if( $v['modcanapp'] )
						{
							$appcats = $v['modcats'];
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
						if( $v['modcanapp'] )
						{
							$appcats = $v['modcats'];
						}
					}
				}
			}
		}
		
		if( $appcats )
		{
			return true;
		}
		
		return false;
	}
}

