<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS articles AJAX operations
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th Nov 2009
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_articles extends ipsAjaxCommand
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
	 * HTML library
	 *
	 * @access	public
	 * @var		object
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
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load Language & Skin
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_articles' );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=articles';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=articles';

		//-----------------------------------------
		// Get database
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
			$this->returnJsonError( $this->lang->words['no_db_id_records'] );
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
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'sort':
			default:
				$this->_sortArticles();
			break;
			
			case 'toggle':
				$this->_toggleApproved();
			break;
		}
	}
	
	/**
	 * Toggle approved/hidden status
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _toggleApproved()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->returnJsonError( $this->lang->words['noid_ajax_toggle'] );
		}
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . $id ) );
		
		if( !$record['primary_id_field'] )
		{
			$this->returnJsonError( $this->lang->words['noid_ajax_toggle'] );
		}
		
		//-----------------------------------------
		// Already approved, change to hidden
		//-----------------------------------------
		
		if( $record['record_approved'] == 1 )
		{
			$this->DB->update( $this->database['database_database'], array( 'record_approved' => -1 ), 'primary_id_field=' . $id );

			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count-1", 'database_id=' . $this->database['database_id'], false, true );

			//-----------------------------------------
			// Recache categories
			//-----------------------------------------
			
			if( $record['category_id'] )
			{
				$this->registry->ccsFunctions->getCategoriesClass( $this->database )->recache( $record['category_id'] );
			}

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_articlehidden'], $record[ $this->database['database_title_field'] ] ) );

			$this->returnHtml( $this->html->ajaxRecordHidden() );
		}
		
		//-----------------------------------------
		// Draft, approve it
		//-----------------------------------------
		
		else
		{
			$this->DB->update( $this->database['database_database'], array( 'record_approved' => 1 ), 'primary_id_field=' . $id );

			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count+1", 'database_id=' . $this->database['database_id'], false, true );
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_articleapproved'], $record[ $this->database['database_title_field'] ] ) );

			//-----------------------------------------
			// If this is first time approval, send notifications
			//-----------------------------------------
			
			$_modQueue	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_modqueue', 'where' => "mod_database={$this->database['database_id']} AND mod_record={$id} AND mod_comment=0" ) );
			
			if( $_modQueue['mod_id'] )
			{
		    	//-----------------------------------------
		    	// Post topic if configured to do so
		    	//-----------------------------------------

				$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );
			
				$record['record_approved']	= 1;
				
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
		    	$_topics		= new $classToLoad( $this->registry, null, $_categories );
		    	
		    	if ( $_topics->postTopic( $record, $_categories->categories[ $record['category_id'] ], $this->database ) )
		    	{
					$_member	= IPSMember::load( $record['member_id'], 'core' );
			    	$record		= array_merge( $_member, $record );
	
					$record['poster_name']			= $record['members_display_name'];
	
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
					$databases		= new $classToLoad( $this->registry );
					$databases->sendRecordNotification( $this->database, $_categories->categories[ $record['category_id'] ], $record );
				}

			    $this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$record['primary_id_field']} AND mod_comment=0" );
			}

			//-----------------------------------------
			// Recache categories
			//-----------------------------------------
			
			if( $record['category_id'] )
			{
				$this->registry->ccsFunctions->getCategoriesClass( $this->database )->recache( $record['category_id'] );
			}

			$this->returnHtml( $this->html->ajaxRecordApproved() );
		}
	}
	
	/**
	 * Get real column name
	 *
	 * @access	public
	 * @param	string		Friendly name
	 * @return	string		Database column
	 */
	public function getColumn( $column )
	{
		if( !$column )
		{
			return '';
		}
		
		switch( $column )
		{
			case 'id':
				return 'r.primary_id_field';
			break;
			
			case 'comments':
				return 'r.record_comments';
			break;

			case 'hits':
				return 'r.record_views';
			break;

			case 'status':
				return 'r.record_approved';
			break;

			case 'category':
				return 'c.category_name';
			break;

			case 'title':		// article_title
				foreach( $this->fields as $field )
				{
					if( $field['field_key'] == 'article_title' )
					{
						return 'r.field_' . $field['field_id'];
					}
				}
			break;
			
			case 'date':		// article_date
				foreach( $this->fields as $field )
				{
					if( $field['field_key'] == 'article_date' )
					{
						return 'r.field_' . $field['field_id'];
					}
				}
			break;
		}

		return '';
	}
	
	/**
	 * Get databases
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _sortArticles()
	{
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $this->database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $this->database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $this->database['database_id'] ) );
		}

		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		
		//-----------------------------------------
		// Sorting and limiting
		//-----------------------------------------
		
		$perPage	= intval($this->request['per_page']) ? intval($this->request['per_page']) : $this->database['database_field_perpage'];
		$perPage	= intval($perPage) > 0 ? $perPage : 50;
		$start		= 0;
		
		$_col		= $this->getColumn( $this->request['sortBy'] );
		$_useCol	= $_col ? $_col : 'r.' . $this->database['database_field_sort'];
		$_numeric	= false;

		foreach( $this->fields as $_field )
		{
			if( $_useCol == 'field_' . $_field['field_id'] )
			{
				$_numeric	= $_field['field_is_numeric'];
			}
		}

		$_dir		= ( $this->request['order'] && in_array( $this->request['order'], array( 'asc', 'desc' ) ) ) ? $this->request['order'] : $this->database['database_field_direction'];
		
		$this->request['sort_col']		= $_useCol;
		$this->request['sort_order']	= $_dir;
		$this->request['per_page']		= $perPage;

		//-----------------------------------------
		// Get total
		//-----------------------------------------
		
		$count		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => $this->database['database_database'] ) );
		
		//-----------------------------------------
		// Pagination
		//-----------------------------------------
		
		$_qs		= ( $_useCol != 'r.' . $this->database['database_field_sort'] ? '&amp;sort_col=' . str_replace( array( 'r.', 'c.' ), '', $_useCol ) : '' ) . '&amp;sort_order=' . $_dir . '&amp;per_page=' . $perPage;
		
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $this->settings['base_url'] . $this->form_code . $_qs,
											 				)	);

		//-----------------------------------------
		// Categories for filtering
		//-----------------------------------------
		
		$categories	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database, false );

		//-----------------------------------------
		// Get records
		//-----------------------------------------
		
		$records	= array();
		
		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $this->database['database_database'] => 'r' ), 
								'order'		=> ( $_numeric ? $_useCol . '+0' : $_useCol ) . ' ' . $_dir, 
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
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['category_name']		= $categories->categories[ $r['category_id'] ]['category_name'];
			$r['_revisions']		= 0;

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

		foreach( $records as $row )
		{
			$html	.= $this->html->articleRow( $row, $this->database, $this->fields, $fieldsClass );
		}

		$this->returnJsonArray( array( 'html' => $html, 'pages' => $pages ) );
	}
}