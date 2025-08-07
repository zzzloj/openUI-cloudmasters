/*-------------------------------------------------------------------------*/
// Setup and Go
/*-------------------------------------------------------------------------*/
var mainobj;
var used_fields = 0;

/*-------------------------------------------------------------------------*/
// Init Fields
/*-------------------------------------------------------------------------*/
function init_fields()
{	
	mainobj   = document.getElementById('fields');	
	create_box();
}

/*-------------------------------------------------------------------------*/
// Create Main Box
/*-------------------------------------------------------------------------*/
function create_box()
{
	var html       = '';
	used_fields = 0;
	
	// List all current fields
	for( var i in fields )
	{
		var qhtml = '';
		used_fields++;
				
		// Get html data ready	
		var options  = fields[i];	 
		var inputhtml = lang_build_string( html_field_data, i, _make_safe( options ) ); 
		
		qhtml += "\n" + inputhtml + "\n";
		
		// Finish wrap and we are done		
		html += lang_build_string(html_field_wrap, qhtml );
	}
	
	mainobj.innerHTML = html;
}

/*-------------------------------------------------------------------------*/
// Write form with array - ipb2.3 legacy support, flagged for future version
/*-------------------------------------------------------------------------*/
function lang_build_string()
{
	if ( ! arguments.length || ! arguments )
	{
		return;
	}
	
	var string = arguments[0];
	
	for( var i = 1 ; i < arguments.length ; i++ )
	{
		var match  = new RegExp('<%' + i + '>', 'gi');
		string = string.replace( match, arguments[i] );
	}
	
	return string;
}

/*-------------------------------------------------------------------------*/
// Write form with array
/*-------------------------------------------------------------------------*/
function write_array()
{
	// Update field data from form
	var tmp_fields = {};
	
	for ( var i in fields )
	{
		// Get the value		
		try
		{
			tmp_fields[ i ] = document.getElementById( 'options_' + i ).value;
		}
		catch(e) { }
	}
	
	// Update Array	
	fields = tmp_fields;
}

/*-------------------------------------------------------------------------*/
// Add Field Data
/*-------------------------------------------------------------------------*/
function add_field()
{
	var maxid = 0;
	
	for ( var i in fields )
	{
		if ( i > maxid )
		{
			maxid = parseInt(i);
		}
	}
	
	maxid = maxid + 1;	
	write_array();	
	fields[ maxid ] = '';	
	create_box();	
	return false;
}

/*-------------------------------------------------------------------------*/
// Remove Field Data
/*-------------------------------------------------------------------------*/
function remove_field( mainid )
{
	if ( confirm( js_lang_confirm ) )
	{
		delete( fields[ mainid ] );
		
		write_array();
		create_box();
	}
	
	return false;
}

/*-------------------------------------------------------------------------*/
// Make it safe
/*-------------------------------------------------------------------------*/
function _make_safe( t )
{
	t = t.replace( /'/g, '&#039;' );
	t = t.replace( /"/g, '&quot;' );
	
	return t;
}