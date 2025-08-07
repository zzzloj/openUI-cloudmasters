<?php
/**
 * @file		blocks.php 	IP.Content block templates methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		13 Dec 2011
 * $LastChangedDate: 2010-10-14 13:11:17 -0400 (Thu, 14 Oct 2010) $
 * @version		v3.4.5
 * $Revision: 477 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * @class		admin_ccs_templates_blocks
 * @brief		IP.Content block templates methods
 */
class admin_ccs_templates_blocks extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_templates' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=blocks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=blocks';

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
		// What to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'doadd':
				$this->saveTemplate('add');
			break;

			case 'doedit':
				$this->saveTemplate('edit');
			break;

			case 'add':
				$this->templateForm('add');
			break;

			case 'edit':
				$this->templateForm('edit');
			break;

			case 'delete':
				$this->deleteTemplate();
			break;

			case 'predelete':
				$this->preDeleteTemplate();
			break;

			case 'export':
				$this->exportSingleTemplate();
			break;

			case 'import':
				$this->importTemplates();
			break;

			case 'recachejs':
				$this->recacheResources();
			break;

			case 'revert':
				$this->revertTemplate();
			break;

			case 'list':
			default:
				$this->listTemplates();
			break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Recaches the master javascript file
	 *
	 * @return	@e void
	 */
	protected function recacheResources()
	{
		if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
		}

		if( $this->registry->ccsAcpFunctions->recacheResources() )
		{
			$this->registry->output->global_message = $this->lang->words['block_js_recached'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&type=' . $this->request['rtype'] );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['error_block_js_recache'], '11CCSB012' );
		}
	}

	/**
	 * Reverts a default template back to its original state
	 *
	 * @return	@e void
	 */
	protected function revertTemplate()
	{
		$tpb_names	= array();
		$_extra		= '';

		if( IN_DEV && $this->request['revert'] == '_all_' )
		{
			//-----------------------------------------
			// We're reverting all default templates
			//-----------------------------------------

			$this->DB->build( array( 	'select' => '*',
										'from' => 'ccs_template_blocks',
										'where' => "tpb_protected = 1"
									));

			$this->DB->execute();

			while( $tpb = $this->DB->fetch() )
			{
				$tpb_names[] = $tpb['tpb_name'];
			}
		}
		else
		{
			$id = intval( $this->request['tpb_id'] );

			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['btemplate_revert_no_id'], '11CCSB017' );
			}

			//-----------------------------------------
			// Get the block that we're working with
			//-----------------------------------------

			$block = $this->DB->buildAndFetch( array( 	'select' => '*',
														'from'	=> 'ccs_template_blocks',
														'where' => "tpb_id = {$id}"
													) );

			if( !$block )
			{
				$this->registry->output->showError( $this->lang->words['btemplate_revert_no_id'], '11CCSB018' );
			}

			//-----------------------------------------
			// Make sure it's a default one
			//-----------------------------------------

			if( !$block['tpb_protected'] )
			{
				$this->registry->output->showError( $this->lang->words['btemplate_revert_not_default'], '11CCSB019' );
			}

			$tpb_names[] = $block['tpb_name'];

			$_extra		=  '&amp;type=' . $block['tpb_app_type'];
		}

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive = new classXMLArchive();

		//-----------------------------------------
		// We need to find the assets and data for this template
		//-----------------------------------------

		$content = file_get_contents( IPSLib::getAppDir( 'ccs' ) . '/xml/feed_block_templates.xml' );

		if( !$content )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_revert_no_xml'], '11CCSB020' );
		}

		$xmlarchive->readXML( $content );

		foreach( $xmlarchive->asArray() as $fileName => $fileMeta )
		{
			if( $fileName == 'btemplates_data.xml' )
			{
				$templateData = $fileMeta['content'];
			}
			else
			{	
				if( in_array( 'feed__' . $fileMeta['path'], $tpb_names ) )
				{
					$templateResources[ $fileMeta['path'] ][ $fileMeta['filename'] ] = $fileMeta['content'];
				}
			}
		}

		//-----------------------------------------
		// Send them off to be imported
		//-----------------------------------------

		$return = $this->registry->ccsFunctions->importBlockTemplates( $templateData, $templateResources, true, $tpb_names );

		if( count( $return['success'] ) )
		{
			//-----------------------------------------
			// Recache the "skin" file and the JS
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache( $engine );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$acpfunctions			= new $classToLoad( $this->registry );
			$acpfunctions->recacheResources();

			if( count( $tpb_names ) > 1 )
			{
				$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempreverted_p'], count( $tpb_names ) ) );
				$this->registry->output->setMessage( sprintf( $this->lang->words['btemplate_revert_success_p'], count( $return['success'] ) ), 1 );		
			}
			else
			{
				//-----------------------------------------
				// Store revision
				//-----------------------------------------
			
				$_revision	= array(
									'revision_type'		=> 'blocktemplate',
									'revision_type_id'	=> $id,
									'revision_content'	=> $block['tpb_content'],
									'revision_date'		=> time(),
									'revision_member'	=> $this->memberData['member_id'],
									);

				$this->DB->insert( 'ccs_revisions', $_revision );

				$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempreverted'], $block['tpb_human_name'] ) );
				$this->registry->output->setMessage( sprintf( $this->lang->words['btemplate_revert_success'], $block['tpb_human_name'] ), 1 );	
			}
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['btemplate_revert_failed'], '11CCSB021' );
		}
	}

	/**
	 * Imports a template XML file
	 *
	 * @return	@e void
	 */
	protected function importTemplates()
	{
		$duplicates = $errored = $success = array();
		$content = $this->registry->getClass('adminFunctions')->importXml();

		//-----------------------------------------
		// Throw an error if there is nothing to import
		//-----------------------------------------

		if( !$content )
		{
			$this->registry->output->showError( $this->lang->words['notemplate_to_import'], '11CCSB011' );
		}
		
		//-----------------------------------------
		// Get classes for later use
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive = new classXMLArchive();
		
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
			else
			{
				$templateResources[ $fileMeta['path'] ][ $fileMeta['filename'] ] = $fileMeta['content'];
			}
		}

		//-----------------------------------------
		// Send it off to be imported
		//-----------------------------------------

		$return = $this->registry->ccsFunctions->importBlockTemplates( $templateData, $templateResources, true );

		if( $return === ccsFunctions::BTEMPLATE_DIR_NOT_WRITABLE )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_block_imp_failed_chmod'], '11CCSB016.1' );		
		}

		if( count( $return['success'] ) )
		{
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
		}

		//-----------------------------------------
		// Either simple redirect if there's no errors to report
		// or show an overview screen
		//-----------------------------------------

		$_d = count( $return['duplicates'] );
		$_e = count( $return['errored'] );
		$_s = count( $return['success'] );
		$lang_d = $lang_e = '';

		if( $_s && !$_d && !$_e )
		{
			$this->registry->output->setMessage( sprintf( $this->lang->words['btemplates_imported'], $_s ), 1 );
		}
		else
		{
			//-----------------------------------------
			// Any duplicates?
			//-----------------------------------------

			if( $_d > 1 )
			{
				$lang_d = sprintf( $this->lang->words['btemplate_import_dupe_p'], $_d );
			}
			elseif( $_d === 1 )
			{
				$lang_d = $this->lang->words['btemplate_import_dupe_s'];
			}

			//-----------------------------------------
			// Any errored?
			//-----------------------------------------

			if( $_e > 1 )
			{
				$lang_e = sprintf( $this->lang->words['btemplate_import_error_p'], $_e );
			}
			elseif( $_e === 1 )
			{
				$lang_e = $this->lang->words['btemplate_import_error_s'];
			} 

			$this->registry->output->setMessage( sprintf( $this->lang->words['btemplate_import_result'], $_s, $lang_d, $lang_e ), 1 );
		}

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempimport'], $_s ) );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Export a single template
	 *
	 * @param	bool	Whether to honor protected templates or not
	 * @return	@e void
	 */
	protected function exportSingleTemplate( $honorProtected = false )
	{
		if( IN_DEV )
		{
			$honorProtected = true;	
		}

		$outName			= 'feed_block_templates';
		
		if( $this->request['export'] == 'group' )
		{
			//-----------------------------------------
			// Check it's a valid dir
			//-----------------------------------------

			if( $this->request['type'] != '*' )
			{
				if( !$this->registry->ccsAcpFunctions->getBlockObject( $this->request['type'] ) )
				{
					$this->registry->output->showError( $this->lang->words['no_type_provided'], '11CCSB010' );
				}
			}

			$this->DB->build( array( 	'select' 	=> '*',
										'from'		=> 'ccs_template_blocks',
										'where'		=> "tpb_app_type = '{$this->request['type']}'"
									));

			$outName .= '_' . $this->request['type'];
		}
		elseif( $this->request['export'] == '_all_' )
		{
			$this->DB->build( array( 	'select'	=> '*',
										'from'		=> 'ccs_template_blocks',
										'where' 	=> "tpb_app_type != '' AND tpb_content_type != ''"
									));

			$outName .= '_all';
		}
		elseif( $this->request['export'] == '_defaults_' )
		{
			$this->DB->build( array( 	'select'	=> '*',
										'from'		=> 'ccs_template_blocks',
										'where' 	=> "tpb_app_type != '' AND tpb_content_type != '' AND tpb_protected = 1"
									));
		}
		else
		{
			$id = intval( $this->request['tpb_id'] );

			$this->DB->build( array( 	'select'	=> '*',
										'from'		=> 'ccs_template_blocks',
										'where'		=> "tpb_id = {$id}"
									));
		}

		$this->DB->execute();

		//-----------------------------------------
		// Build our array of data, and send it off
		// to be built as an XMLArchive
		//-----------------------------------------

		while( $r = $this->DB->fetch() )
		{
			unset( $r['tpb_id'] );

			if( !$honorProtected )
			{
				$r['tpb_protected'] = 0;
			}

			$records[] = $r;

			if( $this->request['tpb_id'] )
			{
				$outName	= $r['tpb_name'];
			}
		}

		if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
		}

		$archive = $this->registry->ccsAcpFunctions->exportBlockTemplates( $records );

		$this->registry->output->showDownload( $archive, $outName . '.xml', 'application/xml', false );
	}

	/**
	 * Pre-delete logic for templates (either copy to blocks, or change the template used)
	 *
	 * @return	@e void
	 */
	protected function preDeleteTemplate()
	{
		if( !in_array( $this->request['type'], array( 'copy', 'update' ) ) )
		{
			$this->deleteTemplate();
			return;
		}

		$id	= intval( $this->request['tpb_id'] );

		$template = $this->DB->buildAndFetch( array( 	'select' => '*',
														'from'	=> 'ccs_template_blocks',
														'where' => "tpb_id = {$id}"
													));

		//-----------------------------------------
		// We're updating to a different template
		//-----------------------------------------

		if( $this->request['type'] == 'update' )
		{
			if( !$this->request['update_to'] )
			{
				$this->registry->output->showError( $this->lang->words['no_update_btemplate'], '11CCSB007' );
			}

			$this->request['update_to'] = intval( $this->request['update_to'] );
			$alternatives = array();

			//-----------------------------------------
			// Get the new template
			//-----------------------------------------

			$updateTo = $this->DB->buildAndFetch( array( 	'select'	=> '*',
															'from'		=> 'ccs_template_blocks',
															'where'		=> "tpb_app_type = '{$template['tpb_app_type']}' AND tpb_content_type = '{$template['tpb_content_type']}' AND tpb_id = {$this->request['update_to']}"
														) );

			if( !$updateTo )
			{
				$this->registry->output->showError( $this->lang->words['error_update_btemplate'], '11CCSB008' );
			}

			//-----------------------------------------
			// Update blocks
			//-----------------------------------------

			$this->DB->update( 'ccs_blocks', array( 'block_template' => $updateTo['tpb_id'] ), "block_template = {$id}" );

			// Continue...
		}

		/* We're copying it to our blocks */

		elseif( $this->request['type'] == 'copy' )
		{
			//-----------------------------------------
			// Get all blocks that will be updated
			//-----------------------------------------

			$this->DB->build( array( 	'select' => '*',
										'from'	=> 'ccs_blocks',
										'where' => "block_template = {$id}"
									));

			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				$r['_block_config'] = unserialize( $r['block_config'] );
				$blocks[] = $r;
			}

			//-----------------------------------------
			// Load the classes we need
			//-----------------------------------------

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/admin.php', 'adminBlockHelper_feed', 'ccs' );
			$extender		= new $classToLoad( $this->registry );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );

			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
			$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );

			foreach( $blocks as $block )
			{
				//-----------------------------------------
				// STEP 1: Get the data source object & feed info
				//-----------------------------------------

				$block['_feed_info']	= array();

				if( $this->registry->ccsAcpFunctions->getBlockObject( $block['_block_config']['feed'] ) )
				{
					$block['_feed_info']	= $this->registry->ccsAcpFunctions->getBlockObject( $block['_block_config']['feed'] )->returnFeedInfo();

					if( $block['_feed_info']['app'] AND !IPSLib::appIsInstalled( $block['_feed_info']['app'] ) )
					{
						continue;
					}
				}

				if( !count($block['_feed_info']) )
				{
					continue;
				}

				//-----------------------------------------
				// STEP 2: Check if the template exists; insert if not
				//-----------------------------------------

				$t = $this->DB->buildAndFetch( array( 	'select' => '*',
														'from' => 'ccs_template_blocks',
														'where' => "tpb_name = '{$block['_feed_info']['templateBit']}_{$block['block_id']}'"
												));
				
				if( $t['tpb_id'] )
				{
					$this->DB->update( 'ccs_template_blocks', array( 'tpb_content' => $template['tpb_content'] ), "tpb_id = {$t['tpb_id']}" );
				}
				else
				{
					$this->DB->insert( 'ccs_template_blocks', array( 
																	'tpb_name' => $block['_feed_info']['templateBit'] .'_'. $block['block_id'],
																	'tpb_params' => $template['tpb_params'],
																	'tpb_content' => $template['tpb_content']
									)								);

					$t['tpb_id'] = $this->DB->getInsertId();
				}
				
				//-----------------------------------------
				// STEP 3: Add cache entry for template
				//-----------------------------------------

				$cache = array( 'cache_type' => 'block',
								'cache_type_id' => $t['tpb_id'],
								'cache_content' => $engine->convertHtmlToPhp( "{$block['_feed_info']['templateBit']}_{$block['block_id']}", $template['tpb_params'], $template['tpb_content'], '', false, true )
							);

				$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );

				//-----------------------------------------
				// STEP 4: Update the block table
				//-----------------------------------------

				$this->DB->update( 'ccs_blocks', array('block_template' => null), "block_id = {$block['block_id']}" );

				//-----------------------------------------
				// STEP 5: Recache block
				//-----------------------------------------

				$extender->recacheBlock( $block, false, false );
			}

			//-----------------------------------------
			// STEP 5: Recache the master template
			//-----------------------------------------

			$_pagesClass->recacheTemplateCache( $engine );
		}

		//-----------------------------------------
		// And now continue with deleting...
		//-----------------------------------------

		$this->deleteTemplate( true );
		return;
	}

	/**
	 * Deletes a template *or* offers a choice on handling a template in use
	 *
	 * @param	boolean		$bypassCheck		Bypass the 'in use' check, and delete template regardless?
	 * @return	@e void
	 */
	protected function deleteTemplate( $bypassCheck=false )
	{
		$id = intval( $this->request['tpb_id'] );
		
		//-----------------------------------------
		// Get the template
		//-----------------------------------------

		$template = $this->DB->buildAndFetch( array( 	'select' => '*',
														'from'   => 'ccs_template_blocks',
														'where'  => "tpb_id = {$id}"
													));

		if( !$template['tpb_id'] )
		{
			return;
		}

		if( $template['tpb_protected'] )
		{
			$this->registry->output->showError( $this->lang->words['btemplate_protected'], '11CCSB009' );
		}

		//-----------------------------------------
		// Make sure it's not in use, if necessary
		//-----------------------------------------

		if( !$bypassCheck )
		{
			$count = $this->DB->buildAndFetch( array(	'select' => "COUNT(block_id) as total",
														'from'   => 'ccs_blocks',
														'where'  => "block_template={$id}"
													));

			if( $count['total'] )
			{
				$alternatives = array();

				// Get similar templates
				$this->DB->build( array( 	'select' => "tpb_id, tpb_name, tpb_human_name",
											'from'   => 'ccs_template_blocks',
											'where'  => "tpb_app_type='{$template['tpb_app_type']}' AND tpb_content_type='{$template['tpb_content_type']}' AND tpb_id != {$id}"
										));
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$alternatives[] = $r;
				}

				$this->registry->output->html .= $this->html->deleteTemplateForm( $count['total'], $id, $alternatives );
				return;
			}
		}

		//-----------------------------------------
		// Do the deletings
		//-----------------------------------------

		$this->DB->delete( 'ccs_template_blocks', "tpb_id={$id}" );
		$this->DB->delete( 'ccs_revisions', "revision_type='blocktemplate' AND revision_type_id=" . $id );
		$this->DB->delete( 'ccs_template_cache',  "cache_type='block' AND cache_type_id={$id}" );
		$this->postProcessDeleteImage( $template['tpb_image'] );

		//-----------------------------------------
		// Delete assets directory
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$fileManagement	= new $classToLoad();
		$fileManagement->removeDirectory( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks/' . $template['tpb_name'] );

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );
		
		//-----------------------------------------
		// Set message and go back to list
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempdeleted'], $template['tpb_name'] ) );

		$this->registry->output->global_message = $this->lang->words['btemplate_deleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $template['tpb_app_type'] );
	}

	/**
	 * Saves a template
	 *
	 * @param	string		$type		Add|Edit template
	 * @return	@e void
	 */
	protected function saveTemplate( $type='add' )
	{
		$blockDir = DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks';

		if ( $this->request['request_method'] != 'post' )
		{
			return $this->templateForm( $type );
		}
		
		$id	= 0;

		if( $type == 'edit' )
		{
			//-----------------------------------------
			// Get the current record
			//-----------------------------------------

			$id	= intval($this->request['template']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'], '11CCSB001' );
			}

			$currentTemplate = $this->DB->buildAndFetch( array(	'select' => '*',
																'from'	 => 'ccs_template_blocks',
																'where'	 => 'tpb_id = ' . $id
															) );
			
			//-----------------------------------------
			// If the app or content type has changed,
			// update the tpb_name
			//-----------------------------------------

			if( ($this->request['tpb_app_types'] == $currentTemplate['tpb_app_type']) && ( $this->request['tpb_content_types'] == $currentTemplate['tpb_content_type'] ) )
			{
				$templateName = $currentTemplate['tpb_name'];
			}
			else
			{
				$templateName = "feed__" . $this->request['tpb_app_types'] . '_' . $this->request['tpb_content_types'] . '_' . time();

				// Keep this for later
				$renamedFrom = $currentTemplate['tpb_name'];
			}
		}
		else
		{
			/* Retain dir if we are duplicating */
			$templateName	= ( $this->request['duplicate'] && $this->request['_assetdir'] ) ? $this->request['_assetdir'] : "feed__" . $this->request['tpb_app_types'] . '_' . $this->request['tpb_content_types'] . '_' . IPS_UNIX_TIME_NOW;

			if( $this->request['duplicate'] )
			{
				$id	= intval($this->request['duplicate']);

				$currentTemplate = $this->DB->buildAndFetch( array(	'select' => '*',
																	'from'	 => 'ccs_template_blocks',
																	'where'	 => 'tpb_id = ' . $id
																) );
			}
		}

		$save	= array(
						'tpb_human_name'	=> trim($this->request['tpb_human_name']),
						'tpb_name'			=> str_replace('*', 'all', $templateName),
						'tpb_desc'			=> trim($this->request['tpb_desc']),
						'tpb_app_type'		=> trim($this->request['tpb_app_types']),
						'tpb_content_type'	=> trim($this->request['tpb_content_types']),
						'tpb_protected'		=> 0,
						'tpb_params'		=> "\$title='', \$records=array(), \$params=array()",
						'tpb_content'		=> str_replace( '&#46;&#46;/', '../', str_replace( '&#092;', '\\', trim($_POST['tpb_content']) ) )
					);

		//-----------------------------------------
		// Only care about protected status when INDEV
		//-----------------------------------------

		if( IN_DEV && isset( $this->request['tpb_protected'] ) )
		{
			$save['tpb_protected'] = $this->request['tpb_protected'];
		}

		//-----------------------------------------
		// Test the syntax
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		
		$_TEST	= $engine->convertHtmlToPhp( 'test__whatever', '$test', $_POST['tpb_content'], '', false, true );
		
		ob_start();
		eval( $_TEST );
		$_RETURN = ob_get_contents();
		ob_end_clean();
		
		if( $_RETURN )
		{
			$this->registry->output->global_error	= $this->lang->words['btemplate_invalid_syntax'];
			$this->templateForm( $type );
			return;
		}

		//-----------------------------------------
		// Template image
		//-----------------------------------------

		if ( !empty($_FILES['FILE_UPLOAD']['name']) && !empty($_FILES['FILE_UPLOAD']['size']) && $_FILES['FILE_UPLOAD']['name'] != 'none' )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
			$upload = new $classToLoad();
			
			$upload->out_file_dir		= $this->defaultPath . $path;
			$upload->max_file_size		= 1000000000;
			$upload->out_file_name		= md5( uniqid( microtime(), true ) );
			$upload->out_file_dir		= $this->settings['upload_dir'] . '/content_templates/';
			$upload->upload_form_field	= 'FILE_UPLOAD';
			$upload->allowed_file_ext	= array('png', 'jpg', 'jpeg', 'gif');
			$upload->make_script_safe	= 1;			
			
			$upload->process();

			if( $upload->error_no && $upload->error_no > 1 )
			{
				$this->registry->output->showError( $this->lang->words['field_upload_error__' . $upload->error_no ], '11CCSB002' );
			}

			if( $upload->error_no !== 1 )
			{
				if( !empty($currentTemplate['tpb_image']) )
				{
					$this->postProcessDeleteImage( $currentTemplate['tpb_image'] );
				}

				// Check image size
				require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
				$image = ips_kernel_image::bootstrap();

				$attributes = $image->extractImageData( $this->settings['upload_dir'] . '/content_templates/' . $upload->parsed_file_name );

				if( $attributes['width'] != 150 || $attributes['height'] != 100 )
				{
					$this->registry->output->global_error	= $this->lang->words['wrong_btemplate_img_size'];
					$this->templateForm( $type );
					return;
				}

				// S'all good in the hood
				$save['tpb_image'] = $upload->parsed_file_name;
			}
		}
		elseif( $type == 'edit' AND $this->request['remove_thumb'] == '1' )
		{
			$this->postProcessDeleteImage( $currentTemplate['tpb_image'] );
		}

		//-----------------------------------------
		// Put it in the DB
		//-----------------------------------------
	
		if( $type == 'edit' && $currentTemplate['tpb_id'] )
		{
			//-----------------------------------------
			// Store a revision first but only if content has changed
			//-----------------------------------------

			if( $save['tpb_content'] != $currentTemplate['tpb_content'] )
			{
				$_revision	= array(
									'revision_type'		=> 'blocktemplate',
									'revision_type_id'	=> $currentTemplate['tpb_id'],
									'revision_content'	=> $currentTemplate['tpb_content'],
									'revision_date'		=> time(),
									'revision_member'	=> $this->memberData['member_id'],
									);
	
				$this->DB->insert( 'ccs_revisions', $_revision );
			}

			$this->DB->update( 'ccs_template_blocks', $save, "tpb_id = {$currentTemplate['tpb_id']}" );
			$this->request['template'] = $currentTemplate['tpb_id'];

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempedited'], $save['tpb_name'] ) );
		}
		else
		{
			if( $this->request['duplicate'] )
			{
				if( $currentTemplate['tpb_image'] && !$this->request['remove_thumb'] )
				{
					$newFileName	= md5( time() . $defaults['tpb_image'] . $this->memberData['member_id'] )  . '.' . IPSText::getFileExtension( $currentTemplate['tpb_image'] );

					@copy( $this->settings['upload_dir'] . '/content_templates/' . $currentTemplate['tpb_image'], $this->settings['upload_dir'] . '/content_templates/' . $newFileName );

					$save['tpb_image']	= $newFileName;
				}

				unset($this->request['duplicate']);
			}

			$this->DB->insert( 'ccs_template_blocks', $save );
			
			$id	= $this->DB->getInsertId();
			
			$this->request['template']	= $id;

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_btempadded'], $save['tpb_name'] ) );
		}

		//-----------------------------------------
		// Set up assets folder
		//-----------------------------------------

		$folderName = str_replace( 'feed__', '', $save['tpb_name'] );

		if( !file_exists( $blockDir .'/'. $folderName ) )
		{
			if( $renamedFrom )
			{
				@rename( $blockDir .'/'. str_replace('feed__', '', $renamedFrom), $blockDir . '/' . $foldername );
			}
			else if( $type == 'add' AND $currentTemplate['tpb_name'] AND $this->request['_assetdir'] )
			{
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
				$fileManagement	= new $classToLoad();
				$fileManagement->copyDirectory( $blockDir . '/' . str_replace( 'feed__', '', $currentTemplate['tpb_name'] ), $blockDir . '/' . $templateName );
			}
			else
			{
				if( @mkdir( $blockDir . '/' . $folderName, IPS_FOLDER_PERMISSION ) )
				{
					@chmod( $blockDir . '/' . $folderName, IPS_FOLDER_PERMISSION );
					@file_put_contents( $blockDir . '/' . $folderName . '/index.html', '' );
				}
			}
		}

		//-----------------------------------------
		// Cache it
		//-----------------------------------------

		if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
		}

		$this->registry->ccsAcpFunctions->recacheResources();

		$cache	= array(
						'cache_type'	=> 'block',
						'cache_type_id'	=> $id,
						);
		
		$cache['cache_content']	= $engine->convertHtmlToPhp( $save['tpb_name'], $save['tpb_params'], $_POST['tpb_content'], '', false, true );
		
		$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );

		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->templateForm( 'edit' );
			return;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		if( $type == 'edit' )
		{
			$this->registry->output->global_message = $this->lang->words['btemplate_edited'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['btemplate_added'];
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $save['tpb_app_type'] );
	}

	/**
	 * Delete an existing image
	 *
	 * @param	string		$imageFile		Image file name
	 * @return	@e true
	 */
	protected function postProcessDeleteImage( $imageFile )
	{
		if( $imageFile && is_file( $this->settings['upload_dir'] . '/content_templates/' . $imageFile ) )
		{
			@unlink( $this->settings['upload_dir'] . '/content_templates/' . $imageFile );
		}

		return true;
	}

	/**
	 * Show form for adding/editing templates
	 *
	 * @param	string		$type		Add|Edit form template
	 * @return	@e void
	 */
	protected function templateForm( $type='add' )
	{
		$form = array();

		//-----------------------------------------
		// Set up for add/edit
		//-----------------------------------------

		if( $type == 'add' )
		{
			$title = $this->lang->words['block_template_add_title'];

			//-----------------------------------------
			// Duplicate the data
			//-----------------------------------------

			if( $this->request['duplicate'] )
			{
				$defaults = $this->DB->buildAndFetch( array( 'select' => '*',
															 'from'   => 'ccs_template_blocks',
															 'where'  => 'tpb_id=' . intval($this->request['duplicate'])
													 )		);

				unset($defaults['tpb_id']);
				unset($defaults['tpb_protected']);

				$form['image']					= $this->settings['upload_url'] . '/content_templates/' . $defaults['tpb_image'];
				$form['_assetdir']				= str_replace('feed__', '', preg_replace( "/_(\d+)$/", "_" . IPS_UNIX_TIME_NOW, $defaults['tpb_name'] ) );
				$form['assets_dir']				= PUBLIC_DIRECTORY . '/ipc_blocks/' . $form['_assetdir'];
				$this->request['tpb_app_types']	= $defaults['tpb_app_type'];
				$defaults['tpb_human_name']		= sprintf( $this->lang->words['duplicateblock_namesuffix'], $defaults['tpb_human_name'] );
			}
		}
		else
		{
			$id = intval( $this->request['template'] );

			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'], '11CCSB003' );
			}

			$title = $this->lang->words['block_template_edit_title'];

			//-----------------------------------------
			// Load existing template
			//-----------------------------------------

			$defaults = $this->DB->buildAndFetch( array( 'select' => '*',
														 'from'   => 'ccs_template_blocks',
														 'where'  => 'tpb_id=' . $id
												 )		);

			if( !$defaults['tpb_id'] )
			{
				$this->registry->output->showError( $this->lang->words['invalid_btemplate_id'], '11CCSB004' );
			}

			if( $defaults['tpb_image'] )
			{
				$form['image']	= $this->settings['upload_url'] . '/content_templates/' . $defaults['tpb_image'];
			}

			if( file_exists( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks/' . str_replace('feed__', '', $defaults['tpb_name']) ) )
			{
				$form['assets_dir'] = PUBLIC_DIRECTORY . '/ipc_blocks/' . str_replace('feed__', '', $defaults['tpb_name']);
			}
		}

		//-----------------------------------------
		// Set up feed types
		//-----------------------------------------

		$feedTypes = $this->registry->ccsAcpFunctions->getFeedBlockConfigs();

		$feedNames = array( 
							array('', $this->lang->words['feed_template_choose'] ),
							array('*', $this->lang->words['feed_template_generic'] ) 
							);

		foreach( $feedTypes as $idx => $feed )
		{
			$feedNames[] = array( $feed['key'], $feed['name'] );
		}

		//-----------------------------------------
		// Get the content types
		//-----------------------------------------	

		if( ( $type == 'edit' && $defaults['tpb_app_type'] != '*' ) || ( $this->request['tpb_app_types'] && $this->request['tpb_app_types'] != '*' ) )
		{
			$contentTypes	= array();
			$toLoad			= ( $this->request['tpb_app_types'] ) ? $this->request['tpb_app_types'] : $defaults['tpb_app_type'];

			if( $this->registry->ccsAcpFunctions->getBlockObject( $toLoad ) )
			{
				$contentTypes	= $this->registry->ccsAcpFunctions->getBlockObject( $toLoad )->returnContentTypes( array(), false );
			}

			$form['content_types']		= $this->registry->output->formDropdown( 'tpb_content_types', $contentTypes, $this->request['tpb_content_types'] ? $this->request['tpb_content_types'] : $defaults['tpb_content_type'] );
		}
		else if( $type == 'edit' && $defaults['tpb_app_type'] == '*' )
		{
			$contentTypes			= $this->registry->ccsAcpFunctions->getGenericContentTypes();
			$form['content_types'] 	= $this->registry->output->formDropdown( 'tpb_content_types', $contentTypes, $this->request['tpb_content_types'] ? $this->request['tpb_content_types'] : $defaults['tpb_content_type'] );
		}
		else
		{
			$contentTypes			= $this->registry->ccsAcpFunctions->getGenericContentTypes();
			$form['content_types'] 	= "<select id='tpb_content_types' name='tpb_content_types' disabled='disabled'></select>";
		}		
			
		//-----------------------------------------
		// Build form controls
		//-----------------------------------------

		if( IN_DEV )
		{
			$form['protected'] = $this->registry->output->formCheckbox( 'tpb_protected', $this->request['tpb_protected'] ? $this->request['tpb_protected'] : $defaults['tpb_protected'] );
		}

		$form['name']			= $this->registry->output->formInput( 'tpb_human_name', $this->request['tpb_human_name'] ? $this->request['tpb_human_name'] : $defaults['tpb_human_name'] );
		$form['app_types']		= $this->registry->output->formDropdown( 'tpb_app_types', $feedNames, $this->request['tpb_app_types'] ? $this->request['tpb_app_types'] : $defaults['tpb_app_type'] );
		$form['description']	= $this->registry->output->formTextarea( 'tpb_desc', $this->request['tpb_desc'] ? $this->request['tpb_desc'] : IPSText::br2nl( $defaults['tpb_desc'] ), 75, 3 );
		$form['upload'] 		= $this->registry->output->formUpload( 'FILE_UPLOAD' );
		$form['content']		= $this->registry->output->formTextarea( 'tpb_content', IPSText::htmlspecialchars( str_replace( '&#092;', '\\', $_POST['tpb_content'] ? $_POST['tpb_content'] : $defaults['tpb_content'] ) ), 75, 30, 'tpb_content', "style='width:100%;'" );

		$this->registry->output->extra_nav[] = array( '', $type == 'edit' ? $this->lang->words['editing_a_template'] : $this->lang->words['adding_a_template'] );
		$this->registry->output->html .= $this->html->blockTemplateForm( $title, ( $type == 'edit' ) ? 'doedit' : 'doadd', $form );

		//-----------------------------------------
		// WYSIWYG editing
		//-----------------------------------------

		$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->html .= $_globalHtml->getWysiwyg( 'tpb_content' );
	}

	/**
	 * List the block templates
	 *
	 * @return	@e void
	 */
	protected function listTemplates()
	{
		$this->DB->build( array(
							'select'	=> 't.tpb_id, t.tpb_name, t.tpb_desc, t.tpb_human_name, t.tpb_app_type, t.tpb_content_type, t.tpb_position, t.tpb_image, t.tpb_protected',
							'from'		=> array( 'ccs_template_blocks' => 't' ),
							'where'		=> 't.tpb_app_type != ""',
							'order'		=> 't.tpb_position ASC',
							'group'		=> 't.tpb_id',
							'add_join'	=> array(
												array(
													'select'	=> 'COUNT(b.block_template) as used_count',
													'from'		=> array( 'ccs_blocks' => 'b' ),
													'where'		=> 't.tpb_id=b.block_template',
													'type'		=> 'left',
													),
												)
						)		);

		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$_rev			= $this->DB->buildAndFetch( array( 'select' => 'count(*) as revisions', 'from' => 'ccs_revisions', 'where' => "revision_type='blocktemplate' AND revision_type_id={$r['tpb_id']}" ) );

			$r['revisions']	= $_rev['revisions'];
			$r['tpb_image'] = $this->settings['upload_url'] . '/content_templates/' . ( $r['tpb_image'] ? $r['tpb_image'] : 'default.png' );

			if( $r['tpb_app_type'] == '*' )
			{
				$r['tpb_app_type'] = '__all';
			}

			$blocks[ $r['tpb_app_type'] ][] = $r;
		}

		//-----------------------------------------
		// Get feed types
		//-----------------------------------------

		$tmp = $this->registry->ccsAcpFunctions->getFeedBlockConfigs();

		foreach( $tmp as $_feed )
		{
			$feedTypes[ $_feed['key'] ] = $_feed['name'];
		}

		ksort( $feedTypes );

		//-----------------------------------------
		// Add generic feed type
		//-----------------------------------------

		$feedTypes['__all']	= $this->lang->words['generic_btemplates'];

		$this->registry->output->html .= $this->html->blockTemplateList( $blocks, $feedTypes );
	}
}