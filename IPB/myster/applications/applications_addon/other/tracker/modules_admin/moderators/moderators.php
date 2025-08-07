<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_tracker_moderators_moderators extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @var string URL shortcut
	 * @access protected
	 * @since 2.0.0
	 */
	protected $form_code;
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var string JS URL shortcut
	 * @access protected
	 * @since 2.0.0
	 */
	protected $form_code_js;
	/**
	 * Skin object
	 *
	 * @var object Skin templates
	 * @access protected
	 * @since 2.0.0
	 */
	protected $html;
	/**
	 * Execution check to enable form wrapper
	 *
	 * @var bool the check
	 * @access protected
	 * @since 2.0.0
	 */
	protected $moduleCheck = FALSE;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate('cp_skin_moderators');

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code    = $this->html->form_code    = 'module=moderators&amp;section=moderators';
		$this->form_code_js = $this->html->form_code_js = 'module=moderators&section=moderators';
		$this->moduleCheck  = $this->html->moduleCheck  = TRUE;

		//-----------------------------------------
		// Load lang
		//-----------------------------------------

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_moderators' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_moderators_add' );
				$this->moderatorForm('add');
				break;
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_moderators_add' );
				$this->saveModerator('add');
				break;
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_moderators_edit' );
				$this->moderatorForm('edit');
				break;
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_moderators_edit' );
				$this->saveModerator('edit');
				break;
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_moderators_delete' );
				$this->doDelete();
				break;
			case 'overview':
			default:
				$this->mainScreen();
				break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Delete the group
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function doDelete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->request['id'] = intval($this->request['id'] );

		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_whichmod'], 1122 );
		}
		
		if ( $this->request['del'] == 'main' )
		{
			$this->DB->delete( 'tracker_moderators', "mg_id=" . $this->request['id'] . " AND type='" . $this->request['type'] . "'" );
		}
		else
		{
			//-----------------------------------------
			// Check to make sure that the relevant groups exist.
			//-----------------------------------------
	
			$original = $this->registry->tracker->moderators()->getModerator( $this->request['id'] );
	
			if( ! is_array( $original ) && ! count( $original ) > 0 )
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 1125 );
			}
	
			//-----------------------------------------
			// Delete
			//-----------------------------------------
	
			$this->DB->delete( 'tracker_moderators', "moderate_id=" . $this->request['id'] );
		}
		
		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------

		$this->registry->tracker->moderators()->rebuild();

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_removedlog'], $original['name'] ) );

		$this->registry->output->global_message = $this->lang->words['m_removed'];
		$this->mainScreen();
	}

	/**
	 * List the groups
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function mainScreen()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$content = "";
		$form    = array();
		$mods    = array();
		$rows    = array('member','group');

		//-----------------------------------------
		// Get moderators
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'    => "md.moderate_id,md.type,md.mg_id,md.is_super, md.project_id, COUNT(md.project_id) AS num_projects",
				'from'      => array('tracker_moderators' => 'md'),
				'add_join'  => array(
					0 => array(
						'select' => 'm.members_display_name',
						'from'   => array( 'members' => 'm' ),
						'where'  => "md.mg_id=m.member_id AND md.type='member'",
						'type'   => 'left'
					),
					1 => array(
						'select' => 'g.g_title',
						'from'   => array( 'groups' => 'g' ),
						'where'  => "md.mg_id=g.g_id AND md.type='group'",
						'type'   => 'left'
					)
				),
				'where'		=> "md.type IN('group','member')",
				'order'		=> "md.type, g.g_title, m.members_display_name",
				'group'		=> "md.mg_id,md.type,g.g_title,m.members_display_name,md.is_super"
			)
		);
		$outer = $this->DB->execute();

		// Loop through the database rows and load the multi-dimensional array based on type then id
		if ( $this->DB->getTotalRows($outer) )
		{
			while ( $row = $this->DB->fetch($outer) )
			{
				if ( ! isset( $rows[ $row['type'] ][ $row['mg_id'] ] ) || ! is_array( $rows[ $row['type'] ][ $row['mg_id'] ] ) )
				{
					$rows[ $row['type'] ][ $row['mg_id'] ] = array(
						'moderate_id'	=> $row['moderate_id'],
						'type'          => $row['type'],
						'mg_id'         => $row['mg_id'],
						'num_projects'  => 0
					);
				}

				// Drop in 'is_super'
				if ( ! isset( $rows[ $row['type'] ][ $row['mg_id'] ]['is_super'] ) || $row['is_super'] > 0 )
				{
					$rows[ $row['type'] ][ $row['mg_id'] ]['is_super'] = $row['is_super'];
				}

				// Drop in 'num_projects'
				if ( ! isset( $rows[ $row['type'] ][ $row['mg_id'] ]['num_projects'] ) || ( $row['is_super'] == 0 && $row['num_projects'] > 0 ) )
				{
					$rows[ $row['type'] ][ $row['mg_id'] ]['num_projects'] = $row['num_projects'];
				}
				
				// Single proejct
				if ( $row['num_projects'] <= 1 )
				{
					$rows[ $row['type'] ][ $row['mg_id'] ]['project']	= $this->registry->tracker->projects()->getProject( $row['project_id'] );
				}

				// Drop in either the group or member name
				if ( $row['type'] == 'group' )
				{
					$rows[ $row['type'] ][ $row['mg_id'] ]['name'] = $row['g_title'];
				}
				else
				{
					$rows[ $row['type'] ][ $row['mg_id'] ]['name'] = $row['members_display_name'];
				}
			}
		}

		// Loop through the rows to generate the output
		if ( count( $rows ) > 0 )
		{
			foreach( $rows as $type => $ext )
			{
				if ( is_array( $ext ) && count( $ext ) > 0 )
				{
					foreach( $ext as $id => $int )
					{
						$content .= $this->html->moderatorsOverviewRow( $int );
					}
				}
			}
		}

		// No rows
		if ( $content == '' )
		{
			$content .= $this->html->noRowsInDatabase( 'moderators' );
		}

		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->moderatorsOverviewWrapper( $content );
	}

	/**
	 * Show the add/edit group form
	 *
	 * @access	private
	 * @param 	string		'add' or 'edit'
	 * @return	void		[Outputs to screen]
	 */
	protected function moderatorForm( $type='edit' )
	{
		$content = "";
		$g_array = array();
		$out     = NULL;

		//-----------------------------------------
		// Grab group data and start us off
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			if ($this->request['id'] == "")
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 11210 );
			}

			$moderator = $this->DB->buildAndFetch(
				array(
					'select'    => "md.*",
					'from'      => array('tracker_moderators' => 'md'),
					'add_join'  => array(
						0 => array(
							'select' => 'm.members_display_name',
							'from'   => array( 'members' => 'm' ),
							'where'  => "md.mg_id=m.member_id and md.type='member'",
							'type'   => 'left'
						),
						1 => array(
							'select' => 'g.g_title',
							'from'   => array( 'groups' => 'g' ),
							'where'  => "md.mg_id=g.g_id and md.type='group'",
							'type'   => 'left'
						)
					),
					'where'    => "md.type IN('group','member') AND moderate_id = " . intval($this->request['id']),
				)
			);

			if ( ! ( is_array( $moderator ) && count( $moderator ) > 0 && $moderator['moderate_id'] > 0 ) )
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 11210 );
			}
			
			// Load template
			if ( $moderator['template_id'] && $moderator['mode'] == 'template' )
			{
				$template = $this->DB->buildAndFetch(
					array(
						'select'    => "md.*",
						'from'      => array('tracker_moderators' => 'md'),
						'where'    => "md.moderate_id = " . $moderator['template_id']
					)
				);
				
				unset( $template['mode'] );
				unset( $template['type'] );
				unset( $template['template_id'] );
				unset( $template['mg_id'] );
				unset( $template['project_id'] );
				unset( $template['moderate_id'] );
				
				$moderator = array_merge( $moderator, $template );
			}
		}
		else
		{
			//-----------------------------------------
			// Get groups for DD
			//-----------------------------------------

			$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => 'g_title' ) );
			$this->DB->execute();

			while ( $row = $this->DB->fetch() )
			{
				$g_array[] = array( $row['g_id'], $row['g_title'] );
			}

			$group['g_title']	= 'New Group';
		}

		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------

		$blocks  = array();
		$fields  = $this->registry->tracker->fields()->getCache();
		$modules = $this->registry->tracker->modules()->getCache();

		$this->registry->tracker->modules()->loadInterface( 'moderator_form.php' );

		if ( is_array( $modules ) && count( $modules ) > 0 )
		{
			foreach( $modules as $mod_id => $mod_data )
			{
				if ( $this->registry->tracker->modules()->moduleIsInstalled( $mod_data['directory'], FALSE ) && 
					 file_exists( $this->registry->tracker->modules()->getModuleFolder( $mod_data['directory'] ) . '/extensions/admin/moderator_form.php' ) )
				{
					require_once( $this->registry->tracker->modules()->getModuleFolder( $mod_data['directory'] ) . '/extensions/admin/moderator_form.php' );
				}
			}

			if ( is_array( $fields ) && count( $fields ) > 0 )
			{
				foreach( $fields  = $this->registry->tracker->fields()->getCache() as $field_data )
				{
					$_class  = 'tracker_admin_moderator_form__' . $modules[ $field_data['module_id'] ]['directory'] . '_field_' . $field_data['field_keyword'];

					if ( class_exists( $_class ) )
					{
						$_object = new $_class( $this->registry );

						$blocks[ $field_data['field_keyword'] ]['content'] = $_object->getDisplayContent( $moderator );
						$blocks[ $field_data['field_keyword'] ]['data']    = $field_data;
						$blocks[ $field_data['field_keyword'] ]['module']  = $this->registry->tracker->cache('modules')->getModule($field_data['module_id']);
					}
				}
			}
		}

		//-----------------------------------------
		// And output to form
		//-----------------------------------------

		$content = $this->html->moderatorForm( $type, $moderator, $blocks, $g_array );

		if ( $this->moduleCheck )
		{
			$this->registry->output->html .= $this->html->moderatorFormWrapper( $type, $moderator, $content, $blocks );
		}
		else
		{
			$out = $content;
		}

		return $out;
	}

	/**
	 * Save the group [add/edit]
	 *
	 * @access	private
	 * @param 	string		'add' or 'edit'
	 * @return	void		[Outputs to screen]
	 */
	private function saveModerator( $type='edit' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$moderate_id  = intval($this->request['moderate_id']);

		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );

		//-----------------------------------------
		// Check...
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			if ( ! $moderate_id > 0 )
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 1128 );
			}
		}

		//-----------------------------------------
		// Set the db array
		//-----------------------------------------

		$db_string = array(
			'can_edit_posts'         => intval($this->request['can_edit_posts']),
			'can_edit_titles'        => intval($this->request['can_edit_titles']),
			'can_del_posts'          => intval($this->request['can_del_posts']),
			'can_del_issues'         => intval($this->request['can_del_issues']),
			'can_lock'               => intval($this->request['can_lock']),
			'can_unlock'             => intval($this->request['can_unlock']),
			'can_move'               => intval($this->request['can_move']),
			'can_merge'              => intval($this->request['can_merge']),
			'can_massmoveprune'      => intval($this->request['can_massmoveprune']),
			'is_super'               => intval($this->request['is_super']),
			'template_id'            => intval($this->request['template_id']),
			'mode'                   => ( intval($this->request['use_template']) == 1 ) ? 'template' : 'normal'
		);

		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------

		$blocks  = array();
		$fields  = $this->registry->tracker->fields()->getCache();
		$modules = $this->registry->tracker->modules()->getCache();

		$this->registry->tracker->modules()->loadInterface( 'moderator_form.php' );

		// If we have modules then load in the moderator_forms
		if ( is_array( $modules ) && count( $modules ) > 0 )
		{
			foreach( $modules as $mod_id => $mod_data )
			{
				if ( $this->registry->tracker->modules()->moduleIsInstalled( $mod_data['directory'], FALSE ) && 
					 file_exists( $this->registry->tracker->modules()->getModuleFolder( $mod_data['directory'] ) . '/extensions/admin/moderator_form.php' ) )
				{
					require_once( $this->registry->tracker->modules()->getModuleFolder( $mod_data['directory'] ) . '/extensions/admin/moderator_form.php' );
				}
			}

			// Loop through the fields to find their class
			if ( is_array( $fields ) && count( $fields ) > 0 )
			{
				foreach( $fields  = $this->registry->tracker->fields()->getCache() as $field_data )
				{
					$_class  = 'tracker_admin_moderator_form__' . $modules[ $field_data['module_id'] ]['directory'] . '_field_' . $field_data['field_keyword'];

					// Got it? ... get the save data
					if ( class_exists( $_class ) )
					{
						$_object = new $_class( $this->registry );

						$remote = $_object->getForSave();

						$db_string = array_merge( $remote, $db_string );
					}
				}
			}
		}

		// Editing?  Check it out then update the row
		if ( $type == 'edit' )
		{
			$test = $this->DB->buildAndFetch(
				array(
					'select' => 'moderate_id, mode',
					'from'   => 'tracker_moderators',
					'where'  => "moderate_id={$moderate_id}"
				)
			);

			if ( is_array( $test ) && isset( $test['moderate_id'] ) && $test['moderate_id'] == $moderate_id )
			{
				unset( $db_string['is_super'] );

				if ( $test['mode'] != 'template' )
				{
					$this->DB->update( 'tracker_moderators', $db_string, 'moderate_id=' . $moderate_id );
				}
				
				ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_editedlog'] );

				$this->registry->output->global_message = $this->lang->words['g_edited'];
			}
			else
			{
				$this->registry->output->showError("We could not find the moderator you were editing");
			}
		}
		// Or so some other setup for the add
		else
		{
			// Group? Check the group out and setup the DB fields
			if ($this->request['type'] == 'group')
			{
				if ( ! isset( $this->request['group_id'] ) || ! intval( $this->request['group_id'] ) > 0 )
				{
					$this->registry->output->showError("We could not match that group ID");
				}

				$group = $this->DB->buildAndFetch(
					array(
						'select' => 'g_id, g_title',
						'from'   => 'groups',
						'where'  => "g_id=".intval($this->request['group_id'])
					)
				);

				if ( ! is_array( $group ) || ! isset( $group['g_id'] ) || ! $group['g_id'] > 0 )
				{
					$this->registry->output->showError("We could not match that group ID");
				}
				else
				{
					$db_string['type']  = 'group';
					$db_string['mg_id'] = $group['g_id'];
				}

				//$ad_log = "Added Group '{$group['g_title']}' as a Tracker moderator";
			}
			// Or member ... Check out the member and setup the DB fields
			else
			{
				if ( !isset( $this->request['member_id'] ) )
				{
					$this->registry->output->showError("We could not find a member with name: '{$this->request['member_id']}'");
				}

				$member = $this->DB->buildAndFetch(
					array(	
						'select' => 'member_id, members_display_name, members_l_display_name',
						'from'   => 'members',
						'where'  => "members_l_display_name='".strtolower($this->request['member_id'])."'"
					)
				);

				if ( ! is_array( $member ) || ! isset( $member['member_id'] ) ||  ! $member['member_id'] > 0 )
				{
					$this->registry->output->showError("We could not find a member with name: '{$this->request['member_id']}'");
				}
				else
				{
					$db_string['type']  = 'member';
					$db_string['mg_id'] = $member['member_id'];
				}

				//$ad_log = "Added Member '{$member['members_display_name']}' as a Tracker moderator";
			}

			// New supermod?  Are the already a supermod?
			if ( isset( $this->request['is_super'] ) && intval( $this->request['is_super'] ) > 0 )
			{
				$this->DB->build(
					array(
						'select' => '*',
						'from'   => 'tracker_moderators',
						'where'  => "type='{$db_string['type']}' and mg_id={$db_string['mg_id']} AND is_super=1"
					)
				);
				$this->DB->execute();

				if( $this->DB->getTotalRows() > 0 )
				{
					$this->registry->output->showError("This member/group is already an Tracker moderator");
				}

				$this->DB->insert( 'tracker_moderators', $db_string );
			}
			// Picked out a bunch of projects?  Check them and setup updates for ones already existing
			else
			{
				if ( isset( $this->request['projects'] ) && $this->request['projects'] != '' )
				{
					$saveData = array();
					
					// Security Checks
					foreach( explode( ',', $this->request['projects'] ) as $id )
					{
						if ( intval($id) == $id )
						{
							$saveData[]	= $id;
						}
					}
					
					// New array
					$this->request['projects'] = $saveData;
					
					// Final check to see if someone attempted to manipulate the data
					if ( count( $this->request['projects'] ) == 0 )
					{
						$this->registry->output->showError("No projects were selected.");
					}
				
					// Carry on like none of this ever happened!
					$pids    = implode( ",", $this->request['projects'] );
					$updates = array();

					$this->DB->build(
						array(
							'select'     => 'moderate_id,project_id',
							'from'       => 'tracker_moderators',
							'where'      => "project_id IN ({$pids}) AND mg_id={$db_string['mg_id']}"
						)
					);
					$this->DB->execute();

					if ( $this->DB->getTotalRows() > 0 )
					{
						while( $row = $this->DB->fetch() )
						{
							$updates[ $row['project_id'] ] = $row['moderate_id'];
						}
					}

					// Process each project being requested
					foreach( $this->request['projects'] as $id )
					{
						$db_string['project_id'] = $id;

						if ( isset( $updates[ $id ] ) && $updates[$id] > 0 )
						{
							// This is Alex's fancy input that checked for conflicts
							// If there are conflicts and a '1' comes in then we're updating
							// the only entry for the project with these new permissions
							if ( isset( $this->request['alex'] ) && intval( $this->request['alex'] ) > 0 )
							{
								$this->DB->update( 'tracker_moderators', $db_string, "moderate_id=".$updates[ $id ] );
							}
						}
						else
						{
							$this->DB->insert( 'tracker_moderators', $db_string );
						}
					}
				}
				else
				{
					$this->registry->output->showError("No projects were selected.");
				}
			}

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_addedlog'] );

			$this->registry->output->global_message = $this->lang->words['g_added'];
		}

		$this->registry->tracker->moderators()->rebuild();

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&' . $this->form_code );
	}
}

?>