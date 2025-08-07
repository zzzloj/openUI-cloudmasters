<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS front end articles interface.  This library sits on top of databases.php
 * and extends it to customize functionality for the article handling specifically.
 * Last Updated: $Date: 2012-03-13 09:45:42 -0400 (Tue, 13 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		9th February 2010
 * @version		$Revision: 10418 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class articlesBuilder extends databaseBuilder
{
	/**
	 * Category limiter
	 *
	 * @var		int
	 */
	public $categoryLimit	= 0;
	
	/**
	 * Gateway call to get article output
	 *
	 * @access	public
	 * @param	array 		Page data
	 * @return	string		HTML output
	 */
	public function getArticles( $page )
	{
		//-----------------------------------------
		// Load skin file if it hasn't been loaded yet
		//-----------------------------------------
		
		$this->pages->loadSkinFile();
		
		$this->page	= $page;
		
		//-----------------------------------------
		// Get the database info
		//-----------------------------------------
		
		$this->database	= $this->_loadArticlesDatabase();
		
		return $this->getDatabase( '', $page );
	}
	
	/**
	 * Limit the article display to a specified category ID and below.
	 * Can be useful to simulate two article systems.
	 *
	 * @access	public
	 * @param	int			Category ID
	 * @return	@e void
	 */
	public function limitCategory( $categoryId )
	{
		$categoryId	= intval($categoryId);

		if( $categoryId )
		{
			$this->categoryLimit	= $categoryId;
		}
	}

	/**
	 * Get and return database HTML
	 *
	 * @access	public
	 * @param	string		Database key
	 * @param	array 		Page data
	 * @return	string		Database HTML
	 */
	public function getDatabase( $key, $page )
	{
		if( !$this->database['database_id'] )
		{
			return '';
		}
		
		if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
		{
			return '';
		}

		$this->request['database'] = $this->database['database_id'];
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );

		//-----------------------------------------
		// Basic perm checking and init
		//-----------------------------------------
		
		$this->_initDatabase( $page );

		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$this->fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$this->fields	= array();

		if( is_array($this->caches['ccs_fields'][ $this->database['database_id'] ]) AND count($this->caches['ccs_fields'][ $this->database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $this->database['database_id'] ] as $_field )
			{
				$this->fields[ $_field['field_id'] ]	= $_field;
			}
		}

		//-----------------------------------------
		// Get category handler
		//-----------------------------------------
		
		$this->categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );

		if( $this->categoryLimit )
		{
			$this->categories->resetRootCategory( $this->categoryLimit );
		}
		
		//-----------------------------------------
		// Fix database furls... sigh :(
		//-----------------------------------------
		
		$this->fixDatabaseFurls();

		//-----------------------------------------
		// What action to take
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'add':
				return $this->_actionAdd();
			break;
			
			case 'save':
				$_result	= $this->_actionSaveAdd( true );
				
				//-----------------------------------------
				// Saved successfully - make sure it's published
				//-----------------------------------------
				
				if( is_array($_result) AND count($_result) )
				{
					$specialFields	= $this->_getSpecialFields();

					if( $_result[2][ $specialFields['date'] ] > time() )
					{
						if( ( $this->memberData['member_id'] AND $_result[2]['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
						{
							$_result[0]	= $this->lang->words['record_saved_success2'];
						}
						else if( $_result[2]['category_id'] )
						{
							$this->registry->output->redirectScreen( $this->lang->words['record_saved_success2'], $this->categories->getCategoryUrl( $this->database['base_link'], $_result[2]['category_id'] ) );
						}
						else
						{
							$this->registry->output->redirectScreen( $this->lang->words['record_saved_success2'], $this->database['base_link'] );
						}
					}
					
					$this->registry->output->redirectScreen( $_result[0], $_result[1] );
				}
				
				return $_result;
			break;
			
			case 'edit':
				return $this->_actionEdit();
			break;
			
			case 'save_edit':
				$_result	= $this->_actionSaveEdit( true );
				
				//-----------------------------------------
				// Saved successfully - make sure it's published
				//-----------------------------------------
				
				if( is_array($_result) AND count($_result) )
				{
					$specialFields	= $this->_getSpecialFields();

					if( $_result[2][ $specialFields['date'] ] > time() )
					{
						if( ( $this->memberData['member_id'] AND $_result[2]['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
						{
							$_result[0]	= $this->lang->words['record_edited_success1'];
						}
						else if( $_result[2]['category_id'] )
						{
							$this->registry->output->redirectScreen( $this->lang->words['record_edited_success1'], $this->categories->getCategoryUrl( $this->database['base_link'], $_result[2]['category_id'] ) );
						}
						else
						{
							$this->registry->output->redirectScreen( $this->lang->words['record_edited_success1'], $this->database['base_link'] );
						}
					}
					
					$this->registry->output->redirectScreen( $_result[0], $_result[1] );
				}
				
				return $_result;
			break;

			case 'delete':
				return $this->_actionDelete();
			break;

			case 'lock':
			case 'unlock':
				return $this->_actionToggleLock();
			break;
			
			case 'pin':
			case 'unpin':
				return $this->_actionPin();
			break;
			
			case 'approve':
			case 'unapprove':
				return $this->_actionApprove();
			break;

			case 'revisions':
				return $this->_showRevisions();
			break;

			case 'deleteRevision':
				return $this->_deleteRevision();
			break;
			
			case 'restoreRevision':
				return $this->_restoreRevision();
			break;
			
			case 'editRevision':
				return $this->_editRevision();
			break;
			
			case 'doEditRevision':
				return $this->_saveRevision();
			break;
			
			case 'compareRevision':
				return $this->_compareRevisions();
			break;

			case 'search':
				$this->_loadTemplates( 'listing' );

				return $this->_getDatabaseListing();
			break;
		}

		//-----------------------------------------
		// If none of the above, view cats, listing or record?
		//-----------------------------------------

		$_content	= '';

		if( $this->request['record'] )
		{
			$_content	.= $this->_getRecordDisplay();
		}
		else if( $this->request['category'] AND $this->request['view'] == 'archive' )
		{
			$this->_loadTemplates( 'listing' );

			$_content	.= $this->_getDatabaseListing();
		}
		else if( $this->request['category'] )
		{
			$_content	.= $this->_getCategoryFrontpage();
		}
		else if( $this->request['view'] == 'categories' )
		{
			$this->_loadTemplates( 'categories' );
			
			$_content	.= $this->_getDatabaseCategories();
		}
		else
		{
			$this->_loadTemplates();
			
			$_content	.= $this->_getDatabaseFrontpage();
		}

		return $_content;
	}
	
	/**
	 * Get listing of categories
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getDatabaseCategories()
	{
		//-----------------------------------------
		// Fudge it to get teaser to show up
		//-----------------------------------------
		
		$categories	= $this->categories->catcache[0];

		if( count($categories) )
		{
			$special	= $this->_getSpecialFields();

			$this->database['database_field_title']	= $special['teaser'];
		}

		$_result	= parent::_getDatabaseCategories();
		
		//-----------------------------------------
		// Navigation + page title
		//-----------------------------------------

		$crumbies	= array(
							array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ),
							array( 0 => $this->lang->words['category_listing'], 1 => '' ),
							);

		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );

		$this->_setPageTitle( array( 'database_name' => sprintf( $this->lang->words['category_listing_pt'], $this->database['database_name'] ) ), 4 );

		$this->pages->setCanonical( $this->database['base_link'] . 'view=categories' );
		
		return $_result;
	}
	
	/**
	 * Get listing of categories
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getDatabaseListing()
	{
		//-----------------------------------------
		// Do we have children?
		//-----------------------------------------
		
		$category					= $this->categories->getCategory( $this->request['category'] );
		$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'], true ) . 'view=archive';
		
		$this->checkPermalink( $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] ) );
		
		$this->pages->setMetaKeywords( $category['category_meta_keywords'] );
		$this->pages->setMetaDescription( $category['category_meta_description'] );
		$this->pages->setCanonical( $category['category_link'] );

		if( $this->request['category'] AND !$category['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_view_perm_cat'], '10CCS18', false, null, 403 );
		}
		else if( $this->request['category'] )
		{
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) != TRUE )
			{
				$this->database['_can_add']	= false;
			}
			else if( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) == TRUE )
			{
				$this->database['_can_add']	= true;
			}
		}
		
		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'catID' => $category['category_id'], 'itemLastUpdate' => $category['category_last_record_date'], 'databaseID' => $category['category_database_id'] ), 'ccs' );

		$category['_has_unread'] = ( $category['category_last_record_date'] && $category['category_last_record_date'] > $rtime ) ? 1 : 0;

		$categories	= $this->categories->catcache[ $this->request['category'] ];
		
		if( count($categories) )
		{
			foreach( $categories as $id => $data )
			{
				$categories[ $id ]					= $this->categories->getCategory( $id );
				$categories[ $id ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $id );
				$_children							= $this->categories->getChildren( $id );
				$categories[ $id ]['children']		= array();
				
				if( count($_children) )
				{
					foreach( $_children as $_child )
					{
						$categories[ $id ]['children'][]							= $this->categories->getCategory( $_child );
						$categories[ $id ]['children'][ $_child ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $_child );
					}
				}
				
				//-----------------------------------------
				// Latest record display
				//-----------------------------------------

				if( in_array( $this->database['database_field_title'], array( 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'rating_real' ) ) )
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= ( $this->database['database_field_title'] == 'record_saved' OR $this->database['database_field_title'] == 'record_updated' ) ? $this->registry->class_localization->getDate( $categories[ $id ][ $this->database['database_field_title'] ], 'LONG' ) : $categories[ $id ][ $this->database['database_field_title'] ];
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				else
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $categories[ $id ], $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				
				if( in_array( $this->database['database_field_content'], array( 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'rating_real' ) ) )
				{
					$categories[ $id ][ $this->database['database_field_content'] . '_value' ]	= ( $this->database['database_field_content'] == 'record_saved' OR $this->database['database_field_content'] == 'record_updated' ) ? $this->registry->class_localization->getDate( $categories[ $id ][ $this->database['database_field_content'] ], 'LONG' ) : $categories[ $id ][ $this->database['database_field_content'] ];
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_content'] . '_value' ];
				}
				else
				{
					$categories[ $id ][ $this->database['database_field_content'] . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ], $categories[ $id ], $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_truncate'] );
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_content'] . '_value' ];
				}
				
				$categories[ $id ]['last_author'] = IPSMember::buildDisplayData( $categories[ $id ]['member_id'] ? $categories[ $id ]['member_id'] : IPSMember::setUpGuest() );

				$categories[ $id ]['record_link']	= $this->getRecordUrl( array( 'primary_id_field' => $categories[ $id ]['category_last_record_id'], 'category_id' => $categories[ $id ]['category_last_record_cat'] ? $categories[ $id ]['category_last_record_cat'] : $id, $this->database['database_field_title'] . '_value' => $categories[ $id ][ $this->database['database_field_title'] . '_value' ] ) );
			}
		}

		//-----------------------------------------
		// Breadcrumbs
		//-----------------------------------------

		if( count($categories) OR $this->request['category'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $this->request['category'], $this->database['base_link'] );
	
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}
		else if( !$this->request['category'] )
		{
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				$crumbies	= array( array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				$crumbies	= array( array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}

		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );

		if( $category['category_name'] )
		{
			$this->_setPageTitle( array( 'category_name' => $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'] ), 2 );
		}
		else
		{
			$this->_setPageTitle( array(), 1 );
		}

		//-----------------------------------------
		// Using forums?
		//-----------------------------------------

		$_useForums	= false;

		if( $category['category_forum_override'] )
		{
			if( $category['category_forum_record'] )
			{
				$_useForums	= true;
			}
		}
		else
		{
			if( $this->database['database_forum_record'] )
			{
				$_useForums	= true;
			}
		}

		//-----------------------------------------
		// Showing records?
		//-----------------------------------------
		
		$_show	= true;
		
		if( $category['category_id'] )
		{
			if( !$category['category_show_records'] )
			{
				$_show	= false;
			}
		}

		$records	= array();
		$pages		= '';
		
		if( $_show )
		{
			//-----------------------------------------
			// Sorting and limiting
			//-----------------------------------------
			
			$perPage	= intval($this->request['per_page']) ? intval($this->request['per_page']) : $this->database['database_field_perpage'];
			$perPage	= intval($perPage) > 0 ? $perPage : 25;
			$start		= intval($this->request['st']) ? intval($this->request['st']) : 0;
			
			$_col		= $this->request['sort_col'];
			$_useCol	= $this->database['database_field_sort'];
			$_numeric	= false;
	
			foreach( $this->fields as $_field )
			{
				if( $_col == 'field_' . $_field['field_id'] )
				{
					$_useCol	= $_col;
					$_numeric	= $_field['field_is_numeric'];
				}
			}
			
			if( in_array( $_col, array( 'rating_real', 'record_views', 'primary_id_field', 'member_id', 'record_saved', 'record_updated' ) ) )
			{
				$_useCol	= $_col;
			}
	
			$_dir		= ( $this->request['sort_order'] && in_array( $this->request['sort_order'], array( 'asc', 'desc' ) ) ) ? $this->request['sort_order'] : $this->database['database_field_direction'];
			
			$this->request['sort_col']		= $_useCol;
			$this->request['sort_order']	= $_dir;
			$this->request['per_page']		= $perPage;
		
			//-----------------------------------------
			// Search
			//-----------------------------------------
	
			$_where	= '';
			
			if( $this->request['search_value'] )
			{
				$_where	= $this->fieldsClass->getSearchWhere( $this->fields, $this->request['search_value'] );
			}
			
			if( $category['category_id'] )
			{
				$_catIds	= array( $this->request['category'] );
				foreach( $this->categories->categories as $_catId => $_cat )
				{
					if ( in_array( $_cat['category_parent_id'], $_catIds ) )
					{
						$_catIds[] = $_catId;
					}
				}
				
				$_where		= $_where ? "category_id IN(" . implode( ',', $_catIds ) . ") AND (" . $_where . ")" : "category_id IN(" . implode( ',', $_catIds ) . ")";
			}
			else
			{
				if( count($this->categories->categories) )
				{
					$_catIds	= array();
					
					foreach( $this->categories->categories as $_catId => $_cat )
					{
						$_catIds[]	= $_catId;
					}
					
					$_where		= $_where ? "category_id IN(" . implode( ',', $_catIds ) . ") AND (" . $_where . ")" : "category_id IN(" . implode( ',', $_catIds ) . ")";
				}
			}
			
			//-----------------------------------------
			// Only show approved records if we can't mod
			//-----------------------------------------
			
			$_approval	= array( 1 );

			if( $this->database['moderate_approve'] )
			{
				$_approval[]	= 0;
			}

			if( $this->database['moderate_delete'] )
			{
				$_approval[]	= -1;
			}

			if( $_where )
			{
				$_where	= "record_approved IN(" . implode( ',', $_approval ) . ") AND " . $_where;
			}
			else
			{
				$_where	= "record_approved IN(" . implode( ',', $_approval ) . ")";
			}
			
			foreach( $this->fields as $_field )
			{
				if( $_field['field_key'] == 'article_date' AND !$this->database['moderate_edit'] )
				{
					if( $this->memberData['member_id'] )
					{
						$_where = ( $_where ? $_where . " AND (field_{$_field['field_id']}+0 <= " . time() : "(field_{$_field['field_id']}+0 <= " . time() ) . " OR member_id={$this->memberData['member_id']})";
					}
					else
					{
						$_where = $_where ? $_where . " AND (field_{$_field['field_id']}+0 <= " . time() . ")" : "field_{$_field['field_id']}+0 <= " . time();
					}
					break;
				}
			}

			//-----------------------------------------
			// Only queued?
			//-----------------------------------------

			if( $this->database['moderate_approve'] AND $this->request['sort'] == 'queued' )
			{
				$_where .= " AND record_approved=0";
			}
			else if( $this->database['moderate_approvec'] AND $this->request['sort'] == 'queuedc' )
			{
				$_where .= " AND record_comments_queued > 0";
			}
			
			$count		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => $this->database['database_database'], 'where' => $_where ) );
			
			//-----------------------------------------
			// Query string
			//-----------------------------------------
			
			$baseUrl	= $this->request['category'] ? $this->categories->getCategoryUrl( $this->database['base_link'], $this->request['category'], true ) : $this->database['base_link'];
			$_qs		= 'view=archive&amp;search_value=' . urlencode($this->request['search_value']) . '&amp;sort_col=' . $_useCol . '&amp;sort_order=' . $_dir . '&amp;per_page=' . $perPage;
			
			if( $this->request['do'] == 'search' )
			{
				$_qs	= "do=search&amp;" . $_qs;
			}

			if( $this->request['sort'] )
			{
				$_qs	.= "&amp;sort=" . $this->request['sort'];
			}
			
			//-----------------------------------------
			// Page links
			//-----------------------------------------
			
			$pages		= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $baseUrl . $_qs,
																	'base'				=> 'none',
											 				)	);

			//-----------------------------------------
			// Count for search results
			//-----------------------------------------
			
			if( $this->request['search_value'] )
			{
				$this->database['keyword']		= $this->request['search_value'];
				$this->database['result_cnt']	= $count['total'];
			}
			
			//-----------------------------------------
			// Set final sorting
			//-----------------------------------------
			
			$_finalSort	= 'record_pinned DESC, ' . ( $_numeric ? $_useCol . '+0' : $_useCol ) . ' ' . $_dir;

			//-----------------------------------------
			// Records
			//-----------------------------------------
			
			$_memberIds			= array();
			$recordIds			= array();
			$sendNotifications	= array();

			$this->DB->build( array(
									'select'	=> 'r.*',
									'from'		=> array( $this->database['database_database'] => 'r' ),
									'order'		=> $_finalSort,
									'where'		=> $_where,
									'limit'		=> array( $start, $perPage ),
									'add_join'	=> array( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) ) ),
							)		);
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$_category			= $this->categories->getCategory( $r['category_id'] );

				$r['_isRead']		= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'databaseID' => $this->database['database_id'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
				$r['_iPosted']		= ( $r['member_id'] == $this->memberData['member_id'] ) ? 1 : 0;
				$r['record_link']	= $this->getRecordUrl( $r );
				$r['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $r['category_id'], true ) . 'view=archive';
				$r['category_name']	= $_category['category_name'];

				if ( ! empty( $r['tag_cache_key'] ) )
				{
					if( $_category['category_id'] AND $_category['category_tags_override'] )
					{
						if( $_category['category_tags_enabled'] )
						{
							$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
						}
					}
					else
					{
						if( $this->database['database_tags_enabled'] )
						{
							$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
						}
					}
				}
	
				$records[ $r['primary_id_field'] ]		= $r;
				$memberIds[ $r['member_id'] ]			= $r['member_id'];
				$recordIds[ $r['primary_id_field'] ]	= $_useForums ? $r['record_topicid'] : $r['primary_id_field'];
				
				//-----------------------------------------
				// Do we need to post topic...?
				//-----------------------------------------
				
				if( !$r['record_topicid'] AND ( $category['category_forum_record'] OR $this->database['database_forum_record'] ) )
				{
					//-----------------------------------------
					// Post topic if configured to do so
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
					$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
					
					if ( $_topics->postTopic( $r, $category, $this->database ) )
					{
						$sendNotifications[]	= $r;
					}
				}
			}
			
			if( count($memberIds) )
			{
				$_members	= IPSMember::load( $memberIds );
				$_mems		= array();
				
				foreach( $_members as $k => $v )
				{
					foreach( $v as $_k => $_v )
					{
						if( strpos( $_k, 'field_' ) === 0 )
						{
							$_k	= str_replace( 'field_', 'user_field_', $_k );
						}
						
						$_mems[ $k ][ $_k ]	= $_v;
					}
				}
				
				$_members	= $_mems;
				
				foreach( $records as $key => $record )
				{
					$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['member_id'] ] ? $_members[ $record['member_id'] ] : IPSMember::setUpGuest() ) );
				}
			}

			foreach( $records as $key => $record )
			{
				foreach( $record as $k => $v )
				{
					if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
					{
						$records[ $key ][ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $record, $this->fields[ $matches[1] ]['field_truncate'] );
						$records[ $key ][ $this->fields[ $matches[1] ]['field_key'] ]	= $records[ $key ][ $k . '_value' ];
					}
				}
			}

			//-----------------------------------------
			// Are we dotty?
			//-----------------------------------------
			
			if( $this->settings['show_user_posted'] and $this->memberData['member_id'] and count($recordIds) )
			{
				if( $_useForums )
				{
					$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );

					$this->DB->build( array( 'select' => 'author_id, topic_id',
											 'from'   => 'posts',
											 'where'  => $_queued . ' AND author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $recordIds ) . ')' )	);
					$this->DB->execute();
					
					while( $p = $this->DB->fetch() )
					{
						foreach( $records as $k => $v )
						{
							if( $v['record_topicid'] == $p['topic_id'] )
							{
								$records[ $k ]['_iPosted'] = 1;
								break;
							}
						}
					}
				}
				else
				{
					$this->DB->build( array( 'select' => 'comment_user, comment_record_id',
											 'from'   => 'ccs_database_comments',
											 'where'  => 'comment_approved=1 AND comment_user=' . $this->memberData['member_id'] . ' AND comment_database_id=' . $this->database['database_id'] . ' AND comment_record_id IN(' . implode( ',', $recordIds ) . ')' )	);
					$this->DB->execute();
					
					while( $p = $this->DB->fetch() )
					{
						if ( is_array( $records[ $p['comment_record_id'] ] ) )
						{
							$records[ $p['comment_record_id'] ]['_iPosted'] = 1;
						}
					}
				}
			}

			//-----------------------------------------
			// Send any topic notifications if needed
			//-----------------------------------------
			
			if( count($sendNotifications) )
			{
				foreach( $sendNotifications as $_recordSend )
				{
					$this->sendRecordNotification( $this->database, $category, array_merge( $_recordSend, array( 'poster_name' => $_members[ $r['member_id'] ]['members_display_name'], 'poster_seo_name' => $_members[ $r['member_id'] ]['members_seo_name'] ) ) );
				}
			}
		}
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		
		foreach( $this->fields as $field )
		{
			if( $field['field_display_listing'] )
			{
				$showFields[]	= $field;
			}
		}
		
		$specialFields	= $this->_getSpecialFields();
		
		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'records' => $records, 'pages' => $pages, 'categories' => $categories, 'parent' => $category, 'show' => $_show, 'special' => $specialFields ) );
		
		return $_content;
	}
	
	/**
	 * Get the frontpage for the articles
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getDatabaseFrontpage()
	{
		$this->checkPermalink( $this->database['base_link'] );
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cache		= $this->registry->ccsFunctions->returnFrontpageCache();
		$_orderBy	= $cache['sort'];
		$_direction	= $cache['order'];
		$_limit		= array( 0, $cache['limit'] );
		$_pageNo	= 0;

		if( $this->request['p'] AND $this->request['p'] > 1 AND $cache['paginate'] )
		{
			$_pageNo	= intval($this->request['p']) - 1;
			$_limit		= array( $_pageNo * $cache['limit'], $cache['limit'] );
		}

		$_numeric	= false;
		
		$_approval	= array( 1 );

		if( $this->database['moderate_approve'] )
		{
			$_approval[]	= 0;
		}

		if( $this->database['moderate_delete'] )
		{
			$_approval[]	= -1;
		}

		$_awhere	= array( 'record_approved IN(' . implode( ',', $_approval ) . ')' );
		
		$_where		= '';

		foreach( $this->fields as $_field )
		{
			if( $_orderBy == 'field_' . $_field['field_id'] )
			{
				$_numeric	= $_field['field_is_numeric'];
			}
			
			if( $_field['field_key'] == 'article_homepage' )
			{
				$_awhere[]	= "field_{$_field['field_id']}=',1,'";
			}

			if( $_field['field_key'] == 'article_date' AND !$this->database['moderate_edit'] )
			{
				if( $this->memberData['member_id'] )
				{
					$_awhere[]	= "( field_{$_field['field_id']}+0 <= " . time() . " OR member_id=" . $this->memberData['member_id'] . ")";
				}
				else
				{
					$_awhere[]	= "field_{$_field['field_id']}+0 <= " . time();
				}
			}
		}
		
		$_where			= implode( " AND ", $_awhere );
		$_finalOrder	= ( $_numeric ? $_orderBy . '+0' : $_orderBy ) . ' ' . $_direction;
		
		if( $cache['pinned'] )
		{
			$_finalOrder	= "record_pinned DESC, " . $_finalOrder;
		}
		
		//-----------------------------------------
		// Restrict to categories we can view
		//-----------------------------------------
		
		if( count($this->categories->categories) )
		{
			$_catIds	= array();
			
			foreach( $this->categories->categories as $_catId => $_cat )
			{
				$_catIds[]	= $_catId;
			}
						
			$_where		= $_where ? "category_id IN(" . implode( ',', $_catIds ) . ") AND (" . $_where . ")" : "category_id IN(" . implode( ',', $_catIds ) . ")";
		}
		
		//-----------------------------------------
		// Get count for pagination, if enabled
		//-----------------------------------------
		
		if( $cache['paginate'] )
		{
			$_count	= $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as total", 'from' => $this->database['database_database'], 'where' => $_where ) );
			
			$this->database['_fp_count']	= $_count['total'];
		}

		$records			= array();
		$recordIds			= array();
		$_memberIds			= array();
		$sendNotifications	= array();

		//-----------------------------------------
		// Retrieve records
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $this->database['database_database'] => 'r' ),
								'order'		=> $_finalOrder,
								'where'		=> $_where,
								'limit'		=> $_limit,
								'add_join'	=> array( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) ) ),
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			foreach( $r as $k => $v )
			{
				if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					$r[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $r, $this->fields[ $matches[1] ]['field_truncate'] );
					$r[ $this->fields[ $matches[1] ]['field_key'] ]	= $r[ $k . '_value' ];
				}
			}

			$_category	= $this->categories->categories[ $r['category_id'] ];
			
			if ( $_category['category_has_perms'] AND $this->registry->permissions->check( 'show', $_category ) != TRUE )
			{
				$r[ $this->database['database_field_content'] . '_value' ]	= $this->lang->words['nosearchpermview'];
			}
			else if( $this->database['perm_2'] != '*' )
			{
				if ( $this->registry->permissions->check( 'show', $this->database ) != TRUE )
				{
					$r[ $this->database['database_field_content'] . '_value' ]	= $this->lang->words['nosearchpermview'];
					$r[ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $r[ $this->database['database_field_content'] . '_value' ];
				}
			}
			
			$r['category_name']	= $_category['category_name'];
			$r['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $r['category_id'] );
			
			$r['_isRead']		= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'databaseID' => $this->database['database_id'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
			$r['record_link']	= $this->getRecordUrl( $r );

			if ( ! empty( $r['tag_cache_key'] ) )
			{
				if( $_category['category_id'] AND $_category['category_tags_override'] )
				{
					if( $_category['category_tags_enabled'] )
					{
						$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
					}
				}
				else
				{
					if( $this->database['database_tags_enabled'] )
					{
						$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
					}
				}
			}

			$records[]						= $r;
			$recordIds[]					= $r['primary_id_field'];
			$memberIds[ $r['member_id'] ]	= $r['member_id'];
			
			//-----------------------------------------
			// Do we need to post topic...?
			//-----------------------------------------
			
			if( !$r['record_topicid'] AND $r['record_approved'] == 1 AND $r['article_date'] > time() AND ( $this->categories->categories[ $r['category_id'] ]['category_forum_record'] OR $this->database['database_forum_record'] ) )
			{
				//-----------------------------------------
				// Post topic if configured to do so
				//-----------------------------------------
				
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
				
				//-----------------------------------------
				// Post topic & send notifications if all goes fine
				//-----------------------------------------

				if ( $_topics->postTopic( $r, $this->categories->categories[ $r['category_id'] ], $this->database ) )
				{
					$sendNotifications[] = $r;
				}
			}
		}
		
		//-----------------------------------------
		// Get attachment ids for all records in one
		//-----------------------------------------
		
		$_hasAttachments	= false;
		
		foreach( $this->fields as $k => $v )
		{
			if( $v['field_type'] == 'attachments' )
			{
				$_hasAttachments	= true;
				break;
			}
		}

		if( $_hasAttachments AND count($recordIds) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_attachments_map', 'where' => "map_database_id={$this->database['database_id']} AND map_record_id IN(" . implode( ',', $recordIds ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$this->caches['ccs_attachments_data'][ $r['map_record_id'] ][ $r['map_id'] ]	= $r;
			}

			foreach( $recordIds as $recordId )
			{
				if( !isset($this->caches['ccs_attachments_data'][ $recordId ]) )
				{
					$this->caches['ccs_attachments_data'][ $recordId ]	= array();
				}
			}
		}

		//-----------------------------------------
		// Get the author data too
		//-----------------------------------------
		
		if( count($memberIds) )
		{
			$_members	= IPSMember::load( $memberIds );
			$_mems		= array();
			
			foreach( $_members as $k => $v )
			{
				foreach( $v as $_k => $_v )
				{
					if( strpos( $_k, 'field_' ) === 0 )
					{
						$_k	= str_replace( 'field_', 'user_field_', $_k );
					}
					
					$_mems[ $k ][ $_k ]	= $_v;
				}
			}
			
			$_members	= $_mems;
			
			foreach( $records as $key => $record )
			{
				$record				= $this->parseAttachments( $this->database, $this->fields, $record );
				$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['member_id'] ] ? $_members[ $record['member_id'] ] : IPSMember::setUpGuest() ) );
			}
			
			//-----------------------------------------
			// Send any topic notifications if needed
			//-----------------------------------------
			
			if( count($sendNotifications) )
			{
				foreach( $sendNotifications as $_recordSend )
				{
					$this->sendRecordNotification( $this->database, $this->categories->categories[ $_recordSend['category_id'] ], array_merge( $_recordSend, array( 'poster_name' => $_members[ $_recordSend['member_id'] ]['members_display_name'], 'poster_seo_name' => $_members[ $_recordSend['member_id'] ]['members_seo_name'] ) ) );
				}
			}
		}
		
		$specialFields	= $this->_getSpecialFields();
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		
		foreach( $this->fields as $field )
		{
			if( $field['field_display_display'] )
			{
				$showFields[]	= $field;
			}
		}
			
		//-----------------------------------------
		// Output
		//-----------------------------------------

		if( $this->categoryLimit )
		{
			$categoryTitle	= $this->categories->categories[ $this->categoryLimit ]['category_page_title'] ? $this->categories->categories[ $this->categoryLimit ]['category_page_title'] : $this->categories->categories[ $this->categoryLimit ]['category_name'];
			$this->_setPageTitle( array( 'category_name' => $_pageNo ? sprintf( $this->lang->words['dbname_page_title'], $categoryTitle, $_pageNo + 1 ) : $categoryTitle, 'database_name' => $this->database['database_name'] ), 2 );
		}
		else
		{
			$this->_setPageTitle( array( 'database_name' => $_pageNo ? sprintf( $this->lang->words['dbname_page_title'], $this->database['database_name'], $_pageNo + 1 ) : $this->database['database_name'] ), 1 );
		}
		
		$this->pages->setCanonical( rtrim( $this->database['base_link'], '?' ) );
		
		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'records' => $records, 'special' => $specialFields ) );
		
		return $_content;
	}
	
	/**
	 * Get the frontpage for the category
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getCategoryFrontpage()
	{
		//-----------------------------------------
		// Category data
		//-----------------------------------------
		
		$category					= $this->categories->getCategory( $this->request['category'] );
		$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] );
		
		$this->checkPermalink( $category['category_link'] );
		
		$this->pages->setMetaKeywords( $category['category_meta_keywords'] );
		$this->pages->setMetaDescription( $category['category_meta_description'] );
		
		$canonical = $category['category_link'];
		
		if ( $this->request['p'] )
		{
			$canonical .= !$this->settings['use_friendly_urls'] ? "&amp;p={$this->request['p']}" : "?p={$this->request['p']}";
		}
		
		$this->pages->setCanonical( $canonical );

		if( $this->request['category'] AND !$category['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_view_perm_cat'], '10CCS19', false, null, 403 );
		}
		else if( $this->request['category'] )
		{
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) != TRUE )
			{
				$this->database['_can_add']	= false;
			}
			else if( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) == TRUE )
			{
				$this->database['_can_add']	= true;
			}
		}

		$this->_loadTemplates( 'category_frontpage', $category['category_template'] );

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cache			= $this->registry->ccsFunctions->returnFrontpageCache();
		$_orderBy		= $cache['sort'];
		$_direction		= $cache['order'];
		$_limit			= array( 0, $cache['limit'] );
		
		if( $this->request['p'] AND $this->request['p'] > 1 AND $cache['paginate'] )
		{
			$_pageNo	= intval($this->request['p']) - 1;
			$_limit		= array( $_pageNo * $cache['limit'], $cache['limit'] );
		}

		$_numeric		= false;
		$specialFields	= $this->_getSpecialFields();
		
		if( !$cache['exclude_subcats'] )
		{
			$_subCats	= $this->categories->getChildren( $category['category_id'] );
			$_subCats[]	= $category['category_id'];
			
			$_where		= "category_id IN(" . implode( ',', $_subCats ) . ")";
		}
		else
		{
			$_where		= 'category_id=' . $category['category_id'];
		}
		
		//-----------------------------------------
		// Limit by expiry date
		// category_id IN(9,10,8) AND (field_33=0 OR field_33 > 1266424982)
		//-----------------------------------------
		
		if( $specialFields['expiry'] )
		{
			$_where	.= " AND ({$specialFields['expiry']}=0 OR {$specialFields['expiry']} > " . time() . ")";
		}

		foreach( $this->fields as $_field )
		{
			if( $_orderBy == 'field_' . $_field['field_id'] )
			{
				$_numeric	= $_field['field_is_numeric'];
			}
			
			if( $_field['field_key'] == 'article_date' AND !$this->database['moderate_edit'] )
			{
				if( $this->memberData['member_id'] )
				{
					$_where	.= " AND ( field_{$_field['field_id']}+0 <= " . time() . " OR member_id=" . $this->memberData['member_id'] . ")";
				}
				else
				{
					$_where	.= " AND field_{$_field['field_id']}+0 <= " . time();
				}
			}
		}

		$_approval	= array( 1 );

		if( $this->database['moderate_approve'] )
		{
			$_approval[]	= 0;
		}

		if( $this->database['moderate_delete'] )
		{
			$_approval[]	= -1;
		}

		$_where	.= " AND record_approved IN(" . implode( ',', $_approval ) . ")";
		
		$_finalOrder	= ( $_numeric ? $_orderBy . '+0' : $_orderBy ) . ' ' . $_direction;
		
		if( $cache['pinned'] )
		{
			$_finalOrder	= "record_pinned DESC, " . $_finalOrder;
		}

		$records			= array();
		$_memberIds			= array();
		$sendNotifications	= array();

		//-----------------------------------------
		// Retrieve records
		//-----------------------------------------

		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $this->database['database_database'] => 'r' ),
								'order'		=> $_finalOrder,
								'where'		=> $_where,
								'limit'		=> $_limit,
								'add_join'	=> array( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) ) ),
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			foreach( $r as $k => $v )
			{
				if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					$r[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $r, $this->fields[ $matches[1] ]['field_truncate'] );
					$r[ $this->fields[ $matches[1] ]['field_key'] ]	= $r[ $k . '_value' ];
				}
			}
			
			$r	= $this->parseAttachments( $this->database, $this->fields, $r );
			
			if ( $this->categories->categories[ $r['category_id'] ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $r['category_id'] ] ) != TRUE )
			{
				$r[ $this->database['database_field_content'] . '_value' ]	= $this->lang->words['nosearchpermview'];
			}
			else if( $this->database['perm_2'] != '*' )
			{
				if ( $this->registry->permissions->check( 'show', $this->database ) != TRUE )
				{
					$r[ $this->database['database_field_content'] . '_value' ]	= $this->lang->words['nosearchpermview'];
					$r[ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $r[ $this->database['database_field_content'] . '_value' ];
				}
			}
			
			$r['category_name']	= $this->categories->categories[ $r['category_id'] ]['category_name'];
			$r['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $r['category_id'] );
			
			$r['_isRead']		= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'databaseID' => $this->database['database_id'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
			$r['record_link']	= $this->getRecordUrl( $r );
			
			if ( ! empty( $r['tag_cache_key'] ) )
			{
				if( $category['category_id'] AND $category['category_tags_override'] )
				{
					if( $category['category_tags_enabled'] )
					{
						$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
					}
				}
				else
				{
					if( $this->database['database_tags_enabled'] )
					{
						$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
					}
				}
			}

			$records[]						= $r;
			$memberIds[ $r['member_id'] ]	= $r['member_id'];
			
			//-----------------------------------------
			// Do we need to post topic...?
			//-----------------------------------------
			
			if( !$r['record_topicid'] AND $r['record_approved'] == 1 AND $r['article_date'] > time() AND ( $this->categories->categories[ $r['category_id'] ]['category_forum_record'] OR $this->database['database_forum_record'] ) )
			{
				//-----------------------------------------
				// Post topic if configured to do so
				//-----------------------------------------
				
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
				
				if ( $_topics->postTopic( $r, $this->categories->categories[ $r['category_id'] ], $this->database ) )
				{
					$sendNotifications[]	= $r;
				}
			}
		}

		//-----------------------------------------
		// Get the author data too
		//-----------------------------------------
		
		if( count($memberIds) )
		{
			$_members	= IPSMember::load( $memberIds );
			$_mems		= array();
			
			foreach( $_members as $k => $v )
			{
				foreach( $v as $_k => $_v )
				{
					if( strpos( $_k, 'field_' ) === 0 )
					{
						$_k	= str_replace( 'field_', 'user_field_', $_k );
					}
					
					$_mems[ $k ][ $_k ]	= $_v;
				}
			}
			
			$_members	= $_mems;
			
			foreach( $records as $key => $record )
			{
				$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['member_id'] ] ? $_members[ $record['member_id'] ] : IPSMember::setUpGuest() ) );
			}
			
			//-----------------------------------------
			// Send any topic notifications if needed
			//-----------------------------------------
			
			if( count($sendNotifications) )
			{
				foreach( $sendNotifications as $_recordSend )
				{
					$this->sendRecordNotification( $this->database, $this->categories->categories[ $_recordSend['category_id'] ], array_merge( $_recordSend, array( 'poster_name' => $_members[ $_recordSend['member_id'] ]['members_display_name'], 'poster_seo_name' => $_members[ $_recordSend['member_id'] ]['members_seo_name'] ) ) );
				}
			}
		}

		//-----------------------------------------
		// Other schtuff (breadcrumbs/title)
		//-----------------------------------------

		if( $this->request['category'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $this->request['category'], $this->database['base_link'] );
	
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}
		else
		{
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				$crumbies	= array( array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				$crumbies	= array( array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}

		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );

		if( $category['category_name'] )
		{
			$this->_setPageTitle( array( 'category_name' => $_pageNo ? sprintf( $this->lang->words['dbname_page_title'], $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'], $_pageNo + 1 ) : ( $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'] ), 'database_name' => $this->database['database_name'] ), 2 );
		}
		else
		{
			$this->_setPageTitle( array( 'database_name' => $_pageNo ? sprintf( $this->lang->words['dbname_page_title'], $this->database['database_name'], $_pageNo + 1 ) : $this->database['database_name'] ), 1 );
		}
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		
		foreach( $this->fields as $field )
		{
			if( $field['field_display_display'] )
			{
				$showFields[]	= $field;
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		/* Followed stuffs */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'ccs', $this->database['database_database'].'_categories' );

		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'category' => $category, 'records' => $records, 'special' => $specialFields, 'follow_data' => $this->_like->render( 'summary', $category['category_id'] ) ) );
		
		return $_content;
	}
	
	/**
	 * Show a record.  Used this to get the special fields, then calls parent.
	 *
	 * @access	protected
	 * @return	string		HTML output
	 */
	protected function _getRecordDisplay()
	{
		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS20', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] );
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS21', false, null, 404 );
			}

			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS22', false, null, 403 );
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'rate', $category ) != TRUE )
			{
				$this->database['_can_rate']	= false;
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'comment', $category ) != TRUE )
			{
				$this->database['_can_comment']	= false;
			}
		}

		if( $record['record_locked'] )
		{
			$this->database['_can_comment']		= false;
		}
		 				
		//-----------------------------------------
		// Get template
		//-----------------------------------------
		
		$this->_loadTemplates( 'record', $record['record_template'] );

		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member	= IPSMember::load( $record['member_id'] );

		$_mem	= array();
		
		if( count($member) AND is_array($member) )
		{
			foreach( $member as $k => $v )
			{
				if( strpos( $k, 'field_' ) === 0 )
				{
					$k	= str_replace( 'field_', 'user_field_', $k );
				}
				
				$_mem[ $k ]	= $v;
			}
		}
		
		$member	= $_mem;
		
		$record	= array_merge( $member ? $member : IPSMember::setUpGuest(), $record );

		//-----------------------------------------
		// Format fields
		//-----------------------------------------
		
		$specialFields	= $this->_getSpecialFields();
		
		foreach( $record as $k => $v )
		{
			if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
			{
				$record[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $record );
				$record[ $this->fields[ $matches[1] ]['field_key'] ]	= $record[ $k . '_value' ];
			}
		}
		
		//-----------------------------------------
		// Published
		//-----------------------------------------
		
		if( $record[ $specialFields['date'] ] > time() AND ( !$this->memberData['member_id'] OR $record['member_id'] != $this->memberData['member_id'] ) AND !$this->database['moderate_edit'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS20.1', false, null, 404 );
		}
		
		$record	= $this->parseAttachments( $this->database, $this->fields, $record );
		
		$record['record_link']	= $this->getRecordUrl( $record, true );
		
		$this->checkPermalink( $record['record_link'] );
		
		if ( $this->settings['reputation_enabled'] )
		{
			$record['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'ccs', 'type' => 'record_id_' . $this->database['database_id'], 'id' => $record['primary_id_field'], 'rep_like_cache' => $record['rep_like_cache'] ) );
		}
		else
		{
			$record['like']	= null;
		}
		
		//-----------------------------------------
		// Now the fun part - look for cross-linked relational fields
		//-----------------------------------------

		foreach( $this->caches['ccs_fields'] as $_dbId => $_dbFields )
		{
			foreach( $_dbFields as $_fieldId => $_fieldData )
			{
				if( $_fieldData['field_type'] == 'relational' )
				{
					$_fieldConfig	= explode( ',', $_fieldData['field_extra'] );

					if( $_fieldConfig[4] AND $_fieldConfig[0] == $this->database['database_id'] )
					{
						//-----------------------------------------
						// Relational field set to crosslink
						//-----------------------------------------

						$this->fields[ $_fieldId ]	= array( '_isRelated' => 1, 'field_id' => $_fieldId, 'field_type' => 'relational', 'field_name' => $this->lang->words['rel_related_ft'], 'field_display_display' => 1 );
						$_remoteDb					= $this->caches['ccs_databases'][ $_dbId ];
						$_fieldValues				= array();

						if( $record[ 'field_' . $_fieldId ] )
						{
							$_fieldValues[ $record[ 'field_' . $_fieldId . '_value' ] ]	= $record[ 'field_' . $_fieldId ];
						}

						$this->DB->build( array( 'select' => '*', 'from' => $_remoteDb['database_database'], 'where' => 'field_' . $_fieldId . '=' . $record['primary_id_field'] . ' OR field_' . $_fieldId . " LIKE '%,{$record['primary_id_field']},%'" ) );
						$outer	= $this->DB->execute();

						while( $r = $this->DB->fetch($outer) )
						{
							$_fieldValues[ $this->registry->output->getTemplate('ccs_global')->auto_crosslink_html( $_remoteDb, $r ) ]	= $r[ $_remoteDb['database_field_title'] ];
						}

						natcasesort( $_fieldValues );

						$record[ 'field_' . $_fieldId ]				= array_keys( $_fieldValues ); /* Leave this unformatted so raw value is there if they want it */
						$record[ 'field_' . $_fieldId . '_value' ]	= implode( ', ', array_keys( $_fieldValues ) );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Do we need to post topic...?
		//-----------------------------------------
		
		if( !$record['record_topicid'] AND ( $category['category_forum_record'] OR $this->database['database_forum_record'] ) )
		{
			//-----------------------------------------
			// Post topic if configured to do so
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );

			if ( $_topics->postTopic( $record, $category, $this->database ) )
			{
				$this->sendRecordNotification( $this->database, $category, array_merge( $record, array( 'poster_name' => $record['members_display_name'], 'poster_seo_name' => $record['members_seo_name'] ) ) );
			}
		}
		
		//-----------------------------------------
		// User editable
		//-----------------------------------------

		if ( $this->registry->permissions->check( 'edit', $this->database ) == TRUE OR $this->registry->permissions->check( 'edit', $category ) == TRUE )
		{
			if( $this->database['database_all_editable'] OR ( $this->memberData['member_id'] AND $record['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= true;
			}
		}
		
		if( $record['category_id'] )
		{
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $category ) != TRUE )
			{
				$this->database['_can_edit']	= false;
			}
		}
		
		if( $this->database['moderate_edit'] )
		{
			$this->database['_can_edit']		= true;
		}
		
		if( $record['record_locked'] AND !$this->database['moderate_edit'] )
		{
			$this->database['_can_edit']		= false;
		}
		
		//-----------------------------------------
		// Breadcrumbs
		//-----------------------------------------

		$crumbies	= array();

		if( $record['category_id'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $record['category_id'], $this->database['base_link'] );

			array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
		}
		else
		{
			$crumbies[]	= array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) );
		}

		array_push( $crumbies, array( 0 => $this->lang->words['navigation_prefix'] . ' ' . $record[ $this->database['database_field_title'] . '_value' ], 1 => '' ) );
		
		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );

		if( $category['category_id'] )
		{
			$this->_setPageTitle( array( 'record_name' => $record[ $this->database['database_field_title'] . '_value' ], 'category_name' => $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'], 'database_name' => $this->database['database_name'] ), 3 );
		}
		else
		{
			$this->_setPageTitle( array( 'record_name' => $record[ $this->database['database_field_title'] . '_value' ], 'database_name' => $this->database['database_name'] ), 3 );
		}
		
		//-----------------------------------------
		// Mark read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'catID' => $record['category_id'], 'itemID' => $record['primary_id_field'], 'databaseID' => $this->database['database_id'] ), 'ccs' );
		
		//-----------------------------------------
		// Meta tags
		//-----------------------------------------
		
		if( $record['record_meta_keywords'] )
		{
			$this->pages->setMetaKeywords( $record['record_meta_keywords'] );
		}
		else
		{
			$text	= $record[ $this->database['database_field_title'] . '_value' ];
			$text	.= "\n " . $record[ $this->database['database_field_content'] . '_value' ];
			
			//-----------------------------------------
			// Mostly copied from output class
			//-----------------------------------------
			
			$text		= str_replace( array( '.', ',', '!', ':', ';', "'", "'", '@', '%', '*', '(', ')' ), '', preg_replace( "/&([^;]+?);/", "", strip_tags($text) ) );
			$_vals		= preg_split( "/\s+?/", $text, -1, PREG_SPLIT_NO_EMPTY );
			$_sw		= explode( ',', $this->lang->words['_stopwords_'] );
			$_fvals		= array();
			$_limit		= 30;
			$_c			= 0;
			
			if ( is_array( $_vals ) )
			{
				foreach( $_vals as $_v )
				{
					if ( strlen( $_v ) >= 3 AND ! in_array( $_v, array_values( $_fvals ) ) AND ! in_array( $_v, $_sw ) )
					{
						$_fvals[] = $_v;
					}
					
					if ( $_c >= $_limit )
					{
						break;
					}
					
					$_c++;
				}
			}

			$this->pages->setMetaKeywords( implode( ',', $_fvals ) );
		}
		
		if( $record['record_meta_description'] )
		{
			$this->pages->setMetaDescription( $record['record_meta_description'] );
		}
		else
		{
			if( $record[ $this->database['database_field_content'] . '_value' ] )
			{
				$text	= $record[ $this->database['database_field_content'] . '_value' ];
			}
			else
			{
				$text	= $record[ $this->database['database_field_title'] . '_value' ];
			}

			$text	= preg_replace( "#<!--hook\.([^\>]+?)-->#", '', preg_replace( "/\<p class='citation'\>.+?\<\/p\>/ims", '', $text ) );
			
			$this->pages->setMetaDescription( trim( IPSText::truncate( strip_tags($text), 247 ) ) );
		}
		
		$this->pages->setCanonical( $this->getRecordUrl( $record ) );
		
		/**
		 * @see	http://wiki.developers.facebook.com/index.php/Facebook_Share/Specifying_Meta_Tags
		 */
		if( $record[ $specialFields['image'] . '_value' ] )
		{
			$this->settings['meta_imagesrc'] = $record[ $specialFields['image'] . '_value' ];

			$this->pages->setHeadLink( '<link rel="image_src" href="' . $record[ $specialFields['image'] . '_value' ] . '" />' );
			$this->pages->setHeadLink( '<meta name="medium" content="news" />' );
		}
		
		//-----------------------------------------
		// Parse attachments, if supported
		//-----------------------------------------
		
		$record[ $this->database['database_field_content'] . '_value' ]	= $this->_parsePromotedAttachments( $record[ $this->database['database_field_content'] . '_value' ] );
		$record[ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $record[ $this->database['database_field_content'] . '_value' ];

		//-----------------------------------------
		// Comments class
		//-----------------------------------------

		if( $this->database['database_comments'] AND $record[ $specialFields['comments_allowed'] ] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
			$this->_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $this->database, 'category' => $this->categories->categories[ $record['category_id'] ], 'record' => $record ) );
			
			$comments	= array(
								'html'  => $this->_comments->fetchFormatted( $record, array( 'offset' => intval( $this->request['st'] ) ) ),
								'count' => $this->_comments->count( $record ),
								);
  		}
  		else
  		{
  			$comments	= array( 'count' => 0 );
  		}

		//-----------------------------------------
		// Get contributer/revision data
		//-----------------------------------------
		
		$record['contributers']		= array();
		
		if( $this->database['database_revisions'] )
		{
			$contributors	= array();
			$contributions	= array();
			
			$this->DB->build( array( 'select' => 'revision_date, revision_member_id', 'from' => 'ccs_database_revisions', 'where' => 'revision_database_id=' . $this->database['database_id'] . ' AND revision_record_id=' . $record['primary_id_field'] . ' AND revision_member_id <> ' . $record['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$contributions[ $r['revision_member_id'] ]	= ( isset($contributions[ $r['revision_member_id'] ]) AND $contributions[ $r['revision_member_id'] ] > $r['revision_date'] ) ? $contributions[ $r['revision_member_id'] ] : $r['revision_date'];
				$contributors[ $r['revision_member_id'] ]	= $r['revision_member_id'];
			}
			
			if( count($contributors) )
			{
				$members	= IPSMember::load( $contributors );
				
				foreach( $contributions as $mid => $date )
				{
					$member	= IPSMember::buildDisplayData( $members[ $mid ] );
					
					$contributions[ $mid ]	= array( 'date' => $date, 'member' => $member );
				}
			}
			
			$record['contributers']	= $contributions;
		}

		//-----------------------------------------
		// Update views count
		//-----------------------------------------
		
		$this->DB->update( $this->database['database_database'], "record_views=record_views+1", "primary_id_field={$record['primary_id_field']}", true, true );
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		
		foreach( $this->fields as $field )
		{
			if( $field['field_display_display'] )
			{
				$showFields[]	= $field;
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		/* Followed stuffs */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'ccs', $this->database['database_database'].'_records' );

		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'record' => $record, 'comments' => $comments, 'special' => $specialFields, 'category' => $category, 'follow_data' => $this->_like->render( 'summary', $record['primary_id_field'] ) ) );
		
		return $_content;
	}

	/**
	 * Load the database
	 *
	 * @access	protected
	 * @return	array 		Database info
	 */
	protected function _loadArticlesDatabase()
	{
		$database	= array();
		
		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $dbid => $_database )
			{
				if( $_database['database_is_articles'] )
				{
					$database	= $_database;
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Store globally for item marking lib
		//-----------------------------------------
		
		if( $database['database_id'] )
		{
			$this->settings['_currentDatabaseId']	= $database['database_id'];
		}
		
		return $database;
	}
	
	/**
	 * Load the appropriate template data
	 *
	 * @access	protected
	 * @param	string		Override to a specific template type
	 * @param	int			Specific template ID to use
	 * @return	@e void
	 */
	protected function _loadTemplates( $override='', $templateId=0 )
	{
		//-----------------------------------------
		// Which template we loading?
		//-----------------------------------------

		$templateId		= intval($templateId);
		$templateType	= $override;
		
		if( $this->request['record'] )
		{
			$templateType	= 'record';
		}
		else if( $this->request['view'] == 'archive' )
		{
			$templateType	= 'listing';
		}
		else if( $this->request['category'] )
		{
			$templateType	= 'category_frontpage';
		}
		else if( $this->request['view'] == 'categories' )
		{
			$templateType	= 'categories';
		}
		else if( !$override )
		{
			$templateType	= 'frontpage';
		}

		//-----------------------------------------
		// Get it
		//-----------------------------------------

		switch( $templateType )
		{
			case 'record':
				$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . ( $templateId ? $templateId : $this->database['database_template_display'] ) ) );
			break;
			
			case 'category_frontpage':
				$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $templateId ) );
			break;
			
			case 'categories':
				$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $this->database['database_template_categories'] ) );
			break;
			
			case 'listing':
			case 'category':
				$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $this->database['database_template_listing'] ) );
			break;
			
			case 'frontpage':
				$cache		= $this->registry->ccsFunctions->returnFrontpageCache();
				$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $cache['template'] ) );
			break;
		}

		//-----------------------------------------
		// Merge into database data and return
		//-----------------------------------------

		if( !is_array($template) OR !count($template) )
		{
			$this->registry->output->showError( $this->lang->words['error_loading_db_template'], '10CCSDB.2' );
		}
		
		$this->database	= array_merge( $this->database, $template );
	}
	
	/**
	 * Get special field mappings (based on field_key)
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function _getSpecialFields()
	{
		$specialFields	= array(
								'title'					=> '',
								'content'				=> '',
								'teaser'				=> '',
								'frontpage'				=> '',
								'date'					=> '',
								'expiry'				=> '',
								'comments_allowed'		=> '',
								'comments_cutoff'		=> '',
								'image'					=> '',
								);

		foreach( $this->fields as $_field )
		{
			switch( $_field['field_key'] )
			{
				case 'article_title':
					$specialFields['title']				= 'field_' . $_field['field_id'];
				break;

				case 'article_body':
					$specialFields['content']			= 'field_' . $_field['field_id'];
				break;

				case 'teaser_paragraph':
					$specialFields['teaser']			= 'field_' . $_field['field_id'];
				break;

				case 'article_image':
					$specialFields['image']				= 'field_' . $_field['field_id'];
				break;
				
				case 'article_homepage':
					$specialFields['frontpage']			= 'field_' . $_field['field_id'];
				break;
				
				case 'article_date':
					$specialFields['date']				= 'field_' . $_field['field_id'];
				break;
				
				case 'article_expiry':
					$specialFields['expiry']			= 'field_' . $_field['field_id'];
				break;
				
				case 'article_comments':
					$specialFields['comments_allowed']	= 'field_' . $_field['field_id'];
				break;
				
				case 'article_cutoff':
					$specialFields['comments_cutoff']	= 'field_' . $_field['field_id'];
				break;
			}
		}

		return $specialFields;
	}

	/**
	 * Attempt to parse attachments from a promoted post...
	 *
	 * @access	protected
	 * @param	string		Content
	 * @return	string		Content (with attachments parsed)
	 */
	protected function _parsePromotedAttachments( $content )
	{
		//-----------------------------------------
		// Only bother if there is an attachment tag
		//-----------------------------------------
		
		if( strpos( $content, '[attachment=' ) !== false )
		{
			preg_match( "/\[attachment=(\d+):.*?\]/i", $content, $matches );
			
			if( $matches[1] )
			{
				$_attach	= $this->DB->buildAndFetch( array( 'select' => 'attach_rel_id', 'from' => 'attachments', 'where' => 'attach_id=' . intval($matches[1]) ) );
				
				if( $_attach['attach_rel_id'] )
				{
					$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
					$_attachments   =  new $classToLoad( $this->registry );
					
					$_attachments->type		= 'post';
					$_attachments->init();

					$attachHTML = $_attachments->renderAttachments( $content, array( $_attach['attach_rel_id'] ), 'ccs_global' );
			
					/* Now parse back in the rendered posts */
					if( is_array($attachHTML) AND count($attachHTML) )
					{
						foreach( $attachHTML as $id => $data )
						{
							/* Get rid of any lingering attachment tags */
							if ( stristr( $data['html'], "[attachment=" ) )
							{
								$data['html'] = IPSText::stripAttachTag( $data['html'] );
							}
							
							if( $data['html'] )
							{
								$content		= $data['html'];
							}
							
							$content		.= $data['attachmentHtml'];
						}
					}
				}
			}
		}
		
		return $content;
	}
}