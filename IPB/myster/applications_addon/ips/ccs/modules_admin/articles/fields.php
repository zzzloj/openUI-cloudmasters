<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article field management
 * Last Updated: $Date: 2011-11-22 21:35:28 -0500 (Tue, 22 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 9864 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_fields extends ipsCommand
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
	 * Main fields handler
	 *
	 * @access	protected
	 * @var		object			Fields action file
	 */
	protected $fieldHandler;

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
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=fields';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=fields';

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
			$this->registry->output->showError( $this->lang->words['no_db_id_fields'], '11CCS9' );
		}

		//-----------------------------------------
		// Get main category handler
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/modules_admin/databases/fields.php', 'admin_ccs_databases_fields', 'ccs' );
		$this->fieldHandler	= new $classToLoad( $this->registry );
		$this->fieldHandler->makeRegistryShortcuts( $this->registry );
		
		$this->fieldHandler->form_code		= $this->form_code;
		$this->fieldHandler->form_code_js	= $this->form_code_js;
		$this->fieldHandler->html			= $this->html;
		$this->fieldHandler->database		= $this->database;
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_listFields();
			break;
			
			case 'add':
			case 'edit':
				$this->_fieldForm( $this->request['do'] );
			break;
			
			case 'doAdd':
				$this->_fieldSave( 'add' );
			break;
			
			case 'doEdit':
				$this->_fieldSave( 'edit' );
			break;

			case 'delete':
				$this->_fieldDelete();
			break;
			
			case 'reorder':
				$this->_fieldReorder();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Reorders fields
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _fieldReorder()
	{
		$this->fieldHandler->_fieldReorder();
	}
	
	/**
	 * Delete a field
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _fieldDelete()
	{
		$this->fieldHandler->_fieldDelete();
	}
	
	/**
	 * Save a new field/edited field
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _fieldSave( $type='add' )
	{
		$this->fieldHandler->_fieldSave( $type );
	}
	
	/**
	 * Form to add/edit a field
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _fieldForm( $type='add' )
	{
		$this->fieldHandler->_fieldForm( $type );
	}

	/**
	 * List all of the created databases
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listFields()
	{
		$this->fieldHandler->_listFields();
	}

}
