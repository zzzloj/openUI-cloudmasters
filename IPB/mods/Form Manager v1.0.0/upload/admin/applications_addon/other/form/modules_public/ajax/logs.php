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

class public_form_ajax_logs extends ipsAjaxCommand 
{
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->getClass('formFunctions')->init();	
        
		if( !$this->memberData['g_fs_view_logs'] )
		{
			$this->returnJsonError( 'no_permission' );
		}        	
		
		switch( $this->request['do'] )
		{
			case 'preview_log':
				$this->previewLog();
			break;			
		}
	}
	
 	protected function previewLog()
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
			$this->returnJsonError( 'no_log_id_match' );		
		}        
		if( !$form['form_id'] )
		{
			$this->returnJsonError( 'no_form_id_match' );		
		}
		if( !$this->registry->permissions->check( 'view', $this->registry->getClass('formForms')->form_data_id[ $formID ] ) )
		{
			$this->returnJsonError( 'no_view_forms_perms' );
		}

        # Setup Parser
        IPSText::getTextClass('bbcode')->parse_html             = 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'form_forms';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
         
        $log['message'] = IPSText::getTextClass('bbcode')->preDisplayParse( $log['message'] );
        
        $log = IPSMember::buildDisplayData( $log );
        
        # Lets display everything now
        $this->returnHTML( $this->registry->output->getTemplate('form')->logPreview( $log, $form ) ); 
	}	
}