<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
class skin_menus extends output {
	/* We must declare a destructor */

	public function __destruct() {
	}

	public function jQueryInit() {
		if (IPB_LONG_VERSION < 34006) {

			$html .=<<<EOF
			<script src="{$this->settings['public_dir']}js/3rd_party/jquery-1.8.3.min.js"></script>
			<script src="{$this->settings['public_dir']}js/3rd_party/jquery-ui-1.9.2.custom.min.js"></script>
			<script>
				boo = jQuery.noConflict();
			</script>
EOF;
		} else {
			$html .= '<script> boo = jQuery.noConflict(); </script>';
		}
		return $html;
	}

	public function main_container($data = array(), $sidebar = '') {
		$html = "";
		$title = IPSLib::getAppTitle('promenu');
		$key = urldecode($this->request['key']) ? urldecode($this->request['key']) : "primary";
		$news = $this->registry->profunctions->gather_news();
		$app = $this->cache->GetCache('app_cache');
		$update = $this->registry->profunctions->get_update($app);
		$group = $this->caches['promenu_groups'][$key];

		$status = $group['promenu_groups_preview_close'] ? "{$this->lang->words['promenu_word_show']} {$this->lang->words['promenu_word_previewl']}" : "{$this->lang->words['promenu_word_hide']} {$this->lang->words['promenu_word_previewl']}";
		$status_msg = $group['promenu_groups_preview_close'] ? "{$this->lang->words['promenu_click_to']} {$this->lang->words['promenu_word_display']} {$this->lang->words['promenu_word_previewl']}" : "{$this->lang->words['promenu_click_to']} {$this->lang->words['promenu_word_close']} {$this->lang->words['promenu_word_previewl']}";
		//$group = $this->caches['promenu_groups'][$this->request['key']];

		if ($group['promenu_groups_template'] === "proMain") {
			$style = $this->registry->profunctions->getHookData('proMainCss.css');
		} else {
			$style = $this->registry->profunctions->getHookData('proOtherCss.css');
		}

		$style = str_replace("{menu_id}", $group['promenu_groups_name'], $style);
		$style = str_replace("\n", "<br>", $style);
		$html .=<<<EOF
		<div style="display:none;" id="thisCss">
		<h3>{$this->lang->words['promenu_default_css']}</h3>
			<div style="width:800px;height:500px;overflow-y:auto;padding:20px;word-wrap:break-word;">
				{$style}
			</div>
		</div>
EOF;
		$html .= $this->jQueryInit();

		$skin = $this->caches['skinsets'][$this->registry->output->skin['set_id']];
		if($skin['set_key'] == "mobile")
		{
			foreach($this->caches['skinsets'] as $k => $v)
			{
				if($v['set_is_default'])
				{
					$style = '<link rel="stylesheet" type="text/css" media="screen,print" href="'.$this->settings['public_dir'].'style_css/css_'.$k.'/promenu_plus.css"/>';
					break;
				}
			}
		}
		else{
 				$style = '<link rel="stylesheet" type="text/css" media="screen,print" href="'.$this->settings['public_dir'].'style_css/'.$this->registry->output->skin['_csscacheid'].'/promenu_plus.css"/>';
 			}

		$html .= <<<EOF
			{$style}
			<link rel="stylesheet" type="text/css" href="{$this->settings['promenu_jquery_ui_css']}" />
			<script src="{$this->settings['js_app_url']}acp.promenuplus.js"></script>
			<script src="{$this->settings['js_app_url']}acp.simplyscroll.js"></script>
			<script>
				boo(document).ready(function(){
					url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
					boo("#section_navigation").find("a").each(function(){
						if(boo(this).attr('href') == url)
						{
							boo(this).parent().append("{$sidebar}");
						}
					})
					boo("#Menus").menusort('main', {
						url: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=reorder&md5check=' + ipb.vars['md5_hash'],
						serializeOptions: { key: 'menus[]' },
						postUpdateProcess: function() {
            				boo("#preview").html(GenAjaxPost( ipb.vars['front_url'].replace(/&amp;/g, '&') + 'app=promenu&module=preview&section=preview&md5check=' + ipb.vars['md5_hash'],"group={$key}"));
						}
						} ).MenuOpen();
						
boo("#Menus").each(function () {
						boo(this).find('.submenus').menusort('subs', {
							url: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=reorder&md5check=' + ipb.vars['md5_hash'],
							serializeOptions: { key: 'menus[]' },
							postUpdateProcess: function() {
            					boo("#preview").html(GenAjaxPost( ipb.vars['front_url'].replace(/&amp;/g, '&') + 'app=promenu&module=preview&section=preview&md5check=' + ipb.vars['md5_hash'],"group={$key}"));
							}
						});
					})
					//boo('html, body').animate({scrollTop: jQ("#menus_{$this->request['id']}").offset().top}, 1000);
				})
			</script>
			<style type="text/css">
		
				.right {
					float: right !important;
                }
				.ipsTooltip.top {
				    background: url({$this->settings['board_url']}/public/style_images/master/stems/tooltip_top.png) no-repeat bottom center;
				}				
				.ipsTooltip {
				    padding: 5px;
				    z-index: 25000;
				}
				.ipsTooltip_inner {
				    padding: 8px;
				    background: #333333;
				    border: 1px solid #333333;
				    color: #fff;
				    -webkit-box-shadow: 0px 2px 4px rgba(0,0,0,0.3), 0px 1px 0px rgba(255,255,255,0.1) inset;
				    -moz-box-shadow: 0px 2px 4px rgba(0,0,0,0.3), 0px 1px 0px rgba(255,255,255,0.1) inset;
				    box-shadow: 0px 2px 4px rgba(0,0,0,0.3), 0px 1px 0px rgba(255,255,255,0.1) inset;
				    -moz-border-radius: 4px;
				    -webkit-border-radius: 4px;
				    border-radius: 4px;
				    font-size: 12px;
				    text-align: center;
				    max-width: 250px;
				}			
				li.openit{
					width: 28px;
					height: 20px;
					display: inline-block;
					background: url({$this->settings['skin_app_url']}images/icon_expand_close.png)no-repeat;
					background-position:center  2px;
					background-color: #E4EEF3;
					background-color: -moz-linear-gradient(top, #FFFFFF 0%, #F4F8FA 1%, #E4EEF3 100%);
					background-color: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#FFFFFF), color-stop(1%,#F4F8FA), color-stop(100%,#E4EEF3)); 
				}

				li.closeit{
					width: 28px;
					height: 20px;
					display: inline-block;
					background: url({$this->settings['skin_app_url']}images/icon_expand_close.png)no-repeat;
					background-position:center -16px;
					background-color: #E4EEF3;
					background-color: -moz-linear-gradient(top, #FFFFFF 0%, #F4F8FA 1%, #E4EEF3 100%);
					background-color: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#FFFFFF), color-stop(1%,#F4F8FA), color-stop(100%,#E4EEF3)); 
				}
				
				li.closeit  a{
					background: transparent !important;
				}
				
				li.openit  a{
					background: transparent !important;
				}
				
				.alt_1{
					background:#D2FFC4 !important;
					border:1px solid #A5FF8A !important;
				}
				
				.alt_2{
					background:#D6F8DE !important;
					border:1px solid #A4F0B7 !important;
				}
				
				.alt_3{
					background:#FFDFEF !important;
					border:1px solid #FF9999 !important;
				}
				
				.alt_1:hover,.alt_2:hover,.alt_3:hover{
					background:#FFE099 !important;
					border:1px solid #FFBE28 !important;
					-webkit-transition: all 0.3s ease-in-out;
					-moz-transition: all 0.3s ease-in-out;					
				}
				
				.ipsControlStrip_more a {
					padding: 1px 0px !important;
					height: 16px;
					background: url({$this->settings['skin_app_url']}images/threelines.png) no-repeat -6px 1px!important;
				}
				
				.copy {
					background-image: url({$this->settings['skin_app_url']}images/copy.png)!important;
					background-repeat: no-repeat  !important;;
					background-position: 8px !important;;
					padding-left: 35px !important;;
				}
				
				/* Container DIV - automatically generated */
				.simply-scroll-container { 
					position: relative;
				}
				
				/* Clip DIV - automatically generated */
				.simply-scroll-clip { 
					position: relative;
					overflow: hidden;
				}
				
				.close-btn {
				    border: 2px solid #c2c2c2;
				    position: relative;
				    padding: 1px 5px;
				    top: 10px;
				    background-color: #23456b;
				    left:-10;
				    border-radius: 20px;
					z-index:1000;
				}
				
				.close-btn a {
				    font-size: 12px;
				    font-weight: bold;
				    color: white;
				    text-decoration: none;
				}
				
				/* UL/OL/DIV - the element that simplyScroll is inited on
				Class name automatically added to element */
				.simply-scroll-list { 
					overflow: hidden;
					margin: 0;
					padding: 0;
					list-style: none;
				}
					
				.simply-scroll-list li {
					padding: 0;
					margin: 0;
					list-style: none;
				}
					
				.simply-scroll-list li img {
					border: none;
					display: block;
				}
				
				/* Custom class modifications - adds to / overrides above
				
				.simply-scroll is default base class */
				
				/* Container DIV */
				.simply-scroll {
					display:inline-block;
					margin-bottom:25px;
					height: 25px;
				}
				
				/* Clip DIV */
				.simply-scroll .simply-scroll-clip {
					height: 25px;
				}
					
				/* Explicitly set height/width of each list item */	
				.simply-scroll .simply-scroll-list li {
					min-width: 100px;
					max-width:500px;
					height: 30px;
				}
				
				.menu_btn {
					padding: 0 10px;
					margin-right: 5px;
					display: inline-block;
					border-radius: 3px;
					background: #FFFFFF;
					background: -moz-linear-gradient(top, #FFFFFF 0%, #F6F9FA 3%, #E4EEF3 100%);
					background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#FFFFFF), color-stop(3%,#F6F9FA), color-stop(100%,#E4EEF3));
					border: 1px solid #afd4f1;
					color: #0b5794;
					text-shadow: 1px 1px 0 #f4f7fa;
					height: 26px;
					line-height: 26px;
					outline: none;
				}
				
				.menu_btn:hover {
					-moz-box-shadow: 0px 0px 2px rgba(0,174,239,0.4);
					-webkit-box-shadow: 0px 0px 2px rgba(0,174,239,0.4);
					box-shadow: 0px 0px 2px rgba(0,174,239,0.4);
					border-color: #73a2c3;
				}
			</style>
			<div class="section_title">
				<h2 style="margin-bottom:5px;">{$title}: {$this->lang->words['promenu_managment']}</h2>
					<div class="simply-scroll simply-scroll-container">

						<div  style="text-align:center;vertical-align:middle;display:inline-block;height: 30px;font-weight:bold;margin-right:5px;">
EOF;
		if (!$this->settings['promenu_checks_api']) {
			$html .="{$this->lang->words['promenu_latest_news']}:";
		}

		$html .=<<<EOF
						</div>
							<div class="simply-scroll-clip" style="display:inline-block;">
								<ul id="scroller" class="simply-scroll-list" style="height: 1375px;margin-top:5px;">
EOF;
		if (count($news) && is_array($news)) {
			foreach ($news as $k5 => $c5) {
				$html .=<<<EOF
									<li>
									<a style="background:transparent;border:0px;" href="{$c5->cjfroggy_news_url}">{$c5->cjfroggy_news_title}</a>
									</li>
EOF;
			}
		}
		$html .=<<<EOF
								</ul>
							</div>
						</div>
				<div style="float:right;height: 30px;display:inline-block;">
					<div  style="display:inline-block;height: 30px;font-weight:bold;margin-right:15px;">{$this->lang->words['promenu_current_version']}: </div><div style="display:inline-block;height: 30px;margin-right:15px;"> {$this->caches['app_cache']['promenu']['app_version']}</div>
EOF;
		if (!$this->settings['promenu_checks_api']) {
			if ($update) {
				$up = explode("|", $update);
				$html .=<<<EOF
					<div style="display:inline-block;height: 30px;"> 
							<a href="{$up[1]}">
								<span class="ipsBadge badge_purple">{$this->lang->words['promenu_update_available']}</span>
							</a>
					</div>
EOF;
			} else {
				$html .= <<<EOF
					<div style="display:inline-block;height:30px;"> 
						<img src="{$this->settings['skin_acp_url']}/images/icons/accept.png">&nbsp;{$this->lang->words['promenu_up_to_date']}
					</div>
EOF;
			}
		}
		$html .=<<<EOF
				</div>
				<ul class="context_menu">
EOF;
		$html .=<<<EOF
					<li>
						<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=addMenu&amp;key={$key}&amp;parent=0&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_add_menu']}">
							<img src="{$this->settings['skin_acp_url']}/images/icons/add.png" alt="">
							{$this->lang->words['promenu_add_menu']}
						</a>
					</li>
EOF;
		$html .=<<<EOF
					<li>
						<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=kerching&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_refresh_cache']}">
							<img src="{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png" >
							{$this->lang->words['promenu_refresh_cache']}
						</a>
					</li>	
					{$this->registry->profunctions->DisplayGroups()}
				
				</ul>
				<script type="text/javascript">
					(function($) {
						$(function() { //on DOM ready
							$("#scroller").simplyScroll({orientation:'vertical', frameRate: 15,speed:1});
						});
					})(jQuery);
				</script>
EOF;
		if ($key != "mobile") {
			$html .=<<<EOF
				<div style="margin-bottom:15px;">
					<script>
						boo(document).ready(function(){
							
							var status = {$group['promenu_groups_preview_close']};
							
							if(status == 0)
							{
								boo("#preview").html(GenAjaxPost( ipb.vars['front_url'].replace(/&amp;/g, '&') + 'app=promenu&module=preview&section=preview&md5check=' + ipb.vars['md5_hash'],"group={$key}")).promise().done(function(){ boo("#preview").show("slide", {direction: "up"}, 1000); });	
							}

							boo("#show_prev").click(function(){
								if(boo(this).data("status") == 1){
									boo(this).data("status",0).html("{$this->lang->words['promenu_word_hide']} {$this->lang->words['promenu_word_previewl']}");
									GenAjaxPost( ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=status&md5check=' + ipb.vars['md5_hash'],"group={$key}&status=0")
									boo("#preview").html(GenAjaxPost( ipb.vars['front_url'].replace(/&amp;/g, '&') + 'app=promenu&module=preview&section=preview&md5check=' + ipb.vars['md5_hash'],"group={$key}")).promise().done(function(){ boo("#preview").show("slide", {direction: "up"}, 1000); });					
								
								}
								else{
									boo(this).data("status",1).html("{$this->lang->words['promenu_word_show']} {$this->lang->words['promenu_word_previewl']}")
									GenAjaxPost( ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=promenu&module=ajax&section=ajax&do=status&md5check=' + ipb.vars['md5_hash'],"group={$key}&status=1")
									boo("#preview").hide("slide", {direction: "down"}, 1000);
								}
							});
						})
					</script>
    				<span class="close-btn">
    					<a id="show_prev" href="#" title="{$status_msg}" data-status="{$group['promenu_groups_preview_close']}">{$status}</a>
    				</span>
    				<div id="preview" style="display:none;"></div>
				</div>
EOF;
		}
		$html .=<<<EOF
				<div class="acp-box" style="background:#EAEEF4;">
					<h3>{$this->lang->words['promenu_word_menus']}</h3>	
EOF;
		if (!count($data)) {
			$html .= <<<EOF
					<table class="ipsTable double_pad"  id="Menus">
						<tr>
							<td>
								{$this->lang->words['promenu_currently_no_menus']}.
							</td>
						</tr>
					</table>
				</div>		
EOF;
		} else {
			$html .= $this->sub_container($data, 1);
		}

		return $html;
	}

	public function sub_container($data, $is = 0) {

		$key = urldecode($this->request['key']) ? urldecode($this->request['key']) : "primary";
		$html = '';
		$root = '';
		$gs = $this->caches['promenu_groups'][$key];

		if (!$is) {
			$html .=<<<EOF
		<div id="submenus" class="submenus" style="border:0px;margin-left:10px;">
EOF;
		} else {
			$html .= <<<EOF
			<div class="ipsExpandable" id="Menus">
EOF;
		}

		$i = 2;

		foreach ($data as $k => $c) {
			$c['promenu_menus_name'] = unserialize($c['promenu_menus_name']);
			foreach ($this->caches['lang_data'] as $kst => $cst) {
				if ($cst['lang_default']) {
					$groupt['lang_default'] = $cst['lang_id'];
					break;
				}
			}

			foreach ($c['promenu_menus_name'] as $ksd => $csd) {
				if ($csd) {
					$default = $csd;
					break;
				}
			}

			$c['promenu_menus_name'] = $c['promenu_menus_name'][$groupt['lang_default']];

			if (!$c['promenu_menus_name']) {
				$c['promenu_menus_name'] = $default;
			}

			if ($i % 2) {
				$root = "alt_1";
			} else {
				$root = "alt_2";
			}

			$unpub = $gs['promenu_groups_group_visibility_enabled'] ? $c['promenu_menus_override'] ? 0 : 1  : strlen($c['perm_view']);

			if (!$unpub) {
				$root = "alt_3";
			}

			$i++;
			if (!$is) {

				$html .= <<<EOF
				<div class="isDraggable isDraggable2" id="menus_{$c['promenu_menus_id']}">
EOF;
			} else {

				$html .= <<<EOF
				<div class="isDraggable isDraggable1" id="menus_{$c['promenu_menus_id']}">
EOF;
			}
			$html .=<<<EOF
				<div id="container" class="item  clearfix ipsControlRow {$root}">
					<div class="col_buttons right">
						<ul class="ipsControlStrip">
EOF;
			if ($c['promenu_menus_has_sub']) {
				if ($c['promenu_menus_is_open']) {
					$html .=<<<EOF
										<li class="closeit">
											<a id="toggle_{$c['promenu_menus_id']}" data-id="{$c['promenu_menus_id']}" data-status="2" href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=status&amp;state=0&amp;id={$c['promenu_menus_id']}&amp;key={$key}&amp;postKey={$this->member->form_hash}">c</a>	
										</li>
EOF;
				} else {
					$html .=<<<EOF
										<li class="openit">
											<a  id="toggle_{$c['promenu_menus_id']}" data-id="{$c['promenu_menus_id']}" data-status="1" href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=status&amp;state=1&amp;id={$c['promenu_menus_id']}&amp;key={$key}&amp;postkey={$this->member->form_hash}">o</a>
										</li>
EOF;
				}
			}

			if ($key != "mobile") {
				$html .=<<<EOF
									<li class="i_add">
										<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=addMenu&amp;key={$key}&amp;parent={$c['promenu_menus_id']}&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_add_sub_menu']}" >{$this->lang->words['promenu_add_sub_menu']}</a>
									</li>
EOF;
			}
			$html .=<<<EOF
									<li class='ipsControlStrip_more ipbmenu' id="menum-{$c['promenu_menus_id']}">
										<a id="tool_{$c['promenu_menus_id']}" href='#' title="{$this->lang->words['promenu_more_options']}">{$this->lang->words['promenu_word_options']}</a>
									</li>
									</ul>
									<ul class='acp-menu' id='menum-{$c['promenu_menus_id']}_menucontent' style='display: none'>
									
EOF;
			if (!$c['promenu_menus_parent_id'] && $key != "mobile") {
				$html .=<<<EOF
									<li class='icon copy'> 
										<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=clone&amp;id={$c['promenu_menus_id']}&amp;key={$key}&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_word_clone']}">{$this->lang->words['promenu_word_clone']}</a>
									</li>
EOF;
			}
			$html .=<<<EOF
							<li class='icon edit'> 
								<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=editMenu&amp;id={$c['promenu_menus_id']}&amp;key={$key}&amp;parent={$c['promenu_menus_parent_id']}" title="{$this->lang->words['promenu_word_edit']}">{$this->lang->words['promenu_word_edit']}</a>
							</li>
							<li class='icon delete'>
								<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=deleteMenus&amp;key={$key}&amp;id={$c['promenu_menus_id']}&amp;pid={$c['promenu_menus_parent_id']}&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_word_delete']}" onclick="return confirm('{$this->lang->words['promenu_delete_alert']}')">{$this->lang->words['promenu_word_delete']}</a>
							</li>
						</ul>
					</div>
EOF;

			if (!$is) {

				$html .= <<<EOF
					<div class="draghandle draghandle2 left"></div>
EOF;
			} else {

				$html .= <<<EOF
						<div class="draghandle draghandle1 left"></div>
EOF;
			}
			$html .=<<<EOF
					<div class="item_info left">
EOF;
			if ($c['promenu_menus_icon_check']) {
				$html .=<<<EOF
							<div class="non_sprite">
								<img 
EOF;
				$html .= 'src="' . $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($c['promenu_menus_icon']))) . '"';
				$html .=<<<EOF
								 width="14px" height="14px"/>
							</div>
EOF;
			} else {
				if ($c['promenu_menus_icon']) {
					$html .= <<<EOF
								<div class="sprites {$c['promenu_menus_icon']}_icon"></div>
EOF;
				}
			}
			$html .=<<<EOF
						<strong style="font-size:1.05em;">
							<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=editMenu&amp;id={$c['promenu_menus_id']}&amp;key={$key}&amp;parent={$c['promenu_menus_parent_id']}&amp;postkey={$this->member->form_hash}" title="{$this->lang->words['promenu_word_edit']}">
							{$c['promenu_menus_name']}
EOF;
			if ($c['promenu_menus_is_mega']) {
				$html .=<<<EOF
					<div style="position:relative;top:-5px;left:0;display:inline-block;">
						<img src="{$this->settings['skin_app_url']}images/mega.png" />
					</div>
EOF;
			}
			$html .=<<<EOF
							</a>
						</strong>
EOF;

			$html .=<<<EOF
					</div>
				</div>
EOF;
			if ($c['promenu_menus_has_sub']) {
				$childData = $this->registry->profunctions->getMenusByParent($c['promenu_menus_id']);
				if ($c['promenu_menus_is_open']) {
					$html .= <<<EOF
							<div id="state_{$c['promenu_menus_id']}" >{$this->sub_container($childData)}</div>		
EOF;
				} else {
					$html .= <<<EOF
							<div id="state_{$c['promenu_menus_id']}" style="display:none;">{$this->sub_container($childData)}</div>
EOF;
				}
			}

			$html .=<<<EOF
				</div>
EOF;
			// <script>
			// jQ("#tool_{$c['promenu_menus_id']}").hover(function(){
			// jQ('#dialog_{$c['promenu_menus_id']}').dialog({
			// modal: false,
			// title: '{$name}',
			// zIndex: 10000,
			// autoOpen: true,
			// minWidth: '400px',
			// minHeight:'400px',
			// resizable: false,
			// show: {effect: "clip",duration: 500, direction: "vertical"},
			// hide: {effect: "clip",duration: 500, direction: "vertical"},
			// draggable: false,
			// position: { my: "center bottom", at: "center bottom", of: jQ(this).parent().parent().parent().parent() }
			// }); 
			// }, function(){jQ('#dialog_{$c['promenu_menus_id']}').dialog("close")});
			// </script>
// EOF;
			// $use_img = $c['promenu_menus_img_as_title_check'] ? "Yes" : "No";
			// $dimensions = '';
			// if($c['promenu_menus_img_as_title_check'])
			// {
			// $dimensions = "<strong>Image Title Dimensions:</strong> width:".$c['promenu_menus_img_as_title_w']." height:".$c['promenu_menus_img_as_title_h'];
			// $dimensions .= "<br><img src='".$c['promenu_menus_title_icon']."' width='".$c['promenu_menus_img_as_title_w']."' height='".$c['promenu_menus_img_as_title_h']."' /><br>";
			// }
// 				
			// if($c['promenu_menus_link_type'] == "app")
			// {
			// $url = "<strong>Application:</strong> ".$c['promenu_menus_app_link']."<br>";
			// }
			// else if($c['promenu_menus_link_type'] == "man"){
			// if($c['promenu_menus_url']){
			// $url = "<strong>URL:</strong> ".$c['promenu_menus_url']."<br>";
			// }
			// }
			// else if($c['promenu_menus_link_type'] == "def"){
			// $url = "<strong>Type:</strong> Non-Clickable<br>";
			// }
			// else{
			// $url = "<strong>Type:</strong> Block<br>";
			// if($c['promenu_menus_url']){
			// $url = "<strong>URL:</strong> ".$c['promenu_menus_url']."<br>";
			// }
			// }
			// $html .=<<<EOF
			// <div id="dialog_{$c['promenu_menus_id']}" style="display:none;">
			// <h3>Quick Details</h3>
			// <strong>Menu ID:</strong>&nbsp;{$c['promenu_menus_id']}<br>
			// <strong>Menu Name:</strong>&nbsp;{$name}<br>
			// <strong>image as title:</strong>&nbsp;{$use_img}<br>
			// {$dimensions}
			// {$url}
			// </div>
// 					
// EOF;
		}

		$html .=<<<EOF
		</div>
EOF;
		return $html;
	}

	public function containers($items = array(), $sidebar) {

		if ($items) {
			$key = $items['promenu_menus_group'];
		} else {
			$key = urldecode($this->request['key']) ? urldecode($this->request['key']) : "primary";
		}

		$group = $this->caches['promenu_groups'][$key];

		$html = '';

		$html .= $this->jQueryInit();

		$html .= <<<EOF
			<link rel="stylesheet" href="{$this->settings['js_app_url']}cm/lib/codemirror.css">		
			<script src="{$this->settings['js_app_url']}acp.promenuplus.js"></script>
			<script src="{$this->settings['js_app_url']}cm/lib/codemirror.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/addon/hint/show-hint.js"></script>
		    <link rel="stylesheet" href="{$this->settings['js_app_url']}cm/addon/hint/show-hint.css">
		    <script src="{$this->settings['js_app_url']}cm/addon/edit/closetag.js"></script>		    
		    <script src="{$this->settings['js_app_url']}cm/addon/selection/active-line.js""></script>
		    <script src="{$this->settings['js_app_url']}cm/addon/hint/html-hint.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/mode/xml/xml.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/mode/javascript/javascript.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/mode/css/css.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/mode/htmlmixed/htmlmixed.js"></script>
		    <script src="{$this->settings['js_app_url']}cm/addon/edit/matchbrackets.js"></script>
    		<script src="{$this->settings['js_app_url']}cm/mode/clike/clike.js"></script>
    		<script src="{$this->settings['js_app_url']}cm/mode/php/php.js"></script>	
    		
			<script src="{$this->settings['js_app_url']}cm/addon/search/searchcursor.js"></script>
			<script src="{$this->settings['js_app_url']}cm/addon/search/match-highlighter.js"></script>	    
			<style>
				.CodeMirror {border: 1px solid black;}
				.CodeMirror-activeline-background {background: #e8f2ff !important;}
				.CodeMirror-focused .cm-matchhighlight {
			        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAFklEQVQI12NgYGBgkKzc8x9CMDAwAAAmhwSbidEoSQAAAABJRU5ErkJggg==);
			        background-position: bottom;
			        background-repeat: repeat-x;
			      }
			</style>						
			<script>
				boo(document).ready(function(){
					boo('#adminform').linkTypeview();
					loadfields();
					url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
					boo("#section_navigation").find("a").each(function(){
						if(boo(this).attr('href') == url)
						{
							boo(this).parent().append("{$sidebar}");
						}
					})					
				});
			</script>
			<form id='adminform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=save&amp;postkey={$this->member->form_hash}'>
EOF;

		if($key === "mobile")
		{
			$html .= "<input type='hidden' name='promenu_menus_parent_id' value='0'/>";
		}
		if (count($items)) {
			$html .= "<input type='hidden' name='promenu_menus_id' value='" . $items['promenu_menus_id'] . "' >";
		}

		if (!count($items)) {
			$html .= <<<EOF
			<div class="acp-box">
				<h3>{$this->lang->words['promenu_add_new_menu_item']}</h3>
EOF;
		} else {
			$html .= <<<EOF
			<div class="acp-box">
				<h3>{$this->lang->words['promenu_skin_menus_edit_menu']}</h3>
EOF;
		}
		$html .=<<<EOF
				<div class='ipsTabBar with_left with_right' id='tabstrip_add'>
					<span class='tab_left'>&laquo;</span>
					<span class='tab_right'>&raquo;</span>
					<ul>
						<li id='tab_1'>
							{$this->lang->words['promenu_basic_settings']}
						</li>
EOF;
		$html .=<<<EOF
							<li id='tab_2'>
								{$this->lang->words['promenu_word_visibility']}
							</li>
EOF;
		if ($group['promenu_groups_template'] == "proMain" && $key != "mobile") {

			if (!$this->request['parent']) {
				$html .=<<<EOF
							<li id='tab_3'>
								{$this->lang->words['promenu_mega_menu_settings']}
							</li>
EOF;
			}
		}
		$html .=<<<EOF
					</ul>
				</div>
			</div>	
			<div class='ipsTabBar_content' id='tabstrip_add_content'>
			<!-- Navigation Item Settings -->
			<div id='tab_1_content'>
EOF;
		$html .= $this->tab1($items);

		$html .= <<<EOF
		</div>
		<div id='tab_2_content'>
EOF;

		$html .= $this->tab2($items);

		$html .= <<<EOF
		</div>
		<div id='tab_3_content'>
				
EOF;
		$html .= $this->tab3($items);

		$html .=<<<EOF
				</div>
				</div>
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_save']}" />
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_save_reload']}" name='return' />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>		
		</form>
EOF;
		$html .= <<<EOF
		<script type='text/javascript'>
			jQ("#tabstrip_add").ipsTabBar({ tabWrap: "#tabstrip_add_content" });
		</script>
EOF;
		return $html;
	}

	protected function tab1($items = array()) {

		if ($items) {
			$key = $items['promenu_menus_group'];
		} else {
			$key = urldecode($this->request['key']) ? urldecode($this->request['key']) : "primary";
		}

		if ($items['promenu_menus_attr']) {
			$attr = unserialize($items['promenu_menus_attr']);

			if ($attr['class']) {
				$attribute .= ' class="' . $attr['class'] . '"';
			}
			if ($attr['style']) {
				$attribute .= ' style="' . $attr['style'] . '" ';
			}
			if ($attr['attr']) {
				$attribute .= $attr['attr'];
			}
		}

		$groups = $this->registry->profunctions->buildGroups();

		unset($groups['mobile']);

		$html .= <<<EOF
		<table id='catItem_table' class='ipsTable'>
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menu_name']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->multilang($key, $items['promenu_menus_name'])}
				</td>
			</tr>
EOF;
		if ($key != "mobile") {
			$html .=<<<EOF
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_title_image']}</strong></td>
				<td class='field_field'>
					<span id="img_title" style="display:none;">
						{$this->lang->words['promenu_word_url']}:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_title_icon', $items['promenu_menus_title_icon'])}
						<span class='desctext'>{$this->lang->words['promenu_url_to_image']}.</span>
						<br>
						{$this->lang->words['promenu_word_width']}:&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_img_as_title_w', $items['promenu_menus_img_as_title_w'] ? $items['promenu_menus_img_as_title_w'] : 14, '', '', '', '', '', 10)}
						<span class='desctext'>{$this->lang->words['promenu_width_of_image']}.</span>
						<br>
						{$this->lang->words['promenu_word_height']}:&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_img_as_title_h', $items['promenu_menus_img_as_title_h'] ? $items['promenu_menus_img_as_title_h'] : 14, '', '', '', '', '', 10)}
						<span class='desctext'>{$this->lang->words['promenu_height_of_image']}.</span>
						<br>
					</span>
						{$this->registry->output->formcheckbox('promenu_menus_img_as_title_check', $items['promenu_menus_img_as_title_check'] ? 1 : 0)} {$this->lang->words['promenu_use_image_as_title']}?<br><span class='desctext'>{$this->lang->words['promenu_if_checked_alert']}.</span>
					<br />
				</td>
			</tr>
EOF;

			$html .=<<<EOF
			<tr class='ipsControlRow' id="attributes">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_descriptions']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->multilangdesc($key, $items['promenu_menus_desc'])}<br />
				<span class='desctext'></span></td>
			</tr>
EOF;
		}
		$html .=<<<EOF
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_icon']}</strong></td>
				<td class='field_field'>
					<span id="dropdown_icon">{$this->registry->output->formDropdown("promenu_menus_icon", $this->registry->profunctions->readDefaultIcons(), $items['promenu_menus_icon'])}</span>
					<span id="img_img" style="display:none;">
						{$this->lang->words['promenu_word_url']}:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_icon_custom', $items['promenu_menus_icon'])}
						<br>
						{$this->lang->words['promenu_word_width']}:&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_icon_w', $items['promenu_menus_icon_w'] ? $items['promenu_menus_icon_w'] : 14, '', '', '', '', '', 10)}<br>
						{$this->lang->words['promenu_word_height']}:&nbsp;&nbsp;{$this->registry->output->formInput('promenu_menus_icon_h', $items['promenu_menus_icon_h'] ? $items['promenu_menus_icon_h'] : 14, '', '', '', '', '', 10)}
					</span>
					&nbsp;
					{$this->registry->output->formcheckbox('promenu_menus_icon_check', $items['promenu_menus_icon_check'] ? 1 : 0)} {$this->lang->words['promenu_word_custom']} {$this->lang->words['promenu_word_icon']}
				<br />
				<span class='desctext'>{$this->lang->words['promenu_icon_placement_alert']}.</span></td>
			</tr>
EOF;
		$html .=<<<EOF
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menu_group']}</strong></td>
				<td class='field_field'>
EOF;
		if (!$this->request['parent'] && $key != "mobile") {
			$html .= $this->registry->output->formDropdown("promenu_menus_group", $groups, $items['promenu_menus_group'] ? $items['promenu_menus_group'] : $key);
		} else {
			$group = $items['promenu_menus_group'] ? $items['promenu_menus_group'] : $key;
			$html .= "<span>" . $group . "</span>";
			$html .= '<input type="hidden" name="promenu_menus_group" value="' . $group . '"/>';
		}
		$html .=<<<EOF
				<br />
				<span class='desctext'>{$this->lang->words['promenu_which_group']}?</span></td>
			</tr>
EOF;
		if ($key != "mobile") {
			$html .=<<<EOF
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_parent_menu']}</strong></td>
				<td class='field_field'>
					{$this->registry->profunctions->buildParentData('promenu_menus_parent_id', $items['promenu_menus_id'], intval($this->request['parent']))}
					<input type="hidden" name="current_parent_id" value="{$this->request['parent']}"/>
					<br />
				<span class='desctext'>{$this->lang->words['promenu_parent_menu']}</span></td>
			</tr>
EOF;
			$html .=<<<EOF
			<tr class='ipsControlRow' id="attributes">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_attributes']}</strong></td>
				<td class='field_field'>{$this->registry->output->formTextarea('promenu_menus_attr', $attribute)}<br />
				<span class='desctext'>{$this->lang->words['promenu_attribute_desc']}</span></td>
			</tr>
EOF;
		}
		$html .=<<<EOF
			<tr class='ipsControlRow'>
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_link_type']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->LinkType($items['promenu_menus_link_type'], $key)}<br />
				<span class='desctext'>{$this->lang->words['promenu_link_type_desc']}.</span></td>
			</tr>
EOF;

		$html .=<<<EOF
			<tr class='ipsControlRow' style="display:none;" id="surl">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_url']}</strong></td>
				<td class='field_field'>{$this->registry->output->formInput('promenu_menus_url', $items['promenu_menus_url'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_url_desc']}!</span></td>
			</tr>
			<tr class='ipsControlRow'  style="display:none;" id="app">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_app_link']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->AppLink($items['promenu_menus_app_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_app_link_desc']}.</span></td>
			</tr>
EOF;
		if(!$this->request['parent'] && $key != "mobile"){
			$html .=<<<EOF
			<tr class='ipsControlRow' style="display:none;" id="menu_activation">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_nonpromenu_activation']}</strong></td>
				<td class='field_field'>
				<span id="name_title">{$this->registry->output->formYesNo('promenu_menus_by_url', $items['promenu_menus_by_url'])}</span>
				<br />
				<span class='desctext'>{$this->lang->words['promenu_enable_menu_active_desc2']}.</span></td>
			</tr>	
EOF;
		}
		if ($key != "mobile") {
			
		$html .= <<<EOF
			<tr class='ipsControlRow' style="display:none;" id="blocks">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_block_code']}</strong></td>
				<td class='field_field'>{$this->registry->output->formTextarea('promenu_menus_block', $items['promenu_menus_block'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_block_desc']}</span></td>
			</tr>
			<tr class='ipsControlRow' style="display:none;" id="phpBlock">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_block_code']}</strong></td>
				<td class='field_field'>{$this->registry->output->formTextarea('promenu_menus_pblock', "<?php\n\n".$items['promenu_menus_block']."\n\n?>")}<br />
				<span class='desctext'>{$this->lang->words['promenu_block_desc']}</span></td>
			</tr>		
			<tr class='ipsControlRow' style="display:none;" id="forum_feature">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_attach_forums']}?</strong></td>
				<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_forums_attatch', $items['promenu_menus_forums_attatch'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_attach_forums_desc']}.</span></td>
			</tr>	
EOF;
		}
		
		if (IPSLib::appIsInstalled('ccs') ) {
			$html .= <<<EOF
			<tr class='ipsControlRow'  style="display:none;" id="content_page">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_content_page']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->contentPages($items['promenu_menus_content_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_content_page_desc']}.</span></td>
			</tr>
EOF;
			if($this->registry->profunctions->proPlus === true){
				$html .= $this->registry->proPlusSkin->skinCCS($items);
			}
		}
		if (IPSLib::appIsInstalled('easypages') ) {
			$html .=<<<EOF
			<tr class='ipsControlRow'  style="display:none;" id="easypages_page">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_easypages_page']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->getEasyPages($items['promenu_menus_easy_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_easypages_page_desc']}.</span></td>
			</tr>
EOF;
			if($this->registry->profunctions->proPlus === true){
				$html .= $this->registry->proPlusSkin->skinEasyPages($items);
			}
		}

		$html .=<<<EOF
		</table>

EOF;
		return $html;
	}

	protected function tab2($items = array()) {
		$group = $this->caches['promenu_groups'][$this->request['key']];

		if ($group['promenu_groups_group_visibility_enabled']) {
			$html .= <<<EOF
			<table id='menuItem_table' class='ipsTable'>
				<tr id='group-show-all' class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_hide_all']}?</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_override', $items['promenu_menus_override'])}<br />
					<span class='desctext'>{$this->lang->words['promenu_hide_all_desc']}</span></td>
				</tr>
				<tr id='group-show' class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_hide_selected']}...</strong></td>
					
					<td class='field_field'>{$this->registry->output->generateGroupDropdown('promenu_menus_view[]', explode(',', $items['promenu_menus_view']), $multiselect = TRUE, 'promenu_menus_view')}<br />
					<span class='desctext'>{$this->lang->words['promenu_hide_selected_desc']}</span></td>
				</tr>
			</table>
EOF;
		} else {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

			$perms = new $classToLoad($this->registry);

			$perm = $items ? $items : 0;

			$html .= $perms->getPermissionsMatrix($perm);
		}
		return $html;
	}

	protected function tab3($items = array()) {

		$html .= <<<EOF
				<table id='menuItem_table' class='ipsTable'>
					<tr id='group-show-all' class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_mega_menu']}?</strong></td>
						<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_is_mega', $items['promenu_menus_is_mega'] ? $items['promenu_menus_is_mega'] : 0)}<br />
						<span class='desctext'>{$this->lang->words['promenu_mega_menu_desc']}</span></td>
					</tr>
					<tr id='group-show' class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_number_columns']}?</strong></td>
						<td class='field_field'>{$this->registry->output->formInput('promenu_menus_mega_column_count', $items['promenu_menus_mega_column_count'] ? $items['promenu_menus_mega_column_count'] : 3, '', '', '', '', '', 2)}<br />
						<span class='desctext'>{$this->lang->words['promenu_number_columns_desc']}</span></td>
					</tr>
				</table>
EOF;

		return $html;
	}

	public function clone_wars($id, $sidebar) {
		$c = $this->registry->profunctions->getSingleMenu($id);
		$group = $this->registry->profunctions->buildGroups();
		unset($group['mobile']);

		$html .= $this->jQueryInit();

		$html .=<<<EOF
		<script src="{$this->settings['js_app_url']}acp.promenuplus.js"></script>
		<script>
			boo(document).ready(function(){
				url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
				boo("#section_navigation").find("a").each(function(){
					if(boo(this).attr('href') == url)
					{
						boo(this).parent().append("{$sidebar}");
					}
				})					
			});
		</script>
		<form id='menuform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=clones&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="old_key" value="{$c['promenu_menus_group']}"/>
			<input type="hidden" name="current_id" value="{$c['promenu_menus_id']}"/>
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>Clone</h3>
				<table class='ipsTable'>
					<tr class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_clone_to_which_group']}?</strong></td>
						<td class='field_field'>
							<span id="name_title">{$this->registry->output->formDropdown("new_key", $group, '')}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_clone_to_which_group_desc']}.</span></td>
					</tr>
				</table>
			</div>
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_save']}" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		return $html;
	}

	public function group_settings($group = array(), $sidebar = '') {
		$title = $group['promenu_groups_name'] ? "{$this->lang->words['promenu_editing_group']}: " . $group['promenu_groups_name'] : "{$this->lang->words['promenu_add_group']}";

		$behavoir[] = array("1", "{$this->lang->words['promenu_hover_default']}");
		$behavoir[] = array("2", "{$this->lang->words['promenu_click_hover']}");
		$behavoir[] = array("3", "{$this->lang->words['promenu_click_only']}");

		$show[] = array("show", "{$this->lang->words['promenu_show_default']}");
		$show[] = array("fade", "{$this->lang->words['promenu_fade_in']}");
		$show[] = array("slide", "{$this->lang->words['promenu_slide_down']}");
		// $show[] = array("slideUI", "Slide Down (jQuery UI)");
		// $show[] = array("blind", "Blind (jQuery UI)");
		// $show[] = array("clip", "Clip (jQuery UI)");
		// $show[] = array("drop", "Drop (jQuery UI)");
		// $show[] = array("explode", "Explode (jQuery UI)");
		// $show[] = array("fold", "Fold (jQuery UI)");
		// $show[] = array("puff", "Puff (jQuery UI)");

		$hide[] = array("hide", "{$this->lang->words['promenu_hide_default']}");
		$hide[] = array("fade", "{$this->lang->words['promenu_fade_out']}");
		$hide[] = array("slide", "{$this->lang->words['promenu_slide_up']}");
		// $hide[] = array("slideUI", "Slide Up (jQuery UI)");
		// $hide[] = array("blind", "Blind (jQuery UI)");
		// $hide[] = array("clip", "Clip (jQuery UI)");
		// $hide[] = array("drop", "Drop (jQuery UI)");
		// $hide[] = array("explode", "Explode (jQuery UI)");
		// $hide[] = array("fold", "Fold (jQuery UI)");
		// $hide[] = array("puff", "Puff (jQuery UI)");

		$d = explode(",", "20={$this->lang->words['promenu_fly_apart']},100={$this->lang->words['promenu_really_fast']},200={$this->lang->words['promenu_default_fast']},400={$this->lang->words['promenu_word_medium']},600={$this->lang->words['promenu_word_slow']},800={$this->lang->words['promenu_word_slower']},1000={$this->lang->words['promenu_really_slow']},1500={$this->lang->words['promenu_blue_hair_pass']},2000={$this->lang->words['promenu_riot_slow']}");

		foreach ($d as $k => $c) {
			if ($c) {
				$c = explode("=", $c);

				$downtime[] = array($c[0], $c[1]);
			}
		}

		$down = $group['promenu_groups_speed_open'] ? $group['promenu_groups_speed_open'] : "200";

		$up = $group['promenu_groups_speed_close'] ? $group['promenu_groups_speed_close'] : "200";

		$zz = $group['promenu_groups_zindex'] ? $group['promenu_groups_zindex'] : "10000";

		$top = $group['promenu_groups_top_offset'] ? $group['promenu_groups_top_offset'] : "10";

		$langer = $group['promenu_groups_enable_alternate_lang_strings'] ? $group['promenu_groups_enable_alternate_lang_strings'] : "0";

		$enableEffects = $group['promenu_groups_allow_effects'] ? $group['promenu_groups_allow_effects'] : 0;

		$speed = $group['promenu_groups_hover_speed'] ? $group['promenu_groups_hover_speed'] : "500";

		$templates[] = array("proMain", "ProMain");

		$templates[] = array("proOther", "ProOther");

		$html .= $this->jQueryInit();

		$html .=<<<EOF
		<script src="{$this->settings['js_app_url']}acp.promenuplus.js"></script>
		<script>
			boo(document).ready(function(){
				boo("#groupform").isNumerical();
					url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
					boo("#section_navigation").find("a").each(function(){
					if(boo(this).attr('href') == url)
					{
						boo(this).parent().append("{$sidebar}");
					}
				})
				
				var template = "{$group['promenu_groups_template']}";
				var group 	= "{$group['promenu_groups_name']}";
				
				if(group == "mobile")
				{
					boo(".mobiles").hide();
				}
				
				if(template == "proOther")
				{
					boo(".noMain").hide();
				}
				
				boo("#promenu_groups_template").change(function(){
					if( boo(this).val() == "proMain")
					{
						boo(".noMain").fadeIn();
					}
					else{
						boo(".noMain").fadeOut();
					}
				})
			});
		</script>
		<style>
		.err,.errs{
			background:#FFDFEF !important;
		}
		</style>
		<form id='groupform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=groupSettings&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="promenu_groups_allow_effects" value="{$enableEffects}">
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>{$title}</h3>
				<table class='ipsTable'>
EOF;

		if ($group) {
			$disabled = 'readonly="readonly"';
		}

		$html .=<<<EOF
				<tr class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_group_name']}</strong></td>
					<td class='field_field'>
					<span id="name_title">
						<input type="" name="promenu_groups_name" value="{$group['promenu_groups_name']}" size="30" class="input_text" {$disabled} maxlength="75">
						<input type="hidden" name="original_key" value="{$group['promenu_groups_name']}"/>
					</span>
EOF;
		if ($group) {
			$html .=<<<EOF
					<br />
					<span class='desctext'>{$this->lang->words['promenu_group_name_desc']}</span>
EOF;
		} else {
			$html .=<<<EOF
					<br />
					<span class='desctext'>{$this->lang->words['promenu_group_name_desc_new']}</span>
EOF;
		}
		$html .=<<<EOF
					    </td>
				</tr>
EOF;
		if (!$group['promenu_groups_static']) {
			$html .=<<<EOF
				<tr class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_group_template']}</strong></td>
					<td class='field_field'>
					<span id="name_title">
						{$this->registry->output->formDropdown('promenu_groups_template', $templates, $group['promenu_groups_template'])}
					</span>
					<br />
					<span class='desctext'>{$this->lang->words['promenu_group_template_desc']}.</span></td>
				</tr>
EOF;
		} else {
			$html .=<<<EOF
			<input type="hidden" name="promenu_groups_template" value="{$group['promenu_groups_template']}">
EOF;
		}
		if ($group['promenu_groups_static'] == 80000 && $group) {
			$html .=<<<EOF
				<tr class='ipsControlRow mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_remote_use']}</strong></td>
					<td class='field_field'>
					<span id="name_title">
						&lt;script type='text/javascript' src='{$this->settings['board_url']}//index.php?app=promenu&amp;module=preview&amp;section=preview&amp;do=menu&amp;group={$group['promenu_groups_name']}&amp;check={$group['promenu_groups_hash']}'&gt;&lt;/script&gt;
					</span>
					<br />
					<span class='desctext'>{$this->lang->words['promenu_remote_use_desc']}.</span></td>
				</tr>
EOF;
		}
		$html .=<<<EOF
				<tr class='ipsControlRow noMain mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_promenu_is_vertical']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_is_vertical', $group['promenu_groups_is_vertical'] ? $group['promenu_groups_is_vertical'] : 0)}<br />
					<span class='desctext'>{$this->lang->words['promenu_promenu_is_vertical_desc']}?</span></td>
				</tr>		
				<tr class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_enable_group']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_enabled', $group['promenu_groups_enabled'])}<br />
					<span class='desctext'>{$this->lang->words['promenu_group_enabled_desc']}</span></td>
				</tr>
				<tr class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_enable_group_visibility']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_group_visibility_enabled', $group['promenu_groups_group_visibility_enabled'])}<br />
					<span class='desctext'>{$this->lang->words['promenu_visibility_enabled_desc']}</span></td>
				</tr>					
				<tr class='ipsControlRow'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_enable_alt_lang_string']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_enable_alternate_lang_strings', $langer)}<br />
					<span class='desctext'>{$this->lang->words['promenu_enable_alt_lang_string']}.</span></td>
				</tr>
EOF;

		$html .=<<<EOF
				<tr class='ipsControlRow noMain mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_promenu_activation']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_tab_activation', $group['promenu_groups_tab_activation'])}<br />
					<span class='desctext'>{$this->lang->words['promenu_enable_menu_active_desc']}?</span></td>
				</tr>
				<tr class='ipsControlRow  noMain mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_nonpromenu_activation']}</strong></td>
					<td class='field_field'>
					<span id="name_title">{$this->registry->output->formYesNo('promenu_groups_by_url', $group['promenu_groups_by_url'])}</span>
					<br />
					<span class='desctext'>{$this->lang->words['promenu_enable_menu_active_desc2']}.</span></td>
				</tr>	
EOF;
					
		$html .=<<<EOF
				<tr class='ipsControlRow mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_hide_on_skin']}?</strong></td>
					<td class='field_field'>{$this->registry->output->formMultiDropdown("promenu_groups_hide_skin[]", $this->registry->output->generateSkinDropdown(), explode(",", $group['promenu_groups_hide_skin']))}<br />
					<span class='desctext'>{$this->lang->words['promenu_hide_on_skin_desc']}.</span></td>
				</tr>
EOF;

		$html .=<<<EOF
				<tr class='ipsControlRow  noMain mobiles'>
					<td class='field_title'><strong class='title'>{$this->lang->words['promenu_enable_arrows']}</strong></td>
					<td class='field_field'>{$this->registry->output->formYesNo('promenu_groups_arrows_enabled', $group['promenu_groups_arrows_enabled'])}<br />
						<span class='desctext'>
							{$this->lang->words['promenu_enable_arrows_desc']}.
						</span>
					</td>
				</tr>
EOF;

		$html .=<<<EOF
					<tr class='ipsControlRow  noMain mobiles'>
						<th class="subhead" style="width: 78%" colspan="2"><span class="larger_text">JavaScript Settings</span></th>
					</tr>
					<tr class='ipsControlRow  noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_behavior']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_behavoir", $behavoir, $group['promenu_groups_behavoir'])}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_behavior_desc']}. </span></td>
					</tr>
EOF;
		$stackem = array( array('0', "Stack them"), array('1', "Step Them"));
		$html .=<<<EOF
					<tr class='ipsControlRow  noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_promenu_border_jumper']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_border", $stackem, $group['promenu_groups_border'] ? $group['promenu_groups_border'] : 0)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_promenu_border_jumper_desc']}. </span></td>
					</tr>
EOF;
		$html .=<<<EOF
					<tr class='ipsControlRow noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_speed_of_animation']}: {$this->lang->words['promenu_word_showing']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_speed_open", $downtime, $down)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_speed_of_open_desc']}.</span></td>
					</tr>
					<tr class='ipsControlRow noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_speed_of_animation']}: {$this->lang->words['promenu_word_hiding']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_speed_close", $downtime, $up)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_speed_of_close_desc']}.</span></td>
					</tr>
					<tr class='ipsControlRow noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_animation']}: {$this->lang->words['promenu_word_showing']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_animation_open", $show, $group['promenu_groups_animation_open'])}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_opening_animation']}.</span></td>
					</tr>
					<tr class='ipsControlRow noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_animation']}: {$this->lang->words['promenu_word_hiding']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formDropdown("promenu_groups_animation_close", $hide, $group['promenu_groups_animation_close'])}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_closing_animation']}.</span></td>
					</tr>
					<tr class='ipsControlRow  noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_hover']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formInput('promenu_groups_hover_speed', $speed, '', '', '', '', '', 75)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_hover_speed_desc']}.</span></td>
					</tr>
					<tr class='ipsControlRow  noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_top_offset_limit']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formInput('promenu_groups_top_offset', $top, '', '', '', '', '', 75)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_top_offset_limit_desc']}.</span></td>
					</tr>	
EOF;

		$html .=<<<EOF
					<tr class='ipsControlRow  noMain mobiles'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_index_limit']}</strong></td>
						<td class='field_field'>
						<span id="name_title">{$this->registry->output->formInput('promenu_groups_zindex', $zz, '', '', '', '', '', 75)}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_index_limit_desc']}.</span></td>
					</tr>					
EOF;
		$html .=<<<EOF
				</table>
				<div class='acp-actionbar'>
					<input type="hidden" id="vlds" value="0">
EOF;
		if(!$group)
		{			
			$html .=<<<EOF
			<input type='submit' id="subMitter" class='button primary' value="{$this->lang->words['promenu_word_save']}" />
EOF;
		}
		else{
			$html .=<<<EOF
			<input type='submit' id="subMitter" class='button primary' value="{$this->lang->words['promenu_word_update']}" />
EOF;
		}
		$html .=<<<EOF
				</div>
			</div>
		</form>
EOF;

		return $html;
	}

}