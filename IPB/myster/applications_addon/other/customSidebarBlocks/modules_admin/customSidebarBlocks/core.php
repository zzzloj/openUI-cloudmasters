<?php
/**
 * (e$30) Custom Sidebar Blocks
 * version: 1.5.0
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_customSidebarBlocks_customSidebarBlocks_core extends ipsCommand
{
	public $html;
	public $registry;
	
	public $form_code;
	public $form_code_js;
	
	public $permissions;
	public $myPreferredEditor;
	
	/**
	 * Main execution method
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		$this->html         	= $this->registry->output->loadTemplate( 'cp_skin_e_CSB');
		
		$this->form_code 		= $this->html->form_code    = 'module=customSidebarBlocks&amp;section=core';
		$this->form_code_js 	= $this->html->form_code_js = 'module=customSidebarBlocks&section=core';
	
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_customSidebarBlocks' ) );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
        require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
        $this->permissions = new classPublicPermissions( ipsRegistry::instance() );		
		
		switch( $this->request['do'] )
		{
			//******Add Block******//
			case 'block_form':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_add' );
				$this->block_form();
			break;
			case 'add_block':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_add' );
				$this->add_block();
			break;
			//******Reorder Blocks******//		
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_add' );
				$this->_reorder();
			break;
			//******Recache Blocks******//		
			case 'recache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_add' );
				$this->rebuildBlockCache();
			break;
			//******Delete Block******//		
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_delete' );
				$this->delete();
			break;
			//******Export Block******//		
			case 'export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_add' );
				$this->export();
			break;			
			//******Settings******//		
			case 'settings':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_settings' );
				$this->showSettings();
			break;
			//******View Blocks******//		
			case 'blocks':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'customSideBarBlocks_view' );
				$this->blocks();
			break;
		}
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Reorder blocks
	 */
	private function _reorder()
	{
		#init
		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax = new classAjax();

		#checks
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
		
 		#Save new position
 		$position = 1;
 		
 		if( is_array($this->request['blocks']) AND count($this->request['blocks']) )
 		{
 			foreach( $this->request['blocks'] as $this_id )
 			{
 				$this->DB->update( 'custom_sidebar_blocks', array( 'csb_position' => $position ), 'csb_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
		#rebuild da cache
		$this->cache->rebuildCache('custom_sidebar_blocks','customSidebarBlocks');
		
		#a ok
 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Export block as a hook
	 * Added as of CSB 2.0
	 */	
	private function export()
	{
		#init
		$block 	= array();
		$id		= intval($this->request['csb_id']);
		
		if ( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['error_no_id'] );		
		}
			
		#grab block from cache
		if ( is_array( $this->caches['custom_sidebar_blocks'][ $id ] ) )
		{
			 $block = $this->caches['custom_sidebar_blocks'][ $id ];
		}
		#get block from db
		else
		{
			$this->DB->build( array( 'select'	=> 'csb.*',
									 'from'		=> array ('custom_sidebar_blocks' => 'csb' ),
									 'where'	=> 'csb.csb_id='.$id,
							)		);			

			$this->DB->execute();

			if ( !$this->DB->getTotalRows() )
			{
				$this->registry->output->showError( $this->lang->words['error_none_found'] );	
			}	
			else
			{
				$block = $this->DB->fetch();
			}
		}
		
		$this->_doExportHook($block);
	}

	/**
	 * Actually export the damn hook already
	 * Sorry, it has been a long day...
	 */
	protected function _doExportHook($block)
	{
		//-----------------------------------------
		// Get hook
		//-----------------------------------------
		
		$hookData	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_hooks', 'where' => "hook_key='customSidebarBlocksHook'" ) );
		
		if( !$hookData['hook_id'] )
		{
			$this->registry->output->showError( $this->lang->words['h_noexport'], 1115 );
		}
		
		$extra_data	= unserialize( $hookData['hook_extra_data'] );
		
		//-----------------------------------------
		// Extra Processing for CSB...
		//-----------------------------------------
		
		$id								= $hookData['hook_id'];
		
		$hookData['hook_name']			= "(e32) Custom Sidebar Block - ".$block['csb_title'];
		$hookData['hook_desc']			= "A standalone hook for the ".$block['csb_title']." Custom Sidebar Block";
		$hookData['hook_update_check']	= "";
		$hookData['hook_version_human']	= "1.0.0";
		$hookData['hook_version_long']	= "100";
		$hookData['hook_extra_data']	= "";
		// $badChars = array('(', ')', ';', '.', '{', '}', '[', ']', '&', '#', ':', ',', '.', '<', '>', '?', '"', '\'', 
								// '+', '=', '*', '$', '^', '=', '%', '@', '!', '~', '`', '/', '\\');
		// $hookData['hook_key']			= "CSB_".str_replace($badChars, '', $block['csb_title']);
		$hookData['hook_key']			= "CSB_Standalone_Hook_ID".$block['csb_id'];
		
		//-----------------------------------------
		// Get hook files
		//-----------------------------------------
		
		$files = array();
		$index = 1;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if ($r['hook_file_real'] == 'customSidebarBlocksSingle.php')
			{
				#edit hook file data so its unique
				$r['hook_classname']	= $hookData['hook_key'];
				$r['hook_file_real']	= $hookData['hook_key'].".php";
				
				$files[ $index ]		= $r;
				$index++;			
			}
		}

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'hookexport' );

		//-----------------------------------------
		// Put hook data in export
		//-----------------------------------------
		
		$xml->addElement( 'hookdata', 'hookexport' );
		$content	= array();
		
		foreach( $hookData as $k => $v )
		{
			if( in_array( $k, array( 'hook_id', 'hook_enabled', 'hook_installed', 'hook_updated', 'hook_position' ) ) )
			{
				continue;
			}
			
			$content[ $k ] = $v;
		}
		
		$xml->addElementAsRecord( 'hookdata', 'config', $content );
		
		//-----------------------------------------
		// Put hook files in export
		//-----------------------------------------
		
		$xml->addElement( 'hookfiles', 'hookexport' );

		foreach( $files as $index => $r )
		{
			$content	= array();
			
			foreach( $r as $k => $v )
			{
				if( in_array( $k, array( 'hook_file_id', 'hook_hook_id', 'hook_file_stored', 'hooks_source' ) ) )
				{
					continue;
				}
				
				$content[ $k ] = $v;
			}
			
			$source	= is_file( IPS_HOOKS_PATH . $r['hook_file_stored'] ) ? file_get_contents( IPS_HOOKS_PATH . $r['hook_file_stored'] ) : '';
			
			#edit file to use new key and block id
			$source	= str_replace('customSidebarBlocksHookSingle',	$hookData['hook_key'],	$r['hooks_source']);
			$source	= str_replace('9999',							$block['csb_id'],	$source);
			
			if( $r['hook_type'] == 'commandHooks' || $r['hook_type'] == 'libraryHooks' )
			{
				$source	= $this->_cleanSource( $source );
			}

			$content['hooks_source'] = $source;

			$xml->addElementAsRecord( 'hookfiles', 'file', $content );
		}
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------

		$this->registry->output->showDownload( $xml->fetchDocument(), $hookData['hook_key'] . '.xml', '', 0 );
	}	
	
	/**
	 * Add/Edit Block Form
	 */	
	private function block_form()
	{
		#init
		$content = array();
		
		if ( $this->request['type'] == 'edit' )
		{
			if ( ! $this->request['csb_id'] )
			{
				$this->registry->output->showError( $this->lang->words['error_no_id'] );		
			}
			
			#grab block from cache
			if ( is_array( $this->caches['custom_sidebar_blocks'][ $this->request['csb_id'] ] ) )
			{
				 $content = $this->caches['custom_sidebar_blocks'][ $this->request['csb_id'] ];
			}
			#get block from db
			else
			{
				$this->DB->build( array( 'select'	=> 'csb.*',
										 'from'		=> array ('custom_sidebar_blocks' => 'csb' ),
										 'where'	=> 'csb.csb_id='.$this->request['csb_id'],
										 'add_join' => array(
															array(																	           
																	'select' => 'p.*',
																	'from'   => array( 'permission_index' => 'p' ),
																	'where'  => "p.app = 'customSidebarBlocks' AND p.perm_type='block' AND perm_type_id=csb.csb_id",
																	'type'   => 'left',
																 )
															)
								)		);			

				$this->DB->execute();

				if ( !$this->DB->getTotalRows() )
				{
					$this->registry->output->showError( $this->lang->words['error_none_found'] );	
				}	
				else
				{
					$content = $this->DB->fetch();
				}
			}
		}
		
		#get permission matrix
		$matrix_html = $this->permissions->adminPermMatrix( 'block', $content );

		#can we fix this stupid WYSIWYJ thing finally???
		$this->myPreferredEditor = $this->memberData['members_editor_choice'];

		#force member to normal editor, we'll change you back later, don't worry.
		$this->memberData['members_editor_choice'] = "std";
		
		#output
		$this->registry->output->html .= $this->html->blockForm( $content, $matrix_html );
		$this->registry->output->html .= $this->html->footer();
	}
	
	/**
	 * Format Post for cache: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	private function formatPostForCache( $postContent )
	{
		require_once( IPS_ROOT_PATH . 'sources/classes/text/parser.php');
		/* Set up parser */
		$parser = new classes_text_parser();
		$parser->set( array( 'memberData'     => $this->memberData['member_id'],
							 'parseBBCode'    => 1,
							 'parseArea'	  => 'topics',
				  			 'parseHtml'      => 0,
				 			 'parseEmoticons' => 1 ) );
		
		/* Make suitable for display */
		$postContent = $parser->display( $postContent );
		
		return $postContent;
	}
	
	/**
	 * Format Post: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	private function formatPost( $postContent )
	{
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
			
		/* Turn off legacy mode */
		//$editor->setLegacyMode( false );
	
		/* Set HTML Flag for the editor. Bug #19796 */
		$editor->setIsHtml( 1 );
		$editor->setAllowBbcode( 1 );
		$editor->setAllowSmilies( 1 );
		//$editor->setBbcodeSection( 'topics' );
		
		$postContent = $editor->process( $postContent );
		
		$postContent = str_replace("<br>","",$postContent);
		
		return $postContent;
	}
	
	private function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}
	
	/**
	 * Add/Edit Block
	 */
	private function add_block()
	{	
		if ( ! $this->request['csb_raw'] && ! $this->request['csb_php'])
		{
			#process editor contents first
			// IPSText::getTextClass('bbcode')->bypass_badwords	= 1;
			// IPSText::getTextClass('bbcode')->parse_smilies		= 1;
			// IPSText::getTextClass('bbcode')->parse_html		= 0;
			// IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			// IPSText::getTextClass('bbcode')->parse_nl2br    	= 1;
			// IPSText::getTextClass('bbcode')->parsing_section 	= 'global';

			if( trim($this->request['csb_content']) == '<br>' OR trim($this->request['csb_content']) == '<br />' )
			{
				$content	= '';
			}
			else
			{
				//$content = $this->formatPost( $this->request['csb_content'] );
				$content = $this->formatPost( html_entity_decode($this->request['csb_content']) );
				//$content = $this->request['csb_content'];
				//$content = IPSText::getTextClass('editor')->processRawPost( 'csb_content' );
			}

			$content = str_replace( "&#39;", "'", IPSText::stripslashes( $content ) );

			$content = IPSText::getTextClass('bbcode')->preDbParse( $content );
		}
		else if ( $this->request['csb_raw'] )
		{
			$content = $_POST['csb_content'];
			$content = str_replace( "&#092;", "\\", $_POST['csb_content']);
			$content = html_entity_decode($_POST['csb_content']);
		}
		else
		{
			$content = trim($_POST['csb_content']);
			
			//damn global§ion issues...
			$content = str_replace( "&amp;", "__ERICS__HACK__FIX__", $_POST['csb_content']);
			$content = html_entity_decode($_POST['csb_content']);
			$content = str_replace( "__ERICS__HACK__FIX__", "&amp;", $_POST['csb_content']);		
			
			//$content = preg_replace('~(?<!"\w )<div>(?! \w")~',"",$content);
			//$content = preg_replace('~(?<!"\w )</div>(?! \w")~',"",$content);
			//$content = preg_replace('~(?<!"\w )<br>(?! \w")~',"",$content);
			
			$contentLength = strlen($content);
			$newContentLength = -1;
			
			#remove opening html entities...
			while ($contentLength != $newContentLength && substr($content, 0, 1) == '<')
			{
				$contentLength = strlen($content);
				$content = preg_replace('/^<[^>]*>/', "", $content);
				$newContentLength = strlen($content);
			}
			
			$contentLength = strlen($content);
			$newContentLength = -1;
			
			#remove closing html entities...
			while ($contentLength != $newContentLength && substr($content, strlen($content)-1, 1) == '>')
			{
				$contentLength = strlen($content);
				$content = preg_replace('/<[^>]*>$/', "", $content);
				$newContentLength = strlen($content);
			}			
			
			//print($content);
			//exit;
		}

		#fill block array
		$csb_datas = array( 'csb_title'			=> $this->request['csb_title'],
							'csb_on' 			=> $this->request['csb_on'],
							'csb_image'	 		=> $this->request['csb_image'],
							'csb_use_perms' 	=> $this->request['csb_use_perms'],
							'csb_use_box' 		=> $this->request['csb_use_box'],
                            'csb_raw' 			=> $this->request['csb_raw'],
							'csb_no_collapse' 	=> $this->request['csb_no_collapse'],
							'csb_php' 			=> $this->request['csb_php'],
							'csb_hide_block' 	=> $this->request['csb_hide_block'],
							'csb_content' 		=> $content
						   );
		
		#adding block?
		if ( ! $this->request['csb_id'] )
		{					  
			$this->DB->insert( 'custom_sidebar_blocks', $csb_datas );
			$new_id = $this->DB->getInsertId();
		
			$this->registry->output->global_message = $this->lang->words['block_added'];	
		}
		#ok, well then we better update one or this was all a huge waste of time
		else
		{						  
			$this->DB->update( 'custom_sidebar_blocks', $csb_datas, 'csb_id='.$this->request['csb_id'] );
			
			$this->registry->output->global_message = str_replace('<%NAME%>', $this->request['csb_title'], $this->lang->words['block_edited']);			
		}
		
		$csb_id = ( $this->request['csb_id'] ) ? $this->request['csb_id'] : $new_id;
		
		$this->permissions->savePermMatrix( $this->request['perms'], $csb_id, 'block' );

		$this->cache->rebuildCache('custom_sidebar_blocks','customSidebarBlocks');		
		
		
		#switch back to your preferred editor
		$this->memberData['members_editor_choice'] = $this->myPreferredEditor;
		
		#reload same form (added in CSB version 1.5.2+)
		if ($this->request['reload'])
		{
			$redirectToUrl = $this->settings['base_url'].$this->html->form_code.'&amp;do=block_form&type=edit&csb_id='.$csb_id;
		}
		else
		{
			$redirectToUrl = $this->settings['base_url'].$this->html->form_code.'&amp;do=blocks';
		}
		
		#now ya'll mozy on away, ya hear
		$this->registry->output->silentRedirectWithMessage( $redirectToUrl );	
	}
	
	/**
	 * List Blocks
	 */	
	private function blocks()
	{
		#init
		$content = "";

		#grab blocks from cache
		if ( is_array( $this->caches['custom_sidebar_blocks'] ) )
		{
			 foreach ( $this->caches['custom_sidebar_blocks'] AS $block )
			 {
				#add row
				$content .= $this->html->blockRow( $block );
			 }
		}
		#get blocks from db
		else
		{
			$this->DB->build( array( 'select'	=> '*',
									 'from'		=> 'custom_sidebar_blocks',
									 'group'	=> 'csb_id',
									 'order'	=> 'csb_position',
							)		);
							
			$this->DB->execute();
	
			if ( $this->DB->getTotalRows() )
			{
				while ( $row = $this->DB->fetch() )
				{	
					#add row
					$content .= $this->html->blockRow( $row );
				}
			}
		}
		
		#output
		$this->registry->output->html .= $this->html->blocksOverviewWrapper( $content );
		$this->registry->output->html .= $this->html->footer();
	}
	
	/**
	 * Show Settings
	 */		
	private function showSettings()
	{
		// require_once( IPSLib::getAppDir( 'core' ).'/modules_admin/tools/settings.php' );
		// $this->registry->setClass( 'class_settings', new admin_core_tools_settings( $registry ) );	
		
		// $settings = $this->registry->getClass('class_settings');
		// $settings->makeRegistryShortcuts( $this->registry );
		
		//bug fix for 1.5
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/settings/settings.php' );
		$settings =  new admin_core_settings_settings();
		$settings->makeRegistryShortcuts( $this->registry );
				
		#settings templates
		$settings->html						= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );	

		#settings form codes
		$settings->form_code					= $settings->html->form_code    = 'module=settings&amp;section=settings';
		$settings->form_code_js				= $settings->html->form_code_js = 'module=settings&amp;section=settings';
		
		#do it
		$this->request['conf_title_keyword']	= 'e_CSB';
		
		$this->registry->output->setMessage($this->request['saved'] ? $this->lang->words['s_updated'] : "", 1);
		
		$settings->return_after_save 		= $this->settings['base_url'] . $returnFormCode . '&saved=1&do=settings';
		$settings->_viewSettings();		
		$this->registry->output->html .= $this->html->footer();
	}
	
	/**
	 * Delete!
	 */		
	private function delete()
	{
		if( ! $this->request['csb_id'] )
		{
			$this->registry->output->showError( $this->lang->words['error_no_id'] );
		}
		
		#delete it!
		$this->DB->delete( 'custom_sidebar_blocks', 'csb_id = ' . $this->request['csb_id'] );
		
		#add message
		$this->registry->output->global_message = $this->lang->words['block_deleted'];

		#rebuild da cache
		$this->cache->rebuildCache('custom_sidebar_blocks','customSidebarBlocks');

		#redirect
		$this->blocks();		
	}

	/**
	 * Cache those Blocks!
	 */
	public function rebuildBlockCache()
	{
		$cache = array();
			
        #get block
		$this->DB->build( array( 'select'	=> 'csb.*',
								 'from'		=> array ('custom_sidebar_blocks' => 'csb' ),
								 'order'    => 'csb.csb_position ASC',
								 'add_join' => array(
													 array(	'select' => 'p.*',			
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.app = 'customSidebarBlocks' AND p.perm_type='block' AND perm_type_id=csb.csb_id",
															'type'   => 'left',
														  )  
													)
						)		);			

		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['csb_id'] ] = $r;
		}
		
		#do it!
		$this->cache->setCache( 'custom_sidebar_blocks', $cache, array( 'array' => 1, 'deletefirst' => 0, 'donow' => 1 ) );

		if ( $this->request['human'] == 'yes' )
		{
			#is someone there?  better redirect...
			$this->registry->output->global_message = $this->lang->words['blocks_recached'];

			#redirect
			$this->blocks();
		}	
	}
}