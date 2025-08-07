<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Versions field public file
* Last Updated: $Date: 2012-09-11 21:58:07 +0100 (Tue, 11 Sep 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1386 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Public
 * Module: Versions
 * Field: Versions
 * Public processing module for the versions field
 * 
 * @package Tracker
 * @subpackage Module-Versions
 * @since 2.0.0
 */
class public_tracker_module_versions_field_version extends iptCommand implements iField
{
	protected $cache;
	protected $versions;
	protected $versionsCache;
	protected $defaultID = NULL;
	
	// Mysql strict errors, has to be integer
	protected $old       = 0;
	protected $update    = 0;
	protected $filterKey = 'verfilter';
	protected $module    = 'versions';
	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;
	protected $filter;
	protected $setPerms;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_versions' ) );

		$this->cache = $this->tracker->cache('versions', 'versions');
		$this->versionsCache  = $this->cache->getCache();
	}

	public function addNavigation()
	{
		$out = NULL;

		if ( is_array( $this->version ) )
		{
			$out = array( 'key' => "{$this->filterKey}={$this->version['version_id']}", 'value' => $this->lang->words['bt_post_assign_version'] . ' ' . $this->version['human'] );
		}

		return $out;
	}

	public function canAccessAll()
	{
		if ( $this->checkPermission('developer') )
		{
			return true;
		}
		
		return false;
	}
	
	public function checkForInputErrors( $postModule )
	{
		if ( ( ! $this->checkPermission('submit') && $postModule == 'new') OR ( ! $this->checkPermission('update') && $postModule == 'reply' ) )
		{
			return false;
		}
		
		if ( ! isset($this->request['new_module_versions_id']) )
		{
			$this->registry->output->showError( $this->lang->words['bt_ver_err_no_ver'] );
		}
		
		$id = intval($this->request['new_module_versions_id']);
		
		$version = $this->versionsCache[$id];
		
		if ( (! is_array( $version ) || $version['project_id'] != $this->project['project_id']) && $id!=0 )
		{
			$this->registry->output->showError( $this->lang->words['bt_ver_err_no_ver'] );
		}
		
		// Some permision checks, can't post into a locked issue, and can't post into staff if we dont have perms
		if ( $data['permissions'] == 'locked' || ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) )
		{
			$this->registry->output->showError( $this->lang->words['bt_ver_err_no_perm'] );
		}
		
		return true;
	}

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] || $this->tracker->moderators()->checkFieldPermission( $this->project['project_id'], $this->module, 'version', $type ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function compileFieldChange( $row )
	{
		$out = FALSE;

		$versionsID    = $row['module_versions_reported_id'];
		$newVersionsID = isset( $this->request['new_module_versions_id'] ) ? intval( $this->request['new_module_versions_id'] ) : $versionsID;
		
		// Bug fix
		$this->old = $versionsID;

		if ( ( ! isset( $this->versionsCache[ $newVersionsID ] ) || ! is_array( $this->versionsCache[ $newVersionsID ] ) ) && $newVersionsID!=0 )
		{
			$newVersionsID = $versionsID;
		}

		if ( $versionsID != $newVersionsID )
		{
			$out = TRUE;
		}
				
		$this->update = $newVersionsID;

		return $out;
	}
	
	public function display( $issue, $type='project' )
	{
		$data	= array();
		
		switch( $type )
		{
			case 'project':
			case 'issue':
				$data	= array(
					'title'	=> $this->lang->words['bt_post_info_version'],
					'data'	=> $this->versionsCache[$issue['module_versions_reported_id']]['human'] ? $this->versionsCache[$issue['module_versions_reported_id']]['human'] : '-',
					'type'	=> 'column'
				);
			break;
		}
		
		return $data;
	}
	
	public function formatMeta( $change )
	{
		if ( $this->checkPermission('show') )
		{
			// @todo Lang abstract
			$change = array_merge(
				array(
					'changedFrom'	=> $this->lang->words['bt_changed_from'] . " " . $this->versionsCache[ $change['old_value'] ]['human'] ? $this->versionsCache[ $change['old_value'] ]['human'] : $this->lang->words['bt_post_no_version'],
					'entryLang'		=> sprintf( $this->lang->words['bt_changed_to'], IPSTEXT::mbstrtolower($this->lang->words['bt_issue_info_version']) ),
					'value'			=> $this->versionsCache[ $change['new_value'] ]['human'] ? $this->versionsCache[ $change['new_value'] ]['human'] : $this->lang->words['bt_post_no_version']
				),
				$change
			);
		}
		
		return $change;
	}

	public function getQuickReplyDropdown( $issue )
	{
		$out = array();

		$out['title'] = $this->lang->words['bt_post_assign_version'];
		$out['data']  = $this->makeDropdown( 'new_module_versions_id', $issue['module_versions_reported_id'] );

		return $out;
	}

	public function getIssueViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_post_info_version'],
			'type'  => 'column'
		);
	}

	public function getPostScreen( $type )
	{
		$out = array();

		switch ( $type )
		{
			case 'new':
				$out['data'] = $this->makeDropdown( 'new_module_versions_id', intval($_POST['new_module_versions_id']) ? intval($_POST['new_module_versions_id']) : $this->defaultID );
				break;
			case 'reply':
				$out['data'] = $this->makeDropdown( 'new_module_versions_id', intval($_POST['new_module_versions_id']) ? intval($_POST['new_module_versions_id']) : $this->issue['module_versions_reported_id'] );
				break;
		}

		$out['title'] = $this->lang->words['bt_post_assign_version'];

		return $out;
	}

	public function getProjectFilterCookie()
	{
		return array( $this->filterKey => $this->filter);
	}

	public function getProjectFilterDropdown()
	{
		$out = NULL;

		$out = $this->makeDropdown( $this->filterKey, $this->filter, TRUE );

		return $out;
	}

	public function getProjectFilterQueryAdds()
	{
		if ( ! $this->checkPermission('show') )
		{
			return '';
		}

		$out = '';

		if ( $this->filter != 'all' )
		{
			$out = "t.module_versions_reported_id=" . $this->filter;
		}

		return $out;
	}

	public function getProjectFilterURLBit()
	{
		$out = array();

		if ( $this->filter != 'all' )
		{
			$out = "{$this->filterKey}={$this->filter}";
		}

		return $out;
	}

	public function getProjectViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_post_info_version'],
			'type'  => 'column'
		);
	}
	
	public function getPerms()
	{
		return $this->setPerms;
	}

	public function initialize( $project, $metadata = array() )
	{
		$this->project = $project;

		$perms = $metadata['perms'];
		unset( $metadata['perms'] );

		$this->metadata = $metadata;
		
		$this->perms    = array();

		// Set perms for edit button
		$this->setPerms = $perms;

		// Check permissions
		$this->perms['show']      = $this->tracker->fields()->perms()->check( 'show',      $perms );
		$this->perms['submit']    = $this->tracker->fields()->perms()->check( 'submit',    $perms );
		$this->perms['update']    = $this->tracker->fields()->perms()->check( 'update',    $perms );
		$this->perms['developer'] = $this->tracker->fields()->perms()->check( 'developer', $perms );
		$this->perms['alter'] 	  = $this->tracker->fields()->perms()->check( 'alter',     $perms );
				
		foreach( $this->versionsCache as $k => $v )
		{
			if ( $v['project_id'] != $this->project['project_id'] )
			{
				continue;
			}
			
			if ( $v['report_default'] )
			{
				$this->defaultID = $v['version_id'];
			}
		}
	}

	/*-------------------------------------------------------------------------*/
	// Make drop down of versionses
	//
	// USAGE: $select_name   is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the versions that should be selected
	//        $counts        adds counts to each option
	//        $project_id    is required when using $counts - the ID to grab the counts for
	//        $disable       0 = show all, 1 = disable non allownew, 2 = hide non allownew
	//        $allow_all     shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $return_type   0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_versions_id', $selected='', $allowAll=FALSE, $single=FALSE, $returnType='drop' )
	{
		$out = NULL;
		
		$options = array();

		if ( $allowAll && ! $single )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : '';
			$options['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_all_versions']}</option>";
		}
		
		$sel = ( !$selected ) ? "selected='selected'" : '';

		$options[0] = "<option value='0' {$sel}>{$this->lang->words['bt_post_no_version']}</option>";

		foreach( $this->versionsCache as $id => $data )
		{
			if ( $data['project_id'] != $this->project['project_id'] )
			{
				continue;
			}
			
			// Change per Trillium Tech: http://community.invisionpower.com/tracker/issue-35681-always-allow-selected-version-in-dropdown/
			// Selected versions go through regardless of permissions, to avoid it being lost.
			if (! ( $selected == $data['version_id'] ) )
			{
				if ( $single )
				{
					continue;
				}
				if ( $data['permissions'] == 'locked' || ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) )
				{
					continue;
				}
			}

			$data['version_id'] = isset( $data['version_id'] ) ? $data['version_id'] : 0;

			$dis   = ( $disable == 'disable' && $data['permissions'] == 'locked' && ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) ) ? " disabled='disabled'" : "" ;
			$sel   = ( $selected == $data['version_id'] ) ? " selected='selected'" : "" ;
			$count = ( $counts ) ? " ({$count_val} {$this->lang->words['bt_global_versions_dd_reports']})" : "" ;

			$options[$id] = "<option value='{$id}'{$dis}{$sel}>{$data['human']}{$count}</option>";
		}

		switch( $returnType )
		{
			case 'drop':    $out = $this->registry->output->getTemplate('tracker_global')->dropdown_wrapper( $selectName, implode("\n", $options) );
				break;
			case 'array':   $out = $options;
				break;
			case 'options': $out = implode("\n", $options);
				break;
		}

		return $out;
	}

	public function parseToSave()
	{
		return array( 'module_versions_reported_id' => $this->update );
	}

	public function postCommitUpdate( $issue )
	{
		if ( $this->versionsCache[ $this->update ]['fixed_versions'] )
		{
			/*if ( $new_fixed == 0 )
			{
				if ( $this->trackerLibrary->projects[ $issue['project_id'] ]['project_versions'] )
				{
					$versions		= unserialize( $this->trackerLibrary->projects[ $issue['project_id'] ]['project_versions'] );
					$new_fixed		= array_pop( $versions );
					$new_fixed		= $versions['versionId'];
				}
			}*/
			
			/* Facebook */
			//require_once( IPSLib::getAppDir('tracker') . '/sources/classes/facebook/connect.php' );
			//$facebook = new facebook_tracker_connect( $this->registry );
			
			/* Test connection */
			/*$facebook->testConnectSession();
			
			try
			{
				$fbuid = $facebook->FB()->get_loggedin_user();
				
				if ( $facebook->API()->users_hasAppPermission("publish_stream") && $fbuid )
				{
					$attachment	= array(
						'name' => $this->issue['issue_title'],
						'href' => $this->settings['base_url'] . 'app=tracker&showissue=' . $this->issue['issue_id'],
						'caption' => $this->settings['board_name'] . ' -> Tracker -> ' . self::$project['title'],
						'description' => IPSText::truncate( $this->post['post'], '100' )
					);
					
					$control	= array();
					$control[]	= array(
						'href'	=> $this->settings['base_url'] . 'app=tracker&showissue=' . $this->issue['issue_id'],
						'text'	=> 'View Report'
					);
					
					$facebook->API()->stream_publish( "has just resolved the issue, '{$this->issue['issue_title']}', in the project '" . self::$project['title'] . "'.", $attachment, $control );
				}
			}
			catch( Exception $e )
			{
			}*/
		}
	}

	public function projectFilterSetup( $cookie )
	{
		$this->filter = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request[ $this->filterKey ]) ? $this->request[ $this->filterKey ] : NULL,
				2 => isset($cookie[ $this->filterKey ])        ? $cookie[ $this->filterKey ]        : NULL,
				4 => 'all'
			)
		);
	}
	
	public function returnFormName($type)
	{
		$data = array( 'form' => 'new_module_versions_id', 'db' => 'module_versions_reported_id' );
		
		return $data[$type];
	}

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update != $this->old && ! is_null( $this->update ) )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'versions', 'version', $this->old, $this->update );

			$out = array( 'module_versions_reported_id' => $this->update );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;
		
		if ( isset( $this->issue['module_versions_reported_id'] ) && isset( $this->versionsCache[ $this->issue['module_versions_reported_id'] ] ) )
		{
			$this->version	= $this->versionsCache[ $this->issue['module_versions_reported_id'] ];
			$this->update	= $this->issue['module_versions_reported_id'];
		}
	}

	public function validateFilterInput()
	{
		$out = TRUE;

		if ( $this->filter == 'all' || $this->filter == 0 )
		{
			$out = FALSE;
		}
		else if ( isset( $this->versionsCache[ $this->filter ] ) )
		{
			$this->version = $this->versionsCache[ $this->filter ];

			$out = FALSE;
		}

		return $out;
	}
}