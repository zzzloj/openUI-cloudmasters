<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-10-10 15:53:22 +0100 (Wed, 10 Oct 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicModules
* @link			http://ipbtracker.com
* @version		$Revision: 1388 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_projects_projects extends ipsCommand
{
	public $project = array();

	/*-------------------------------------------------------------------------*/
	// Run me, called by module wrapper
	/*-------------------------------------------------------------------------*/

	public function doExecute( ipsRegistry $registry )
	{
		// Fixed in
		if( $this->request['do'] == 'fixedlist' )
		{
			return $this->generateFixedIn();
		}
		
		$this->registry->tracker->addGlobalNavigation();
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('trackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'trackerTags', classes_tags_bootstrap::run( 'tracker', 'issues' )  );
		}

		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_project' ) );

		/* User Issues View */
		if ( $this->member->getProperty('member_id') > 0 && isset( $this->request['mode'] ) && $this->request['mode'] == "user" )
		{
			/* Language */
			$this->registry->class_localization->loadLanguageFile( array( 'public_tracker' ) );

			$this->renderUserIssues();

			/* Append output */
			$this->registry->output->addNavigation( $this->lang->words['bt_ucp_membugs_header'], "app=tracker&amp;module=projects&amp;section=projects&amp;mode=user" );
			$this->registry->tracker->pageTitle = IPSLib::getAppTitle( 'tracker' ) . ' -> ' . $this->lang->words['bt_ucp_membugs_header'];
			$this->registry->tracker->sendOutput();
			return;
		}
		
		$this->request['pid'] = $this->request['pid'] ? $this->request['pid'] : $this->request['project_id'];

		/* Initiate projects */
		$this->projects = $this->registry->tracker->projects()->getCache();
		$this->project  = $this->projects[ intval( $this->request['pid'] ) ];

		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------

		if ( ! $this->project['project_id'] )
		{
			$this->registry->output->showError( 'The project you tried to access does not exist!', '20T105' );
		}

		/* Build permissions */
		$this->registry->tracker->projects()->createPermShortcuts( $this->project['project_id'] );

		/* FIELDS-NEW */
		$this->registry->tracker->fields()->initialize( $this->project['project_id'] );
		
		/* Moderators */
		$this->registry->tracker->moderators()->buildModerators( $this->project['project_id'] );

		if ( ! $this->member->tracker['show_perms'] )
		{
			$this->registry->output->showError( 'You do not have permission to view this project', '10T100' );
		}

		//-----------------------------------------
		// If this is a sub forum, we need to get
		// the cat details, and parent details
		//-----------------------------------------

		$this->registry->tracker->projects()->createBreadcrumb( $this->project['project_id'] );

		$this->renderProject();
		
		/* FIELDS-NEW */
		//$this->registry->tracker->fields()->shutdown('project');

		/* Append output */
		$this->registry->tracker->pageTitle = $this->project['title'] . ' -> ' . IPSLib::getAppTitle( 'tracker' );
		$this->registry->tracker->sendOutput();
	}

	function generateFixedIn()
	{
		$this->request['pid'] = $this->request['pid'] ? $this->request['pid'] : $this->request['project_id'];

		/* Initiate projects */
		$this->projects = $this->registry->tracker->projects()->getCache();
		$this->project  = $this->projects[ intval( $this->request['pid'] ) ];
		
		/* We are allowed to manage this project? */
		$this->registry->tracker->projects()->createPermShortcuts( $this->project['project_id'] );

		if ( ! $this->member->tracker['show_perms'] )
		{
			$this->registry->output->showError( 'You are not allowed to view this project', '10T109' );
		}

		# What version did we input?
		$this->cache = $this->registry->tracker->cache('versions', 'versions');
		$this->versionsCache  = $this->cache->getCache();
		
		if ( ! count( $this->versionsCache ) )
		{
			$this->registry->output->showError( "The project you tried to access doesn't have any versions!", '10T110' );
		}

		/* Privacy Module */
		$pEnabled = $this->registry->tracker->fields()->active('privacy', $this->project['project_id']);
		$privacy = $pEnabled ? ',module_privacy' : '';

		/* Seems we are safe to go go! */
		if ( $this->request['fixed_in'] == 'all' )
		{
			$this->DB->build(
				array(
					'select'  => 'issue_id,title,title_seo'.$privacy,
					'from'    => 'tracker_issues',
					'where'   => "project_id=".$this->project['project_id']." AND module_versions_fixed_id!=0"
				)
			);
		}
		else
		{
			$version = intval( $this->request['fixed_in'] );
			
			$this->DB->build(
				array(
					'select'  => 'issue_id,title,title_seo'.$privacy,
					'from'    => 'tracker_issues',
					'where'   => "project_id=".$this->project['project_id']." AND module_versions_fixed_id='{$version}' AND module_versions_fixed_id!=0"
				)
			);
		}

		$this->DB->execute();

		$content = array();

		/* We found something? */
		if ( $this->DB->getTotalRows() )
		{
			while( $row = $this->DB->fetch() )
			{
				if($this->registry->tracker->fields()->active('privacy', $this->project['project_id']))
				{
					/* Don't show this on the fixed list.. */
					if(isset($row['module_privacy']) && $row['module_privacy'])
					{
						continue;
					}
				}

				$content[] = "	[*][url=".$this->registry->getClass('output')->buildSEOUrl( "showissue={$row['issue_id']}", 'publicWithApp', $row['title_seo'], 'showissue' )."]{$row['title']}[/url]";
			}
		}

		$totalIssues = count($content);

		if(!$totalIssues)
		{
			$this->registry->output->showError( 'bt_no_fixed_issues', '10T200' );
		}

		$txt_version = $this->request['fixed_in'];

		if ( $this->request['fixed_in'] == 'all' )
		{
			$txt_version = $this->lang->words['bt_all_releases'];
		}

		print "<title>{$this->lang->words['bt_issues_fixed_in']} {$txt_version}</title><h1>{$this->lang->words['bt_results_found']} {$totalIssues}</h1><textarea style='width:100%; height:100%'>[list]\n" . implode( "\n", $content ) . "\n[/list]</textarea>";
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Main render project engine
	/*-------------------------------------------------------------------------*/

	function renderProject()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->settings['tracker_bugs_perpage'] = $this->settings['tracker_bugs_perpage'] ? $this->settings['tracker_bugs_perpage'] : 25;

		// If we've changed the filters, bounce back to page 1
		$this->request['st'] = isset($this->request['changefilters']) ? 0 : (intval($this->request['st']) > 0 ? intval($this->request['st']) : 0);

		/* FIELDS-NEW */
		$fields = $this->registry->tracker->fields()->display( array(), 'project' );
		
		// Filters
		$data			= $this->registry->tracker->projects()->makeFilters($this->project);
		$this->project	= $data['project'];
		
		//-------------------------------
		// Get all project owner/mods for links
		//-------------------------------
		$owners = array();

		if( is_array($this->caches['tracker_mods']) AND count($this->caches['tracker_mods']) )
		{
			foreach ( $this->caches['tracker_mods'] as $k => $v )
			{
				if ( $v['type'] == 'member' && ! $v['is_super'] && $v['mg_id'] > 0 && 
					 !in_array( $v['mg_id'], $owners ) && $v['project_id'] == $this->project['project_id'] )
				{
					$owners[ $v['mg_id'] ] = $v['mg_id'];
				}
			}
		}

		if ( $this->settings['tracker_proj_led_display'] > 0 && count( $owners )  )
		{
			$owner_info = array();

			$this->DB->build(
				array(
					'select' => 'member_id, members_display_name, member_group_id, members_seo_name',
					'from'   => 'members',
					'where'  => 'member_id IN ('.implode( ',', $owners ).')'
				)
			);
			$this->DB->execute();

			if ( $this->DB->getTotalRows() )
			{
				while ( $row = $this->DB->fetch() )
				{
					$owner_info[ $row['member_id'] ] = array( 'mname' => $row['members_display_name'], 'mgroup' => $row['member_group_id'], 'seo_name' => $row['members_seo_name'] );
				}
			}
			
			$mods	 = array();
			$mod_ids = array();
			
			if ( in_array( $this->settings['tracker_proj_led_display'], array( 1, 3 ) ) )
			{
				if( count($this->caches['tracker_mods']) )
				{
					foreach ( $this->caches['tracker_mods'] as $k => $v )
					{
						if ( !$v['is_super'] && $v['project_id'] == $this->project['project_id'] )
						{
							switch ( $v['type'] )
							{
								case 'group':
									if ( isset( $this->caches['group_cache'][ $v['mg_id'] ] ) )
									{
										$mods[] = IPSMember::makeNameFormatted( "<a href='{$this->settings['base_url']}app=members&section=view&module=list&amp;max_results=20&amp;filter={$v['mg_id']}&amp;sort_order=asc&amp;sort_key=members_display_name'>{$this->caches['group_cache'][ $v['mg_id'] ]['g_title']}</a>", $v['mg_id'] );
									}
									break;
								case 'member':
									if ( ! in_array( $v['mg_id'], $mod_ids ) )
									{
										$mod_ids[ $v['mg_id'] ] = $v['mg_id'];
										$mods[] = IPSMember::makeProfileLink( IPSMember::makeNameFormatted( $owner_info[ $v['mg_id'] ]['mname'], $owner_info[ $v['mg_id'] ]['mgroup'] ), $v['mg_id'], $owner_info[ $v['mg_id'] ]['seo_name'] );
									}
									break;
							}
						}
					}
				}
			}
			
			unset( $mod_ids );
		}
		
		if ( count( $mods ) )
		{
			$this->project['moderator'] = $mods;
		}

		//-----------------------------------------
		// Start printing the page
		//-----------------------------------------
		
		/* Sub projects */
		$this->project['subprojects'] = $this->buildSubProjects();

		foreach( $fields as $k => $v )
		{
			if ( $v['extra'] == 'fixed_in' )
			{
				try
				{
					$this->project['fixed_in'] = $this->registry->tracker->fields()->getFieldClass($k)->fixedInList( $this->project );
				}
				catch( Exception $ex ){}
			}	
		}
		
		// like system
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );
		$likeClass = classes_like::bootstrap( 'tracker', 'projects' );

		$this->project['like'] = $likeClass->render( 'summary', $this->project['project_id'] );

		$this->registry->tracker->output .= $this->registry->output->getTemplate('tracker_projects')->trackerProjectTemplate( $this->project, $data['issues'], $fields, $this->registry->tracker->generateActiveUsers( 'project', $this->project['project_id'] ) );

		return TRUE;
	}

	/*-------------------------------------------------------------------------*/
	// Main render project engine
	/*-------------------------------------------------------------------------*/

	function renderUserIssues()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->settings['tracker_bugs_perpage'] = $this->settings['tracker_bugs_perpage'] ? $this->settings['tracker_bugs_perpage'] : 25;
		$this->project = array('severity_add' => 1, 'severity_col' => 1);

		# If we've changed the filters, bounce back to page 1
		$this->request['st'] = isset($this->request['changefilters']) ? 0 : (intval($this->request['st']) > 0 ? intval($this->request['st']) : 0);

		// Filters
		$issue_array = $this->registry->tracker->projects()->makeFilters($this->project);
		
		$this->registry->tracker->output .= $this->registry->output->getTemplate('tracker_projects')->userIssues( $this->project, $issue_array );

		return TRUE;
	}

	/*-------------------------------------------------------------------------*/
	// Builds the sub-projects display (if there are any)
	/*-------------------------------------------------------------------------*/

	function buildSubProjects()
	{
		require_once( IPSLib::getAppDir( 'tracker' ) . '/modules_public/projects/list.php' );
		$this->registry->setClass( 'trackerView', new public_tracker_projects_list( $this->registry ) );

		$lib = $this->registry->getClass( 'trackerView' );
		$lib->makeRegistryShortcuts( $this->registry );

		return $lib->printProject( $this->project['project_id'] );
	}
}

?>