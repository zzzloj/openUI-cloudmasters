<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS block creation wizard
 * Last Updated: $Date: 2012-01-25 17:21:54 -0500 (Wed, 25 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10192 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_blocks_wizard extends ipsCommand
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
		
		$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang', 'public_lang' ) );

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
			case 'editBlockTemplate':
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&do=editBlock&amp;block=' . intval($this->request['block']) . '&amp;step=template' );
			break;

			case 'editBlock':
				$this->_preLaunchWizard();
			break;

			case 'process':
				$this->_saveStep1();
			break;
			
			case 'preview':
				$this->_inlinePreview();
			break;

			case 'continue':
			default:
				$this->_wizardProxy();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Preview a block
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _inlinePreview()
	{
		//-----------------------------------------
		// Get wizard session
		//-----------------------------------------

		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : '';
		$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		$tpb_id 	= intval( $this->request['tpb_id'] );

		if( !$session['wizard_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_invalid_session'], '11CCSB005' );
		}

		//-----------------------------------------
		// Only accept feeds kthx
		//-----------------------------------------

		if( $session['wizard_type'] != 'feed' )
		{
			$this->registry->output->showError( $this->lang->words['only_feeds_allowed'], '11CCSB006' );
		}

		//-----------------------------------------
		// Part 1 of faking some block data for the caching methods
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );

		$blockConfig				= unserialize( $session['wizard_config'] );
		$blockConfig['feed']		= $blockConfig['feed_type'];
		$blockConfig['content']		= $blockConfig['content_type'];
		$blockConfig['offset_a']	= $blockConfig['offset_start'];
		$blockConfig['offset_b']	= $blockConfig['offset_end'];

		//-----------------------------------------
		// Get the block template name
		//-----------------------------------------

		$galleryKeys = $this->registry->ccsAcpFunctions->getBlockObject( $blockConfig['feed_type'] )->returnTemplateGalleryKeys( $blockConfig['feed_type'], $blockConfig['content_type'] );

		$skin = $this->DB->buildAndFetch( array( 
												'select'	=> '*',
												'from'		=> 'ccs_template_blocks',
												'where'		=> "tpb_id={$tpb_id} AND ( (tpb_app_type = '{$galleryKeys['feed_type']}' AND tpb_content_type = '{$galleryKeys['content_type']}') OR (tpb_app_type = '*' AND ( tpb_content_type = '{$galleryKeys['content_type']}' OR tpb_content_type= '*' ) ) )"
										)		);
		
		//-----------------------------------------
		// Part 2 of faking some block data for the caching methods
		//-----------------------------------------

		$block = array(
						'block_type'		=> $session['wizard_type'],
						'tpb_name'			=> $skin['tpb_name'],
						'block_cache_ttl' 	=> false,
						'block_config' 		=> serialize( $blockConfig )
						);

		//-----------------------------------------
		// Execute the cache function, which will return
		// the block with our specified template used
		//-----------------------------------------

		if( $block['block_type'] AND is_file( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php', "adminBlockHelper_" . $block['block_type'], 'ccs' );
			$extender		= new $classToLoad( $this->registry );

			// Add name back in for preview purposes
			$block['block_name'] = $blockConfig['title'];
			$content		= $extender->recacheBlock( $block, true, true );

			$output	= $this->registry->ccsFunctions->injectBlockFramework( $this->html->inline_preview_wrapper( $content ) );

			print $output;
			exit;
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['error_previewing'], '11CCSB007' );
		}
	}

	/**
	 * Wrapper function for the proxy method to load any necessary data
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _preLaunchWizard()
	{
		$id		= intval($this->request['block']);
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );

		if( !$block['block_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_not_found_edit'], '11CCS22' );
		}
		
		if( $block['block_type'] AND is_file( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php', "adminBlockHelper_" . $block['block_type'], 'ccs' );
			$extender		= new $classToLoad( $this->registry );
			$session		= $extender->createWizardSession( $block );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['block_not_found_edit'], '11CCS23' );
		}
		
		$session['wizard_started']	= time();
		
		$this->DB->insert( 'ccs_block_wizard', $session );
		
		$step	= 1;
		
		if( $this->request['step'] == 'template' )
		{
			switch( $block['block_type'] )
			{
				case 'custom':
					$step	= 3;
				break;
				
				case 'plugin':
					$step	= 4;
				break;
				
				case 'feed':
					$step	= 5;
				break;
			}
		}
		else if( $this->request['_jumpstep'] )
		{
			$step	= $this->request['_jumpstep'] - 1;
		}
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'module=blocks&section=wizard&wizard_session=' . $session['wizard_id'] . '&continuing=1&step=' . $step );
	}
	
	/**
	 * Save step 1 and continue
	 * We need a special step for step 1 because we don't have the 'type' yet saved
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _saveStep1()
	{
		$types	= array( 'feed', 'plugin', 'custom' );

		if( !in_array( $this->request['type'], $types ) )
		{
			$this->registry->output->showError( $this->lang->words['block_invalid_type'], '11CCS25' );
		}
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : '';

		$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		
		if( !$session['wizard_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_invalid_session'], '11CCS26' );
		}
		
		$this->DB->update( 'ccs_block_wizard', array( 'wizard_step' => 1, 'wizard_type' => $this->request['type'], 'wizard_name' => $this->request['name'] ), "wizard_id='{$sessionId}'" );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&wizard_session=' . $sessionId );
	}
	
	/**
	 * This is a proxy function.  It determines what step of the wizard we are on and acts appropriately
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _wizardProxy()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : '';
		
		$session	= array();
		
		if( $sessionId )
		{
			$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		}
		else
		{
			$sessionId	= md5( uniqid( microtime(), true ) );

			$this->DB->insert( 'ccs_block_wizard', array( 'wizard_id' => $sessionId, 'wizard_step' => 0, 'wizard_started' => time() ) );
		}
		
		if( $this->request['_jump'] )
		{
			$this->request['step']--;
		}
		
		if( $session['wizard_name'] )
		{
			$this->registry->output->extra_nav[] = array( '', $session['wizard_name'] );
		}
		else
		{
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['addnewblock_nav'] );
		}

		//-----------------------------------------
		// Proxy off to appropriate function
		//-----------------------------------------
		
		if( $session['wizard_type'] AND is_file( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $session['wizard_type'] . '/admin.php' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $session['wizard_type'] . '/admin.php', "adminBlockHelper_" . $session['wizard_type'], 'ccs' );
			$extender		= new $classToLoad( $this->registry );
						
			$this->registry->output->html .= $extender->returnNextStep( $session );
		}
		else
		{
			$_blockTypes	= array();
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			
			foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks' ) as $object )
			{
				if( $object->isDir() AND !$object->isDot() )
				{
					if( is_file( $object->getPathname() . '/admin.php' ) )
					{
						$_folder	= str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/', '', str_replace( '\\', '/', $object->getPathname() ) );

						$classToLoad	= IPSLib::loadLibrary( $object->getPathname() . '/admin.php', "adminBlockHelper_" . $_folder, 'ccs' );
						$_class 		= new $classToLoad( $this->registry );
						$_blockTypes[]	= $_class->getBlockConfig();
					}
				}
			}

			$this->registry->output->html .= $this->html->wizard_step_1( $sessionId, $_blockTypes );
		}
	}
}