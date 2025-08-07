<?php

/**
* Tracker 2.1.0
* 
* Projects Javascript PHP Interface
* Last Updated: $Date: 2012-12-06 16:27:50 +0000 (Thu, 06 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1395 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: PUBLIC
 * Project AJAX processor
 * 
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class public_tracker_ajax_projects extends ipsAjaxCommand 
{
	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_project' ) );

		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('trackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'trackerTags', classes_tags_bootstrap::run( 'tracker', 'issues' )  );
		}
		
		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		if ( $this->request['showproject'] )
		{
			$this->getIssues();
			return;
		}
		
		switch( $this->request['do'] )
		{
			case 'edit':
				$this->edit();
				break;
			case 'save':
				$this->save();
				break;
			case 'filter':
				$this->getIssues();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}
	
	private function getIssues()
	{
		if ( ! $this->request['showproject'] && ! $this->request['project_id'] )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
		}

		$projectID	= intval( $this->request['showproject'] ) ? intval( $this->request['showproject'] ) : intval( $this->request['project_id'] );
		
		/* Initiate projects */
		$this->projects = $this->registry->tracker->projects()->getCache();
		$this->project	= $this->projects[ $projectID ];
		
		/* FIELDS-NEW */
		$this->registry->tracker->fields()->initialize( $this->project['project_id'] );

		require_once( IPSLib::getAppDir( 'tracker' ) . '/modules_public/projects/projects.php' );
		$this->registry->setClass( 'trackerView', new public_tracker_projects_projects( $this->registry ) );

		$lib = $this->registry->getClass( 'trackerView' );
		$lib->makeRegistryShortcuts( $this->registry );
		
		// Page links
		$this->settings['tracker_bugs_perpage'] = $this->settings['tracker_bugs_perpage'] ? $this->settings['tracker_bugs_perpage'] : 25;

		// If we've changed the filters, bounce back to page 1
		$this->request['st'] = isset($this->request['changefilters']) ? 0 : (intval($this->request['st']) > 0 ? intval($this->request['st']) : 0);

		$printedIssues = $this->registry->tracker->projects()->makeFilters($this->project);
		
		// Column span
		$fields = $this->registry->tracker->fields()->display( array(), 'project' );
		$this->settings['colspan'] = $fields['colspan'];

		$printedIssuesHTML = $this->registry->output->getTemplate('tracker_projects')->trackerIssueRow($printedIssues['issues']);

		/* Return */
		$this->returnJsonArray(
			array(
				'pagination'	=> $this->parseAndCleanHooks( $printedIssues['project']['SHOW_PAGES'] ),
				'issues'		=> $this->parseAndCleanHooks( $this->registry->output->parseIPSTags($printedIssuesHTML ) )
			)
		);
	}
	
	private function edit()
	{
		if ( ! $this->request['project_id'] OR $this->request['project_id'] != intval( $this->request['project_id'] ) )
		{
			$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
			return;
		}
		
		$out			= array();
		$this->versions = array();
		
		// Security
		$this->request['project_id'] = intval($this->request['project_id']);
		
		/* Get permissions & project */
		$project = $this->registry->tracker->projects()->getProject($this->request['project_id']);
		$this->registry->tracker->moderators()->buildModerators($this->request['project_id']);
		$this->registry->tracker->fields()->initialize($this->request['project_id']);
		
		if ( ! $this->registry->tracker->fields()->field('version')->checkPermission('alter') OR ! $this->registry->tracker->modules()->moduleIsInstalled('versions') )
		{
			$out['error']	= 'true';
			$out['message']	= 'You do not have permission to carry out this action.';
		}
		else
		{
			/* Get versions from database */
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> 'tracker_module_version',
					'where'		=> 'project_id=' . intval($this->request['project_id']),
					'order'		=> 'position ASC'
				)
			);
			$this->DB->execute();
	
			if ( $this->DB->getTotalRows() )
			{
				while( $row = $this->DB->fetch() )
				{
					if ( $row['version_id'] > $this->maxID )
					{
						$this->maxID = $row['version_id'];
					}
	
					$this->versions[] = $row;
				}
			}
			
			$out['html']	= $this->registry->output->getTemplate('tracker_projects')->manageProject($project, $this->versions);
			$out['title']	= "Editing Project: " . $project['title'];
			$out['count']	= $this->maxID + 1;
		}
		
		$this->returnJsonArray($out);
	}
	
	private function save()
	{
		$saveArray	= array();
		$data 		= json_decode( $_POST['data'], true );
		
		if ( count( $data ) > 0 )
		{
			$count = 0;
			
			foreach( $data as $v )
			{
				$count++;
				
				$v['version_id'] = intval( $v['version_id'] );
	
				$saveArray[ $v['version_id'] ] = array(
					'human'				=> $v['human'],
					'project_id'		=> intval($this->request['project_id']),
					'permissions'		=> $v['type'],
					'report_default'	=> intval($v['default']),
					'locked'			=> ( $v['type'] == 'locked' ? 1 : 0 ),
					'position'			=> $count
				);
				
				// Did we get any data?
				if ( trim($v['human']) == '' )
				{
					continue;
				}
	
				if ( $v['save_type'] == 'new' )
				{
					$this->DB->insert( 'tracker_module_version', $saveArray[ $v['version_id'] ] );
				}
				else if ( $v['save_type'] == 'save' )
				{
					$this->DB->update( 'tracker_module_version', $saveArray[ $v['version_id'] ], 'version_id=' . $v['version_id'] );
				}
				else if ( $v['save_type'] == 'delete' )
				{
					if ( ! $v['move_to'] )
					{
						continue;
					}
	
					/* No version assigned */
					if ( $v['move_to'] == 'nva' )
					{
						$v['move_to'] = '';
					}
	
					$this->DB->update( 'tracker_issues', array( 'module_versions_reported_id' => intval( $v['move_to'] ) ), 'module_versions_reported_id=' . $v['version_id'] );
					$this->DB->delete( 'tracker_module_version', 'version_id=' . $v['version_id'] );
				}
			}
		}
		
		$this->registry->tracker->cache('versions', 'versions')->rebuild();
		$this->returnJsonArray( array( 'success' => true ) );
	}
}