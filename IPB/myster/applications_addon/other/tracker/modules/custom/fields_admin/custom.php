<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
*
* Status module
* Last Updated: $Date: 2011-01-03 15:02:26 +0000 (Mon, 03 Jan 2011) $
*
* @author		$Author: alex $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminModules
* @link			http://ipbtracker.com
* @version		$Revision: 1191 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Type: Admin
 * Module: Status
 * Field: Status
 * ACP settings page for status field
 *
 * @package Tracker
 * @subpackage Module-Status
 * @since 2.0.0
 */
class admin_tracker_module_custom_field_custom extends iptCommand
{
	public $html;

	protected $ad_skin;
	protected $perms;

	/*-------------------------------------------------------------------------*/
	// Run me - called by the wrapper
	/*-------------------------------------------------------------------------*/

	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->tracker->modules()->loadTemplate( 'cp_skin_custom', 'custom' );

		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_module_custom' ) );

		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=modules&amp;section=fields&amp;component=custom&amp;file=custom';
		$this->html->form_code_js = $this->form_code_js = 'module=modules&section=fields&component=custom&file=custom';

		//--------------------------------------------
		// Sup?
		//--------------------------------------------

		switch($this->request['do'])
		{
			case 'add':
				$this->showForm('add');
				break;

			case 'edit':
				$this->showForm('edit');
				break;

			case 'do_edit':
				$this->save('edit');
				break;

			case 'do_add':
				$this->save();
				break;

			case 'delete':
				$this->delete( intval( $this->request['field_id'] ) );
				break;

			case 'reorder':
				$this->reorder();
				break;

			case 'recache':
				$this->tracker->cache( 'custom', 'custom' )->rebuild();
				$this->registry->output->global_message = 'Fields recached';
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&' . $this->form_code );
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_fields_manage' );
				$this->customOverview();
				break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	function showForm( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------

		$field_id = intval($this->request['field_id']);
		$field    = array();

		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------

		if ( $type == 'add' )
		{
			$field = array(
				'title'              => "New Field",
				'title_plural'       => "New Fields",
				'field_global_options'     => 1,
				'field_project_display'    => 0,
				'field_not_null'           => 0,
				'field_multimod_update'    => 0,
				'field_project_filter'     => 0,
			);

			$field['field_perms'] = array(
				'view'   => "",
				'submit' => "",
				'modify' => ""
			);

			$disable_keyword = FALSE;

			$title    = "Create New Custom Field";
			$button   = "Create New Custom Field";

			$field['custom_perms'] = $this->perms()->adminPermMatrix( 'customFieldCustomProject', unserialize( $field['custom_perms'] ) );
		}
		else
		{
			$field = $this->DB->buildAndFetch(
				array(
					'select' => 'c.*',
					'from' 	 => array( 'tracker_module_custom' => 'c' ),
					'add_join'   => array(
										array(
											'select' => 'GROUP_CONCAT( CONCAT( o.key_value, "=", o.human ) ORDER BY o.position ASC ) as options',
											'from'   => array( 'tracker_module_custom_option' => 'o' ),
											'where'  => 'o.field_id=c.field_id',
											'type'   => 'left'
										)
									),
					'where'  => "c.field_id={$field_id}",
					'group'  => 'o.field_id'
				)
			);

			$disable_keyword = TRUE;

			if ( ! $field['field_id'] )
			{
				$this->registry->output->global_message = "No ID was passed, please try again.";
				$this->customOverview();
				return;
			}

			//-----------------------------------------
			// Format...
			//-----------------------------------------

			$field['options'] = str_replace( ',', "\n", $field['options'] );

			$field['field_projects'] = unserialize( $field['field_projects'] );

			$field['custom_perms'] = $this->perms()->adminPermMatrix( 'customFieldCustomProject', unserialize( $field['custom_perms'] ) );

			$formcode = 'edit_save';
			$title    = "Edit Custom Field ".$field['field_title'];
			$button   = "Save Changes";
		}

		if ( ! is_array( $field['field_projects'] ) )
		{
			$field['field_projects'] = array();
		}

		$this->registry->output->html .= $this->html->showForm( $field, $permissions, $type );
	}

	protected function save( $type='add' )
	{
		$field_id              = intval($this->request['field_id']);
		$field_title           = IPSText::stripslashes( trim($this->request['title']) );
		$field_title_plural    = IPSText::stripslashes( trim($this->request['title_plural']) );
		$field_desc            = IPSText::stripslashes( trim($this->request['description']) );
		$field_projects        = $this->request['projects'];
		$field_type            = $this->request['type'];
		$field_max_input       = intval($this->request['max_input']);
		$field_global_options  = intval($this->request['global_options']);
		$field_project_display = intval($this->request['project_display']);
		$field_not_null        = intval($this->request['not_null']);
		$field_input_format    = IPSText::stripslashes( trim($this->request['input_format']) );
		$field_content         = $this->request['content'];
		$field_multimod_update = intval($this->request['multimod_update']);
		$field_project_filter  = intval($this->request['project_filter']);
		$field_issue_type	   = IPSText::stripslashes( trim($this->request['issue_display_type']) );
		$field_options         = IPSText::stripslashes( trim($this->request['options']) );

		// No hack attempts please!
		if ( $field_issue_type != 'info' && $field_issue_type != 'post' )
		{
			$field_issue_type = 'info';
		}

		// Drop in projects
		$saveData = array();

		// Security Checks
		foreach( explode( ',', $this->request['projects'] ) as $id )
		{
			if ( intval($id) == $id )
			{
				$saveData[]	= $id;
			}
		}

		$field_projects		= implode( ',', $saveData );

		if ( $type == 'edit' )
		{
			$log_type = "Edited";
		}
		else
		{
			$log_type = "Added";
		}

		//--------------------------------------------
		// Checks...
		//--------------------------------------------

		if ( $type == 'edit' )
		{
			if ( $field_id < 1 )
			{
				$this->registry->output->global_message = "No ID was passed, please try again";
				$this->field_overview();
				return;
			}
		}

		if ( ! $field_title )
		{
			$this->registry->output->global_message = "You must provide a 'Field Title'.";
			$this->showForm( $type );
			return;
		}

		if ( ! is_array($field_projects) && ! count($field_projects) )
		{
			$this->registry->output->global_message = "You must provide select 'Projects' for this field to be active in.";
			$this->showForm( $type );
			return;
		}

		//--------------------------------------------
		// Save...
		//--------------------------------------------
		$custom_perms = $this->perms()->savePermMatrix( $this->request['perms'], $field_projects, $field_id );

		$array = array(
			'title'					=> $field_title,
			'title_plural'			=> $field_title_plural,
			'description'			=> $field_desc,
			'type'					=> $field_type,
			'max_input'				=> $field_max_input,
			'project_display'		=> $field_project_display,
			'not_null'				=> $field_not_null,
			'input_format'			=> $field_input_format,
			'multimod_update'		=> $field_multimod_update,
			'project_filter'		=> $field_project_filter,
			'projects'				=> $field_projects,
			'issue_display_type'	=> $field_issue_type,
			'custom_perms'			=> serialize( $custom_perms )
		);

		if ( $type == 'add' )
		{
			$row = $this->DB->buildAndFetch(
				array (
					'select' => 'max(position) as field_order',
					'from' => 'tracker_module_custom'
				)
			);

			$array['position']  = $row['field_order'] + 1;

			// Insert our custom field
			$this->DB->insert( 'tracker_module_custom', $array );
			$field_id = $this->DB->getInsertId();

			// Add columns to issues, so we can insert data per issue!
			$this->DB->addField( 'tracker_issues', "field_{$field_id}", 'text' );

			// Optimise the table now we've made changes.
			$this->DB->optimize( 'tracker_issues' );

			$this->registry->output->global_message = 'Custom Field Created';
		}
		else
		{
			$this->DB->update( 'tracker_module_custom', $array, 'field_id='.$field_id );
			$this->DB->delete( 'tracker_module_custom_option', 'field_id='.$field_id );

			$this->registry->output->global_message = 'Custom Field Edited';
		}

		//-----------------------------------------
		// Handle dropdown options
		//-----------------------------------------

		/**
		 * Take the input field from a textarea with k=v lines
		 * to an array of keys and values.
		 */
		if( !empty( $field_options ) ) {
			$tmp		= explode( "\n", str_replace( '<br />', "\n", $field_options ) );
			$options	= array();
			$i 			= 0;

			foreach( $tmp as $v ) {
				$v 		= explode( "=", $v, 2 );

				$insert = array(
					'field_id'	=> $field_id,
					'key_value'	=> $v[0],
					'human'		=> $v[1],
					'position'	=> $i++
				);

				$this->DB->insert( 'tracker_module_custom_option', $insert );
			}
		}

		//-----------------------------------------
		// Update statistics & project cache
		//-----------------------------------------

		$this->registry->tracker->cache('custom', 'custom')->rebuild();
		$this->registry->tracker->projects()->rebuild();

		$this->registry->getClass('adminFunctions')->saveAdminLog( "Tracker: {$log_text} Custom Field #" . $field_id );

		// Redirect
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&' . $this->form_code );
	}

	protected function perms()
	{
		$out = NULL;

		if ( ! is_object( $this->perms ) )
		{
			$this->perms = new tracker_custom_field_permissions( $this->registry );
		}

		$out = $this->perms;

		return $out;
	}

	/*-------------------------------------------------------------------------*/
	// Categories Overview
	/*-------------------------------------------------------------------------*/

	function customOverview()
	{
		// Valid types
		$types = array(
			'text'	=> 'Text Input',
			'drop'	=> 'Drop Down',
			'area'	=> 'Text Area'

		);

		//-----------------------------------------
		// Get categories
		//-----------------------------------------

		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_custom',
				'order'  => 'position ASC'
			)
		);
		$this->DB->execute();

		$count_order	= $this->DB->getTotalRows();
		$rows			= array();

		while( $r = $this->DB->fetch() )
		{
			$r['_type']		= $types[ $r['type'] ];
			$r['_rules']	= array();

			// Info block or post?
			if ( $r['issue_display_type'] == 'post' )
			{
				$r['_rules'][] = '<li>Shows in first post</li>';
			}

			// Drop in max input
			if ( $r['max_input'] )
			{
				$r['_rules'][] = '<li>Max Input: ' . $r['max_input'] . ' characters</li>';
			}

			// Drop in project display
			if ( $r['project_display'] )
			{
				$r['_rules'][] = '<li>Displays in Project View</li>';
			}

			// Drop in required
			if ( $r['not_null'] )
			{
				$r['_rules'][] = '<li>Field is required</li>';
			}

			// Drop in filters
			if ( $r['project_filter'] )
			{
				$r['_rules'][] = '<li>Displays in Project Filters</li>';
			}

			// Drop in input format
			if ( $r['input_format'] )
			{
				$r['_rules'][] = '<li>Input must match: <strong>' . $r['input_format'] . '</strong></li>';
			}

			$r['_rules']	= implode( '', $r['_rules'] );

			// No rules
			if ( ! $r['_rules'] )
			{
				$r['_rules'] = "<li><em>No rules specified</em></li>";
			}

			$rows[] = $r;
		}

		//-----------------------------------------
		// End the table and print
		//-----------------------------------------

		$this->registry->output->html .= $this->html->customOverview( $rows );
	}

	protected function delete( $id )
	{
		// Delete from fields
		$this->DB->delete( 'tracker_module_custom', "field_id=" . intval( $id ) );
		$this->DB->delete( 'tracker_module_custom_option', "field_id=" . intval( $id ) );

		// Delete from permission index
		$this->DB->delete( 'permission_index', "app='tracker' AND perm_type='custom_" . intval( $id ) . "'" );

		// Drop from issues
		$this->DB->dropField( 'tracker_issues', 'field_' . intval( $id ) );

		// Recache
		$this->registry->tracker->cache('custom', 'custom')->rebuild();
		$this->registry->tracker->projects()->rebuild();

		// Redirect
		$this->registry->output->global_message = 'Field deleted!';
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=tracker&' . $this->form_code );
	}

	/**
	 * AJAX Action: Reorder
	 */
	protected function reorder()
	{
		/* Get ajax class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax = new $classToLoad();

		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}

		//-----------------------------------------
		// Save new position
		//-----------------------------------------
		$position = 1;

		if( is_array($this->request['field']) AND count($this->request['field']) )
		{
			foreach( $this->request['field'] as $id )
			{
				$this->DB->update( 'tracker_module_custom', array( 'position' => $position ), 'field_id=' . $id );

				$position++;
			}
		}

		$this->tracker->cache( 'custom', 'custom' )->rebuild();

		$ajax->returnString( 'OK' );
		exit;
	}

	/**
	* Formats the text for saving
	*
	* @param 	text	Custom field input
	* @return 	text	Converted text
	*/
	public function formatTextToSave( $t )
	{
		$t = str_replace( '<br>'  , "\n", $t );
		$t = str_replace( '<br />', "\n", $t );
		$t = str_replace( '&#39;' , "'" , $t );

		if ( @get_magic_quotes_gpc() )
		{
			$t = stripslashes( $t );
		}

		return $t;
	}
}

class tracker_custom_field_permissions extends classPublicPermissions
{
	/**
	 * Builds a permission selection matrix
	 *
	 * @param	string		$type			The permission type to build
	 * @param	array		$default		Current permissions
	 * @param	string		$app			App that the type belongs too, default is the current app
	 * @param	string		$only_perm		Only show this permission
	 * @param	boolean		$addOutsideBox	Add or not the outside acp-box
	 * @return	@e string	HTML
	 */
	public function adminPermMatrix( $type, $default, $app='', $only_perm='', $addOutsideBox=true )
	{
		/* INI */
		$perm_names   = array();
		$perm_matrix  = array();
		$perm_checked = array();
		$perm_map     = array();
		$html         = ipsRegistry::getClass( 'output' )->loadTemplate( 'cp_skin_permissions', 'members' );

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_permissions' ), 'members' );

		/* Get Mappings */
		$mapping     = new trackerPermMappingCustomFieldCustomProject();
		$perm_names  = $mapping->getPermNames();
		$perm_map    = $mapping->getMapping();

		/* Language... */
		$this->lang->loadLanguageFile( array( 'admin_' . $app ), $app );

		foreach( $perm_names as $key => $perm )
		{
			$perm_names[ $key ]	= ipsRegistry::getClass('class_localization')->words['perm_tracker_' . $key ] ? ipsRegistry::getClass('class_localization')->words['perm_tracker_' . $key ] : $perm;
		}

		/* Single Perm? */
		if( $only_perm )
		{
			$new_perm_array = array();
			$new_perm_array[$only_perm] = $perm_names[$only_perm];
			$perm_names = $new_perm_array;
		}

		/* Loop through the masks */
		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => "perm_name ASC" ) );
		$this->registry->DB()->execute();

		while( $data = $this->registry->DB()->fetch() )
		{
			/* Reset row */
			$matrix_row = array();

			/* Loop through the permissions */
			foreach( $perm_names as $key => $perm )
			{
				/* Restrict? */
				if( $only_perm && $key != $only_perm )
				{
					continue;
				}

				/* Checked? */
				$checked = '';
				if( $default[ $perm_map[ $key ] ] == '*' )
				{
					$checked = " checked='checked'";

					/* Add the global flag */
					$perm_checked['*'][$key] = $checked;
				}
				else if( in_array( $data['perm_id'], explode( ',', IPSText::cleanPermString( $default[ $perm_map[ $key ] ] ) ) ) )
				{
					$checked = " checked='checked'";
				}

				$perm_checked[ $data['perm_id'] ][ $key ] = $checked;
				$matrix_row[$key] = $perm;
			}

			$data['perm_name'] = str_replace( '%', '&#37;', $data['perm_name'] );

			/* Add row to matrix */
			$perm_matrix[$data['perm_id'].'%'.$data['perm_name']] = $matrix_row;
		}

		/* Return the matrix */
		return $html->permissionMatrix( $perm_names, $perm_matrix, $perm_checked, $mapping->getPermColors(), $type, $addOutsideBox );
	}

	/**
	 * Saves a permission matrix to the database
	 *
	 * @param array $perm_matrix The array of data to be saved
	 * @param int $type_id ID of the type for this permission row. EX: forum_id for a forum
	 * @param string $type The field permission type to build
	 * @param string $app Module that the type belongs too
	 * @return bool success/failure
	 * @access public
	 * @since 2.0.0
	 */
	/**
	 * Saves a permission matrix to the database
	 *
	 * @param	string	Type of permission being saved. Ex: forum
	 * @param	int		ID of the type for this permission row. EX: forum_id for a forum
	 * @param	string	The permission type to build
	 * @param	string	App that the type belongs too, default is the current app
	 * @return	bool
	 */
	public function savePermMatrix( $perm_matrix, $type_id, $type, $app='' )
	{
		/* INI */
		$mapping_array = array();
		$perm_save_row = array();

		/* Get Mappings */
		$mapping     = new trackerPermMappingCustomFieldCustomProject();
		$mapping_array = $mapping->getMapping();
		$customTable   = ( method_exists( $mapping, 'getCustomTable' ) ) ? $mapping->getCustomTable() : false;

		/* Loop through mapping and build save array */
		foreach( $mapping_array as $k => $col )
		{
			/* Setup the column */
			$perm_save_row[$col] = '';

			/* Check the matrix for this perm */
			if( $perm_matrix[ 'customFieldCustomProject' . $k] )
			{
				/* Global? */
				if( !empty( $perm_matrix[ 'customFieldCustomProject' . $k]['*'] ) )
				{
					$perm_save_row[$col] = '*';
				}
				else
				{
					/* Do we have permissions? */
					if( is_array( $perm_matrix[ 'customFieldCustomProject' . $k] ) && count( $perm_matrix[ 'customFieldCustomProject' . $k] ) )
					{
						/* Loop through the permissions */
						$perm_save_row[$col] = ',';

						foreach( $perm_matrix[ 'customFieldCustomProject' . $k] as $mask => $v )
						{
							if( $v == 1 )
							{
								$perm_save_row[$col] .= "{$mask},";
							}
						}
					}
				}
			}
		}

		// Return data
		$return = array();

		if ( ! $customTable )
		{
			/* Ensure all text fields have a default value */
			for ( $i = 2 ; $i < 8 ; $i++ )
			{
				if ( ! isset( $perm_save_row['perm_' . $i ] ) )
				{
					$perm_save_row['perm_' . $i ] = '';
				}
			}

			/* build the rest of the save array */
			$perm_save_row['app']          = 'tracker';
			$perm_save_row['perm_type']    = 'custom_' . $type;

			/* Loop through projects */
			foreach( explode(',', $type_id ) as $k => $v )
			{
				$v = intval($v);

				$perm_save_row['perm_type_id'] = $v;

				/* Save */
				$this->registry->DB()->delete( 'permission_index', "app='tracker' AND perm_type='custom_{$type}' AND perm_type_id={$v}" );
				$this->registry->DB()->insert( 'permission_index', $perm_save_row );

				$return = $perm_save_row;
				$return['perm_type_id'] = $type_id;
			}
		}

		return $return;
	}
}

?>