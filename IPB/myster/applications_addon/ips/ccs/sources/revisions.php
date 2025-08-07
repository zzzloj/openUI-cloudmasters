<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS revision manager functions
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		31st August 2010
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class revisionManager
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;
	protected $lang;
	/**#@-*/
	
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
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_revisions' );
		
		//-----------------------------------------
		// Set up stuff
		// Note: The rogue 's' is on purpose - type would be 'page' while module is 'pages' (same with template vs templates)
		//-----------------------------------------
		
		$_codeType			= $this->request['type'];

		if( $_codeType == 'blocktemplate' )
		{
			$_codeType	= 'template';
		}

		$this->form_code	= $this->html->form_code	= 'module=' . $_codeType . 's&amp;section=revisions&amp;type=' . $this->request['type'] . '&amp;ttype=' . $this->request['ttype'];
		$this->form_code_js	= $this->html->form_code_js	= 'module=' . $_codeType . 's&section=revisions&type=' . $this->request['type'] . '&ttype=' . $this->request['ttype'];
		
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
	}

	/**
	 * Pass and route request
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function passRequest()
	{
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'revisions':
				$this->_listRevisions();
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

			case 'clearAll':
				$this->_deleteAllRevisions();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Delete all revisions for an item
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteAllRevisions()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevdel1'], '11CCS701.1' );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		$this->DB->delete( "ccs_revisions", "revision_type='{$this->request['type']}' AND revision_type_id=" . $id );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_revisionsdeleted'], $this->request['type'] . ' ' . $this->_getTitle( $id, $this->request['type'] ) ) );
		
		$this->registry->output->setMessage( $this->lang->words['revision_clr_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;id=' . $id );
	}
	
	/**
	 * Get the title for the content
	 *
	 * @access	protected
	 * @param	int			ID
	 * @param	string		Type
	 * @return	string		Title
	 */
	protected function _getTitle( $id, $type )
	{
		$title	= array( 'title' => '' );

		switch( $type )
		{
			case 'page':
				$title	= $this->DB->buildAndFetch( array( 'select' => 'page_name as title', 'from' => 'ccs_pages', 'where' => "page_id=" . $id ) );
			break;
			
			case 'block':
				$title	= $this->DB->buildAndFetch( array( 'select' => 'block_name as title', 'from' => 'ccs_blocks', 'where' => "block_id=" . $id ) );
			break;
			
			case 'template':
				$title	= $this->DB->buildAndFetch( array( 'select' => 'template_name as title', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $id ) );
			break;

			case 'blocktemplate':
				$title	= $this->DB->buildAndFetch( array( 'select' => 'tpb_human_name as title', 'from' => 'ccs_template_blocks', 'where' => "tpb_id=" . $id ) );
			break;
		}
		
		return $title['title'];
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
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['revisions_no_id'], '11CCS700' );
		}
		
		$title	= $this->_getTitle( $id, $this->request['type'] );

		//-----------------------------------------
		// Get all revisions for this content
		//-----------------------------------------
		
		$revisions	= array();
		
		$this->DB->build( array( 
								'select'	=> 'r.*', 
								'from'		=> array( 'ccs_revisions' => 'r' ), 
								'where'		=> "r.revision_type='{$this->request['type']}' AND r.revision_type_id=" . $id, 
								'order'		=> 'r.revision_date DESC',
								'add_join'	=> array(
												array(
													'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
													'from'		=> array( 'members' => 'm' ),
													'where'		=> 'm.member_id=r.revision_member',
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

		$this->registry->output->html .= $this->html->revisions( $title, $revisions, $id, $this->request['type'] );

		if( $this->request['type'] == 'page' )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => 'page_folder', 'from' => 'ccs_pages', 'where' => "page_id=" . $id ) );
			$this->registry->ccsAcpFunctions->addNavigation( $page['page_folder'] );
		}	
	
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['revisions_title_pre'] . ' ' . $title );
	}
	
	/**
	 * Delete a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _deleteRevision()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevdel'], '11CCS701' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevdel'], '11CCS702' );
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		$this->DB->delete( "ccs_revisions", "revision_id=" . $id );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_revisiondeleted'], $this->request['type'] . ' ' . $this->_getTitle( $id, $this->request['type'] ) ) );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['revision_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;id=' . $revision['revision_type_id'] );
	}
	
	/**
	 * Restore a revision
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _restoreRevision()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevres'], '11CCS703' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevres'], '11CCS704' );
		}
		
		//-----------------------------------------
		// Backup current version, and restore this revision
		//-----------------------------------------
		
		switch( $revision['revision_type'] )
		{
			case 'page':
				$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $revision['revision_type_id'] ) );
				
				if( $current['page_id'] )
				{
					$newRevision	= array(
											'revision_type'		=> 'page',
											'revision_type_id'	=> $revision['revision_type_id'],
											'revision_content'	=> $current['page_content'],
											'revision_date'		=> time(),
											'revision_member'	=> $this->memberData['member_id'],
											);

					$this->DB->insert( "ccs_revisions", $newRevision );
					
					$this->DB->update( "ccs_pages", array( 'page_content' => $revision['revision_content'] ), "page_id=" . $revision['revision_type_id'] );
					
					$this->DB->delete( "ccs_revisions", "revision_id=" . $revision['revision_id'] );
				}
			break;
			
			case 'block':
				//-----------------------------------------
				// We're going to cheat here... :-"
				//-----------------------------------------
				
				$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name LIKE '%_{$revision['revision_type_id']}'" ) );
				$block		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => "block_id=" . $revision['revision_type_id'] ) );
				
				if( $current['tpb_id'] AND $block['block_id'] )
				{
					$newRevision	= array(
											'revision_type'		=> 'block',
											'revision_type_id'	=> $revision['revision_type_id'],
											'revision_content'	=> $block['block_type'] == 'custom' ? $block['block_content'] : $current['tpb_content'],
											'revision_date'		=> time(),
											'revision_member'	=> $this->memberData['member_id'],
											);

					$this->DB->insert( "ccs_revisions", $newRevision );
					
					if( $block['block_type'] == 'custom' )
					{
						$this->DB->update( "ccs_blocks", array( 'block_content' => $revision['revision_content'], 'block_cache_last' => 0, 'block_cache_output' => '' ), "block_id=" . $revision['revision_type_id'] );
					}
					else
					{
						$this->DB->update( "ccs_template_blocks", array( 'tpb_content' => $revision['revision_content'] ), "tpb_id=" . $current['tpb_id'] );
						$this->DB->update( "ccs_blocks", array( 'block_cache_last' => 0, 'block_cache_output' => '' ), "block_id=" . $revision['revision_type_id'] );
					}
					
					//-----------------------------------------
					// Clear page caches
					//-----------------------------------------
		
					$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
					
					$this->DB->delete( "ccs_revisions", "revision_id=" . $revision['revision_id'] );
				}
			break;
			
			case 'template':
				$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $revision['revision_type_id'] ) );
				
				if( $current['template_id'] )
				{
					$newRevision	= array(
											'revision_type'		=> 'template',
											'revision_type_id'	=> $revision['revision_type_id'],
											'revision_content'	=> $current['template_content'],
											'revision_date'		=> time(),
											'revision_member'	=> $this->memberData['member_id'],
											);

					$this->DB->insert( "ccs_revisions", $newRevision );
					
					$this->DB->update( "ccs_page_templates", array( 'template_content' => $revision['revision_content'] ), "template_id=" . $revision['revision_type_id'] );
					$this->DB->update( "ccs_pages", array( 'page_cache' => null ), "page_template_used=" . $revision['revision_type_id'] );
					
					$this->DB->delete( "ccs_revisions", "revision_id=" . $revision['revision_id'] );

					//-----------------------------------------
					// Recache the template
					//-----------------------------------------
					
					$cache	= array(
									'cache_type'	=> 'template',
									'cache_type_id'	=> $revision['revision_type_id'],
									);

					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
					$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
					$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $current['template_key'], $current['template_database'] ? '$data' : '', $revision['revision_content'], '', false, true );
					
					$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$revision['revision_type_id']}" ) );
					
					if( $hasIt['cache_id'] )
					{
						$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$revision['revision_type_id']}" );
					}
					else
					{
						$this->DB->insert( 'ccs_template_cache', $cache );
					}

					//-----------------------------------------
					// Recache the "skin" file
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$_pagesClass	= new $classToLoad( $this->registry );
					$_pagesClass->recacheTemplateCache( $engine );
				}
			break;

			case 'blocktemplate':
				$current	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => 'tpb_id=' . $revision['revision_type_id'] ) );
				
				if( $current['tpb_id'] )
				{
					$newRevision	= array(
											'revision_type'		=> 'blocktemplate',
											'revision_type_id'	=> $revision['revision_type_id'],
											'revision_content'	=> $current['tpb_content'],
											'revision_date'		=> time(),
											'revision_member'	=> $this->memberData['member_id'],
											);

					$this->DB->insert( "ccs_revisions", $newRevision );
					
					$this->DB->update( "ccs_template_blocks", array( 'tpb_content' => $revision['revision_content'] ), "tpb_id=" . $revision['revision_type_id'] );
					$this->DB->update( "ccs_blocks", array( 'block_cache_output' => null, 'block_cache_last' => 0 ), "block_template=" . $revision['revision_type_id'] );
					
					$this->DB->delete( "ccs_revisions", "revision_id=" . $revision['revision_id'] );
				}
			break;
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_revisionrestored'], $revision['revision_type'] . ' ' . $this->_getTitle( $id, $revision['revision_type'] ) ) );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['restoredrevision'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=revisions&amp;id=' . $revision['revision_type_id'] );
	}
	
	/**
	 * Form to edit a revision
	 *
	 * @access	public
	 * @param	string		Default content
	 * @return	@e void
	 */
	public function _editRevision( $content='' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['revisionnoidedit'], '11CCS705' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['revisionnoidedit'], '11CCS706' );
		}
		
		$editorType	 = 'html';
		
		if( $revision['revision_type'] == 'page' )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => 'page_type', 'from' => 'ccs_pages', 'where' => 'page_id=' . $revision['revision_type_id'] ) );
			
			if( $page['page_type'] == 'bbcode' )
			{
				$editorType	= 'bbcode';
			}
		}
		else if( $revision['revision_type'] == 'block' )
		{
			$block	= $this->DB->buildAndFetch( array( 'select' => 'block_type, block_config', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $revision['revision_type_id'] ) );
			
			if( $block['block_type'] == 'custom' )
			{
				$_config	= unserialize($block['block_config']);
				
				if( $_config['type'] == 'bbcode' )
				{
					$editorType	= 'bbcode';
				}
			}
		}
		
		switch( $editorType )
		{
			case 'html':
				$editor_area	= $this->registry->output->formTextarea( "content", IPSText::htmlspecialchars( $content ? $content : $revision['revision_content'] ), 100, 30, "content", "style='width:100%;'" );
				$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
				
				$this->registry->output->html .= $_globalHtml->getWysiwyg( 'content' );
			break;
			
			case 'bbcode':
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
				$editor = new $classToLoad();

				$editor->setContent( $content ? $content : $revision['revision_content'] );

				$editor_area	= $editor->show( 'content' );
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->editRevision( $revision, $editor_area );
		
		if( $this->request['type'] == 'page' )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => 'page_folder', 'from' => 'ccs_pages', 'where' => "page_id=" . $id ) );
			$this->registry->ccsAcpFunctions->addNavigation( $page['page_folder'] );
		}

		$this->registry->output->extra_nav[] = array( '', $this->lang->words['revisione_title_pre'] . ' ' . $this->_getTitle( $revision['revision_type_id'], $this->request['type'] ) );
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
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['revisionnoidedit'], '11CCS707' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['revisionnoidedit'], '11CCS708' );
		}
		
		//-----------------------------------------
		// Handle data appropriately for content type
		//-----------------------------------------
		
		$content	= '';
		
		switch( $revision['revision_type'] )
		{
			case 'page':
				$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $revision['revision_type_id'] ) );
				
				if( $page['page_type'] == 'bbcode' )
				{
					$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
					$editor			= new $classToLoad();
					$content		= $editor->process( $_POST['content'] );
			
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$content		= IPSText::getTextClass( 'bbcode' )->preDbParse( $content );
				}
				else
				{
					$content	= $_POST['content'];
				}
				
				$content	= IPSText::formToText( trim($content) );
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $page['page_type'] == 'php' )
				{
					if( strpos( $content, '<?php' ) !== false OR strpos( $content, '<?' ) !== false )
					{
						$this->registry->output->global_error	= $this->lang->words['php_page_php_tag'];
						
						$this->_editRevision( $_POST['content'] );
						return;
					}
				}
			break;
			
			case 'block':
				$block		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $revision['revision_type_id'] ) );
				$_config	= unserialize($block['block_config']);
				
				if( $block['block_type'] == 'custom' AND $_config['type'] == 'bbcode' )
				{
					$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
					$editor			= new $classToLoad();
					$content		= $editor->process( $_POST['content'] );
			
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$content		= IPSText::getTextClass( 'bbcode' )->preDbParse( $content );
				}
				else
				{
					$content	= $_POST['content'];
				}
				
				$content	= IPSText::formToText( trim($content) );
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $block['block_type'] == 'custom' AND $_config['type'] == 'php' )
				{
					if( strpos( $content, '<?php' ) !== false OR strpos( $content, '<?' ) !== false )
					{
						$this->registry->output->global_error	= $this->lang->words['php_page_php_tag'];
						
						$this->_editRevision( $_POST['content'] );
						return;
					}
				}
				else if( $block['block_type'] != 'custom' )
				{
					$content	= str_replace( '&#46;&#46;/', '../', trim($_POST['content']) );
					
					//-----------------------------------------
					// Test the syntax
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
					$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
					
					$_TEST	= $engine->convertHtmlToPhp( 'testtemplate', '', $content, '', false, true );
			
					ob_start();
					eval( $_TEST );
					$_RETURN = ob_get_contents();
					ob_end_clean();
			
					if( $_RETURN )
					{
						$this->registry->output->global_error	= $this->lang->words['revisionbadtemplatesyntax'];
						
						$this->_editRevision( $_POST['content'] );
						return;
					}
				}
			break;
			
			case 'template':
			case 'blocktemplate':
				$content	= str_replace( '&#46;&#46;/', '../', trim($_POST['content']) );
				
				//-----------------------------------------
				// Test the syntax
				//-----------------------------------------
				
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
				$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
				
				$_TEST	= $engine->convertHtmlToPhp( 'testtemplate', '', $content, '', false, true );
		
				ob_start();
				eval( $_TEST );
				$_RETURN = ob_get_contents();
				ob_end_clean();
		
				if( $_RETURN )
				{
					$this->registry->output->global_error	= $this->lang->words['revisionbadtemplatesyntax'];
					
					$this->_editRevision( $_POST['content'] );
					return;
				}
			break;
		}
		
		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		$this->DB->update( 'ccs_revisions', array( 'revision_content' => $content ), 'revision_id=' . $id );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_revisionedited'], $revision['revision_type'] . ' ' . $this->_getTitle( $id, $revision['revision_type'] ) ) );

		$this->registry->output->setMessage( $this->lang->words['revisioneditedsuccess'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;id=' . $revision['revision_type_id'] );
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
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevcom'], '11CCS709' );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevcom'], '11CCS710' );
		}
		
		switch( $revision['revision_type'] )
		{
			case 'page':
				$data	= $this->DB->buildAndFetch( array( 'select' => 'page_id as content_id, page_content as content', 'from' => 'ccs_pages', 'where' => 'page_id=' . $revision['revision_type_id'] ) );
			break;
			
			case 'block':
				$_block	= $this->DB->buildAndFetch( array( 'select' => 'block_id,block_content,block_type', 'from' => 'ccs_blocks', 'where' => "block_id=" . $revision['revision_type_id'] ) );
				
				if( $_block['block_type'] != 'custom' )
				{
					$data	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id as content_id, tpb_content as content', 'from' => 'ccs_template_blocks', 'where' => "tpb_name LIKE '%_{$revision['revision_type_id']}'" ) );
				}
				else
				{
					$data['content']	= $_block['block_content'];
					$data['content_id']	= $_block['block_id'];
				}
				
				$data['content']				= nl2br($data['content']);
				$revision['revision_content']	= nl2br($revision['revision_content']);
			break;
			
			case 'template':
				$data	= $this->DB->buildAndFetch( array( 'select' => 'template_id as content_id, template_content as content', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $revision['revision_type_id'] ) );
				
				$data['content']	= nl2br($data['content']);
			break;

			case 'blocktemplate':
				$data	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id as content_id, tpb_content as content', 'from' => 'ccs_template_blocks', 'where' => 'tpb_id=' . $revision['revision_type_id'] ) );
				
				$data['content']				= nl2br($data['content']);
				$revision['revision_content']	= nl2br($revision['revision_content']);
			break;
		}

		if( !$data['content_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnotfindrevcom'], '11CCS711' );
		}

		//-----------------------------------------
		// Run diff
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classDifference.php', 'classDifference' );
		$differences	= new $classToLoad();
		$differences->method = 'PHP';
		
		$result	= $differences->formatDifferenceReport( $differences->getDifferences( IPSText::br2nl( $revision['revision_content'] ), IPSText::br2nl( $data['content'] ), 'unified' ), 'unified', false );

		if( !$result )
		{
			$result	= nl2br( str_replace( "\t", "&nbsp; &nbsp; ", IPSText::htmlspecialchars( IPSText::br2nl( $revision['revision_content'] ) ) ) );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->compareRevisions( $result, $id );
		
		if( $this->request['type'] == 'page' )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => 'page_folder', 'from' => 'ccs_pages', 'where' => "page_id=" . $id ) );
			$this->registry->ccsAcpFunctions->addNavigation( $page['page_folder'] );
		}

		$this->registry->output->extra_nav[] = array( '', $this->lang->words['revisionc_title_pre'] . ' ' . $this->_getTitle( $revision['revision_type_id'], $this->request['type'] ) );
	}
}