<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
class profunctions {
	/**
	 * Registry Object Shortcuts
	 *
	 * @var     $registry
	 * @var     $DB
	 * @var     $settings
	 * @var     $request
	 * @var     $lang
	 * @var     $member
	 * @var     $memberData
	 * @var     $cache
	 * @var     $caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	public 	  $proPlus;
	public	  $proper = false;
	public	  $okie;
	
	/**
	 * Constructor
	 *
	 * @param   object      $registry       Registry object
	 * @return  @e void
	 */
	public function __construct( ipsRegistry $registry ) {
		$this->registry = $registry;
		$this->lang = $this->registry->getClass('class_localization');
		$this->DB = $this->registry->DB();
		$this->settings = &$this->registry->fetchSettings();
		$this->request = &$this->registry->fetchRequest();
		$this->member = $this->registry->member();
		$this->memberData = &$this->registry->member()->fetchMemberData();
		$this->cache = $this->registry->cache();
		$this->caches = & $this->registry->cache()->fetchCaches();
		$this->proPlus = false;
		if($this->settings['pro_cop_rem_key'] && $this->settings['pro_cop_first'] && $this->settings['pro_cop_last']){
			$say = str_split(md5($this->settings['pro_cop_first'].$this->settings['pro_cop_last'].$this->settings['board_url']),6);
			unset($say[2]);
			unset($say[5]);
			$say = implode("-",$say);
			if($say === $this->settings['pro_cop_rem_key']){
				$this->proper = true;
			}
		}
		if( is_file( IPSLib::getAppDir("promenu") . "/sources/plus/proPlus.php" ) && is_file( IPSLib::getAppDir("promenu") . "/sources/plus/proPlusSkin.php" ) )
		{
			$this->proPlus = true;
			if ( !$this->registry->isClassLoaded('proPlus') ) {
				$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/plus/proPlus.php", 'proPlus', 'promenu');
				$this->registry->setClass( 'proPlus', new $classToLoad( $this->registry ) );
			}
			if ( !$this->registry->isClassLoaded('proPlusSkin') ) {
				$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/plus/proPlusSkin.php", 'proPlusSkin', 'promenu');
				$this->registry->setClass( 'proPlusSkin', new $classToLoad( $this->registry ) );
			}
		}
	}

	/**
	 * buildGroupCache 
	 * gets the groups from the DB and formats for the cache
	 *  @return	@e void
	 */
	public function GetGroupsFromDb() {
		$g = array();
		$gs = array();

		$this->DB->build(array(
			'select' => '*',
			'from' => 'promenuplus_groups',
			'order' => 'promenu_groups_name asc'));

		$q = $this->DB->execute();

		while ($gs = $this->DB->fetch($q)) {
			$g[$gs['promenu_groups_name']] = $gs;
		}

		return $g;
	}

	/**
	 * buildGroupCache 
	 * takes database info and stores into the Cache for groups.
	 * @return  @e void
	 */
	public function buildGroupCache() {
		$g = $this->GetGroupsFromDb();
		$this->cache->setCache('promenu_groups', $g, array('array' => 1, 'donow' => 1));
        $this->kerching();
	}

	/**
	 * kerching 
	 * gets the menus for each group, and formats them for the cache.
	 *  @return	@e void
	 */
	public function kerching() {
		$groups = $this->GetGroupsFromDb();

		if (count($groups) && is_array($groups)) {
			foreach ($groups as $k => $c) {
				if(intval($c['promenu_groups_enabled'])){
					$cache[$k] = $this->buildMenuCache(0, $k);
				}
			}
			if(intval($this->settings['promenu_use_file_cache'])){
				$this->cache->setCache('promenu_menus', array(), array('array' => 1, 'donow' => 1));
				$this->writeToDiskPro($cache,'menu.php');
			}
			else{
				$this->cache->setCache('promenu_menus', $cache, array('array' => 1, 'donow' => 1));
			}
		}
	}

	/**
	 * writeToDiskPro
	 * @param mixed $content
	 */
	public function writeToDiskPro($content,$filename){
	
		$path = IPS_CACHE_PATH.'cache/promenu/';

		$able = 0;
		if ( IPSLib::isWritable(IPS_CACHE_PATH.'cache/') === TRUE )
		{
			$able = 1;
	
			if ( ! is_dir( $path ) )
			{
				if ( ! @ mkdir( $path, IPS_FOLDER_PERMISSION ) )
				{
					//throw error here!
					$this->registry->output->logErrorMessage( $path." Can not be created");
				}
				else
				{
					@file_put_contents( $path.'index.html', '' );
					@chmod( $path, IPS_FOLDER_PERMISSION );
					$able = 1;
				}
			}
		}
		else{
			$this->registry->output->logErrorMessage( IPS_CACHE_PATH."cache/promenu/ Not Writeable");
		}
	
		if($able == 1)
		{
	
			if(is_file($path.$filename)){
				@unlink($path.$filename);
			}
			if ( $FH = @fopen( $path.$filename, 'w' ) )
			{
				$content = json_encode($content);
				fwrite( $FH, $content, strlen($content) );
				fclose( $FH );
				@chmod( $path.$filename, IPS_FILE_PERMISSION );
	
			}
		}
	
	}
		
	
	/**
	 * buildMenuCache 
	 * get menu data from the DB, loops thru it for children elements, and does some pre-processing for the frontend.
	 *  @param string $id this is for the parent id of menus so it can group them properly (too lazy to change to PID or something more sensical)
	 *  @param string $key the key is always the "group" the menu items belong too!
	 *  @return	array
	 */
	protected function buildMenuCache($id = 0, $key = '') {
		if (!$id) {
			$id = 0;
		}

		$entry = array();
		$data = $this->getMenusByParent($id, $key);

		if (count($data) && is_array($data)) {
			$k = '';
			$c = '';
			foreach ($data as $k => $c) {
				if (!$c['promenu_menus_icon_check']) {
					if ($c['promenu_menus_icon']) {
						$c['promenu_menus_icon_class'] = $c['promenu_menus_icon'];
						$c['promenu_menus_icon'] = $this->settings['img_url'] . "/promenu/icons/promenu_default_icons.png";
						$c['promenu_menus_icon_w'] = 14;
						$c['promenu_menus_icon_h'] = 14;
					}
				}

				$c['promenu_menus_attr'] = unserialize($c['promenu_menus_attr']);

				$c['promenu_menus_desc'] = unserialize($c['promenu_menus_desc']);

				$c['promenu_menus_name'] = unserialize($c['promenu_menus_name']);
				
				$d = $c['promenu_menus_app_link'];
				
				if($c['promenu_menus_link_type'] != "app")
				{
					unset($c['promenu_menus_app_link']);
				}
				
				if($d === "ccsDB2"){
					$c['promenu_menus_link_type'] = "app";
					$c['promenu_menus_app_link'] = "ccsDB2";
				}
				if(count($c) && is_array($c)){
					$vv = '';
					$kk = '';
					$new = '';
					$cc = '';
					foreach($c as $kk => $vv){
					 	if(!empty($vv)){
					 		$con = 0;
					 		if($kk == "promenu_menus_attr"){
					 			if(empty($vv['class']) && empty($vv['style']) && empty($vv['attr'])){
					 				$con = 1;
					 			}
					 		}
					 		if($kk === "promenu_menus_desc"){
					 			if(!is_array($vv) && !count($vv)){
					 				$con = 1;
					 			}
					 		}
					 		if(!intval($con)){
								$new = str_replace("promenu_menus_","",$kk);
								$cc[$new] = $vv;
					 		}
					 	}
					}
				}
				
				$entry[$id][$c['promenu_menus_id']] = $cc;

				if ($c['promenu_menus_has_sub']) {
					$b = self::buildMenuCache($c['promenu_menus_id'], $key);
				}
				if (count($b)) {
					$entry = $this->ArrayMergeRecursiveNew($entry, $b);
				}
			}
		}
		return $entry;
	}

	/**
	 * GetCaches 
	 *  eh, this function is pretty useless but i like it!
	 *  @param string $key the key is always what group you are wanting!
	 *  @return	array
	 */
	public function GetCaches($key = '') {
		if(intval($this->settings['promenu_use_file_cache'])){		
			$path = IPS_CACHE_PATH.'cache/promenu/menu.php';
			if(is_file($path)){
				$data = json_decode($this->GetContent($path),true);
			}
			else{
				$this->kerching();
				$data = json_decode($this->GetContent($path),true);
			}
		}
		if($this->settings['pro_cop_rem_key'] && $this->settings['pro_cop_first'] && $this->settings['pro_cop_last']){
			$say = str_split(md5($this->settings['pro_cop_first'].$this->settings['pro_cop_last'].$this->settings['board_url']),6);
			unset($say[2]);
			unset($say[5]);
			$say = implode("-",$say);
			if($say === $this->settings['pro_cop_rem_key']){
				$this->proper = true;
			}
		}
		$g = $this->cache->getCache('promenu_groups');
		$a = intval($this->settings['promenu_use_file_cache'])? $data : $this->cache->getCache('promenu_menus');
		$c = '';
		if (!$key) {
			//load them all!
			$c['groups'] = $this->caches['promenu_groups'];
			$c['menus'] = intval($this->settings['promenu_use_file_cache']) ? $data : $this->caches['promenu_menus'];
		} else if ($key) {
			//booh! we aren't loading them all.
			$c['groups'] = $g[$key];
			$c['menus'] = $a[$key];
		}
		$this->okie = $this->proper;
		return $c;
	}

	public function getNonStaticGroups(){
		$groups = $this->cache->getCache('promenu_groups');
		$g = array();
		if(count($groups) && is_array($groups)){
			foreach($groups as $k => $v)
			{
				if($v['promenu_groups_static'] != 1)
				{
					$g[$k] = $v['promenu_groups_name'];
				}
			}
		}
		
		return $g;
	}

	/**
	 * buildGroups 
	 *  returns an array for all the groups to be used in dropdowns...don't i have a function for this already?
	 *  @return	array
	 */
	public function buildGroups() {
		$groups = $this->caches['promenu_groups'];

		if (count($groups) && is_array($groups)) {
			foreach ($groups as $k => $c) {
				$g[$k] = array($c['promenu_groups_name'], $c['promenu_groups_name']);
			}
		}
		return $g;
	}

	/**
	 * DisplayGroups 
	 *  this is for the ACP to show the groups in the command bar.
	 *  @return	string
	 */
	public function DisplayGroups() {
		$key = $this->request['key'] ? $this->request['key'] : "primary";
		
		$html = '';

		$g = $this->buildGroups();
		
		$group = $this->caches['promenu_groups'][$key];

		$confirm = sprintf($this->lang->words['promenu_delete_group_alert'], $key);

		if (count($g) && is_array($g)) {

				$html .=<<<EOF
				<div class="right" style="position:relative;">
					<script>
					jQ(document).ready(function(){
						jQ("#altdrop").click(function(e){
							Event.stop(e);
							if(jQ(this).data("isOpens") != 1)
							{
								jQ(this).data("isOpens",1).next().fadeIn();
							}
							else{
								jQ(this).removeData("isOpens").next().fadeOut()
							}
						})
						jQ("body").not("#notMe").click(function(){
							jQ("#notMe").fadeOut().prev().removeData("isOpens");
						})
					})
					</script>
					<a href="#" id="altdrop" class="menu_btn" data-tooltip="{$this->lang->words['promenu_click_custom_group_options']}">
						<img src="{$this->settings['skin_app_url']}images/threelines.png" style="margin-top:-2px;"/>
					</a>
					<div id="notMe" style="display:none;position:absolute;width:350px;left:-130px;background: #eaeef4;">
						<ul>
							<li style="font-weight:bold;">
								<a href="#" id="promenu_information" style="width:150px;">
									<img src="{$this->settings['skin_acp_url']}/images/icons/ipsnews_item.gif" > {$this->lang->words['promenu_information']}
								</a>
								<script>
									boo('#promenu_information').click(function(e){
										e.preventDefault();
										urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=newsUpdate&md5check=" + ipb.vars['md5_hash'];
										PopupImport = new ipb.Popup( 'PopupImport',{
																				type: 'pane',
																				modal: true,
																				w: '810px',
																				h: '530px',
																				ajaxURL: urls,
																				hideAtStart: false, 
																				close: 'a[rel="close"]'
																			},
																			{
																				afterHide: function() { PopupImport.kill() }
																			}
														);
									});
								</script>
							</li>
							<li style="font-weight:bold;">
								<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=ExportGroup&amp;key={$key}&amp;postkey={$this->member->form_hash}" id="addForums"  style="width:150px;">
								<img src="{$this->settings['skin_acp_url']}/images/icons/add.png"> Add Forum Links
								</a>
								<script>
									boo('#addForums').click(function(e){
										e.preventDefault();
										urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=addForums&key={$key}&md5check=" + ipb.vars['md5_hash'];
										PopupImport = new ipb.Popup( 'PopupImport',{
																				type: 'pane',
																				modal: true,
																				w: '810px',
																				h: '530px',
																				ajaxURL: urls,
																				hideAtStart: false, 
																				close: 'a[rel="close"]'
																			},
																			{
																				afterHide: function() { PopupImport.kill() }
																			}
														);
									});
								</script>
							</li> 
EOF;
			if (!$group['promenu_groups_static']) {
					$html .=<<<EOF
							<li style="font-weight:bold;">
								<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=ExportGroup&amp;key={$key}&amp;postkey={$this->member->form_hash}" data-tooltip="{$this->lang->words['promenu_export_as_hook']}" style="width:150px;">
								<img src="{$this->settings['skin_acp_url']}/images/icons/export.png"> {$this->lang->words['promenu_export_as_hook']}
								</a>
							</li>
							<li style="font-weight:bold;">
								<a style="cursor:pointer;width:150px;" id="creatCss" data-tooltip="{$this->lang->words['promenu_default_css']}">
									<img src="{$this->settings['skin_acp_url']}/images/icons/layout_content.png" > {$this->lang->words['promenu_default_css']}
								</a>
								<script>
									//urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=css&key={$key}&secure_key=" + ipb.vars['md5_hash'];
									$('creatCss').observe( 'click', function(e) {
										popupcreatCss = new ipb.Popup( 'popupcreatCss',
																		{
																			type: 'pane',
																			modal: true,
																			initial: $('thisCss').innerHTML,
																			w: '810px',
																			h: '530px',
																			hideAtStart: false, 
																			close: 'a[rel="close"]'
																		},
																		{
																			afterHide: function() { popupcreatCss.kill() }
																		}
																	);
										Event.stop(e);
										return false;
								});
								</script>
							</li>
							<li style="font-weight:bold;">
								<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=delgroup&amp;key={$key}&amp;postkey={$this->member->form_hash}" onclick="return confirm('{$confirm}');" data-tooltip="{$this->lang->words['promenu_delete_group']}" style="width:150px;">
									<img src="{$this->settings['skin_acp_url']}/images/icons/delete.png" > {$this->lang->words['promenu_delete_group']}
								</a>
							</li>
EOF;
			}
				$html .=<<<EOF
						</ul>
					</div>
				</div>
EOF;
			
			
			$html .=<<<EOF
			<li class="right" style="font-weight:bold;">
				<a href="#" id="importApps" data-group="{$key}">
					<img src="{$this->settings['skin_acp_url']}/images/icons/import.png" > {$this->lang->words['promenu_import_menus']}
				</a>
				<script>
					boo('#importApps').click(function(e){
						e.preventDefault();
						urls = ipb.vars['base_url']+"app=promenu&module=ajax&section=ajax&do=importApps&key="+boo(this).data('group')+"&md5check=" + ipb.vars['md5_hash'];
						console.log(boo(this).data('group'));
						PopupImport = new ipb.Popup( 'PopupImport',{
																type: 'pane',
																modal: true,
																w: '810px',
																h: '530px',
																ajaxURL: urls,
																hideAtStart: false, 
																close: 'a[rel="close"]'
															},
															{
																afterHide: function() { PopupImport.kill() }
															}
										);
					});
				</script>
			</li>  
            <li class="right" style="font-weight:bold;">
				<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=AddGroup&amp;postkey={$this->member->form_hash}" data-tooltip="{$this->lang->words['promenu_add_group']}">
					<img src="{$this->settings['skin_acp_url']}/images/icons/add.png" > {$this->lang->words['promenu_add_group']}
				</a>
			</li>
EOF;
			$html .=<<<EOF
			<li class="right" style="font-weight:bold;">
				<a href="{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=EditGroups&amp;key={$key}&amp;postkey={$this->member->form_hash}" data-tooltip="{$this->lang->words['promenu_edit_group']}" id="GroupSettings_1">
					<img src="{$this->settings['skin_acp_url']}/images/icons/pencil.png" > {$this->lang->words['promenu_edit_group']}
				</a>
			</li>
EOF;
			$html .=<<<EOF
			<li class="right" style="font-weight:bold;">
				Select Group: {$this->registry->output->formDropdown("promenu_group", $g, $key, '', "onchange='LoadGroups();'")}
			</li>
EOF;
		}

		return $html;
	}

	/**
	 *  AppLink 
	 *  builds the currently install app list into a drop down for the acp.
	 *  @param string $cur if this is set, will select the default if editing.
	 *  @return	string
	 */
	public function AppLink($cur = '') {
		$apps = array();
		$apps[] = array("0","Select One");
		foreach (ipsRegistry::$applications as $app_key => $app) {
			if (strtolower($app['app_title']) != "promenu") {
				$disabled = '';
				if ($app['app_hide_tab']) {
					$disabled = $this->lang->words['promenu_word_hidden'];
				}  
				if (!$app['app_enabled']) {
					$disabled = $this->lang->words['promenu_word_disabled'];
				}
				if($app['app_title'] === "Content")
				{
					$apps[] = array("ccsDB","Content Database");
				}
				$apps[] = array($app_key, $app['app_title'] . $disabled);
			}
		}
		sort($apps);

		return $this->registry->output->formDropdown("promenu_menus_app_link", $apps, $cur);
	}

	/**
	 * LinkType 
	 * a little bit different function, given choics for installed apps and limits if parent or not.
	 * @param string $cur if there is a current, displays it.
	 * @param string $parent is parent set or not, shows different options.
	 * @return string
	 */
	public function LinkType($cur = '', $group = '') {

		$type[] = array("def", "{$this->lang->words['promenu_non_click']}");
		$type[] = array("man", "{$this->lang->words['promenu_insert_url']}");
		$type[] = array("app", "{$this->lang->words['promenu_word_application']}");
		$type[] = array("html", "{$this->lang->words['promenu_raw_html_block']}");
		if ($this->proPlus === true) {
			$type[] = array("pblock", "{$this->lang->words['promenu_php_block']}");
			$type[] = array("wrap", "{$this->lang->words['promenu_wrapper']}");
			if (IPSLib::appIsInstalled('ccs') && $this->caches['app_cache']['ccs']['app_enabled']) {
				$type[] = array("cblock", "{$this->lang->words['promenu_ipc_block']}");
			}
			if (IPSLib::appIsInstalled('easypages') && $this->caches['app_cache']['easypages']['app_enabled']) {
				$type[] = array("eblock", "{$this->lang->words['promenu_easy_page_block']}");
			}
		}
		return $this->registry->output->formDropdown("promenu_menus_link_type", $type, $cur);
	}

	public function getMenusByParent($pid = 0, $key = '') {
		if (!$key) {
			$key = urldecode($this->request['key']) ? urldecode($this->request['key']) : "primary";
		}

		if ($pid) {
			$where = 'm.promenu_menus_parent_id=' . $pid;
		} else {
			$where = 'm.promenu_menus_parent_id=' . $pid . ' AND m.promenu_menus_group="' . $key . '"';
		}
		$menus = array();

		if (IPSLib::appIsInstalled('ccs')) {
			$addjoin = array(array(
					'select' => 'p.perm_view',
					'from' => array('permission_index' => 'p'),
					'where' => "p.perm_type = 'menu' AND p.app = 'promenu' AND p.perm_type_id = m.promenu_menus_id",
					'type' => 'left')
				, array('select' => 'pa.page_seo_name, pa.page_folder,pa.page_folder',
					'from' => array('ccs_pages' => 'pa'),
					'where' => "pa.page_id=m.promenu_menus_content_link",
					'type' => 'left'));
		} else {
			$addjoin = array(
				array(
					'select' => 'p.perm_view',
					'from' => array('permission_index' => 'p'),
					'where' => "p.perm_type = 'menu' AND p.app = 'promenu' AND p.perm_type_id = m.promenu_menus_id",
					'type' => 'left'
				)
			);
		}

		$this->DB->build(array('select' => 'm.*',
			'from' => array('promenuplus_menus' => 'm'),
			'where' => $where,
			'order' => 'm.promenu_menus_order ASC',
			'add_join' => $addjoin));
		$q = $this->DB->execute();

		while ($b = $this->DB->fetch($q)) {
			$menus[] = $b;
		}

		return $menus;
	}

	/**
	 * get_single_menu
	 * sometimes we just need to single a guy out!
	 * @param int what is the id of the menu item we are singling out?
	 * @return array
	 */
	public function getSingleMenu($id) {
		$addjoin = array(
			array(
				'select' => 'p.perm_view',
				'from' => array('permission_index' => 'p'),
				'where' => "p.perm_type = 'menu' AND p.app = 'promenu' AND p.perm_type_id = m.promenu_menus_id",
				'type' => 'left'
			)
		);

		$c = $this->DB->buildAndFetch(array('select' => 'm.*',
			'from' => array('promenuplus_menus' => 'm'),
			'where' => 'm.promenu_menus_id=' . $id,
			'add_join' => $addjoin));

		return $c;
	}

	/**
	 * buildChildData
	 * gets the children for the buildParentData function
	 * @param int $pid parent id we are stripping by
	 * @param int $sub how many arrows do we place in front of it?
	 * @param int $id  strips out itself, and children so we don't get linking back to itself, causing a infinite loop.
	 * @return array  
	 */
	protected function buildChildData($pid, $sub, $id) {
		$mark = '';
		$build = array();
		$builder = array();
		foreach ($this->caches['lang_data'] as $k => $c) {
			if ($c['lang_default']) {
				$default = $c['lang_id'];
				break;
			}
		}
		$data = $this->getMenusByParent($pid);

		if (count($data)) {
			for ($i = 1; $i <= $sub + 1; $i++) {
				$mark .= "&rarr;&nbsp;";
			}

			foreach ($data as $k => $d) {
				$names = unserialize($d['promenu_menus_name']);

				if ($id != $d['promenu_menus_id'] && $d['promenu_menus_parent_id'] != $id) {
					$build[] = array($d['promenu_menus_id'], $mark . $names[$default]);

					if ($d['promenu_menus_has_sub'] == 1 && $d['promenu_menus_parent_id'] != $id) {
						$builder = $this->buildChildData($d['promenu_menus_id'], $sub + 1, $id);

						if (count($builder)) {

							$build = array_merge($build, $builder);
						}
					}
				}
			}
		}

		return $build;
	}

	/**
	 * buildParentData
	 * gatheers up the root parents to build a drop down for the acp.
	 * @param string $name the name of the field for post
	 * @param int $id strips itself and its children from being built so we can't link to itself or children, causing a infinite loop
	 * @param int $parent if it is already defined like for an edit, lets display it in the dropdown
	 * @return string
	 */
	public function buildParentData($name = '', $id = "", $current = "") {
		$entry = array();
		$subs = array();

		foreach ($this->caches['lang_data'] as $k => $c) {
			if ($c['lang_default']) {
				$default = $c['lang_id'];
				break;
			}
		}

		$entry[] = array(0, '--Parent Category--');

		$data = $this->getMenusByParent(0);

		if (count($data) && is_array($data)) {
			foreach ($data as $k => $d) {
				$names = unserialize($d['promenu_menus_name']);

				if ($id != $d['promenu_menus_id']) {
					$entry[] = array($d['promenu_menus_id'], $names[$default]);
				}
				if ($d['promenu_menus_has_sub'] == 1) {
					$subs = $this->buildChildData($d['promenu_menus_id'], "0", $id);
					if (count($subs)) {
						$entry = array_merge_recursive($entry, $subs);
					}
				}
			}
		}
		$output = $this->registry->output->formDropdown($name, $entry, $current);

		return $output;
	}

	/**
	 * gather_id_for_delete
	 * gathering up the menu items for deletion, which is a bit tricky
	 * @param int $id the id of the first item we are deleting, then we work our way down thru the children
	 * @return array
	 */
	public function gatherIdForDel($id) {
		$parents = $this->getMenusByParent($id);
		$pds[$id] = $id;
		if (count($parents) && is_array($parents)) {
			foreach ($parents as $key => $p) {
				$pds[$p['promenu_menus_id']] = $p['promenu_menus_id'];

				if ($p['promenu_menus_has_sub'] == 1) {
					$kids = $this->gatherIdForDel($p['promenu_menus_id']);
					$pds = $this->ArrayMergeRecursiveNew($pds, $kids);
				}
			}
		}
		return $pds;
	}

	/**
	 * ArrayMergeRecursiveNew 
	 * formats the menu items in a array to allow easy navigation thru
	 * @return array
	 */
	protected function ArrayMergeRecursiveNew() {
		$arrays = func_get_args();

		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			reset($base); //important
			while (list($key, $value) = @each($array)) {
				if (is_array($value) && @is_array($base[$key])) {
					$base[$key] = $this->ArrayMergeRecursiveNew($base[$key], $value);
				} else {
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}

	/**
	 * get_update
	 * checks the remote froggy to see if there is an update for the app
	 * @param array $app appdata from the appcache
	 * @return string
	 */
	public function get_update($app) {
		if (!$this->settings['promenu_checks_api']) {
			$update = $this->GetContent("http://provisionists.org/index.php?app=froggy&amp;module=versions&amp;section=versions&amp;app_key=promenu&amp;boardVersion=" . $app['core']['app_long_version'] . "&amp;version=" . $app['promenu']['app_long_version']);
			return $update;
		}
	}

	/**
	 * gather_news
	 * gets the new from the remote froggy
	 * @return array
	 */
	public function gather_news() {
		if (!$this->settings['promenu_checks_api']) {
			$news = $this->GetContent("http://provisionists.org/index.php?app=froggy&amp;module=news&amp;section=news&amp;app_key=promenu");
			$news = json_decode($news);

			if (!count($news) && !is_array($news)) {
				$news = array();
			}

			return $news;
		}
	}

	/**
	 * GetContent
	 * get remote content, or use for html scraping, mainly for the two above functions
	 * @param string $url location of the content we are grabbing
	 * @return string
	 */
	public function GetContent($url) {
		/* Get the file managemnet class */
		$classToLoad = IPSLib::loadLibrary(IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement');

		$query = new $classToLoad();

		$query->timeout = 2;

		return $query->getFileContents($url);
	}

	/**
	 * multilang
	 * function for displaying the multiple langagues
	 * @param string $key always the group name
	 * @param string $items always the saved content from the menu item if it is there, if not, blank array
	 * @return string
	 */
	public function multilang($key, $items = array()) {
		$group = $this->caches['promenu_groups'][$key];

		$items = unserialize($items);

		$langer = $this->caches['lang_data'];

		if ($group['promenu_groups_enable_alternate_lang_strings']) {
			$html .= "<div style='width:100%;'>";
			foreach ($langer as $k => $c) {
				$star = '';
				$short = $c['lang_id'];
				if ($c['lang_default']) {
					$star = "*";
				}
				$html .=<<<EOF
				<span style="width:150px;display:inline-block;">{$c['lang_title']}{$star}</span><span id="name_title">{$this->registry->output->formInput("promenu_menus_name[{$short}]", $items[$short], '', '', '', '', '', 255)}</span><br>
EOF;
			}
		} else {
			foreach ($langer as $k => $c) {
				if ($c['lang_default']) {
					$default = $k;
					break;
				}
			}
			$short = $langer[$default]['lang_id'];
			$html .=<<<EOF
					<span id="name_title">{$this->registry->output->formInput("promenu_menus_name[{$short}]", $items[$short], '', '', '', '', '', 255)}</span><br>
EOF;
		}

		return $html;
	}

	/**
	 * multilangdesc
	 * function for displaying the multiple langagues
	 * @param string $key always the group name
	 * @param string $items always the saved content from the menu item if it is there, if not, blank array
	 * @return string
	 */
	public function multilangdesc($key, $items = array()) {
		$group = $this->caches['promenu_groups'][$key];

		$items = unserialize($items);

		$langer = $this->caches['lang_data'];

		if ($group['promenu_groups_enable_alternate_lang_strings']) {
			$html .= "<div style='width:100%;'>";
			foreach ($langer as $k => $c) {
				$star = '';
				$short = $c['lang_id'];
				if ($c['lang_default']) {
					$star = "*";
				}
				$html .=<<<EOF
				<span style="width:150px;display:inline-block;">
					{$c['lang_title']}{$star}
				</span>
				<span id="name_title">
					{$this->registry->output->formTextarea("promenu_menus_desc[{$short}]", $items[$short])}
				</span>
				<br>
EOF;
			}
		} else {
			foreach ($langer as $k => $c) {
				if ($c['lang_default']) {
					$default = $k;
					break;
				}
			}
			$short = $langer[$default]['lang_id'];
			$html .=<<<EOF
					<span id="name_title">{$this->registry->output->formTextarea("promenu_menus_desc[{$short}]", $items[$short])}</span><br>
EOF;
		}

		return $html;
	}

	/**
	 * readDefaultIcons
	 * a list of the default icons we offer
	 * @return array
	 */
	public function readDefaultIcons() {
		$icon[] = array("0", "--{$this->lang->words['promenu_choose_icon']}--");
		$icon[] = array("Arcade", "{$this->lang->words['promenu_word_arcade']}");
		$icon[] = array("Blog", "{$this->lang->words['promenu_word_blog']}");
		$icon[] = array("Calendar", "{$this->lang->words['promenu_word_calendar']}");
		$icon[] = array("Cart", "{$this->lang->words['promenu_word_cart']}");
		$icon[] = array("Chat", "{$this->lang->words['promenu_word_chat']}");
		$icon[] = array("Download", "{$this->lang->words['promenu_word_download']}");
		$icon[] = array("Forums", "{$this->lang->words['promenu_word_forums']}");
		$icon[] = array("Gallery", "{$this->lang->words['promenu_word_gallery']}");
		$icon[] = array("Help", "{$this->lang->words['promenu_word_help']}");
		$icon[] = array("Home", "{$this->lang->words['promenu_word_home']}");
		$icon[] = array("Info", "{$this->lang->words['promenu_word_info']}");
		$icon[] = array("Media", "{$this->lang->words['promenu_word_media']}");
		$icon[] = array("Members", "{$this->lang->words['promenu_word_members']}");
		$icon[] = array("More", "{$this->lang->words['promenu_word_more']}");
		$icon[] = array("News", "{$this->lang->words['promenu_word_news']}");
		$icon[] = array("Shoutbox", "{$this->lang->words['promenu_word_shoutbox']}");
		return $icon;
	}

	/**
	 * check4ForumsLink
	 * checks to make sure the items being deleted should be deleted!
	 * @param array $checks the list of ids already gathered.
	 * @param array $group the group we are check the ideas againts 
	 * @return array
	 */
	public function check4ForumsLink($checks = array(), $group = '') {
		if (count($checks) && is_array($checks)) {

			$this->DB->build(array('select' => '*',
				'from' => 'promenuplus_menus',
				'where' => 'promenu_menus_app_link = "forums" AND promenu_menus_group = "' . $group['promenu_groups_name'] . '" AND promenu_menus_forums_attatch=1'));
			$q = $this->DB->execute();

			while ($b = $this->DB->fetch($q)) {
				$del = $this->gatherIdForDel($b['promenu_menus_id']);

				if (count($del) && is_array($del)) {
					foreach ($del as $k => $c) {
						unset($checks[$c]);
					}
				}
			}

			return $checks;
		}
	}

	/**
	 * ParseMenus 
	 * takes cache data for menus, and makes it useful for the front end templates
	 * @param array $data
	 * @param int $parent
	 * @param array $group
	 * @param bool $preview
	 * @return array 
	 */
	public function ParseMenus($data = array(), $parent = 0, $group = array(), $preview = FALSE) {
	
		if (!count($data[$parent]) && !is_array($data[$parent])) {
			return FALSE;
		}
	
		if (IPB_LONG_VERSION > 34006) {
	
			$classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/text/parser.php', 'classes_text_parser');
	
			$parser = new $classToLoad();
	
			/* Set up some settings */
			$parser->set(array('parseArea' => 'promenu',
					'memberData' => $this->memberData,
					'parseBBCode' => true,
					'parseHtml' => true,
					'parseEmoticons' => true));
		} else {
			$classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite');
			$this->editor = new $classToLoad();
			$this->editor->setAllowHtml("1");
	
			IPSText::getTextClass('bbcode')->parsing_section = 'promenu';
			IPSText::getTextClass('bbcode')->parse_smilies = TRUE;
			IPSText::getTextClass('bbcode')->parse_bbcode = TRUE;
			IPSText::getTextClass('bbcode')->parse_html = TRUE;
			IPSText::getTextClass('bbcode')->parse_nl2br = TRUE;
	
			IPSText::getTextClass('bbcode')->bypass_badwords = FALSE;
			IPSText::getTextClass('bbcode')->parsing_mgroup = $this->memberData['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others = $this->memberData['mgroup_others'];
		}
		//first things first
		foreach ($data[$parent] as $k => $c) {
			//$access = $this->accessCheck($c, $group);
			if ($this->accessCheck($c, $group)) {
				if ($this->childCheck($data[$c['id']], $group)) {
					$c['has_sub'] = 1;
				} else {
					$c['has_sub'] = 0;
				}
	
				if ($c['forums_parent_id'] && $c['forums_id']) {
					$c['url'] = $this->registry->output->buildSEOUrl($c['url'], 'public', $c['forums_seo'], 'showforum');
				}
	
				if ($c['url']) {
					$c['url'] = 'href="' . $c['url'] . '"';
				}
	
				$c['nav_app'] = $c['id'];
	
				if ($c['link_type'] === "app") {
					switch ($c['app_link']) {
						case 'core':
							$c['url'] = $this->registry->output->buildSEOUrl("app=core&amp;module=help", 'public', 'false', 'core');
							break;
						case 'forums':
							$c['url'] = $this->registry->output->buildSEOUrl("act=idx", 'public', $c['app_link'], "act=idx");
							break;
						case 'members':
							$c['url'] = $this->registry->output->buildSEOUrl('app=members&amp;module=list', 'public', 'false', 'members_list');
							break;
						case 'ccs':
							/* Load up ccsFunctions if not loaded already */
							if (!$this->registry->isClassLoaded('ccsFunctions')) {
								$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
								$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
							}
							$c['url'] = $this->registry->ccsFunctions->returnPageUrl(array('page_seo_name' => $c['page_seo_name'], 'page_folder' => $c['page_folder'], 'page_id' => $c['content_link']));
							break;
						case 'ccsDB':
							/* Load up ccsFunctions if not loaded already */
							if (!$this->registry->isClassLoaded('ccsFunctions')) {
								$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
								$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
							}
							$c['url'] = $this->registry->ccsFunctions->returnDatabaseUrl( $c['content_link'] );
							break;
						case 'ccsDB2':
							/* Load up ccsFunctions if not loaded already */
							if (!$this->registry->isClassLoaded('ccsFunctions')) {
								$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
								$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
							}
							$c['url'] = $this->registry->ccsFunctions->returnDatabaseUrl( $c['forums_parent_id'], $c['forums_id'] );
							break;
						case 'easypages':
							$c['app_link'] = '';
							$c['url'] = $this->registry->output->buildSEOUrl("app=easypages&page={$c['easy_link']}", 'public', 'easypages', 'easypages');
							break;
						default:
							$c['url'] = $this->registry->output->buildSEOUrl("app={$c['app_link']}", 'public', 'false', "app={$c['app_link']}");
							break;
					}
	
					$c['url'] = 'href="' . $c['url'] . '"';
	
					$c['nav_app'] = $c['app_link'];
	
				}
	
				if ($group['promenu_groups_behavoir'] == 2 && !$c['parent_id']) {
					$c['hovernoclick'] = "";
					$c['click'] = "click";
				} else if ($group['promenu_groups_behavoir'] == 2 && $c['parent_id']) {
					$c['hovernoclick'] = "hovernoclick";
					$c['click'] = "";
				} else if ($group['promenu_groups_behavoir'] == 3) {
					$c['hovernoclick'] = "";
					$c['click'] = "click";
				} else {
					$c['hovernoclick'] = "";
					$c['click'] = "";
				}
	
	
				if('href="' . $this->settings['this_url'] . '"' == $c['url'] && $group['promenu_groups_tab_activation'] )
				{
					$c['click'] .= " active";
				}
				else if( $c['app_link'] == 'ccs' && $this->registry->getCurrentApplication() == 'ccs' && $group['promenu_groups_tab_activation']) {
					if( $this->registry->getClass('ccsFunctions')->getFolder() == $c['page_folder'] &&$this->registry->ccsFunctions->getPageName() == $c['page_seo_name'] && $c['app_link'] == $this->registry->getCurrentApplication()) {
						$c['click'] .= " active";
					}
	
					if(!$this->registry->getClass('ccsFunctions')->getPageName()) {
						if($c['page_seo_name'] == $this->settings['ccs_default_page'] && !$this->request['id']) {
							$c['click'] .= " active";
						}
					}
				}
				else if($c['app_link'] === $this->registry->getCurrentApplication() && $group['promenu_groups_tab_activation'] )
				{
					$c['click'] .= " active";
				}
	
				if($c['forums_id'])
				{
					if($this->request['f'] == $c['forums_id'])
					{
						$c['click'] .= " active";
					}
				}
	
				if ($c['has_sub']) {
					if ($group['promenu_groups_arrows_enabled']) {
						if (!$c['parent_id']) {
							$c['arrow'] = "downarrow";
						} else {
							$c['arrow'] = "otherarrow";
						}
					}
				}
	
				if($c['link_type'] === 'wrap'){
					$c['url'] = 'href="' . $this->registry->output->buildSEOUrl("app=promenu&amp;module=preview&amp;section=preview&do=wrapper&amp;id=".$c['id'], 'public', 'false', "proWrapper").'"';
				}
				else if ($c['link_type'] === 'html' ) {
					if (!$c['parent_id']) {
						$c['arrow'] = "downarrow";
					} else {
						$c['arrow'] = "otherarrow";
					}
				} else if ($c['link_type'] === 'pblock' && $this->proPlus === true ) {
					if (!$c['parent_id']) {
						$c['arrow'] = "downarrow";
					} else {
						$c['arrow'] = "otherarrow";
					}
	
					$c['block'] = $this->registry->proPlus->parsePHP($c['block']);
				} else if ($c['link_type'] === 'cblock' && $this->proPlus === true && IPSLib::appIsInstalled('ccs') ) {
						
					if (!$c['parent_id']) {
						$c['arrow'] = "downarrow";
					} else {
						$c['arrow'] = "otherarrow";
					}
	
					$c['block'] = $this->registry->proPlus->parseCblock($c['block']);
						
				} else if ($c['link_type'] === 'eblock' && $this->proPlus === true && IPSLib::appIsInstalled('easypages') ) {
					if (!$c['parent_id']) {
						$c['arrow'] = "downarrow";
					} else {
						$c['arrow'] = "otherarrow";
					}
	
					$c['block'] = $this->registry->proPlus->parseEblock($c['block']);
				}
	
				if ($this->caches['vnums']['long'] > 34006) {
	
					if ($c['block']) {
						$c['block'] = $parser->BBCodeToHtml($c['block']);
					}
				} else {
					if ($c['block']) {
						$c['block'] = IPSText::getTextClass('bbcode')->preDisplayParse($c['block']);
					}
				}
				if (!$group['promenu_groups_arrows_enabled']) {
					$c['arrow'] = '';
				}
	
				if (!$this->memberData['language'] && $group['promenu_groups_enable_alternate_lang_strings']) {
					foreach ($this->caches['lang_data'] as $ks => $cs) {
						if ($cs['lang_default']) {
							$lang['lang_default'] = $cs['lang_id'];
							break;
						}
					}
				} else {
					if ($group['promenu_groups_enable_alternate_lang_strings']) {
						$lang['lang_default'] = $this->memberData['language'];
					} else {
						foreach ($this->caches['lang_data'] as $ks => $cs) {
							if ($cs['lang_default']) {
								$lang['lang_default'] = $cs['lang_id'];
								break;
							}
						}
					}
				}
	
				foreach ($c['name'] as $ks => $cs) {
					if ($cs) {
						$default = $cs;
						break;
					}
				}
	
				$c['name'] = $c['name'][$lang['lang_default']];
	
				if (!$c['name']) {
					$c['name'] = $default;
				}
	
				if (count($c['desc']) && is_array($c['desc'])) {
					foreach ($c['desc'] as $ks => $cs) {
						if ($cs) {
							$default = $cs;
							break;
						}
					}
	
					$c['desc'] = $c['desc'][$lang['lang_default']];
	
					if (!$c['desc']) {
						$c['desc'] = $default;
					}
	
					$c['attr']['attr'] = preg_replace("/{promenu_desc}/i", $c['desc'], $c['attr']['attr']);
	
					$c['attr']['attr'] = preg_replace("/{promenu_title}/i", $c['name'], $c['attr']['attr']);
				}
	
				if ($preview) {
					unset($c['url']);
				}
	
				if ($c['icon_check']) {
					$c['icon'] = $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($c['icon'])));
				}
	
				if ($c['img_as_title_check']) {
					$c['title_icon'] = $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($c['title_icon'])));
				}
	
				$data[$parent][$c['id']] = $c;
	
				if ($c['has_sub']) {
					$data = self::ParseMenus($data, $c['id'], $group, $preview);
				}
	
			} else {
				unset($data[$parent][$c['id']]);
			}
		}
	
		return $data;
	}

	/**
	 * childCheck
	 * ugh perms/group permission check for parents/children to see if current user can see anything
	 * @param array $data
	 * @param array $group
	 * @return bool
	 */
	protected function childCheck($data, $group) {

		$return = false;

		if (count($data) && is_array($data)) {

			foreach ($data as $k => $c) {
				if ($this->accessCheck($c, $group)) {
					$return = true;
					break;
				}
			}
		}

		return $return;
	}

	/**
	 * accessCheck
	 * can they view it?
	 * @param array $data
	 * @param array $group
	 * @return bool
	 */
	protected function accessCheck($data, $group) {

		if ($group['promenu_groups_group_visibility_enabled']) {
			$return = false;
			if (!$data['override']) {
				if ($this->GroupCheck(explode(",", $data['view']))) {
					$return = true;
				}
			}
		} else if (!$group['promenu_groups_group_visibility_enabled']) {
			if ($this->registry->permissions->check('view', $data)) {
				$return = true;
			}
		}

		return $return;
	}

	/**
	 * GroupCheck
	 * checks group perms, similar to isingroup from IPS, but with a bit of a twist.
	 * @param array $data
	 * @return bool
	 */
	protected function GroupCheck($data) {

		$group = $this->memberData['mgroup_others'] ? explode(",", $this->memberData['mgroup_others']) : array();

		$return = true;
		$cheese = true;

		if (count($group) && is_array($group)) {
			foreach ($group as $k) {
				if (in_array($k, $data)) {
					$return = false;
					$cheese = false;
				}
			}
		} else {
			$cheese = false;
		}

		if (!$cheese) {
			if (in_array($this->memberData['member_group_id'], $data)) {
				$return = false;
			} else {
				$return = true;
			}
		}
		return $return;
	}

	/**
	 * CanItouch
	 * checks to see if a touch device
	 * @return bool
	 */
	public function CanItouch() {
		//return TRUE;
		if ($this->registry->output->isLargeTouchDevice()) {
			return TRUE;
		} else if ($this->registry->output->isSmallTouchDevice()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * BuildDefaultMenus
	 * builds a default list of menu items from the app list.
	 * @param array $group
	 * @param int $new
	 * @return @e void
	 */
	public function BuildDefaultMenus($group) {
		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $classToLoad($this->registry);
	
		$c = $this->DB->buildAndFetch(array('select' => 'promenu_menus_order', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=0 AND promenu_menus_group="'.$group.'"','order' => 'promenu_menus_order DESC'));
		
		$i = $c['promenu_menus_order'] + 1;
		
		foreach ($this->cache->getCache('app_cache') as $app => $item) {
                    if($item['app_public_title']){
			foreach ($this->caches['lang_data'] as $k => $c) {
				$appname[$c['lang_id']] = $item['app_public_title'];
			}

			$name = serialize($appname);

			$app = $item['app_directory'];


			if ($app != "core" && $app != "promenu") {
					if($item['app_enabled']){
						if($item['app_hide_tab'])
						{
							$override = 1;
						}
						else{
							$override = 0;
						}
					}
					else{
						$override = 1;
					}				
				$save = array(
					'promenu_menus_name' => $name,
					'promenu_menus_parent_id' => 0,
					'promenu_menus_img_as_title_check' => 0,
					'promenu_menus_title_icon' => 0,
					'promenu_menus_img_as_title_w' => 14,
					'promenu_menus_img_as_title_h' => 14,
					'promenu_menus_desc' => '',
					'promenu_menus_icon' => 0,
					'promenu_menus_icon_check' => 0,
					'promenu_menus_icon_w' => 14,
					'promenu_menus_icon_h' => 14,
					'promenu_menus_group' => $group,
					'promenu_menus_link_type' => 'app',
					'promenu_menus_url' => NULL,
					'promenu_menus_app_link' => $app,
					'promenu_menus_forums_attatch' => 0,
					'promenu_menus_forums_parent_id' => 0,
					'promenu_menus_forums_id' => 0,
					'promenu_menus_forums_seo' => 0,
					'promenu_menus_block' => NULL,
					'promenu_menus_content_link' => 0,
					'promenu_menus_attr' => NULL,
					'promenu_menus_override' => $override,
					'promenu_menus_view' => implode(",", $item['app_tab_groups']),
					'promenu_menus_order' => $i,
					'promenu_menus_has_sub' => 0,
					'promenu_menus_is_open' => 0,
					'promenu_menus_is_mega' => 0,
					'promenu_menus_mega_column_count' => 3);

				$i++;
				
				if($save['promenu_menus_app_link'] === "ccs")
				{
					$a = $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => 'page_seo_name="'.$this->settings['ccs_default_page'].'"' ) );
					$save['promenu_menus_content_link'] = $a['page_id'];
				}
				
				if($save['promenu_menus_app_link'] == "easypages")
				{
					$a = $this->DB->buildAndFetch( array( 'select' => 'page_key', 'from' => 'ep_pages', 'order' => 'page_id ASC') );
					$save['promenu_menus_easy_link'] = $a['page_key'];				
				}
				
				$this->DB->insert('promenuplus_menus', $save);

				$ids = $this->DB->getInsertId();

				if (!$item['app_hide_tab']) {
					$save['perms'] = $this->group2perm($item['app_tab_groups']);
					if(!$item['app_enabled']) {
						$save['perms']['view'] = '';
					}
				}

				$perms->savePermissionsMatrix($ids, $save['perms']);
			}
                    }
		}
		$this->kerching();
	}

	/**
	 * BuildDefaultMenus
	 * builds a default list of menu items from the app list.
	 * @param array $group
	 * @param int $new
	 * @return @e void
	 */
	public function buildMissingApps($group) {
		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $classToLoad($this->registry);
	
		$c = $this->DB->buildAndFetch(array('select' => 'promenu_menus_order', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=0 AND promenu_menus_group="'.$group.'"','order' => 'promenu_menus_order DESC'));
		
		$i = $c['promenu_menus_order'] + 1;
		
		foreach ($this->cache->getCache('app_cache') as $app => $item) {
			$d = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_link_type="app" AND promenu_menus_app_link="'.$app.'" AND promenu_menus_group="'.$group.'"'));
			if(!count($d) && !is_array($d)){
                            if($item['app_public_title']){
				foreach ($this->caches['lang_data'] as $k => $c) {
					$appname[$c['lang_id']] = $item['app_public_title'];
				}
	
				$name = serialize($appname);

				if ($app != "core" && $app != "promenu") {
					 //!$item['app_hide_tab'] ? $item['app_enabled'] ? 0 : 1 : !$item['app_enabled'] ? 1 : 0,
					if($item['app_enabled']){
						if($item['app_hide_tab'])
						{
							$override = 1;
						}
						else{
							$override = 0;
						}
					}
					else{
						$override = 1;
					}

					$save = array(
						'promenu_menus_name' => $name,
						'promenu_menus_parent_id' => 0,
						'promenu_menus_img_as_title_check' => 0,
						'promenu_menus_title_icon' => 0,
						'promenu_menus_img_as_title_w' => 14,
						'promenu_menus_img_as_title_h' => 14,
						'promenu_menus_desc' => '',
						'promenu_menus_icon' => 0,
						'promenu_menus_icon_check' => 0,
						'promenu_menus_icon_w' => 14,
						'promenu_menus_icon_h' => 14,
						'promenu_menus_group' => $group,
						'promenu_menus_link_type' => 'app',
						'promenu_menus_url' => NULL,
						'promenu_menus_app_link' => $app,
						'promenu_menus_forums_attatch' => 0,
						'promenu_menus_forums_parent_id' => 0,
						'promenu_menus_forums_id' => 0,
						'promenu_menus_forums_seo' => 0,
						'promenu_menus_block' => NULL,
						'promenu_menus_content_link' => 0,
						'promenu_menus_attr' => NULL,
						'promenu_menus_override' => $override,
						'promenu_menus_view' => implode(",", $item['app_tab_groups']),
						'promenu_menus_order' => $i,
						'promenu_menus_has_sub' => 0,
						'promenu_menus_is_open' => 0,
						'promenu_menus_is_mega' => 0,
						'promenu_menus_mega_column_count' => 3);
	
					$i++;
					
					if($save['promenu_menus_app_link'] === "ccs")
					{
						$a = $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => 'page_seo_name="'.$this->settings['ccs_default_page'].'"' ) );
						$save['promenu_menus_content_link'] = $a['page_id'];
					}
					
					if($save['promenu_menus_app_link'] == "easypages")
					{
						$a = $this->DB->buildAndFetch( array( 'select' => 'page_key', 'from' => 'ep_pages', 'order' => 'page_id ASC') );
						$save['promenu_menus_easy_link'] = $a['page_key'];				
					}
					
					$this->DB->insert('promenuplus_menus', $save);
	
					$ids = $this->DB->getInsertId();
	
					if (!$item['app_hide_tab']) {
						$save['perms'] = $this->group2perm($item['app_tab_groups']);
						if(!$item['app_enabled']) {
							$save['perms']['view'] = '';
						}
					}
	
					$perms->savePermissionsMatrix($ids, $save['perms']);
				}
                            }
			}
		}
		$this->kerching();
	}
	/**
	 * perm2group
	 * takes permissions and converts them to group visibility
	 * @param array $perms
	 * @return mixed
	 */
	public function perm2group($perms) {
		$return = '';
		
		if($perms == "*")
		{
			return $return;
		}
		
		$perms = explode(',', $perms);
		foreach ($this->caches['group_cache'] as $k => $c) {
			if (!in_array($c['g_perm_id'], $perms)) {
				$return[] = $c['g_id'];
			}
		}

		if (count($return) && is_array($return)) {
			return implode(',', $return);
		}		
	}

	public function perm2groupArray($perms)
	{
		$return = '';
		
		if(count($perms) && is_array($perms))
		{
			foreach($perms as $k => $v)
			{
				if($k == "*")
				{
					return "*";
				}
				$return .= ",".$k;
			}
		}
		
		return $return;
	}
	/**
	 * group2perm
	 * takes group visibility and converts to permissions visibility
	 * @param array $groups
	 * @return array
	 */
	public function group2perm($groups) {
		$perm = array();

		if (count($groups) && is_array($groups)) {
			$i = 0;
			foreach ($this->caches['group_cache'] as $k => $c) {
				if (!in_array($c['g_perm_id'], $groups)) {
					$perm['menuview'][$c['g_id']] = 1;
					$i++;
				}
			}

			if (count($this->caches['group_cache']) == $i) {
				$perm['menuview']['*'] = 1;
			}
		} else {
			$perm['menuview']["*"] = 1;
			foreach ($this->caches['group_cache'] as $k => $c) {
				$perm['menuview'][$c['g_id']] = 1;
				$i++;
			}
		}
		return $perm;
	}

	/**
	 * GetCss
	 * gets basic css for the ajax menus
	 * @return string
	 */
	public function GetCss() {
		$style = '';
		$css = '';
		$cssm = '';

		$css = $this->GetContent(DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . "/style_css/" . $this->registry->output->skin['_csscacheid'] . "/ipb_styles.css");

		preg_match_all('/#community_app_menu(.*?){([^}]+)?/is', $css, $cssm);

		foreach ($cssm[0] as $k => $st) {
			$style .= $st . "}\n";
		}
		preg_match_all('/#primary_nav(.*?){([^}]+)?/is', $css, $cssmm);

		foreach ($cssmm[0] as $k => $st) {
			$style .= $st . "}\n";
		}
		preg_match_all('/.main_width(.*?){([^}]+)?/is', $css, $cssmm);

		foreach ($cssmm[0] as $k => $st) {
			$style .= $st . "}\n";
		}
		return $style;
	}

	public function getHookData($file){
		$c = $this->GetContent(IPSLib::getAppDir("promenu")."/sources/creation/".$file);
		
		return $c;
	}
	
	public function replaceFirst($input, $search, $replacement){
	    $pos = stripos($input, $search);
	    if($pos === false){
	        return $input;
	    }
	    else{
	        $result = substr_replace($input, $replacement, $pos, strlen($search));
	        return $result;
	    }
	}
	
	public function replaceLast($search, $replace, $subject)
	{
	    $pos = strrpos($subject, $search);
	
	    if($pos !== false)
	    {
	        $subject = substr_replace($subject, $replace, $pos, strlen($search));
	    }
	
	    return $subject;
	}
	
	/**
	 * contentPages 
	 *  if Content is installed, will build the "pages for drop down"
	 *  @param string $cur if there is a current on a edit, displays it.
	 *  @return	string
	 */
	public function contentPages($cur = '') {
		$pages = array();

		$this->DB->build(array('select' => 'page_name, page_folder, page_seo_name, page_id, page_omit_filename, page_view_perms',
			'from' => 'ccs_pages',
			'order' => 'page_folder, page_seo_name'));

		$_page = $this->DB->execute();

		$pages[] = array(0, " --- {$this->lang->words['promenu_word_none']} --- ");

		while ($pa = $this->DB->fetch($_page)) {
			if ($pa['page_folder']) {
				$pages[] = array($pa['page_id'], $pa['page_folder'] . '/' . $pa['page_seo_name']);
			} else {
				$pages[] = array($pa['page_id'], $pa['page_seo_name']);
			}
		}
		return $this->registry->output->formDropdown("promenu_menus_content_link", $pages, $cur);
	}	
	
	/**
	 * getEasyPages
	 * @param string $cur 
	 * @return string
	 */
	public function getEasyPages($cur = '') {
		$easypages = array();

		$this->DB->build(array('select' => 'page_id, page_title, page_key',
			'from' => 'ep_pages',
			'order' => 'page_title'
		));
		$_page = $this->DB->execute();

		$easypages[] = array(0, " --- {$this->lang->words['promenu_word_none']} --- ");

		while ($pa = $this->DB->fetch($_page)) {
			$easypages[] = array($pa['page_key'], $pa['page_title']);
		}
		return $this->registry->output->formDropdown("promenu_menus_easy_link", $easypages, $cur);
	}

	public function getDatabaseContent($cur = ''){
		$cache = $this->cache->getCache('ccs_databases');
		$db[] = array('',"Select One");
		if(is_array($cache) && count($cache)){
			foreach($cache as $k => $v){
				$db[] = array($v['database_id'],$v['database_name']);
			}
		}
		
		return $this->registry->output->formDropdown("promenu_menus_content_link", $db, $cur);
	}
	
	public function getClass($class = '') {
		switch ($class) {
			case 'postClassPromenu':
				if (!$this->registry->isClassLoaded('postClassPromenu')) {
					$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/post.php', 'postClassPromenu', 'promenu');
					$this->registry->setClass('postClassPromenu', new $classToLoad($this->registry));
				}
				break;
		}
		return $this->registry->getClass($class);
	}
	/**
	 * BuildForumChildren
	 * gathers forums, and dumps them into the menus table as menu items
	 * @param int $parent 
	 * @param array $group
	 * @param string|int $pid
	 * @return	@e void
	 */
	public function BuildForumChildren($parent, $group, $pid = 'root') {
		$ids = '';
		$fc  = '';
		
		if (!$this->registry->isClassLoaded('class_forums')) {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('forums') . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums');

			$this->registry->setClass('class_forums', new $classToLoad($this->registry));
		}
		$this->registry->getClass('class_forums')->forumsInit();

		$permsToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $permsToLoad($this->registry);

		$fc = $this->registry->class_forums->forum_cache;

		$i = 1;
		
		$langer = $this->caches['lang_data'];

		foreach ( $fc[$pid] as $k => $c ) {

			foreach ( $langer as $ks => $cs ) {
				$lang[$cs['lang_id']] = $c['name'];
				if ($c['description']) {
					$langdes[$cs['lang_id']] = $c['description'];
				}
			}

			$a['promenu_menus_name'] = serialize($lang);
			$a['promenu_menus_parent_id'] = $parent;
			if ( $cs['description'] ) {
				$a['promenu_menus_desc'] = serialize($langdes);
			}
			$a['promenu_menus_group'] = $group;
			$a['promenu_menus_link_type'] = "man";
			$a['promenu_menus_url'] = 'showforum=' . $c['id'];
			$a['promenu_menus_view'] = $this->perm2group($c['perm_view']);
			$a['promenu_menus_order'] = $i;
			$a['promenu_menus_forums_id'] = $c['id'];

			if ($pid == 'root') {
				$a['promenu_menus_forums_parent_id'] = -1;
			} else {
				$a['promenu_menus_forums_parent_id'] = $pid;
			}
			$a['promenu_menus_forums_seo'] = $c['name_seo'];

			if (count($fc[$c['id']]) && is_array($fc[$c['id']])) {
				$a['promenu_menus_has_sub'] = 1;
			} else {
				$a['promenu_menus_has_sub'] = 0;
			}

			$a['promenu_menus_is_open'] = 0;

			$cd = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_forums_id=' . $c['id'] . ' AND promenu_menus_parent_id=' . $parent ));

			if ( count( $cd ) && is_array( $cd ) ) {
				$ids = $cd['promenu_menus_id'];
			} else {
				$this->DB->insert("promenuplus_menus", $a);

				$ids = $this->DB->getInsertId();

				$p = $this->group2perm(explode(",", $a['promenu_menus_view']));

				$perms->savePermissionsMatrix($ids, $p);

				$this->DB->update("promenuplus_menus", array("promenu_menus_has_sub" => 1), 'promenu_menus_id=' . $parent);
								
			}

			$i++;

			if ($a['promenu_menus_has_sub']) {
				self::BuildForumChildren($ids, $group, $c['id']);
			}
		}
	}	
	public function buildDatabases($id,$group,$db,$parent=0){
	
		$permsToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $permsToLoad($this->registry);

		//$fc = $this->registry->class_forums->forum_cache;
		$fc = $this->DB->buildAndFetchAll( array( 
													'select' => "*",
													'from' => "ccs_database_categories",
													'where' => "category_database_id={$db} AND category_parent_id={$parent}"
		) );
													
		$dbPerms =  $this->cache->getCache('ccs_databases');
		$i = 1;
		
		$langer = $this->caches['lang_data'];
		if (!$this->registry->isClassLoaded('ccsFunctions')) {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
			$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
		}
		
		foreach ( $fc as $k => $c ) {

			foreach ( $langer as $ks => $cs ) {
				$lang[$cs['lang_id']] = $c['category_name'];
				if ($c['category_description']) {
					$langdes[$cs['lang_id']] = $c['category_description'];
				}
			}
			
			if(!$c['category_has_perms']){
				$c['perm_view'] = $dbPerms[$db]['perm_view'];
			}
			else{
				$fff = $this->DB->buildAndFetch( array( 
																	'select' => "*",
																	'from' => "permission_index",
																	'where' => "app='ccs' AND perm_type='categories' AND perm_type_id={$c['category_id']}"
																	
				) );
				$c['perm_view'] = $fff['perm_view'];
			}
			
			$a['promenu_menus_name'] = serialize($lang);
			$a['promenu_menus_parent_id'] = $id;
			if ( $cs['description'] ) {
				$a['promenu_menus_desc'] = serialize($langdes);
			}
			$a['promenu_menus_group'] = $group;
			$a['promenu_menus_link_type'] = "man";
			$a['promenu_menus_url'] = $c['category_id'];
			$a['promenu_menus_view'] = $this->perm2group($c['perm_view']);
			$a['promenu_menus_order'] = $i;
			$a['promenu_menus_forums_id'] = $c['category_id'];
			$a['promenu_menus_app_link'] = "ccsDB2";
			$a['promenu_menus_forums_parent_id'] = $db;
		
			//$a['promenu_menus_forums_seo'] = $c['name_seo'];

			$a['promenu_menus_is_open'] = 0;
			$child = $this->DB->buildAndFetchAll( array( 'select' => "*", 'from' => "ccs_database_categories",'where' => "category_database_id={$db} AND category_parent_id={$c['category_id']}" ) );
			if (count($child) && is_array($child)) {
				$a['promenu_menus_has_sub'] = 1;
			} else {
				$a['promenu_menus_has_sub'] = 0;
			}
			$cd = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_forums_id=' . $c['category_id'] . ' AND promenu_menus_parent_id=' . $id ));

			if ( count( $cd ) && is_array( $cd ) ) {
				$this->DB->update( "promenuplus_menus", $a, 'promenu_menus_id=' . $cd['promenu_menus_id'] );
				
				$ids = $cd['promenu_menus_id'];
				
				$p = $this->group2perm(explode(",", $a['promenu_menus_view']));

				$perms->savePermissionsMatrix($ids, $p);
			} else {
				$this->DB->insert("promenuplus_menus", $a);

				$ids = $this->DB->getInsertId();

				$p = $this->group2perm(explode(",", $a['promenu_menus_view']));

				$perms->savePermissionsMatrix($ids, $p);

				$this->DB->update("promenuplus_menus", array("promenu_menus_has_sub" => 1), 'promenu_menus_id=' . $id);
			}

			$i++;
			if ($a['promenu_menus_has_sub']) {
				self::buildDatabases($ids, $group, $db,$c['category_id']);
			}
		}	
	}
}