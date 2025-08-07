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

class admin_form_manage_manage extends ipsCommand
{
	public $html;
	
	public function doExecute( ipsRegistry $registry )
	{
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_manage' );
		$this->form_code    = $this->html->form_code    = 'module=manage&amp;section=manage';
		$this->form_code_js = $this->html->form_code_js = 'module=manage&section=manage';	   
		$this->lang->loadLanguageFile( array( 'admin_form' ), 'form' );
        
		switch ($this->request['do'])
		{
			case 'converter':				
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'access_converter' );
				$this->converter();
				break;
			case 'converter_step1':				
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'access_converter' );
				$this->converter_step1();
				break;                
			case 'converter_step2':				
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'access_converter' );
				$this->converter_step2();
				break;
			case 'converter_step3':				
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'access_converter' );
				$this->converter_step3();
				break;                
                                		  
			case 'settings':              					
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_settings' );			
				$this->settings();
				break;				
		}
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
    
    /*-------------------------------------------------------------------------*/
    // Contact System Converter
    /*-------------------------------------------------------------------------*/
    protected function converter()
    {
        $missingTables  = array();        
        $databaseChecks = array( 'contact_fields', 'contact_logs', 'contact_subjects', 'form_fields', 'form_fields_values', 'form_forms', 'form_logs' );
        
        foreach( $databaseChecks as $table )        
        {
            if( !$this->DB->checkForTable( $table ) )
            {
                $missingTables[] = $table;
            }
        }  
        
        if( count( $missingTables ) )
        {
            $this->registry->output->showError( $this->lang->words['converter_missing_tables'] . implode( ", ", $missingTables ) );    
        }

        $this->registry->output->html .= $this->html->converterForm();   
    } 
    
    /*-------------------------------------------------------------------------*/
    // Contact System Converter Step 1 (Subjects)
    /*-------------------------------------------------------------------------*/
    protected function converter_step1()
    {    
        $tableDeletes = array( 'form_fields', 'form_fields_values', 'form_forms', 'form_logs' );
        
        foreach( $tableDeletes as $table )        
        {
            $this->DB->delete( $table );
        }    

        # Go through each subject 
		$this->DB->build( array( 'select' => '*',  'from' => 'contact_subjects' ));
		$outer = $this->DB->execute();

        while( $subject = $this->DB->fetch( $outer ))
        {	
            # Setup Form Details
    		$save = array(
                          'form_id'       => $subject['id'],
                          'form_name'     => $subject['name'],                          
                          'name_seo'      => IPSText::makeSeoTitle( $subject['name'] ), 
                          'options'       => serialize( array( 'log_message'  => $this->lang->words['default_log_message'],							
							                                   'enable_rss'   => intval( $this->settings['cs_enable_rss'] ),
                                                               'confirm_type' => 1,
                                                               'confirm_data' => '',
                                                               'attachments'  => ( $subject['attach_type'] ) ? 1 : 0,
						                              )      ),
                          'pm_settings'   => serialize( array( 'enable'        => intval( $subject['pm_enable'] ),
                                                               'sender'        => '-1',
                                                               'receiver'      => $subject['pm_author'],
                                                               'receiver_type' => intval( $subject['pm_method'] ),
                                                               'groups'        => $subject['pm_groups'],
                                                               'subject'       => $this->lang->words['default_pm_subject'],
                                                               'message'       => $this->lang->words['default_pm_message']
                                                  )      ),
                          'email_settings' => serialize( array( 'enable'        => intval( $subject['email_enable'] ),
                                                                'sender'        => $subject['email_address'],
                                                                'receiver'      => $subject['email_address'],
                                                                'receiver_type' => intval( $subject['email_method'] ),
                                                                'groups'        => $subject['email_groups'],
                                                                'subject'       => $this->lang->words['default_email_subject'],
                                                                'message'       => $this->lang->words['default_email_message'],
                                                  ) ),
                          'topic_settings' => serialize( array( 'enable' => intval( $subject['topic_enable'] ),
                                                                'author' => intval( $subject['topic_author'] ),
                                                                'forum'  => intval( $subject['topic_forum'] ),                                                      
                                                                'title'  => $this->lang->words['default_topic_title'],
                                                                'post'   => $this->lang->words['default_topic_post']
                                                   )      ),                                                      
                          'position'      => intval( $subject['position'] ),
                          'form_rules'    => ( $this->settings['cs_info'] ) ? $this->settings['cs_info'] : '',
                         );                
            
            # Insert Form
            $this->DB->insert( 'form_forms', $save );
            
            # Setup Form Submissions
            $perm_save = array( 'app' => 'form', 'perm_type' => 'form', 'perm_type_id' => intval( $subject['id'] ), 'perm_view' => IPSText::cleanPermString( $subject['perms'] ), 'perm_2' => IPSText::cleanPermString( $subject['perms'] ), 'perm_3' => IPSText::cleanPermString( $this->settings['cs_logs'] ) );
            $this->DB->insert( 'permission_index', $perm_save );           			
        }     

		$this->registry->output->redirect( "{$this->settings['base_url']}{$this->form_code}&do=converter_step2&conversion_type={$this->request['conversion_type']}", $this->lang->words['converter_step1_done'], 5 );
    }
    
    /*-------------------------------------------------------------------------*/
    // Contact System Converter Step 2 (Fields)
    /*-------------------------------------------------------------------------*/
    protected function converter_step2()
    {           
        $defaultFields      = array();
        $emailConfirmFields = array();
        
        # Go through each subject 
		$this->DB->build( array( 'select' => 'id',  'from' => 'contact_subjects' ));
		$outer = $this->DB->execute();

        while( $subject = $this->DB->fetch( $outer ))
        {
            # Get any custom fields for this subject
    		$this->DB->build( array( 'select' => '*', 'from' => 'contact_fields', 'where' => "FIND_IN_SET('".$subject['id']."', subject_id) > 0" ));
    		$outer_field = $this->DB->execute();
    
            while( $field = $this->DB->fetch( $outer_field ))
            {
                # Setup Field Details
        		$save = array('field_form_id'  => $subject['id'],
                              'field_title'    => $field['title'],                          
                              'field_name'     => IPSText::makeSeoTitle( $field['title'] ),                      
                              'field_value'    => $field['value'],
                              'field_text'     => $field['text'],                              
                              'field_type'     => $field['type'],
                              'field_required' => intval( $field['required'] ),                             
                              'field_options'  => $field['options'],
                              'field_extras'   => $field['field_extras'],
                              'field_data'     => $field['field_data'], 
                              'field_position' => intval( $field['position'] ),
                             );                         
                
                # Insert Fields
                $this->DB->insert( 'form_fields', $save ); 
            }
            
            # Add fixed contact fields.
    		$defaultFields = array( 
                                    array('field_form_id'  => $subject['id'],
                                          'field_title'    => 'Your Name',                          
                                          'field_name'     => 'your-name',  
                                          'field_value'    => '{member_name}',                      
                                          'field_type'     => 'input',
                                          'field_required' => 1,                             
                                          'field_extras'   => serialize( array( 'size'  => 30 ) ),
                                         ),
                                    array('field_form_id'  => $subject['id'],
                                          'field_title'    => 'Email Address',                          
                                          'field_name'     => 'email-address',
                                          'field_value'    => '{member_email}',                                          
                                          'field_text'     => 'Please enter an address we can reply to if necessary.',                        
                                          'field_type'     => 'input',
                                          'field_required' => 1,                             
                                          'field_extras'   => serialize( array( 'size'  => 45 ) ),
                                         ),
                                    array('field_form_id'  => $subject['id'],
                                          'field_title'    => 'Contact Message',                          
                                          'field_name'     => 'contact-message',                   
                                          'field_type'     => 'editor',
                                          'field_required' => 1,                             
                                         ),                                                                                           
                                  ); 
                         
            # Add our fixed fields from contact             
            foreach( $defaultFields as $key => $field_save )
            {
                $this->DB->insert( 'form_fields', $field_save );
            }
        }

        $this->registry->output->redirect( "{$this->settings['base_url']}{$this->form_code}&do=converter_step3&conversion_type={$this->request['conversion_type']}", $this->lang->words['converter_step2_done'], 5 );	
    }
    
    /*-------------------------------------------------------------------------*/
    // Contact System Converter Step 3 (Logs)
    /*-------------------------------------------------------------------------*/
    protected function converter_step3()
    {    
        $subjectNames  = array();
        
        # Go through each subject 
		$this->DB->build( array( 'select' => 'id,name',  'from' => 'contact_subjects' ));
		$outer = $this->DB->execute();

        while( $subject = $this->DB->fetch( $outer ))
        { 
            $subjectNames[ $subject['name'] ] = $subject;
            $lastSubjectId = $subject['id'];
        }
        
        # Go through each log 
		$this->DB->build( array( 'select' => '*',  'from' => 'contact_logs' ));
		$outer = $this->DB->execute();

        while( $log = $this->DB->fetch( $outer ))
        {
            # Setup Log Data
    		$save = array('log_form_id'  => ( $subjectNames[ $log['subject'] ]['id'] ) ? $subjectNames[ $log['subject'] ]['id'] : intval( $lastSubjectId ),
                          'member_id'    => intval( $log['member_id'] ),                          
                          'member_name'  => $log['name'], 
                          'member_email' => $log['useremail'],                      
                          'log_date'     => $log['date'],
                          'ip_address'   => $log['ip'],                              
                          'message'      => $log['message'],
                          'has_attach'   => ( $log['attach_type'] == 'contact_log' ) ? 1 : 0,                             
                         );
            
            # Insert Logs
            $this->DB->insert( 'form_logs', $save );             
        }
        
        # Update legacy setting
        if( $this->request['conversion_type'] == 'single' )
        {
    	    $this->DB->update('core_sys_conf_settings', array( 'conf_value' => 1 ), "conf_key='fm_legacy_form'");
            $this->DB->update('core_sys_conf_settings', array( 'conf_value' => 'form_required' ), "conf_key='form_landing_page'");           
        }  
        else
        {
    	    $this->DB->update('core_sys_conf_settings', array( 'conf_value' => 0 ), "conf_key='fm_legacy_form'");
            $this->DB->update('core_sys_conf_settings', array( 'conf_value' => 'form_list' ), "conf_key='form_landing_page'");            
        }  
        
        # Rebuild settings cache
        $classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core').'/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
    	$settings    = new $classToLoad();
    	$settings->makeRegistryShortcuts( $this->registry );
    	$settings->settingsRebuildCache();    
        
        # Rebuild field and form cache
        $this->registry->getClass('formFields')->rebuild_fields( 1 );        
        $this->registry->getClass('formForms')->rebuild_form_info();
        $this->registry->getClass('formForms')->rebuild_forms();        
        
        $this->registry->output->html .= $this->html->converterDone();
    }
    
	/*-------------------------------------------------------------------------*/
	// Settings
	/*-------------------------------------------------------------------------*/
	protected function settings()
	{	
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core').'/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$settings    = new $classToLoad();
		$settings->makeRegistryShortcuts( $this->registry );	   
       
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
		$settings->form_code	= $settings->html->form_code    = 'module=settings&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=settings&section=settings';
        
        ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
	
		$this->request['conf_title_keyword']	= 'form';
		$settings->return_after_save			= $this->settings['base_url'] . $this->html->form_code . '&amp;do=settings';

		$settings->_viewSettings();
	}
}
?>