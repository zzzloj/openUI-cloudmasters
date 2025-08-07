<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
*
* Custom field public file
* Last Updated: $Date: 2011-01-22 16:46:35 +0000 (Sat, 22 Jan 2011) $
*
* @author		$Author: alex $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Custom
* @link			http://ipbtracker.com
* @version		$Revision: 1213 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Public
 * Module: Custom
 * Field: Custom
 * Public processing module for the Custom field
 *
 * @package Tracker
 * @subpackage Module-Custom
 * @since 2.0.0
 */
class public_tracker_module_custom_field_custom extends iptCommand
{
	protected $cache;

	protected $custom;
	protected $customCache;
	protected $projectCustomCache;

	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_module_custom' ) );

		$this->cache = $this->tracker->cache('custom', 'custom');
		$this->customCache  = $this->cache->getCache();

		$this->projectCustomCache	= array();

		// Separate it out into projects
		foreach( $this->customCache as $k => $v )
		{
			// Projects
			foreach( explode(',', $v['projects']) as $id )
			{
				$this->projectCustomCache[$id][] = $v;
			}
		}
	}

	public function initialize( $project, $metadata = array() )
	{
		return $this->projectCustomCache[ $project['project_id'] ];
	}
}

class public_tracker_module_custom_field_custom_field extends iptCommand implements iField
{
	protected $cache;
	protected $permLib;

	protected $custom;

	protected $defaultID = NULL;
	protected $old       = NULL;
	protected $update    = NULL;

	// protected $filterKey = '';
	protected $module    = 'custom';

	protected $issue;
	protected $metadata;
	protected $perms;
	protected $project;

	protected $filter;

	public function doExecute( ipsRegistry $registry ){}

	public function addNavigation()
	{
		$this->projectFilterSetup( array() );

		$out = NULL;

		if ( !empty( $this->filter ) && $this->filter != 'all' )
		{
			$out = array( 'key' => "{$this->metadata['field_keyword']}={$this->filter}", 'value' => $this->metadata['title'] . ': ' . $this->metadata['options'][ $this->filter ]['human'] );
		}

		return $out;
	}

	public function setModuleCache( $module )
	{
		$this->mCache &= $module;
	}

	public function checkForInputErrors( $postModule )
	{
		if ( ( ! $this->checkPermission('submit') && $postModule == 'new') OR ( ! $this->checkPermission('update') && $postModule == 'reply' ) )
		{
			return false;
		}

		if ( $this->metadata['not_null'] && ( 	!isset( $this->request['field_' . $this->metadata['field_id'] ] ) ||
												empty( $this->request['field_' . $this->metadata['field_id'] ] ) ||
												trim( $this->request['field_' . $this->metadata['field_id'] ] ) == '' ) )
		{
			$this->registry->output->showError( 'A required field was left empty' );
		}

		$id = IPSText::stripslashes( trim( $this->request['field_' . $this->metadata['field_id']] ) );

		// Some setting checks, max input etc.
		if ( $this->metadata['max_input'] && strlen($id) > $this->metadata['max_input'] )
		{
			$this->registry->output->showError( 'You have exceed the allowed size of this field. The maximum input allowed is ' . $this->metadata['max_input'] . ' characters, and you are using ' . strlen($id) );
		}

		// Input format
		if ( trim($this->metadata['input_format']) )
		{
			$regex = str_replace( 'n', '\\d', preg_quote( $this->metadata['field_input_format'], "#" ) );
			$regex = str_replace( 'a', '\\w', $regex );

			if ( ! preg_match( '#^' . $regex . '$#i', trim( $this->request[ 'field_' . $this->metadata['field_id'] ] ) ) )
			{
				$this->registry->output->showError( 'You have entered illegal characters into a ' . $this->metadata['title'] . '. The allowed input format is ' . $this->metadata['input_format'] );
			}
		}

		return true;
	}

	public function checkPermission( $type )
	{
		$out = FALSE;

		if ( $this->perms[ $type ] )
		{
			$out = TRUE;
		}

		return $out;
	}

	public function compileFieldChange( $row )
	{
		$out = FALSE;

		$customID    = $row['field_' . $this->metadata['field_id'] ];
		$newCustomID = isset( $this->request['field_' . $this->metadata['field_id'] ] ) ? IPSText::stripslashes( trim( $this->request['field_' . $this->metadata['field_id'] ] ) ) : $row['field_' . $this->metadata['field_id'] ];

		// Bug fix
		$this->old = $customID;

		if ( ! $newCustomID )
		{
			$newCustomID = $customID;
		}

		if ( $customID != $newCustomID )
		{
			$out = TRUE;
		}

		$this->update = $newCustomID;

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
					'title'	=> $this->metadata['title'],
					'data'	=> $issue['field_' . $this->metadata['field_id'] ] ? $issue['field_' . $this->metadata['field_id'] ] : '-',
					'type'	=> 'column'
				);
			break;
		}

		// Dropdowns are special -- show the 'human' value instead of key.
		if( $this->metadata['type'] == 'drop' && !empty( $issue['field_' . $this->metadata['field_id'] ] ) )
		{
			$data['data'] = $this->metadata['options'][ $issue['field_' . $this->metadata['field_id'] ] ]['human'];
		}
		elseif( $this->metadata['type'] == 'drop' ) {
			$data['data'] = '-';
		}

		// We're not showing in project view!
		if( $type == 'project' && ! $this->metadata['project_display'] )
		{
			return array();
		}

		// First post OMGZ
		if ( $type == 'issue' && ( $this->metadata['issue_display_type'] == 'post' OR $this->metadata['type'] == 'area' ) )
		{
			$data['type'] = 'firstpost';

			if ( $data['data'] == '-' )
			{
				$data['data'] = '';
			}
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
					'changedFrom'	=> $this->lang->words['bt_changed_from'] . " " . $change['old_value'],
					'entryLang'		=> sprintf( $this->lang->words['bt_changed_to'], $this->metadata['title'] ),
					'value'			=> ($this->metadata['type'] == 'drop') ? $this->metadata['options'][ $change['new_value'] ]['human'] : $change['new_value']
				),
				$change
			);

			if ( ! $change['old_value'] )
			{
				unset( $change['changedFrom'] );
			}

			// Text area? Don't display it inline
			if ( $this->metadata['type'] == 'area' )
			{
				unset( $change['entryLang'] );
				$change['value'] = "changed content of {$this->metadata['title']}";
			}
		}

		return $change;
	}

	public function getQuickReplyDropdown( $issue )
	{
		$out = array();

		$out['title'] = $this->metadata['title'];

		// What are we displaying?
		switch( $this->metadata['type'] )
		{
			case 'drop':
				$out['data']	= $this->makeDropdown( 'field_' . $this->metadata['field_id'], $issue['field_' . $this->metadata['field_id'] ] );
			break;

			case 'text':
				$out['data']	= $issue['field_' . $this->metadata['field_id'] ];
				$out['type']	= 'text';
				$out['key']		= 'field_' . $this->metadata['field_id'];
			break;

			case 'area':
				// Text area does not support quick reply
			break;
		}

		return $out;
	}

	public function getPostScreen( $type )
	{
		$out = array();

		$out['title'] = $this->metadata['title'];

		// What are we displaying?
		switch( $this->metadata['type'] )
		{
			case 'drop':
				if ( $type == 'new' )
				{
					$out['data']	= $this->makeDropdown( 'field_' . $this->metadata['field_id'], IPSText::stripslashes( trim($_POST['field_' . $this->metadata['field_id']]) ) ?  IPSText::stripslashes( trim($_POST['field_' . $this->metadata['field_id']]) ) : '' );
				}
				else
				{
					$out['data']	= $this->makeDropdown( 'field_' . $this->metadata['field_id'], $this->issue['field_' . $this->metadata['field_id'] ] );
				}
			break;

			case 'text':
				if ( $type == 'new' )
				{
					$out['data']	= IPSText::stripslashes( trim($_POST['field_' . $this->metadata['field_id'] ]) );
				}
				else
				{
					$out['data']	= $this->issue['field_' . $this->metadata['field_id'] ];
				}


				$out['type']	= 'text';
				$out['key']		= 'field_' . $this->metadata['field_id'];
			break;

			case 'area':
				if ( $type == 'new' )
				{
					$out['data']	= IPSText::stripslashes( trim($_POST['field_' . $this->metadata['field_id'] ]) );
				}
				else
				{
					$out['data']	= $this->issue['field_' . $this->metadata['field_id'] ];
				}


				$out['type']	= 'area';
				$out['key']		= 'field_' . $this->metadata['field_id'];
			break;
		}

		return $out;
	}

	public function getProjectFilterCookie()
	{
		return array( $this->metadata['field_keyword'] => $this->filter);
	}

	public function getProjectFilterDropdown()
	{
		if( $this->metadata['project_filter'] )
		{
			$out = $this->makeDropdown( $this->metadata['field_keyword'], $this->filter, TRUE, FALSE, 'drop' );
		}
		else {
			$out = '';
		}

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
			$out = "t.field_{$this->metadata['field_id']}='" . IPSText::stripslashes( trim( $this->filter ) ) . "'";
		}

		return $out;
	}

	public function getProjectFilterURLBit()
	{
		$out = array();

		if ( $this->filter != 'all' )
		{
			$out = "{$this->metadata['field_keyword']}={$this->filter}";
		}

		return $out;
	}

	public function initialize( $project, $metadata = array() )
	{
		$this->project = $project;

		IPSLib::doDataHooks( $metadata, 'trackerCustomFieldInitialize' );

		$perms = unserialize($metadata['custom_perms']);
		unset( $metadata['custom_perms'] );

		$this->metadata = $metadata;

		$this->perms    = array();

		// Check permissions
		$this->perms['show']      = $this->perms()->check( 'show',      $perms );
		$this->perms['submit']    = $this->perms()->check( 'submit',    $perms );
		$this->perms['update']    = $this->perms()->check( 'update',    $perms );
		$this->perms['alter'] 	  = $this->perms()->check( 'alter',     $perms );
	}

	protected function perms()
	{
		$out = NULL;

		if ( ! is_object( $this->permsLib ) )
		{
			$this->permsLib = new tracker_custom_field_permissions_admin( $this->registry );
		}

		$out = $this->permsLib;

		return $out;
	}

	/*-------------------------------------------------------------------------*/
	// Make drop down of a custom field
	//
	// USAGE: $select_name   is the name to be given to the select (DROPDOWN ONLY)
	//        $selected      is the ID of the Custom that should be selected
	//        $counts        adds counts to each option
	//        $project_id    is required when using $counts - the ID to grab the counts for
	//        $disable       0 = show all, 1 = disable non allownew, 2 = hide non allownew
	//        $allow_all     shows the 'ALL' option in the DD (cannot be used with $single)
	//        $single        returns only the default or selected when = 1
	//        $return_type   0 = dropdown, 1 = array, 2 = options only
	/*-------------------------------------------------------------------------*/
	protected function makeDropdown( $selectName='module_custom_id', $selected='', $allowAll=FALSE, $single=FALSE, $returnType='drop' )
	{
		$out = NULL;

		// Check this is a dropdown
		if ( $this->metadata['type'] != 'drop' )
		{
			return $out;
		}

		$options = array();

		if ( $allowAll && ! $single )
		{
			$all = sprintf( $this->lang->words['bt_all_custom'], $this->metadata['title'] );

			$sel = ( $selected == 'all' ) ? "selected='selected'" : '';
			$options['all'] = "<option value='all' {$sel}>{$all}</option>";
		}

		$sel = ( !$selected ) ? "selected='selected'" : '';

		$options[0] = "<option value='0' {$sel}>{$this->lang->words['custom_field_dropdown_default']}</option>";

		if( isset( $this->metadata['options'] ) && is_array( $this->metadata['options'] ) ) {
			foreach( $this->metadata['options'] as $id => $data )
			{
				// if ( $data['project_id'] != $this->project['project_id'] )
				// {
				// 	continue;
				// }

				if ( $single && ! ( $selected == $data['key_value'] ) )
				{
					continue;
				}

				if ( $data['permissions'] == 'locked' || ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) )
				{
					continue;
				}

				$data['key_value'] = isset( $data['key_value'] ) ? $data['key_value'] : 0;

				// $dis   = ( $disable == 'disable' && $data['permissions'] == 'locked' && ( $data['permissions'] == 'staff' && ! $this->checkPermission('developer') ) ) ? " disabled='disabled'" : "" ;
				$sel   = ( $selected == $data['key_value'] ) ? " selected='selected'" : "" ;
				// $count = ( $counts ) ? " ({$count_val} {$this->lang->words['bt_global_Custom_dd_reports']})" : "" ;

				$options[$id] = "<option value='{$data['key_value']}'{$sel}>{$data['human']}</option>";
			}
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
		return array( 'field_' . $this->metadata['field_id'] => trim( $this->update ) );
	}

	public function postCommitUpdate( $issue )
	{

	}

	public function projectFilterSetup( $cookie )
	{
		$this->filter = $this->tracker->selectSetVariable(
			array(
				1 => isset($this->request[ $this->metadata['field_keyword'] ]) ? $this->request[ $this->metadata['field_keyword'] ] : NULL,
				2 => isset($cookie[ $this->metadata['field_keyword'] ])        ? $cookie[ $this->metadata['field_keyword'] ]        : NULL,
				4 => 'all'
			)
		);
	}

	public function returnFormName($type)
	{
		$data = array( 'form' => 'field_' . $this->metadata['field_id'], 'db' => 'field_' . $this->metadata['field_id'] );

		return $data[$type];
	}

	public function setFieldUpdate( $issue )
	{
		$out = array();

		if ( $this->update && $this->old != $this->update )
		{
			$this->tracker->fields()->registerFieldChangeRecord( $issue['issue_id'], 'custom', $this->metadata['field_id'], $this->old, $this->update );

			$out = array( 'field_' . $this->metadata['field_id'] => trim( $this->update ) );
		}

		return $out;
	}

	public function setIssue( $issue )
	{
		$this->issue = $issue;

		if ( isset( $this->issue['field_' . $this->metadata['field_id']] ) )
		{
			$this->update	= $this->issue['field_' . $this->metadata['field_id']];
		}
	}

	public function validateFilterInput()
	{
		$out = TRUE;

		if ( $this->filter == 'all' || $this->filter == 0 )
		{
			$out = FALSE;
		}
		// else if ( isset( $this->mCache[ $this->filter ] ) )
		// {
		// 	$this->version = $this->mCache[ $this->filter ];

		// 	$out = FALSE;
		// }

		return $out;
	}
}

class tracker_custom_field_permissions_admin extends classPublicPermissions
{
	/**
	* Check a custom field permission, can't use default IPB
	*
	* @since 2.1.0
	* @access public
	* @return int
	*/
	public function check( $perm, $row, $otherMasks=array() )
	{
		$class 		= new trackerPermMappingCustomFieldCustomProject();
		$mapping	= $class->getMapping();

		if( isset( $mapping[ $perm ] ) )
		{
			$value = $row[ $mapping[ $perm ] ];
		}

		// All permission
		if ( $value == '*' )
		{
			return true;
		}
		else
		{
			if ( strstr( ',' . $this->memberData['member_group_id'] . ',', $value ) )
			{
				return true;
			}
		}

		return false;
	}
}