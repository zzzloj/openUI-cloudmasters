<?php
/**
 * @file		version_upgrade.php 	IP.Download Manager 2.x upgrader
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
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
 * @brief		IP.Download Manager 2.x upgrader
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
	 * Fetchs output
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
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
				$this->upgradeDownloads();
			break;
		}
		
		return true;	
	}
	
	/**
	 * Main work of upgrading IP.Downloads
	 *
	 * @return	@e void
	 */
	public function upgradeDownloads()
	{
		//-----------------------------------------
		// Got our XML?
		//-----------------------------------------
		
		if ( is_file( IPS_ROOT_PATH . 'applications_addon/ips/downloads/xml/information.xml' ) )
		{
			//-----------------------------------------
			// Not already "installed"?
			//-----------------------------------------
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'app_directory', 'from' => 'core_applications', 'where' => "app_directory='downloads'" ) );
			
			if( !$check['app_directory'] )
			{
				//-----------------------------------------
				// Get position
				//-----------------------------------------
				
				$max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as max', 'from' => 'core_applications' ) );
				
				$_num	= $max['max'] + 1;
				
				//-----------------------------------------
				// Get XML data
				//-----------------------------------------
				
				$data	= IPSSetUp::fetchXmlAppInformation( 'downloads' );

				//-----------------------------------------
				// Get current versions
				//-----------------------------------------
				
				if ( $this->DB->checkForTable( 'downloads_upgrade_history' ) )
				{
					/* Fetch current version number */
					$version = $this->DB->buildAndFetch( array( 'select' => '*',
																'from'   => 'downloads_upgrade_history',
																'order'  => 'idm_version_id DESC',
																'limit'  => array( 0, 1 ) ) );
																
					$data['_currentLong']	= $version['idm_version_id'];
					$data['_currentHuman']	= $version['idm_version_human'];
				}
				
				$_enabled   = ( $data['disabledatinstall'] ) ? 0 : 1;
	
				if ( $data['_currentLong'] )
				{
					//-----------------------------------------
					// Insert record
					//-----------------------------------------
					
					$this->DB->insert( 'core_applications', array(   'app_title'        => $data['name'],
																	 'app_public_title' => ( $data['public_name'] ) ? $data['public_name'] : '',	// Allow blank in case it's an admin-only app
																	 'app_description'  => $data['description'],
																	 'app_author'       => $data['author'],
																	 'app_version'      => $data['_currentHuman'],
																	 'app_long_version' => $data['_currentLong'],
																	 'app_directory'    => $data['key'],
																	 'app_added'        => time(),
																	 'app_position'     => $_num,
																	 'app_protected'    => 0,
																	 'app_location'     => IPSLib::extractAppLocationKey( $data['key'] ),
																	 'app_enabled'      => $_enabled ) );
				}
			}
		}
		
		/* Convert category perms */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'downloads_categories' ) );
								
		$o = $this->DB->execute();
					
		while( $row = $this->DB->fetch( $o ) )
		{
			if ( strstr( $row['cperms'], 'a:' ) )
			{
				$_perms = unserialize( stripslashes( $row['cperms'] ) );
				
				if ( is_array( $_perms ) )
				{
					$_show		= ( $_perms['show'] )		? ',' . implode( ',', explode( ',', $_perms['show'] ) ) . ',' : '';
					$_view		= ( $_perms['view'] )		? ',' . implode( ',', explode( ',', $_perms['view'] ) ) . ',' : '';
					$_add		= ( $_perms['add'] )		? ',' . implode( ',', explode( ',', $_perms['add'] ) ) . ',' : '';
					$_download	= ( $_perms['download'] )	? ',' . implode( ',', explode( ',', $_perms['download'] ) ) . ',' : '';
					$_rate		= ( $_perms['rate'] )		? ',' . implode( ',', explode( ',', $_perms['rate'] ) ) . ',' : '';
					$_comment	= ( $_perms['comment'] )	? ',' . implode( ',', explode( ',', $_perms['comment'] ) ) . ',' : '';
					$_auto		= ( $_perms['auto'] )		? ',' . implode( ',', explode( ',', $_perms['auto'] ) ) . ',' : '';
					
					$this->DB->insert( 'permission_index', array( 'app'				=> 'downloads',
																  'perm_type'		=> 'cat',
																  'perm_type_id'	=> $row['cid'],
																  'perm_view'		=> str_replace( ',*,', '*', $_view ),
																  'perm_2'			=> str_replace( ',*,', '*', $_show ),
																  'perm_3'			=> str_replace( ',*,', '*', $_add ),
																  'perm_4'			=> str_replace( ',*,', '*', $_download ),
																  'perm_5'			=> str_replace( ',*,', '*', $_comment ),
																  'perm_6'			=> str_replace( ',*,', '*', $_rate ),
																  'perm_7'			=> str_replace( ',*,', '*', $_auto ) ) );
				}
				else
				{
					IPSSetUp::addLogMessage( "Skipped perms (no array) for IP.Downloads category id: " . $row['cid'], '20000', 'core' );
				}
			}
			else
			{
				IPSSetUp::addLogMessage( "Skipped perms for IP.Downloads category id: " . $row['cid'], '20000', 'core' );
			}
		}
		
		//-----------------------------------------
		// Recache categories manually...
		//-----------------------------------------

		$cache = array();

		$this->DB->build( array( 
								'select'	=> 'c.*',
								'from'		=> array( 'downloads_categories' => 'c' ),
								'order'		=> 'c.cparent, c.cposition',
								'add_join'	=> array(
													array(
															'select' => 'p.*',
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.perm_type='cat' AND p.perm_type_id=c.cid AND p.app='downloads'",
															'type'   => 'left',
														)
									)
						)	);

		$this->DB->execute();
		
		while( $cat = $this->DB->fetch() )
		{
			$cat['cfileinfo']	= unserialize( $cat['cfileinfo'] );
			$cat['coptions']	= unserialize( $cat['coptions'] );
			
			$cache[ $cat['cparent'] ][ $cat['cid'] ] = $cat;
		}

		$this->cache->setCache( 'idm_cats', $cache, array( 'array' => 1, 'donow' => 1 ) );	
	}
}