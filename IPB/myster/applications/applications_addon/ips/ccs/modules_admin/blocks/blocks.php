<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS blocks
 * Last Updated: $Date: 2012-03-14 13:28:26 -0400 (Wed, 14 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10425 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_blocks_blocks extends ipsCommand
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
	 * Uploader library
	 *
	 * @access	public
	 * @var		object
	 */
	public $upload;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=blocks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=blocks';
		
		//-----------------------------------------
		// Fix \ issue with submitted data
		//-----------------------------------------
		
		$_fields	= array( 'custom_template', 'custom_content' );
		
		foreach( $_fields as $_field )
		{
			if( $_POST[ $_field ] )
			{
				$_POST[ $_field ]	= str_replace( '&#092;', '\\', $_POST[ $_field ] );
			}
		}

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
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'delete':
				$this->_deleteBlock();
			break;
			
			case 'recache':
				$this->_recacheBlock();
			break;

			case 'recacheAll':
				$this->_recacheAllBlocks();
			break;
			
			case 'export':
				$this->_exportBlock();
			break;
			
			case 'exportBlock':
				$this->_exportSingleBlock();
			break;
			
			case 'import':
				$this->_importBlock();
			break;
			
			case 'addCategory':
				$this->_categoryForm( 'add' );
			break;
			case 'editCategory':
				$this->_categoryForm( 'edit' );
			break;
			
			case 'doAddCategory':
				$this->_categorySave( 'add' );
			break;
			case 'doEditCategory':
				$this->_categorySave( 'edit' );
			break;
			
			case 'deleteCategory':
				$this->_deleteCategory();
			break;

			default:
				$this->_list();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Delete a category
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _deleteCategory()
	{
		$id	= intval($this->request['id']);
		
		$category	= $this->DB->buildAndFetch( array( 'select' => 'container_name', 'from' => 'ccs_containers', 'where' => 'container_id=' . $id ) );

		$this->DB->update( 'ccs_blocks', array( 'block_category' => 0 ), 'block_category=' . $id );
		$this->DB->delete( 'ccs_containers', 'container_id=' . $id );
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blockcatdeleted'], $category['container_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['block_cat_deleted'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}

	/**
	 * Save a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	@e void
	 */
	protected function _categorySave( $type='add' )
	{
		//-----------------------------------------
		// Protected links from dashboard
		//-----------------------------------------
		
		if ( $this->request['request_method'] != 'post' )
		{
			return $this->_categoryForm( $type );
		}
		
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'], '11CCS12' );
			}
		}
		
		$save	= array( 'container_name' => $this->request['category_title'] );
		
		if( $type == 'add' )
		{
			$save['container_type']		= 'block';
			$save['container_order']	= 100;
			
			$this->DB->insert( 'ccs_containers', $save );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blockcatadded'], $save['container_name'] ) );
		}
		else
		{
			$this->DB->update( 'ccs_containers', $save, "container_id=" . $category['container_id'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blockcatedited'], $save['container_name'] ) );
		}
		
		$this->registry->output->setMessage( $this->lang->words['block_cat_save__good'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Form to add/edit a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	@e void
	 */
	protected function _categoryForm( $type='add' )
	{
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'], '11CCS13' );
			}
		}
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['edit_block_cat__title'] . ' ' . $category['container_name'] );
		
		$this->registry->output->html .= $this->html->categoryForm( $type, $category );
	}

	/**
	 * Import skin templates for a plugin block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _importBlock()
	{
		//-----------------------------------------
		// Developer reimporting templates?
		//-----------------------------------------
		
		if( $this->request['dev'] )
		{
			$templates	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				if( !preg_match( "/_(\d+)$/", $r['tpb_name'] ) )
				{
					$templates[ $r['tpb_name'] ]	= $r;
				}
			}
			
			$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/plugin_block_templates.xml' );
			
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
			$xml->loadXML( $content );
			
			foreach( $xml->fetchElements('template') as $template )
			{
				$_template	= $xml->fetchElementsFromRecord( $template );

				if( $_template['tpb_name'] )
				{
					unset($_template['tpb_id']);
					
					if( isset( $templates[ $_template['tpb_name'] ] ) )
					{
						$this->DB->update( "ccs_template_blocks", $_template, "tpb_id={$templates[ $_template['tpb_name'] ]['tpb_id']}" );
					}
					else
					{
						$this->DB->insert( "ccs_template_blocks", $_template );
					}
				}
			}
			
			$this->registry->output->setMessage( $this->lang->words['block_import_devgood'] );

			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
		}
		
		$content = $this->registry->getClass('adminFunctions')->importXml();

		if( !$content )
		{
			$this->registry->output->showError( $this->lang->words['block_not_imported'], '11CCS13.1' );
		}
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		//-----------------------------------------
		// First, see if this is an XMLArchive
		// If it is, this is a feed block with block
		// templates included. We need to import them.
		//-----------------------------------------	

		if( count( $xml->fetchElements('xmlarchive') ) )
		{
			$this->_importBlockWithTemplate( $content );
			return;
		}

		//-----------------------------------------
		// First, find out if this is just a plugin
		//-----------------------------------------
		
		$_fullBlock	= false;
		$_block		= array();
		$_blockId	= 0;
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );
		}
		
		if( count($_block) )
		{
			$_fullBlock	= true;
		}

		//-----------------------------------------
		// If full block, insert block first to get id
		//-----------------------------------------
		
		if( $_fullBlock )
		{
			$_blockId = $this->_insertBlockData( $_block );
		}

		//-----------------------------------------
		// Do the template regardless
		//-----------------------------------------
		
		$tpbId	= 0;

		foreach( $xml->fetchElements('template') as $template )
		{
			$entry  = $xml->fetchElementsFromRecord( $template );

			if( !$entry['tpb_name'] )
			{
				continue;
			}
			
			$templatebit	= array(
									'tpb_name'		=> $entry['tpb_name'],
									'tpb_params'	=> $entry['tpb_params'],
									'tpb_content'	=> $entry['tpb_content'],
									);

			//-----------------------------------------
			// Fix name if full block
			//-----------------------------------------
			
			if( $_fullBlock )
			{
				$templatebit['tpb_name']	= preg_replace( "/^(.+?)_(\d+)$/", "\\1_{$_blockId}", $templatebit['tpb_name'] );
			}
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$templatebit['tpb_name']}'" ) );
			
			if( $check['tpb_id'] )
			{
				$this->DB->update( 'ccs_template_blocks', $templatebit, 'tpb_id=' . $check['tpb_id'] );
				$tpbId	= $check['tpb_id'];
			}
			else
			{
				$this->DB->insert( 'ccs_template_blocks', $templatebit );
				$tpbId	= $this->DB->getInsertId();
			}
		}
		
		//-----------------------------------------
		// Recache skin if full block
		//-----------------------------------------
		
		if( $_fullBlock AND $tpbId )
		{
			$cache	= array(
							'cache_type'	=> 'block',
							'cache_type_id'	=> $tpbId,
							);
	
			$classToLoad			= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classTemplateEngine.php', 'classTemplate' );
			$engine					= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
			$cache['cache_content']	= $engine->convertHtmlToPhp( "{$templatebit['tpb_name']}", $templatebit['tpb_params'], $templatebit['tpb_content'], '', false, true );
			
			$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$tpbId}" ) );
			
			if( $hasIt['cache_id'] )
			{
				$this->DB->update( 'ccs_template_cache', $cache, "cache_type='block' AND cache_type_id={$tpbId}" );
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

		if( !$_fullBlock AND !$tpbId )
		{
			$this->registry->output->showError( $this->lang->words['block_not_imported'], '11CCS13.2' );
		}
		
		if( $_fullBlock )
		{
			$this->registry->output->setMessage( $this->lang->words['block_full_import_good'] );
		}
		else
		{
			$this->registry->output->setMessage( $this->lang->words['block_import_good'] );

			$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_adminlog_blocktimported'] );
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Inserts a new block into the DB
	 *
	 * @access	protected
	 * @param 	array 	Array of block data
	 * @return	@e int 	New block ID
	 */
	protected function _insertBlockData( $_block )
	{
		unset($_block['block_id']);
		unset($_block['block_cache_last']);
		unset($_block['block_position']);
		unset($_block['block_category']);
		unset($_block['template_name']);
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'block_id', 'from' => 'ccs_blocks', 'where' => "block_key='{$_block['block_key']}'" ) );
		
		//-----------------------------------------
		// Instead of updating, just change key to prevent
		// overwriting someone's configured block
		//-----------------------------------------
		
		if( $check['block_id'] )
		{
			$_block['block_key']	= $_block['block_key'] . md5( uniqid( microtime() ) );
		}
		
		$this->DB->insert( 'ccs_blocks', $_block );
		$_blockId	= $this->DB->getInsertId();

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blockimported'], $_block['block_name'] ) );

		return $_blockId;
	}

	/**
	 * Import a block that contains template data
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _importBlockWithTemplate( $content )
	{
		//-----------------------------------------
		// Get classes for later use
		//-----------------------------------------

		if( !$this->registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$this->registry->setClass( 'ccsFunctions', new $classToLoad( $this->registry ) );
		}

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive = new classXMLArchive();

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		$xmlarchive->readXML( $content );

		foreach( $xmlarchive->asArray() as $fileName => $fileMeta )
		{
			if( $fileName == 'btemplates_data.xml' )
			{
				$templateData = $fileMeta['content'];
			}
			elseif( $fileName == 'blockdata.xml' )
			{
				$blockData = $fileMeta['content'];
			}
			else
			{
				$templateResources[ $fileMeta['path'] ][ $fileMeta['filename'] ] = $fileMeta['content'];
			}
		}

		//-----------------------------------------
		// Make sure we have block data, and if so,
		// prepare it for import
		//-----------------------------------------

		if( !$blockData )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_error_no_block'], '11CCSB014' );
		}

		$xml->loadXML( $blockData );

		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );
		}

		if( !count( $_block ) )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_error_no_block'], '11CCSB015' );
		}

		//-----------------------------------------
		// Send off the templates to be imported
		//-----------------------------------------

		$return = $this->registry->ccsFunctions->importBlockTemplates( $templateData, $templateResources, true );

		if( $return === ccsFunctions::BTEMPLATE_DIR_NOT_WRITABLE )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_block_imp_failed_chmod'], '11CCSB016' );		
		}

		if( count( $return['errored'] ) )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_block_imp_failed'], '11CCSB013' );
		}

		if( count( $return['success'] ) || count( $return['duplicates'] ) )
		{
			// Get ID for the template
			$tpbID = $this->DB->buildAndFetch( array(	'select' => 'tpb_id',
														'from'	=> 'ccs_template_blocks',
														'where'	=> "tpb_name = '{$_block['template_name']}'"
													));

			//-----------------------------------------
			// And now import the block itself
			//-----------------------------------------

			$_block['block_template'] = $tpbID['tpb_id'];
			$this->_insertBlockData( $_block );
			
			//-----------------------------------------
			// Recache the "skin" file and the JS
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache( $engine );

			if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
				$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
			}

			$this->registry->ccsAcpFunctions->recacheResources();

			$this->registry->output->setMessage( $this->lang->words['block_full_import_good'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
		}
	}

	/**
	 * Export a block that has associated templates
	 *
	 * @access	protected
	 * @param 	array 	Block data to export
	 * @return	@e void
	 */
	protected function _exportBlockWithTemplate( $block )
	{
		if( !$block['block_id'] )
		{
			return $this->_exportSingleBlock();
		}

		//-----------------------------------------
		// Get the template for this block
		//-----------------------------------------
		$id = intval( $block['block_template'] );

		$template = $this->DB->buildAndFetch( array(	'select' => '*',
														'from'	=> 'ccs_template_blocks',
														'where' => "tpb_id = {$id}"
												) );

		// Store the template key with the block for easier
		// inserting later
		$block['template_name'] = $template['tpb_name'];

		//-----------------------------------------
		// Pass off to the export handler
		//-----------------------------------------

		if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
		}

		$archive = $this->registry->ccsAcpFunctions->exportBlockTemplates( array( $template ), $block );

		$this->registry->output->showDownload( $archive, 'block_' . $block['block_key'] . '.xml', 'application/xml', false );
	}

	/**
	 * Export a single block record
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _exportSingleBlock()
	{
		$id		= intval($this->request['block']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'], '11CCS14' );
		}
		
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		$config	= unserialize($block['block_config']);
		
		if( !$block['block_id'] )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'], '11CCS15' );
		}

		$template	= array();
		
		switch( $block['block_type'] )
		{
			case 'custom':
				$templateName	= "block__custom_{$block['block_id']}";
				$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$templateName}'" ) );
			break;
			
			case 'feed':
				$_feedConfig	= array();

				if( $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] ) )
				{
					$_feedConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] )->returnFeedInfo();
				}

				if( $_feedConfig['app'] AND !IPSLib::appIsInstalled( $_feedConfig['app'] ) )
				{
					$_feedConfig	= array();
				}

				//-----------------------------------------
				// Gallery template or custom?
				//-----------------------------------------
			
				if( $block['block_template'] )
				{
					return $this->_exportBlockWithTemplate( $block );
				}
				else
				{
					$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" ) );
				}
			break;
			
			case 'plugin':
				$_pluginConfig	= array();

				if( $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' ) )
				{
					$_pluginConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' )->returnPluginInfo();
				}
				
				$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" ) );
			break;
		}

		if( !$template['tpb_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_export_notemplate'], '11CCS16' );
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );

		$xml->newXMLDocument();
		$xml->addElement( 'blockexport' );
		$xml->addElement( 'blockdata', 'blockexport' );
		$xml->addElementAsRecord( 'blockdata', 'block', $block );
		$xml->addElement( 'blocktemplate', 'blockexport' );
		$xml->addElementAsRecord( 'blocktemplate', 'template', $template );
		
		$this->registry->output->showDownload( $xml->fetchDocument(), 'block_' . $block['block_key'] . '.xml', '', 0 );
	}
		
	/**
	 * Export skin templates from a plugin block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _exportBlock()
	{
		$id		= preg_replace( "#[^a-zA-Z0-9_\-]#", "", $this->request['block'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'], '11CCS17' );
		}
		
		//-----------------------------------------
		// Build default block templates for release
		//-----------------------------------------
		
		if( $id == '_all_' )
		{
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();
			$xml->addElement( 'blockexport' );
			$xml->addElement( 'blocktemplate', 'blockexport' );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				if( !preg_match( "/_(\d+)$/", $r['tpb_name'] ) )
				{
					$xml->addElementAsRecord( 'blocktemplate', 'template', $r );
				}
			}

			$this->registry->output->showDownload( $xml->fetchDocument(), 'plugin_block_templates.xml', '', 0 );
			
			exit;
		}
		
		//-----------------------------------------
		// Allow exporting of single block templates
		//-----------------------------------------

		$_pluginConfig	= array();

		if( $this->registry->ccsAcpFunctions->getBlockObject( $id, 'plugin' ) )
		{
			$_pluginConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $id, 'plugin' )->returnPluginInfo();
		}
		
		if( !$_pluginConfig['templateBit'] )
		{
			$this->registry->output->showError( $this->lang->words['nothingto_export'], '11CCS18' );
		}
		
		$template	= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
											)		);

		if( !$template['tpb_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnot_find_export'], '11CCS19' );
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );

		$xml->newXMLDocument();
		$xml->addElement( 'blockexport' );
		$xml->addElement( 'blocktemplate', 'blockexport' );
		$xml->addElementAsRecord( 'blocktemplate', 'template', $template );
		
		$this->registry->output->showDownload( $xml->fetchDocument(), 'block_' . $_pluginConfig['key'] . '.xml', '', 0 );
	}
	
	/**
	 * List the current blocks
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _list()
	{
		//-----------------------------------------
		// Get current blocks
		//-----------------------------------------
		
		$blocks	= array();

		$this->DB->build( array(
								'select'	=> 'b.*',
								'from'		=> array( 'ccs_blocks' => 'b' ),
								'order'		=> 'b.block_position ASC',
								'group'		=> 'b.block_id',
								'add_join'	=> array(
													array(
														'select'	=> "COUNT(r.revision_id) as revisions",
														'from'		=> array( 'ccs_revisions' => 'r' ),
														'where'		=> "r.revision_type='block' AND r.revision_type_id=b.block_id",
														'type'		=> 'left', 
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$blocks[ intval($r['block_category']) ][]	= $r;
		}

		//-----------------------------------------
		// Remove any stale blocks
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_block_wizard', "wizard_started < " . ( time() - ( 6 * 60 * 60 ) ) );
		
		//-----------------------------------------
		// Get block categories
		//-----------------------------------------
		
		$categories	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->listBlocks( $blocks, $categories, $this->registry->ccsAcpFunctions->getPluginBlockConfigs() );
	}
	
	/**
	 * Delete a block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _deleteBlock()
	{
		if( $this->request['type'] == 'wizard' )
		{
			$id	= IPSText::md5Clean( $this->request['block'] );
			
			$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$id}'" );
			
			$this->registry->output->setMessage( $this->lang->words['wsession_deleted'] );
		}
		else
		{
			$id		= intval($this->request['block']);
			$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
			$config	= unserialize( $block['block_config'] );
			
			$this->DB->delete( 'ccs_blocks', 'block_id=' . $id );
			$this->DB->delete( 'ccs_revisions', "revision_type='block' AND revision_type_id=" . $id );
			
			$template	= array();
			
			switch( $block['block_type'] )
			{
				case 'custom':
					$templateName	= "block__custom_{$block['block_id']}";
					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$templateName}'" ) );
				break;
				
				case 'feed':
					$_feedConfig	= array();

					if( $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] ) )
					{
						$_feedConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] )->returnFeedInfo();
					}

					if( $_feedConfig['app'] AND !IPSLib::appIsInstalled( $_feedConfig['app'] ) )
					{
						$_feedConfig	= array();
					}

					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" ) );
				break;
				
				case 'plugin':
					$_pluginConfig	= array();

					if( $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' ) )
					{
						$_pluginConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' )->returnPluginInfo();
					}
					
					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" ) );
				break;
			}

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blockdeleted'], $block['block_name'] ) );
	
			if( $template['tpb_id'] )
			{
				$this->DB->delete( 'ccs_template_blocks', 'tpb_id=' . $template['tpb_id'] );
				
				$this->DB->delete( 'ccs_template_cache', "cache_type='block' AND cache_type_id=" . $template['tpb_id'] );
			}
			
			//-----------------------------------------
			// Clear page caches
			//-----------------------------------------

			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
					
			$this->registry->output->setMessage( $this->lang->words['block_deleted'] );
			
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache();
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Recache a block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _recacheBlock()
	{
		//-----------------------------------------
		// Get skin
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$pageBuilder	= new $classToLoad( $this->registry );
		$pageBuilder->recacheTemplateCache();
		$pageBuilder->loadSkinFile();
		
		$id		= intval($this->request['block']);
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
			
		if( $block['block_id'] )
		{
			if( $block['block_template'] )
			{
				$template		= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> 'ccs_template_blocks', 
															'where'		=> "tpb_id='{$block['block_template']}'" 
													)		);

				$block['tpb_name']	= $template['tpb_name'];
			}

			if( $block['block_type'] AND is_file( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php', "adminBlockHelper_" . $block['block_type'], 'ccs' );
				$extender		= new $classToLoad( $this->registry );
				$extender->recacheBlock( $block );
			}
		}
		
		//-----------------------------------------
		// Clear page caches
		//-----------------------------------------

		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
		
		$this->registry->output->setMessage( $this->lang->words['block_recached'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Recache a block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _recacheAllBlocks()
	{
		//-----------------------------------------
		// Get skin
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$pageBuilder	= new $classToLoad( $this->registry );
		$pageBuilder->recacheTemplateCache();
		$pageBuilder->loadSkinFile();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks' ) );
		$outer = $this->DB->execute();
		
		while( $block = $this->DB->fetch($outer) )
		{
			if( $block['block_template'] )
			{
				$template		= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> 'ccs_template_blocks', 
															'where'		=> "tpb_id='{$block['block_template']}'" 
													)		);

				$block['tpb_name']	= $template['tpb_name'];
			}

			if( $block['block_type'] AND is_file( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php', "adminBlockHelper_" . $block['block_type'], 'ccs' );
				$extender		= new $classToLoad( $this->registry );
				$extender->recacheBlock( $block );
			}
		}
		
		//-----------------------------------------
		// Clear page caches
		//-----------------------------------------

		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
		
		$this->registry->output->setMessage( $this->lang->words['all_blocks_recached'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
}