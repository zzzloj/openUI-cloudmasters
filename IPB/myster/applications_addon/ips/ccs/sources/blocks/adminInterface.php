<?php
/**
 * Admin block plugin interface
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10056 $ 
 * @since		1st March 2009
 */

interface adminBlockHelperInterface
{
	/**
	 * Wizard launcher.  Should determine the next step necessary and act appropriately.
	 *
	 * @access	public
	 * @param	array 				Session data
	 * @return	string				HTML to output to screen
	 */
	public function returnNextStep( $session );
	
	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig();

	/**
	 * Return the block content to display.  Checks cache and updates cache if needed.
	 *
	 * @access	public
	 * @param	array 	Block data
	 * @return	string 	Content to output
	 */
	public function getBlockContent( $block );
	
	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	bool				Cache done successfully
	 */
	public function recacheBlock( $block, $return=false );
	
	/**
	 * Store data to initiate a wizard session based on given block table data
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	array 				Data to store for wizard session
	 */
	public function createWizardSession( $block );
}

/**
 * Admin block parent class.  Contains methods common to all block administration.
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10056 $ 
 * @since		14 Sept 2010
 */
class adminBlockHelper
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
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
	 * Current session
	 *
	 * @access	public
	 * @var		array
	 */
	public $session;
	
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
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;

		if( IN_ACP )
		{
			//-----------------------------------------
			// Set up stuff
			//-----------------------------------------
			
			$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
			$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';
		}
	}
	
	/**
	 * Return the block content to display.  Checks cache and updates cache if needed.
	 *
	 * @access	public
	 * @param	array 	Block data
	 * @return	string 	Content to output
	 */
	public function getBlockContent( $block )
	{
		if( $block['block_cache_ttl'] )
		{
			if( $block['block_cache_ttl'] == '*' AND $block['block_cache_output'] )
			{
				return $block['block_cache_output'];
			}
			
			$expired	= time() - ( $block['block_cache_ttl'] * 60 );
			
			if( $block['block_cache_last'] > $expired )
			{
				if( $block['block_cache_output'] )
				{
					return $block['block_cache_output'];
				}
			}
		}
		
		return $this->recacheBlock( $block );
	}
	
	/**
	 * Run DB update query
	 *
	 * @access	protected
	 * @param	string		Session ID
	 * @param	integer		Current step
	 * @param	array 		Config data
	 * @return	bool
	 */
	protected function _saveToDb( $sessionId, $currentStep, $configData )
	{
		$this->DB->update( 'ccs_block_wizard', array( 'wizard_config' => serialize($configData), 'wizard_step' => ($currentStep + 1) ), "wizard_id='{$sessionId}'" );
		return true;
	}
	
	/**
	 * Insert or update block
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @param	array 		Block data to save
	 * @return	array 		Block data + id
	 */
	protected function _insertOrUpdate( $session, $block )
	{
		if( $session['config_data']['block_id'] )
		{
			$_old	= $this->DB->buildAndFetch( array( 'select' => 'block_id, block_content, block_type', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $session['config_data']['block_id'] ) );

			$this->saveRevision( $_old, $block );

			$this->DB->update( 'ccs_blocks', $block, 'block_id=' . $session['config_data']['block_id'] );
			$block['block_id']	= $session['config_data']['block_id'];
			
			//-----------------------------------------
			// Clear page caches
			//-----------------------------------------

			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
		}
		else
		{
			$this->DB->insert( 'ccs_blocks', $block );
			$block['block_id']	= $this->DB->getInsertId();
		}
				
		return $block;
	}

	/**
	 * Save a new block revision
	 *
	 * @access	public
	 * @param	array 			Old data
	 * @param	array 			New data
	 * @return	@e void
	 */
	public function saveRevision( $block, $newBlock )
	{
		if( $block['block_type'] != 'custom' )
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name LIKE '%_{$block['block_id']}'" ) );
			$content	= $template['tpb_content'];
			
			if( $content == $_POST['custom_template'] )
			{
				return;
			}
		}
		else
		{
			$content	= $block['block_content'];
			
			if( $content == $newBlock['block_content'] )
			{
				return;
			}
		}
		
		$_revision	= array(
							'revision_type'		=> 'block',
							'revision_type_id'	=> $block['block_id'],
							'revision_content'	=> $content,
							'revision_date'		=> time(),
							'revision_member'	=> $this->memberData['member_id'],
							);

		$this->DB->insert( 'ccs_revisions', $_revision );
	}
	
	/**
	 * Determine if we are saving and going elsewhere or not
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @param	int			Step number
	 * @return	mixed		void, or bool
	 */
	protected function _saveAndGo( $session, $step )
	{
		$step--;
		
		//-----------------------------------------
		// If we have an error, just return
		//-----------------------------------------
		
		if( $this->registry->output->global_error )
		{
			return false;
		}
		
		if( $session['config_data']['block_id'] AND $this->request['save_button'] )
		{
			//-----------------------------------------
			// Save block
			//-----------------------------------------
			
			$block	= $this->_saveBlock( $session );
			
			$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
			
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache();
			
			//-----------------------------------------
			// Recache block
			//-----------------------------------------

			define( 'RECACHE_SKIN', true );

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

			$this->registry->output->setMessage( $this->lang->words['block_q_edit_saved'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=blocks' );
		}
		else if( $session['config_data']['block_id'] AND $this->request['save_and_reload'] )
		{
			//-----------------------------------------
			// Save block
			//-----------------------------------------
			
			$block	= $this->_saveBlock( $session );
			
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass	= new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache();
			
			//-----------------------------------------
			// Recache block
			//-----------------------------------------

			define( 'RECACHE_SKIN', true );

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

			$this->registry->output->setMessage( $this->lang->words['block_q_edit_saved'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=blocks&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=' . $step . '&_jump=1' );
		}
		
		return false;
	}
}