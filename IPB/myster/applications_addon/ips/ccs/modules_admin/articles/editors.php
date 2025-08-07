<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article editors management
 * Last Updated: $Date: 2011-11-28 21:04:21 -0500 (Mon, 28 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 9902 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_editors extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	protected
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	protected
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Skin object
	 *
	 * @access	protected
	 * @var		object			Skin templates
	 */	
	protected $html;
	
	/**
	 * Main moderators handler
	 *
	 * @access	protected
	 * @var		object			Moderator action file
	 */
	protected $editorHandler;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_articles' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=editors';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=editors';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// Get some libs we'll want
		//-----------------------------------------
		
		$this->database	= $this->DB->buildAndFetch( array(
															'select'	=> 'd.*',
															'from'		=> array( 'ccs_databases' => 'd' ),
															'where'		=> 'd.database_is_articles=1',
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
			$this->registry->output->showError( $this->lang->words['no_db_id_mods'], '11CCS8' );
		}

		//-----------------------------------------
		// Get main category handler
		//-----------------------------------------
		
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/modules_admin/databases/moderators.php', 'admin_ccs_databases_moderators', 'ccs' );
		$this->editorHandler	= new $classToLoad( $this->registry );
		$this->editorHandler->makeRegistryShortcuts( $this->registry );
		
		$this->editorHandler->form_code		= $this->form_code;
		$this->editorHandler->form_code_js	= $this->form_code_js;
		$this->editorHandler->html			= $this->html;
		$this->editorHandler->database		= $this->database;

		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
		$this->fixOtherStrings();
		
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
	 * Fix language strings for this db
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function fixOtherStrings()
	{
		$this->lang->words['no_id_for_mod_del']			= $this->lang->words['no_id_for_ed_del'];
		$this->lang->words['moderator_deleted_success']	= $this->lang->words['editor_deleted_success'];
		$this->lang->words['no_id_for_mod_edit']		= $this->lang->words['no_id_for_ed_edit'];
		$this->lang->words['no_id_for_mod_type']		= $this->lang->words['no_id_for_ed_type'];
		$this->lang->words['mod_already_present']		= $this->lang->words['ed_already_present'];
		$this->lang->words['moderator_add_success']		= $this->lang->words['editor_add_success'];
		$this->lang->words['moderator_edit_success']	= $this->lang->words['editor_edit_success'];
		$this->lang->words['editing_moderator']			= $this->lang->words['editing_editor'];
		$this->lang->words['adding_moderator']			= $this->lang->words['adding_editor'];
	}
	
	/**
	 * Delete a moderator
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _moderatorDelete()
	{
		$this->editorHandler->_moderatorDelete();
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
		$this->editorHandler->_moderatorSave( $type );
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
		$this->editorHandler->_moderatorForm( $type );
	}

	/**
	 * List all of the moderators
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listModerators()
	{
		$this->editorHandler->_listModerators();
	}
}
