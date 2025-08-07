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

class admin_form_ajax_fields extends ipsAjaxCommand 
{
	public function doExecute( ipsRegistry $registry )
	{       
	    $this->lang->loadLanguageFile( array( 'admin_form' ), 'form' );
       
    	switch( $this->request['do'] )
    	{   
 			case 'view_quicktags':
				$this->view_quicktags();
			break;     	   
           
			default:
			case 'view_example':
				$this->viewExample();
			break;
    	}
	}
	
	/*-------------------------------------------------------------------------*/
	// View Field Example
	/*-------------------------------------------------------------------------*/
	protected function viewExample()
	{
		$html = $this->registry->output->loadTemplate('cp_skin_forms');
		
		$id = intval( $this->request['id'] );
        
		$field = $this->DB->buildAndFetch( array( 'select' => 'field_id, field_title, field_data', 'from' => 'form_fields', 'where' => 'field_id='.$id ) );														
	
		if ( !$field['field_id'] )
		{
			$this->returnJsonError( $this->lang->words['no_field_id_match'] );
			exit();
		}	
			
		$this->returnHTML( $html->viewExample( $field ) );		
	}
    
 	/*-------------------------------------------------------------------------*/
	// View Quick Tags
	/*-------------------------------------------------------------------------*/
	protected function view_quicktags()
	{
		$html = $this->registry->output->loadTemplate('cp_skin_forms');	
				
		$this->returnHTML( $html->quicktags() );		
	}   
}