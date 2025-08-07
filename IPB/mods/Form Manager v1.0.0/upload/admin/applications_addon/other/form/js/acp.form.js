ACPForm = {
	
	init: function()
	{
		Debug.write("Initializing acp.form.js");
		
		document.observe("dom:loaded", function(){
            ipb.delegate.register(".viewquicktags", ACPForm.viewQuickTags.bindAsEventListener( this, "app=form&amp;module=ajax&amp;section=fields&amp;do=view_quicktags" ));
		    ACPForm.autoComplete = new ipb.Autocomplete( $('members_display_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
            ACPForm.initEvents();
        });
	},
    
	initEvents: function()
	{
        if( $('send_method_pm') )
        {
            $('send_method_pm').observe( 'change', ACPForm.changePMSendMethod );
        }
		
		if( $F('send_method_pm') )
		{
			ACPForm.changePMSendMethod();
		}
        
        if( $('send_method_email') )
        {
            $('send_method_email').observe( 'change', ACPForm.changeEmailSendMethod );
        }
		
		if( $F('send_method_email') )
		{
			ACPForm.changeEmailSendMethod();
		}          
	}, 
    
	changePMSendMethod: function()
	{       
	    send_method_pm = $F('send_method_pm');
        
    	$('send_method_pm_1').hide();  
        $('send_method_pm_2').hide();        

     	if( send_method_pm == '1' )
    	{
    	    $('send_method_pm_1').show();  
        }
        else
        {
            $('send_method_pm_2').show();     
        }    
	},
    
	changeEmailSendMethod: function()
	{       
	    send_method_email = $F('send_method_email');
        
    	$('send_method_email_1').hide();  
        $('send_method_email_2').hide();        

     	if( send_method_email == '1' )
    	{
    	    $('send_method_email_1').show();  
        }
        else
        {
            $('send_method_email_2').show();     
        }    
	}, 
    
	findName: function()
	{
		if ( $('members_display_name').value == "" )
		{
			alert("You must enter a display name!");
			return false;
		}
		
		return true;
	},       
    
	viewExample: function( e, url )
	{
		Event.stop(e);		
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		ACPForm.popups['example'] = new ipb.Popup('field_example', { type: 'pane', modal: false, hideAtStart: false, w: '650px', h: '650px', ajaxURL: url } );		
	},
    
	viewQuickTags: function( e, url )
	{
		Event.stop(e);		
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		ACPForm.popups['quick_tags'] = new ipb.Popup('quicktags', { type: 'pane', modal: false, hideAtStart: false, w: '600px', h: '600px', ajaxURL: url } );		
	}    
};

ACPForm.init();