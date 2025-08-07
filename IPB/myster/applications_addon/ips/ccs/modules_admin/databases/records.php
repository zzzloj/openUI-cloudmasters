<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS database record management
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_databases_records extends ipsCommand
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
	 * Fields array
	 *
	 * @access	public
	 * @var		array 			Fields
	 */	
	public $fields				= array();
	
	/**
	 * Filters for listing
	 *
	 * @access	public
	 * @var		array 			Filters
	 */	
	public $filters				= array();

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_database_records' );
		
		//-----------------------------------------
		// Need 'ID'
		//-----------------------------------------
		
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_records'], '11CCS67' );
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
			$this->registry->output->showError( $this->lang->words['no_db_id_records'], '11CCS68' );
		}
		
		//-----------------------------------------
		// Get all the fields for this DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => "field_database_id=" . $this->database['database_id'], 'order' => 'field_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->fields[ $r['field_id'] ]	= $r;
		}
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=databases&amp;section=records&amp;id=' . $this->request['id'];
		$this->form_code_js	= $this->html->form_code_js	= 'module=databases&section=records&id=' . $this->request['id'];

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang', 'public_lang' ) );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $_id );
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $this->database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $this->database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $this->database['database_id'] ) );
		}

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code, $this->lang->words['dbrecords_title'] . ' ' . $this->database['database_name'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_listRecords();
			break;
			
			case 'add':
			case 'edit':
				$this->_recordForm( $this->request['do'] );
			break;
			
			case 'doAdd':
				$this->_recordSave( 'add' );
			break;
			
			case 'doEdit':
				$this->_recordSave( 'edit' );
			break;

			case 'delete':
				$this->_recordDelete();
			break;
			
			case 'lock':
				$this->_recordLock();
			break;
			
			case 'pin':
				$this->_recordPin();
			break;
			
			case 'approve':
				$this->_recordApprove();
			break;
			
			case 'revisions':
				$this->_listRevisions();
			break;

			case 'clearRevisions':
				$this->_deleteAllRevisions();
			break;

			case 'deleteRevision':
				$this->_deleteRevision();
			break;
			
			case 'restoreRevision':
				$this->_restoreRevision();
			break;
			
			case 'editRevision':
				$this->_editRevision();
			break;
			
			case 'doEditRevision':
				$this->_saveRevision();
			break;
			
			case 'compareRevision':
				$this->_compareRevisions();
			break;
			
			case 'comments':
				$this->_listComments();
			break;
			
			case 'deleteComment':
				$this->_deleteComment();
			break;
			
			case 'toggleComment':
				$this->_toggleComment();
			break;
			
			case 'editComment':
				$this->_editComment();
			break;
			
			case 'doEditComment':
				$this->_saveComment();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * List revisions of a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listRevisions()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['record']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_view'], '11CCS69' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_view'], '11CCS70' );
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		$record['title']	= $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		
		//-----------------------------------------
		// Get all revisions for this record
		//-----------------------------------------
		
		$revisions	= array();
		
		$this->DB->build( array( 
								'select'	=> 'r.*', 
								'from'		=> array( 'ccs_database_revisions' => 'r' ), 
								'where'		=> 'r.revision_database_id=' . $this->database['database_id'] . ' AND r.revision_record_id=' . $id, 
								'order'		=> 'r.revision_date DESC',
								'add_join'	=> array(
												array(
													'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
													'from'		=> array( 'members' => 'm' ),
													'where'		=> 'm.member_id=r.revision_member_id',
													'type'		=> 'left'
													)
												)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$revisions[]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->revisions( $this->database, $this->fields, $record, $revisions );
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['dbrevisions_title'] . ' ' . $record['title'] );
	}
	
	/**
	 * Delete all revisions
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteAllRevisions()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['record']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_clear'], '11CCS69.1' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_clear'], '11CCS70.1' );
		}

		//-----------------------------------------
		// Get library and delete
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
		$revisions		= new $classToLoad( $this->registry );
		$revisions->deleteAllRevisions( $this->database['database_id'], $id );

		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['revisions_clr_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=revisions&amp;record=' . $id );
	}
	
	/**
	 * Delete a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteRevision()
	{
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_delete'], '11CCS71' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_delete'], '11CCS72' );
		}
		
		//-----------------------------------------
		// Get library and delete
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
		$revisions		= new $classToLoad( $this->registry );
		$revisions->deleteRevision( $id );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['revision_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=revisions&amp;record=' . $revision['revision_record_id'] );
	}
	
	/**
	 * Restore a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _restoreRevision()
	{
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_restore'], '11CCS73' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_restore'], '11CCS74' );
		}
		
		//-----------------------------------------
		// Get library and restore
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
		$revisions		= new $classToLoad( $this->registry );
		$revisions->restoreRevision( $this->database, $id );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['revision_restored_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=revisions&amp;record=' . $revision['revision_record_id'] );
	}
	
	/**
	 * Form to edit a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _editRevision()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_edit'], '11CCS75' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_edit'], '11CCS76' );
		}
		
		//-----------------------------------------
		// Sort for the form
		//-----------------------------------------
		
		$revisionData	= $this->request['do'] == 'doEditRevision' ? $this->request : unserialize($revision['revision_data']);
		$revisionData['primary_id_field']	= $id;
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$record				= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $revision['revision_record_id'] ) );
		$record['title']	= $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		
		//-----------------------------------------
		// Categories
		//-----------------------------------------

		$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
		
		$this->request['category']	= $revisionData['category_id'];
		$categories					= $_categories->getSelectMenu();

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->recordForm( 'edit', $this->database, $this->fields, $revisionData, $fieldsClass, $categories, true );
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['revisione_title_pre'] . ' ' . $record['title'] );
	}
	
	/**
	 * Save edits to a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _saveRevision()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['record']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_edit'], '11CCS77' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_edit'], '11CCS78' );
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		//-----------------------------------------
		// Process the input
		//-----------------------------------------
		
		$_save	= array();

		foreach( $this->fields as $field )
		{
			$_save['field_' . $field['field_id'] ]	= $fieldsClass->processInput( $field );
			
			if( $error = $fieldsClass->getError() )
			{
				$this->registry->output->global_error	= $error;
				$this->_editRevision();
				return;
			}
		}
		
		foreach( $this->fields as $field )
		{
			$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
		}

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		$this->DB->update( 'ccs_database_revisions', array( 'revision_data' => serialize($_save) ), 'revision_id=' . $id );

		$this->registry->output->setMessage( $this->lang->words['revision_edit_success'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=revisions&amp;record=' . $revision['revision_record_id'] );
	}
	
	/**
	 * Compare revision to current active copy of content
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _compareRevisions()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_compare'], '11CCS79' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_compare'], '11CCS80' );
		}
		
		$revisionData	= unserialize($revision['revision_data']);
		
		$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $revision['revision_record_id'] ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_id_compare'], '11CCS81' );
		}

		//-----------------------------------------
		// Get field type handlers
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		$record				= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $revision['revision_record_id'] ) );
		$record['title']	= $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );

		//-----------------------------------------
		// Loop through all the fields and run through
		// plugin functionality to determine if there are
		// any differences
		//-----------------------------------------
		
		$differences	= array();
		
		foreach( $this->fields as $_field )
		{
			$differences[ $_field['field_id'] ]	= $fieldsClass->compareRevision( $_field, $record['field_' . $_field['field_id'] ], $revisionData['field_' . $_field['field_id'] ] );
		}
		
		$this->registry->output->html .= $this->html->compareRevisions( $this->database, $this->fields, $differences );
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['revisionc_title_pre'] . ' ' . $record['title'] );
	}
	
	/**
	 * Delete a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordDelete()
	{
		$_id	= intval($this->request['record']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_del'], '11CCS82' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_del'], '11CCS83' );
		}
		
		IPSLib::doDataHooks( $record, 'ccsPreDelete' );

		//-----------------------------------------
		// Delete record
		//-----------------------------------------
		
		$this->DB->delete( $this->database['database_database'], 'primary_id_field=' . $_id );
		$this->DB->delete( 'ccs_slug_memory', "memory_type='record' AND memory_type_id=" . $_id . " AND memory_type_id_2=" . $this->database['database_id'] );
		
		//-----------------------------------------
		// Delete revisions
		//-----------------------------------------

		$this->DB->delete( 'ccs_database_revisions', 'revision_database_id=' . $this->database['database_id'] . ' AND revision_record_id=' . $_id );
		
		//-----------------------------------------
		// Delete comments
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_comments', 'comment_database_id=' . $this->database['database_id'] . ' AND comment_record_id=' . $_id );

		//-----------------------------------------
		// Ratings
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_ratings', 'rating_database_id=' . $this->database['database_id'] . ' AND rating_record_id=' . $_id );
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		foreach( $this->fields as $field )
		{
			$fieldsClass->postProcessDelete( $field, $record );
		}

		//-----------------------------------------
		// Topic
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
		$_topics		= new $classToLoad( $this->registry, $fieldsClass, $this->registry->ccsFunctions->getCategoriesClass( $this->database ) );
		$_topics->removeTopic( $record, $this->registry->ccsFunctions->getCategoriesClass( $this->database )->categories[ $record['category_id'] ], $this->database );

		//-----------------------------------------
		// Update database
		//-----------------------------------------

		if( $record['record_approved'] == 1 )
		{
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($this->database['database_record_count'] - 1) ), 'database_id=' . $this->database['database_id'] );
		}

		//-----------------------------------------
		// Remove tags
		//-----------------------------------------
		
		$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->deleteByMetaId( array( $record['primary_id_field'] ) );
		
		//-----------------------------------------
		// Remove likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'ccs', 'default' );
		$_like->remove( array( $record['primary_id_field'] ) );
		
		//-----------------------------------------
		// Data hook
		//-----------------------------------------

		IPSLib::doDataHooks( $record, 'ccsPostDelete' );

		//-----------------------------------------
		// Recache category
		//-----------------------------------------

		$this->registry->ccsFunctions->getCategoriesClass( $this->database )->recache( $record['category_id'] );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecorddeleted'], $record[ $this->database['database_field_title'] ], $this->database['database_name'] ) );
		
		$this->registry->output->setMessage( $this->lang->words['record_deleted_successa'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Lock or unlock a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordLock()
	{
		$_id	= intval($this->request['record']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_lock'], '11CCS84' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_lock'], '11CCS85' );
		}
		
		//-----------------------------------------
		// Toggle lock/unlock status
		//-----------------------------------------
		
		IPSLib::doDataHooks( $record, $record['record_locked'] ? 'ccsPreUnlock' : 'ccsPreLock' );

		$this->DB->update( $this->database['database_database'], array( 'record_locked' => $record['record_locked'] ? 0 : 1 ), 'primary_id_field=' . $_id );

		IPSLib::doDataHooks( $record, $record['record_locked'] ? 'ccsPostUnlock' : 'ccsPostLock' );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordlocked'], $record[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['record_locked_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Pin or unpin a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordPin()
	{
		$_id	= intval($this->request['record']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_pin'], '11CCS86' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_pin'], '11CCS87' );
		}
		
		//-----------------------------------------
		// Toggle lock/unlock status
		//-----------------------------------------
		
		IPSLib::doDataHooks( $record, $record['record_pinned'] ? 'ccsPreUnpin' : 'ccsPrePin' );

		$this->DB->update( $this->database['database_database'], array( 'record_pinned' => $record['record_pinned'] ? 0 : 1 ), 'primary_id_field=' . $_id );

		IPSLib::doDataHooks( $record, $record['record_pinned'] ? 'ccsPostUnpin' : 'ccsPostPin' );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordpinned'], $record[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['record_pin_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Approve or unapprove a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordApprove()
	{
		$_id	= intval($this->request['record']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_app'], '11CCS88' );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_record_app'], '11CCS89' );
		}
		
		//-----------------------------------------
		// Toggle approval status
		//-----------------------------------------
		
		if( $record['record_approved'] == 1 )
		{
			IPSLib::doDataHooks( $record, 'ccsPreUnapprove' );

			$this->DB->update( $this->database['database_database'], array( 'record_approved' => -1 ), 'primary_id_field=' . $_id );
			
			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count-1", 'database_id=' . $this->database['database_id'], false, true );

			IPSLib::doDataHooks( $record, 'ccsPostUnapprove' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordhidden'], $record[ $this->database['database_field_title'] ], $this->database['database_name'] ) );
		}
		else
		{
			IPSLib::doDataHooks( $record, 'ccsPreApprove' );

			$this->DB->update( $this->database['database_database'], array( 'record_approved' => 1 ), 'primary_id_field=' . $_id );
			
			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count+1", 'database_id=' . $this->database['database_id'], false, true );
			
			IPSLib::doDataHooks( $record, 'ccsPostApprove' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordapproved'], $record[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

			//-----------------------------------------
			// If this is first time approval, send notifications
			//-----------------------------------------
			
			$_modQueue	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_modqueue', 'where' => "mod_database={$this->database['database_id']} AND mod_record={$record['primary_id_field']} AND mod_comment=0" ) );
			
			if( $_modQueue['mod_id'] )
			{
		    	//-----------------------------------------
		    	// Post topic if configured to do so
		    	//-----------------------------------------

				$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
			
				$record['record_approved']	= 1;
				
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
		    	$_topics		= new $classToLoad( $this->registry, null, $_categories );
		    	
		    	if( $_topics->checkForTopicSupport( $this->database, $_categories->categories->categories[ $record['category_id'] ] ) )
		    	{
			    	if ( $_topics->postTopic( $record, $_categories->categories[ $record['category_id'] ], $this->database ) )
			    	{
						$_member	= IPSMember::load( $record['member_id'], 'core' );
				    	$record		= array_merge( $_member, $record );
		
						$record['poster_name']			= $record['members_display_name'];
		
						$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
						$databases		= new $classToLoad( $this->registry );
						$databases->sendRecordNotification( $this->database, $_categories->categories[ $record['category_id'] ], $record );
					}
				}
				else
				{
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
					$databases		= new $classToLoad( $this->registry );
					$databases->sendRecordNotification( $this->database, $_categories->categories[ $record['category_id'] ], $record );
				}

			    $this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$record['primary_id_field']} AND mod_comment=0" );
			}
		}
		
		//-----------------------------------------
		// Recache categories
		//-----------------------------------------
		
		if( $record['category_id'] )
		{
			if( !is_object( $_categories ) )
			{
				$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
			}
			
			$_categories->recache( $record['category_id'] );
		}

		$this->registry->output->setMessage( $this->lang->words['record_app_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save a new record/edited record
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _recordSave( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['record']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS90' );
			}
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
			
			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS91' );
			}
		}
		else
		{
			$record	= array();
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		//-----------------------------------------
		// Process the input
		//-----------------------------------------
		
		$_save		= array( 'post_key' => $type == 'edit' ? $record['post_key'] : $this->request['post_key'] );
		$_hasError	= false;

		foreach( $this->fields as $field )
		{
			$_save['field_' . $field['field_id'] ]	= $fieldsClass->processInput( $field );
			
			if( $error = $fieldsClass->getError() )
			{
				$this->registry->output->global_error	= $error;
				$_hasError								= true;
			}
		}
		
		if( $_hasError )
		{
			foreach( $this->fields as $field )
			{
				$fieldsClass->onErrorCallback( $field );
			}

			$this->_recordForm( $type, array_merge( $this->request, $_save ) );
			return;
		}
		
		foreach( $this->fields as $field )
		{
			$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
		}

		$_save['category_id']				= intval($this->request['category_id']);
		$_save['record_static_furl']		= IPSText::makeSeoTitle( trim($this->request['record_static_furl']) );
		$_save['record_dynamic_furl']		= IPSText::makeSeoTitle( $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $_save, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] ) );
		$_save['record_meta_keywords']		= trim($this->request['record_meta_keywords']);
		$_save['record_meta_description']	= trim($this->request['record_meta_description']);

		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------
		
		$where		= array( 'meta_parent_id'	=> $this->request['category_id'],
							 'member_id'		=> $this->memberData['member_id'],
							 'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_POST['ipsTags'] ) ) );
									  
		if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( $type, $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $this->request['category_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => $type == 'add' ? 1 : $record['record_approved'] ) );

			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getErrorMsg() )
			{
				foreach( $this->fields as $field )
				{
					$fieldsClass->onErrorCallback( $field );
				}
	
				$this->registry->output->global_error	= $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getFormattedError();
				$this->_recordForm( $type, array_merge( $this->request, $_save ) );
				return;
			}
		}
		
		//-----------------------------------------
		// Previewing?
		//-----------------------------------------
		
		if( $this->request['preview'] )
		{
			$this->_recordForm( $type, $_save );
			return;
		}

		//-----------------------------------------
		// Category handler
		//-----------------------------------------

		$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
		
		if( $type == 'add' )
		{
			/* Check for uniqueness */
			if( $_save['record_static_furl'] )
			{
				$_test	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $this->database['database_database'], 'where' => "record_static_furl='" . $_save['record_static_furl'] . "' AND category_id={$_save['category_id']}" ) );

				if( $_test['primary_id_field'] )
				{
					$this->registry->output->global_error	= $this->lang->words['static_furl_duplicate'];
					$this->_recordForm( $type, array_merge( $this->request, $_save ) );
					return;
				}
			}

			$_save['member_id']			= $this->memberData['member_id'];
			$_save['record_saved']		= time();
			$_save['record_updated']	= time();
			$_save['record_approved']	= 1;

			IPSLib::doDataHooks( $_save, 'ccsPreSave' );
			
			$this->DB->insert( $this->database['database_database'], $_save );
			
			$id	= $this->DB->getInsertId();
			
			$_save['primary_id_field']	= $id;

			//-----------------------------------------
			// Update database
			//-----------------------------------------
			
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($this->database['database_record_count'] + 1) ), 'database_id=' . $this->database['database_id'] );

			foreach( $this->fields as $field )
			{
				$fieldsClass->postProcessInput( $field, $id );
			}
			
			IPSLib::doDataHooks( $_save, 'ccsPostSave' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordadded'], $_save[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

			//-----------------------------------------
			// Send notifications
			//-----------------------------------------
			
			$_save['poster_name']			= $this->memberData['members_display_name'];

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
			$databases		= new $classToLoad( $this->registry );
			$databases->sendRecordNotification( $this->database, $_categories->categories[ $_save['category_id'] ], $_save );

	    	//-----------------------------------------
	    	// Post topic if configured to do so
	    	//-----------------------------------------
	    	
	    	$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
	    	$_topics		= new $classToLoad( $this->registry, $fieldsClass, $_categories, $this->fields );
	    	$_topics->postTopic( $_save, $_categories->categories[ $_save['category_id'] ], $this->database );
	    	
			//-----------------------------------------
			// Show message
			//-----------------------------------------
			
			$this->registry->output->setMessage( $this->lang->words['record_add_success'] );
		}
		else
		{
			/* Check for uniqueness */
			if( $_save['record_static_furl'] )
			{
				$_test	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $this->database['database_database'], 'where' => "primary_id_field <> {$record['primary_id_field']} AND record_static_furl='" . $_save['record_static_furl'] . "' AND category_id={$_save['category_id']}" ) );

				if( $_test['primary_id_field'] )
				{
					$this->registry->output->global_error	= $this->lang->words['static_furl_duplicate'];
					$this->_recordForm( $type, array_merge( $this->request, $_save ) );
					return;
				}
			}

			//-----------------------------------------
			// Remember past URL?
			//-----------------------------------------

			if( $_save['record_static_furl'] != $record['record_static_furl'] )
			{
				$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $record );
				
				$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => rtrim( $url, '/?' ), 'memory_type' => 'record', 'memory_type_id' => $record['primary_id_field'], 'memory_type_id_2' => $this->database['database_id'] ) );
			}

			$_save['record_updated']	= time();

			$_merged	= array_merge( $record, $_save );
			IPSLib::doDataHooks( $_merged, 'ccsPreEditSave' );
			
			$this->DB->update( $this->database['database_database'], $_save, 'primary_id_field=' . $record['primary_id_field'] );

			foreach( $this->fields as $field )
			{
				$fieldsClass->postProcessInput( $field, $record['primary_id_field'] );
			}

			IPSLib::doDataHooks( $_merged, 'ccsPostEditSave' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbrecordedited'], $_save[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

			//-----------------------------------------
			// Revisions?
			//-----------------------------------------
			
			if( $this->database['database_revisions'] )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
				$revisions		= new $classToLoad( $this->registry );
				$revisions->storeRevision( $this->database, $record );
			}
			
	    	//-----------------------------------------
	    	// Post topic if configured to do so
	    	//-----------------------------------------
	    	
	    	$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
	    	$_topics		= new $classToLoad( $this->registry, $fieldsClass, $_categories, $this->fields );
	    	$_topics->postTopic( array_merge( $record, $_save ), $_categories->categories[ $_save['category_id'] ], $this->database, 'edit' );

			$this->registry->output->setMessage( $this->lang->words['record_edit_success'] );
		}
		
		//-----------------------------------------
		// Store tags
		//-----------------------------------------
		
		if ( ! empty( $_POST['ipsTags'] ) )
		{
			if( $type == 'add' )
			{
				$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->add( $_POST['ipsTags'], array( 'meta_id'			=> $_save['primary_id_field'],
													      				 'meta_parent_id'	=> $_save['category_id'],
													      				 'member_id'		=> $this->memberData['member_id'],
													      				 'meta_visible'		=> $_save['record_approved'] ) );
		 	}
		 	else
		 	{
				$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->replace( $_POST['ipsTags'], array( 'meta_id'			=> $record['primary_id_field'],
														      				 'meta_parent_id'	=> $_save['category_id'],
														      				 'member_id'		=> $this->memberData['member_id'],
														      				 'meta_visible'		=> $record['record_approved'] ) );
		 	}
		}
		
		//-----------------------------------------
		// Recache category
		//-----------------------------------------

		$_categories->recache( $_save['category_id'] );
		
		if( $record['category_id'] AND $_save['category_id'] != $record['category_id'] )
		{
			$_categories->recache( $record['category_id'] );
		}

		//-----------------------------------------
		// Delete URL memories of this URL
		//-----------------------------------------

		$this->registry->ccsFunctions->fetchFreshUrl	= true;
		
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $_save );

		$this->DB->delete( 'ccs_slug_memory', "memory_url='" . rtrim( $url, '/?' ) . "'" );

		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->request['record']	= $this->request['record'] ? $this->request['record'] : $_save['primary_id_field'];
			
			$this->_recordForm( 'edit', $_save );
			return;
		}
		else if( $this->request['save_and_another'] )
		{
			$this->request['record']	= 0;
			$_POST						= array();
			$_REQUEST					= array();
			
			$this->_recordForm( 'add' );
			return;
		}

		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Form to add/edit a record
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @param	array 		Data to use
	 * @return	@e void
	 */
	public function _recordForm( $type='add', $_save=array() )
	{
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['record']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS92' );
			}
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
			
			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS93' );
			}
			
			$this->settings['post_key']	= $record['post_key'];
			
			if( $_save )
			{
				$record	= array_merge( $record, $_save );
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['edit_record_navbar'] );
		}
		else
		{
			$this->settings['post_key']	= md5( uniqid( microtime() ) );
			
			$record	= array( 'post_key' => $this->settings['post_key'] );
			
			foreach( $this->fields as $fielddata )
			{
				if( $fielddata['field_default_value'] )
				{
					$record['field_' . $fielddata['field_id'] ]	= $fielddata['field_default_value'];
				}
			}

			if( count($_save) )
			{
				$record	= array_merge( $record, $_save );
				
				$this->settings['post_key']	= $_save['post_key'];
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['add_record_navbar'] );
		}

		//-----------------------------------------
		// Categories
		//-----------------------------------------

		$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
		
		$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];
		$categories					= $_categories->getSelectMenu();

		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		$record['_tagBox']	= '';

		if( $type == 'add' )
		{
			$where = array( 'meta_parent_id'	=> $this->request['category_id'],
							'member_id'			=> $this->memberData['member_id'],
							'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
							);
		}
		else
		{
			$where = array( 'meta_id'		 => $record['primary_id_field'],
						    'meta_parent_id' => $record['category_id'],
						    'member_id'	     => $this->memberData['member_id']
							);

			if ( $_REQUEST['ipsTags'] )
			{
				$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) );
			}
		}

		if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( $type, $where ) )
		{
			$record['_tagBox'] = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', ipsRegistry::getClass('output')->templateHooks( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where ) ) );
		}

		IPSLib::doDataHooks( $record, $type == 'add' ? 'ccsPreAdd' : 'ccsPreEdit' );

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->recordForm( $type, $this->database, $this->fields, $record, $fieldsClass, $categories );
	}

	/**
	 * List all of the records in this database
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listRecords()
	{
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		
		//-----------------------------------------
		// Sorting and limiting
		//-----------------------------------------
		
		$perPage	= intval($this->request['per_page']) ? intval($this->request['per_page']) : $this->database['database_field_perpage'];
		$perPage	= intval($perPage) > 0 ? $perPage : 50;
		$start		= intval($this->request['st']) ? intval($this->request['st']) : 0;
		
		$_col		= $this->request['sort_col'];
		$_pinned	= $this->request['sort_col'] ? false : true;
		$_useCol	= 'r.' . $this->database['database_field_sort'];
		$_numeric	= false;

		foreach( $this->fields as $_field )
		{
			if( $_col == 'field_' . $_field['field_id'] )
			{
				$_useCol	= 'r.' . $_col;
				$_numeric	= $_field['field_is_numeric'];
			}
			else if( !$_col AND $this->database['database_field_sort'] == 'field_' . $_field['field_id'] )
			{
				$_numeric	= $_field['field_is_numeric'];
			}
		}

		if( in_array( $_col, array( 'rating_real', 'record_views', 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'record_approved', 'record_locked', 'record_comments', 'category_name' ) ) )
		{
			$_useCol	= $_col == 'category_name' ? 'c.' . $_col : 'r.' . $_col;
		}

		$_dir		= ( $this->request['sort_order'] && in_array( $this->request['sort_order'], array( 'asc', 'desc' ) ) ) ? $this->request['sort_order'] : $this->database['database_field_direction'];
		
		$this->request['sort_col']		= str_replace( array( 'c.', 'r.' ), '', $_useCol );
		$this->request['sort_order']	= $_dir;
		$this->request['per_page']		= $perPage;
		
		//-----------------------------------------
		// Search
		//-----------------------------------------

		$_where	= '';
		
		if( $this->request['search_value'] AND $this->request['search_value'] != $this->lang->words['articles_search_phrase'] )
		{
			$_where	= '(' . $fieldsClass->getSearchWhere( $this->fields, $this->request['search_value'] ) . ')';
		}
		
		if( $this->request['category'] )
		{
			$this->filters[]	= "r.category_id=" . intval($this->request['category']);
		}

		if( $this->request['record_approved'] )
		{
			$this->filters[]	= "r.record_approved=" . ( $this->request['record_approved'] == 'yes' ? 1 : ( $this->request['record_approved'] == 'no' ? 0 : -1 ) );
		}
		
		if( $this->request['has_comments'] )
		{
			$this->filters[]	= "r.record_comments" . ( $this->request['has_comments'] == 'yes' ? " > 0 " : "=0" );
		}
		
		if( $this->request['article_pinned'] )
		{
			$this->filters[]	= "r.record_pinned=1";
		}
		
		if( count($this->filters) )
		{
			if( $_where )
			{
				$_where	.= " AND " . implode( " AND ", $this->filters );
			}
			else
			{
				$_where	= implode( " AND ", $this->filters );
			}
		}

		//-----------------------------------------
		// Get total
		//-----------------------------------------
		
		$count		= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as total',
													'from'		=> array( $this->database['database_database'] => 'r' ), 
													'where'		=> $_where,
													'add_join'	=> array(
																		array(
																			'from'	=> array( 'ccs_database_categories' => 'c' ),
																			'where'	=> 'c.category_id=r.category_id',
																			'type'	=> 'left',
																			)
																		)
												)		);
		
		//-----------------------------------------
		// Pagination
		//-----------------------------------------

		$_fieldSt	= intval($this->request['field_st']);
		$_qs		= '&amp;search_value=' . urlencode($this->request['search_value']) . ( $_useCol != 'r.' . $this->database['database_field_sort'] ? '&amp;sort_col=' . str_replace( array( 'r.', 'c.' ), '', $_useCol ) : '' ) . '&amp;sort_order=' . $_dir . '&amp;per_page=' . $perPage . '&amp;category=' . intval($this->request['category']);
		
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . $this->form_code . '&amp;field_st=' . $_fieldSt . $_qs,
											 				)	);

		//-----------------------------------------
		// Categories for filtering
		//-----------------------------------------
		
		$categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database, false );
		$catFilter	= $categories->getSelectMenu();
				
		//-----------------------------------------
		// Get records
		//-----------------------------------------
		
		$records	= array();
		
		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $this->database['database_database'] => 'r' ), 
								'order'		=> ( $_pinned ? 'r.record_pinned DESC, ' : '' ) . ( $_numeric ? $_useCol . '+0' : $_useCol ) . ' ' . $_dir,
								'where'		=> $_where,
								'limit'		=> array( $start, $perPage ),
								'add_join'	=> array(
													array(
														'from'	=> array( 'ccs_database_categories' => 'c' ),
														'where'	=> 'c.category_id=r.category_id',
														'type'	=> 'left',
														),
													$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) )
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$r['category_name']					= $categories->categories[ $r['category_id'] ]['category_name'];
			$r['_revisions']					= 0;

			if ( ! empty( $r['tag_cache_key'] ) )
			{
				$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
			}

			$records[ $r['primary_id_field'] ]	= $r;
		}
		
		$_ids	= array_keys($records);
		
		if( count($_ids) )
		{
			$this->DB->build( array( 'select' => 'COUNT(*) as total, revision_record_id', 'from' => 'ccs_database_revisions', 'where' => 'revision_database_id=' . $this->database['database_id'] . ' AND revision_record_id IN(' . implode( ',', $_ids ) . ')', 'group' => 'revision_record_id' ) );
			$this->DB->execute();
			
			while( $_q = $this->DB->fetch() )
			{
				$records[ $_q['revision_record_id'] ]['_revisions']	= $_q['total'];
			}
		}
		
		//-----------------------------------------
		// Number of queued comments
		//-----------------------------------------
		
		$comments	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'ccs_database_comments', 'where' => "comment_approved=0 AND comment_database_id=" . $this->database['database_id'] ) );
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->records( $this->database, $this->fields, $records, $pages, $fieldsClass, $_qs, $catFilter, $comments['total'] );
	}

	
	/**
	 * List comments of a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listComments()
	{
		$record		= array();
		
		if( $this->request['filter'] != 'queued' )
		{
			$id	= intval($this->request['record']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_view'], '11CCS94' );
			}
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $id ) );
			
			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_view'], '11CCS95' );
			}
			
			//-----------------------------------------
			// Get possible field types
			//-----------------------------------------
			
			$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
			
			$record['title']	= $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		}
		
		//-----------------------------------------
		// Get all comments for this record
		//-----------------------------------------
		
		$perPage	= 50;
		$start		= intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		
		//-----------------------------------------
		// Using forums?
		//-----------------------------------------
		
		$this->categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
	    $forumIntegration	= $this->categories->categories[ $record['category_id'] ]['category_forum_override'] ? $this->categories->categories[ $record['category_id'] ]['category_forum_record'] : $this->database['database_forum_record'];
	    $commentsToo		= $this->categories->categories[ $record['category_id'] ]['category_forum_override'] ? $this->categories->categories[ $record['category_id'] ]['category_forum_comments'] : $this->database['database_forum_comments'];
		
	    if( $this->request['filter'] != 'queued' AND $forumIntegration AND $commentsToo )
	    {
			ipsRegistry::getAppClass( 'forums' );
		
			$topic		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=" . $record['record_topicid'] ) );
			$count		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'posts', 'where' => "pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" ) );

			$pages		= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $record['primary_id_field'],
											 				)	);
										 				
			$this->DB->build( array(
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.pid <> {$topic['topic_firstpost']} AND p.topic_id={$record['record_topicid']}",
									'order'		=> 'p.post_date ASC',
									'limit'		=> array( $start, $perPage ),
									'add_join'	=> array(
														array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=p.author_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pd.*',
															'from'		=> array( 'pfields_content' => 'pd' ),
															'where'		=> 'pd.member_id=m.member_id',
															'type'		=> 'left',
															),
														)
							)		);
			$outer	= $this->DB->execute();

			while( $row = $this->DB->fetch($outer) )
			{
				$row	= IPSMember::buildDisplayData( $row );
				
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->registry->getClass('class_forums')->allForums[ $topic['forum_id'] ]['use_html'] and $this->caches['group_cache'][ $row['member_group_id'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->registry->getClass('class_forums')->allForums[ $topic['forum_id'] ]['use_ibc'];
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
				
				$comments[]	= $row;
			}
			
			//-----------------------------------------
			// Output
			//-----------------------------------------
	
			$this->registry->output->html .= $this->html->topicComments( $this->database, $record, $comments, $pages );
		}
		
		//-----------------------------------------
		// Comments stored locally
		//-----------------------------------------
		
		else
		{
			if( $this->request['filter'] == 'queued' )
			{
				$_where	= "c.comment_database_id={$this->database['database_id']} AND c.comment_approved=0";
			}
			else
			{
				$_where	= "c.comment_database_id={$this->database['database_id']} AND c.comment_record_id={$record['primary_id_field']}";
			}
			
			$count		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_database_comments c', 'where' => $_where ) );
			
			$pages		= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . $this->form_code . '&amp;do=comments' . ( $this->request['filter'] == 'queued' ? '&amp;filter=queued' : '&amp;record=' . $record['primary_id_field'] ),
											 				)	);
										 				
			$this->DB->build( array(
									'select'	=> 'c.*',
									'from'		=> array( 'ccs_database_comments' => 'c' ),
									'where'		=> $_where,
									'order'		=> 'c.comment_date ASC',
									'limit'		=> array( $start, $perPage ),
									'add_join'	=> array(
														array(
															'select'	=> 'm.*',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=c.comment_user',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pd.*',
															'from'		=> array( 'pfields_content' => 'pd' ),
															'where'		=> 'pd.member_id=m.member_id',
															'type'		=> 'left',
															),
														)
							)		);
			$outer	= $this->DB->execute();
	
			IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'ccs_comment';
			
			while( $comment = $this->DB->fetch($outer) )
			{
				$comment	= IPSMember::buildDisplayData( $comment );
				
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $comment['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $comment['mgroup_others'];
				$comment['comment_post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $comment['comment_post'] );
				
				$comments[]	= $comment;
			}
			
			//-----------------------------------------
			// Output
			//-----------------------------------------
	
			$this->registry->output->html .= $this->html->comments( $this->database, $record, $comments, $pages );
		}
		
		if( $this->request['filter'] == 'queued' )
		{
			$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;filter=queued', $this->lang->words['dbcomments_title_q'] );
		}
		else
		{
			$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $record['primary_id_field'], $this->lang->words['dbcomments_title'] . ' ' . $record['primary_id_field'] );
		}
	}
	
	/**
	 * Toggle a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _toggleComment()
	{
		$id		= intval($this->request['comment']);
		$pid	= intval($this->request['pid']);
		
		if( !$id AND !$pid )
		{
			$this->registry->output->showError( $this->lang->words['no_comment_id_toggle'], '11CCS96' );
		}
		
		//-----------------------------------------
		// Comments within CCS?
		//-----------------------------------------
		
		if( $id )
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'c.*',
														'from'		=> array( 'ccs_database_comments' => 'c' ),
														'where'		=> 'c.comment_id=' . $id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.*',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.primary_id_field=c.comment_record_id',
																				'type'		=> 'left',
																				),
																			array(
																				'select'	=> 'm.members_display_name as poster_name',
																				'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=c.comment_user',
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$comment['comment_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_toggle'], '11CCS97' );
			}
			
			//-----------------------------------------
			// Toggle
			//-----------------------------------------
			
			if( $comment['comment_approved'] )
			{
				$this->DB->update( 'ccs_database_comments', array( 'comment_approved' => '-1' ), 'comment_id=' . $id );
				
				$this->DB->update( 'ccs_custom_database_' . $comment['comment_database_id'], "record_comments=record_comments-1, record_comments_queued=record_comments_queued+1", 'primary_id_field=' . $comment['comment_record_id'], false, true );
				
				if( $comment['category_id'] )
				{
					$this->DB->update( 'ccs_database_categories', "category_record_comments=category_record_comments-1, category_record_comments_queued=category_record_comments_queued+1", 'category_id=' . $comment['category_id'], false, true );
				}
				
				$_string	= 'comment_toggledh_success';
			}
			else
			{
				$this->DB->update( 'ccs_database_comments', array( 'comment_approved' => 1 ), 'comment_id=' . $id );
				
				//-----------------------------------------
				// Update comment count
				//-----------------------------------------
				
				$update	= "record_comments=record_comments+1, record_comments_queued=record_comments_queued-1";
				
				//-----------------------------------------
				// Bump record?
				//-----------------------------------------
				
				if( $this->database['database_comment_bump'] )
				{
					if( $comment['comment_date'] > $comment['record_updated'] )
					{
						$update	.= ", record_updated='{$comment['comment_date']}'";
					}
				}
				
				$this->DB->update( 'ccs_custom_database_' . $comment['comment_database_id'], $update, 'primary_id_field=' . $comment['comment_record_id'], false, true );
				
				if( $comment['category_id'] )
				{
					$this->DB->update( 'ccs_database_categories', "category_record_comments=category_record_comments+1, category_record_comments_queued=category_record_comments_queued-1", 'category_id=' . $comment['category_id'], false, true );
				}
				
				//-----------------------------------------
				// If this is first time approval, send notifications
				//-----------------------------------------
				
				$_modQueue	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_modqueue', 'where' => "mod_database={$this->database['database_id']} AND mod_record={$comment['comment_record_id']} AND mod_comment={$comment['comment_id']}" ) );
				
				if( $_modQueue['mod_id'] )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
					$_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $this->database, 'category' => $this->registry->ccsFunctions->getCategoriesClass( $this->database )->categories[ $comment['category_id'] ], 'record' => $comment ) );
					$_comments->sendNotifications( $_comments->remapFromLocal( $comment ), $comment['comment_post'] );
	
				    $this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$comment['comment_record_id']} AND mod_comment={$comment['comment_id']}" );
				}
				
				$_string	= 'comment_toggled_success';
			}
			
			$_url	= $comment['comment_record_id'] . '&filter=' . $this->request['filter'];
		}
		
		//-----------------------------------------
		// Comments stored in topics?
		//-----------------------------------------
		
		else
		{
			$comment	= $this->DB->buildAndFetch( array(
														//-----------------------------------------
														// The extra columns are for the notifications
														//-----------------------------------------
														
														'select'	=> 'p.pid, p.queued, p.author_id as comment_user, p.pid as comment_id, p.post as comment_post',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> 'p.pid=' . $pid,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.*',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.record_topicid=p.topic_id',
																				'type'		=> 'left',
																				)
																			)
												)		);

			if( !$comment['pid'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_toggle'], '11CCS98' );
			}
			
			//-----------------------------------------
			// Get possible field types
			//-----------------------------------------
			
			$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
			
			//-----------------------------------------
			// Categories
			//-----------------------------------------
	
			$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );

			if( $comment['queued'] )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $fieldsClass, $_categories, $this->fields );
				$_topics->toggleComment( $comment, $_categories->categories[ $comment['category_id'] ], $this->database, $pid, true );

				//-----------------------------------------
				// Update comment count
				//-----------------------------------------
				
				$update	= "record_comments=record_comments+1, record_comments_queued=record_comments_queued-1";
				
				//-----------------------------------------
				// Bump record?
				//-----------------------------------------
				
				if( $this->database['database_comment_bump'] )
				{
					if( $comment['post_date'] > $comment['record_updated'] )
					{
						$update	.= ", record_updated='{$comment['post_date']}'";
					}
				}
				
				$this->DB->update( $this->database['database_database'], $update, 'primary_id_field=' . $comment['primary_id_field'], false, true );

				if( $comment['category_id'] )
				{
					$this->DB->update( 'ccs_database_categories', "category_record_comments=category_record_comments+1, category_record_comments_queued=category_record_comments_queued-1", 'category_id=' . $comment['category_id'], false, true );
				}
				
				//-----------------------------------------
				// If this is first time approval, send notifications
				//-----------------------------------------
				
				$_modQueue	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_modqueue', 'where' => "mod_database={$this->database['database_id']} AND mod_record={$comment['primary_id_field']} AND mod_comment={$comment['pid']}" ) );
				
				if( $_modQueue['mod_id'] )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
					$_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $this->database, 'category' => $this->registry->ccsFunctions->getCategoriesClass( $this->database )->categories[ $comment['category_id'] ], 'record' => $comment ) );
					$_comments->sendNotifications( $_comments->remapFromLocal( $comment ), $comment['comment_post'] );
	
				    $this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$comment['primary_id_field']} AND mod_comment={$comment['pid']}" );
				}
				
				$_string	= 'comment_toggled_success';
			}
			else
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $fieldsClass, $_categories, $this->fields );
				$_topics->hideComment( $comment, $_categories->categories[ $comment['category_id'] ], $this->database, $pid, '' );
				
				$this->DB->update( $this->database['database_database'], "record_comments=record_comments-1, record_comments_queued=record_comments_queued+1", 'primary_id_field=' . $comment['primary_id_field'], false, true );

				if( $comment['category_id'] )
				{
					$this->DB->update( 'ccs_database_categories', "category_record_comments=category_record_comments-1, category_record_comments_queued=category_record_comments_queued+1", 'category_id=' . $comment['category_id'], false, true );
				}
				
				$_string	= 'comment_toggledh_success';
			}

			$_url	= $comment['primary_id_field'];
		}
		
		IPSLib::doDataHooks( $comment, 'ccsCommentToggleVisibility' );

		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcommenttoggled'], $comment[ $this->database['database_field_title'] ], $this->database['database_name'] ) );
		
		$this->registry->output->setMessage( $this->lang->words[ $_string ] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $_url );
	}
	
	/**
	 * Delete a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteComment()
	{
		$id		= intval($this->request['comment']);
		$pid	= intval($this->request['pid']);
		
		if( !$id AND !$pid )
		{
			$this->registry->output->showError( $this->lang->words['no_comment_id_delete'], '11CCS99' );
		}
		
		if( $id )
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'c.*',
														'from'		=> array( 'ccs_database_comments' => 'c' ),
														'where'		=> 'c.comment_id=' . $id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.*',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.primary_id_field=c.comment_record_id',
																				'type'		=> 'left',
																				)
																			)
													)	);
		
			if( !$comment['comment_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_delete'], '11CCS100' );
			}
			
			//-----------------------------------------
			// Delete
			//-----------------------------------------
			
			$this->DB->delete( 'ccs_database_comments', 'comment_id=' . $id );
			
			if( $comment['comment_approved'] )
			{
				$this->DB->update( $this->database['database_database'], "record_comments=record_comments-1", 'primary_id_field=' . $comment['comment_record_id'], false, true );
			}
			
			$this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$comment['comment_record_id']} AND mod_comment={$comment['comment_id']}" );
			
			$_url	= $comment['comment_record_id'] . '&filter=' . $this->request['filter'];
		}
		else
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'p.pid, p.queued',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> 'p.pid=' . $pid,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.*',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.record_topicid=p.topic_id',
																				'type'		=> 'left',
																				)
																			)
												)		);

			if( !$comment['pid'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_delete'], '11CCS101' );
			}
			
			//-----------------------------------------
			// Get possible field types
			//-----------------------------------------
			
			$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
			
			//-----------------------------------------
			// Categories
			//-----------------------------------------
	
			$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
		
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $fieldsClass, $_categories, $this->fields );
			$_topics->removeComment( $comment, $_categories->categories[ $comment['category_id'] ], $this->database, $pid );
			
			if( !$comment['queued'] )
			{
				$this->DB->update( $this->database['database_database'], "record_comments=record_comments-1", 'primary_id_field=' . $comment['primary_id_field'], false, true );
			}
			
			$this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$comment['primary_id_field']} AND mod_comment={$comment['pid']}" );
			
			$_url	= $comment['primary_id_field'];
		}

		IPSLib::doDataHooks( $comment, 'ccsCommentPostDelete' );

		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcommentdeleted'], $comment[ $this->database['database_field_title'] ], $this->database['database_name'] ) );
		
		$this->registry->output->setMessage( $this->lang->words['comment_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $_url );
	}

	/**
	 * Form to edit a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _editComment()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id		= intval($this->request['comment']);
		$pid	= intval($this->request['pid']);
		
		if( !$id AND !$pid )
		{
			$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS102' );
		}
		
		if( $id )
		{
			$comment	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_comments', 'where' => 'comment_id=' . $id ) );
			
			if( !$comment['comment_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS103' );
			}
			
			$value = $comment['comment_post'];
		}
		else
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'p.*',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> 'p.pid=' . $pid,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.primary_id_field',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.record_topicid=p.topic_id',
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$comment['pid'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS104' );
			}
			
			$value = $comment['post'];
			
			$comment['comment_record_id']	= $comment['primary_id_field'];
		}

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();

		$editor->setContent( $value );

		$editor_area	= $editor->show( 'Post' );

		IPSLib::doDataHooks( $comment, 'ccsEditComment' );

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->commentForm( $this->database, $comment, $editor_area );
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $comment['comment_record_id'], $this->lang->words['dbecomment_title'] . ' ' . $comment['comment_record_id'] );
	}
	
	/**
	 * Save edits to a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _saveComment()
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id		= intval($this->request['comment_id']);
		$pid	= intval($this->request['pid']);
		
		if( !$id AND !$pid )
		{
			$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS105' );
		}
		
		if( $id )
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'c.*',
														'from'		=> array( 'ccs_database_comments' => 'c' ),
														'where'		=> 'c.comment_id=' . $id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.*',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.primary_id_field=c.comment_record_id',
																				'type'		=> 'left',
																				)
																			)
													)	);
			
			if( !$comment['comment_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS106' );
			}
		}
		else
		{
			$comment	= $this->DB->buildAndFetch( array(
														'select'	=> 'p.*',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> 'p.pid=' . $pid,
														'add_join'	=> array(
																			array(
																				'select'	=> 'r.primary_id_field',
																				'from'		=> array( $this->database['database_database'] => 'r' ),
																				'where'		=> 'r.record_topicid=p.topic_id',
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$comment['pid'] )
			{
				$this->registry->output->showError( $this->lang->words['no_comment_id_edit'], '11CCS107' );
			}
			
			$comment['comment_record_id']	= $comment['primary_id_field'];
		}

		//-----------------------------------------
		// Process the input
		//-----------------------------------------
		
		/* Check the comment */
		if( IPSText::mbstrlen( trim( $_POST['Post'] ) ) < 1 )
		{
			$this->registry->output->showError( 'no_comment_submitted', '11CCS108' );
		}

		/* Process the comment */
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $classToLoad();
		$content		= $editor->process( $_POST['Post'] );

		IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html			= 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section		= 'ccs_comment';

		$content		= IPSText::getTextClass( 'bbcode' )->preDbParse( $content );

		/* Build the comment entry */
		if( $id )
		{
			$update = array(
							'comment_post'	=> $content,
						 );
		}
		else
		{
			$update = array(
							'post'			=> $content,
						 );
		}

		/* Assign any errors */
	    if( IPSText::getTextClass('bbcode')->error )
	    {
	    	$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
	    	
	    	$this->registry->output->showError( IPSText::getTextClass('bbcode')->error, '11CCS109' );
	    }

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		if( $id )
		{
			$this->DB->update( 'ccs_database_comments', $update, 'comment_id=' . $id );
		}
		else
		{
			$this->DB->update( 'posts', $update, 'pid=' . $pid );
		}

		$_merged	= array_merge( $comment, $update );
		IPSLib::doDataHooks( $_merged, 'ccsCommentEditPostSave' );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcommentedited'], $comment[ $this->database['database_field_title'] ], $this->database['database_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['comment_edit_success'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=comments&amp;record=' . $comment['comment_record_id'] . '&filter=' . $this->request['filter'] );
	}
}
