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

class class_fields
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

	/*-------------------------------------------------------------------------*/
	// Setup field cache
	/*-------------------------------------------------------------------------*/	
	public function init()
	{
		if( !IPSLib::appIsInstalled('form') )
		{
			return;
		}
       
		if ( !is_array( $this->caches['form_fields'] ) AND !count( $this->caches['form_fields'] ) )
		{
			$this->rebuild_fields();
		}    

		# Organize our array
		foreach( $this->caches['form_fields'] as $field_id => $field )
		{
            $this->fields[ $field['field_form_id'] ][ $field['field_id'] ] = $field;
            $this->fieldById[ $field_id ] = $field;				
		}
	}
    
	/*-------------------------------------------------------------------------*/
	// Rebuild all field cache
	/*-------------------------------------------------------------------------*/	
	public function rebuild_fields( $update=0 )
	{
        # Lets update for admin cases.
        if( IN_ACP )
        {
            $update = 1;         
        }

		$field_cache = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'form_fields', 'order' => 'field_position ASC' ) );
		$outer = $this->DB->execute();
		
		while( $field = $this->DB->fetch( $outer ) )
		{          
			$field['field_options'] = unserialize( $field['field_options'] );
            $field['field_extras']  = unserialize( $field['field_extras'] );
            
            # Check seo name, may need to update for first time users.
            if( !$field['field_name'] )
            {
                $field['field_name'] = IPSText::makeSeoTitle( $field['field_title'] );
                $this->DB->update( 'form_fields', array( 'field_name' => $field['field_name'] ), "field_id=".$field['field_id'] );
            }

            # Update field settings?            
            if( $update )
            {
                $field['field_data'] = $this->generateField( $field, 1 );
                $this->DB->update( 'form_fields', array( 'field_data' => $field['field_data'] ), "field_id=" . $field['field_id'] );    
            }
			
			$field_cache[ $field['field_id'] ] = $field;
		}

		$this->cache->setCache( 'form_fields', $field_cache, array( 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );		
	}    
    
    /*-------------------------------------------------------------------------*/
    // Make Custom Field Data
    /*-------------------------------------------------------------------------*/
    public function generateField( $data=array(), $bypassCheck=0 )
    {
        if( !is_array( $data['field_extras'] ) )
        {
            $data['field_extras']  = unserialize( $data['field_extras'] );
            $data['field_options'] = unserialize( $data['field_options'] );             
        } 
        
        $this->lang->words['field_value_no_match'] = str_replace( "<#EXTRA#>", $data['field_value'], $this->lang->words['field_value_no_match'] );
        
    	//--------------------------------
    	// Input
    	//-------------------------------- 
    	if($data['field_type'] == 'input')
    	{
    		if( !$bypassCheck && !$data['field_extras']['size'] )
    		{ 
     			return $this->registry->output->showError( $this->lang->words['field_size_required'] );	
     		}
    		 	
    		$html_data .= "<input type='input' class='input_text' size='{$data['field_extras']['size']}' name='custom_fields[{$data['field_id']}]' value='{$data['field_value']}' />";  
    	}
    	
    	//--------------------------------
    	// Password
    	//-------------------------------- 	
    	elseif($data['field_type'] == 'password')
    	{
    		if( !$bypassCheck && !$data['field_extras']['size'] )
    		{ 
     			return $this->registry->output->showError( $this->lang->words['field_size_required'] );	
     		}
    		 	  
    		$html_data .= "<input type='password' class='input_text' size='{$data['field_extras']['size']}' name='custom_fields[{$data['field_id']}]' value='{$data['field_value']}' />";  
    	}	
    	
    	//--------------------------------
    	// Text Area
    	//-------------------------------- 	
    	elseif($data['field_type'] == 'textarea')
    	{
    	  	if( !$bypassCheck && !$data['field_extras']['cols'] )
    		{ 
     			return $this->registry->output->showError( $this->lang->words['field_columns_required'] );	
     		}	  
    		if( !$bypassCheck && !$data['field_extras']['rows'] )
    		{ 
     			return $this->registry->output->showError( $this->lang->words['field_rows_required'] );	
     		}
    		 	  
    		$html_data .= "<textarea name='custom_fields[{$data['field_id']}]' class='input_text' cols='{$data['field_extras']['cols']}' rows='{$data['field_extras']['rows']}'>{$data['field_value']}</textarea>";  
    	}       
        
    	
    	//--------------------------------
    	// Dropdown
    	//-------------------------------- 		
    	elseif($data['field_type'] == 'dropdown')
    	{
    		if( !$bypassCheck && !is_array($data['field_options']) )
    		{
    			return $this->registry->output->showError( $this->lang->words['field_options_required'] );	
    		}
    
    		if( !$bypassCheck && $data['field_value'] && !in_array($data['field_value'], $data['field_options']) )
    		{    		    
    			return $this->registry->output->showError( $this->lang->words['field_value_no_match'] );	  
    		}	  
            
            if( is_array( $data['field_options'] ) )
            {
                $html_data .=  "<select name='custom_fields[{$data['field_id']}]'>";
 
         		foreach($data['field_options'] as $key => $value)
        		{		  
        			if( empty($value) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_empty_option'] );	
        			}		
        
        			$html_data .= "<option value='{$value}'";			
        			$html_data .= $value == $data['field_value'] ? " selected='selected'>{$value}</option>" : ">{$value}</option>";
        		}
                
        		$html_data .=  "</select>";               
            } 
    	}
    	
    	//--------------------------------
    	// Radio Buttons
    	//-------------------------------- 	
    	elseif($data['field_type'] == 'radiobutton')
    	{
    		if( !$bypassCheck && !is_array($data['field_options']) )
    		{
    			return $this->registry->output->showError( $this->lang->words['field_options_required'] );	
    		}
    		  
    		if( !$bypassCheck && $data['field_value'] && !in_array($data['field_value'], $data['field_options']) )
    		{
    			return $this->registry->output->showError( $this->lang->words['field_value_no_match'] );	  
    		}
            
            if( is_array( $data['field_options'] ) )
            {
        		foreach($data['field_options'] as $key => $value)
        		{		  
        			if( empty($value) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_empty_option'] );	
        			}
        						
        			$html_data .= "<input type='radio' id='{$value}' name='custom_fields[{$data['field_id']}]' value='{$value}' ";			
        			$html_data .= $value == $data['field_value'] ? " checked='checked' /> <label for='{$value}'>{$value}</label><br />" : " /> <label for='{$value}'>{$value}</label><br /><br />";
        		}                
            }
    	}
    	
    	//--------------------------------
    	// Check Boxes
    	//-------------------------------- 	
    	elseif($data['field_type'] == 'checkbox')
    	{
    		if( !$bypassCheck && !is_array($data['field_options']) )
    		{
    			return $this->registry->output->showError( $this->lang->words['field_options_required'] );	
    		}	  
    	  	  
    	  	$value_array = explode(",", $data['field_value'] ); 
    	  	
    	  	# Go through selected values ane see if they exist in field options.
            if( is_array( $value_array ) )
            {
        	  	foreach($value_array as $value)
        	  	{
        			if( !$bypassCheck && $value && !in_array($value, $data['field_options']) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_value_no_match'] );	  
        			}	  	  
        		}                
            }
            
            if( is_array( $data['field_options'] ) )
            {
        		foreach($data['field_options'] as $key => $value)
        		{	  
        			if( !$bypassCheck && empty($value) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_empty_option'] );	
        			}	
        						
        			$html_data .= "<input type='checkbox' id='{$value}' name='custom_fields[{$data['field_id']}][]' value='{$value}' ";			
        			$html_data .= in_array($value, $value_array) ? " checked='checked' /> <label for='{$value}'>{$value}</label><br />" : " /> <label for='{$value}'>{$value}</label><br /><br />";
        		}                
            }
        }
    	
    	//--------------------------------
    	// Multi Select
    	//-------------------------------- 		
    	elseif($data['field_type'] == 'multiselect')
    	{
    		if( !$bypassCheck && !$data['field_extras']['multi_size'] )
    		{ 
     			return $this->registry->output->showError( $this->lang->words['field_size_required'] );	
     		}	  
    	  
    		if( !$bypassCheck && !is_array($data['field_options']) )
    		{
    			return $this->registry->output->showError( $this->lang->words['field_options_required'] );	
    		}			  
    	  
    	 	$value_array = explode(",", $data['field_value'] ); 
    	 	
    	  	# Go through selected values ane see if they exist in field options.
            if( is_array( $value_array ) )
            {
        	  	foreach($value_array as $value)
        	  	{
        			if( !$bypassCheck && $value && !in_array($value, $data['field_options']) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_value_no_match'] );	  
        			}	  	  
        		}                
            }	
            
            if( is_array( $data['field_options'] ) )
            {
        	  	$html_data .=  "<select name='custom_fields[{$data['field_id']}][]' size='{$data['field_extras']['multi_size']}' multiple='multiple'>";

        		foreach($data['field_options'] as $key => $value)
        		{	  
        			if( !$bypassCheck && empty($value) )
        			{
        				return $this->registry->output->showError( $this->lang->words['field_empty_option'] );	
        			}
        
        			$html_data .= "<option value='{$value}'";			
        			$html_data .= in_array($value, $value_array) ? " selected='selected'>{$value}</option>" : ">{$value}</option>";
        		}       		
        		
        		$html_data .=  "</select>";                
            }	
    	}
    
    	return $html_data;
    }
}
?>