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

class public_form_display_index extends ipsCommand
{	
	public function doExecute( ipsRegistry $registry )
	{
        $this->registry->getClass('formFunctions')->init();
        
        $this->post_key = isset( $this->request['post_key'] ) && $this->request['post_key'] ? $this->request['post_key'] : md5(microtime());
		
		switch ( ipsRegistry::$request['do'] )
		{
		    case 'view_form':
		    	$this->viewForm();
		    break;		  
		    case 'view_logs':
		    	$this->viewLogs();
		    break;
		    case 'view_log':
		    	$this->viewLog();
		    break;	 
	      
		     default:
		      $this->splash();
		      break;
		}
        
		# Add Javascript
		$this->registry->output->addToDocumentHead('raw', "<script type='text/javascript' src='{$this->settings['public_dir']}js/form.js'></script>" );        		

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
	// Splash Landing Page
	/*-------------------------------------------------------------------------*/    
    public function splash()
    {
        # Skip the rest and just show legacy form?
        if( $this->settings['fm_legacy_form'] )
        {
            $this->legacyForm(); 
            return;           
        }      
        
        # Show a list of forms
        if( $this->settings['form_landing_page'] == 'form_list' )
        {
            $this->viewFormList();
        }
        # Require a form url
        else if( $this->settings['form_landing_page'] == 'form_required' )
        {
            $this->registry->output->showError( 'no_form_selected' );
        } 
        # Show a selected form
        else
        {
            $this->viewForm( $this->settings['form_landing_page'] );    
        }      
    }
    
 	/*-------------------------------------------------------------------------*/
	// View Legacy Form
	/*-------------------------------------------------------------------------*/
	public function legacyForm()
	{   
        $formList = array();	   
       
		if( !is_array( $this->registry->getClass('formForms')->forms_data ) AND !count( $this->registry->getClass('formForms')->forms_data ) )
		{
			$this->registry->output->showError( 'no_forms_display' );
		}
        
        # Get a list of forms
		foreach( $this->registry->getClass('formForms')->forms_data[ 0 ] as $f_id => $f_data )
		{          
			if( isset( $this->registry->getClass('formForms')->viewForms[$f_id] ) )
			{
                # Skip if form has no fields.
                if( !is_array( $this->registry->getClass('formFields')->fields[ $f_id ] ) AND !count( $this->registry->getClass('formFields')->fields[ $f_id ] ) )
			    {
                    continue;
			    } 
                
				$formList[] = $f_data;                
			}			
		}
        
        $this->output = $this->registry->output->getTemplate('form')->legacySubjectSelect( $formList );      
		$this->page_title = $this->lang->words['title'];   
    }   
    
	/*-------------------------------------------------------------------------*/
	// View Form List
	/*-------------------------------------------------------------------------*/
	public function viewFormList() 
	{       
		$forms = array();  
        $memberIds = array();      		
		
		if( !is_array( $this->registry->getClass('formForms')->forms_data[ 0 ] ) AND !count( $this->registry->getClass('formForms')->forms_data[ 0 ] ) )
		{
			$this->registry->output->showError( 'no_forms_display' );
		}
        
		foreach( $this->registry->getClass('formForms')->forms_data[ 0 ] as $f_id => $f_data )
		{          
			if( isset( $this->registry->getClass('formForms')->viewForms[$f_id] ) )
			{
				$f_data['subforms']	= "";

				$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $f_data['form_id'] ), 'form' );
                
				if( !isset($f_data['_has_unread']) )
				{
					$f_data['_has_unread']	= ( $f_data['info']['date'] && $f_data['info']['date'] > $rtime ) ? 1 : 0;
				}	
	
          		if( $this->registry->permissions->check( 'logs', $this->registry->getClass('formForms')->form_data_id[ $f_data['form_id'] ]) )
        		{
          		    $f_data['info']['show_last_info'] = true;                      
        		}                  
    
				if( count($this->registry->getClass('formForms')->subform_data[$f_id]) > 0 )
				{
					$sub_links = array();
					
					foreach( $this->registry->getClass('formForms')->subform_data[$f_id] as $key => $subform_id )
					{
						if( isset( $this->registry->getClass('formForms')->viewForms[$subform_id] ) )
						{
							$subform_data = $this->registry->getClass('formForms')->form_data_id[ $subform_id ];
						
							if ( is_array( $subform_data ) )
							{
								$subform_time = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $subform_data['form_id'] ), 'form' );
								
								if( !isset($subform_data['new']) )
								{
									$subform_data['new'] = ( $subform_data['info']['date'] && $subform_data['info']['date'] > $subform_time ) ? 1 : 0;
								}

								$sub_links[] = $subform_data;
							}
						}
					}
					
					$f_data['subforms'] = $sub_links;
				}	
				
				$forms[ $f_data['form_id'] ] = $f_data;                
                                
                $memberIds[ $f_data['info']['member_id'] ] = $f_data['info']['member_id'];
			}			
		} 
        
		if( count($memberIds) )
		{
			$members	= IPSMember::load( $memberIds );
			
			$members[0] = IPSMember::setUpGuest();
			
			foreach( $forms as $k => $v )
			{
				$forms[ $k ] = array_merge( $forms[ $k ], IPSMember::buildDisplayData( $members[ $v['info']['member_id'] ] ) );
			}
		}  
  
        $this->page_title = $this->lang->words['title'];        
        $this->output .= $this->registry->output->getTemplate('form')->formListOverview( $forms );        	
    }
    
	/*-------------------------------------------------------------------------*/
	// View Form
	/*-------------------------------------------------------------------------*/
	public function viewForm( $formid="" ) 
	{       
        $formID     = ( $formid ) ? intval( $formid ) : intval( $this->request['id'] );        
		$form       = $this->registry->getClass('formForms')->form_data_id[ $formID ];        
        $formFields = array();
        
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
        
           
        
        # Process real time fields.        
        foreach( $this->registry->getClass('formFields')->fields[ $formID ] as $field )
        {
            if( $field['field_type'] == 'editor' )
            {
                if( $field['field_value'] )
                {
                    $this->editor->setContent( $field['field_value'] );    
                }
								
				$field['field_data'] = $this->editor->show( "custom_fields[{$field['field_id']}]" );  
            }
            
            $field = $this->registry->getClass('formFunctions')->parseQuickTags( $field, $formID );
            
            $formFields[] = $field;
        }
        
        # Load attachments?        
        if( $form['options']['attachments'] AND $this->memberData['g_fs_allow_attach'] )
        {
      		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
    		$class_attach = new $classToLoad( $this->registry );
    		$class_attach->type				= 'form';
    		$class_attach->attach_post_key	= $this->post_key;
            $this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
            
    		$class_attach->init();
    		$class_attach->getUploadFormSettings();   
            
            $attachmentUpload = $this->registry->getClass('output')->getTemplate('post')->uploadForm( $this->post_key, 'form', $class_attach->attach_stats, '0' );            
        }     
        
        # Load captcha if necessary
		if( !$this->memberData['g_fs_bypass_captcha'] )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();
		}        
        
        $form['form_rules'] = IPSText::getTextClass('bbcode')->preDisplayParse( $this->registry->getClass('formFunctions')->parseQuickTags( $form['form_rules'], $formID ) );
        
        # Lets display everything now
        $this->output .= $this->registry->output->getTemplate('form')->formView( $this->post_key, $form, $formFields, $captchaHTML, $attachmentUpload );        
                
        # Setup meta and the rest
        if( $form['description'] )
        {
            $this->registry->output->addMetaTag( 'keywords', $form['form_name'] . ' ' . str_replace( "\n", " ", str_replace( "\r", "", strip_tags( $form['description'] ) ) ), TRUE );
            $this->registry->output->addMetaTag( 'description', $form['description'], FALSE, 155 );    
        }
        
		$this->page_title = $form['form_name'];	
		$this->registry->getClass('formForms')->formNav( $formID );
        
        return $this->output;
    }
    
	/*-------------------------------------------------------------------------*/
	// View Logs
	/*-------------------------------------------------------------------------*/
	public function viewLogs() 
	{    
	    $logs = array();
        $st	  = intval( $this->request['st'] );
        
        # Setup Parser
 		IPSText::getTextClass('bbcode')->parse_html      = 1;
		IPSText::getTextClass('bbcode')->parse_smilies   = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode    = 1;
		IPSText::getTextClass('bbcode')->parsing_section = 'form_forms';        
        
		# Filter Settings
		$sort_by    = ( $this->settings['fm_logs_default_sort_field'] ) ? $this->settings['fm_logs_default_sort_field'] : 'log_date';
		$sort_order = ( $this->settings['fm_logs_default_sort_order'] ) ? $this->settings['fm_logs_default_sort_order'] : 'DESC';
		$perpage    = ( $this->settings['fm_logs_per_page'] ) ? intval( $this->settings['fm_logs_per_page'] ) : "20";

		# Filter Request
		$this->request['per_page']   = ( $this->request['per_page'] ) ? intval( $this->request['per_page'] ) : $perpage;
		$this->request['sort_by']    = ( $this->request['sort_by'] ) ? $this->request['sort_by'] : $sort_by;
		$this->request['sort_order'] = ( $this->request['sort_order'] ) ? $this->request['sort_order'] : $sort_order;
        
        $orderSQL = $this->request['sort_by']." ".$this->request['sort_order'];       
       
        # Get our logs
		$this->DB->build( array( 
								'select'   => "l.*",
								'from'	   => array( 'form_logs' => 'l' ),
 								'where'	   => $this->DB->buildRegexp( "perm_3", $this->member->perm_id_array ),
								'group'    => 'l.log_id',
								'order'    => $orderSQL,
								'limit'    => array( $st, intval( $this->request['per_page'] ) ),
								'add_join' => array(
													array(
															'select' => 'i.*',
															'from'   => array( 'permission_index' => 'i' ),
															'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
															'type'   => 'left',
														),
													array(
															'select' => 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
															'from'   => array( 'members' => 'mem' ),
															'where'  => "mem.member_id=l.member_id",
															'type'   => 'left',
														),
													)													
									)		);
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
		    $is_read = $this->registry->getClass( 'classItemMarking' )->isRead( array( 'forumID' => $r['log_form_id'], 'itemID' => $r['log_id'], 'itemLastUpdate' => $r['log_date'] ), 'form' );

            $r['_isRead'] = $is_read;
            $r['log_icon'] = ipsRegistry::getClass( 'class_forums' )->fetchTopicFolderIcon( $r, 0, $is_read );
            
    		if( !$r['_isRead'] )
    		{
    			$r['_unreadUrl'] = $this->registry->output->buildSEOUrl( 'app=form&amp;do=view_log&amp;id='.$r['log_id'], 'public' );
    		}    
            
            $r['message'] = IPSText::getTextClass('bbcode')->preDisplayParse( $r['message'] );       
          
            $logs[ $r['log_id'] ] = $r;                                
            $memberIds[ $r['member_id'] ] = $r['member_id'];
		} 

		if( count($memberIds) )
		{
			$members	= IPSMember::load( $memberIds );			
			$members[0] = IPSMember::setUpGuest();
			
			foreach( $logs as $k => $v )
			{
				$logs[ $k ] = array_merge( $logs[ $k ], IPSMember::buildDisplayData( $members[ $v['member_id'] ] ) );
			}
		}
        
        # Pagination
		$this->DB->build( array( 
								'select'   => "l.*",
								'from'	   => array( 'form_logs' => 'l' ),
 								'where'	   => $this->DB->buildRegexp( "perm_3", $this->member->perm_id_array ),
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
		$this->DB->execute();  
        
		$pages = $this->registry->output->generatePagination( array( 'totalItems'		 => $this->DB->GetTotalRows(),
																	 'itemsPerPage'		 => intval( $this->request['per_page'] ),
																	 'currentStartValue' => $st,
																	 'baseUrl'			 => "app=form&do=view_logs&sort_by={$this->request['sort_by']}&sort_order={$this->request['sort_order']}&per_page={$this->request['per_page']}",
															)		);                       
        
        
        $this->output .= $this->registry->output->getTemplate('form')->logsOverview( $logs, $pages ); 
        
		$this->page_title = $this->lang->words['form_logs'];
		$this->registry->output->addNavigation( $this->lang->words['form_logs'], '' );        	 
    }
    
	/*-------------------------------------------------------------------------*/
	// View Form Log
	/*-------------------------------------------------------------------------*/
	public function viewLog() 
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
											array(
													'select' => 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
													'from'   => array( 'members' => 'mem' ),
													'where'  => "mem.member_id=l.member_id",
													'type'   => 'left',
												),
											array( 
													'select'	=> 'pp.*',
													'from'		=> array( 'profile_portal' => 'pp' ),
													'where'		=> 'pp.pp_member_id=l.member_id',
													'type'		=> 'left'
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
        
        # Get Field Values    
		$this->DB->build( array( 'select' => '*', 'from' => 'form_fields_values', 'where' => 'value_log_id='.$log['log_id'] ) );
		$this->DB->execute();
                
		while( $value = $this->DB->fetch() )
		{
            $fieldValues[ $value['value_field_id'] ] = $value['value_value'];
        }
        
		# Load editor
		if ( !is_object($this->editor) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$this->editor = new $classToLoad();
		} 
        
        # Display Attachments
		if( $form['options']['attachments'] AND $this->memberData['g_fs_allow_attach'] )
		{
			$log['message'] = preg_replace( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", ""   , $log['message'] );
			
			$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' ); 
      		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
    		$class_attach = new $classToLoad( $this->registry );
			$class_attach->type  = 'form';
			$class_attach->init();
			
			$attachHtml            = $class_attach->renderAttachments( $log['message'], array( $log['log_id'] => $log['log_id'] ) );
			$log['attachmentHtml'] = $attachHtml[ $log['log_id'] ]['attachmentHtml'];					
		}          
        
        # Setup Parser
        IPSText::getTextClass('bbcode')->parse_html             = 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'form_forms';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
         
        $log['message'] = IPSText::getTextClass('bbcode')->preDisplayParse( $log['message'] );
        
        $log = IPSMember::buildDisplayData( $log );
        
		# Display custom fields        
        foreach( $this->registry->getClass('formFields')->fields[ $formID ] as $field )
        {     
            if( $field['field_type'] == 'editor' )
            {               
                //$fieldValues[ $field['field_id'] ] = IPSText::getTextClass('bbcode')->preDisplayParse( $fieldValues[ $field['field_id'] ] );
                continue;
            }
            
		    $logFields[] = array( 'name' => $field['field_title'], 'value' => $fieldValues[ $field['field_id'] ] );        
        } 
        
        # Lets display everything now
        $this->output .= $this->registry->output->getTemplate('form')->viewLog( $log, $form, $logFields ); 
        
		# Mark log as read
	  	$this->registry->classItemMarking->markRead( array( 'forumID' => $log['log_form_id'], 'itemID' => $log['log_id'] ), 'form' );             
        
		$this->page_title = "{$form['form_name']} {$this->lang->words['log']} #{$log['log_id']}";	
		$this->registry->output->addNavigation( $this->lang->words['form_logs'], '' ); 
    }    
}
?>