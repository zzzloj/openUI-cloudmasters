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

class public_form_post_form extends ipsCommand
{	
	public function doExecute( ipsRegistry $registry )
	{
		$this->registry->getClass('formFunctions')->init();
		
        $this->submitForm();		

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
	// Submit Form
	/*-------------------------------------------------------------------------*/
	protected function submitForm() 
	{    
        $formID     = intval( $this->request['id'] );        
		$form       = $this->registry->getClass('formForms')->form_data_id[ $formID ];        
        $formFields = array();
        
		$this->page_title = $this->lang->words['forms_submitted'];
		$this->registry->output->addNavigation( $this->lang->words['forms_submitted'] , '' );   
         
        # Check exist and can view
		if( !$form['form_id'] )
		{
			$this->registry->output->showError( 'no_form_id_match' );		
		}        	
		if( !$this->registry->permissions->check( 'view', $this->registry->getClass('formForms')->form_data_id[ $formID ] ) )
		{
			$this->registry->output->showError( 'no_view_forms_perms' );
		}
        
        # Do we have fields for form?
		if( !is_array( $this->registry->getClass('formFields')->fields[ $formID ] ) AND !count( $this->registry->getClass('formFields')->fields[ $formID ] ) )
		{
			$this->registry->output->showError( 'no_fields_for_form' );
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
        
		//-----------------------------------------
		// Captcha check applies?
		//----------------------------------------- 
        
		if( !$this->memberData['g_fs_bypass_captcha'] )
		{
			if ( $this->registry->getClass('class_captcha')->validate() !== TRUE )
			{
				$this->registry->output->showError( 'captcha_error' );
			}				
		} 
        
		//-----------------------------------------
		// Any submit wait time?
		//----------------------------------------- 
        
		if( $this->memberData['g_fs_submit_wait'] )
		{
			$lastLog = $this->DB->buildAndFetch( array( 'select' => 'MAX(log_date) as last_submit', 'from' => 'form_logs', 'where' => "ip_address='".$this->member->ip_address."'" ) );
	
			if( ( $lastLog['last_submit'] + $this->memberData['g_fs_submit_wait'] ) > IPS_UNIX_TIME_NOW )
			{       
			    $timeLeft = round( ( $this->memberData['g_fs_submit_wait'] - ( IPS_UNIX_TIME_NOW - $lastLog['last_submit'] ) ) / 60 );
             
				$this->lang->words['submit_wait_error'] = str_replace("<#EXTRA#>", $timeLeft, $this->lang->words['submit_wait_error'] );
				$this->registry->output->showError( 'submit_wait_error' );
			}
		}        

		//-----------------------------------------
		// Check required fields
		//----------------------------------------- 
      
        foreach( $this->registry->getClass('formFields')->fields[ $formID ] as $field )
        {            
            if( $field['field_required'] AND empty( $this->request['custom_fields'][ $field['field_id'] ] ) )
            {
                $this->lang->words['field_required'] = str_replace("<#EXTRA#>", $field['field_title'], $this->lang->words['field_required'] );	 	  		
				$this->registry->output->showError( 'field_required' );                
            }
            
            $formFields[] = $field;
        }
        
		//-----------------------------------------
		// Record Log
		//-----------------------------------------  
        
        $log_save = array( 'log_form_id'  => $form['form_id'],
                           'member_id'    => $this->memberData['member_id'],
                           'member_name'  => $this->memberData['members_display_name'],
                           'member_email' => ( $form['options']['confirm_email'] ) ? $this->request['custom_fields'][ intval( $form['options']['confirm_email'] ) ] : $this->memberData['email'],
                           'log_date'     => IPS_UNIX_TIME_NOW,
                           'ip_address'   => $this->member->ip_address,
                           'log_post_key' => $this->request['post_key'],
                           'message'      => IPSText::getTextClass('bbcode')->preDBParse( $this->registry->getClass('formFunctions')->parseQuickTags( $form['options']['log_message'], $formID ) ),
                         );                        
        
        $this->DB->insert( 'form_logs', $log_save );
        $logID = $this->DB->getInsertId();
        
		//-----------------------------------------
		// Record custom fields
		//----------------------------------------- 
        
        foreach( $this->registry->getClass('formFields')->fields[ $formID ] as $field )
        {       
            $saveFields = array( 'value_field_id'  => $field['field_id'],
                                 'value_member_id' => $this->memberData['member_id'],
                                 'value_form_id'   => $form['form_id'],
                                 'value_log_id'    => $logID,
                                 'value_value'     => $this->request['custom_fields'][ $field['field_id'] ]            
                               );
                               
            $this->DB->insert( 'form_fields_values', $saveFields );
        }      
        
		//-----------------------------------------
		// Create topic
		//-----------------------------------------
        
        if( $form['topic_settings']['enable'] )
        {
            $topicAuthor = ( $form['topic_settings']['author'] == '-1' ) ? $this->memberData['member_id'] : intval( $form['topic_settings']['author'] );
            
            $this->createTopic( $topicAuthor, $form['topic_settings']['forum'], $this->registry->getClass('formFunctions')->parseQuickTags( $form['topic_settings']['title'], $formID ), $this->registry->getClass('formFunctions')->parseQuickTags( $form['topic_settings']['post'], $formID ) );
        }
        
		//-----------------------------------------
		// Send out PMs
		//-----------------------------------------
        
        if( $form['pm_settings']['enable'] )
        {
            # PM single user
		  	if( $form['pm_settings']['receiver_type'] == '1' )
		  	{            
                $pmReceiver = intval( $form['pm_settings']['receiver'] );
            }
            # PM entire groups
            else
            {            
                $pmReceiver = array();
                
 				$this->DB->build( array( 'select' => 'member_id, member_group_id', 'from'   => 'members', 'where' => 'member_group_id IN(' . IPSText::cleanPermString( $form['pm_settings']['groups'] ) . ' ) ' ) );
				$this->DB->execute();
                
				while( $member = $this->DB->fetch() )
				{
				    $pmReceiver[] = $member['member_id'];
                }                
            }
            
            $pmSender = ( $form['pm_settings']['sender'] == '-1' ) ? $this->memberData['member_id'] : intval( $form['pm_settings']['sender'] );
            
            $this->sendPM( $pmReceiver, $pmSender, $this->registry->getClass('formFunctions')->parseQuickTags( $form['pm_settings']['subject'], $formID ), $this->registry->getClass('formFunctions')->parseQuickTags( $form['pm_settings']['message'], $formID ) );
        } 
        
		//-----------------------------------------
		// Send out Emails
		//-----------------------------------------
        
        if( $form['email_settings']['enable'] )
        {
            # Email single user
		  	if( $form['email_settings']['receiver_type'] == '1' )
		  	{       
				# Comma seperator values
				if( preg_match( "/\b,\b/i", $form['email_settings']['receiver'] ) )
				{		  	   
                    $emailAddress = explode( ",", $form['email_settings']['receiver'] );
                }
                else
                {
                    $emailAddress = $form['email_address'];    
                }                
            }
            # Email entire groups
            else
            {            
                $emailAddress = array();
                
 				$this->DB->build( array( 'select' => 'member_id, email, member_group_id', 'from'   => 'members', 'where' => 'member_group_id IN(' . IPSText::cleanPermString( $form['email_settings']['groups'] ) . ' ) ' ) );
				$this->DB->execute();
                
				while( $member = $this->DB->fetch() )
				{
				    $emailAddress[] = $member['email'];
                }                
            }
            
            $this->sendEmail( $emailAddress, $form['email_settings']['sender'], $this->registry->getClass('formFunctions')->parseQuickTags( $form['email_settings']['subject'], $formID ), $this->registry->getClass('formFunctions')->parseQuickTags( $form['email_settings']['message'], $formID ) );
        }  
        
		//-----------------------------------------
		// Save Attachments
		//-----------------------------------------     
               
        if( $form['options']['attachments'] AND $this->memberData['g_fs_allow_attach'] AND $logID )
        {            
      		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
    		$class_attach = new $classToLoad( $this->registry );
            $class_attach->type            = 'form';
            $class_attach->attach_post_key = $this->request['post_key'];
            $class_attach->attach_rel_id   = $logID;
            $class_attach->init();
        
            $return = $class_attach->postProcessUpload();
            
            $this->DB->update( 'form_logs', array( 'has_attach' => intval( $return['count'] ) ), "log_id=".$logID );
        }        
        
		//-----------------------------------------
		// Form Confirmation
		//----------------------------------------- 
        
        $this->registry->getClass('formForms')->rebuild_form_info( $formID );  
        
        # Confirm email
        $confirmEmail = ( $form['options']['confirm_email'] ) ? $this->request['custom_fields'][ intval( $form['options']['confirm_email'] ) ] : $this->memberData['email']; 
        $this->sendEmail( $confirmEmail, $form['email_settings']['sender'], $this->registry->getClass('formFunctions')->parseQuickTags( $this->lang->words['confirm_email_subject'], $formID ), $this->registry->getClass('formFunctions')->parseQuickTags( $this->lang->words['confirm_email_message'], $formID ) );
                   
        # Confirm message
        if( $form['options']['confirm_type'] == '1' )
        {
            $confirmMessage = ( $form['options']['confirm_data'] ) ? IPSText::getTextClass('bbcode')->preDisplayParse( $form['options']['confirm_data'] ) : $this->lang->words['form_confirm_message'];
            $this->output .= $this->registry->output->getTemplate('form')->formConfirmPage( $this->registry->getClass('formFunctions')->parseQuickTags( $confirmMessage, $formID ) );     
        }
        # Confirm url
        else
        {
            if( $form['options']['confirm_data'] AND IPSText::xssCheckUrl( $form['options']['confirm_data'] ) )            
            {
                $this->registry->output->silentRedirect( trim( $form['options']['confirm_data'] ) );    
            }
        }             
    }

	/*-------------------------------------------------------------------------*/
	// Create Topic
	/*-------------------------------------------------------------------------*/
    protected function sendPM( $pmAuthor, $pmSender, $pmSubject, $pmMessage  )
    {
        if( !$pmAuthor OR !$pmSender OR !$pmSubject )
        {
            return false;
        }
        
        if( count( $pmAuthor ) AND is_array( $pmAuthor ) )
        {               
            foreach( $pmAuthor as $member_id )
            {
        		if ( $this->editor->getRteEnabled() )
        		{
        			$pmMessage = IPSText::getTextClass('bbcode')->convertForRTE( $pmMessage );	
        		}
        		else
        		{
        			$pmMessage = IPSText::getTextClass('bbcode')->preEditParse( $pmMessage );
        		}                    
                					
    			try
    			{
    				require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
    				$this->messenger = new messengerFunctions( $this->registry );
    		
    			 	$this->messenger->sendNewPersonalTopic( $member_id, $pmSender, array(), $pmSubject, $pmMessage, 
    												array( 'forcePm'  => 1, 'isSystem' => ( $this->settings['fm_system_pms'] ) ? TRUE : FALSE )
    												);
    			}
    			catch( Exception $error )
    			{
    			}
                
                //unset( $pmMessage );
            }
        }
        else
        {            
    		if ( $this->editor->getRteEnabled() )
    		{
    			$pmMessage = IPSText::getTextClass('bbcode')->convertForRTE( $pmMessage );	
    		}
    		else
    		{
    			$pmMessage = IPSText::getTextClass('bbcode')->preEditParse( $pmMessage );
    		}                    
            					
			try
			{
				require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
				$this->messenger = new messengerFunctions( $this->registry );
		
			 	$this->messenger->sendNewPersonalTopic( $pmAuthor, $pmSender, array(), $pmSubject, $pmMessage, 
												array( 'forcePm'  => 1, 'isSystem' => ( $this->settings['fm_system_pms'] ) ? TRUE : FALSE  )
												);
			}
			catch( Exception $error )
			{

			}            
        }
    }    
    
	/*-------------------------------------------------------------------------*/
	// Send Email
	/*-------------------------------------------------------------------------*/
    protected function sendEmail( $emailAddress, $emailSender, $emailSubject, $emailMessage  )
    {
        if( !$emailAddress OR !$emailSubject OR !$emailMessage )
        {
            return false;
        }      
        
        if( count( $emailAddress ) AND is_array( $emailAddress ) )
        {
            foreach( $emailAddress as $email )
            {
                # Problem!!!
                if( !IPSText::checkEmailAddress( $email ) ) 
                {
                    continue;
                }                
                
        		if ( $this->settings['email_use_html'] )
        		{
        			IPSText::getTextClass('email')->setHtmlEmail( true );
        			IPSText::getTextClass('email')->setHtmlTemplate( $emailMessage );
        		}
        		else
        		{
        			IPSText::getTextClass('email')->setPlainTextTemplate( stripslashes( IPSText::getTextClass('email')->cleanMessage( $emailMessage ) ) );
        		}				    
        		
        		IPSText::getTextClass( 'email' )->subject = $emailSubject;			
        		IPSText::getTextClass( 'email' )->buildMessage( array() );
        		IPSText::getTextClass( 'email' )->to      = $email;		
        		IPSText::getTextClass( 'email' )->sendMail();
            }
        }
        else
        {
            if( !IPSText::checkEmailAddress( $emailAddress ) ) 
            {
                return;
            }           

     		if ( $this->settings['email_use_html'] )
    		{
    			IPSText::getTextClass('email')->setHtmlEmail( true );
    			IPSText::getTextClass('email')->setHtmlTemplate( $emailMessage );
    		}
    		else
    		{
    			IPSText::getTextClass('email')->setPlainTextTemplate( stripslashes( IPSText::getTextClass('email')->cleanMessage( $emailMessage ) ) );
    		}				    
    		
    		IPSText::getTextClass( 'email' )->subject = $emailSubject;			
    		IPSText::getTextClass( 'email' )->buildMessage( array() );
    		IPSText::getTextClass( 'email' )->to      = $emailAddress;
            IPSText::getTextClass( 'email' )->from    = $emailSender;		
    		IPSText::getTextClass( 'email' )->sendMail();           
        }      
    }

	/*-------------------------------------------------------------------------*/
	// Create Topic
	/*-------------------------------------------------------------------------*/
    protected function createTopic( $topicAuthor, $forumId, $topicTitle, $topicPost )
    {        
        if( !$topicAuthor OR !$forumId OR !$topicTitle )
        {
            return false;
        }
        
		# Get our forum and posting class
        require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
        $appClass    = new app_class_forums( ipsRegistry::instance() );

        require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
        $this->_postClass = new classPost( ipsRegistry::instance() );
        $this->_postClass->setSettings( array( 'enableSignature' => 1, 'enableEmoticons' => 1, 'post_htmlstatus' => 0, 'enableTracker'   => 0 ) );

        # Temp bug patch, IPS require a tag?
        $_POST['ipsTags'] = ( $this->settings['tags_min'] ) ? 'form' : '';        
    
		$post_contents = IPSText::UNhtmlspecialchars(  $topicPost  );
        
        $this->_postClass->setIsPreview( false );
        $this->_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $forumId ] );
        $this->_postClass->setForumID( $forumId );
        $this->_postClass->setPostContent( $post_contents );
        $this->_postClass->setAuthor( $topicAuthor );
        $this->_postClass->setPublished( true );        
        $this->_postClass->setTopicTitle( $topicTitle );
        
        # Try this out for some of the classPost problems.
		$this->_postClass->setBypassPermissionCheck( true );
		
        try
        {
            if( $this->_postClass->addTopic() )
            {
            	$topic_data = $this->_postClass->getTopicData();  			            	
	        }
        }
        catch( Exception $error )
        {							
        }        
    }
}
?>