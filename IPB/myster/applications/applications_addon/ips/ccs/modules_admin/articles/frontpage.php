<?php
/**
 * <pre>
 * Invision Power Services
 * IP.CCS article front page management
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_frontpage extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */	
	protected $html;

	/**
	 * Database info
	 *
	 * @var		array 			DB info
	 */	
	public $database			= array();
	
	/**
	 * Fields info
	 *
	 * @var		array 			Field info
	 */	
	public $fields				= array();
	
	/**
	 * Special mapped fields
	 *
	 * @var		array 			Fields
	 */
	public $specialFields		= array();
	
	/**
	 * Category lib
	 *
	 * @var		object
	 */
	public $categories;

	/**
	 * Main class entry point
	 *
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
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=frontpage';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=frontpage';

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
			$this->registry->output->showError( $this->lang->words['no_db_id_fp'], '11CCS10' );
		}

		//-----------------------------------------
		// And fields
		//-----------------------------------------
		
		$this->fields	= array();

		if( is_array($this->caches['ccs_fields'][ $this->database['database_id'] ]) AND count($this->caches['ccs_fields'][ $this->database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $this->database['database_id'] ] as $_field )
			{
				$this->fields[ $_field['field_id'] ]	= $_field;
				
				$this->specialFields[ $_field['field_key'] ]	= $_field;
			}
		}
		
		//-----------------------------------------
		// And categories
		//-----------------------------------------
		
		$this->categories	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database, false );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'frontpage':
			default:
				$this->_frontPage();
			break;
			
			case 'config':
				$this->_updateConfig();
			break;
			
			case 'remove':
				$this->_removeArticles( array( $this->request['article'] ) );
			break;
			
			case 'articles':
				$this->_removeArticles( $this->request['articles'] );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Update frontpage configuration
	 *
	 * @return	@e void
	 */
	protected function _updateConfig()
	{
		//-----------------------------------------
		// Get cache settings
		//-----------------------------------------
		
		$cache	= $this->registry->ccsFunctions->returnFrontpageCache();

		$cache['categories']		= '*';
		$cache['limit']				= intval($this->request['config_limit']);
		$cache['sort']				= $this->request['config_sort'];
		$cache['order']				= $this->request['config_order'];
		$cache['pinned']			= intval($this->request['config_pinned']);
		$cache['paginate']			= intval($this->request['config_paginate']);
		$cache['template']			= intval($this->request['config_template']);
		$cache['exclude_subcats']	= intval($this->request['config_subcats']);
		
		$this->cache->setCache( 'ccs_frontpage', $cache, array( 'array' => 1 ) );

		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_adminlog_frontpageconfig'] );
		
		$this->registry->output->setMessage( $this->lang->words['fp_settings_updated'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Remove articles from front page
	 *
	 * @param	array 		Article ids to remove from front page
	 * @return	@e void
	 */
	protected function _removeArticles( $articles )
	{
		$articles	= IPSLib::cleanIntArray( $articles );
		
		if( !is_array($articles) OR !count($articles) )
		{
			//-----------------------------------------
			// Throw error
			//-----------------------------------------
			
			$this->registry->output->showError( $this->lang->words['no_arts_remove_fp'], '11CCS11' );
		}
		
		$update	= array(
						'field_' . $this->specialFields['article_homepage']['field_id'] => 0,
						);

		$this->DB->update( $this->database['database_database'], $update, 'primary_id_field IN(' . implode( ',', $articles ) . ')' );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_fpartremoved'], count($articles) ) );

		$this->registry->output->setMessage( $this->lang->words['fp_articles_removed'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Front page manager
	 * 
	 * @return void
	 */
	protected function _frontPage()
	{
		//-----------------------------------------
		// Get cache settings
		//-----------------------------------------
		
		$cache	= $this->registry->ccsFunctions->returnFrontpageCache();
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		
		//-----------------------------------------
		// And get flagged articles
		// Note: checkbox fields are arrays natively,
		//	so we have to check for =',1,' instead of =1
		//-----------------------------------------
		
		$articles	= array();
		
		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( $this->database['database_database'] => 'r' ),
								'where'		=> $this->specialFields['article_homepage']['field_id'] ? "field_{$this->specialFields['article_homepage']['field_id']}=',1,'" : '',
								'order'		=> $cache['sort'] . ' ' . $cache['order'],
								'limit'		=> array( 100 ),
								'add_join'	=> array(
													array(
														'select'	=> 'm.*',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=r.member_id',
														'type'		=> 'left'
														)
													)
						)		);
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$r['title']		= $fieldsClass->getFieldValue( $this->specialFields['article_title'], $r, $this->specialFields['article_title']['field_truncate'] );
			$r['body']		= $fieldsClass->getFieldValue( $this->specialFields['article_body'], $r, $this->specialFields['article_body']['field_truncate'] );
			$r['teaser']	= $fieldsClass->getFieldValue( $this->specialFields['teaser_paragraph'], $r, $this->specialFields['teaser_paragraph']['field_truncate'] );
			$r['date']		= $fieldsClass->getFieldValue( $this->specialFields['article_date'], $r, $this->specialFields['article_date']['field_truncate'] );
			$r['category']	= $this->categories->categories[ $r['category_id'] ]['category_name'];
			
			$articles[]	= $r;
		}

		//-----------------------------------------
		// Get categories too
		//-----------------------------------------
		
		#$this->request['category']	= ( $cache['categories'] != '*' AND $cache['categories'] ) ? explode( ',', $cache['categories'] ) : array();
		
		$categories	= '';#$this->categories->getSelectMenu();
		
		//-----------------------------------------
		// Fields
		//-----------------------------------------
		
		$fields		= array();
		$fields[]	= array( 'primary_id_field', $this->lang->words['field__id'] );
		$fields[]	= array( 'member_id', $this->lang->words['field__member'] );
		$fields[]	= array( 'record_saved', $this->lang->words['field__saved'] );
		$fields[]	= array( 'record_updated', $this->lang->words['field__updated'] );
		$fields[]	= array( 'rating_real', $this->lang->words['field__rating'] );
		
		foreach( $this->fields as $r )
		{
			if( in_array( $r['field_type'], array( 'checkbox', 'multiselect', 'attachments' ) ) )
			{
				continue;
			}

			$fields[]	= array( 'field_' . $r['field_id'], $r['field_name'] );
		}
		
		//-----------------------------------------
		// And front page templates
		//-----------------------------------------
		
		$templates	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_database=4' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates[]	= array( $r['template_id'], $r['template_name'], $r['template_category'] );
		}

		$containers	= array();
		
		$this->DB->build( array( 'select' => 'container_id, container_name', 'from' => 'ccs_containers', 'where' => "container_type='arttemplate'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$containers[ $r['container_id'] ]	= $r['container_name'];
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->frontpageForm( $cache, $articles, $fields, $this->database, $categories, $templates, $containers );
	}
}