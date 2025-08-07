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

class admin_form_forms_forms extends ipsCommand
{
	public $html;
	
	public function doExecute( ipsRegistry $registry )
	{
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_forms' );
		$this->form_code    = $this->html->form_code    = 'module=forms&amp;section=forms';
		$this->form_code_js = $this->html->form_code_js = 'module=forms&section=forms';	
        $this->lang->loadLanguageFile( array( 'admin_form' ), 'form' );   
		
		switch ($this->request['do'])
		{                
			case 'forms':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'view_forms' );			
				$this->forms();
				break;
  			case 'forms_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_forms' );
				$this->formsForm('new');
				break;
			case 'forms_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_forms' );			
				$this->formsSave('new');
				break;				
			case 'forms_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_forms' );			
				$this->formsForm('edit');
				break;				
			case 'forms_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_forms' );			
				$this->formsSave('edit');
				break;
			case 'forms_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'delete_forms' );			
				$this->formsDelete();
				break;                                												
			case 'forms_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_forms' );			
				$this->formsMove();
				break;  
                
  			case 'fields_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_fields' );
				$this->fieldsForm('new');
				break;
			case 'fields_add_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_fields' );			
				$this->fieldsSave('new');
				break;				
			case 'fields_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_fields' );			
				$this->fieldsForm('edit');
				break;				
			case 'fields_edit_do':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_fields' );			
				$this->fieldsSave('edit');
				break;
			case 'fields_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'delete_fields' );			
				$this->fieldsDelete();
				break;                                												
			case 'fields_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_fields' );			
				$this->fieldsMove();
				break;					
			case 'rebuild_cache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'view_forms' );			
				$this->rebuildCache();
				break;
                					
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'view_forms' );			
				$this->forms();
				break;				
		}
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
    
    /*-------------------------------------------------------------------------*/
    // Form List
    /*-------------------------------------------------------------------------*/
    public function forms()
    {
        $forms  = array(); 
        $fields = array();   
        
        $formID = intval( $this->request['form_id'] );
        
    	$this->DB->build( array( 'select' => '*', 'from' => 'form_forms', 'order' => "position ASC", 'where' => ( $formID ) ? "form_id=".$formID : "" ) );
    	$this->DB->execute();
        	 
    	while( $r = $this->DB->fetch() )
    	{		 	
    		$forms[] = $r;		 	
    	}
        
    	$this->DB->build( array( 'select' => '*', 'from' => 'form_fields', 'order' => 'field_position ASC', 'where' => ( $formID ) ? "field_form_id=".$formID : "" ) );
    	$this->DB->execute();		
        
    	while( $field = $this->DB->fetch() )
    	{    
            $fields[ $field['field_form_id'] ][ $field['field_id'] ] = $field;
        }   
        
        foreach( $forms as $form )
        { 
            $temp_html = "";
     
    		if( count( $fields[ $form['form_id'] ] ) )
    		{
        		foreach( $fields[ $form['form_id'] ] as $id => $field )
        		{	    			
       				$temp_html = $this->html->formFields( $form, $fields[ $form['form_id'] ] );    					
        		}            
            }   
            
            $formhtml .= $this->html->renderForm( $temp_html, $form );	   
        }        
        
        $this->registry->output->html .= $this->html->mainFormScreen( $formhtml );   
    }
    
	/*-------------------------------------------------------------------------*/
	// Form Form
	/*-------------------------------------------------------------------------*/	
	public function formsForm( $type='new' )
	{       
		$id        = intval( $this->request['id'] );
		$data      = array();
        $fieldList = array();
				
		if ( $type == 'new' )
		{
			$form['url']    = 'forms_add_do';
			$form['title']  = $this->lang->words['add_form'];
            
            $data = array( 'pm_settings' => array( 'sender'        => '-1',
                                                   'receiver'      => $this->memberData['member_id'],
                                                   'receiver_type' => '1',
                                                   'subject'       => $this->lang->words['default_pm_subject'],
                                                   'message'       => $this->lang->words['default_pm_message'],
                                                  ),
                            'email_settings' => array( 'enable'        => 1,
                                                       'sender'        => $this->settings['email_out'],
                                                       'receiver'      => $this->memberData['email'],
                                                       'receiver_type' => $this->memberData['email'],
                                                       'subject'       => $this->lang->words['default_email_subject'],
                                                       'message'       => $this->lang->words['default_email_message'],
                                                  ),
                            'topic_settings' => array( 'enable' => 1,
                                                       'author' => $this->memberData['member_id'],
                                                       'title'  => $this->lang->words['default_topic_title'],
                                                       'post'   => $this->lang->words['default_topic_post'],
                                                  ),                                                  
                           'options'       => array( 'log_message'  => $this->lang->words['default_log_message'],
                                                     'enable_rss'   => 1,
                                                     'confirm_type' => 1,
                                                     'attachments'  => 0,
                                                    )
                         );
           
		}
		else
		{			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'form_forms', 'where' => 'form_id='.$id ) );

			if ( !$data['form_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_forms_id_match'];
				$this->forms();
				return;
			}
			
			$form['url']   = 'forms_edit_do';
			$form['title'] = $this->lang->words['edit_form'];
            
            $data['options']        = unserialize( $data['options'] );
            $data['pm_settings']    = unserialize( $data['pm_settings'] );
            $data['email_settings'] = unserialize( $data['email_settings'] );
            $data['topic_settings'] = unserialize( $data['topic_settings'] );
		}
        
		//-----------------------------------------
		// Forums Dropdown
		//-----------------------------------------
	
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php' );
	
		$aff = new admin_forum_functions( $this->registry );
		$aff->forumsInit();
		$dropdown_forums = $aff->adForumsForumList(1);

		//-----------------------------------------
		// Fields Dropdown
		//-----------------------------------------
        
		if( is_array( $this->registry->getClass('formFields')->fields[ $data['form_id'] ] ) AND count( $this->registry->getClass('formFields')->fields[ $data['form_id'] ] ) )
		{
            foreach( $this->registry->getClass('formFields')->fields[ $data['form_id'] ] as $field )
            {
                $fieldList[] = array( $field['field_id'], $field['field_title'] );    
            }
            
            array_unshift( $fieldList, array( 0, "-- {$this->lang->words['select_field']} --" ) );
		}        	
	
		//-----------------------------------------
		// Groups Dropdown
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'groups' ) );
		$this->DB->execute();
						
		while( $row = $this->DB->fetch() )
		{
			if ( $row['g_access_cp'] )
			{
				$row['g_title'] .= "( {$this->lang->words['staff']} )";
			}
			
			$dropdown_groups[] = array( $row['g_id'], $row['g_title'] );
		} 
        
        # Load New Editor
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();        
        
		if ( $data['form_rules'] )
		{
			$this->editor->setContent( $data['form_rules'], 'form_forms' );
		}    

        $show_editor = $this->editor->show( 'form_rules', array( 'height' => 250 ) );        
        
    	# Method Array
    	$receiverType[] = array( "1", $this->lang->words['type_individual'] );	
    	$receiverType[] = array( "2", $this->lang->words['type_groups'] );	
        
    	# Confirm Type
    	$confirmTypeList[] = array( "1", $this->lang->words['message'] );	
    	$confirmTypeList[] = array( "2", $this->lang->words['redirect_url'] );                   
	
		//-----------------------------------------
		// Setup form and form html
		//-----------------------------------------
				
        # General        
		$data['form_name']    = $this->registry->output->formInput( 'form_name', $data['form_name'] );
        $data['description']  = $this->registry->output->formTextarea( 'description', IPSText::br2nl( $data['description'] ) );
		$data['log_message']  = $this->registry->output->formTextarea( 'log_message', IPSText::br2nl( $data['options']['log_message'] ) );
        $data['enable_rss']   = $this->registry->output->formYesNo( 'enable_rss', intval( $data['options']['enable_rss'] ) );
        $data['confirm_type'] = $this->registry->output->formDropdown( 'confirm_type', $confirmTypeList, intval( $data['options']['confirm_type'] ) );
        $data['confirm_data'] = $this->registry->output->formTextarea( 'confirm_data', IPSText::br2nl( $data['options']['confirm_data'] ) );
        $data['attachments']  = $this->registry->output->formYesNo( 'attachments', $data['options']['attachments'] );
		$data['form_rules']   = $show_editor; 
        
        if( count( $fieldList ) )
        {
            $data['confirm_email'] = $this->registry->output->formDropdown( 'confirm_email', $fieldList, intval( $data['options']['confirm_email'] ) );    
        } 
             
        
        # PM Settings        
		$data['pm_enable']  = $this->registry->output->formYesNo( 'pm_enable', $data['pm_settings']['enable'] );
		$data['pm_subject']   = $this->registry->output->formInput( 'pm_subject', $data['pm_settings']['subject'] );
		$data['pm_type']  = $this->registry->output->formDropdown( 'pm_type', $receiverType, $data['pm_settings']['receiver_type'], 'send_method_pm' );
		$data['pm_sender']  = $this->registry->output->formInput( 'pm_sender', $data['pm_settings']['sender'] );
		$data['pm_receiver']  = $this->registry->output->formInput( 'pm_receiver', $data['pm_settings']['receiver'] );        
        $data['pm_groups']  = $this->registry->output->formMultiDropdown( "pm_groups[]", $dropdown_groups, explode( ",", $data['pm_settings']['groups'] ), 5 );
        $data['pm_message'] = $this->registry->output->formTextarea( 'pm_message', IPSText::br2nl( $data['pm_settings']['message'] ) );

        # Email Settings        
		$data['email_enable']  = $this->registry->output->formYesNo( 'email_enable', $data['email_settings']['enable'] );
		$data['email_subject'] = $this->registry->output->formInput( 'email_subject', $data['email_settings']['subject'] );
		$data['email_type']  = $this->registry->output->formDropdown( 'email_type', $receiverType, $data['email_settings']['receiver_type'], 'send_method_email' );
		$data['email_sender'] = $this->registry->output->formInput( 'email_sender', $data['email_settings']['sender'] );
		$data['email_receiver'] = $this->registry->output->formInput( 'email_receiver', $data['email_settings']['receiver'] );        
        $data['email_groups']  = $this->registry->output->formMultiDropdown( "email_groups[]", $dropdown_groups, explode( ",", $data['email_settings']['groups'] ), 5 );
        $data['email_message'] = $this->registry->output->formTextarea( 'email_message', IPSText::br2nl( $data['email_settings']['message'] ) );
        
        # Topic Settings        
		$data['topic_enable'] = $this->registry->output->formYesNo( 'topic_enable', $data['topic_settings']['enable'] );
		$data['topic_author'] = $this->registry->output->formInput( 'topic_author', $data['topic_settings']['author'] );
		$data['topic_title']  = $this->registry->output->formInput( 'topic_title', $data['topic_settings']['title'] );
		$data['topic_forum']  = $this->registry->output->formDropdown( "topic_forum", $dropdown_forums, $data['topic_settings']['forum'] );
        $data['topic_post']   = $this->registry->output->formTextarea( 'topic_post', IPSText::br2nl( $data['topic_settings']['post'] ) );
        
		#Permissions
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( ipsRegistry::instance() );
		$form['permissions']	= $permissions->adminPermMatrix( 'form', $this->registry->getClass('formForms')->form_data_id[ $data['form_id'] ], 'form', '', false );
                 		
		$this->registry->output->html .= $this->html->formsForm( $form, $data );	
	}  
    
	/*-------------------------------------------------------------------------*/
	// Forms Save
	/*-------------------------------------------------------------------------*/
	private function formsSave( $type='new' )
	{
		if( !$this->request['form_name'] )
		{
			$this->registry->output->showError( 'form_name_required' );
		}
        
        # Form Rules Save
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$content = $editor->process( $_POST['form_rules'] );
		
		IPSText::getTextClass('bbcode')->parse_html		 = 1;
		IPSText::getTextClass('bbcode')->parse_smilies	 = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode	 = 1;
		IPSText::getTextClass('bbcode')->parsing_section = 'form_forms';

 		$formRules = IPSText::getTextClass( 'bbcode' )->preDBParse( $content );         
        
        # Form Options
		$formOptions = array( 'log_message'   => $this->request['log_message'],							
							  'enable_rss'    => intval( $this->request['enable_rss'] ),
                              'confirm_type'  => intval( $this->request['confirm_type'] ),
                              'confirm_data'  => $this->request['confirm_data'],
                              'confirm_email' => $this->request['confirm_email'],
                              'attachments'   => intval( $this->request['attachments'] ),
						);

		$save = array('form_name'     => $this->request['form_name'],
                      'name_seo'      => IPSText::makeSeoTitle( $this->request['form_name'] ),
                      'description'   => $this->request['description'],
                      'pm_settings'   => serialize( array( 'enable'        => intval( $this->request['pm_enable'] ),
                                                           'sender'        => $this->request['pm_sender'],
                                                           'receiver'      => $this->request['pm_receiver'],
                                                           'receiver_type' => $this->request['pm_type'],
                                                           'groups'        => is_array( $this->request['pm_groups'] ) ? IPSText::cleanPermString( implode( ",", $this->request['pm_groups'] ) ) : '',
                                                           'subject'       => $this->request['pm_subject'],
                                                           'message'       => $this->request['pm_message']
                                                  )      ),
                      'email_settings' => serialize( array( 'enable'        => intval( $this->request['email_enable'] ),
                                                            'sender'        => $this->request['email_sender'],
                                                            'receiver'      => $this->request['email_receiver'],
                                                            'receiver_type' => $this->request['email_type'],
                                                            'groups'        => is_array( $this->request['email_groups'] ) ? IPSText::cleanPermString( implode( ",", $this->request['email_groups'] ) ) : '',
                                                            'subject'       => $this->request['email_subject'],
                                                            'message'       => $this->request['email_message'],
                                                  ) ),
                      'topic_settings' => serialize( array( 'enable' => intval( $this->request['topic_enable'] ),
                                                            'author' => intval( $this->request['topic_author'] ),
                                                            'forum'  => intval( $this->request['topic_forum'] ),                                                      
                                                            'title'  => $this->request['topic_title'],
                                                            'post'   => $this->request['topic_post']
                                                   )      ),                      
                      'options'       => serialize( $formOptions ),
                      'form_rules'    => $formRules,
                     );
             
        # PM Checks
        if( $this->request['pm_enable'] )
        {
    		if( $this->request['pm_type'] == 1 AND !$this->request['pm_receiver'] )
    		{
                $this->registry->output->showError( 'pm_receiver_required' );  
    		}                         
    		if( $this->request['pm_type'] == 2 AND !$this->request['pm_groups'] )
    		{
                $this->registry->output->showError( 'pm_group_required' );  
    		}
    		if( !$this->request['pm_subject'] )
    		{
                $this->registry->output->showError( 'pm_subject_required' );  
    		}            
        }

        # Email Checks
        if( $this->request['email_enable'] )
        {        
    		if( $this->request['email_receiver'] )
    		{ 
        		if( !preg_match( "/\b,\b/i", $this->request['email_receiver'] ) ) 
        		{
        			if( !IPSText::checkEmailAddress( $this->request['email_receiver'] ) )	
        			{
        				$this->registry->output->showError( 'email_format_issue' );		
        			}		
        		}
        		else
        		{
        			$address_array = explode( ",", $this->request['email_receiver'] );
        			
        			foreach( $address_array as $email )
        			{
        				if( !IPSText::checkEmailAddress( $email ) )	
        				{
        					$this->registry->output->showError( 'email_format_issue' );		
        				}
        			}	
        		}		  
            }      
        
    		if( $this->request['email_type'] == 2 AND !$this->request['email_groups'] )
    		{
                $this->registry->output->showError( 'email_groups_required' );  
    		} 
    		if( !$this->request['email_subject'] )
    		{
                $this->registry->output->showError( 'email_subject_required' );  
    		}
        }
        
        # Topic Checks
		if( $this->request['topic_enable'] && !$this->request['topic_author'] OR !$this->request['topic_title'] )
		{
            $this->registry->output->showError( 'topic_fields_required' );  
		}          
           
		if ( $type == 'new' )
		{
			$max_pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(form_id) as next_id', 'from' => 'form_forms' ) );
			$save['position'] = intval( $max_pos['next_id'] ) + 1;
			
			$this->DB->insert( 'form_forms', $save );
            $form_id = $this->DB->getInsertId();
            
            # Save Permissions
    		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
    		$permissions	= new $classToLoad( ipsRegistry::instance() );
    		$permissions->savePermMatrix( $this->request['perms'], $form_id, 'form', 'form' );
    		
            $this->registry->getClass('formForms')->rebuild_form_info( $form_id );
    		$this->registry->getClass('formForms')->rebuild_forms();                      			
			
	        $this->registry->output->global_message = $this->lang->words['form_added'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms' );
		}
		else		
		{			
			$form_id = intval( $this->request['id'] );
			$this->DB->update( 'form_forms', $save, "form_id=".$form_id );
            
            # Save Permissions
    		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
    		$permissions	= new $classToLoad( ipsRegistry::instance() );
    		$permissions->savePermMatrix( $this->request['perms'], $form_id, 'form', 'form' );
    		
            $this->registry->getClass('formForms')->rebuild_form_info( $form_id );
    		$this->registry->getClass('formForms')->rebuild_forms();                       
	        
	        $this->registry->output->global_message = $this->lang->words['form_updated'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms' );
		}
	}      

	/*-------------------------------------------------------------------------*/
	// Forms Delete
	/*-------------------------------------------------------------------------*/
	private function formsDelete()
	{
		$id = intval( $this->request['id'] );
		$this->DB->delete( 'form_forms', "form_id=".$id );
        
		$this->registry->output->global_message = $this->lang->words['form_deleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms' );		
	}
    
	/*-------------------------------------------------------------------------*/
	// Forms Move
	/*-------------------------------------------------------------------------*/    
	private function formsMove()
	{
		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax = new classAjax();
		
		# Check Md5
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['secure_key_error'] );
			exit();
		}
 		
 		$position = 1;
 		
 		if( is_array($this->request['form']) AND count($this->request['form']) )
 		{
 			foreach( $this->request['form'] as $this_id )
 			{
 				$this->DB->update( 'form_forms', array( 'position' => $position ), 'form_id=' . $this_id ); 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}
    
	/*-------------------------------------------------------------------------*/
	// Field Form
	/*-------------------------------------------------------------------------*/	
	public function fieldsForm( $type='new' )
	{       
		$id   = intval( $this->request['id'] );
		$data = array();
				
		if ( $type == 'new' )
		{
			$form['url']    = 'fields_add_do';
			$form['title']  = $this->lang->words['add_field'];
            
            $field = array( 'field_form_id'  => intval( $this->request['form_id'] ),
                            'field_title'    => $this->lang->words['default_field_title'],
                            'field_value'    => $this->lang->words['default_field_value'], 
                            'field_required' => 0, 
                            'field_text'     => $this->lang->words['default_field_text'],
                            'field_type'     => "dropdown", 
                            'field_values' => array( 1 => $this->lang->words['default_field_values_1'], 2 => $this->lang->words['default_field_values_2'], 3 => $this->lang->words['default_field_values_3'] )                                                                    
                          );            
		}
		else
		{			
			$field = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'form_fields', 'where' => 'field_id='.$id ) );

			if ( !$field['field_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_fields_id_match'];
				$this->fields();
				return;
			}
			
			$form['url']   = 'fields_edit_do';
			$form['title'] = $this->lang->words['edit_field'];
            
            $field['field_values'] = unserialize( $field['field_options'] );    		
    		$field_extras = unserialize( $field['field_extras'] );            
		}
        
		# Form dropdown                
    	$this->DB->build(array( 'select' => '*', 'from' => 'form_forms', 'order' => 'position ASC' ) );
    	$this->DB->execute();    
    
    	while($d = $this->DB->fetch()) 
		{
			$formList[] = array( $d['form_id'], $d['form_name'] );
		}           
	
    	# Javify field options
        $field['field_values'] = IPSText::simpleJsonEncode( $field['field_values'] );
    	
    	# Setup Field Extras
        $field['size']       = ( $field_extras['size'] ) ? $field_extras['size'] : "25";
        $field['multi_size'] = ( $field_extras['multi_size'] ) ? $field_extras['multi_size'] : "5";
        $field['rows']       = ( $field_extras['rows'] ) ? $field_extras['rows'] : "6";
        $field['cols']       = ( $field_extras['cols'] ) ? $field_extras['cols'] : "45";
        
    	# Setup field type array       
    	$typeList = array ( array ( 0 => 'input', 1 => $this->lang->words['input_text'] ), array ( 0 => 'dropdown', 1 => $this->lang->words['dropdown'] ), array ( 0 => 'multiselect', 1 => $this->lang->words['dropdown_multiselect'] ), array ( 0 => 'radiobutton', 1 => $this->lang->words['radio_buttons'] ), array ( 0 => 'checkbox', 1 => $this->lang->words['check_boxes'] ), array ( 0 => 'textarea', 1 => $this->lang->words['text_area'] ), array ( 0 => 'editor', 1 => $this->lang->words['full_editor'] ), array ( 0 => 'password', 1 => $this->lang->words['password'] ) );	
	
		//-----------------------------------------
		// Form Variables
		//-----------------------------------------	
        	
		$form['field_title']    = $this->registry->output->formInput( 'field_title', $field['field_title'] );
		$form['field_value']    = $this->registry->output->formTextarea( 'field_value', IPSText::br2nl( $field['field_value'] ) );
        $form['field_text']     = $this->registry->output->formTextarea( 'field_text', IPSText::br2nl( $field['field_text'] ) );
        $form['field_type']     = $this->registry->output->formDropdown( "field_type", $typeList, $field['field_type'], "field_type" );		
        $form['field_form_id']  = $this->registry->output->formDropdown( "field_form_id", $formList, $field['field_form_id'] );		
		$form['field_required'] = $this->registry->output->formYesNo( 'field_required', intval( $field['field_required'] ) );       
        		
		$this->registry->output->html .= $this->html->fieldsForm( $form, $field );	
	} 
    
	/*-------------------------------------------------------------------------*/
	// Field Save
	/*-------------------------------------------------------------------------*/
	protected function fieldsSave( $type='add' )
	{                  
	    # Check required fields
    	if( !$this->request['field_title'] )
    	{ 
     		$this->registry->output->showError( 'field_title_required' );	
     	}                 	

	    # Check Form
        $form = $this->DB->buildAndFetch( array( 'select' => 'form_id', 'from' => 'form_forms', 'where' => "form_id=". intval( $this->request['field_form_id'] ) ) );    
    
        if( !$form['form_id'] )
        {
            $this->registry->output->showError( 'no_forms_id_match' );    
        }
        
        # Setup field extras
        $extras = array( 'size'       => intval( $this->request['size'] ),
                         'multi_size' => intval( $this->request['multi_size'] ),
                         'rows'       => intval( $this->request['rows'] ),        
                         'cols'       => intval( $this->request['cols'] )     
                       );
                       
        # Parse field options                       
		if ( isset( $_POST['options'] ) AND is_array( $_POST['options'] ) and count( $_POST['options'] ) )
		{				
			foreach( $_POST['options'] as $id => $value )
			{
				if ( !$id OR !$value )
				{
					continue;
				}
				
				$options[ $id ] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $value ) ) ), 255 );
			}
		}   
        
        # Setup Save Array
        $save['field_title']    = $this->request['field_title'];
        $save['field_name']     = IPSText::makeSeoTitle( $this->request['field_title'] );	
        $save['field_value']    = $this->request['field_value'];
        $save['field_text']     = $this->request['field_text'];
        $save['field_type']     = $this->request['field_type'];                
        $save['field_required'] = intval( $this->request['field_required'] );
        $save['field_value']    = $this->request['field_value'];
        $save['field_value']    = $this->request['field_value']; 
        $save['field_value']    = $this->request['field_value'];
        $save['field_value']    = $this->request['field_value'];
        $save['field_value']    = $this->request['field_value'];         
        $save['field_form_id']  = intval( $this->request['field_form_id'] );    
    	$save['field_extras']   = serialize( $extras );
        $save['field_options']  = serialize( $options );        
        $save['field_data']     = $this->registry->getClass('formFields')->generateField( $save );
        
		if ( $type == 'new' )
		{
			$max_pos = $this->DB->buildAndFetch( array( 'select' => 'MAX(field_id) as next_id', 'from' => 'form_fields' ) );
			$save['field_position'] = intval($max_pos['next_id']) + 1;
			
			$this->DB->insert( 'form_fields', $save );
            
            $this->registry->getClass('formFields')->rebuild_fields(1);		
			
	        $this->registry->output->global_message = $this->lang->words['field_added'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms&amp;form_id='.intval( $this->request['field_form_id'] ) );
		}
		else		
		{			
			$id = intval( $this->request['id'] );
			$this->DB->update( 'form_fields', $save, "field_id=".$id );
            
            $this->registry->getClass('formFields')->rebuild_fields(1);
	        
	        $this->registry->output->global_message = $this->lang->words['field_updated'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms&amp;form_id='.intval( $this->request['field_form_id'] ) );
		}
	}
    
	/*-------------------------------------------------------------------------*/
	// Fields Move
	/*-------------------------------------------------------------------------*/    
	private function fieldsMove()
	{
		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax = new classAjax();
		
		# Check Md5
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['secure_key_error'] );
			exit();
		}
 		
 		$position = 1;
 		
 		if( is_array($this->request['field']) AND count($this->request['field']) )
 		{
 			foreach( $this->request['field'] as $this_id )
 			{
 				$this->DB->update( 'form_fields', array( 'field_position' => $position ), 'field_id=' . $this_id ); 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	} 
         
	/*-------------------------------------------------------------------------*/
	// Rebuild Field/Form Cache
	/*-------------------------------------------------------------------------*/
	protected function rebuildCache()
	{        
        $this->registry->getClass('formFields')->rebuild_fields( 1 );        
        $this->registry->getClass('formForms')->rebuild_form_info();
        $this->registry->getClass('formForms')->rebuild_forms();
        
		$this->registry->output->global_message = $this->lang->words['form_field_cache_rebuilt'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms' );
	}

	/*-------------------------------------------------------------------------*/
	// Field Delete
	/*-------------------------------------------------------------------------*/
	protected function fieldsDelete()
	{
		$this->DB->delete( 'form_fields', "field_id=".intval( $this->request['id'] ) );
		
		$this->registry->output->global_message = $this->lang->words['field_deleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=forms' );        
	}  
}
?>