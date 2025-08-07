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

class public_form_display_extras extends ipsCommand
{
	protected $output				= "";	
	
	public function doExecute( ipsRegistry $registry )
	{	
		$this->registry->getClass('formFunctions')->init();			
		
	  	if( !$this->memberData['member_id'] )
	  	{
			$this->registry->output->showError( 'no_permission' );    
		}
		
		switch( $this->request['do'] )
		{
	    	case 'markread':
	      	 $this->markRead();
	      	break;      			
		}
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();		
	}
    
	/*-------------------------------------------------------------------------*/
	// Mark form as read
	/*-------------------------------------------------------------------------*/	
	public function markRead()
	{ 
		# Load em
        $formID = intval( $this->request['id'] );
        $form   = $this->registry->getClass('formForms')->form_data_id[ $formID ];        
        
        # Check exist and can view
		if( !$form['form_id'] )
		{
			$this->registry->output->showError( 'no_form_id_match' );		
		}        	
		if( !$this->registry->permissions->check( 'view', $this->registry->getClass('formForms')->form_data_id[ $formID ] ) )
		{
			$this->registry->output->showError( 'no_view_forms_perms' );
		}
        
        # Mark sub categories
        $subforms = $this->registry->getClass('formForms')->getChildForms( $formID );
        
		if ( is_array( $subforms ) && count( $subforms ) )
		{
			foreach ( $subforms as $id )
			{
				$this->registry->classItemMarking->markRead( array( 'forumID' => $id ), 'form' );
			}
		} 
        
        # Mark original form and redirect out of here        
        $this->registry->classItemMarking->markRead( array( 'forumID' => $formID ), 'form' );  
        
        if ( $this->memberData['member_id'] )
        {
        	$this->registry->classItemMarking->writeMyMarkersToDB();
        }     
        
        $this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=form', 'app=form', 302, 'app=form' );       
    }
	
}