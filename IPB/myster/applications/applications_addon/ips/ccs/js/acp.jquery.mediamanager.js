// ************************************ //
// Media manager						//
// ************************************ //
var mediamanager = {};

(function($){

	mediamanager = function($){

		//-------------------------------------
		// PROPERTIES
		var browser = null,			// Reference to browser pane
			tree = null,			// Reference to folder tree
			buttons = null,			// Reference to button list
			focusFile = null,		// File object with current focus
			selectedFiles = [],		// Array of selected files
			currentDir = null,		// The currently-viewed folder
			cache = {},				// Cache object for viewing folers
			searchCache = {},		// Cache object for search results
			templates = {},			// Templates passed to media manager
			searchTimeout = null,	// setTimeout reference for search event
			lastVal = '',			// Last search value recorded
			isSearching = false,	// Is user searching?
			workingDir = '';		// Current working directory

		//-------------------------------------
		// PUBLIC METHODS	
		var init = function( initialDir, _templates, attachSettings ){

			browser = $('#media_browser');
			tree = $('#media_sidebar > ul');
			buttons = $('#media_actions');
			templates = _templates;

			// Init SWFUpload
			attachments.init( attachSettings );

			// General events
			$(".file", browser).live('click', clickFile );					// Row action
			$("#media_browser").click( {}, clickOff );						// Cancel selection
			$("#media_search").focus( focusSearch ).blur( blurSearch );		// Search box
			$(".popup_cancel").live('click', function(e){
				var pop = $( this ).parents('#media_popups > div');
				if( pop ){
					hidePopup( $(pop) );
				}
			});

			// Actions
			$('li[data-role="move"]:not(.disabled)', buttons).live( 'click', actionMove );
			$('li[data-role="delete"]:not(.disabled)', buttons).live( 'click', actionDelete );
			$('li[data-role="new"]:not(.disabled)', buttons).live( 'click', actionNewFolder );
			$('li[data-role="upload"]:not(.disabled)', buttons).live( 'click', actionUpload );
			$('#do_move_files').live( 'click', actionMoveDo );
			$('#do_new_folder').live( 'click', actionNewFolderDo );
			$('#do_rename_folder').live( 'click', actionRenameFolderDo );
			$('#do_upload:not( [disabled] )').live( 'click', attachments.primedForUpload );
			$('.folder_delete', tree).live( 'click', actionFolderDelete );

			// Set up loading throbber
			$('#media_loading').ajaxStart( function(){
				$('#media_no_results').hide();
				$(this).show();
			}).ajaxStop( function(){
				$(this).hide();
			});

			// Get our initial directory
			if( initialDir ){
				fetchFolderContents( { dir: initialDir, force: true } );
			}

			// Set up folders
			$( tree ).show();
			$('a', tree).click( clickTree );

			// Select and open the initial dir (working backwards from selected dir)
			$('li[data-path="' + initialDir + '"]')
				.find('span')
				.first()
				.trigger('click')
				.end().end()
				.parents('li')
				.add('#media_sidebar > ul > li')
				.find('> a:first-child')
				.trigger('click');
		},

		//-----------------------------------------
		// Shows the specified popup
		actionFolderDelete = function(e)
		{
			if( e ){ e.preventDefault(); }

			var item = $(this).parent('li');
			var path = $(item).data('path');
			var name = $(item).data('name');

			if( !path ){
				return;
			}

			if( !confirm( ipb.lang['confirm_delete_folder'].replace(/\[name\]/g, name) ) ){
				return;
			}

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=folderdelete&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							dir: path
						}
					}).done( function(json){
						
						//Debug.write( json );

						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						if( json['success'] ){
							$(item).slideUp('fast', function(){
								// remove item
								$(item).remove();
								// empty cache
								cache = {};
								// if selected dir has gone, reselect root
								if( !$('li[data-path="' + currentDir + '"').length ){
									$('span', tree).first().trigger('click');
								}
							});
						}
					});
		},

		//-----------------------------------------
		// Shows the specified popup
		showPopup = function( popup, callback )
		{
			var _cb = null;
			
			// Check no popups are open
			if( existing = $('#media_popups > div:visible') )
			{
				$.each( existing, function(i, item){
					hidePopup( item );
				});
			}

			// Our working dir is the one that will be affected
			// by whatever this popup does. It's so that the user doesn't
			// click another folder while the popup is open, and end up
			// deleting the wrong one.
			workingDir = currentDir;

			// Callback?
			if( $.isFunction( callback.before ) ){
				callback.before();
			}
			
			if( $.isFunction( callback.after ) )
			{
				_cb = function() { setTimeout( callback.after, 500 ) };
			}

			var height = $( popup ).height();
			$( popup ).css('top', '-' + height + 'px').show().animate({'top': 0}, 100, _cb );
		},

		//-----------------------------------------
		// Hides the specified popup
		hidePopup = function( popup, callback )
		{
			workingDir = '';

			var height = $( popup ).height();

			$( popup ).animate({'top': '-' + height + 'px'}, 100, 'linear', function(){
				$( this ).hide();

				if( $.isFunction( callback ) ){
					setTimeout( callback, 500 );
				}
			});
		},

		//-----------------------------------------
		// Move some files
		actionMoveDo = function(e)
		{
			if( e ){ e.preventDefault(); }

			var paths = gatherSelectedPaths();
			var dir = $('#move_files').val();

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=move&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							files: paths,
							dir: dir
						}
					}).done( function(json){
						
						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						if( json['success'] ){
							// empty the cache
							cache = {};

							if( isSearching ){
								runSearch();
							} else {
								fetchFolderContents( { dir: currentDir, force: true }, e );
							}

							unselectAll();
							checkActionButtons();
						}

						if( json['failed'] ){
							alert( ipb.lang['some_failed_move'] );
						}

						hidePopup( $('#media_popup_move') );

					});
		},

		//-----------------------------------------
		// Show the move dialog
		actionNewFolderDo = function(e)
		{
			if( e ){ e.preventDefault(); }
			
			if( !$('#folder_name').val() ){
				return;
			}

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=new&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							folder_name: $('#folder_name').val(),
							dir: workingDir
						}
					}).done( function(json){
						
						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						window.location.reload();
					});

		},

		//-----------------------------------------
		// Show the move dialog
		actionUpload = function(e)
		{
			if( e ){ e.preventDefault(); }

			showPopup( $('#media_popup_upload'), {
				after: attachments.reInit
			});
		},

		//-----------------------------------------
		// Show the move dialog
		actionNewFolder = function(e)
		{
			if( e ){ e.preventDefault(); }
			$('#folder_name').val('').focus();

			showPopup( $('#media_popup_newfolder'), {} );
		},

		//-----------------------------------------
		// Show the rename dialog
		actionRenameFolder = function(e)
		{
			if( e ){ e.preventDefault(); }
			$('#rename_folder_name').val( e.target.innerHTML );

			showPopup( $('#media_popup_renamefolder'), {} );
		},

		actionRenameFolderDo = function(e)
		{
			var newname = $('#rename_folder_name').val();
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=renamefolder&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							name: newname,
							dir: workingDir
						}
					}).done( function(json){
						
						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						if( json['success'] ){
							//$(e.target).html( newname );
							$('li[data-path="' + workingDir + '"]', tree)
								.attr('data-path', json['success'])
								.attr('data-name', newname)
								.find('span')
								.first()
								.html( newname );

							if( cache[ workingDir ] ){
								delete( cache[ workingDir ] );
							}

							hidePopup( $('#media_popup_renamefolder') );
						}
					});

		},

		//-----------------------------------------
		// Show the move dialog
		actionMove = function(e)
		{
			if( e ){ e.preventDefault(); }
			showPopup( $('#media_popup_move'), {} );
		},
		//-----------------------------------------
		// Focus into search box
		actionDelete = function(e)
		{
			if( e ){ e.preventDefault(); }

			if( !confirm( ipb.lang['confirm_file_delete'] ) )
			{
				return;
			}

			var paths = gatherSelectedPaths();
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=delete&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							files: paths
						}
					}).done( function(json){
						
						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						if( json['success'] ){
							fetchFolderContents( { dir: currentDir, force: true }, e );
							unselectAll();
							checkActionButtons();
						}

						if( json['failed'] ){
							alert( ipb.lang['some_failed_delete'] );
						}
					});
		},
		//-----------------------------------------
		// Get all file paths from selected files
		gatherSelectedPaths = function()
		{
			var paths = [];

			$.each( selectedFiles, function(i, elem){
				paths.push( $(elem).data('path') );
			});

			return paths;
		},
		//-----------------------------------------
		// Click a folder in the sidebar
		clickTree = function(e)
		{
			if( e ){ e.preventDefault(); }

			var list = $(this).parent('li');
			var path = $(list).data('path');

			if( e.target.tagName == 'SPAN' )
			{
				if( isSearching ){
					cancelSearch();
				}

				if( $(list).hasClass('selected') && e.shiftKey && !$(list).is('#media_sidebar > ul > li') ){
					actionRenameFolder(e);
				}

				fetchFolderContents( { dir: path } );

				$('li', tree).removeClass('selected');
				$(list).addClass('selected');

				unselectAll();
				checkActionButtons();
			}
			else if( !$(e.target).hasClass('folder_delete') )
			{
				if( $(list).hasClass('open') ){
					$(list).children('ul').slideUp('fast').end().filter('.expandable').removeClass('open').addClass('closed');
				} else {
					$(list).children('ul').slideDown('fast').end().filter('.expandable').addClass('closed').addClass('open');
				}
			}			
		},

		//-----------------------------------------
		// Focus into search box
		focusSearch = function(e)
		{
			clearTimeout( searchTimeout );
			searchTimeout = setTimeout( runSearch, 300 );
		},
		//-----------------------------------------
		// Blur search box
		blurSearch = function(e)
		{
			clearTimeout( searchTimeout );
		},
		//-----------------------------------------
		// Run the search if the value has changed
		runSearch = function(e)
		{
			isSearching = true;

			searchTimeout = setTimeout( runSearch, 300 );
			var curVal = $("#media_search").val();

			if( curVal == lastVal ){
				return;
			}

			// Make sure all folders are unselected
			$('li', tree).removeClass('selected').css('opacity','0.3');

			if( curVal == '' ){
				lastVal = '';
				cancelSearch();
				return;
			}			

			fetchResults( curVal );
			lastVal = curVal;
		},
		//-----------------------------------------
		// Actually get the results
		fetchResults = function( term )
		{
			if( searchCache[ term ] ){
				buildFolderContents( searchCache[ term ] );
				return;
			}

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=search&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({
						type: 'post',
						url: url,
						data: {
							term: term
						}
					}).done( function(json){

						if( json['error'] ){
							alert( json['error'] );
							return;
						}

						searchCache[ term ] = json;
						buildFolderContents( json );
					});
		},

		//-----------------------------------------
		// Cancel searching
		cancelSearch = function()
		{
			Debug.write(currentDir);
			// Reselect current dir
			$('li[data-path="' + currentDir + '"]', tree).addClass('selected');
			// Make sure all folders are unselected
			$('li', tree).css('opacity','1');
			// Set value to empty
			$('#media_search').val('');

			isSearching = false;

			buildFolderContents( cache[ currentDir ] );
		},

		//-----------------------------------------
		// Grabs the contents of a folder (from cache or ajax)
		fetchFolderContents = function(data, e)
		{
			if( e ){ e.preventDefault(); }

			if( currentDir == data.dir && !data.force ){
				return;
			}

			if( cache[ data.dir ] && !data.force ){
				currentDir = data.dir;
				buildFolderContents( cache[ data.dir ] );
				return;
			}

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=listdirfiles&secure_key=" + ipb.vars['md5_hash'];

			$.ajax({ 
						type: 'POST',
						url: url,
						data: {
							dir: data.dir
						}
					})
					.done( function(json){
						if( json['error'] )
						{
							alert( json['error'] );
							return;
						}

						currentDir = data.dir;
						cache[ data.dir ] = json;
						buildFolderContents( json );
					})
					.fail( function(){
						alert( ipb.lang['mm_error_fetchingfolder'] );
					});
		},

		//-----------------------------------------
		// Builds the file blocks based on json
		buildFolderContents = function( json )
		{
			var html = '';

			if( json['no_results'] ){
				var lang = ( isSearching ) ? ipb.lang['no_search_files'] : ipb.lang['no_files'];

				$(browser).html('');
				$('#media_no_results').html( lang ).fadeIn('fast');
				return;
			}

			$('#media_no_results').hide();

			$.each( json, function(item, bits){
				if( bits['type'] == 'image' ){
					bits['img'] = bits['url'];
				} else {
					bits['img'] = bits['icon'];
				}

				html += templates.file( bits );
			});

			if( html == '' )
			{
				$(browser).html('');
				$('#media_no_results').html( ipb.lang['no_files'] ).fadeIn('fast');
				return;
			}

			$( browser ).html( html );
			$('.file', browser).fadeIn('fast').attr('unselectable', 'unselectable');
		},

		//-----------------------------------------
		// Gets file info from the cache
		getFileInformation = function( file )
		{
			// Get file id
			var fileid = $(file).data('fileid');

			try {
				if( isSearching ){
					var fileinfo = searchCache[ lastVal ][ fileid ];
				} else {
					var fileinfo = cache[ currentDir ][ fileid ];
				}
			}
			catch(err){ Debug.write( err ); return; }

			if( !fileinfo ){
				return '';
			}

			fileinfo['_last_modified'] = doDate( fileinfo['last_modified'] ); 
			fileinfo['_size'] = doSize( fileinfo['size'] );

			return templates.info( fileinfo );

		},
		//-----------------------------------------
		// Formats a date
		doDate = function( date )
		{
			date = new Date( date * 1000 );
			return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
		},
		//-----------------------------------------
		// Formats a file size
		doSize = function( bytes ) 
		{
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

			if(bytes == 0){
				return "<em>--</em>";
			}

			var i = parseInt( Math.floor( Math.log( bytes ) / Math.log(1024) ) );
			return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
		},
		//-----------------------------------------
		// Renders the status information
		renderFileInformation = function( files )
		{
			if( files.length > 1 ){
				$('#media_path').html( files.length + ' files selected');
			} else if( files.length == 1 ) {
				$('#media_path').html( getFileInformation( files[0] ) );
			} else {
				$('#media_path').html('');
			}
		},

		//-----------------------------------------
		// Event handler for clicking a file
		clickFile = function(e)
		{
			e.preventDefault();

			// Select more than one
			if( e.ctrlKey || e.metaKey ){
				selectMore(this);
			}
			// Select a batch
			else if( e.shiftKey ){
				selectBatch(this);
			}
			// Select just one
			else {
				selectOne(this);
			}

			checkActionButtons();
		},

		//-----------------------------------------
		// Enable/disable action buttons as needed
		checkActionButtons = function()
		{
			if( !selectedFiles.length ){
				$('li[data-role="delete"],li[data-role="move"]', buttons).addClass('disabled');
			} else {
				$('li[data-role="delete"],li[data-role="move"]', buttons).removeClass('disabled');
			}
		},

		//-----------------------------------------
		// Select another file
		selectMore = function( file )
		{
			if( $(file).hasClass('selected') ){
				$(file).removeClass('selected');

				if( focusFile == file ){
					focusFile = null;
				}

				selectedFiles = $A( selectedFiles ).without( file );
			} else {
				$(file).addClass('selected');

				if( focusFile == null ){
					focusFile = file;
				}

				selectedFiles.push( file );
			}

			renderFileInformation( selectedFiles );
		},

		//-----------------------------------------
		// Select just one file
		selectOne = function( file )
		{
			unselectAll();

			if( $(file).hasClass('selected') ){
				$(this).removeClass('selected');
				focusFile = null;
				selectedFiles = $A( selectedFiles ).without( file );
			} else {
				$(file).addClass('selected');
				focusFile = file;
				selectedFiles.push( file );
			}

			renderFileInformation( selectedFiles );
		},

		//-----------------------------------------
		// Select a batch (shift key)
		selectBatch = function( file )
		{
			// Store temporarily
			tmpFocusFile = focusFile;

			//Unselect everything
			unselectAll();

			// Reset focused file
			$( tmpFocusFile ).addClass('selected');
			focusFile = tmpFocusFile;
			selectedFiles = [ tmpFocusFile ];

			// Is clicked file before or after focused file?
			var files = $('.file', browser);
			var posFocus = $.inArray( focusFile, files );
			var posClick = $.inArray( file, files );

			if( posFocus === -1 || posClick === -1 ){
				posFocus = 0;
				posClick = files.length;
			}

			// jQuery 1.5 doesn't support passing an element in next/prevUntil
			// So we'll get the fileid attribute and build a selector instead
			var fileid = $(file).data('fileid');

			if( posFocus < posClick ){
				var toSelect = $( focusFile ).nextUntil('.file[data-fileid="' + fileid + '"]').add( file ).add( focusFile );
			} else {
				var toSelect = $( focusFile ).prevUntil('.file[data-fileid="' + fileid + '"]').add( file ).add( focusFile );
			}

			// Select each one
			$( toSelect ).addClass('selected');
			selectedFiles = toSelect;

			renderFileInformation( selectedFiles );
		},

		//-----------------------------------------
		// focus off of a selection
		clickOff = function(e)
		{
			if( e.target.id == 'media_browser' ){
				unselectAll();
				checkActionButtons();
				renderFileInformation( selectedFiles );
			}
		},

		//-----------------------------------------
		// Unselect all files
		unselectAll = function()
		{
			$('.file', browser).removeClass('selected');
			focusFile = null;
			selectedFiles = [];
		},

		attachments = {
			obj: null,
			options: {},
			totalFiles: {},

			//-----------------------------------------
			// Initialize attachments
			init: function( opt )
			{
				attachments.options['url'] = opt['url'].replace(/&amp;/g, "&");
				attachments.options['swf_url'] = opt['swf_url'];

				Debug.write( attachments.options['url'] );

				var settings = {
					upload_url: 			attachments.options['url'],
					flash_url: 				attachments.options['swf_url'],
					file_post_name: 		'FILE_UPLOAD',
					file_types: 			'*.*',
					file_types_description: '',
					file_size_limit: 		'100 MB',
					file_upload_limit:  	0,
					file_queue_limit: 		100,
					custom_settings: 		{},
					post_params: 			{},
					debug: 					0, //1,
					
					// ---- BUTTON SETTINGS ----
					button_placeholder_id: 			'buttonPlaceholder',
					button_width: 					200,
					button_height: 					30,
					button_window_mode: 			SWFUpload.WINDOW_MODE.TRANSPARENT,
					button_cursor: 					SWFUpload.CURSOR.HAND,
				
					// ---- EVENTS ---- 
					upload_error_handler: 			attachments._uploadError,
					upload_start_handler: 			attachments._uploadStart,
					upload_success_handler: 		attachments._uploadSuccess,
					upload_complete_handler: 		attachments._uploadComplete,
					upload_progress_handler: 		attachments._uploadProgress,
					file_dialog_complete_handler: 	attachments._fileDialogComplete
					/*file_queue_error_handler: 		this._fileQueueError.bind(this),
					queue_complete_handler: 		this._queueComplete.bind(this),
					file_queued_handler: 			this._fileQueued.bind(this)*/
				};
				
				attachments.obj = new SWFUpload( settings );
			},

			reInit: function()
			{
				attachments.setParams();

				$('#upload_select').show();
				$('#upload_progress').hide();

				$('#do_upload').attr('disabled', 'disabled').css('opacity', '0.3');

				var pos = $('#choose_files').position();
				$('#SWFUpload_0').css({'position': 'absolute', 'top': pos.top + 'px', 'left': pos.left + 'px'});
			},

			//-----------------------------------------
			// Make sure post params are set
			setParams: function()
			{
				Debug.write( workingDir );
				attachments.obj.setPostParams( { 'dir': workingDir } );
			},

			//-----------------------------------------
			// Set the progress bar/text
			setProgress: function( data )
			{
				attachments.stats = attachments.obj.getStats();

				$('#upload_progress .progress > span').css('width', data.percent + '%' );
				$('#upload_progress .status').html( ipb.lang['mm_upload_template'].evaluate( { name: data.name, idx: data.idx + 1, total: attachments.totalFiles } ) );

				if( attachments.stats.files_queued === 0 ){
					attachments.finishedQueue();
				}
			},

			//-----------------------------------------
			// Hides the popup, deletes the cache, shows errors
			finishedQueue: function()
			{
				// Reload screen or delete cache
				if( workingDir == currentDir ){
					fetchFolderContents( { dir: workingDir, force: true } );
				} else {
					if( cache[ workingDir ] ){
						delete( cache[ workingDir ] );
					}
				}

				// Hide upload popup
				hidePopup( $('#media_popup_upload'), function(){
					$('#upload_select').show();
					$('#upload_progress').hide();	
				});				

				// Any errors uploading?
				if( attachments.stats.upload_errors ){
					alert( attachments.stats.upload_errors + ipb.lang['mm_not_uploaded_suc'] );
				}
			},

			//-----------------------------------------
			// SWFUPLOAD: uploadProgress event
			_uploadProgress: function( file, bytesLoaded, bytesTotal)
			{
				Debug.write( "Attachments (uploadProgress) " + file + ' ' + bytesLoaded + ' ' + bytesTotal );

				var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

				attachments.setProgress({ 	idx: file.index,
											percent: percent,
											name: file.name,
											status: 'in_progress'
										 });
			},
			//-----------------------------------------
			// SWFUPLOAD: uploadComplete event	
			_uploadComplete: function( file )
			{
				Debug.write( "Attachments (uploadComplete) " + file );

				attachments.setProgress({ 	idx: file.index,
											percent: '100',
											name: file.name,
											status: 'complete'
										 });

			},
			//-----------------------------------------
			// SWFUPLOAD: uploadSuccess event
			_uploadSuccess: function( file, serverData )
			{
				Debug.write( "Attachments (uploadSuccess) " + file );
				Debug.write( serverData );

				// Check it really was successful
				try {
					var obj = $.parseJSON( serverData );

					if( obj['error'] ){
						attachments.obj.setStats( { upload_errors: attachments.stats.upload_errors + 1 } );
					}
				} catch(err){}

			},
			//-----------------------------------------
			// SWFUPLOAD: uploadStart event
			_uploadStart: function( file )
			{
				Debug.write( "Attachments (uploadStart) " + file );

				if( $('#upload_select:visible') ){
					$('#upload_select').hide();
					$('#upload_progress').show();
					$('#SWFUpload_0').css( { 'position': 'absolute', 'top': '0px', 'left': '0px' } );
				}

				$('#upload_progress .progress > span').css('width', '0%');
			},
			//-----------------------------------------
			// SWFUPLOAD: uploadError event
			_uploadError: function( file, errorCode, message )
			{
				var msg;

				switch( errorCode )
				{
					case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
						msg = 'http_error';
						break;
					case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
						msg = 'failed';
						break;
					case SWFUpload.UPLOAD_ERROR.IO_ERROR:
						msg = 'io_error';
						break;
					case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
						msg = 'security_error';
						break;
					case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
						msg = 'upload_limit';
						break;
					case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
						msg = 'invalid_mime';
						break;
					default:
						msg = 'error: ' + errorCode;
						break;
				}
				
				Debug.write( "Attachment (uploadError) " + errorCode + ": " + message );
				return false;
			},
			//-----------------------------------------
			// SWFUPLOAD: fileDialogComplete event
			_fileDialogComplete: function( number, queued, total )
			{
				Debug.write( "Attachment (fileDialogComplete) Number: " + number + ", Queued: " + queued );
				$('#do_upload').removeAttr('disabled').css('opacity', '1');

				attachments.totalFiles = total;
			},
			//-----------------------------------------
			// Files have been selected, now activate Start button
			primedForUpload: function( e )
			{
				if( e ){ e.preventDefault(); }
				attachments.obj.startUpload();
			}
		};

		//-----------------------------------------
		// Make public methods public
		return {
			init: init
		}


	}($);
}(jQuery));