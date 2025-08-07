<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Severity field public file
* Last Updated: $Date: 2012-12-06 00:21:06 +0000 (Thu, 06 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
* @link			http://ipbtracker.com
* @version		$Revision: 1394 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Public
 * Module: Severity
 * Field: Severity
 * Public processing module for the severity field
 * 
 * @package Tracker
 * @subpackage Module-Severity
 * @since 2.0.0
 */
class public_tracker_module_severity_field_severity extends iptCommand implements iField
{
	protected $cache;

	protected $severity;
	protected $severityCache;

	protected $defaultID = 0;
	
	// Mysql strict errors, has to be integer
	protected $old       = 0;
	protected $update    = 0;

	protected $filterKey = 'sevfilter';
	protected $module    = 'severity';

	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;

	protected $filter;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_severity' ) );

		$this->cache  = $this->tracker->cache('severity', 'severity');
		$this->severityCache = $this->cache->getCache();
		
		$this->severityCache = $this->severityCache['skin' . $this->registry->output->skin['set_id'] ];

		$this->registry->output->addToDocumentHead( 'raw', "<link rel='stylesheet' type='text/css' title='Main' media='screen' href='{$this->settings['board_url']}/cache/tracker/skin{$this->registry->output->skin['set_id']}_severity.css' />\n" );
	}

	public function addNavigation()
	{
		$out = NULL;

		if ( is_array( $this->severity ) )
		{
			$out = array( 'key' => "{$this->filterKey}={$this->severity['severity_id']}", 'value' => $this->lang->words['bt_issue_assign_severity'] . ' ' . $this->lang->words['bt_global_severity_'.$this->severity['severity_id'] ] );
		}

		return $out;
	}
	
	public function checkForInputErrors( $postModule ) { return false; }

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] || $this->tracker->moderators()->checkFieldPermission( $this->project['project_id'], $this->module, 'severity', $type ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function compileFieldChange( $row )
	{
		if ( ! $this->checkPermission('update') && ! $this->checkPermission('submit') )
		{
			$this->update = $row['module_severity_id'];
			return false;
		}
		
		$out = FALSE;

		// Multi checks
		$this->request['new_module_severity_id'] = $this->request['new_module_severity_id'] ? $this->request['new_module_severity_id'] : $this->request['module_severity_id'];

		$severityID    = $row['module_severity_id'];
		$newSeverityID = isset( $this->request['new_module_severity_id'] ) ? intval( $this->request['new_module_severity_id'] ) : $row['module_severity_id'];

		// Bug fix
		$this->old = $severityID;

		if ( isset( $this->request['new_module_severity_id'] ) && intval( $this->request['new_module_severity_id'] ) === 0 )
		{
			$newSeverityID = 0;
		}
		else if ( ! isset( $this->severityCache[ $newSeverityID ] ) || ! is_array( $this->severityCache[ $newSeverityID ] ) )
		{
			$newSeverityID = $severityID;
		}
		
		if ( $severityID != $newSeverityID )
		{
			$out = TRUE;
		}

		$this->update = $newSeverityID;

		return $out;
	}
	
	public function display( $issue, $type = 'project' )
	{
		if ( ! $this->checkPermission('show') )
		{
			return false;
		}
		
		$data		= array();
		$bg			= $this->severityCache[ $issue['module_severity_id'] ]['background_color'];
		$lang		= $issue['module_severity_id'] . ' - ' . $this->lang->words['bt_global_severity_' . $issue['module_severity_id']];
		$padding	= 0;

		if ( $issue['module_severity_id'] != 0 )
		{
			$padding = 4;
		}

		switch( $type )
		{
			case 'project':
				if ( ! isset($issue['module_status_id']) )
				{
					$data	= array(
						'title'	=> $this->lang->words['bt_issue_info_severity'],
						'data'	=> "<span class='ipsBadge severity_{$issue['module_severity_id']}'>" . $lang . "</span>",
						'type'	=> 'inline'
					);
				}
			break;
			case 'issue':
				$data	= array(
					'title'	=> $this->lang->words['bt_issue_info_severity'],
					'data'	=> "<span class='severity_{$issue['module_severity_id']} sev_full' style='padding:3px;'>" . $lang . "</span>",
					'type'	=> 'severity'
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
					'changedFrom'	=> $this->lang->words['bt_changed_from'] . " " . $change['old_value'] . ' - ' . $this->lang->words['bt_global_severity_' . $change['old_value']],
					'entryLang'		=> sprintf( $this->lang->words['bt_changed_to'], IPSTEXT::mbstrtolower($this->lang->words['bt_issue_info_severity']) ),
					'value'			=> $change['new_value'] . ' - ' . $this->lang->words['bt_global_severity_' . $change['new_value']]
				),
				$change
			);
		}
				
		return $change;
	}

	public function getQuickReplyDropdown( $issue )
	{
		if ( ! $this->checkPermission('update') )
		{
			return false;
		}
		
		$out = array();

		$out['title'] = $this->lang->words['bt_issue_assign_severity'];
		$out['data']  = $this->makeDropdown( 'new_module_severity_id', $issue['module_severity_id'] );

		return $out;
	}

	public function getIssueViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_issue_info_severity'],
			'type'  => 'severity'
		);
	}

	public function getPostScreen( $type )
	{
		$out = array();

		switch ( $type )
		{
			case 'new':
				if ( ! $this->checkPermission('submit') )
				{
					return false;
				}
				
				$out['data'] = $this->makeDropdown( 'new_module_severity_id',     intval($_POST['new_module_severity_id'])     ? intval($_POST['new_module_severity_id'])     : 0 );
				break;
			case 'reply':
				if ( ! $this->checkPermission('update') )
				{
					return false;
				}
				
				$out['data'] = $this->makeDropdown( 'new_module_severity_id', intval($_POST['new_module_severity_id']) ? intval($_POST['new_module_severity_id']) : $this->issue['module_severity_id'] );
				break;
		}

		$out['title'] = $this->lang->words['bt_issue_assign_severity'];

		return $out;
	}

	public function getProjectFilterCookie()
	{
		return array( $this->filterKey => $this->filter );
	}

	public function getProjectFilterDropdown()
	{
		if ( ! $this->checkPermission('show') )
		{
			return '';
		}

		$out = NULL;

		$out = $this->makeDropdown( $this->filterKey, $this->filter, TRUE, TRUE );

		return $out;
	}

	public function getProjectFilterQueryAdds()
	{
		if ( ! $this->checkPermission('show') )
		{
			return false;
		}
		
		$out = '';

		if ( $this->filter != 'all' )
		{
			$out = "t.module_severity_id=" . $this->filter;
		}

		return $out;
	}

	public function getProjectFilterURLBit()
	{
		if ( ! $this->checkPermission('show') )
		{
			return false;
		}
		
		$out = '';

		if ( $this->filter != 'all' )
		{
			$out = "{$this->filterKey}={$this->filter}";
		}

		return $out;
	}

	public function getProjectViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_issue_info_severity'],
			'type'  => 'severity'
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
		$this->perms['show']     = $this->tracker->fields()->perms()->check( 'show',     $perms );
		$this->perms['submit']   = $this->tracker->fields()->perms()->check( 'submit',   $perms );
		$this->perms['update']   = $this->tracker->fields()->perms()->check( 'update',   $perms );
	}

	/*-------------------------------------------------------------------------*/
	// Make drop down of severityes
	//
	// USAGE: $select_name   is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the severity that should be selected
	//        $counts        adds counts to each option
	//        $project_id    is required when using $counts - the ID to grab the counts for
	//        $disable       0 = show all, 1 = disable non allownew, 2 = hide non allownew
	//        $allow_all     shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $return_type   0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_severity_id', $selected=0, $colors=TRUE, $allowAll=FALSE, $single=FALSE, $returnType='drop' )
	{
		$out = NULL;
		
		$options = array();

		$sev_array = array();

		if ( $allowAll && ! $single )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : "" ;
			$options['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_global_severity_dd_all']}</option>";
		}

		for( $count=0; $count < 6; $count++ )
		{
			if ( $single && ! ( $selected == $count ) )
			{
				continue;
			}

			$sel = ( strval($selected) == strval($count) ) ? "selected='selected'" : "" ;
			$col = ( $colors ) ? "class='severity_{$count}'" : '';

			$options[$count] = "<option value='{$count}' {$sel} {$col}>{$count} - {$this->lang->words['bt_global_severity_dd_'.$count]}</option>";
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
		return array( 'module_severity_id' => $this->update );
	}

	public function postCommitUpdate( $issue )
	{
		if ( $this->severityCache[ $this->update ]['fixed_severity'] )
		{
			// facebook api
		}
	}

	public function projectFilterSetup( $cookie )
	{
		if ( ! $this->checkPermission('show') )
		{
			return false;
		}
		
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
		$data = array( 'form' => 'new_module_severity_id', 'db' => 'module_severity_id' );
		
		return $data[$type];
	}

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update != $this->old && ! is_null( $this->update ) )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'severity', 'severity', $this->old, $this->update );

			$out = array( 'module_severity_id' => $this->update );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;
		
		if ( isset( $this->issue['module_severity_id'] ) && isset( $this->severityCache[ $this->issue['module_severity_id'] ] ) )
		{
			$this->severity = $this->severityCache[ $this->issue['module_severity_id'] ];
			$this->severity['severity_id'] = $this->issue['module_severity_id'];
			$this->update	= $this->issue['module_severity_id'];
		}
	}

	public function validateFilterInput()
	{
		if ( ! $this->checkPermission('show') )
		{
			return false;
		}
		
		$out = TRUE;

		if ( $this->filter == 'all' )
		{
			$out = FALSE;
		}
		else if ( isset( $this->severityCache[ $this->filter ] ) )
		{
			$this->severity = $this->severityCache[ $this->filter ];
			$this->severity['severity_id'] = $this->filter;
			
			$out = FALSE;
		}
		else if ( $this->filter == 0 )
		{
			$out = FALSE;
		}

		return $out;
	}
}