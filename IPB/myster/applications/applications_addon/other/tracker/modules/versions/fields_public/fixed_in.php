<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Versions field public file
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
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
class public_tracker_module_versions_field_fixed_in extends iptCommand implements iField
{
	protected $cache;

	protected $versions;
	protected $versionsCache;

	protected $defaultID = NULL;
	
	// Mysql strict errors, has to be integer
	protected $old       = 0;
	protected $update    = 0;

	protected $module    = 'versions';

	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_versions' ) );

		$this->cache = $this->tracker->cache('versions', 'versions');
		$this->versionsCache  = $this->cache->getCache();
	}

	public function addNavigation() {}

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] || $this->tracker->moderators()->checkFieldPermission( $this->project['project_id'], $this->module, 'fixed_in', $type ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function checkForInputErrors( $postModule )
	{
		if ( $postModule == 'new' ) { return false; }
		
		if ( ! isset($this->request['module_versions_fixed_id']) && ! isset($this->request['new_module_versions_fixed_id']) )
		{
			$this->registry->output->showError( $this->lang->words['bt_ver_err_fixed_no'] );
		}
		
		$id = intval($this->request['module_versions_fixed_id']) ? intval($this->request['module_versions_fixed_id']) : intval($this->request['new_module_versions_fixed_id']);
		
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
	}

	public function compileFieldChange( $row )
	{
		$out = FALSE;

		$versionsID    = $row['module_versions_fixed_id'];
		$newVersionsID = isset( $this->request['new_module_versions_fixed_id'] ) ? intval( $this->request['new_module_versions_fixed_id'] ) : $versionsID;

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
					'title'	=> $this->lang->words['bt_post_info_ver_fix'],
					'data'	=> $this->versionsCache[$issue['module_versions_fixed_id']]['human'] ? $this->versionsCache[$issue['module_versions_fixed_id']]['human'] : '-',
					'type'	=> 'column',
					'extra'	=> 'fixed_in'
				);
			break;
		}
		
		return $data;
	}
	
	public function fixedInList( $project )
	{
		if ( ! $this->checkPermission('report') )
		{
			return false;
		}
		
		return $this->makeDropdown( 'fixed_in', '', true, false, 'drop', false );
	}
	
	public function formatMeta( $change )
	{
		if ( $this->checkPermission('show') )
		{
			// @todo Lang abstract
			$change = array_merge(
				array(
					'changedFrom'	=> $this->lang->words['bt_changed_from'] . " " . $this->versionsCache[ $change['old_value'] ]['human'] ? $this->versionsCache[ $change['old_value'] ]['human'] : $this->lang->words['bt_post_no_version'],
					'entryLang'		=> sprintf( $this->lang->words['bt_changed_to'], $this->lang->words['bt_ver_fixed_in'] ),
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

		$out['title'] = $this->lang->words['bt_post_assign_ver_fix'];
		$out['data']  = $this->makeDropdown( 'new_module_versions_fixed_id', $issue['module_versions_fixed_id'] );

		return $out;
	}

	public function getIssueViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_post_info_ver_fix'],
			'type'  => 'column'
		);
	}

	public function getPostScreen( $type )
	{
		$out = array();

		switch ( $type )
		{
			case 'new':
				$out['data'] = $this->makeDropdown( 'new_module_versions_fixed_id', intval($_POST['new_module_versions_fixed_id']) ? intval($_POST['new_module_versions_fixed_id']) : $this->defaultID );
				break;
			case 'reply':
				$out['data'] = $this->makeDropdown( 'new_module_versions_fixed_id', intval($_POST['new_module_versions_fixed_id']) ? intval($_POST['new_module_versions_fixed_id']) : $this->issue['module_versions_fixed_id'] );
				break;
		}

		$out['title'] = $this->lang->words['bt_post_assign_ver_fix'];

		return $out;
	}

	public function getProjectFilterCookie() { return array(); }
	public function getProjectFilterDropdown() { return ''; }
	public function getProjectFilterQueryAdds() { return '';}
	public function getProjectFilterURLBit() { return array(); }

	public function getProjectViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_post_info_ver_fix'],
			'type'  => 'column'
		);
	}

	public function initialize( $project, $metadata = array() )
	{
		$this->project = $project;

		$perms = $metadata['perms'];
		unset( $metadata['perms'] );

		$this->metadata = $metadata;
		$this->perms    = array();

		// Check permissions
		$this->perms['show']      = $this->tracker->fields()->perms()->check( 'show',      $perms );
		$this->perms['submit']    = $this->tracker->fields()->perms()->check( 'submit',    $perms );
		$this->perms['update']    = $this->tracker->fields()->perms()->check( 'update',    $perms );
		$this->perms['developer'] = $this->tracker->fields()->perms()->check( 'developer', $perms );
		$this->perms['report']    = $this->tracker->fields()->perms()->check( 'report',    $perms );
	}

	/*-------------------------------------------------------------------------*/
	// Make drop down of versions
	//
	// USAGE: $selectName    is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the versions that should be selected
	//        $allowAll      shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $returnType    0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_versions_fixed_id', $selected='', $allowAll=FALSE, $single=FALSE, $returnType='drop', $showNVA=TRUE )
	{
		$out = NULL;
		
		$options = array();

		if ( $allowAll && ! $single )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : '';
			$options['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_all_versions']}</option>";
		}
		
		$sel = ( !$selected ) ? "selected='selected'" : '';

		if ( $showNVA )
		{
			$options[0] = "<option value='0' {$sel}>{$this->lang->words['bt_post_no_version']}</option>";
		}
		
		foreach( $this->versionsCache as $id => $data )
		{
			if ( $data['project_id'] != $this->project['project_id'] )
			{
				continue;
			}
			
			if ( $single && ! ( $selected == $data['version_id'] ) )
			{
				continue;
			}
			
			if ( $data['permissions'] == 'locked' || ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) )
			{
				continue;
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
		return array('module_versions_fixed_id' => $this->update );
	}

	public function postCommitUpdate( $issue )
	{
	}

	public function projectFilterSetup( $cookie )
	{
	}
	
	public function returnFormName($type)
	{
		$data = array( 'form' => 'new_module_versions_fixed_id', 'db' => 'module_versions_fixed_id' );
		
		return $data[$type];
	}

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update != $this->old && ! is_null( $this->update ) )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'versions', 'fixed_in', $this->old, $this->update );

			$out = array( 'module_versions_fixed_id' => $this->update );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;
		
		if ( isset( $this->issue['module_versions_fixed_id'] ) && isset( $this->versionsCache[ $this->issue['module_versions_fixed_id'] ] ) )
		{
			$this->version	= $this->versionsCache[ $this->issue['module_versions_fixed_id'] ];
			$this->update	= $this->issue['module_versions_fixed_id'];
		}
	}

	public function validateFilterInput()
	{
		return false;
	}
}