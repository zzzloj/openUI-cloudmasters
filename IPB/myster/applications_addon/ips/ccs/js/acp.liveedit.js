/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.tablesorter.js - Sortable table column	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _liveedit = window.IPBACP;
_liveedit.prototype.liveedit = Class.create({
	
	source: 		null,
	target: 		null,
	targetDisplay:	null,
	modified: 		false,
	
	initialize: function( source, target, options )
	{
		if( !$( source ) || !$( target ) )
		{
			return;
		}
		
		this.source	= $( source );		
		this.target	= $( target );
		this.options = Object.extend({
			url:		'',
			template:	new Template( "<div id='#{id}' class='field_subfield_wrap'><span id='#{displayid}'><em>" + ipb.lang['changejsinit'] + "</em></span> &nbsp;&nbsp;<a href='#' class='action_link' id='#{changeid}'>" + ipb.lang['changejstitle'] + "</a></div>" ),
			wrapper:	'',
			isFurl:		false,
			ajaxCallback: null
		}, options || {});


		document.observe("dom:loaded", function(){
			this.checkTargetInitial();

			if( this.source.tagName == 'SELECT' )
			{
				this.source.observe('change', this.checkTarget.bindAsEventListener(this));
			}
			else
			{
				this.source.observe('keyup', this.checkTarget.bindAsEventListener(this));
			}
		}.bind(this));
	},
	
	checkTargetInitial: function()
	{
		if( $F(this.target) )
		{
			this.modified	= true;
		}
		
		//if( !this.modified )
		//{
			this.setUpTarget();
		//}
	},
	
	checkTarget: function( e )
	{
		if( !this.modified )
		{
			var _value	= this.clean( $F( this.source ) );

			if( !this.options.isFurl )
			{
				this.continueTargetCheck( _value );
			}
		}
	},
	
	continueTargetCheck: function( _value )
	{
		if( this.options.url != '' && _value && !this.modified )
		{
			if( this.options.ajaxCallback )
			{
				this.options.url	= this.options.ajaxCallback( this.options.url );
			}

			new Ajax.Request( this.options.url.replace(/&amp;/g, "&"),
								{
									method: 'post',
									parameters: {
										secure_key: ipb.vars['md5_hash'],
										value: _value
									},
									onSuccess: function(t)
									{
										if( Object.isUndefined( t.responseJSON ) )
										{
											this.updateField( _value );
											return;
										}
												
										if( t.responseJSON['error'] )
										{
											alert( t.responseJSON['error'] );
											this.updateField( _value );
											return;
										}
										
										if( t.responseJSON['value'] )
										{
											this.updateField( t.responseJSON['value'] );
											return;
										}
										
										if( !this.modified )
										{
											this.updateField( _value );
										}
									}.bind(this)
								}
							);	
		}
		else
		{
			this.updateField( _value );
		}
	},
	
	updateField: function( value )
	{
		if( this.options.isFurl && value )
		{
			value = value + '-r#';
		}
		
		$( this.targetDisplay ).update( value );
		
		if( !this.options.isFurl )
		{
			$( this.target ).value	= value;
		}
	},
	
	clean: function( value )
	{
		if( this.options.isFurl && value )
		{
			var req			= ipb.vars['app_url'] + "module=ajax&section=databases&do=checkFurl&value=" + value;

			new Ajax.Request( req.replace(/&amp;/g, "&"),
								{
									method: 'post',
									parameters: {
										secure_key: ipb.vars['md5_hash']
									},
									onSuccess: function(t)
									{
										if( t.responseJSON['value'] )
										{
											this.continueTargetCheck( t.responseJSON['value'] );
										}
									}.bind(this)
								}
							);
		}
		else
		{
			return value.replace( /[^0-9a-zA-Z]+/gi, this.options.isFurl ? '-' : '_' ).toLowerCase();
		}
	},
	
	setUpTarget: function()
	{
		var _id			= this.options.wrapper ? this.options.wrapper : $( this.target ).id;
		var _template	= this.options.template.evaluate( { 'id': _id + '_wrap', 'displayid': _id + '_display', 'changeid': _id + '_change' } );

		if( this.options.wrapper )
		{
			$( this.options.wrapper ).insert( { before: _template } );

			if( !this.modified )
			{
				$( this.options.wrapper ).hide();	
			}
			else
			{
				$( _id + '_wrap' ).hide();
			}
		}
		else
		{
			this.target.insert( { before: _template } );

			if( !this.modified )
			{
				this.target.hide();	
			}
			else
			{
				$( _id + '_wrap' ).hide();
			}
		}
		
		this.targetDisplay	= _id + '_display';
		
		$( _id + '_change' ).observe( 'click', function(e){
			$( _id + '_wrap' ).hide();
			$( _id ).show();
			
			this.modified	= true;
			
			Event.stop(e);
			return false;
		}.bindAsEventListener(this));
		
		if( this.options.wrapper && $('cancel_' + this.options.wrapper ) )
		{
			$('cancel_' + this.options.wrapper ).observe( 'click', function(e){
				$( this.target ).value	= '';
				$( _id + '_wrap' ).show();
				$( _id ).hide();
				
				this.modified	= false;
				
				this.checkTarget( e );
				
				Event.stop(e);
				return false;
			}.bindAsEventListener(this));
		}
		
		this.checkTarget();
	}
});
