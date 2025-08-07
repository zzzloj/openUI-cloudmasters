<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_promenu_ajax_ajax extends ipsAjaxCommand {

	/**
	 * 
	 * @param ipsRegistry $registry
	 */
	public function doExecute(ipsRegistry $registry) {
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		switch ($this->request['do']) {
			case 'state':
				$this->state();
				break;
			case 'reorder':
				$this->reorder();
				break;
			case 'status':
				$this->status();
				break;
			case 'getLinkType':
				$this->getLinkType();
				break;
			case 'getApplink':
				$this->getAppListData();
				break;
			case 'css':
				$this->css();
				break;
			case 'deleteMenus':
				$this->deleteMenus();
				break;
			case 'move':
				$this->move();
				break;
			case 'cloneGroup':
				$this->cloneGroup();
				break;
			case 'cloneSingle':
				$this->clone_wars();
				break;
			case 'importApps':
				$this->importApps();
				break;
			case 'newsUpdate':
				$this->newsUpdate();
				break;
			case 'phpVer':
				$this->phpVer();
				break;
			case 'checkPage':
				$this->checkPage();
				break;
			case 'addForums' :
				$this->addForums();
				break;
		}
	}
	
	public function addForums(){
		$key = $this->request['key'] ? $this->request['key'] : "primary";
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );
						
		$aff = new $classToLoad( $this->registry );
		$aff->forumsInit();
		$dropdown = $aff->adForumsForumList(1);
		
		$html .=<<<EOF
		<form id='menuform' method='post' action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=addForums&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="group" value="{$key}">
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>Forums Add</h3>
				<table class='ipsTable'>
					<tr class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_move_to_which_group']}</strong></td>
						<td class='field_field'>
							<span id="name_title">{$this->registry->output->formDropdown("new_key", $dropdown, '')}</span>
						<br />
						<span class='desctext'>select a forum to build a menu for.</span></td>
					</tr>
				</table>
			</div>
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="Add" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		$this->returnHtml($html);
	}
	public function checkPage(){
		if (!$this->registry->isClassLoaded('ccsFunctions')) {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
			$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
		}
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->request['db'] );

		if( !$url OR $url == '#' )
		{
			$this->returnHtml("error");
		}
	}
	
	protected function phpVer(){
		$phpIt = $_POST['data'];
		$phpIt = $this->registry->profunctions->replaceFirst($phpIt, "<?php", "");
		$phpIt = $this->registry->profunctions->replaceFirst($phpIt, "<?", "");
		$phpIt = $this->registry->profunctions->replaceLast("?>","",$phpIt);
		$phpIt = trim($phpIt);
		if(!$phpIt){
			$this->returnHtml("0");
		}
		$asdeqwerewrasdfasd = $this->registry->proPlus->Iteval($phpIt);
		if(!$asdeqwerewrasdfasd){
			$this->returnHtml("1");
		}
		
		$this->returnHtml("2");
	}
	
	protected function newsUpdate(){
		$news = $this->registry->profunctions->gather_news();
		$app = $this->cache->GetCache('app_cache');
		$update = $this->registry->profunctions->get_update($app);		
		$html .=<<<EOF
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>{$this->lang->words['promenu_information']}</h3>
				<table class='ipsTable'>
					<tr class='ipsControlRow'>
						<td class='field_field'>
						<div style="font-weight:bold;font-size:12px;">Site: {$this->settings['board_url']}</div>
EOF;
			if (!$this->settings['promenu_checks_api']) {
				$html .=<<<EOF
					<div style="font-weight:bold;font-size:18px;">
						{$this->lang->words['promenu_latest_news']}:
					</div>
EOF;
				if (count($news) && is_array($news)) {
					foreach ($news as $k5 => $c5) {
						$html .=<<<EOF
							<a style="background:transparent;border:0px;" href="{$c5->cjfroggy_news_url}">{$c5->cjfroggy_news_title}</a><br>
EOF;
					}
				}
				$html .=<<<EOF
				<div style="float:right;height: 30px;display:inline-block;">
					<div  style="display:inline-block;height: 30px;font-weight:bold;margin-right:15px;">{$this->lang->words['promenu_current_version']}: </div><div style="display:inline-block;height: 30px;margin-right:15px;"> {$this->caches['app_cache']['promenu']['app_version']}</div>
EOF;
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
			else{
				$html .= $this->lang->words['promenu_information_disabled'];
			}
		$html .=<<<EOF
						</td>
					</tr>
				</table>
			</div>
EOF;

		$this->returnHtml($html);		
	}
	
	protected function importApps(){
		$html .=<<<EOF
		<form id='menuform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=importApps&amp;&amp;postkey={$this->member->form_hash}'>

			<div class="acp-box" style="background:#EAEEF4;">
				<h3>{$this->lang->words['promenu_import_menus']}</h3>
				<table class='ipsTable'>
					<tr class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menus_import_all']}</strong></td>
						<td class='field_field'>
							<span id="name_title">
								<input type="hidden" name="key" value="{$this->request['key']}" />
								{$this->registry->output->formYesNo('importAll',0)}
							</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_menus_import_all_desc']}.</span></td>
					</tr>
				</table>
			</div>
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_import_menus']}" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		$this->returnHtml($html);
	}
	
	protected function clone_wars() {
		$c = $this->registry->profunctions->getSingleMenu(intval($this->request['id']));
		$group = $this->registry->profunctions->buildGroups();

		$html .=<<<EOF
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
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_clone']}" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		$this->returnHtml($html);
	}

	protected function cloneGroup() {
		$group = $this->registry->profunctions->buildGroups();
		unset($group[$this->request['key']]);
		$html .=<<<EOF
		<form id='menuform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=cloneGroup&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="old_key" value="{$this->request['key']}"/>
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>Clone Group</h3>
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
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_clone']}" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		$this->returnHtml($html);
	}	

	protected function move(){
		$key = $this->request['key'];
		$ids = $this->request['id'];
		$group = $this->registry->profunctions->buildGroups();
		unset($group[$key]);

		$html .=<<<EOF
		<form id='menuform' method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=move&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="old_key" value="{$key}"/>
EOF;
		if(count($ids) && is_array($ids)){
			foreach($ids as $k => $v){
				$d = explode(":",$v);
				$id = $d[0];
				$pid = $d[1];
				if(intval($id)){
					$html .=<<<EOF
					<input type="hidden" name="id[]" value="{$id}" />			
EOF;
				}	
			}
		}
		$html .=<<<EOF
			<div class="acp-box" style="background:#EAEEF4;">
				<h3>{$this->lang->words['promenu_word_move']}</h3>
				<table class='ipsTable'>
					<tr class='ipsControlRow'>
						<td class='field_title'><strong class='title'>{$this->lang->words['promenu_move_to_which_group']}</strong></td>
						<td class='field_field'>
							<span id="name_title">{$this->registry->output->formDropdown("new_key", $group, '')}</span>
						<br />
						<span class='desctext'>{$this->lang->words['promenu_move_to_which_group_desc']}.</span></td>
					</tr>
				</table>
			</div>
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_move']}" />
				<input type='submit' class='button redbutton' name='cancel'  value="{$this->lang->words['promenu_word_cancel']}" />
			</div>
		</form>
EOF;

		$this->returnHtml($html);
	}
	
	/**
	 * deleteMenus
	 * deletes menu items, both parent and subs
	 * @return @e void
	 */
	protected function deleteMenus() {

		$ids = $this->request['id'];
		if(count($ids) && is_array($ids)){
			foreach($ids as $k => $v){
				$d = explode(":",$v);
				$id = $d[0];
				$pid = $d[1];
				
				if( intval( $id ) ){
					
					$del = $this->registry->profunctions->gatherIdForDel($id);
			
					if (count($del) && is_array($del)) {
						foreach ($del as $k => $c) {
							$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $c);
							$this->DB->delete("permission_index", 'perm_type_id=' . $c . ' AND app="promenu"');
						}
			
					} else {
						$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $id);
						$this->DB->delete("permission_index", 'perm_type_id=' . $id.' AND app="promenu"');
					}
					$c = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=' . intval($pid)));
			
					if ($c['count'] <= 0) {
						$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 0), 'promenu_menus_id=' . intval($pid));
					}
				}
			}
		}
		
		$this->registry->profunctions->kerching();
	}
	/**
	 * @return @e void
	 */
	public function status() {
		$a['promenu_groups_preview_close'] = $this->request['status'];
		$group = $this->request['group'];

		$this->DB->update("promenuplus_groups", $a, "promenu_groups_name='" . $group . "'");
		$this->registry->profunctions->buildGroupCache();
	}

	/**
	 * @return @e void
	 */
	public function reorder() {
		$this->registry->getClass('class_permissions')->return = true;

		if(!$this->registry->class_permissions->checkPermission( 'promenu_add_group' ))
		{
			$this->returnHtml("error");
		}
		/* Define the position */
		$position = 1;
		/* Check if the array is present and count the menus */
		if (is_array($this->request['menus']) && count($this->request['menus'])) {
			/* Time to update the database with the new position */
			foreach ($this->request['menus'] as $this_id) {
				$this->DB->update('promenuplus_menus', array('promenu_menus_order' => $position), 'promenu_menus_id=' . $this_id);
				$position++;
			}
		}
		$this->registry->profunctions->kerching();
		$this->returnJsonArray($this->request['menus']);
	}

	/**
	 * @return @e void
	 */
	protected function state() {
		if ($this->request['state']) {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 1), 'promenu_menus_id=' . intval($this->request['id']));
		} else {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 0), 'promenu_menus_id=' . intval($this->request['id']));
		}
	}

	protected function getLinkType(){
		$type = $this->request['data'];
		$id = $this->request['id'];
		
		if(intval($id))
		{
			$items = $this->registry->profunctions->getSingleMenu($id);
		}
		else{
			$items = array();
		}
		
		if($type === "man" || $type==="html" || $type === "pblock" || $type==="cblock" || $type ==="eblock"){
			$html .= <<<EOF
			<tr class='ipsControlRow show' style="display:none;" id="surl">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_word_url']}</strong></td>
				<td class='field_field'>{$this->registry->output->formInput('promenu_menus_url', $items['promenu_menus_url'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_url_desc']}!</span></td>
			</tr>
EOF;
		}
		if($type==="html" || $type === "pblock" || $type==="cblock" || $type ==="eblock"){
			$html .=<<<EOF
			<tr class='ipsControlRow show' style="display:none;" id="menu_activation">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_nonpromenu_activation']}</strong></td>
				<td class='field_field'>
				<span id="name_title">{$this->registry->output->formYesNo('promenu_menus_by_url', $items['promenu_menus_by_url'])}</span>
				<br />
				<span class='desctext'>{$this->lang->words['promenu_enable_menu_active_desc2']}.</span></td>
			</tr>
EOF;
		}
		if($type === "app"){
			$html .=<<<EOF
			<tr class='ipsControlRow show' style="display:none;" id="app">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_app_link']}</strong></td>
				<td class='field_field'>
					{$this->registry->profunctions->AppLink($items['promenu_menus_app_link'])}<br />
					<span class='desctext'>{$this->lang->words['promenu_app_link_desc']}.</span>
				</td>
			</tr>
			<script>
			( function($) {

				LoadAjaxStuff = function(){
					if($("#promenu_menus_id"))
					{
						id = $("#promenu_menus_id").val();
					} 
					else{
						id = 0;
					}
					var url = ipb.vars['app_url'].replace(/&amp;/g, '&')+'module=ajax&section=ajax&do=getApplink&md5check=' + ipb.vars['md5_hash'];
					$.ajax({
							type : "POST",
							url : url.replace(/&amp;/g, '&'),
							processData: false,				
							data: "data="+$("#promenu_menus_app_link").val()+"&id="+id,
							dataType: "html",
							beforeSend: function(data){
								$(".show2").remove();
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
								$('.show2').fadeIn();
							},
							error: function(){
								alert("error, time out page reloading..." );
							}
						})
				}
				LoadAjaxStuff();
				$("#promenu_menus_app_link").change(function(e){
					LoadAjaxStuff();
				});
			}(boo));	
			</script>		
EOF;
		}
		
		if($type === "html")
		{
			$html .= <<<EOF
				
			<tr class='ipsControlRow show' id="blocks">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_block_code']}</strong></td>
				<td class='field_field'>{$this->registry->output->formTextarea('promenu_menus_block', $items['promenu_menus_block'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_block_desc']}</span></td>
			<script>
			(function($){
				$(document).ready(function(){
					dochtml = CodeMirror.fromTextArea(document.getElementById("promenu_menus_block"), {
						lineNumbers: true,
					    styleActiveLine: true,	
						highlightSelectionMatches: true,
						mode: 'text/html',
						autoCloseTags: true,
						lineNumbers: true,
				  		lineWrapping: true,
						extraKeys: {"Ctrl-Space": "autocomplete"}		
					});
				});
			}(boo))
			</script>				
			</tr>

EOF;
		}

		if($type === "pblock")
		{
			$html .=<<<EOF
			<tr class='ipsControlRow show' id="phpBlock">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_block_code']}</strong></td>
				<td class='field_field'>{$this->registry->output->formTextarea('promenu_menus_pblock', "<?php\n".$items['promenu_menus_block']."\n?>")}<br />
				<span class='desctext'>{$this->lang->words['promenu_block_desc']}</span></td>
			</tr>
			<script>
			(function($){
				$(document).ready(function(){
					docphp = CodeMirror.fromTextArea(document.getElementById("promenu_menus_pblock"), {
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
					})
				})
			}(boo));
			</script>
EOF;
		}
		if($type === "cblock"){
			$html .= $this->registry->proPlusSkin->skinCCS($items);
		}
		if($type === "eblock")
		{
			$html .= $this->registry->proPlusSkin->skinEasyPages($items);
		}

		if($type === "wrap"){
			$html .= $this->registry->proPlusSkin->skinWrapper($items);
		}
		$this->returnHtml($html);
	}
	
	protected function getAppListData(){
	
		$type = $this->request['data'];
		$id = $this->request['id'];
		if(intval($id))
		{
			$items = $this->registry->profunctions->getSingleMenu($id);
		}
		else{
			$items = array();
		}				
		if (IPSLib::appIsInstalled('ccs') && $type === "ccs") {
			$html .= <<<EOF
			<tr class='ipsControlRow show2' id="content_page">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_content_page']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->contentPages($items['promenu_menus_content_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_content_page_desc']}.</span></td>
			</tr>
EOF;
		}
		if (IPSLib::appIsInstalled('easypages') && $type ==="easypages") {
			$html .=<<<EOF
			<tr class='ipsControlRow show2' id="easypages_page">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_easypages_page']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->getEasyPages($items['promenu_menus_easy_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_easypages_page_desc']}.</span></td>
			</tr>
EOF;
		}
		
		if($type === "forums")
		{
			$html .=<<<EOF
			<tr class='ipsControlRow show2' id="forum_feature">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_attach_forums']}?</strong></td>
				<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_forums_attatch', $items['promenu_menus_forums_attatch'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_attach_forums_desc']}.</span></td>
			</tr>
EOF;
		}
		if($type === "ccsDB" && IPSLib::appIsInstalled('ccs')) {
			$html .= <<<EOF
			<tr class='ipsControlRow show2' id="content_page2">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_content_page2']}</strong></td>
				<td class='field_field'>{$this->registry->profunctions->getDatabaseContent($items['promenu_menus_content_link'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_content_page_desc2']}.</span></td>
			</tr>
EOF;
		
			$html .=<<<EOF
			<tr class='ipsControlRow show2' id="forum_feature">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_attach_db']}?</strong></td>
				<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_forums_attatch', $items['promenu_menus_forums_attatch'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_attach_db_desc']}.</span></td>
			</tr>
EOF;
		}
		$this->returnHtml($html);
	}
}