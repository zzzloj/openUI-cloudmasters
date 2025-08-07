( function($) {

	LoadGroups = function(){	
		a = encodeURI( $("#promenu_group").val() );
		window.location = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus&key="+a;
	},
	loadfields = function(){
		if($('#promenu_menus_link_type').val()){
			if($("#promenu_menus_id").length != 0)
			{
				id = $("#promenu_menus_id").val();
			} 
			else{
				id = 0;
			}

			ent = $("#promenu_menus_link_type").val();
			$.ajax({
					type : "POST",
					url : ipb.vars['app_url'].replace(/&amp;/g, '&')+'module=ajax&section=ajax&do=getLinkType&md5check=' + ipb.vars['md5_hash'],
					processData: false,				
					data: "data="+ent+"&id="+id,
					dataType: "html",
					beforeSend: function(data){

					},					
					success : function(data) {
						$("#catItem_table").find('tbody').append(data);		
					},		
					complete:function(){
						$('.show').show();

					},
					error: function(){
						alert("error, time out page reloading..." );
					}
				})
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
		
		$("#promenu_menus_link_type",this).change(function(e){
			if($("#promenu_menus_id"))
			{
				id = $("#promenu_menus_id").val();
			} 
			else{
				id = 0;
			}
			ent = $("#promenu_menus_link_type").val();
			$.ajax({
					type : "POST",
					url : ipb.vars['app_url'].replace(/&amp;/g, '&')+'module=ajax&section=ajax&do=getLinkType&md5check=' + ipb.vars['md5_hash'],
					processData: false,				
					data: "data="+ent+"&id="+id,
					dataType: "html",
					beforeSend: function(data){
						$(".show,.show2").remove();
						if($('#ajax_loading').length == 0)
						{
							$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
						}
						$('#ajax_loading').fadeIn(100);	
					},					
					success : function(data) {
						$("#catItem_table").find('tbody').append(data);		
					},		
					complete:function(){
						$('#ajax_loading').fadeOut(100);
						$('.show').fadeIn();
					},
					error: function(){
						alert("error, time out page reloading..." );
					}
				})
		});
	},
	$.fn.DelMove = function(e){
		
		$('#massMove').click(function(e){
			e.preventDefault();
			ids = '';
			i = 0;
			$("#itsa").find("input.delo").each(function(e){
				if($(this).prop('checked') && $(this).data("pid") == 0){
					ids = "id[]="+$(this).data('id')+":"+$(this).data("pid")+"&"+ids;
					i++;
				}				
			});
			if(i != 0){
				urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=move&"+ids+"key="+$("#itsa").data('key')+"&md5check=" + ipb.vars['md5_hash'];
				PopupMove = new ipb.Popup( 'PopupMove',{
														type: 'pane',
														modal: true,
														w: '810px',
														h: '530px',
														ajaxURL: urls,
														hideAtStart: false, 
														close: 'a[rel="close"]'
													},
													{
														afterHide: function() { PopupMove.kill() }
													}
								);
			}
			else{
				alert("Only Root Parent Items can be moved.");
			}
		});
		
		$("a[id*=singleClone]").click(function(e){
			e.preventDefault();
			urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=cloneSingle&id="+$(this).data('id')+"&md5check="+ipb.vars['md5_hash'];
			PopupClone = new ipb.Popup( 'PopupClone',{
													type: 'pane',
													modal: true,
													w: '810px',
													h: '530px',
													ajaxURL: urls,
													hideAtStart: false, 
													close: 'a[rel="close"]'
												},
												{
													afterHide: function() { PopupClone.kill() }
												}
							);
		});
		
		$("#massClone").click(function(e){
			e.preventDefault();
			urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=cloneGroup&key="+$("#itsa").data('key')+"&md5check=" + ipb.vars['md5_hash'];
			PopupClone = new ipb.Popup( 'PopupClone',{
													type: 'pane',
													modal: true,
													w: '810px',
													h: '530px',
													ajaxURL: urls,
													hideAtStart: false, 
													close: 'a[rel="close"]'
												},
												{
													afterHide: function() { PopupClone.kill() }
												}
							);			
		});
		
		$("#identity_main").click(function(){
			if(!$(this).data("isChecked")){
				$(this).data("isChecked",1);
				$("#itsa").find(".delo").each(function(){
					
					if(!$(this).prop('checked'))
					{
						$(this).attr("checked",1);
					}
				});
			}
			else{
				$(this).removeData("isChecked");
				$("#itsa").find(".delo").each(function(){
					if($(this).prop('checked'))
					{
						$(this).removeAttr("checked");
					}
				});
			}
		});
		
		$("input[id*=identity_]").click(function(){
			var isOpen = 0;
			$("#itsa").find("input.delo").each(function(){
				if($(this).prop('checked'))
				{
					isOpen = 1;
				}
			});
			
			if(isOpen === 1){
				if(!$("#delMove").data("iOpen")){
					$("#delMove").show("slide",{direction:"up"}).data("iOpen",1);
				}
			}
			else{
				$("#delMove").hide("slide",{direction:"down"}).removeData("iOpen");
			}
		});
		
		$("#massDel").click(function(e){
			key = $("#itsa").data('key');
			e.preventDefault();
			ids = '';
			if( confirm(deleteMass) ){
				i = 0;
				is = '';
				$("#itsa").find("input.delo").each(function(e){
					if($(this).prop('checked')){
						ids = "id[]="+$(this).data('id')+":"+$(this).data("pid")+"&"+ids;
						is = $(this).data('id')+","+is;
						
					}
				});
				url = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=deleteMenus&md5check=' + ipb.vars['md5_hash'];
				AjaxPost( url, ids, key, is);
				if($("#identity_main").prop("checked"))
				{
					$("#identity_main").removeAttr("checked").removeData("isChecked");	
				}
				$("#delMove").removeData("iOpen").hide("slide",{direction:"down"});
			}
		});
	},
	GenAjaxPost = function(url, pData){
		var info = $.ajax({
			type : "POST",
			url : url,
			data: pData,
			processData: false,
			async : false,
		}).responseText;	
		return info;
	},
	AjaxPost = function(urls,datas,other,is){
			$.ajax({
					type : "POST",
					url : urls,
					processData: false,				
					data: datas,
					dataType: "html",
					beforeSend: function(data){
						is = is.split(",");
						$.each(is, function(key,val){
							$("#menus_"+val).hide("slide",{direction:"right"},300).promise().done(function(){ $(this).remove()});
						})
						if($('#ajax_loading').length == 0)
						{
							$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
						}
						$('#ajax_loading').fadeIn(100);	
					},					
					success : function(data) {
					},		
					complete:function(){
						$('#ajax_loading').fadeOut(100);
						//if(!$("#show_prev").data("status")){
							cjAjax({
								url: ipb.vars['front_url'].replace(/&amp;/g, '&') + 'app=promenu&module=preview&section=preview&md5check=' + ipb.vars['md5_hash'],
								data: "group="+other,
								showLoad: 0,
								callBack: function(data) {
            						$("#preview").html(data);
								}
							});
						//}
						var exist = 0;
						$("#itsa").find("input.delo").each(function(e){
							if($(this).attr("id") !== "identity_main"){
								exist = 1;
							}
						});
						if(!exist){
							$("#selectAll,#massClone").hide().promise().done( function(e){ $(this).remove(); $("#noMenus").fadeIn(); } );
						}
					},
					error: function(){
						alert("error, time out page reloading..." );
					}
				})
	},
	cjAjax = function(op){
		var defaults = {
				url: '',
				data: '',
				type: "POST",
				dataType: "html",
				callBack: function(e){},
				showLoad: 1
			};	
		var o = $.extend( defaults, op );	
				$.ajax({
					type : o['postType'],
					url : o['url'].replace( /&amp;/g, '&' ),
					processData: false,				
					data: o['data'],
					dataType: o['dataType'],
					beforeSend: function(data){
						if(o['showLoad']){
							if($('#ajax_loading').length == 0)
							{
								$('#ipboard_body').prepend( ipb.templates['ajax_loading'] );
							}
							$('#ajax_loading').fadeIn(100);
						}	
					},					
					success : function(data) {
						return o['callBack'](data);
					},		
					complete:function(){
						if(o['showLoad']){
							$('#ajax_loading').fadeOut(100);
						}
					},
					error: function(){
						alert("error, time out page reloading..." );
						location.reload();
					}
				});
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
						if(data == "error")
						{
							alert("You do not have permission to access this function");
						}
						i = 0;
						$.each(data, function(k,c) {
							if(i == 0)
							{
								$("#menus_"+c).find("div.item").removeClass("alt_2").removeClass("alt_1").addClass("alt_2");
								i = 1;
							}
							else{
								$("#menus_"+c).find("div.item").removeClass("alt_2").removeClass("alt_1").addClass("alt_1");
								i = 0;
							}
						});
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
	
 ProValidate = function(e){

 	errors = 0;
 	names = 0; 	
 	$("#erroShow").hide();

 	$("#adminform").find("tr").each(function(){
 		$(this).removeClass("errors");
 	});
 	
 	$("#erroShow").find("li").each(function(){
 		$(this).hide();
 	});
	
	
 	$("input[id*=promenu_menus_name]").each(function(){
 		if($(this).val()){
 			names++;
 		}
 	})
 	
 	if(names == 0){
 		errors = 1;
 		$("#proName").addClass("errors");
 		$("#noName").show();
 	}
 	
 	if($("#promenu_menus_img_as_title_check").prop("checked"))
 	{
 		if(!$("#promenu_menus_title_icon").val())
 		{
 			errors = 1;
 			$("#proTitleImage").addClass("errors");
 			$("#noIMGurl").show();
 		}
 		if(!$("#promenu_menus_img_as_title_w").val())
 		{
 			errors = 1;
 			$("#proTitleImage").addClass("errors");
 			$("#noTimgW").show();
 		}
 		else{
 			if(isNaN($("#promenu_menus_img_as_title_w").val()))
 			{
 				errors = 1;
 				$("#proTitleImage").addClass("errors");
 				$("#noTimgWnumeric").show();
 			}
 		}
 		
 		if(!$("#promenu_menus_img_as_title_h").val())
 		{
 			errors = 1;
 			$("#proTitleImage").addClass("errors");
 			$("#noTimgH").show();
 		}
 		else{
 			if(isNaN($("#promenu_menus_img_as_title_h").val()))
 			{
 				errors = 1;
 				$("#proTitleImage").addClass("errors");
 				$("#noTimgHnumeric").show();
 			}
 		}	
 	}

 	if($("#promenu_menus_icon_check").prop("checked"))
 	{
 		if(!$("#promenu_menus_icon_custom").val())
 		{
 			errors = 1;
 			$("#proTitleImagei").addClass("errors");
 			$("#noIMGurli").show();
 		}
 		if(!$("#promenu_menus_icon_w").val())
 		{
 			errors = 1;
 			$("#proTitleImagei").addClass("errors");
 			$("#noTimgWi").show();
 		}
 		else{
 			if(isNaN($("#promenu_menus_icon_w").val()))
 			{
 				errors = 1;
 				$("#proTitleImagei").addClass("errors");
 				$("#noTimgWnumerici").show();
 			}
 		}
 		
 		if(!$("#promenu_menus_icon_h").val())
 		{
 			errors = 1;
 			$("#proTitleImagei").addClass("errors");
 			$("#noTimgHi").show();
 		}
 		else{
 			if(isNaN($("#promenu_menus_icon_h").val()))
 			{
 				errors = 1;
 				$("#proTitleImagei").addClass("errors");
 				$("#noTimgHnumerici").show();
 			}
 		}	
 	}
	
	if($("#promenu_menus_link_type").val() === "app")
	{
		if(!isNaN($("#promenu_menus_app_link").val())){
			errors = 1;
			$("#app").addClass("errors");
			$("#napp").show();
		}
		else{
			if($("#promenu_menus_app_link").val() === "ccs")
			{
				if($("#promenu_menus_content_link").val() == 0){
					errors = 1;
					$("#content_page").addClass("errors");
					$("#nccs").show();
				}
			}
			
			if($("#promenu_menus_app_link").val() === "ccsDB"){
//				aa = cjAjax({
//									url: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=checkPage&md5check=' + ipb.vars['md5_hash'],
//									postType: "POST",
//									data: "db="+$("#promenu_menus_content_link").val(),
//									dataType: "html",
//									callBack: function(data) {
//            							if(data == "error"){
//											errors = 1;
//											boo("#content_page").addClass("errors");
//											boo("#nopage").show();
//										}
//									}
//								});
			url = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=checkPage&md5check=' + ipb.vars['md5_hash'];
			pData = "db="+$("#promenu_menus_content_link").val();
			aa = GenAjaxPost(url,pData);
			
			if(aa == "error"){
				errors = 1;
				$("#content_page").addClass("errors");
				$("#nopage").show();
			}

			}
			
			if($("#promenu_menus_app_link").val() === "easypages")
			{
				if(!isNaN($("#promenu_menus_easy_link").val())){
					errors = 1;
					$("#easypages_page").addClass("errors");
					$("#neasy").show();
				}
			}
		}
	}

	if($("#promenu_menus_link_type").val() === "wrap")
	{
		if(!$("#promenu_menus_wrapper").val()){
			errors = 1;
			$("#wurl").addClass("errors");
			$("#nwrapUrl").show();
		}
		if(!$("#promenu_menus_wrapper_wrapped_yes").prop("checked")){
			if(isNaN($("#promenu_menus_wrapper_height").val()))
			{
				errors = 1;
				$("#wurlh").addClass("errors");
				$("#nwrapH").show();
			}
			
			if(!$("#promenu_menus_wrapper_height").val())
			{
				errors = 1;
				$("#wurlh").addClass("errors");
				$("#nwrapHBlank").show();
			}
		}
	}

	if($("#promenu_menus_link_type").val() === "cblock")
	{
		if(!ipchtml.getValue())
		{
			errors = 1;
			$("#ipc_blocks").addClass("errors");
			$("#nblock").show();
		}		
	}

	if($("#promenu_menus_link_type").val() === "eblock")
	{
		if(!easyhtml.getValue())
		{
			errors = 1;
			$("#easypages_blocks").addClass("errors");
			$("#nblock").show();
		}		
	}

 	if($("#promenu_menus_link_type").val() === "pblock")
 	{
 		datas = docphp.getValue();
 		url = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=phpVer&md5check=' + ipb.vars['md5_hash'];
 		a = GenAjaxPost(url,"data="+datas);
 		if(a == 0)
		{
			errors = 1;
			$("#phpBlock").addClass("errors");
			$("#nblock").show();
		}
		if(a == 1){
			errors = 1;
			$("#phpBlock").addClass("errors");
			$("#phpValid").show();
		}
 	}
	
	if($("#promenu_menus_link_type").val() === "html")
	{
		if(!dochtml.getValue())
		{
			errors = 1;
			$("#blocks").addClass("errors");
			$("#nblock").show();
		}
	}
 	if($("#promenu_menus_is_mega_yes").prop("checked"))
 	{
 		if(isNaN($("#promenu_menus_mega_column_count").val())){
 			errors = 1;
 			$("#megaCount").addClass("errors");
 			$("#nmegacount").show();
 		}
 	}	
 	if(errors == 1){
 		$("#erroShow").fadeIn();
 		$('html, body').animate({scrollTop: $("#erroShow").offset().top}, 200)
 		return false;
 	}
 	else{
 		return true;
 	}
 }	
	
}(boo));