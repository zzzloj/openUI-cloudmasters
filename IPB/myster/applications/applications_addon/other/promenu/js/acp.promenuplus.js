( function($) {
	LoadGroups = function(){	
		a = encodeURI( $("#promenu_group").val() );
		window.location = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus&key="+a;
	},
	 
	loadfields = function(){
		a = $('#promenu_menus_link_type').val();
		
		if(a == "html"){	
			$("#blocks,#surl,#menu_activation").fadeIn();
			htmlEditor("promenu_menus_block");
		}
		else if(a == "man")
		{
			$("#surl").show();
		}
		else if(a == "app"){
			$("#app").show();
			if($("#promenu_menus_app_link").val() === "ccs")
			{
				$("#content_page").show();
			}	
			if($("#promenu_menus_app_link").val() === "easypages"){
				$("#easypages_page").show();
			}
		}
		else if(a == "ipc"){
			$("#content_page").show();
		}
		 
		else if(a == "cblock")
		{
			$("#ipc_blocks,#surl,#menu_activation").show();
			//htmlEditor("ipc_blocks_code");
		}
		else if(a == "eblock"){
			$("#easypages_blocks,#surl,#menu_activation").show();
			//htmlEditor("easypage_blocks_code");
		}
		else if(a == "pblock"){
			$("#phpBlock,#surl,#menu_activation").show();
			phpEditor();
		}		
		else{
			$("#blocks,#surl,#app,#menu_activation").hide();
			htmlEditor("promenu_menus_block");
		}
		
		if($("#promenu_menus_img_as_title_check").is(":checked")){
			$("#img_title").show();
		}
		if($("#promenu_menus_icon_check").is(":checked")){
			$("#dropdown_icon").hide().promise().done(function(){
				$("#img_img").show()
				});
		}

		if($("#promenu_menus_app_link").val() === "forums")
		{
			$("#forum_feature").show();
		}
	},
	phpEditor = function(){
		var php = CodeMirror.fromTextArea(document.getElementById("promenu_menus_pblock"), {
		    	lineNumbers: true,
		    	styleActiveLine: true,
		    	highlightSelectionMatches: true,
		       	matchBrackets: true,
		       	mode: "application/x-httpd-php",
		       	indentUnit: 4,
		       	indentWithTabs: true,
		       	enterMode: "keep",
		       	tabMode: "shift",
  				lineWrapping: true,
		});
	},

	htmlEditor = function(name){
	      CodeMirror.commands.autocomplete = function(cm) {
	          CodeMirror.showHint(cm, CodeMirror.htmlHint);
	      }
		  var html = CodeMirror.fromTextArea(document.getElementById(name), {
		        lineNumbers: true,
		        styleActiveLine: true,	
		        highlightSelectionMatches: true,
		        mode: 'text/html',
		        autoCloseTags: true,
		        lineNumbers: true,
  				lineWrapping: true,
		       	extraKeys: {"Ctrl-Space": "autocomplete"
			}
		});
	}
	GenAjaxPost = function(url, pData){
		var info = $.ajax({
			type : "POST",
			url : url,
			data: pData,
			processData: false,
			async : false
		}).responseText;	
		return info;
	},	
	getTemplatesForAdd = function() {
		type = $("select[id*=skinGroup]").val();
		$("#skin_id,#skin_position,#skin_type,#skin_group").hide();
		if(type != 0)
		{
			url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getTemplatesForAdd&amp;secure_key=" + ipb.vars['md5_hash'];
			url = url.replace( /&amp;/g, '&' );
			a = GenAjaxPost(url,"i=1&group=" + type);
			if(a){
				$("#skin_group_content").html(a);
				$("#skin_group").show("slide",{
					direction: "left"
				}, 500);
			}
		}
		else{
			$("#skin_id,#skin_position,#skin_type,#skin_group").hide();
		}
	},	
	getTypeOfHook = function() {
		type = $("select[id*=skinFunction]").val();
		$("#skin_id,#skin_position,#skin_type").hide();
		if(type != 0)
		{
			var newRow = ipb.templates['hook_pointTypes'].evaluate({
				index: 1
			});
			$("#skin_type").html(newRow);
			$("#skin_type").show("slide", {
				direction: "left"
			}, 500);

		}
		else{
			$("#skin_id,#skin_position,#skin_type").hide();
		}
	},	
	getHookIds = function() {
		type = $("select[id*=type]").val();
		group = $("select[id*=skinGroup]").val();
		template = $("select[id*=skinFunction]").val();
		$("#skin_id,#skin_position").hide();

		if(type != 0) {
			url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getHookIds&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=1&type=" + type + "&template=" + template + "&group=" + group;
			url = url.replace( /&amp;/g, '&' );
			
			a = GenAjaxPost(url, "i=1&type="+type+"&template="+template+"&group="+group);
			if(a){
				$("#skin_id_content").html(a);
				$("#skin_id").show("slide",{
					direction: "left"
				}, 500);
			}
		}
		else{
			$("#skin_id,#skin_position").hide();
		}
		
	},	
	getHookEntryPoints = function(){
		type = $("select[id*=type]").val();
		options = '';
		if( type == 'foreach' )
		{
			options += "<option value='outer.pre'>(outer.pre) " + ipb.lang['a_outerpre'] + "</option>";
			options += "<option value='inner.pre'>(inner.pre) " + ipb.lang['a_innerpre'] + "</option>";
			options += "<option value='inner.post'>(inner.post) " + ipb.lang['a_innerpost']+ "</option>";
			options += "<option value='outer.post'>(outer.post) " + ipb.lang['a_outerpost']+ "</option>";
		}
		else
		{
			options += "<option value='pre.startif'>(pre.startif) " + ipb.lang['a_prestartif'] + "</option>";
			options += "<option value='post.startif'>(post.startif) " + ipb.lang['a_poststartif'] + "</option>";
			options += "<option value='pre.else'>(pre.else) " + ipb.lang['a_preelse'] + "</option>";
			options += "<option value='post.else'>(post.else) " + ipb.lang['a_postelse'] + "</option>";
			options += "<option value='pre.endif'>(pre.endif) " + ipb.lang['a_preendif'] + "</option>";
			options += "<option value='post.endif'>(post.endif) " + ipb.lang['a_postendif'] + "</option>";
		}
		/* Insert the new hook type row */
		//if ( type != '0' )
		//{
		var newRow = ipb.templates['hook_pointLocation'].evaluate({
			index: 1, 
			hookPoints: options
		});
			
		$("#skin_position").html(newRow).show("slide", {
			direction: "left"
		}, 500);
	//}
	},
	hoverCount = function(){
		$("#promenu_groups_hover_speed").keypress(function(){
			console.log($(this).val());
			if($(this).val() >= 1000)
			{
				alert(" to long");
			}
		});
	}
	$.fn.isNumerical = function(e){
		
		return this.each(function(e){
			$("#promenu_groups_hover_speed",this).keyup(function(){
				if($(this).val() >= 1000)
				{
					$(this).parent().parent().addClass("err").append("<br><span class='remove'>this time "+$(this).val()+" can lead to extremely long opening and closing times.</span>")
				}
				else{
					$(this).parent().parent().removeClass("err");
					$('.remove').each(function(){
						$(this).remove();
					});
				}
			});
			
			$("#promenu_groups_hover_speed,#promenu_groups_zindex,#promenu_groups_top_offset").keyup(function(){
				if(!$.isNumeric($(this).val()))
				{				
					$(this).parent().parent().addClass("errs").append("<br><span class='removes'>this must be a numerical value!</span>")
				}
				else{
					$(this).parent().parent().removeClass("errs");
					$('.removes').each(function(){
						$(this).remove();
					});
				}
			})

		});
	},
	$.fn.hookCheck = function(e){
		return this.each(function(e) {
			$("#exportSub").click(function(e){
				
				if($("select[id*=skinGroup]").val() == 0)
				{
					$("select[id*=skinGroup]").parent().addClass("err").prev().addClass("err");
					e.preventDefault();
				}
				else{
					$("select[id*=skinGroup]").parent().removeClass("err").prev().removeClass("err");
				}
				
				if($("select[id*=skinFunction]").val() == 0)
				{
					$("select[id*=skinFunction]").parent().addClass("err").prev().addClass("err");
					e.preventDefault();
				}
				else{
					$("select[id*=skinFunction]").parent().removeClass("err").prev().removeClass("err");
				}
				
				if($("select[id*=type]").val() == 0)
				{
					$("select[id*=type]").parent().addClass("err").prev().addClass("err");
					e.preventDefault();
				}
				else{
					$("select[id*=type]").parent().removeClass("err").prev().removeClass("err");
				}
				
				if($("select[id*=id]").val() == 0 )
				{
					$("select[id*=id]").parent().addClass("err").prev().addClass("err");
					e.preventDefault();
				}
				else if($("select[id*=id]").val() == undefined)
				{
					$("#skin_id").find("td").addClass("err");
					e.preventDefault();					
				}
				else{
					$("select[id*=id]").parent().removeClass("err").prev().removeClass("err");
				}
			});
		});		
	},
	$.fn.extend({
		insertAtCaret: function(myValue){
			return this.each(function(i) {
				if (document.selection) {
					//For browsers like Internet Explorer
					this.focus();
					sel = document.selection.createRange();
					sel.text = myValue;
					this.focus();
				}
				else if (this.selectionStart || this.selectionStart == '0') {
					//For browsers like Firefox and Webkit based
					var startPos = this.selectionStart;
					var endPos = this.selectionEnd;
					var scrollTop = this.scrollTop;
					this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
					this.focus();
					this.selectionStart = startPos + myValue.length;
					this.selectionEnd = startPos + myValue.length;
					this.scrollTop = scrollTop;
				} else {
					this.value += myValue;
					this.focus();
				}
			})
		}
	}),
	$.fn.groupSettings = function(e){
		return this.each(function(e){
			$('a[id*=GroupSettings_]',this).click(function(e){
				e.preventDefault();
				if( !$(this).data("isOpen")){
					$("#GroupSettings_1").data("isOpen",1);
					$("#GroupSettings_2").data("isOpen",1);
					$.ajax({
						type : "POST",
						url : ipb.vars['app_url'].replace(/&amp;/g, '&')+'module=ajax&section=ajax&do=createGroups&md5check=' + ipb.vars['md5_hash'],
						processData: false,				
						data: "group="+$(this).data('group'),
						dataType: "html",
						beforeSend: function(data){
							if($('#ajax_loading').length == 0)
							{
								$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
							}
							$('#ajax_loading').fadeIn(100);	
						},					
						success : function(data) {
							$("#group_Settings").html(data);		
						},		
						complete:function(){
							$('#ajax_loading').fadeOut(100);
							$("#group_Settings").fadeIn();
						},
						error: function(){
							alert("error, time out page reloading..." );
						}
					})
				}
				else{
					$("#GroupSettings_1").removeData("isOpen");
					$("#GroupSettings_2").removeData("isOpen");
					$("#group_Settings").fadeOut();
				}
			})
		});
	},
	$.fn.linkTypeview = function(){
		
		$("#promenu_menus_img_as_title_check",this).click(function(){
			if($(this).is(":checked")){
				$("#img_title").fadeIn();
			}
			else{
				$("#img_title").fadeOut();		
			}
		});
		
		$("#promenu_menus_icon_check",this).click(function(){
			if($(this).is(":checked")){
				$("#dropdown_icon").fadeOut().promise().done(function(){
					$("#img_img").fadeIn()
					});
			}
			else{
				$("#img_img").fadeOut().promise().done(function(){
					$("#dropdown_icon").fadeIn()
					});
			}
		});
		
		$('.block-preview-link',this).click(function(e){
			e.preventDefault();

			var id		= $(this).attr("id").replace( '-blockPreview', '' );
			var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=blocks&secure_key=" + ipb.vars['md5_hash'] + '&do=preview&id=' + id;
			
			popup = new ipb.Popup( 'blockPreview', {
				type: 'pane', 
				modal: true, 
				w: '600px;',  
				ajaxURL: url, 
				hideAtStart: false, 
				close: 'a[rel="close"]'
			} );
			return false;
		});
		
		$("#promenu_menus_app_link",this).change(function(e){
			a = $(this).val();
			$("#content_page,#forum_feature,#easypages_page").hide();
			if(a === "ccs")
			{
				$("#content_page").fadeIn();
			}
			else if(a === "forums")
			{
				$("#forum_feature").fadeIn();
			}
			else if(a === "easypages"){
				$("#easypages_page").fadeIn();
			}
		});
		
		$("#promenu_menus_link_type",this).change(function(e){
			$("#blocks,#surl,#app,#phpBlock,#content_page,#ipc_blocks,#easypages_page,#easypages_blocks,#forum_feature,#menu_activation").hide(10);
			$(".CodeMirror").remove();
			a = $(this).val();
			if(a == "html" ){
				
				$("#blocks,#surl,#menu_activation").fadeIn();
				htmlEditor("promenu_menus_block");
			}
			else if( a == "pblock")
			{
				$("#phpBlock,#surl,#menu_activation").fadeIn();
				phpEditor();
			}
			else if(a == "man")
			{
				$("#surl").fadeIn();
			}
			else if(a == "app"){
				$("#app").fadeIn();
				if($("#promenu_menus_app_link").val() === "ccs")
				{
					$("#content_page").fadeIn();
				}
				if($("#promenu_menus_app_link").val() === "forums")
					{
						$("#forum_feature").fadeIn();
					}
			}
			else if(a == "cblock")
			{
				$("#ipc_blocks,#surl,#menu_activation").fadeIn();
				//htmlEditor("ipc_blocks_code");
			}
			else if(a == "eblock"){
				$("#easypages_blocks,#surl").fadeIn();
			//	htmlEditor("easypage_blocks_code");
			}
			else{
				$("#blocks,#surl,#app,#content_page,#forum_feature,#menu_activation").fadeOut();
				htmlEditor("promenu_menus_block");
			}
		})
		
		$('.insert_tag',this).click(function(e){
			e.preventDefault();
			if( $("#promenu_menus_link_type").val() == "cblock"){
				$("#ipc_blocks_code").insertAtCaret($(this).data("tag"));
			}
			else{
				$(".CodeMirror-scroll").find("div:first").insertAtCaret($(this).data("tag"));
			}
		});
	},
	$.fn.MenuOpen = function(){
		return this.each(function(e){
			$("[id*=toggle_]").click(function(e){
				e.preventDefault();
				var id = $(this).data("id");
				if($(this).data("status") == 1)
				{
					var status = 1;
					var state = "state="+1+"&id="+$(this).data("id");
				}
				else{
					var status = 2;
					var state = "state="+0+"&id="+$(this).data("id");
				}
				$.ajax( {
					url : ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=state&md5check=' + ipb.vars['md5_hash'],
					type: "POST",
					data: state,
					processData: false,
					beforeSend: function(data){
						if($('#ajax_loading').length == 0)
						{
							$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
						}
						$('#ajax_loading').fadeIn(100);		
					},
					success: function(data){
						if( data['error'] )
						{
							alert( data['error'] );
							if( data["__session__expired__log__out__"] ){
								window.location.reload();
							}
						}

					},
					complete:function(){
						$('#ajax_loading').fadeOut(100);
						if(status == 1)
						{
							$("#state_"+id).show("slide", {
								direction: "left"
							},1000);
							$("#toggle_"+id).data("status",2).parent().removeClass("openit").addClass("closeit")
						}
						else{
							$("#state_"+id).hide("slide", {
								direction: "right"
							},1000);
							$("#toggle_"+id).data("status",1).parent().removeClass("closeit").addClass("openit")
						}
					},
					error: function(){
						alert( ipb.lang['session_timed_out'] );
						window.location.reload();
					}
				});				
			})
		});
	},
	$.fn.menusort = function (type, options) {
		var defaults = {
			'main': {
				handle: 'div.draghandle1',
				items: 'div.isDraggable1',
				opacity: 0.6,
				revert: false,
				tolerance:'pointer',
				sendType: 'get',
				distance:10,	
				axis:'y',
				scrollSensitivity:20,
				scrollSpeed:2
			},
			'subs': {
				handle: 'div.draghandle2',
				items: 'div.isDraggable2',
				opacity: 0.6,
				revert: false,
				zIndex: 5 ,
				tolerance:'pointer',
				sendType: 'get',
				distance:10,	
				axis:'y',
				scrollSensitivity:20,
				scrollSpeed:2
			}
		};
		
		return this.each(function(){

			var x = defaults[type];
			var o = $.extend( x, options );
			

			o['update'] = function( e, ui )
			{
				if ( ! Object.isUndefined( o['callBackUrlProcess'] ) )
				{
					o['url'] = o['callBackUrlProcess']( o['url'] );
				}
				
				var serial = $(this).sortable("serialize", ( o['serializeOptions'] || {} ) );
				$.ajax( {
					url: o['url'].replace( /&amp;/g, '&' ),
					type: o['sendType'],
					data: serial,
					processData: false,
					beforeSend: function(data){
						if($('#ajax_loading').length == 0)
						{
							$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
						}
						$('#ajax_loading').fadeIn(100);		
					},
					success: function(data){
						console.log(data);
						if(data == "error")
						{
							alert("You do not have permission to access this function");
						}
						i = 0;
						$.each(data, function(k,c) {

							if(i == 0)
							{
								$("#menus_"+c).find("div:first").removeClass("alt_2").removeClass("alt_1").addClass("alt_2");
								i = 1;
							}
							else{
								$("#menus_"+c).find("div:first").removeClass("alt_2").removeClass("alt_1").addClass("alt_1");
								i = 0;
							}
						})
						if( data['error'] )
						{
							alert( data['error'] );
							if( data["__session__expired__log__out__"] ){
								window.location.reload();
							}
						}

						if ( ! Object.isUndefined( o['postUpdateProcess'] ) )
						{
							o['postUpdateProcess'](data);
								
						}
					},
					complete:function(){
						$('#ajax_loading').fadeOut(100);
					},
					error: function(){
						alert( ipb.lang['session_timed_out'] );
						window.location.reload();
					}
				});
			}
			
			$(this ).sortable( o );
		});
	};
}(jQuery));