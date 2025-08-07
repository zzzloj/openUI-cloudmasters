<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Privacy field public file
* Last Updated: $Date: 2012-05-29 14:08:19 +0100 (Tue, 29 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Privacy
* @link			http://ipbtracker.com
* @version		$Revision: 1372 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Public
 * Module: Privacy
 * Field: Privacy
 * Public processing module for the privacy field
 * 
 * @package Tracker
 * @subpackage Module-Privacy
 * @since 2.0.0
 */
class public_tracker_module_privacy_field_privacy extends iptCommand implements iField
{
	protected $privacy;
	
	// Mysql strict errors, has to be integer
	protected $old       = 0;
	protected $update    = 0;

	protected $filterKey = 'privfilter';
	protected $module    = 'privacy';

	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;

	protected $filter;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_privacy' ) );
	}

	public function addNavigation()
	{
		$out = NULL;

		if ( isset( $this->privacy ) && $this->privacy != 'all' )
		{
			$out = array( 'key' => "{$this->filterKey}={$this->privacy}", 'value' => $this->lang->words['bt_project_breadcrumb_privacy'] . $this->lang->words['bt_global_privacy_'.$this->privacy ] );
		}

		return $out;
	}

	public function checkForInputErrors( $postModule ) { return false; }

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] || $this->tracker->moderators()->checkFieldPermission( $this->project['project_id'], $this->module, 'privacy', $type ) )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function compileFieldChange( $row )
	{
		if ( ! $this->checkPermission('update') && ! $this->checkPermission('submit') )
		{
			return false;
		}

		$out = FALSE;

		$privacy    = $row['module_privacy'];
		$newPrivacy = isset( $this->request['new_module_privacy'] ) ? intval( $this->request['new_module_privacy'] ) : $row['module_privacy'];
		
		// Bug fix
		$this->old    = $privacy;

		if ( $newPrivacy !== 1 && $newPrivacy !== 0 )
		{
			$newPrivacy = $privacy;
		}

		if ( $privacy != $newPrivacy )
		{
			$out = TRUE;
		}

		$this->update = $newPrivacy;

		return $out;
	}
	
	public function display( $issue, $type = 'project' )
	{
		$data		= array();
		$lang		= $this->lang->words['bt_global_privacy_' . $issue['module_privacy']];
		$padding	= 0;

		switch( $type )
		{
			case 'project':
				$data	= array(
					'title'	=> $this->lang->words['bt_issue_info_privacy'],
					'data'	=> "<span class='privacy_{$issue['module_privacy']}' style='padding:3px;'>" . $lang . "</span>",
					'type'	=> 'privacy'
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
			$type = array( 0 => 'public', 1 => 'private' );
			
			$change = array_merge(
				array(
					'entryLang'		=> $this->lang->words['bt_privacy_made_issue'],
					'value'			=> $type[ $change['new_value'] ]
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

		$out['title'] = $this->lang->words['bt_issue_assign_privacy'];
		$out['data']  = $this->makeDropdown( 'new_module_privacy', $issue['module_privacy'] );

		return $out;
	}

	public function getIssueViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_issue_info_privacy'],
			'type'  => 'privacy'
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
				
				$out['data'] = $this->makeDropdown( 'new_module_privacy',     intval($_POST['new_module_privacy'])     ? intval($_POST['new_module_privacy'])     : 0 );
				break;
			case 'reply':
				if ( ! $this->checkPermission('update') )
				{
					return false;
				}
				
				$out['data'] = $this->makeDropdown( 'new_module_privacy', intval($_POST['new_module_privacy']) ? intval($_POST['new_module_privacy']) : $this->issue['module_privacy'] );
				break;
		}

		$out['title'] = $this->lang->words['bt_issue_assign_privacy'];

		return $out;
	}

	public function getProjectFilterCookie()
	{
		return array( $this->filterKey => $this->privacy );
	}

	public function getProjectFilterDropdown()
	{
		$out = NULL;

		if ( $this->checkPermission('show') )
		{
			$out = $this->makeDropdown( $this->filterKey, $this->privacy, TRUE );
		}
		
		return $out;
	}

	public function getProjectFilterQueryAdds()
	{
		$out = '';

		if ( ! $this->checkPermission('show') )
		{
			$out = "(t.module_privacy=0 OR t.starter_id={$this->memberData['member_id']})";
		}
		else if ( $this->privacy != 'all' )
		{
			$out = "t.module_privacy=" . $this->privacy;
		}

		return $out;
	}

	public function getProjectFilterURLBit()
	{
		$out = '';

		if ( ! $this->checkPermission('show') )
		{
			return $out;
		}
		
		if ( $this->privacy != 'all' )
		{
			$out = "{$this->filterKey}={$this->privacy}";
		}

		return $out;
	}

	public function getProjectViewSetup()
	{
		return array(
			'title' => $this->lang->words['bt_issue_info_privacy'],
			'type'  => 'privacy'
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
	// Make drop down of privacyes
	//
	// USAGE: $select_name   is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the privacy that should be selected
	//        $counts        adds counts to each option
	//        $project_id    is required when using $counts - the ID to grab the counts for
	//        $disable       0 = show all, 1 = disable non allownew, 2 = hide non allownew
	//        $allow_all     shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $return_type   0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_privacy', $selected=0, $allowAll=FALSE, $single=FALSE, $returnType='drop' )
	{
		$out = NULL;
		
		$options = array();

		if ( $allowAll && ! $single )
		{
			$sel = ( $selected == 'all' ) ? "selected='selected'" : "" ;
			$options['all'] = "<option value='all' {$sel}>{$this->lang->words['bt_global_privacy_dd_all']}</option>";
		}

		for( $count=1; $count >= 0; $count-- )
		{
			if ( $single && ! ( $selected == $count ) )
			{
				continue;
			}

			$sel = ( strval($selected) == strval($count) ) ? "selected='selected'" : "" ;

			$options[$count] = "<option value='{$count}' {$sel} {$col}>{$this->lang->words['bt_global_privacy_dd_'.$count]}</option>";
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
		return array( 'module_privacy' => $this->update );
	}

	public function postCommitUpdate( $issue ) {}

	public function projectFilterSetup( $cookie )
	{
		$this->privacy = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request[ $this->filterKey ]) ? $this->request[ $this->filterKey ] : NULL,
				2 => isset($cookie[ $this->filterKey ])        ? $cookie[ $this->filterKey ]        : NULL,
				4 => 'all'
			)
		);
	}
	
	public function returnFormName($type)
	{
		$data = array( 'form' => 'new_module_privacy', 'db' => 'module_privacy' );
		
		return $data[$type];
	}

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update != $this->old && ! is_null( $this->update ) )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'privacy', 'privacy', $this->old, $this->update );

			$out = array( 'module_privacy' => $this->update );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;

		if ( isset( $this->issue['module_privacy'] ) )
		{
			$this->privacy	= $this->issue['module_privacy'];
			$this->update	= $this->privacy;
		}
		
		// A few permissions
		if ( $this->privacy == 1 && ! $this->checkPermission('show') )
		{
			if ( $issue['starter_id'] == $this->memberData['member_id'] )
			{
				$this->perms['show'] = 1;
				
				return true;
			}

			$this->registry->output->showError( 'We could not find the issue you were attempting to view' );
		}
	}

	public function validateFilterInput()
	{
		$out = TRUE;

		if ( $this->privacy == 'all' || $this->privacy == 0 || $this->privacy == 1 )
		{
			$out = FALSE;
		}

		return $out;
	}
}