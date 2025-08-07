<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_tracker_moderators_templates extends ipsCommand
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

		$this->form_code    = $this->html->form_code    = 'module=moderators&amp;section=templates';
		$this->form_code_js = $this->html->form_code_js = 'module=moderators&section=templates';
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

		//-----------------------------------------
		// Check to make sure that the relevant groups exist.
		//-----------------------------------------

		$original = $this->registry->tracker->moderators()->getTemplate( $this->request['id'] );

		if( ! is_array( $original ) && ! count( $original ) > 0 )
		{
			$this->registry->output->showError( $this->lang->words['m_whichmod'], 1125 );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		$this->DB->delete( 'tracker_moderators', "moderate_id=" . $this->request['id'] );

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
		$rows    = array();

		//-----------------------------------------
		// Get moderators
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'    => "m.moderate_id,m.name",
				'from'      => array( 'tracker_moderators' => 'm' ),
				'where'     => "m.type = 'template'",
				'add_join'  => array(
					0 => array(
						'select' => "count( mc.moderate_id ) AS num_uses",
						'from'   => array( 'tracker_moderators' => 'mc' ),
						'where'  => "m.moderate_id=mc.template_id AND mc.mode='template'",
						'type'   => 'left'
					)
				),
				'order'     => "m.name",
				'group'     => 'm.moderate_id,m.name'
			)
		);
		$this->DB->execute();

		while ( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Add
			//-----------------------------------------

			$content .= $this->html->templatesOverviewRow( $row );
		}

		/* No rows */
		if ( ! $content )
		{
			$content .= $this->html->noRowsInDatabase( 'permission masks' );
		}

		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->templatesOverviewWrapper( $content );
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

		if ($type == 'edit')
		{
			if ($this->request['id'] == "")
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 11210 );
			}

			$moderator = $this->DB->buildAndFetch(
				array(
					'select'    => "*",
					'from'      => 'tracker_moderators',
					'where'    => "type = 'template' AND moderate_id = " . intval($this->request['id']),
				)
			);

			if ( ! ( is_array( $moderator ) && count( $moderator ) > 0 && $moderator['moderate_id'] > 0 ) )
			{
				$this->registry->output->showError( $this->lang->words['m_whichmod'], 11210 );
			}
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

		$this->html->templateCheck = TRUE;

		$content = $this->html->moderatorForm( $type, $moderator, $blocks, $g_array );

		$this->registry->output->html .= $this->html->templateFormWrapper( $type, $moderator, $content, $blocks );
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
		$moderate_id  = intval($this->request['id']);

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
			'name'                   => trim($this->request['name']),
			'mode'					 => '',
			'mg_id'					 => 0
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
					'select' => 'moderate_id',
					'from'   => 'tracker_moderators',
					'where'  => "moderate_id={$moderate_id} AND type='template'"
				)
			);

			if ( is_array( $test ) && isset( $test['moderate_id'] ) && $test['moderate_id'] == $moderate_id )
			{
			
				$this->DB->update( 'tracker_moderators', $db_string, 'moderate_id=' . $moderate_id );

				ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_editedlog'] );

				$this->registry->output->global_message = $this->lang->words['g_edited'];
			}
			else
			{
				$this->registry->output->showError("We could not find the moderator you were editing");
			}
		}
		// Or add the new template
		else
		{
			$db_string['type'] = "template";

			$this->DB->insert( 'tracker_moderators', $db_string );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_addedlog'] );

			$this->registry->output->global_message = $this->lang->words['g_added'];
		}

		$this->registry->tracker->moderators()->rebuild();

		$this->mainScreen();
	}
}

?>