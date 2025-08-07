<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS feeds block type admin plugin
 * Last Updated: $Date: 2012-02-15 10:55:57 -0500 (Wed, 15 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10303 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class adminBlockHelper_feed extends adminBlockHelper implements adminBlockHelperInterface
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
			
			$this->html = $registry->output->loadTemplate( 'cp_skin_blocks_feed' );
		}
		
		parent::__construct( $registry );
		
		//-----------------------------------------
		// Get interface
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );/*noLibHook*/
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
		// Info should be "plugin,database id;content type"
		//-----------------------------------------
		
		$_pieces	= explode( ",", $info );
		
		if( $_pieces[0] )
		{
			if( $this->registry->ccsAcpFunctions->getBlockObject( $_pieces[0] ) )
			{
				return $this->registry->ccsAcpFunctions->getBlockObject( $_pieces[0] )->getTags( $_pieces[1] );
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
		return array( 'feed', $this->registry->class_localization->words['block_type__feed'] );
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
			$_feedConfig	= array();

			if( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] ) )
			{
				$_feedConfig	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->returnFeedInfo();

				if( $_feedConfig['app'] AND !IPSLib::appIsInstalled( $_feedConfig['app'] ) )
				{
					$_feedConfig	= array();
				}
			}
		}
		
		$this->html->inactiveSteps	= $_feedConfig['inactiveSteps'];

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Select feed type
			//-----------------------------------------
			
			case 2:
				$_feedTypes	= $this->registry->ccsAcpFunctions->getFeedBlockConfigs();

				$html	= $this->html->feed__wizard_2( $session, $_feedTypes );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name']; //$this->lang->words["feed_name__{$session['config_data']['feed_type']}"];
				$session['config_data']['description']	= $session['config_data']['description'] ? $session['config_data']['description'] : ( $session['config_data']['block_id'] ? '' : $this->lang->words["feed_description__{$session['config_data']['feed_type']}"] );
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

				$html	= $this->html->feed__wizard_3( $session, $categories );
			break;
			
			//-----------------------------------------
			// Step 4: Content types available from the feed
			//-----------------------------------------
			
			case 4:
				if( !$this->_saveAndGo( $session, 4 ) )
				{
					$formData	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->returnContentTypes( $session );
					$html		= $this->html->feed__wizard_4( $session, $formData );
				}
			break;

			//-----------------------------------------
			// Step 5: Filter types available
			//-----------------------------------------
			
			case 5:
				if( !$this->_saveAndGo( $session, 5 ) )
				{
					$formData	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->returnFilters( $session );
					$formData1	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->returnOrdering( $session );
					$html		= $this->html->feed__wizard_5( $session, $formData, $formData1 );
				}
			break;

			//-----------------------------------------
			// Step 6: Edit the HTML template
			//-----------------------------------------
			
			case 6:
				if( !$this->_saveAndGo( $session, 6 ) )
				{
					$template		= array();
					$feedTemplates	= array();

					//-----------------------------------------
					// Fetch possible feed templates
					//-----------------------------------------

					$galleryKeys = $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->returnTemplateGalleryKeys( $session['config_data']['feed_type'], $session['config_data']['content_type'] );

					$this->DB->build( array(
											'select'	=> 'tpb_id, tpb_name, tpb_human_name, tpb_app_type, tpb_content_type, tpb_position, tpb_image',
											'from'		=> 'ccs_template_blocks',
											'where'		=> "(tpb_app_type = '{$galleryKeys['feed_type']}' AND tpb_content_type = '{$galleryKeys['content_type']}') OR (tpb_app_type = '*' AND ( tpb_content_type = '{$galleryKeys['content_type']}' OR tpb_content_type = '*' ) )",
											'order'		=> 'tpb_position ASC',
									)		);
					$this->DB->execute();

					while( $r = $this->DB->fetch() )
					{
						if( !$r['tpb_image'] )
						{
							$r['tpb_image'] = 'default.png';
						}

						$r['tpb_image'] = $this->settings['upload_url'] . '/content_templates/' . $r['tpb_image'];
						 
						$feedTemplates[ $r['tpb_id'] ] = $r;
					}

					//-----------------------------------------
					// Custom templates?
					//-----------------------------------------

					if( $session['config_data']['block_id'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['block_id']}'" 
															)		);
					}
	
					if( !$template['tpb_content'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['content_type']}'" 
															)		);
	
						if( !$template['tpb_content'] )
						{
							$template	= $this->DB->buildAndFetch( array( 
																		'select'	=> '*', 
																		'from'		=> 'ccs_template_blocks', 
																		'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
																)		);
						}
					}
	
					if( method_exists( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] ), 'modifyTemplate' ) )
					{
						$template['tpb_content']	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->modifyTemplate( $session, $template['tpb_content'] );
					}
	
					$editor_area	= $this->registry->output->formTextarea( "custom_template", IPSText::htmlspecialchars($template['tpb_content']), 100, 30, "custom_template", "style='width:100%;'" );
					
					//-----------------------------------------
					// WYSIWYG editing
					//-----------------------------------------
	
					$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
					
					$this->registry->output->html .= $_globalHtml->getWysiwyg( 'custom_template' );
				
					$html	.= $this->html->feed__wizard_6( $session, $editor_area, $feedTemplates );
				}
			break;

			//-----------------------------------------
			// Step 7: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 7:
				//-----------------------------------------
				// Using gallery or custom templates?
				//-----------------------------------------

				if( $this->request['use_gallery'] && $this->request['template_id'] )
				{
					$session['config_data']['template'] = intval( $this->request['template_id'] );
				}
				else
				{				
					//-----------------------------------------
					// Test the syntax
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
					$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
					
					$_TEST	= $engine->convertHtmlToPhp( 'test__whatever', '$test', $_POST['custom_template'], '', false, true );
					
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
					
						$html	= $this->html->feed__wizard_6( $session, $editor_area );
						break;
					}

					$session['config_data']['template']	= 0;
				}

				//-----------------------------------------
				// Save the block
				//-----------------------------------------
				
				$block	= $this->_saveBlock( $session );
				
				//-----------------------------------------
				// Save the template
				//-----------------------------------------
				
				if( !$this->request['use_gallery'] )
				{
					if( $this->request['request_method'] == 'post' )					
					{
						$templateHTML	= str_replace( "&#092;", '\\', $_POST['custom_template'] );
		
						$template		= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['block_id']}'" 
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
																		'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['content_type']}'" 
																)		);
		
							if( !$template['tpb_id'] )
							{
								$template	= $this->DB->buildAndFetch( array( 
																			'select'	=> '*', 
																			'from'		=> 'ccs_template_blocks', 
																			'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
																	)		);
							}
		
							$this->DB->insert( 'ccs_template_blocks', 
																	array( 
																		'tpb_name'		=> "{$_feedConfig['templateBit']}_{$block['block_id']}",
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
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['block_id']}'" 
															)		);
		
						if( !$template['tpb_id'] )
						{
							$template	= $this->DB->buildAndFetch( array( 
																		'select'	=> '*', 
																		'from'		=> 'ccs_template_blocks', 
																		'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['content_type']}'" 
																)		);
		
							if( !$template['tpb_id'] )
							{
								$template	= $this->DB->buildAndFetch( array( 
																			'select'	=> '*', 
																			'from'		=> 'ccs_template_blocks', 
																			'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
																	)		);
							}
						}
					}
				
				
					$cache	= array(
									'cache_type'	=> 'block',
									'cache_type_id'	=> $template['tpb_id'],
									);
			
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
					$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
					$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_feedConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
					
					$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );
				}
		
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
					if( $block['block_template'] )
					{
						$template		= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_id='{$block['block_template']}'" 
															)		);

						$block['tpb_name']	= $template['tpb_name'];
					}

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
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=6&_jump=1' );
				}
				else
				{
					$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$html	= $this->html->feed__wizard_DONE( $block );
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
	public function recacheBlock( $block, $return=false, $previewMode=false )
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

			if( $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] ) )
			{
				$_config		= $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] )->returnFeedInfo();

				if( !$_config['app'] OR IPSLib::appIsInstalled( $_config['app'] ) )
				{
					$content	= $this->registry->ccsAcpFunctions->getBlockObject( $config['feed'] )->executeFeed( $block, $previewMode );
				}
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
							'wizard_type'	=> 'feed',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'template'		=> $block['block_template'],
															'feed_type'		=> $config['feed'],
															'content_type'	=> $config['content'],
															'filters'		=> $config['filters'],
															'sortby'		=> $config['sortby'],
															'sortorder'		=> $config['sortorder'],
															'offset_start'	=> $config['offset_a'],
															'offset_end'	=> $config['offset_b'],
															'hide_empty'	=> $config['hide_empty'],
															'title'			=> $block['block_name'],
															'key'			=> $block['block_key'],
															'description'	=> $block['block_description'],
															'cache_ttl'		=> $block['block_cache_ttl'],
															'block_id'		=> $block['block_id'],
															'category'		=> $block['block_category']
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
				$session['config_data']['feed_type']	= $this->request['feed_type'];
				
				if( !$session['config_data']['feed_type'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->setMessage( $this->registry->class_localization->words['block_ft_is_required'] );
					
					return $session;
				}
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$_auto	= false;
				
				if( !$this->request['feed_key'] )
				{
					$this->request['feed_key']	= strtolower( str_replace( ' ', '_', $this->request['feed_title'] ) );
					$_auto						= true;
				}
				
				$session['config_data']['title']		= $this->request['feed_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['feed_key'] );
				$session['config_data']['description']	= $this->request['feed_description'];
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
					
					$this->registry->output->setMessage( $this->registry->class_localization->words['block_key_is_required'] );
					
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
					
					$this->registry->output->setMessage( $this->registry->class_localization->words['block_key_in_use'] );
					
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

				if( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] ) )
				{
					$validateResult	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->checkFeedContentTypes( $this->request );
				}
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'], '10CCS90' );
				}

				$session['config_data']['content_type']	= $validateResult[1];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 5:
				$validateResult	= array();

				if( $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] ) )
				{
					$validateResult	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->checkFeedFilters( $session, $this->request );
				}

				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'], '10CCS91' );
				}

				$session['config_data']['filters']		= $validateResult[1];
				
				$validateResult	= $this->registry->ccsAcpFunctions->getBlockObject( $session['config_data']['feed_type'] )->checkFeedOrdering( $this->request, $session );

				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'], '10CCS92' );
				}
				
				$session['config_data']['sortby']		= $validateResult[1]['sortby'];
				$session['config_data']['sortorder']	= $validateResult[1]['sortorder'];
				$session['config_data']['offset_start']	= $validateResult[1]['offset_start'];
				$session['config_data']['offset_end']	= $validateResult[1]['offset_end'];

				if( isset($validateResult[1]['filters']) AND count($validateResult[1]['filters']) )
				{
					foreach( $validateResult[1]['filters'] as $k => $v )
					{
						$session['config_data']['filters'][ $k ]	= $v;
					}
				}

				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;

			case 6:
				if( $this->request['use_gallery'] && $this->request['template_id'] )
				{
					$session['config_data']['template'] = intval( $this->request['template_id'] );
				}
				else
				{				
					$session['config_data']['template']	= 0;
				}

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
						'block_type'		=> 'feed',
						'block_content'		=> '',
						'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
						'block_category'	=> $session['config_data']['category'],
						'block_template'	=> $session['config_data']['template'],
						'block_config'		=> serialize( 
														array( 
															'feed'			=> $session['config_data']['feed_type'],
															'content'		=> $session['config_data']['content_type'],
															'filters'		=> $session['config_data']['filters'],
															'sortby'		=> $session['config_data']['sortby'],
															'sortorder'		=> $session['config_data']['sortorder'],
															'offset_a'		=> $session['config_data']['offset_start'],
															'offset_b'		=> $session['config_data']['offset_end'],
															'hide_empty'	=> $session['config_data']['hide_empty'],
															) 
														)
						);
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blocksaved'], $block['block_name'] ) );
		
		return $this->_insertOrUpdate( $session, $block );
	}
}