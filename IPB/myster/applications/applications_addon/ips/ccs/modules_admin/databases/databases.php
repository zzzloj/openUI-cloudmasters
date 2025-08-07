<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS database management console
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

class admin_ccs_databases_databases extends ipsCommand
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
	 * Container type to check
	 *
	 * @access	public
	 * @var		string			One of dbtemplate or arttemplate
	 */
	public $containerType		= 'dbtemplate';

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_databases' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=databases&amp;section=databases';
		$this->form_code_js	= $this->html->form_code_js	= 'module=databases&section=databases';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->request['id'] );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_listDatabases();
			break;

			case 'visit':
				$this->_visitDatabase();
			break;
			
			case 'add':
			case 'edit':
				$this->_databaseForm( $this->request['do'] );
			break;
			
			case 'doAdd':
				$this->_databaseSave( 'add' );
			break;
			
			case 'doEdit':
				$this->_databaseSave( 'edit' );
			break;

			case 'delete':
				$this->_databaseDelete();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Visit a database
	 *
	 * @return	@e void
	 */
	protected function _visitDatabase()
	{
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_db_visit'], '11CCS38.v1' );
		}
		
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $_id );
		
		if( !$url OR $url == '#' )
		{
			$this->registry->output->showError( $this->lang->words['no_page_for_db_visit'], '11CCS38.v2' );
		}

		$this->registry->output->redirectScreen( $this->lang->words['taking_you_to_page'], $url );
	}
	
	/**
	 * Delete a database
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _databaseDelete()
	{
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_db_del'], '11CCS38' );
		}
		
		$database	= $this->DB->buildAndFetch( array(
													'select'	=> 'd.*', 
													'from'		=> array( 'ccs_databases' => 'd' ), 
													'where'		=> 'd.database_id=' . $_id,
													'add_join'	=> array(
																		array(
																			'select'	=> 'p.*',
																			'from'		=> array( 'permission_index' => 'p' ),
																			'where'		=> "p.perm_type='databases' AND p.perm_type_id=d.database_id",
																			'type'		=> 'left',
																			)
																		)
											)		);
		
		if( !$database['database_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_db_del'], '11CCS39' );
		}
		
		//-----------------------------------------
		// Delete database
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_databases', 'database_id=' . $_id );
		$this->DB->delete( 'permission_index', "app='ccs' AND perm_type='databases' AND perm_type_id=" . $_id );
		
		//-----------------------------------------
		// Delete fields
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_fields', 'field_database_id=' . $_id );
		
		//-----------------------------------------
		// Revisions
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_revisions', 'revision_database_id=' . $_id );
		
		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_categories', 'category_database_id=' . $_id );
		
		//-----------------------------------------
		// Comments
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_comments', 'comment_database_id=' . $_id );
		
		//-----------------------------------------
		// Ratings
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_ratings', 'rating_database_id=' . $_id );

		//-----------------------------------------
		// Delete tags
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $_id ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $_id, classes_tags_bootstrap::run( 'ccs', 'records-' . $_id ) );
		}

		$_records	= array();
		
		$this->DB->build( array( 'select' => 'primary_id_field', 'from' => $database['database_database'] ) );
		$this->DB->execute();
		
		while( $_r = $this->DB->fetch() )
		{
			$_records[]	= $_r['primary_id_field'];
		}
		
		$this->registry->getClass('ccsTags-' . $_id )->deleteByMetaId( $_records );
		
		//-----------------------------------------
		// Delete records
		//-----------------------------------------
		
		$this->DB->dropTable( $database['database_database'] );
		
		//-----------------------------------------
		// Rebuild cache
		//-----------------------------------------
		
		$this->rebuildCache();

		//-----------------------------------------
		// Log and redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbdeleted'], $database['database_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['db_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save a new database/edited database
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _databaseSave( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['id']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_db_edit'], '11CCS40' );
			}
			
			$database	= $this->DB->buildAndFetch( array(
														'select'	=> 'd.*', 
														'from'		=> array( 'ccs_databases' => 'd' ), 
														'where'		=> 'd.database_id=' . $_id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'p.*',
																				'from'		=> array( 'permission_index' => 'p' ),
																				'where'		=> "p.perm_type='databases' AND p.perm_type_id=d.database_id",
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$database['database_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_db_edit'], '11CCS41' );
			}
		}
		else
		{
			$database	= array();
		}
		
		$_save	= array(
						'database_name'					=> trim($this->request['database_name']),
						'database_key'					=> trim( preg_replace( "/[^a-zA-Z0-9\-_]/", '', $this->request['database_key'] ) ),
						'database_description'			=> trim($this->request['database_description']),
						'database_template_listing'		=> intval($this->request['database_template_listing']),
						'database_template_display'		=> intval($this->request['database_template_display']),
						'database_template_categories'	=> intval($this->request['database_template_categories']),
						'database_all_editable'			=> intval($this->request['database_all_editable']),
						'database_search'				=> intval($this->request['database_search']),
						'database_comment_bump'			=> intval($this->request['database_comment_bump']),
						'database_revisions'			=> intval($this->request['database_revisions']),
						'database_field_title'			=> trim($this->request['database_field_title']),
						'database_field_content'		=> trim($this->request['database_field_content']),
						'database_field_sort'			=> trim($this->request['database_field_sort']),
						'database_field_direction'		=> trim( strtolower( $this->request['database_field_direction'] ) ),
						'database_field_perpage'		=> $this->request['database_field_perpage'] ? intval($this->request['database_field_perpage']) : 25,
						'database_comment_approve'		=> intval($this->request['database_comment_approve']),
						'database_record_approve'		=> intval($this->request['database_record_approve']),
						'database_lang_sl'				=> trim($this->request['database_lang_sl']),
						'database_lang_pl'				=> trim($this->request['database_lang_pl']),
						'database_lang_su'				=> trim($this->request['database_lang_su']),
						'database_lang_pu'				=> trim($this->request['database_lang_pu']),
						'database_rss'					=> intval($this->request['database_rss']),
						'database_rss_cache'			=> null,
						'database_rss_cached'			=> 0,
						'database_tags_enabled'			=> intval($this->request['database_tags_enabled']),
						'database_tags_noprefixes'		=> intval($this->request['database_tags_noprefixes']),
						'database_tags_predefined'		=> IPSText::stripslashes($_POST['database_tags_predefined']),
						);

		if( !$_save['database_name'] )
		{
			$this->registry->output->showError( $this->lang->words['database_name_missing'], '11CCS148.1' );
		}
		
		//-----------------------------------------
		// Forum posting options
		//-----------------------------------------

		$_save['database_forum_record']		= intval($this->request['database_forum_record']);
		$_save['database_forum_comments']	= intval($this->request['database_forum_comments']);
		$_save['database_forum_delete']		= intval($this->request['database_forum_delete']);
		$_save['database_forum_forum']		= intval($this->request['database_forum_forum']);
		$_save['database_forum_prefix']		= $this->request['database_forum_prefix'];
		$_save['database_forum_suffix']		= $this->request['database_forum_suffix'];
					
		if( $_save['database_forum_record'] )
		{
			if( !$_save['database_forum_forum'] )
			{
				$this->registry->output->setMessage( $this->lang->words['db_error__selectforum'] );
				$this->_databaseForm( $type );
				return;
			}
			
			$forum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'forums', 'where' => 'id=' . $_save['database_forum_forum'] ) );
			
			if( !$forum['id'] )
			{
				$this->registry->output->global_error = $this->lang->words['db_error__noforum'];
				$this->_databaseForm( $type );
				return;
			}

			if( !$forum['sub_can_post'] )
			{
				$this->registry->output->global_error = $this->lang->words['db_error__canpost'];
				$this->_databaseForm( $type );
				return;
			}
			
			if( $forum['redirect_on'] )
			{
				$this->registry->output->global_error = $this->lang->words['db_error__redirect'];
				$this->_databaseForm( $type );
				return;
			}
		}

		//-----------------------------------------
		// Checks against field sorting stuff
		//-----------------------------------------
		
		$_save['database_field_perpage']	= intval($_save['database_field_perpage']) > 0 ? $_save['database_field_perpage'] : 25;
		$_save['database_field_direction']	= in_array( $_save['database_field_direction'], array( 'asc', 'desc' ) ) ? $_save['database_field_direction'] : 'desc';
		
		if( $type == 'add' )
		{
			//-----------------------------------------
			// Primary saving
			//-----------------------------------------
			
			$_save['database_field_count']	= 0;
			$_save['database_record_count']	= 0;
			
			$_key	= $this->DB->buildAndFetch( array( 'select' => 'database_id', 'from' => 'ccs_databases', 'where' => "database_key='{$_save['database_key']}'" ) );
			
			if( $_key['database_id'] )
			{
				$this->registry->output->showError( $this->lang->words['database_key_in_use'], '11CCS42' );
			}
			
			$this->DB->insert( 'ccs_databases', $_save );
			
			$id	= $this->DB->getInsertId();
			
			//-----------------------------------------
			// Permission matrix
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
	   		$permissions	= new $classToLoad( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $id, 'databases' );
			
			//-----------------------------------------
			// Create new table
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/' . strtolower($this->settings['sql_driver']) . '.php', 'ccs_database_abstraction', 'ccs' );
			$_dbAbstraction	= new $classToLoad( $this->registry );
			$_dbAbstraction->createTable( $this->settings['sql_tbl_prefix'] . 'ccs_custom_database_' . $id );
			
			$this->DB->update( 'ccs_databases', array( 'database_database' => 'ccs_custom_database_' . $id ), 'database_id=' . $id );
	
			//-----------------------------------------
			// Rebuild cache
			//-----------------------------------------
			
			$this->rebuildCache();

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbadded'], $_save['database_name'] ) );
				
			$this->registry->output->setMessage( $this->lang->words['database_add_success'] );
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=databases&section=fields&id=' . $id );
			//$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=databases&section=databases' );
		}
		else
		{
			if( $_save['database_key'] != $database['database_key'] )
			{
				$_key	= $this->DB->buildAndFetch( array( 'select' => 'database_id', 'from' => 'ccs_databases', 'where' => "database_key='{$_save['database_key']}'" ) );
				
				if( $_key['database_id'] )
				{
					$this->registry->output->showError( $this->lang->words['database_key_in_use'], '11CCS43' );
				}
			}
			
			$this->DB->update( 'ccs_databases', $_save, 'database_id=' . $database['database_id'] );
			
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
	   		$permissions	= new $classToLoad( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $database['database_id'], 'databases' );
			
			//-----------------------------------------
			// Used to store revisions, but no longer do
			//-----------------------------------------
			
			if( !$_save['database_revisions'] AND $database['database_revisions'] )
			{
				$this->DB->delete( 'ccs_database_revisions', 'revision_database_id=' . $database['database_id'] );
			}

			//-----------------------------------------
			// Update fulltext indexes if appropriate
			//-----------------------------------------

			if( $database['database_field_title'] != $_save['database_field_title'] )
			{
				$_field	= $this->DB->buildAndFetch( array( 'select' => 'field_type, field_id', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . intval( str_replace( 'field_', '', $_save['database_field_title'] ) ) ) );

				if( in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) )
				{
					$this->DB->addFulltextIndex( $database['database_database'], $_save['database_field_title'] );
				}

				if( $this->DB->checkForIndex( $database['database_field_title'], $database['database_database'] ) )
				{
					/* Trick driver :x */
					$this->DB->dropField( $database['database_database'], 'INDEX ' . $database['database_field_title'] );
				}
			}

			if( $database['database_field_content'] != $_save['database_field_content'] )
			{
				$_field	= $this->DB->buildAndFetch( array( 'select' => 'field_type, field_id', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . intval( str_replace( 'field_', '', $_save['database_field_title'] ) ) ) );

				if( in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) )
				{
					$this->DB->addFulltextIndex( $database['database_database'], $_save['database_field_content'] );
				}

				if( $this->DB->checkForIndex( $database['database_field_content'], $database['database_database'] ) )
				{
					/* Trick driver :x */
					$this->DB->dropField( $database['database_database'], 'INDEX ' . $database['database_field_content'] );
				}
			}
			
			//-----------------------------------------
			// Rebuild cache
			//-----------------------------------------
			
			$this->rebuildCache();
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbedited'], $_save['database_name'] ) );

			$this->registry->output->setMessage( $this->lang->words['database_edit_success'] );
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}
	
	/**
	 * Form to add/edit a database
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _databaseForm( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['id']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_db_edit'], '11CCS44' );
			}
			
			$database	= $this->DB->buildAndFetch( array(
														'select'	=> 'd.*', 
														'from'		=> array( 'ccs_databases' => 'd' ), 
														'where'		=> 'd.database_id=' . $_id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'p.*',
																				'from'		=> array( 'permission_index' => 'p' ),
																				'where'		=> "p.perm_type='databases' AND p.perm_type_id=d.database_id",
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$database['database_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_db_edit'], '11CCS45' );
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['editing_db_title'] . ' ' . $database['database_name'] );
		}
		else
		{
			$database	= array(
								'database_rss'	=> 20,
								);

			$this->registry->output->extra_nav[] = array( '', $this->lang->words['adding_db_title'] );
		}
		
		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$templates	= array( 1 => array(), 2 => array(), 3 => array() );
		
		$this->DB->build( array( 'select' => 'template_id, template_name, template_database, template_category', 'from' => 'ccs_page_templates', 'where' => 'template_database > 0', 'order' => 'template_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates[ $r['template_database'] ][]	= array( $r['template_id'], $r['template_name'], $r['template_category'] );
		}
		
		$categories	= array();
		
		$this->DB->build( array( 'select' => 'container_id, container_name', 'from' => 'ccs_containers', 'where' => "container_type='{$this->containerType}'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[ $r['container_id'] ]	= $r['container_name'];
		}
		
		//-----------------------------------------
		// Fields
		//-----------------------------------------
		
		$fields		= array();
		$fields[]	= array( 'primary_id_field', $this->lang->words['field__id'] );
		$fields[]	= array( 'member_id', $this->lang->words['field__member'] );
		$fields[]	= array( 'record_saved', $this->lang->words['field__saved'] );
		$fields[]	= array( 'record_updated', $this->lang->words['field__updated'] );
		$fields[]	= array( 'rating_real', $this->lang->words['field__rating'] );
		
		if( $database['database_id'] )
		{
			$this->DB->build( array( 'select' => 'field_id, field_name', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=' . $database['database_id'] , 'order' => 'field_position ASC' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( in_array( $r['field_type'], array( 'checkbox', 'multiselect', 'attachments' ) ) )
				{
					continue;
				}

				$fields[]	= array( 'field_' . $r['field_id'], $r['field_name'] );
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->databaseForm( $type, $database, $templates, $fields, $categories );
	}

	/**
	 * List all of the created databases
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _listDatabases()
	{
		//-----------------------------------------
		// Get all databases
		//-----------------------------------------
		
		$databases	= array();
		
		$this->DB->build( array(
								'select'	=> 'd.*',
								'from'		=> array( 'ccs_databases' => 'd' ),
								'order'		=> 'd.database_name ASC',
								'add_join'	=> array(
													array(
														'select'	=> 'i.*',
														'from'		=> array( 'permission_index' => 'i' ),
														'where'		=> "i.app='ccs' AND i.perm_type='databases' AND i.perm_type_id=d.database_id",
														'type'		=> 'left',
														),
													),
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Don't show articles db here
			//-----------------------------------------
			
			if( $r['database_is_articles'] )
			{
				continue;
			}

			$r['_categories']	= 0;
			$r['_moderators']	= 0;
			
			$databases[ $r['database_id'] ]	= $r;
		}
		
		$_ids	= array_keys($databases);
		
		if( count($_ids) )
		{
			$this->DB->build( array( 'select' => 'COUNT(*) as total, category_database_id', 'from' => 'ccs_database_categories', 'where' => 'category_database_id IN(' . implode( ',', $_ids ) . ')', 'group' => 'category_database_id' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$databases[ $r['category_database_id'] ]['_categories']	= $r['total'];
			}

			$this->DB->build( array( 'select' => 'COUNT(*) as total, moderator_database_id', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id IN(' . implode( ',', $_ids ) . ')', 'group' => 'moderator_database_id' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$databases[ $r['moderator_database_id'] ]['_moderators']	= $r['total'];
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->databases( $databases );
	}
	
	/**
	 * Rebuild cache of databases
	 * Don't automatically use shortcuts, as they won't be set up if makeRegistryShortcuts() wasn't called
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		$databases	= array();
		
		ipsRegistry::DB()->build( array(
										'select'	=> 'd.*', 
										'from'		=> array( 'ccs_databases' => 'd' ), 
										'order'		=> 'd.database_name ASC',
										'add_join'	=> array(
															array(
																'select'	=> 'i.*',
																'from'		=> array( 'permission_index' => 'i' ),
																'where'		=> "perm_type='databases' AND perm_type_id=d.database_id",
																'type'		=> 'left',
																),
															)
							)		);

		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			//-----------------------------------------
			// Don't add RSS caches in cache
			//-----------------------------------------
			
			unset($r['database_rss_cache']);
			
			$databases[ $r['database_id'] ]	= $r;
		}
		
		ipsRegistry::cache()->setCache( 'ccs_databases', $databases, array( 'array' => 1, 'deletefirst' => 1 ) );
		
		ipsRegistry::cache()->rebuildCache( 'rss_output_cache', 'global' );
	}
}
