<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS database field management
 * Last Updated: $Date: 2012-03-20 11:48:47 -0400 (Tue, 20 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10449 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_databases_fields extends ipsCommand
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_database_fields' );
		
		//-----------------------------------------
		// Need 'ID'
		//-----------------------------------------
		
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_fields'], '11CCS46' );
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
			$this->registry->output->showError( $this->lang->words['no_db_id_fields'], '11CCS47' );
		}
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=databases&amp;section=fields&amp;id=' . $this->request['id'];
		$this->form_code_js	= $this->html->form_code_js	= 'module=databases&section=fields&id=' . $this->request['id'];

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
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code, $this->lang->words['dbfields_title'] . ' ' . $this->database['database_name'] );
		
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
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax			= new $classToLoad();
		
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

 		$position	= 1;
 		
 		if( is_array($this->request['field']) AND count($this->request['field']) )
 		{
 			foreach( $this->request['field'] as $this_id )
 			{
 				if( !$this_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_database_fields', array( 'field_position' => $position ), 'field_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->rebuildCache();

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbfieldsreorder'], $this->database['database_name'] ) );

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Delete a field
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _fieldDelete()
	{
		$_id	= intval($this->request['field']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_field_del'], '11CCS48' );
		}
		
		$field	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . $_id ) );
		
		if( !$field['field_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_field_del'], '11CCS49' );
		}
		
		//-----------------------------------------
		// Allow field plugins to perform any cleanup necessary
		//-----------------------------------------

		$this->registry->getClass('ccsFunctions')->getFieldsClass()->preDeleteField( $this->database, $field );

		//-----------------------------------------
		// Delete field
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_fields', 'field_id=' . $_id );
		
		//-----------------------------------------
		// Remove field from table
		//-----------------------------------------
		
		$this->DB->dropField( $this->database['database_database'], 'field_' . $_id );
		
		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		$_update	= array( 'database_field_count' => ($this->database['database_field_count'] - 1) );
		
		if( $this->database['database_field_title'] == 'field_' . $_id )
		{
			$_update['database_field_title']	= 'primary_id_field';
		}
		
		if( $this->database['database_field_sort'] == 'field_' . $_id )
		{
			$_update['database_field_sort']		= 'record_updated';
		}
		
		if( $this->database['database_field_content'] == 'field_' . $_id )
		{
			$_update['database_field_content']	= 'primary_id_field';
		}
		
		$this->DB->update( 'ccs_databases', $_update, 'database_id=' . $this->database['database_id'] );
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->rebuildCache();

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbfielddeleted'], $field['field_name'], $this->database['database_name'] ) );
		
		$this->registry->output->setMessage( $this->lang->words['field_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
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
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['field']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_field_edit'], '11CCS50' );
			}
			
			$field	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . $_id ) );
			
			if( !$field['field_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_field_edit'], '11CCS51' );
			}
		}
		else
		{
			$field	= array();
		}
		
		$_key	= $this->request['field_key'] ? trim( preg_replace( "/[^a-zA-Z0-9\-_]/", '', $this->request['field_key'] ) ) : md5( uniqid( microtime(), true ) );
		
		$_save	= array(
						'field_database_id'			=> $this->database['database_id'],
						'field_name'				=> trim($this->request['field_name']),
						'field_key'					=> $_key,
						'field_description'			=> trim($this->request['field_description']),
						'field_type'				=> trim($this->request['field_type']),
						'field_required'			=> intval($this->request['field_required']),
						'field_user_editable'		=> intval($this->request['field_user_editable']),
						'field_max_length'			=> intval($this->request['field_max_length']),
						'field_extra'				=> IPSText::br2nl( trim($this->request['field_extra']) ),
						'field_html'				=> intval($this->request['field_html']),
						'field_is_numeric'			=> intval($this->request['field_is_numeric']),
						'field_truncate'			=> intval($this->request['field_truncate']),
						'field_default_value'		=> $_POST['field_default_value'],
						'field_display_listing'		=> intval($this->request['field_display_listing']),
						'field_display_display'		=> intval($this->request['field_display_display']),
						'field_format_opts'			=> ( is_array($this->request['field_format_opts']) AND count($this->request['field_format_opts']) ) ? implode( ',', $this->request['field_format_opts'] ) : '',
						'field_topic_format'		=> $_POST['field_topic_format'],
						'field_filter'				=> intval( $this->request['field_filter'] ),
						);

		if( !$_save['field_name'] )
		{
			$this->registry->output->showError( $this->lang->words['field_name_missing'], '11CCS148.2' );
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fields			= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		$types			= $fields->getTypes();
		
		//-----------------------------------------
		// Validator
		//-----------------------------------------
		
		$validators	= $fields->getValidators();
		
		if( $this->request['field_validator'] != 'none' )
		{
			if( isset( $validators[ $this->request['field_validator'] ] ) )
			{
				if( $this->request['field_validator'] == 'custom' )
				{
					$_save['field_validator']	= $this->request['field_validator'] . ';_;' . str_replace( '&#092;', '\\', $_POST['field_validator_custom'] ) . ';_;' . $this->request['field_validator_error'];
				}
				else
				{
					$_save['field_validator']	= $this->request['field_validator'];
				}
			}
		}
		else
		{
			$_save['field_validator']	= '';
		}
		
		//-----------------------------------------
		// Verify field type
		//-----------------------------------------
		
		$_isOk	= false;
		
		foreach( $types as $_type )
		{
			if( $_type[0] == $_save['field_type'] )
			{
				$_isOk	= true;
				$_save	= $fields->preSaveField( $_save );
				break;
			}
		}
		
		if( !$_isOk )
		{
			$this->registry->output->showError( $this->lang->words['field_type_invalid'], '11CCS52' );
		}
		
		if ( $_save['field_required'] and !$_save['field_user_editable'] )
		{
			$this->registry->output->showError( 'err_field_cannot_be_required', '11CCS55' );
		}
		
		if( $type == 'add' )
		{
			//-----------------------------------------
			// Check key
			//-----------------------------------------

			if( !$fields->checkFieldKey( $_save['field_database_id'], $_save['field_key'] ) )
			{
				$_save['field_key']	= md5( uniqid( microtime(), true ) );
			}
			
			//-----------------------------------------
			// Set position
			//-----------------------------------------
			
			$max	=  $this->DB->buildAndFetch( array( 'select' => 'MAX(field_position) as position', 'from' => 'ccs_database_fields', 'where' => "field_database_id={$_save['field_database_id']}" ) );
			
			$_save['field_position']	= $max['position'] + 1;
			
			$this->DB->insert( 'ccs_database_fields', $_save );
			
			$id	= $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add field to db
			//-----------------------------------------
			
			$this->DB->addField( $this->database['database_database'], 'field_' . $id, ( $_save['field_type'] == 'editor' OR $_save['field_type'] == 'textarea' ) ? ' MEDIUMTEXT' : 'TEXT' );

			//-----------------------------------------
			// Set the default value for all existing records
			//-----------------------------------------

			$this->DB->update( $this->database['database_database'], array( 'field_' . $id => $_save['field_default_value'] ) );
			
			//-----------------------------------------
			// Update database
			//-----------------------------------------
			
			$this->DB->update( 'ccs_databases', array( 'database_field_count' => ($this->database['database_field_count'] + 1) ), 'database_id=' . $this->database['database_id'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbfieldadded'], $_save['field_name'], $this->database['database_name'] ) );

			$this->registry->output->setMessage( $this->lang->words['field_add_success'] );
		}
		else
		{
			if( $_save['field_key'] != $field['field_key'] )
			{
				if( !$fields->checkFieldKey( $_save['field_database_id'], $_save['field_key'] ) )
				{
					$_save['field_key']	= md5( uniqid( microtime(), true ) );
				}
			}
			
			$this->DB->update( 'ccs_database_fields', $_save, 'field_id=' . $field['field_id'] );
			
			$id	= $field['field_id'];
			
			if( $_save['field_type'] != $field['field_type'] )
			{
				$type		= 'TEXT';
				$oldtype	= 'TEXT';
				$_medium	= array( 'editor', 'textarea' );
				
				if( in_array( $_save['field_type'], $_medium ) )
				{
					$type		= 'MEDIUMTEXT';
				}
				
				if( in_array( $field['field_type'], $_medium ) )
				{
					$oldtype	= 'MEDIUMTEXT';
				}
				
				if( $type != $oldtype )
				{
					$this->DB->changeField( $this->database['database_database'], 'field_' . $field['field_id'], 'field_' . $field['field_id'], $type );
				}
			}

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbfieldedited'], $_save['field_name'], $this->database['database_name'] ) );

			$this->registry->output->setMessage( $this->lang->words['field_edit_success'] );
		}
		
		if( $this->request['title_field'] AND $this->database['database_field_title'] != 'field_' . $id )
		{
			$this->DB->update( 'ccs_databases', array( 'database_field_title' => 'field_' . $id ), 'database_id=' . $this->database['database_id'] );

			if( in_array( $_save['field_type'], array( 'input', 'textarea', 'editor' ) ) )
			{
				$this->DB->addFulltextIndex( $this->database['database_database'], 'field_' . $id );
			}

			if( $this->DB->checkForIndex( $this->database['database_field_title'], $this->database['database_database'] ) )
			{
				/* Trick driver :x */
				$this->DB->dropField( $this->database['database_database'], 'INDEX ' . $this->database['database_field_title'] );
			}
		}

		if( $this->request['content_field'] AND $this->database['database_field_content'] != 'field_' . $id )
		{
			$this->DB->update( 'ccs_databases', array( 'database_field_content' => 'field_' . $id ), 'database_id=' . $this->database['database_id'] );

			if( in_array( $_save['field_type'], array( 'input', 'textarea', 'editor' ) ) )
			{
				$this->DB->addFulltextIndex( $this->database['database_database'], 'field_' . $id );
			}

			if( $this->DB->checkForIndex( $this->database['database_field_content'], $this->database['database_database'] ) )
			{
				/* Trick driver :x */
				$this->DB->dropField( $this->database['database_database'], 'INDEX ' . $this->database['database_field_content'] );
			}
		}
		
		if( $this->request['title_field'] OR $this->request['content_field'] )
		{
			$this->cache->rebuildCache( 'ccs_databases', 'ccs' );
		}
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->rebuildCache();
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
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
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['field']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_field_edit'], '11CCS53' );
			}
			
			$field	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . $_id ) );
			
			if( !$field['field_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_field_edit'], '11CCS54' );
			}

			if( $this->database['database_field_title'] == 'field_' . $field['field_id'] )
			{
				$field['_isTitle']	= true;
			}

			if( $this->database['database_field_content'] == 'field_' . $field['field_id'] )
			{
				$field['_isContent']	= true;
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['editing_field_title'] . ' ' . $field['field_name'] );
		}
		else
		{
			$field	= array( 'field_display_listing' => 1, 'field_display_display' => 1, 'field_user_editable' => 1 );
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['adding_field_title'] );
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fields			= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		$types			= $fields->getTypes();
		
		//-----------------------------------------
		// And validators
		//-----------------------------------------
		
		$validators	= $fields->getValidators();

		//-----------------------------------------
		// Only allow one attachments field per record
		//-----------------------------------------
		
		$_attach	= $this->DB->buildAndFetch( array( 'select' => 'field_id', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=' . $this->database['database_id'] . " AND field_type='attachments'" ) );

		if( $_attach['field_id'] AND $_attach['field_id'] != $field['field_id'] )
		{
			$_newTypes	= array();
			
			foreach( $types as $_type )
			{
				if( $_type[0] == 'attachments' )
				{
					continue;
				}
				
				$_newTypes[]	= $_type;
			}
			
			$types	= $_newTypes;
		}
		
		$field['field_topic_format']	= IPSText::textToForm( $field['field_topic_format'] );
		$field['field_default_value']	= IPSText::textToForm( $field['field_default_value'] );

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->fieldForm( $type, $this->database, $field, $types, $validators );
	}

	/**
	 * List all of the created databases
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listFields()
	{
		//-----------------------------------------
		// Get all fields
		//-----------------------------------------
		
		$fields	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=' . $this->database['database_id'], 'order' => 'field_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$fields[]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->fields( $this->database, $fields );
	}
	
	/**
	 * Rebuild cache of fields
	 * Don't automatically use shortcuts, as they won't be setup if makeRegistryShortcuts() wasn't called
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		$fields	= array();
		
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'order' => 'field_position ASC' ) );
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$fields[ $r['field_database_id'] ][ $r['field_id'] ]	= $r;
		}
		
		ipsRegistry::cache()->setCache( 'ccs_fields', $fields, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
}
