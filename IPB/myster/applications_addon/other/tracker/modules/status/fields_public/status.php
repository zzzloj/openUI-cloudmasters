<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
*
* Status field public file
* Last Updated: $Date: 2013-02-16 15:36:07 +0000 (Sat, 16 Feb 2013) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Status
* @link			http://ipbtracker.com
* @version		$Revision: 1404 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Public
 * Module: Status
 * Field: Status
 * Public processing module for the status field
 *
 * @package Tracker
 * @subpackage Module-Status
 * @since 2.0.0
 */
class public_tracker_module_status_field_status extends iptCommand implements iField
{
	protected $cache;

	protected $status;
	protected $statusCache;

	protected $updateStatusIds = array();

	protected $defaultID = NULL;

	// Mysql strict errors, has to be integer
	protected $old       = 0;
	protected $update    = 0;

	protected $filterKey = 'catfilter';
	protected $module    = 'status';

	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;

	public $hasShutdown	= true;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_status' ) );

		$this->cache = $this->tracker->cache('status', 'status');
		$this->statusCache  = $this->cache->getCache();

		// Get default
		foreach( $this->statusCache as $k => $v )
		{
			if ( $v['default_open'] )
			{
				$this->defaultID = $k;
			}
		}
	}

	public function addNavigation()
	{
		$out = NULL;

		if ( is_array( $this->status ) )
		{
			$out = array( 'key' => "{$this->filterKey}={$this->status['status_id']}", 'value' => $this->lang->words['bt_issue_assign_status'] . ' ' . $this->status['title'] );
		}

		return $out;
	}

	public function addReplyText( $issue, $post )
	{
		$out = '';

		// Reply text
		if ( isset( $this->statusCache[ $this->update ]['reply_text'] ) && $this->statusCache[ $this->update ]['reply_text'] && ! $post )
		{
			$out = $this->statusCache[ $this->update ]['reply_text'] . "<br />";
		}

		return $out;
	}

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] || $this->tracker->moderators()->checkFieldPermission( $this->project['project_id'], $this->module, 'status', $type ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function checkForInputErrors( $postModule )
	{
		$inputID = intval($this->request['new_module_status_id']);

		if ( ! isset( $this->statusCache[ $inputID ] ) || ! is_array( $this->statusCache[ $inputID ] ) )
		{
			$this->registry->output->showError( $this->lang->words['bt_sts_err_no_sts'] );
		}

		$status = $this->statusCache[$inputID];

		if ( $status['allow_new'] == 0 && ! $this->checkPermission('allowall') && $postModule == 'new' )
		{
			$this->registry->output->showError( $this->lang->words['bt_sts_err_no_perm'] );
		}

		if ( $status['closed'] && (!$this->memberData['g_tracker_ismod'] || $postModule == 'new')  )
		{
			$this->registry->output->showError( $this->lang->words['bt_sts_err_locked_sts'] );
		}
	}

	public function compileFieldChange( $row )
	{
		$out = FALSE;

		// Multi checks
		$this->request['new_module_status_id'] = $this->request['new_module_status_id'] ? $this->request['new_module_status_id'] : $this->request['module_status_id'];

		$statusID    = $row['module_status_id'];
		$newStatusID = isset( $this->request['new_module_status_id'] ) ? intval( $this->request['new_module_status_id'] ) : $row['module_status_id'];

		// Bug fix
		$this->old = $statusID;

		if ( ! isset( $this->statusCache[ $newStatusID ] ) || ! is_array( $this->statusCache[ $newStatusID ] ) )
		{
			$newStatusID = $statusID;
		}

		if ( $statusID != $newStatusID )
		{
			$out = TRUE;
		}

		$this->update = $newStatusID;

		/* Find default one if it's not set */
		if(is_null($this->update))
		{
			foreach($this->statusCache as $c)
			{
				if($c['default_open'])
				{
					$this->update = $c['status_id'];
					$out = TRUE;
					break;
				}
			}
		}

		return $out;
	}

	public function display( $issue, $type='project' )
	{
		if ( ! $issue['module_status_id'] && $issue['issue_id'] )
		{
			$this->updateStatusIds[]	= $issue['issue_id'];
			$issue['module_status_id']	= $this->defaultID;
		}

		$data		= array();

		if ( isset($issue['module_severity_id']) )
		{
			$severity = 'severity_' . $issue['module_severity_id'];
		}

		switch( $type )
		{
			case 'project':
				$data	= array(
					'title'	=> $this->lang->words['bt_issue_info_status'],
					'data'	=> "<span class='ipsBadge {$severity}' style='color:grey;'>" . $this->statusCache[$issue['module_status_id']]['title'] . '</span>',
					'type'	=> 'inline'
				);
				break;

			case 'issue':
				$data	= array(
					'title'	=> $this->lang->words['bt_issue_info_status'],
					'data'	=> $this->statusCache[$issue['module_status_id']]['title'],
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
					'changedFrom'	=> $this->lang->words['bt_changed_from'] . " " . $this->statusCache[ $change['old_value'] ]['title'],
					'entryLang'		=> sprintf( $this->lang->words['bt_changed_to'], IPSTEXT::mbstrtolower($this->lang->words['bt_issue_info_status']) ),
					'value'			=> $this->statusCache[ $change['new_value'] ]['title']
				),
				$change
			);
		}

		return $change;
	}

	public function getQuickReplyDropdown( $issue )
	{
		$out = array();

		$out['title'] = $this->lang->words['bt_issue_assign_status'];
		$out['data']  = $this->makeDropdown( 'new_module_status_id', $issue['module_status_id'], false, ( $this->checkPermission('allowall') ? 'show' : 'hide' ) );

		return $out;
	}

	public function getIssueViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_issue_info_status'],
			'type'  => 'column'
		);
	}

	public function getPostScreen( $type )
	{
		$out = array();

		switch ( $type )
		{
			case 'new':
				$out['data'] = $this->makeDropdown( 'new_module_status_id', intval($_POST['new_module_status_id']) ? intval($_POST['new_module_status_id']) : $this->defaultID, false, ( $this->checkPermission('allowall') ? 'show' : 'hide' ) );
				break;
			case 'reply':
				$out['data'] = $this->makeDropdown( 'new_module_status_id', intval($_POST['new_module_status_id']) ? intval($_POST['new_module_status_id']) : $this->issue['module_status_id'], false, ( $this->checkPermission('allowall') ? 'show' : 'hide' ) );
				break;
		}

		$out['title'] = $this->lang->words['bt_issue_assign_status'];

		return $out;
	}

	public function getProjectFilterCookie()
	{
		return array( $this->filterKey => $this->status['status_id'] );
	}

	public function getProjectFilterDropdown()
	{
		$out = NULL;

		$out = $this->makeDropdown( $this->filterKey, $this->status['status_id'], TRUE, 'show', TRUE );

		return $out;
	}

	public function getProjectFilterQueryAdds()
	{
		if ( ! $this->checkPermission('show') )
		{
			return '';
		}

		$out = '';

		if ( is_array( $this->status ) )
		{
			$out = "t.module_status_id=" . $this->status['status_id'];
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
			'title' => $this->lang->words['bt_issue_info_status'],
			'type'  => 'inline'
		);
	}

	public function returnFormName($type)
	{
		$data = array( 'form' => 'new_module_status_id', 'db' => 'module_status_id' );

		return $data[$type];
	}

	public function initialize( $project, $metadata = array() )
	{
		$this->project = $project;

		$perms = $metadata['perms'];
		unset( $metadata['perms'] );

		$this->metadata = $metadata;
		$this->perms    = array();

		// Check permissions
		$this->perms['show']     = $this->tracker->fields()->perms()->check( 'show',     $perms );
		$this->perms['submit']   = $this->tracker->fields()->perms()->check( 'submit',   $perms );
		$this->perms['update']   = $this->tracker->fields()->perms()->check( 'update',   $perms );
		$this->perms['allowall'] = $this->tracker->fields()->perms()->check( 'allowall', $perms );
	}

	/*-------------------------------------------------------------------------*/
	// Make drop down of statuses
	//
	// USAGE: $select_name   is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the status that should be selected
	//        $counts        adds counts to each option
	//        $project_id    is required when using $counts - the ID to grab the counts for
	//        $disable       0 = show all, 1 = disable non allownew, 2 = hide non allownew
	//        $allow_all     shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $return_type   0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_status_id', $selected=0, $counts=FALSE, $disable='show', $allowAll=FALSE, $single=FALSE, $returnType='drop' )
	{
		$out = NULL;

		$options = array();

		if ( $allowAll && ! $single )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : "" ;
			$options['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_global_status_dd_all']}</option>";
		}

		foreach( $this->statusCache as $id => $data )
		{
			if ( $single && ! ( $selected == $data['status_id'] ) )
			{
				continue;
			}

			if ( $disable == 'hide' && ! $data['allow_new'] && ! ( $selected == $data['status_id'] ) )
			{
				continue;
			}

			if ( $data['closed'] && (!$this->memberData['g_tracker_ismod'] || $this->request['do'] == 'postnew') )
			{
				continue;
			}

			$data['status_id'] = isset( $data['status_id'] ) ? $data['status_id'] : 0;

			if ( $counts )
			{
				$countData		= unserialize( $this->project['serial_data'] );
				$count_val		= isset( $countData['status'][ $data['status_id'] ] ) ? $countData['status'][ $data['status_id'] ] : 0;
			}

			$dis   = ( $disable == 'disable' && ! $data['allow_new'] ) ? " disabled='disabled'" : "" ;
			$sel   = ( $selected == $data['status_id'] ) ? " selected='selected'" : "" ;
			$count = ( $counts ) ? " ({$count_val} {$this->lang->words['bt_projects_reports']})" : "" ;

			$options[$id] = "<option value='{$id}'{$dis}{$sel}>{$data['title']}{$count}</option>";
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
		/* Find default one if it's not set */
		if(!$this->update)
		{
			foreach($this->statusCache as $c)
			{
				if($c['default_open'])
				{
					$this->update = $c['status_id'];
					break;
				}
			}
		}

		return array( 'module_status_id' => $this->update );
	}

	public function postCommitUpdate( $issue )
	{
		if ( $this->statusCache[ $this->update ]['fixed_status'] )
		{

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

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update != $this->old && ! is_null( $this->update )  )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'status', 'status', $this->old, $this->update );

			$out = array( 'module_status_id' => $this->update );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;

		if ( isset( $this->issue['module_status_id'] ) && isset( $this->statusCache[ $this->issue['module_status_id'] ] ) )
		{
			$this->status 	= $this->statusCache[ $this->issue['module_status_id'] ];
			$this->update	= $this->issue['module_status_id'];
		}
	}

	public function validateFilterInput()
	{
		$out = TRUE;

		if ( $this->filter == 'all' )
		{
			$out = FALSE;
		}
		else if ( isset( $this->statusCache[ $this->filter ] ) )
		{
			$this->status = $this->statusCache[ $this->filter ];

			$out = FALSE;
		}

		return $out;
	}
}