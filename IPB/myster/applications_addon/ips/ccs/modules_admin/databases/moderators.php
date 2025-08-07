<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS database moderator management
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		24th Sept 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_databases_moderators extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	public
	 * @var		string			URL shortcut
	 */
	public $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	public
	 * @var		string			JS URL shortcut
	 */
	public $form_code_js;
	
	/**
	 * Skin object
	 *
	 * @access	public
	 * @var		object			Skin templates
	 */	
	public $html;

	/**
	 * Database info
	 *
	 * @access	public
	 * @var		array 			DB info
	 */	
	public $database			= array();

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_database_moderators' );
		
		//-----------------------------------------
		// Need 'ID'
		//-----------------------------------------
		
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_mods'], '11CCS55' );
		}
		
		$this->database	= $this->DB->buildAndFetch( array(
															'select'	=> 'd.*',
															'from'		=> array( 'ccs_databases' => 'd' ),
															'where'		=> 'd.database_id=' . $_id,
															'add_join'	=> array(
																				array(
																					'select'	=> 'i.*',
																					'from'		=> array( 'permission_index' => 'i' ),
																					'where'		=> "i.app='ccs' AND i.perm_type='databases' AND i.perm_type_id=d.database_id",
																					'type'		=> 'left',
																					),
																				),
													)		);
		
		if( !$this->database['database_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_mods'], '11CCS56' );
		}
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=databases&amp;section=moderators&amp;id=' . $this->request['id'];
		$this->form_code_js	= $this->html->form_code_js	= 'module=databases&section=moderators&id=' . $this->request['id'];

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $_id );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code, $this->lang->words['dbmods_title'] . ' ' . $this->database['database_name'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_listModerators();
			break;
			
			case 'add':
			case 'edit':
				$this->_moderatorForm( $this->request['do'] );
			break;
			
			case 'doAdd':
				$this->_moderatorSave( 'add' );
			break;
			
			case 'doEdit':
				$this->_moderatorSave( 'edit' );
			break;

			case 'delete':
				$this->_moderatorDelete();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Delete a moderator
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _moderatorDelete()
	{
		$_id	= intval($this->request['moderator']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_mod_del'], '11CCS57' );
		}
		
		$moderator	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_id=' . $_id ) );
		
		if( !$moderator['moderator_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_mod_del'], '11CCS58' );
		}
		
		//-----------------------------------------
		// Delete moderator
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_moderators', 'moderator_id=' . $_id );

		if( $moderator['moderator_type'] == 'group' )
		{
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbmodgdeleted'], $this->caches['group_cache'][ $_id ]['g_title'], $this->database['database_name'] ) );
		}
		else
		{
			$member	= IPSMember::load( $_id, 'basic' );
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbmodmdeleted'], $member['members_display_name'], $this->database['database_name'] ) );
		}

		$this->registry->output->setMessage( $this->lang->words['moderator_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save a new field/edited moderator
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _moderatorSave( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['moderator']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_mod_edit'], '11CCS59' );
			}
			
			$moderator	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_id=' . $_id ) );
			
			if( !$moderator['moderator_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_mod_edit'], '11CCS60' );
			}
		}
		else
		{
			$moderator	= array();
		}
		
		//-----------------------------------------
		// Set group or user id
		//-----------------------------------------
		
		$_typeId	= 0;
		
		if( $this->request['moderator_type'] == 'group' )
		{
			$groupId	= intval($this->request['moderator_group']);
			
			if( !$this->caches['group_cache'][ $groupId ]['g_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_mod_type'], '11CCS61' );
			}
			
			$_typeId	= $groupId;
		}
		else
		{
			$userName	= trim($this->request['moderator_member']);
			
			$member		= IPSMember::load( $userName, 'core', 'displayname' );
			
			$_typeId	= $member['member_id'];
		}

		if( !$_typeId )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_mod_type'], '11CCS62' );
		}
		
		$_save	= array(
						'moderator_database_id'			=> $this->database['database_id'],
						'moderator_type'				=> trim($this->request['moderator_type']),
						'moderator_type_id'				=> $_typeId,
						'moderator_add_record'			=> intval($this->request['moderator_add_record']),
						'moderator_delete_record'		=> intval($this->request['moderator_delete_record']),
						'moderator_edit_record'			=> intval($this->request['moderator_edit_record']),
						'moderator_lock_record'			=> intval($this->request['moderator_lock_record']),
						'moderator_unlock_record'		=> intval($this->request['moderator_unlock_record']),
						'moderator_delete_comment'		=> intval($this->request['moderator_delete_comment']),
						'moderator_approve_record'		=> intval($this->request['moderator_approve_record']),
						'moderator_approve_comment'		=> intval($this->request['moderator_approve_comment']),
						'moderator_pin_record'			=> intval($this->request['moderator_pin_record']),
						'moderator_edit_comment'		=> intval($this->request['moderator_edit_comment']),
						'moderator_restore_revision'	=> intval($this->request['moderator_restore_revision']),
						'moderator_extras'				=> intval($this->request['moderator_extras']),
						);

		if( $type == 'add' )
		{
			//-----------------------------------------
			// Check for existing moderator
			//-----------------------------------------
			
			$_key	= $this->DB->buildAndFetch( array( 'select' => 'moderator_id', 'from' => 'ccs_database_moderators', 'where' => "moderator_type='{$_save['moderator_type']}' AND moderator_type_id={$_save['moderator_type_id']} AND moderator_database_id='{$_save['moderator_database_id']}'" ) );
			
			if( $_key['moderator_id'] )
			{
				$this->registry->output->showError( $this->lang->words['mod_already_present'], '11CCS63' );
			}

			$this->DB->insert( 'ccs_database_moderators', $_save );
			
			$id	= $this->DB->getInsertId();

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbmodadded'], $_save['moderator_type'] . ' ' . $_typeId, $this->database['database_name'] ) );

			$this->registry->output->setMessage( $this->lang->words['moderator_add_success'] );
		}
		else
		{
			if( $_save['moderator_type'] != $moderator['moderator_type'] OR $_save['moderator_type_id'] != $moderator['moderator_type_id'] )
			{
				$_key	= $this->DB->buildAndFetch( array( 'select' => 'moderator_id', 'from' => 'ccs_database_moderators', 'where' => "moderator_type='{$_save['moderator_type']}' AND moderator_type_id={$_save['moderator_type_id']} AND moderator_database_id='{$_save['moderator_database_id']}'" ) );
				
				if( $_key['moderator_id'] )
				{
					$this->registry->output->showError( $this->lang->words['mod_already_present'], '11CCS64' );
				}
			}
			
			$this->DB->update( 'ccs_database_moderators', $_save, 'moderator_id=' . $moderator['moderator_id'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbmodedited'], $_save['moderator_type'] . ' ' . $_typeId, $this->database['database_name'] ) );

			$this->registry->output->setMessage( $this->lang->words['moderator_edit_success'] );
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Form to add/edit a moderator
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _moderatorForm( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------

		if( $type == 'edit' )
		{
			$_id	= intval($this->request['moderator']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_mod_edit'], '11CCS65' );
			}
			
			$moderator	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_id=' . $_id ) );
			
			if( !$moderator['moderator_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_mod_edit'], '11CCS66' );
			}
			
			if( $moderator['moderator_type'] == 'member' AND $moderator['moderator_type_id'] )
			{
				$member	= IPSMember::load( $moderator['moderator_type_id'], 'core' );
				
				$moderator['moderator_member']	= $member['members_display_name'];
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['editing_moderator'] );
		}
		else
		{
			$moderator	= array();
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['adding_moderator'] );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->moderatorForm( $type, $this->database, $moderator );
	}

	/**
	 * List all of the moderators
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listModerators()
	{
		//-----------------------------------------
		// Get all moderators
		//-----------------------------------------
		
		$moderators	= array();
		$members	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id=' . $this->database['database_id'] ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$moderators[]	= $r;
			
			if( $r['moderator_type'] == 'member' )
			{
				$members[ $r['moderator_type_id'] ]	= $r['moderator_type_id'];
			}
		}
		
		if( count($members) )
		{
			$members	= IPSMember::load( $members );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->moderators( $this->database, $moderators, $members );
	}
}
