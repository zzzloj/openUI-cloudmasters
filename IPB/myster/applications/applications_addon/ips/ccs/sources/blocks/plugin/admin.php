<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS plugins block type admin plugin
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class adminBlockHelper_plugin extends adminBlockHelper implements adminBlockHelperInterface
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		if( IN_ACP )
		{
			//-----------------------------------------
			// Load HTML
			//-----------------------------------------
			
			$this->html = $registry->output->loadTemplate( 'cp_skin_blocks_plugin' );
		}
		
		parent::__construct( $registry );
		
		//-----------------------------------------
		// Get interface
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );/*noLibHook*/
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Extra block info (type)
	 * @return	array
	 */
	public function getTags( $info )
	{
		//-----------------------------------------
		// Info should be the plugin we are setting up
		//-----------------------------------------
		
		if( $info )
		{
			if( $this->registry->ccsAcpFunctions->getBlockObject( $info, 'plugin' ) )
			{
				return $this->registry->ccsAcpFunctions->getBlockObject( $info, 'plugin' )->getTags();
			}
		}

		return array();
	}

	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig() 
	{
		return array( 'plugin', $this->registry->class_localization->words['block_type__plugin'] );
	}

	/**
	 * Wizard launcher.  Should determine the next step necessary and act appropriately.
	 *
	 * @access	public
	 * @param	array 				Session data
	 * @return	string				HTML to output to screen
	 */
	public function returnNextStep( $session ) 
	{
		$session['config_data']	= unserialize( $session['wizard_config'] );
		$session['wizard_step']	= $this->request['step'] ? $this->request['step'] : 1;

		if( $session['wizard_step'] > 1 AND !$this->request['continuing'] AND !$this->request['_jump'] )
		{
			$session	= $this->_storeSubmittedData( $session );
		}

		$newStep	= $session['wizard_step'] + 1;
		$html		= '';
		
		if( $newStep > 2 )
		{
			$_pluginConfig	= array();

			if( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['plugin_type'], 'plugin' ) )
			{
				$_pluginConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['plugin_type'], 'plugin' )->returnPluginInfo();
			}
		}
		
		if( $newStep > 2 AND !$_pluginConfig['hasConfig'] )
		{
			$this->html->inactiveSteps	= array( 4 );
		}

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Allow to select plugin type
			//-----------------------------------------
			
			case 2:
				$html	= $this->html->plugin__wizard_2( $session, $this->registry->ccsAcpFunctions->getPluginBlockConfigs() );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name']; //$this->lang->words["plugin_name__{$session['config_data']['plugin_type']}"];
				$session['config_data']['description']	= $session['config_data']['description'] ? $session['config_data']['description'] : ( $session['config_data']['block_id'] ? '' : $this->lang->words["plugin_description__{$session['config_data']['plugin_type']}"] );
				$session['config_data']['hide_empty']	= $session['config_data']['hide_empty'] ? $session['config_data']['hide_empty'] : 0;

				//-----------------------------------------
				// Category, if available
				//-----------------------------------------

				$categories				= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$categories[]	= array( $r['container_id'], $r['container_name'] );
				}
				
				if( count($categories) )
				{
					array_unshift( $categories, array( '0', $this->lang->words['no_selected_cat'] ) );
				}

				$html	= $this->html->plugin__wizard_3( $session, $categories );
			break;
			
			//-----------------------------------------
			// Step 4: Custom configuration options
			//-----------------------------------------
			
			case 4:
				if( !$this->_saveAndGo( $session, 4 ) )
				{
					$formData	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['plugin_type'], 'plugin' )->returnPluginConfig( $session );
					$html		= $this->html->plugin__wizard_4( $session, $formData );
				}
			break;

			//-----------------------------------------
			// Step 5: Edit the HTML template
			//-----------------------------------------
			
			case 5:
				if( !$this->_saveAndGo( $session, 5 ) )
				{
					$template		= array();
					
					if( $session['config_data']['block_id'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$session['config_data']['block_id']}'" 
															)		);
					}
	
					if( !$template['tpb_content'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
															)		);
					}
	
					$editor_area	= $this->registry->output->formTextarea( "custom_template", IPSText::htmlspecialchars($template['tpb_content']), 100, 30, "custom_template", "style='width:100%;'" );
					
					//-----------------------------------------
					// WYSIWYG editing
					//-----------------------------------------
	
					$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
					
					$this->registry->output->html .= $_globalHtml->getWysiwyg( 'custom_template' );

					$html	= $this->html->plugin__wizard_5( $session, $editor_area );
				}
			break;

			//-----------------------------------------
			// Step 6: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 6:
				//-----------------------------------------
				// Test the syntax
				//-----------------------------------------
				
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
				$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );

				$_TEST	= $engine->convertHtmlToPhp( 'test__whatever', '$test', str_replace( "&#092;", '\\', $_POST['custom_template'] ), '', false, true );
				
				ob_start();
				eval( $_TEST );
				$_RETURN = ob_get_contents();
				ob_end_clean();
				
				if( $_RETURN )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error	= $this->lang->words['bad_template_syntax'];
					
					$editor_area	= $this->registry->output->formTextarea( "custom_template", IPSText::htmlspecialchars( str_replace( "&#092;", '\\', $_POST['custom_template'] ) ), 100, 30, "custom_template", "style='width:100%;'" );
					
					//-----------------------------------------
					// WYSIWYG editing
					//-----------------------------------------
	
					$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
					
					$this->registry->output->html .= $_globalHtml->getWysiwyg( 'custom_template' );
					
					$html	= $this->html->plugin__wizard_5( $session, $editor_area );
					break;
				}

				//-----------------------------------------
				// Save block
				//-----------------------------------------
				
				$block	= $this->_saveBlock( $session );
				
				//-----------------------------------------
				// Save the template
				//-----------------------------------------
				
				if( $this->request['request_method'] == 'post' )
				{
					$templateHTML	= str_replace( "&#092;", '\\', $_POST['custom_template'] );
			
					$template		= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" 
														)		);
			
					if( $template['tpb_id'] )
					{
						$this->DB->update( 'ccs_template_blocks', array( 'tpb_content' => $templateHTML ), 'tpb_id=' . $template['tpb_id'] );
					}
					else
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
															)		);
			
						$this->DB->insert( 'ccs_template_blocks', 
											array( 
												'tpb_name'		=> "{$_pluginConfig['templateBit']}_{$block['block_id']}",
												'tpb_content'	=> $templateHTML ,
												'tpb_params'	=> $template['tpb_params'],
												)
										);
						$template['tpb_id']	= $this->DB->getInsertId();
					}
				}
				else
				{
					$template		= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" 
														)		);
			
					if( !$template['tpb_id'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
															)		);
					}
				}
		
				$cache	= array(
								'cache_type'	=> 'block',
								'cache_type_id'	=> $template['tpb_id'],
								);
		
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
				$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
				$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_pluginConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
				
				$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );
		
				//-----------------------------------------
				// Recache the "skin" file
				//-----------------------------------------
				
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
				$_pagesClass	= new $classToLoad( $this->registry );
				$_pagesClass->recacheTemplateCache( $engine );
				
				//-----------------------------------------
				// Recache block
				//-----------------------------------------
				
				if( $block['block_cache_ttl'] )
				{
					$block['block_cache_output']	= $this->recacheBlock( $block );
					$block['block_cache_last']		= time();
				}
				
				//-----------------------------------------
				// Delete wizard session and show done screen
				//-----------------------------------------

				if( $session['config_data']['block_id'] AND $this->request['save_button'] )
				{
					$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$this->registry->output->setMessage( $this->lang->words['block_q_edit_saved'] );
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=blocks' );
				}
				else if( $session['config_data']['block_id'] AND $this->request['save_and_reload'] )
				{
					$this->registry->output->setMessage( $this->lang->words['block_q_edit_saved'] );
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=5&_jump=1' );
				}
				else
				{
					$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$html	= $this->html->plugin__wizard_DONE( $block );
				}
			break;
		}
		
		return $html;
	}

	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	bool				Cache done successfully
	 */
	public function recacheBlock( $block, $return=false )
	{
		if( $block['block_cache_ttl'] AND $block['block_cache_last'] > ( time() - ( $block['block_cache_ttl'] * 60 ) ) )
		{
			$content	= $block['block_cache_output'];
		}
		else
		{
			//-----------------------------------------
			// Load skin in case it's needed
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$pageBuilder->loadSkinFile();
			
			$config		= unserialize( $block['block_config'] );
			$content	= '';

			if( !$this->registry->isClassLoaded( 'ccsAcpFunctions' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
				$this->registry->setClass( 'ccsAcpFunctions', new $classToLoad( $this->registry ) );
			}

			if( $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' ) )
			{
				$content	= $this->registry->ccsAcpFunctions->getBlockObject( $config['plugin'], 'plugin' )->executePlugin( $block );
			}
	
			if( !$return )
			{
				$this->DB->update( 'ccs_blocks', array( 'block_cache_output' => $content, 'block_cache_last' => time() ), 'block_id=' . intval($block['block_id']) );
			}
		}
		
		return $content;
	}
	
	/**
	 * Store data to initiate a wizard session based on given block table data
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	array 				Data to store for wizard session
	 */
	public function createWizardSession( $block )
	{
		$config		= unserialize( $block['block_config'] );
		$session	= array(
							'wizard_id'		=> md5( uniqid( microtime(), true ) ),
							'wizard_step'	=> 1,
							'wizard_type'	=> 'plugin',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'plugin_type'	=> $config['plugin'],
															'custom_config'	=> $config['custom'],
															'hide_empty'	=> $config['hide_empty'],
															'title'			=> $block['block_name'],
															'key'			=> $block['block_key'],
															'description'	=> $block['block_description'],
															'cache_ttl'		=> $block['block_cache_ttl'],
															'block_id'		=> $block['block_id'],
															'category'		=> $block['block_category'],
															)
														)
							);

		return $session;
	}
	
	/**
	 * Store the data submitted for the last step
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Session data (updated)
	 */
	protected function _storeSubmittedData( $session )
	{
		switch( $session['wizard_step'] )
		{
			case 2:
				if( !$this->request['plugin_type'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error = $this->registry->class_localization->words['noblock_plugin_error'];
					
					return $session;
				}

				$session['config_data']['plugin_type']	= $this->request['plugin_type'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$_auto	= false;
				
				if( !$this->request['plugin_key'] )
				{
					$this->request['plugin_key']	= strtolower( str_replace( ' ', '_', $this->request['plugin_title'] ) );
					$_auto							= true;
				}
				
				$session['config_data']['title']		= $this->request['plugin_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['plugin_key'] );
				$session['config_data']['description']	= $this->request['plugin_description'];
				$session['config_data']['hide_empty']	= $this->request['hide_empty'];
				$session['config_data']['cache_ttl']	= trim($this->request['cache_ttl']);

				//-----------------------------------------
				// Make sure block key isn't taken
				//-----------------------------------------
				
				if( !$session['config_data']['title'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error = $this->registry->class_localization->words['block_name_is_required'];
					
					return $session;
				}
				
				if( !$session['config_data']['key'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error = $this->registry->class_localization->words['block_key_is_required'];
					
					return $session;
				}
				
				$where	= "block_key='{$session['config_data']['key']}'";
				
				if( $session['config_data']['block_id'] )
				{
					$where .= " AND block_id<>" . $session['config_data']['block_id'];
				}
				
				$check	= $this->DB->buildAndFetch( array( 'select' => 'block_id', 'from' => 'ccs_blocks', 'where' => $where ) );
				
				if( $check['block_id'] AND !$_auto )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error = $this->registry->class_localization->words['block_key_in_use'];
					
					return $session;
				}
				else if( $check['block_id'] AND $_auto )
				{
					$session['config_data']['key']	= $session['config_data']['key'] . '_' . IPS_UNIX_TIME_NOW;
				}
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 4:
				$validateResult	= array();

				if( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['plugin_type'], 'plugin' ) )
				{
					$validateResult	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['plugin_type'], 'plugin' )->validatePluginConfig( $this->request );
				}
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['plugin_not_validated'], '10CCS93' );
				}

				$session['config_data']['custom_config']	= $validateResult[1];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
		}
		
		return $session;
	}

	/**
	 * Save the block to the DB
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Block data
	 */
	protected function _saveBlock( $session )
	{
		$block	= array(
						'block_active'		=> 1,
						'block_name'		=> $session['config_data']['title'],
						'block_key'			=> $session['config_data']['key'],
						'block_description'	=> $session['config_data']['description'],
						'block_type'		=> 'plugin',
						'block_content'		=> '',
						'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
						'block_category'	=> $session['config_data']['category'],
						'block_config'		=> serialize( 
														array( 
															'plugin'		=> $session['config_data']['plugin_type'],
															'custom'		=> $session['config_data']['custom_config'],
															'hide_empty'	=> $session['config_data']['hide_empty'],
															) 
														)
						);
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blocksaved'], $block['block_name'] ) );

		return $this->_insertOrUpdate( $session, $block );
	}
}