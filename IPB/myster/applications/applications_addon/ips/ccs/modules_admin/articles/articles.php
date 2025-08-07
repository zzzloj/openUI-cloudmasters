<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article management
 * Last Updated: $Date: 2012-02-03 12:12:36 -0500 (Fri, 03 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 10249 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_articles extends ipsCommand
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
	 * Main records handler
	 *
	 * @access	protected
	 * @var		object			Records action file
	 */
	protected $recordHandler;

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
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=articles';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=articles';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang', 'public_lang' ) );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// Get db info
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
			$this->registry->output->showError( $this->lang->words['no_db_id_records'], '11CCS3' );
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
		// Get main handler
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/modules_admin/databases/records.php', 'admin_ccs_databases_records', 'ccs' );
		$this->recordHandler	= new $classToLoad( $this->registry );
		$this->recordHandler->makeRegistryShortcuts( $this->registry );
		
		$this->recordHandler->form_code		= $this->form_code;
		$this->recordHandler->form_code_js	= $this->form_code_js;
		$this->recordHandler->html			= $this->html;
		$this->recordHandler->database		= $this->database;
		$this->recordHandler->fields		= $this->fields;
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );

		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $this->database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $this->database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $this->database['database_id'] ) );
		}

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
		$this->recordHandler->_listRevisions();
	}
	
	/**
	 * Delete all revisions
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteAllRevisions()
	{
		$this->recordHandler->_deleteAllRevisions();
	}
	
	/**
	 * Delete a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteRevision()
	{
		$this->recordHandler->_deleteRevision();
	}
	
	/**
	 * Restore a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _restoreRevision()
	{
		$this->recordHandler->_restoreRevision();
	}
	
	/**
	 * Form to edit a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _editRevision()
	{
		$this->recordHandler->_editRevision();
	}
	
	/**
	 * Save edits to a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _saveRevision()
	{
		$this->recordHandler->_saveRevision();
	}
	
	/**
	 * Compare revision to current active copy of content
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _compareRevisions()
	{
		$this->recordHandler->_compareRevisions();
	}
	
	/**
	 * Delete a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordDelete()
	{
		$this->recordHandler->_recordDelete();
	}
	
	/**
	 * Lock or unlock a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordLock()
	{
		$this->recordHandler->_recordLock();
	}
	
	/**
	 * Pin or unpin a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordPin()
	{
		$this->recordHandler->_recordPin();
	}
	
	/**
	 * Approve or unapprove a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _recordApprove()
	{
		$this->recordHandler->_recordApprove();
	}
	
	/**
	 * Save a new record/edited record.
	 * Had to copy most of the function so that I can alter how some things work/are stored.
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
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS4' );
			}
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $_id ) );
			
			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_record_edit'], '11CCS5' );
			}
		}
		else
		{
			$record	= array();
		}
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		
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
		$_save['record_static_furl']		= IPSText::alphanumericalClean( trim($this->request['record_static_furl']), '.,' );
		$_save['record_dynamic_furl']		= IPSText::makeSeoTitle( $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $_save, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] ) );
		$_save['record_meta_keywords']		= trim($this->request['record_meta_keywords']);
		$_save['record_meta_description']	= trim($this->request['record_meta_description']);
		$_save['record_template']			= intval($this->request['record_template']);
		$_save['record_pinned']				= intval($this->request['record_pinned']);
		$_save['record_approved']			= intval($this->request['record_approved']);

		//-----------------------------------------
		// Author
		//-----------------------------------------
		
		$_save['member_id']			= ( $type == 'edit' ) ? $record['member_id'] : $this->memberData['member_id'];
		
		if( $this->request['record_members_display_name'] )
		{
			$member					= IPSMember::load( $this->request['record_members_display_name'], 'members', 'displayname' );
			$_save['member_id']		= $member['member_id'] ? $member['member_id'] : $_save['member_id'];
		}

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

		$_categories	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database );
		
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

			$_save['record_saved']		= time();
			$_save['record_updated']	= time();
			$_save['record_approved']	= intval($this->request['record_approved']);

			IPSLib::doDataHooks( $_save, 'ccsPreSave' );
			
			$this->DB->insert( $this->database['database_database'], $_save );
			
			$id	= $this->DB->getInsertId();
			
			$_save['primary_id_field']	= $id;
			$this->request['record']	= $id;

			//-----------------------------------------
			// Update database
			//-----------------------------------------
			
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($this->database['database_record_count'] + 1) ), 'database_id=' . $this->database['database_id'] );

			foreach( $this->fields as $field )
			{
				$fieldsClass->postProcessInput( $field, $id );
			}
			
			IPSLib::doDataHooks( $_save, 'ccsPostSave' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_articleadded'], $_save[ $this->database['database_field_title'] ] ) );

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
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_articleedited'], $_save[ $this->database['database_field_title'] ] ) );

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
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=edit&record=' . $this->request['record'] );
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
		/* Default published to yes */
		if( $type == 'add' AND !count($_save) )
		{
			$_save['record_approved']	= 1;
		}
		
		/* Fix nav */
		$this->registry->getClass('output')->ignoreCoreNav	= true;
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=articles', ipsRegistry::$modules_by_section['ccs']['articles']['sys_module_title'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=articles&amp;section=articles', $this->lang->words['articles_manage_title'] );
		
		$this->recordHandler->_recordForm( $type, $_save );
	}

	/**
	 * List all of the records in this database
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listRecords()
	{
		foreach( $this->fields as $field )
		{
			if( $this->request['article_homepage'] AND $field['field_key'] == 'article_homepage' )
			{
				$this->recordHandler->filters[]	= "r.field_{$field['field_id']}=',1,'";
			}

			if( $field['field_key'] == 'article_date' )
			{
				ipsRegistry::$request['sort_col']	= 'field_' . $field['field_id'];
			}
		}

		$this->recordHandler->_listRecords();
	}

	/**
	 * List comments of a record
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listComments()
	{
		$this->recordHandler->_listComments();
	}
	
	/**
	 * Toggle a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _toggleComment()
	{
		$this->recordHandler->_toggleComment();
	}
	
	/**
	 * Delete a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteComment()
	{
		$this->recordHandler->_deleteComment();
	}

	/**
	 * Form to edit a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _editComment()
	{
		$this->recordHandler->_editComment();
	}
	
	/**
	 * Save edits to a comment
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _saveComment()
	{
		$this->recordHandler->_saveComment();
	}
}
