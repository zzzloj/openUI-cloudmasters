<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.4.0
|   =============================================
|   by Michael John
|   Copyright 2011-2013 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_portal_blocks_blocks extends ipsCommand
{
	private $html;
	private $form_code;
	private $form_code_js;	

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
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'admin_portal' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{				
			case 'block_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockForm('new');
				break;
			case 'block_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockSave('new');
				break;				
			case 'block_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockForm('edit');
				break;				
			case 'block_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );			
				$this->blockSave('edit');
				break;
			case 'block_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_delete' );
				$this->blockDelete();
				break;
			case 'block_show':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blockShow();
				break;                
			case 'block_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blockMove();
				break;                          
			case 'block_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blockImport();
				break;
			case 'block_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blockExport();
				break;
                                			
			case 'blocks':
			default:
            $this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blocks_manage' );
				$this->blocks();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
    
	/*-------------------------------------------------------------------------*/
	// Manage Blocks
	/*-------------------------------------------------------------------------*/
	public function blocks()
	{
		$this->registry->output->extra_nav[] = array( 'do=blocks', $this->lang->words['custom_blocks'] );
	
		$this->DB->build( array( 'select' => '*', 'from' => 'portal_blocks', 'order' => 'position ASC' ) );
		$this->DB->execute();
		
		$leftBlocks  = array();	
		$mainBlocks  = array();        
		$rightBlocks = array();
        		 
		while( $r = $this->DB->fetch() )
		{		
            if( $r['align'] == '1' )
            {
                $leftBlocks[] = $r;    
            }
            else if( $r['align'] == '2' )
            {
                $mainBlocks[] = $r;    
            }
            else if( $r['align'] == '3' )
            {
                $rightBlocks[] = $r;    
            } 
            else if( $r['align'] == '4' )
            {
                $topBlocks[] = $r;    
            }
            else if( $r['align'] == '5' )
            {
                $bottomBlocks[] = $r;    
            }                                   					 	
		}
		
		$this->registry->output->html .= $this->html->blocksOverview( $leftBlocks, $mainBlocks, $rightBlocks, $topBlocks, $bottomBlocks );
	}
	
	/*-------------------------------------------------------------------------*/
	// Block Form
	/*-------------------------------------------------------------------------*/	
	public function blockForm( $type='new' )
	{	
		$id = $this->request['id'] ? intval( $this->request['id'] ) : 0;
				
		if ( $type == 'new' )
		{
			$form['url']    = 'block_add_do';
			$form['title']  = $this->lang->words['add_block'];
			$form['button'] = $this->lang->words['add_block'];
            
            # Default Field
            $data = array( 
                          'template'   => 1, 
                          'show_block' => 1, 
                          'align'      => intval( $this->request['tab'] ) 
                         );
		}
		else
		{			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => 'block_id='.$id ) );

			if ( !$data['block_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_blocks_match_id'];
				$this->blocks();
				return;
			}
			
			$form['url']    = 'block_edit_do';
			$form['title']  = "{$this->lang->words['edit_block']} ".$block['title'];
			$form['button'] = $this->lang->words['edit_block'];
		}

		//-----------------------------------------
		// Setup form and form html
		//-----------------------------------------
        
		# Block Position
		$alignOption[] = array( '4', $this->lang->words['top_blocks'] );        
		$alignOption[] = array( '1', $this->lang->words['left_blocks'] );
		$alignOption[] = array( '2', $this->lang->words['main_blocks'] ); 
		$alignOption[] = array( '3', $this->lang->words['right_blocks'] ); 
		$alignOption[] = array( '5', $this->lang->words['bottom_blocks'] );                 

		$formFields['title']      = $this->registry->output->formInput( 'title', $data['title'] );
        $formFields['align']      = $this->registry->output->formDropdown( 'align', $alignOption, $data['align'] );
        $formFields['show_block'] = $this->registry->output->formYesNo( 'show_block', intval( $data['show_block'] ) );
        $formFields['template']   = $this->registry->output->formYesNo( 'template', intval( $data['template'] ) );
		$formFields['block_code'] = $this->registry->output->formTextArea( 'block_code', $data['block_code'], "60", "15" );
		
		$this->registry->output->html .= $this->html->blockForm( $form, $formFields, $data );	
	}	
	
	/*-------------------------------------------------------------------------*/
	// Save Block
	/*-------------------------------------------------------------------------*/	
	public function blockSave( $type='new' )
	{
		$id = intval( $this->request['id'] );        
        
		$save['title']      = trim( IPSText::stripslashes( IPSText::htmlspecialchars( $_POST['title'] ) ) );
        $save['name']       = IPSText::makeSeoTitle( $save['title'] );
        $save['align']      = intval( $this->request['align'] );
        $save['show_block'] = intval( $this->request['show_block'] );
        $save['template']   = intval( $this->request['template'] );
		$save['block_code'] = trim( IPSText::stripslashes( $_POST['block_code'] ) );
        
 		if ( !$save['name'] OR !$save['block_code'] )
		{				
			$this->registry->output->showError( $this->lang->words['all_fields_required'] );
		}       
	 
		if ( $type == 'new' )
		{
		  	$save['position'] = 0;
			$this->DB->insert( 'portal_blocks', $save );
			$this->registry->output->global_message = $this->lang->words['block_added'];
		}
		else
		{
			$this->DB->update( 'portal_blocks', $save, 'block_id='.$id );
			$this->registry->output->global_message = $this->lang->words['block_updated'];
		}

        $this->request['tab'] = $save['align'];
		$this->blocks();
	}
    
	/*-------------------------------------------------------------------------*/
	// Block Move
	/*-------------------------------------------------------------------------*/
	public function blockMove()
	{
	    /* Get ajax class */
        $classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
        $ajax   = new $classToLoad();

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		$position = 1;
 		
 		if( is_array($this->request['blocks']) AND count($this->request['blocks']) )
 		{
 			foreach( $this->request['blocks'] as $this_id )
 			{
 				$this->DB->update( 'portal_blocks', array( 'position' => $position ), 'block_id=' . $this_id ); 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}  

	/*-------------------------------------------------------------------------*/
	// Export Blcok
	/*-------------------------------------------------------------------------*/    
	protected function blockExport()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'blockexport' );
		$xml->addElement( 'portalblock', 'blockexport' );
        
        # Export all blocks?
        if( $this->request['id'] )
        {
            $_where = "block_id=".intval( $this->request['id'] );
        }
        	
        # Get portal blocks    	
		$this->DB->build( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => $_where ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			unset( $r['block_id'] );
			
			$xml->addElementAsRecord( 'portalblock', 'block', $r );
		}
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->registry->output->showDownload( $xmlData, 'portal_block.xml', '', 0 );
	} 
    
	/*-------------------------------------------------------------------------*/
	// Import Blcok
	/*-------------------------------------------------------------------------*/  
	protected function blockImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml( 'portal_block.xml' );

		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = "Upload failed. The Block XML file was missing, empty, or corrupted. ";
			$this->blocks();
			return;
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$blocks = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'portal_blocks' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$blocks[ $r['name'] ] = $r['block_id'];
		}
		
		//-----------------------------------------
		// Parse block
		//-----------------------------------------
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$entry = $xml->fetchElementsFromRecord( $block );
			$name  = ( $entry['name'] ) ? $entry['name'] : IPSText::makeSeoTitle( $entry['title'] );
			
			if ( $name )
			{          
				$array = array( 'name'		 => $name,
								'title'	     => $entry['title'],
								'align'	     => intval( $entry['align'] ),
                                'template'	 => intval( $entry['template'] ),
                                'position'	 => intval( $entry['position'] ),
                                'show_block' => intval( $entry['show_block'] ),
                                'block_code' => $entry['block_code']
								);
	
				if ( $blocks[ $name ] )
				{
					$this->DB->update( 'portal_blocks', $array, "block_id=" . $blocks[ $name ] );
				}
				else
				{
					$this->DB->insert( 'portal_blocks', $array );
				}
			}
		}
                    
		$this->registry->output->global_message = "Thank you, import is complete.";
		
		$this->blocks();
	}       
    
	/*-------------------------------------------------------------------------*/
	// Show Block
	/*-------------------------------------------------------------------------*/
	protected function blockShow()
	{
		$id = intval($this->request['id']);	
        
        $block = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => 'block_id='.$id ) );
			
		if ( ! $block['block_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['no_blocks_match_id'];
			$this->blocks();
			return;
		}

		$this->DB->update( 'portal_blocks', array( 'show_block' => intval( $this->request['show'] ) ), "block_id='".$id."'"  );
		
		$this->registry->output->global_message = $this->lang->words['block_show_changed'];
        
        $this->request['tab'] = $block['align'];
		$this->blocks();
		return;
	}      
    
	/*-------------------------------------------------------------------------*/
	// Delete Block
	/*-------------------------------------------------------------------------*/
	protected function blockDelete()
	{
		$id = intval($this->request['id']);	
			
        $block = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'portal_blocks', 'where' => 'block_id='.$id ) );
			
		if ( ! $block['block_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['no_blocks_match_id'];
			$this->blocks();
			return;
		}
		
		$this->DB->delete( 'portal_blocks', "block_id=".$id  );
		
		$this->registry->output->global_message = $this->lang->words['block_deleted'];
        $this->request['tab'] = $block['align'];
		$this->blocks();
		return;
	}    
}