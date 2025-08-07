<?php

/*
+--------------------------------------------------------------------------
|   IP.Board v3.1.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
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
			case 'projects':
				$this->upgradeProjects();
				break;
			case 'versions':
				$this->upgradeVersions();
				break;
			case 'sql':
			case 'sql1':
				$this->upgradeSql(1);
				break;
			case 'sql2':
				$this->upgradeSql(2);
				break;
			case 'sql3':
				$this->upgradeSql(3);
				break;
			case 'moderators':
				$this->upgradeModerators();
				break;
			case 'seo':
				$this->seoTitles();
				break;
			case 'fields':
				$this->insertFields();
				break;
			case 'finish':
				$this->finish();
				break;
			
			default:
				$this->upgradeProjects();
				break;
		}
		
		// Bug fix
		IPSSetUp::setSavedData( 'appdir', 'tracker' );
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	* Run SQL files
	* 
	* @access	public
	* @param	int
	*/
	public function upgradeSql( $id=1 )
	{
		$cnt        = 0;
		$SQL        = array();
		$file       = '_updates_'.$id.'.php';
		$output     = "";
		$path       = IPSLib::getAppDir( 'tracker' ) . '/setup/versions/upg_20000/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix     = $this->registry->dbFunctions()->getPrefix();
		$sourceFile = '';
		
		if ( file_exists( $path ) )
		{
			require( $path );
			
			/* Set DB driver to return any errors */
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';
				
				$query  = str_replace( "<%time%>", time(), $query );
				
				if ( $this->settings['mysql_tbl_type'] )
				{
					if ( preg_match( "/^create table(.+?)/i", $query ) )
					{
						$query = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->settings['mysql_tbl_type']};", $query );
					}
				}
				
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
				
				if( IPSSetUp::getSavedData('man') )
				{
					$query = trim( $query );
					
					/* Ensure the last character is a semi-colon */
					if ( substr( $query, -1 ) != ';' )
					{
						$query .= ';';
					}
					
					$output .= $query . "\n\n";
				}
				else
				{			
					$this->DB->query( $query );
					
					if ( $this->DB->error )
					{
						$this->registry->output->addError( "<br />" . $query."<br />".$this->DB->error );
					}
					else
					{
						$cnt++;
					}
				}
			}
		
			$this->registry->output->addMessage("$cnt queries run....");
		}
		
		/* Next Page */
		$this->request['st'] = 0;
		
		if ( $id < 3 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->request['workact'] = 'moderators';	
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '20000', $id );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
	}	
	
	
	/**
	* Upgrade Projects
	* 
	* @access	public
	* @param	int
	*/
	public function upgradeProjects()
	{
		$start 			= intval( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		$subStart		= intval( IPSSetUp::getSavedData('sst') ) ? intval( IPSSetUp::getSavedData('sst') ) : 0;
		$savedVersions	= $this->getSavedVersions() ? unserialize( $this->getSavedVersions() ) : array();
		
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_projects',
				'limit'		=> array( $start, 1 )
			)
		);
		$out = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $out ) )
		{
			while( $project = $this->DB->fetch( $out ) )
			{
				// Permissions
				$permission = array();
				$permission['perm_view']	= ( $project['project_show_perms'] == '*' ) ? '*' : ',' . $project['project_show_perms'] . ',';
				$permission['perm_2']		= ( $project['project_read_perms'] == '*' ) ? '*' : ',' . $project['project_read_perms'] . ',';
				$permission['perm_3']		= ( $project['project_start_perms'] == '*' ) ? '*' : ',' . $project['project_start_perms'] . ',';
				$permission['perm_4']		= ( $project['project_reply_perms'] == '*' ) ? '*' : ',' . $project['project_reply_perms'] . ',';
				$permission['perm_5']		= ( $project['project_upload_perms'] == '*' ) ? '*' : ',' . $project['project_upload_perms'] . ',';
				$permission['perm_6']		= ( $project['project_download_perms'] == '*' ) ? '*' : ',' . $project['project_download_perms'] . ',';
				
				// Default stuff for perms
				$permission['app']			= 'tracker';
				$permission['perm_type']	= 'project';
				$permission['perm_type_id']	= $project['project_id'];
				
				// Handle versions
				$versions = explode( "\n", $project['project_versions'] );
				
				$this->DB->build(
					array(
						'select'	=> '*',
						'from'		=> 'tracker_issues',
						'where'		=> 'project_id=' . $project['project_id'],
						'limit'		=> array( $subStart, 200 )
					)
				);
				$in = $this->DB->execute();
				
				if ( $this->DB->getTotalRows( $in ) )
				{
					while( $issue = $this->DB->fetch( $in ) )
					{
						if ( ! isset( $savedVersions[ $issue['project_id'] ] ) )
						{
							$savedVersions[ $issue['project_id'] ] = array();
						}
						
						// Reported Version
						if ( $issue['issue_version'] && ! in_array( $issue['issue_version'], $savedVersions[ $issue['project_id'] ] ) )
						{
							if ( in_array( $issue['issue_version'], $versions ) )
							{
								$savedVersions[ $issue['project_id'] ][ $issue['issue_version'] ] = array( 'version' => $issue['issue_version'], 'project' => $issue['project_id'], 'type' => 'open' );
							}
							else
							{
								// No longer in our main project versions (aka its been removed)
								$savedVersions[ $issue['project_id'] ][ $issue['issue_version'] ] = array( 'version' => $issue['issue_version'], 'project' => $issue['project_id'], 'type' => 'locked' );
							}
						}
						
						// Fixed Version
						if ( $issue['issue_fixed_in'] && ! in_array( $issue['issue_fixed_in'], $savedVersions[ $issue['project_id'] ] ) )
						{
							if ( in_array( $issue['issue_fixed_in'], $versions ) )
							{
								$savedVersions[ $issue['project_id'] ][ $issue['issue_fixed_in'] ] = array( 'version' => $issue['issue_fixed_in'], 'fixed' => true, 'project' => $issue['project_id'], 'type' => 'open' );
							}
							else
							{
								// No longer in our main project versions (aka its been removed)
								$savedVersions[ $issue['project_id'] ][ $issue['issue_fixed_in'] ] = array( 'version' => $issue['issue_fixed_in'], 'fixed' => true, 'project' => $issue['project_id'], 'type' => 'locked' );
							}
						}
					}
					
					$cnt = $this->DB->getTotalRows($in) + $subStart;
					
					// Show messages
					$this->registry->output->addMessage( $project['project_title'] . ': ' . $cnt . " issues prepared for conversion....");
					$this->registry->output->addMessage( $project['project_title'] . ": Project Permissions converted....");
					
					IPSSetUp::setSavedData( 'sst', $subStart + 200 );
					$this->request['st']	= $this->request['st'];
				}
				else
				{
					// Insert permission
					foreach( $permission as $perm => $key )
					{
						if ( $permission[$perm] == ',,' )
						{
							$permission[$perm] = '';
						}
					}
					
					$this->DB->insert( 'permission_index', $permission );
				
					IPSSetUp::setSavedData( 'sst', 0 );
					$this->request['st']	= $this->request['st'] + 1;
				}
			}
			
			// Save data
			$this->setSavedVersions( serialize( $savedVersions ) );
			
			$this->request['workact']	= 'projects';
		}
		else
		{
			/* Next Page */
			$this->request['st']		= 0;
			$this->request['workact']	= 'versions';
		}
	}
	
	/**
	* Upgrade Versions
	* 
	* @access	public
	*/
	public function upgradeVersions()
	{
		$versions = $this->getSavedVersions() ? unserialize( $this->getSavedVersions() ) : array();
		$start	  = intval( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		
		if ( count( $versions ) )
		{
			$current	= 0;
			
			foreach ( $versions as $k => $v )
			{
				$current++;
				
				// Limit it to 5 projects at a time
				if ( $current <= 5 )
				{
					// Version order per project
					$i = 0;
					
					// Loop through versions for this project.
					foreach( $v as $a => $b )
					{
						if ( $b['version'] )
						{
							// Increment order
							$i++;
							
							// Default
							$default = 0;
							
							if ( ! next( $v ) )
							{
								$default = 1;
							}
							
							// Insert version into database
							$this->DB->insert( 'tracker_module_version',
								array(
									'project_id' 		=> $b['project'],
									'human'				=> $b['version'],
									'permissions'		=> $b['type'],
									'fixed_only'		=> 0,
									'report_default'	=> $default,
									'fixed_default'		=> 0,
									'position'			=> $i,
									'locked'			=> ( $b['type'] == 'locked' ) ? 1 : 0
								)
							);
							
							// Where was it put?
							$id = $this->DB->getInsertId();
							
							// Update existing issues
							if ( $b['fixed'] )
							{
								$this->DB->update( 'tracker_issues',
									array(
										'module_versions_fixed_id'	=> $id
									),
									"issue_fixed_in='" . $b['version'] . "' AND project_id=" . $b['project']
								);
							}
							else
							{
								$this->DB->update( 'tracker_issues',
									array(
										'module_versions_reported_id'	=> $id
									),
									"issue_version='" . $b['version'] . "' AND project_id=" . $b['project']
								);
							}
						}
					}
					
					// Remove project
					unset( $versions[$k] );
				}
			}
									
			$_ok = 0;
			
			foreach( $versions as $pid => $version )
			{
				if ( count( $version ) )
				{
					$_ok = 1;
				}
			}
			
			if ( ! $_ok )
			{
				$versions = array();
			}
			
			$this->setSavedVersions( serialize( $versions ) );
			
			$cnt = $start + 5;
			$this->request['st'] = $cnt;
			
			$this->registry->output->addMessage("$cnt versions converted....");
			
			$this->request['workact'] = 'versions';
		}
		else
		{
			// Update all old issues
			$this->DB->update( 'tracker_issues', array( 'issue_version' => 0 ), "issue_version=''" );
			$this->DB->update( 'tracker_issues', array( 'issue_fixed_in' => 0 ), "issue_fixed_in=''" );
			
			$this->registry->output->addMessage("No more versions to convert....");
			$this->request['st']	  = 0;
			$this->request['workact'] = 'sql';
		}
	}
	
	/**
	* Upgrade Moderators
	* 
	* @access	public
	*/
	public function upgradeModerators()
	{
		$this->DB->update( 'tracker_moderators', array( 'mode' => 'normal' ), '1=1' );
		$this->registry->output->addMessage("Moderators converted....");
		
		$this->request['workact'] = 'seo';
	}
	
	/**
	* SEO Titles
	*
	* @access public
	*/
	public function seoTitles()
	{
		$start = intval( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_issues',
				'limit'		=> array( $start, 100 )
			)
		);
		$out = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $out ) )
		{
			while( $issue = $this->DB->fetch( $out ) )
			{
				$this->DB->update( 'tracker_issues',
					array(
						'title_seo'				=> IPSText::makeSeoTitle( $issue['title'] ),
						'starter_name_seo'		=> IPSText::makeSeoTitle( $issue['starter_name'] ),
						'last_poster_name_seo'	=> IPSText::makeSeoTitle( $issue['last_poster_name'] )
					),
					'issue_id=' . $issue['issue_id']
				);
			}
			
			$this->registry->output->addMessage( $start + $this->DB->getTotalRows( $out ) . " SEO titles created....");
			
			$this->request['st']	  = $start + 100;
			$this->request['workact'] = 'seo';
		}
		else
		{
			$this->request['st']	  = 0;
			$this->request['workact'] = 'fields';
		}
	}
	
	/**
	* Sort out fields
	* 
	* @access	public
	*/
	public function insertFields()
	{
		$storedFields	= array();
		$start 			= intval( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		
		// Grab fields
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_field'
			)
		);
		$fields = $this->DB->execute();
		
		while( $field = $this->DB->fetch( $fields ) )
		{
			$storedFields[] = $field['field_id'];
		}
		
		// Grab projects
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_projects',
				'limit'		=> array( $start, 10 )
			)
		);
		$projects = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $projects ) )
		{
			while( $project = $this->DB->fetch( $projects ) )
			{
				$count = 0;
				
				foreach( $storedFields as $k => $v )
				{
					$count++;
					
					$this->DB->insert( 'tracker_project_field',
						array(
							'project_id'	=> $project['project_id'],
							'field_id'		=> $v,
							'position'		=> $count,
							'enabled'		=> 1
						)
					);
				}
				
				// Default perms
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'statusFieldStatusProject',
						'perm_type_id'	=> $project['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> '*',
						'perm_3'		=> ',4,',
						'perm_4'		=> ',4,'
					)
				);
				
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'severityFieldSeverityProject',
						'perm_type_id'	=> $project['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> ',4,',
						'perm_3'		=> ',4,'
					)
				);
				
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'versionsFieldVersionProject',
						'perm_type_id'	=> $project['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> '*',
						'perm_3'		=> ',4,',
						'perm_4'		=> ',4,'
					)
				);
				
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'versionsFieldFixed_inProject',
						'perm_type_id'	=> $project['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> ',4,',
						'perm_3'		=> ',4,',
						'perm_4'		=> ',4,',
						'perm_5'		=> ',4,'
					)
				);
			}
			
			$this->registry->output->addMessage( $start + $this->DB->getTotalRows( $projects ) . " Project Fields created....");
			
			$this->request['st']	  = $start + 10;
			$this->request['workact'] = 'fields';
		}
		else
		{
			$this->registry->output->addMessage( "ALL Project Fields created....");
			$this->request['st']	  = 0;
			$this->request['workact'] = 'finish';
		}
	}
		
	/**
	* Finish up conversion stuff
	* 
	* @access	public
	*/
	public function finish()
	{
		// Mark it as an IPS app
		$this->DB->update( 'core_applications', array( 'app_location' => 'ips' ), "app_directory='tracker'" );
		
		// Load Tracker and REBUILD REBUILD REBUILD
		if ( ! $this->registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker	= new $classToLoad();
			$this->tracker->execute( $this->registry );
	
			$this->registry->setClass( 'tracker', $this->tracker );
		}
		
		$this->tracker->modules()->rebuild();
		$this->tracker->fields()->rebuild();
		$this->tracker->moderators()->rebuild();
		$this->tracker->projects()->rebuild();
		$this->tracker->cache('stats')->rebuild();
		$out = $this->tracker->cache('files')->rebuild();
		
		// Require applications
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/applications/applications.php', 'admin_core_applications_applications' );
		$application = new $classToLoad( $this->registry );
		$application->makeRegistryShortcuts( $this->registry );
		$application->moduleRecacheAll(1);
		
		if ( $out == 'CANNOT_WRITE' )
		{
			$this->registry->output->addError( 'Cannot write to the Tracker cache files, please make sure all files in ' . DOC_IPS_ROOT_PATH . 'cache/tracker/ are writeable (CHMOD 777), and then refresh this page' );
		}
		
		/* Loop through and rebuild modules */
		foreach( $this->tracker->modules()->getCache() as $k => $v )
		{
			if ( is_dir( $this->tracker->modules()->getModuleFolder( $v['directory'] ) . '/sources/' ) )
			{
				foreach( new DirectoryIterator( $this->tracker->modules()->getModuleFolder( $v['directory'] ) . '/sources/' ) as $file )
				{
					// Make sure it isn't a folder
					if ( $file->isFile() )
					{
						$name = $file->getFilename();
						
						if ( preg_match( '#cache_(.+?).php#is', $name, $matches ) )
						{
							$this->tracker->cache( $matches[1], $v['directory'] )->rebuild();
						}
					}
				}
			}
		}
		
		$this->registry->output->addMessage( "SQL clean up finished....");
		$this->registry->output->addMessage( "Tracker Caches rebuilt....");
		
		/* Last function, so unset workact */
		$this->request['workact'] = '';
	}
	
	private function getSavedVersions()
	{
		if ( $this->_savedData )
		{
			return $this->_savedData;
		}
		
		$this->_savedData = @file_get_contents( DOC_IPS_ROOT_PATH . '/cache/tracker/upgrade_data.flatfile' );
		return $this->_savedData;
	}
	
	private function setSavedVersions( $data )
	{
		if ( ! is_writable( DOC_IPS_ROOT_PATH . '/cache/tracker/' ) )
		{
			die( 'Tracker cache files are not writeable, upgrader cannot continue. Please set proper permissions on ' . DOC_IPS_ROOT_PATH . '/cache/tracker/' );
		}
		
		@file_put_contents( DOC_IPS_ROOT_PATH . '/cache/tracker/upgrade_data.flatfile', $data );
	}
}
	
?>