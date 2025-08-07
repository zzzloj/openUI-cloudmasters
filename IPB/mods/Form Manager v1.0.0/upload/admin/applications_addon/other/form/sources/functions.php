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

if( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit;
}

class formFunctions
{	
	public function __construct( $registry )
	{
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   = $this->registry->settings();
		$this->request    = $this->registry->request();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
    
	public function init()
	{		
		$this->registry->output->addNavigation( $this->lang->words['title'] , 'app=form', 'form', 'app=form' );	
        
        $formList = array();
		
        # Get a list of forms
        if( is_array( $this->registry->getClass('formForms')->forms_data[ 0 ] ) AND count( $this->registry->getClass('formForms')->forms_data[ 0 ] ) )
        {
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
        }		  
          
		$this->registry->output->addContent( $this->registry->output->getTemplate( 'form' )->topMenu( $formList ) );	
				
	}    
    
	/*-------------------------------------------------------------------------*/
	// Parse Quick Tags
	/*-------------------------------------------------------------------------*/
	public function parseQuickTags( $data=array(), $formID=0 )
	{ 
        # Setup our form and fields
	    $form   = $this->registry->getClass('formForms')->form_data_id[ $formID ];
	    $fields = $this->registry->getClass('formFields')->fields[ $formID ];
        
        if( !$form['form_id'] )
        {
            return $data;   
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
               
        # Setup Field List
        foreach( $fields as $field )
        {     
            $fieldName = $this->request['custom_fields'][ $field['field_id'] ];
            
		    if( is_array( $this->request['custom_fields'][ $field['field_id'] ] ) )
            {
                $fieldName = "\n* ".implode("\n* ", $this->request['custom_fields'][ $field['field_id'] ] );
            }            
            
            if( $field['field_type'] == 'editor' )
            {
                $fieldName = $this->editor->process( $_POST['custom_fields'][ $field['field_id'] ] );
                $fieldName = IPSText::getTextClass( 'bbcode' )->preDbParse( $fieldName );
            }
            
            $fieldName = ( $this->request['custom_fields'][ $field['field_id'] ] ) ? $fieldName : '--';   
            
            $customFieldList .= "[b]{$field['field_title']}:[/b] {$fieldName}<br />";         
        }	   
       
        $search = array( "/{member_name}/",                        
                         "/{member_id}/", 
                         "/{member_email}/", 
                         "/{member_ip}/",                         
                         "/{board_name}/",
                         "/{board_url}/",    
                         "/{form_id}/",                          
                         "/{form_name}/",
                         "/{form_url}/", 
                         "/{field_name_(\d+)}/e",
                         "/{field_value_(\d+)}/e",                                                   
                         "/{field_list}/", 
                        );

        $replace = array( $this->memberData['members_display_name'], 
                          $this->memberData['member_id'],
                          $this->memberData['email'],
                          $this->member->ip_address, 
                          $this->settings['board_name'],
                          $this->settings['board_url'],
                          $form['form_id'],
                          $form['form_name'], 
                          $this->registry->getClass('output')->buildSEOUrl( "app=form&amp;do=view_form&amp;id={$form['form_id']}", "public", "{$form['name_seo']}", "form_view_form" ),                         
                          '$fields["$1"]["field_title"]', 
                          '$this->request["custom_fields"]["$1"]', 
                          $customFieldList
                        );
        
        return preg_replace( $search, $replace, $data );
    }	

}
?>