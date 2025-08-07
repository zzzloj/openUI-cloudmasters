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
		
		//-----------------------------------------
		// Update comments count
		//-----------------------------------------
		
		$comments	= array();
		
		$this->DB->build( array( 'select' => 'COUNT(*) as num_comments, comment_fid', 'from' => 'downloads_comments', 'group' => 'comment_fid', 'where' => 'comment_open=1' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$comments[ $r['comment_fid'] ]	= $r['num_comments'];
		}
		
		if( count($comments) )
		{
			foreach( $comments as $fid => $cnt )
			{
				$this->DB->update( 'downloads_files', array( 'file_comments' => $cnt ), 'file_id=' . $fid );
			}
		}

		//-----------------------------------------
		// Continue with upgrade
		//-----------------------------------------
		
		return true;
	}
}
