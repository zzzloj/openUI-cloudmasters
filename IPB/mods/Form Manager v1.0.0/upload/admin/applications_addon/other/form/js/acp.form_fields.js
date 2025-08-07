ACPFormFields = {
	
	fields: $H(),
    
	init: function()
	{
		Debug.write("Initializing acp.form_fields.js");
		
		document.observe("dom:loaded", function(){
			ACPFormFields.initEvents();
            ACPFormFields.initFields();
		});
	},
	
	initEvents: function()
	{
		$('field_type').observe('change', ACPFormFields.changeFieldType);
		
		if( $F('field_type') )
		{
			ACPFormFields.changeFieldType();	
		}  		
	},   
        
	initFields: function()
	{
	    if( ACPFormFields.fields.size() > 0 )
		{
		    ACPFormFields.fields.each( function( f )
			{
				var field_id    = f.key;
				var field_value = f.value;
                
                var html = ipb.templates['field_box'].evaluate( { field_id: field_id, field_value: field_value } );
                
                $( 'fields_container' ).insert( html );
                
				if( $('remove_field_' + field_id) )
				{
					$('remove_field_' + field_id).observe('click', ACPFormFields.removeField.bindAsEventListener( this, field_id ) );
				}
				
				$('field_' + field_id + '_wrap').show();           

            });
        } 
        
        if( $('add_field') )
        {
			$('add_field').observe('click', ACPFormFields.addField );
		}		
	}, 
    
	addField: function(e)
	{
		Event.stop(e);

		var new_id = ACPFormFields.getNextField();

        // Add field and remove option
		var item = ipb.templates['field_box'].evaluate( { field_id: new_id, value: '' } );
		$( 'fields_container' ).insert( item );
		
		if( $('remove_field_' + new_id) )
		{
			$('remove_field_' + new_id).observe('click', ACPFormFields.removeField.bindAsEventListener( this, new_id ) );
		}
		
		// Show box
		new Effect.BlindDown( $('field_' + new_id + '_wrap'), { duration: 0.3 } );
        
		// Add to fields array
		ACPFormFields.fields.set( new_id, $H() );
	},    
    
	removeField: function(e, field_id)
	{
		Event.stop(e);
		
		if( !$('field_' + field_id + '_wrap') ){ return; }
		
		// Confirm box
	 	if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			return;
		}
		
		// Remove Field
		new Effect.BlindUp( $('field_' + field_id + '_wrap'), { duration: 0.3, afterFinish: function(){
			$('field_' + field_id + '_wrap').remove();
		} } );
		
		ACPFormFields.fields.unset( field_id );
	},     
      
	changeFieldType: function(e)
	{
	    field_type = $F('field_type');
       
        $('field_extras').hide();
        $('field_extras_container').hide();
        $('selected_value').hide();
        $('fields_container').hide();       
        $('add_field_options').hide();

    	// Is this a multiple value field type?	  
    	if( field_type == 'dropdown' || field_type == 'radiobutton' || field_type == 'checkbox' || field_type == 'multiselect' )
    	{
    	    $('fields_container').show();
    	    $('add_field_options').show();
    	}
        
    	// Is this a multiple choice field type?
    	if( field_type == 'checkbox' || field_type == 'multiselect' )
    	{
    	    $('selected_value').show();
    	}        

    	// Input Field
    	if( field_type == 'input' )
    	{    	   
    	    $('field_extras').show();        
	 	    $('field_extras_container').show().update( ipb.templates['input_box'] ); 
     	}
    	// Password Field
    	if( field_type == 'password' )
    	{
    	    $('field_extras').show();
	 	    $('field_extras_container').show().update( ipb.templates['input_box'] );                  	   
    	}		
    	// MultiSelect Dropdown
    	if( field_type == 'multiselect' )
    	{
    	    $('field_extras').show();
	 	    $('field_extras_container').show().update( ipb.templates['multi_box'] );             
    	}
    	// Textarea
    	if( field_type == 'textarea' )
    	{
    	    $('field_extras').show();
	 	    $('field_extras_container').show().update( ipb.templates['dropdown_box'] );              
     	}
    },
    
	viewExample: function( e, url )
	{
		Event.stop(e);		
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		ACPFormFields.popups['example'] = new ipb.Popup('field_example', { type: 'pane', modal: false, hideAtStart: false, w: '650px', h: '650px', ajaxURL: url } );		
	},    
    
   	getNextField: function()
	{  
		var next_id = parseInt( ACPFormFields.fields.max( function(f){
				return parseInt( f.key );
			}) ) + 1;
            
		if ( isNaN( next_id ) )
		{
			var next_id = 1;
		}
        
		return next_id;
	}    
};

ACPFormFields.init();