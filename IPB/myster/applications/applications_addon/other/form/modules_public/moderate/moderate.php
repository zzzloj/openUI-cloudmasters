<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_form_moderate_moderate extends ipsCommand
{	
	public function doExecute( ipsRegistry $registry )
	{
        $this->registry->getClass('formFunctions')->init();
        
		if ( !$this->memberData['g_fs_moderate_logs'] )
		{
			$this->registry->output->showError( 'no_moderate_logs_perms' );
		}        
		
		switch ( ipsRegistry::$request['do'] )
		{
		    case 'delete_log':
		    	$this->deleteLog();
		    break;            		  
		    case 'logs_multimod':
		    	$this->logsMultimod();
		    break;
            
		    case 'reply_send':                 
		    	$this->replySend();             
		      break;            
		    case 'reply_log':                 
		     default:
		    	$this->replyForm();             
		      break;
		}		

		$this->registry->output->setTitle( $this->page_title.' - '.$this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
        
		# Removal of this copyright code without purchasing copyright removal is illegal and violates your license agreement.
        if( !$this->settings['devfuse_copy_num'] )
        {
            $this->registry->output->addContent( "<br /><div class='ipsType_smaller desc lighter' style='clear: both; text-align:right;'><a href='http://www.devfuse.com/products/' title='DevFuse products page'>Form Manager</a> v1.0.0 &copy; ".date("Y")." <a href='http://www.devfuse.com/' title='DevFuse home page'>DevFuse</a></div>" );    
        }        
         
		$this->registry->output->sendOutput();
	}
    
	/*-------------------------------------------------------------------------*/
	// Log Reply Form
	/*-------------------------------------------------------------------------*/
	public function replyForm() 
	{     
        $logID = intval( $this->request['id'] );
        
		$log = $this->DB->buildAndFetch( array( 
						'select'   => "l.*",
						'from'	   => array( 'form_logs' => 'l' ),
						'where'	   => "l.log_id=".$logID." AND ". $this->DB->buildRegexp( "perm_3", $this->member->perm_id_array ),
						'group'    => 'l.log_id',
						'add_join' => array(
											array(
													'select' => 'i.*',
													'from'   => array( 'permission_index' => 'i' ),
													'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
													'type'   => 'left',
												),                                              
											)													
							)		);
    
        $formID     = $log['log_form_id'];
		$form       = $this->registry->getClass('formForms')->form_data_id[ $formID ];
        $formFields = $this->registry->getClass('formFields')->fields[ $formID ];        
        
        # Check exist and can view
		if( !$log['log_id'] )
		{
			$this->registry->output->showError( 'no_log_id_match' );		
		}        
		if( !$form['form_id'] )
		{
			$this->registry->output->showError( 'no_form_id_match' );		
		}
		if( !$this->registry->permissions->check( 'view', $this->registry->getClass('formForms')->form_data_id[ $formID ] ) )
		{
			$this->registry->output->showError( 'no_view_forms_perms' );
		} 
        
		# Load editor
		if ( !is_object($this->editor) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$this->editor = new $classToLoad();
		}   
        
        # Setup Parser
        IPSText::getTextClass('bbcode')->parse_html             = 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'form_forms';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others']; 
        
		if ( $this->editor->getRteEnabled() )
		{
			$replyMessage = "[quote]<br />{$log['message']}".'[/quote]'."<br />";
		}
		else
		{
			$log['message'] = IPSText::getTextClass('bbcode')->preEditParse( $log['message'] );			
			$replyMessage = "[quote]\n{$log['message']}".'[/quote]'."\n";
		}        
        
		$this->editor->setContent( $replyMessage );
 		$show_editor = $this->editor->show( 'message', array( 'height' => 350 ) );      
         
        $senderEmail = ( $form['email_settings']['sender'] ) ? $form['email_settings']['sender'] : $this->settings['email_out'];
        
        $form['email_settings']['subject'] = $this->registry->getClass('formFunctions')->parseQuickTags( $form['email_settings']['subject'], $formID );  
        
        # Lets display everything now
        $this->output .= $this->registry->output->getTemplate('form')->replyForm( $log, $form, $senderEmail, $show_editor );
        
		$this->page_title = $form['form_name']." - ". $this->lang->words['log_reply'];	
		$this->registry->output->addNavigation( $this->lang->words['log_reply'], '' );                     
    }
    
	/*-------------------------------------------------------------------------*/
	// Reply Send
	/*-------------------------------------------------------------------------*/
	protected function replySend() 
	{   
		# Check for empty required fields
        if( !$this->request['sender_email'] OR !$this->request['receiver_email'] OR !$this->request['subject'] )
        {
            $this->registry->output->showError( 'required_fields' );
        }
	
        if( !IPSText::checkEmailAddress( $this->request['sender_email'] ) OR !IPSText::checkEmailAddress( $this->request['receiver_email'] ) ) 
        {
            $this->registry->output->showError( 'email_format_issue' );
        }   
    
		# Load editor
		if ( !is_object($this->editor) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$this->editor = new $classToLoad();
		}   
        
        # Setup Parser
        IPSText::getTextClass('bbcode')->parse_html             = 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'form_forms';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];       
		
		$replyMessage = $this->editor->process( $_POST['message'] );	  
		$replyMessage = IPSText::getTextClass('bbcode')->preDbParse( $replyMessage );
        
        # Send reply email
 		if ( $this->settings['email_use_html'] )
		{
			IPSText::getTextClass('email')->setHtmlEmail( true );
			IPSText::getTextClass('email')->setHtmlTemplate( $replyMessage );
		}
		else
		{
			IPSText::getTextClass('email')->setPlainTextTemplate( stripslashes( IPSText::getTextClass('email')->cleanMessage( $replyMessage ) ) );
		}	    
		
		IPSText::getTextClass( 'email' )->subject = $this->request['subject'];			
		IPSText::getTextClass( 'email' )->buildMessage( array() );
		IPSText::getTextClass( 'email' )->to      = $this->request['receiver_email'];
        IPSText::getTextClass( 'email' )->from    = $this->request['sender_email'];		
		IPSText::getTextClass( 'email' )->sendMail();        
        
        $this->registry->output->redirectScreen( $this->lang->words['log_reply_sent'], $this->settings['base_url'] . "app=form&do=view_logs" );
	}    
    
	/*-------------------------------------------------------------------------*/
	// Delete Form Log
	/*-------------------------------------------------------------------------*/
	protected function deleteLog() 
	{    
        $logID = intval( $this->request['id'] );
        
		$log = $this->DB->buildAndFetch( array( 
						'select'   => "l.*",
						'from'	   => array( 'form_logs' => 'l' ),
						'where'	   => "l.log_id=".$logID." AND ". $this->DB->buildRegexp( "perm_3", $this->member->perm_id_array ),
						'group'    => 'l.log_id',
						'add_join' => array(
											array(
													'select' => 'i.*',
													'from'   => array( 'permission_index' => 'i' ),
													'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
													'type'   => 'left',
												),                                              
											)													
							)		);
    
        $formID = $log['log_form_id'];
		$form   = $this->registry->getClass('formForms')->form_data_id[ $formID ];        
        
        # Check exist and can view
		if( !$log['log_id'] )
		{
			$this->registry->output->showError( 'no_log_id_match' );		
		}        
		if( !$form['form_id'] )
		{
			$this->registry->output->showError( 'no_form_id_match' );		
		}
		if( !$this->registry->permissions->check( 'view', $this->registry->getClass('formForms')->form_data_id[ $formID ] ) )
		{
			$this->registry->output->showError( 'no_view_forms_perms' );
		} 
        
        # Auth key check        
        if( $this->member->form_hash != $this->request['secure_key'] ) 
        {
            $this->registry->output->showError( 'secure_key_error' );   
        }  
        
        # Remove attachments
        if( $log['has_attach'] )
        {
            $classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
            $class_attach = new $classToLoad( $this->registry );        
			$class_attach->type  = 'form';
			$class_attach->init();
			
			$class_attach->bulkRemoveAttachment( array( $logID ) );
        }      
        
        # Delete and redirect back to logs
        $this->DB->delete( 'form_logs', "log_id=".$logID );
        $this->DB->delete( 'form_fields_values', "value_log_id=".$logID );
        
        $this->registry->getClass('formForms')->rebuild_form_info( $formID ); 
        
        $this->registry->output->redirectScreen( $this->lang->words['log_deleted'], $this->registry->getClass('output')->buildSEOUrl( "do=view_logs", 'publicWithApp' ) ); 
    }
    
   	/*-------------------------------------------------------------------------*/
	// Multi Delete Form Logs
	/*-------------------------------------------------------------------------*/
	protected function logsMultimod() 
	{ 
        # Auth key check        
        if( $this->member->form_hash != $this->request['auth_key'] ) 
        {
            $this->registry->output->showError( 'secure_key_error' );
        }
        
		$ids       = array();
        $attachIds = array();
	 		
	 	foreach( $this->request as $key => $value )
	 	{
	 		if ( preg_match( "/^lid_(\d+)$/", $key, $match ) )
	 		{
	 			if ($this->request[$match[0]])
	 			{
	 				$ids[] = $match[1];
	 			}
	 		}
	 	}	 	
	
	 	$logCount = count( $ids );
          		
	 	if( !$logCount )
	 	{
	 		$this->registry->output->showError( 'no_checked_boxes' );
	 	} 
         
        $ids = IPSLib::cleanIntArray( $ids ); 
        
        # Get attachment logs
        $this->DB->build( array( 'select' => 'log_id, has_attach', 'from' => 'form_logs', 'where'  => "log_id IN (".implode( ',',$ids ).")" )	);
        $this->DB->execute();
			
		while( $log = $this->DB->fetch() )
		{
            if( $log['has_attach'] )
            {
                $attachIds[ $log['log_id'] ] = $log['log_id'];  
            }
        }
        
        # Bulk remove attachments
        if( count( $attachIds ) )
        {
            $classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
            $class_attach = new $classToLoad( $this->registry );        
			$class_attach->type  = 'form';
			$class_attach->init();
			
			$class_attach->bulkRemoveAttachment( $attachIds );
        }
        
 		if( $this->request['action'] == 'delete' )
	 	{        
            $this->DB->delete( 'form_logs', "log_id IN (".implode( ',',$ids ).")" );
            $this->DB->delete( 'form_fields_values', "value_log_id IN (".implode( ',',$ids ).")" );
        }  
 		elseif($this->request['action'] == 'export_csv')
 		{

		}
 		elseif($this->request['action'] == 'export_sql')
 		{
	
		} 
        
        $this->registry->getClass('formForms')->rebuild_forms(); 
        
        $this->registry->output->silentRedirect( $this->settings['base_url']."app=form&do=view_logs" );	       
                	   
    }
}
?>