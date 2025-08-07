/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* contact.js - Contact System 					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/* Modified By: Michael (DevFuse)				*/
/************************************************/

var _form = window.IPBoard;

_form.prototype.form = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{			
		Debug.write("Initializing form.js");
		
		document.observe("dom:loaded", function(){
		  
          ipb.delegate.register(".show_log", ipb.form.showLog); 
		});
	},
    
	showLog: function(e,elem)
	{	   
	    log_id = elem.id.match('showlog_([0-9a-z]+)')[1];
        
		if( Object.isUndefined(log_id) ){ return; }
		
		if( parseInt(log_id) == 0 ){
			return false;
		} 	
		
		Event.stop(e);
        
        if( $('log_preview_' + log_id ).visible() )
        {
            $( 'showlog_' + log_id ).addClassName('closed').removeClassName('open');
            
           $('log_preview_' + log_id ).fade({ afterFinish: function(){
               $('log_preview_' + log_id).hide();        
             }
           });            
        }
        else
        {
            $( 'showlog_' + log_id ).addClassName('open').removeClassName('closed');
            
    		var url = ipb.vars['base_url'] + "app=form&module=ajax&section=logs&do=preview_log&id=" + log_id + "&secure_key=" + ipb.vars['secure_hash'];

    		new Ajax.Request( url.replace(/&amp;/, '&'),
    		 				{
    							method: 'get',
    							onSuccess: function(t)
    							{    							
    								if( t.responseText != 'error' )
    								{                                    
                                           $('log_preview_' + log_id ).fade({ afterFinish: function(){
                                               $('log_preview_' + log_id).update( t.responseText );
                                               $('log_preview_' + log_id).appear();        
                                             }
                                           });
    								}
    								
    							}
    						});                       
        }       
        
	},

}
function check_boxes()
{
	var ticked = $('maincheckbox').checked;
	
	var checkboxes = document.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( typeof(e) != 'undefined' && e.type == 'checkbox')
		{
			var boxname		= e.id;
			var boxcheck	= boxname.replace( /^(.+?)_.+?$/, "$1" );
			
			if ( boxcheck == 'lid' )
			{
				e.checked = ticked;
			}			
		}
	}
}

ipb.form.init();