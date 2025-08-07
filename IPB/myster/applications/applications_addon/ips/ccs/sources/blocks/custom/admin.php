<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS custom block type admin plugin
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

class adminBlockHelper_custom extends adminBlockHelper implements adminBlockHelperInterface
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
			
			$this->html = $registry->output->loadTemplate( 'cp_skin_blocks_custom' );
		}
		
		parent::__construct( $registry );
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
		return array(
					$this->lang->words['block_custom__cat'] => array( array( '&#36;title', $this->lang->words['block_custom__title'] ) ),
					);
	}

	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig() 
	{
		return array( 'custom', $this->registry->class_localization->words['block_type__custom'] );
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
							'wizard_type'	=> 'custom',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'type'			=> $config['type'],
															'hide_empty'	=> $config['hide_empty'],
															'title'			=> $block['block_name'],
															'key'			=> $block['block_key'],
															'description'	=> $block['block_description'],
															'content'		=> $block['block_content'],
															'cache_ttl'		=> $block['block_cache_ttl'],
															'block_id'		=> $block['block_id'],
															'category'		=> $block['block_category'],
															)
														)
							);

		return $session;
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

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Allow to select block type
			//-----------------------------------------
			
			case 2:
				$session['config_data']['type']	= $session['config_data']['type'] ? $session['config_data']['type'] : 'html';
				
				$html	= $this->html->custom__wizard_2( $session );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['hide_empty']	= $session['config_data']['hide_empty'] ? $session['config_data']['hide_empty'] : 0;
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name'];

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
				
				$html	= $this->html->custom__wizard_3( $session, $categories );
			break;

			//-----------------------------------------
			// Step 4: Edit the HTML template
			//-----------------------------------------
			
			case 4:
				if( !$this->_saveAndGo( $session, 4 ) )
				{
					if( $session['config_data']['type'] == 'basic' )
					{
						$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
						$editor = new $classToLoad();
						
						//$editor->setAllowHtml( 1 );	This will make the content show as bbcode..
						$editor->setContent( $session['config_data']['content'] );

						$editor_area	= $editor->show( 'custom_content' );
					}
					else
					{
						$editor_area	= $this->registry->output->formTextarea( "custom_content", IPSText::htmlspecialchars( $session['config_data']['content'] ), 100, 30, "custom_content", "style='width:100%;'" );
					}

					$html	= $this->html->custom__wizard_4( $session, $editor_area );
					
					if( $session['config_data']['type'] != 'basic' )
					{
						$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
						
						$html .= $_globalHtml->getWysiwyg( 'custom_content', $session['config_data']['type'] );
					}
				}
			break;

			//-----------------------------------------
			// Step 5: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 5:
				$block	= $this->_saveBlock( $session );

				//-----------------------------------------
				// Recache block
				//-----------------------------------------
		
				define( 'RECACHE_SKIN', true );
				$block['block_cache_output']	= $this->recacheBlock( $block );
				$block['block_cache_last']		= time();

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
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=4&_jump=1' );
				}
				else
				{
					$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$html	= $this->html->custom__wizard_DONE( $block );
				}
			break;
		}
		
		return $html;
	}
	
	/**
	 * Parse block
	 *
	 * @access	protected
	 * @param	array 		Block data
	 * @return	string		Parsed block content
	 */
	protected function _parseBlock( $block )
	{
		$config		= unserialize( $block['block_config'] );
		$content	= '';
		
		switch( $config['type'] )
		{
			case 'basic':
				IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';
				
				if( IN_ACP )
				{
					$_baseUrlBackup				= $this->settings['base_url'];
					$this->settings['base_url']	= $this->settings['board_url'] . '/index.php?';
				}
				
				$content	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $block['block_content'] );
				
				if( IN_ACP )
				{
					$this->settings['base_url']	= $_baseUrlBackup;
				}
			break;
			
			case 'html':
				$content	= $block['block_content'];
			break;
			
			case 'php':
				//-----------------------------------------
				// We need to push the raw PHP code into the
				// template system or it'll cache and be
				// one page load behind
				// @see http://forums.invisionpower.com/tracker/issue-17502-block-caching/
				//-----------------------------------------
				
				$content	= "<php>ob_start();\n" . $block['block_content'] . "\n\$IPBHTML .= ob_get_contents();\nob_end_clean();</php>";
				//ob_start();
				//eval( $block['block_content'] );
				//$content	= ob_get_contents();
				//ob_end_clean();
			break;
		}

		return $content;
	}

	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	string				New cached content
	 */
	public function recacheBlock( $block, $return=false )
	{
		//-----------------------------------------
		// Save the template
		//-----------------------------------------

		$templateHTML	= $this->_parseBlock( $block );

		$template		= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='block__custom_{$block['block_id']}'" 
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
														'where'		=> "tpb_name='block__custom'" 
												)		);

			$this->DB->insert( 'ccs_template_blocks', 
								array( 
									'tpb_name'		=> 'block__custom_' . $block['block_id'],
									'tpb_content'	=> $templateHTML,
									'tpb_params'	=> $template['tpb_params'],
									)
							);
			$template['tpb_id']	= $this->DB->getInsertId();
		}
		
		$cache	= array(
						'cache_type'	=> 'block',
						'cache_type_id'	=> $template['tpb_id'],
						);

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		$cache['cache_content']	= $engine->convertHtmlToPhp( 'block__custom_' . $block['block_id'], $template['tpb_params'], $templateHTML, '', false, true );
		
		$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass	= new $classToLoad( $this->registry );

		if( defined('RECACHE_SKIN') AND RECACHE_SKIN )
		{
			$_pagesClass->recacheTemplateCache( $engine );
		}

		$_pagesClass->loadSkinFile();

		/**
		 * @link	http://community.invisionpower.com/tracker/issue-23660-php-tags-evaldexecuted-in-acp/
		 */
		ob_start();
		$func 		= 'block__custom_' . $block['block_id'];
		$content	= $this->registry->output->getTemplate('ccs')->$func( $block['block_name'], $templateHTML );
		ob_end_clean();

		if( !$return AND $block['block_cache_ttl'] )
		{
			$this->DB->update( 'ccs_blocks', array( 'block_cache_output' => $content, 'block_cache_last' => time() ), 'block_id=' . intval($block['block_id']) );
		}
		
		return $content;
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
				$validTypes	= array( 'basic', 'html', 'php' );
				
				if( !in_array( $this->request['custom_type'], $validTypes ) )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['block_invalid_custom_type'];
					
					return $session;
				}
				
				$session['config_data']['type']		= $this->request['custom_type'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$_auto	= false;
				
				if( !$this->request['custom_key'] )
				{
					$this->request['custom_key']	= strtolower( str_replace( ' ', '_', $this->request['custom_title'] ) );
					$_auto							= true;
				}

				$session['config_data']['title']		= $this->request['custom_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['custom_key'] );
				$session['config_data']['description']	= $this->request['custom_description'];
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
				if( $session['config_data']['type'] == 'basic' )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
					$editor = new $classToLoad();
					$editor->setAllowHtml( 1 );
					
					$session['config_data']['content']	= $editor->process( $_POST['custom_content'] );
					
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$session['config_data']['content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $session['config_data']['content'] );
				}
				else
				{
					$session['config_data']['content']	= IPSText::formToText( trim($_POST['custom_content']) );
				}
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $session['config_data']['type'] == 'php' )
				{
					$session['config_data']['content']	= str_replace( "&#092;", '\\', $session['config_data']['content'] );
						
					if( strpos( trim($session['config_data']['content']), '<?php' ) === 0 OR strpos( trim($session['config_data']['content']), '<?' ) === 0 )
					{
						$session['wizard_step']--;
						
						$this->registry->output->global_error = $this->lang->words['php_page_php_tag'];
						
						return $session;
					}
				}
		
				//-----------------------------------------
				// Test the syntax
				//-----------------------------------------
				
				$_test_content	= $session['config_data']['content'];
				
				if( $session['config_data']['type'] == 'php' )
				{
					$_test_content	= "<php>ob_start();\n" . $session['config_data']['content'] . "\n\$IPBHTML .= ob_get_contents();\nob_end_clean();</php>";
				}

				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
				$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
				
				$_TEST	= $engine->convertHtmlToPhp( 'test__whatever', '$test', $_test_content, '', false, true );
				
				ob_start();
				eval( $_TEST );
				$_RETURN = ob_get_contents();
				ob_end_clean();

				if( $_RETURN )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error	= $this->lang->words['bad_template_syntax'];
					
					return $session;
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
						'block_type'		=> 'custom',
						'block_content'		=> $session['config_data']['content'],
						'block_category'	=> $session['config_data']['category'],
						'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
						'block_config'		=> serialize( array( 'type' => $session['config_data']['type'], 'hide_empty' => $session['config_data']['hide_empty'], ) )
						);

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_blocksaved'], $block['block_name'] ) );
		
		return $this->_insertOrUpdate( $session, $block );
	}
}
