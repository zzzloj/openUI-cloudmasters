/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.tablesorter.js - Sortable table column	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _tablesorter = window.IPBACP;
_tablesorter.prototype.tablesorter = Class.create({
	
	table: null,
	url: '',
	options: null,
	currentColumn: null,
	
	initialize: function( table, options, callbacks )
	{
		if( !$( table ) ){
			return;
		}
		
		this.table = $( table );		
		this.options = Object.extend({
			url: '',
			startOrder: 'desc',
			startColumn: 'id'
		}, options || {});
		
		if( this.options.url == '' ){
			return;
		}
		
		this.callbacks = callbacks || {};
		
		document.observe("dom:loaded", function(){
			this.setOrder( this.options.startOrder );
			this.currentColumn = this.options.startColumn;
			this.currentOrder = this.options.startOrder;
			
			// Set up events on TH
			$( this.table ).select('th.sort').each( function(elem){
				$( elem ).observe('click', this.doSort.bindAsEventListener(this));
			}.bind(this));
		}.bind(this));
	},
	
	doSort: function( e )
	{
		Event.stop(e);
		
		var elem = Event.findElement( e, '.sort' );
		var sortColumn = $( elem ).id.replace('sort_', '');
		var req = '';

		// Is this already the sorting column?
		if( sortColumn == this.currentColumn ){
			// Change the order
			req = "&order=" + (( this.currentOrder == 'desc' ) ? 'asc' : 'desc') + "&sortBy=" + this.currentColumn;
		} else {
			// Change the column
			req = "&order=" + this.currentOrder + "&sortBy=" + sortColumn;
		}
		
		req = this.options.url + req;
		
		// Grab results
		new Ajax.Request(	req.replace(/&amp;/g, "&"),
							{
								method: 'post',
								evalJSON: 'force',
								parameters: {
									secure_key: ipb.vars['md5_hash']
								},
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( ipb.lang['js__nofilterperm'] );
										return;
									}
											
									if( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
										return;
									}
									
									// Clear out the body
									$( this.table ).down('tbody').update( t.responseJSON['html'] );
									
									// Page links
									if( $('pages_bottom') )
									{
										$('pages_bottom').update( t.responseJSON['pages'] );
									}

									if( $('pages_top') )
									{
										$('pages_top').update( t.responseJSON['pages'] );
									}

									// Callback?
									if( Object.isFunction( this.callbacks['afterUpdate'] ) ){
										this.callbacks['afterUpdate']( this );
									}
									
									// Now update the column highlight and up/down sort arrows
									if( sortColumn == this.currentColumn ){
										var newOrder = ( this.currentOrder == 'desc' ) ? 'asc' : 'desc';
										this.setOrder( newOrder );
										this.currentOrder = newOrder;
										$('sort_' + this.currentColumn).removeClassName('asc').removeClassName('desc').addClassName( newOrder );
									} else {
										if( $( 'sort_' + this.currentColumn ) ){
											$( 'sort_' + this.currentColumn ).removeClassName('active');
										}
										$( 'sort_' + sortColumn ).addClassName('active').removeClassName('asc').removeClassName('desc').addClassName( this.currentOrder );
										this.currentColumn = sortColumn;
									}
								}.bind(this)
							}
						);									
		
		Debug.write( req );
	},
	
	setOrder: function( order )
	{
		$( this.table ).removeClassName('asc').removeClassName('desc').addClassName( order );		
	}	
});