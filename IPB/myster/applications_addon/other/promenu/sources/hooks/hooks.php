<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class promenuHooks {

	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	protected $menu;
	protected $pro;

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct(ipsRegistry $registry) {
		$this->registry = $registry;
		$this->lang = $this->registry->getClass('class_localization');
		$this->DB = $this->registry->DB();
		$this->settings = &$this->registry->fetchSettings();
		$this->request = &$this->registry->fetchRequest();
		$this->member = $this->registry->member();
		$this->memberData = &$this->registry->member()->fetchMemberData();
		$this->cache = $this->registry->cache();
		$this->caches = & $this->registry->cache()->fetchCaches();
		$this->pro = $this->registry->profunctions;
	}

	/**
	 * menus
	 * builds the html for the menus on the front end
	 * @param array $settings
	 * @return string
	 */
	public function menus($settings) {
		$cache = $settings['cache'];

		$group = $cache['groups'];

		$menu = $cache['menus'];

		$preview = $settings['preview'] ? 1 : 0;

		$okay = $this->pro->okie ? $this->pro->okie : 0;

		$js = <<<EOF
		docking:{$group['promenu_groups_allow_docking']},preview:{$preview},group:"{$group['promenu_groups_name']}",more:{$group['promenu_groups_promore']},template:"{$group['promenu_groups_template']}",Step:{$group['promenu_groups_border']},MenuBehavior: {$group['promenu_groups_behavoir']},OpenSpeed: {$group['promenu_groups_speed_open']}, CloseSpeed: {$group['promenu_groups_speed_close']}, OpenAnimation: '{$group['promenu_groups_animation_open']}', CloseAnimation: '{$group['promenu_groups_animation_close']}',Okie:{$okay}, TopOffSet: {$group['promenu_groups_top_offset']}, HoverSensitivity: {$group['promenu_groups_hover_speed']}, zindex: {$group['promenu_groups_zindex']}
EOF;
		if ($this->pro->CanItouch() && !$this->settings['promenu_disable_tab_pop']) {
			$group['promenu_groups_behavoir'] = 3;

			$js = <<<EOF
			docking:{$group['promenu_groups_allow_docking']},preview:{$preview},group:"{$group['promenu_groups_name']}",more:{$group['promenu_groups_promore']},template:"{$group['promenu_groups_template']}",Step:{$group['promenu_groups_border']},isTouch : 1,MenuBehavior: {$group['promenu_groups_behavoir']},OpenSpeed: {$group['promenu_groups_speed_open']}, CloseSpeed: {$group['promenu_groups_speed_close']}, OpenAnimation: '{$group['promenu_groups_animation_open']}', CloseAnimation: '{$group['promenu_groups_animation_close']}',Okie:{$okay}, TopOffSet: {$group['promenu_groups_top_offset']}, HoverSensitivity: {$group['promenu_groups_hover_speed']}, zindex: {$group['promenu_groups_zindex']}
EOF;
		}

		if (count($menu[0]) && is_array($menu[0])) {
			foreach ($menu[0] as $k => $c) {
				if ($group['promenu_groups_name'] !== "mobile" ) {
					$html .= $this->registry->output->getTemplate('promenu_plus')->$settings['template']($c, $menu, $group);
				} else {
					$html .= $this->registry->output->getTemplate('promenu_plus')->proMain($c, $menu, $group);
				}
			}
		}
		$html .=<<<EOF
			<li id="promore_other_apps_{$group['promenu_groups_name']}" style="display:none;">
				<a id="cpromenu_" class="downarrow"><span style="padding-right:15px;background-origin:padding-box!important;">{$this->lang->words['promenu_promore']}</span></a>
				<div class='boxShadow baseRoot' style='display: none; position: absolute;'><ul id='moreChild{$group['promenu_groups_name']}'></ul></div>
			</li>
EOF;
		if ($settings['jsMenuEnabled']  && !intval($temp)) {
			if($group['promenu_groups_name'] !== "mobile"){
			$html .=<<<EOF
			<script>
				projQ(document).ready(function() {
					projQ("#{$settings['ulID']}").ProActivation({ Activation: {$group['promenu_groups_tab_activation']}}).ProMenuJs({ {$js} });
				});
			</script>
EOF;
			}
			else if(!intval(!$temp)){
			$html .=<<<EOF
			<script>
				projQ(document).ready(function() {
					projQ("#{$settings['ulID']}").ProMenuJs();
				});
			</script>
EOF;
			}
		}

		if ($group['promenu_groups_add_other'] && !$group['promenu_groups_make_super']) {
			if (!(!$this->memberData['member_id'] && $this->settings['force_login'] ) && !($this->settings['board_offline'] && !$this->memberData['g_access_offline']) && $this->memberData['g_view_board']) {

				$quickNav = $this->registry->output->buildSEOUrl("app=core&amp;module=global&amp;section=navigation&amp;inapp=" . IPS_APP_COMPONENT, 'public');
				$html .=<<<EOF
				<li class='right' id="nav_app_quick">
					<a href="{$quickNav}" rel="quickNavigation" accesskey='9' id='quickNavLaunch' title='{$this->lang->words['launch_quicknav']}'><span>&nbsp;</span></a>
				</li>		
EOF;
			}

			if ($this->registry->getCurrentApplication() != 'core' AND IPSLib::appIsSearchable($this->registry->getCurrentApplication())) {
				$app = $this->registry->getCurrentApplication();
			} else {
				$app = "forums";
			}
			$viewNew = $this->registry->output->buildSEOUrl("app=core&amp;module=search&amp;do=viewNewContent&amp;search_app=" . $app, 'public');
			if ($this->request['do'] === "viewNewContent") {
				$active = "active";
			}
			$html .=<<<EOF
				<li id='nav_explore' class='right {$active}'>
					<a href='{$viewNew}' accesskey='2'>{$this->lang->words['view_new_posts']}</a>
				</li>
EOF;
		}
		if ($group['promenu_groups_allow_docking']) {
			$html .=<<<EOF
				<li class="right" style="display:none;" id="proToTop">
					<a title='{$this->lang->words['go_to_top']}'><img src="{$this->settings['img_url']}/promenu/toTop.png"/></a>
				</li>		
EOF;
		}
		if ($preview) {
			$html .=<<<EOF
			 <div class="clearfix"></div>		
EOF;
		}
		
		
		if($group['promenu_groups_name'] !== "primary" && $group['promenu_groups_template'] === "proMain" || $group['promenu_groups_name'] !== "mobile" && $group['promenu_groups_template'] === "proMain"){
			$data['html'] = $html;
			$data['rhtml'] = $this->buildHookData($menu,$group);
		}
		else{
			$data = $html;
		}
		
		return $data;
	}
	/**
	 * Jquery_load
	 * loads all the needed information for initing jquery
	 * @return string
	 */
	public function Jquery_load($wrapper = 0) {
		$output = '';

		if (IPSLib::appIsInstalled('promenu')) {
			$output .= $this->registry->output->getTemplate('promenu_plus')->proJS($wrapper);
			return $output;
		}
	}

	public function buildHookData($menu,$group){
		if($group['promenu_groups_name'] === "mobile"){
			$group['promenu_groups_default_mobile'] = 3;
		}
		if($group['promenu_groups_default_mobile'] == 3){		
			
			//if($group['promenu_groups_static']){
			$newGroup = $group['promenu_groups_name'].'_menu';
		
			$menus = $this->buildResponsiveMenu(array('cache' => array('menus' => $menu,"group" => $group)),$group['promenu_groups_name'].'_responsive');
			if ($group['promenu_groups_add_other'] && !$group['promenu_groups_make_super']) {
				if (!(!$this->memberData['member_id'] && $this->settings['force_login'] ) && !($this->settings['board_offline'] && !$this->memberData['g_access_offline']) && $this->memberData['g_view_board']) {
					$quickNav = $this->registry->output->buildSEOUrl("app=core&amp;module=global&amp;section=navigation&amp;inapp=" . IPS_APP_COMPONENT, 'public');
					$menus .=<<<EOF
					<li id="nav_app_quick">
						<a href="{$quickNav}" rel="quickNavigation" accesskey='9' id='quickNavLaunch' title='{$this->lang->words['launch_quicknav']}'>
							<p style="width:30px;height:30px;display:inline-block"></p>
							{$this->lang->words['launch_quicknav']}
						</a>
					</li>		
EOF;
				}

				if ($this->registry->getCurrentApplication() != 'core' AND IPSLib::appIsSearchable($this->registry->getCurrentApplication())) {
					$app = $this->registry->getCurrentApplication();
				} else {
					$app = "forums";
				}
				$viewNew = $this->registry->output->buildSEOUrl("app=core&amp;module=search&amp;do=viewNewContent&amp;search_app=" . $app, 'public');
				if ($this->request['do'] === "viewNewContent") {
					$active = "active";
				}
				$menus .=<<<EOF
					<li id='nav_explore' class='{$active}'>
						<a href='{$viewNew}' accesskey='2'>
							<span style="width:30px;height:30px;display:inline-block"></span>
							{$this->lang->words['view_new_posts']}
						</a>
					</li>
EOF;
			}
    		$c = $this->registry->profunctions->getSingleMenu(intval($this->request['id']));
            if(intval($c['promenu_menus_wrapper_wrapped'])){
                return $this->registry->output->getTemplate('promenu_plus')->proResponsiveBody('wrapper_menu_responsive',$menus,$group,"wrapper_menu");
            }
            
			return $this->registry->output->getTemplate('promenu_plus')->proResponsiveBody($group['promenu_groups_name'].'_responsive',$menus,$group,$newGroup);		
		}
	}
	public function buildResponsiveMenu($data,$alt){
		$menu = $data['cache']['menus'];
		$group = $data['cache']['group'];
		$html = '';
		if(is_array($menu[0]) && count($menu[0])){
			foreach($menu[0] as $k => $v){
				$html .= $this->registry->output->getTemplate('promenu_plus')->proResponsive($v, $menu, $group,$alt);
			}
			if($group['promenu_groups_name'] === "mobile"){
				$html .= $this->registry->output->getTemplate('promenu_plus')->proUser();
			}
			return $html;
		}
	}
	
	public function responsivePrimary(){
            if(intval($this->settings['promenu_menus_kill_primary'])){
                return '';
            }
			$cache 	= $this->registry->profunctions->GetCaches('primary');

			if($cache['groups']['promenu_groups_default_mobile'] == 3){		
				$cache['menus'] = $this->registry->profunctions->ParseMenus($cache['menus'], 0, $cache['groups']);

				$menus = $this->buildResponsiveMenu(array('cache' => $cache),'primary_responsive');
				if (!(!$this->memberData['member_id'] && $this->settings['force_login'] ) && !($this->settings['board_offline'] && !$this->memberData['g_access_offline']) && $this->memberData['g_view_board']) {

					$quickNav = $this->registry->output->buildSEOUrl("app=core&amp;module=global&amp;section=navigation&amp;inapp=" . IPS_APP_COMPONENT, 'public');
					$menus .=<<<EOF
					<li id="nav_app_quick">
						<a href="{$quickNav}" rel="quickNavigation" accesskey='9' id='quickNavLaunch' title='{$this->lang->words['launch_quicknav']}'>
							<p style="width:30px;height:30px;display:inline-block"></p>
							{$this->lang->words['launch_quicknav']}
						</a>
					</li>		
EOF;
				}

				if ($this->registry->getCurrentApplication() != 'core' AND IPSLib::appIsSearchable($this->registry->getCurrentApplication())) {
					$app = $this->registry->getCurrentApplication();
				} else {
					$app = "forums";
				}
				$viewNew = $this->registry->output->buildSEOUrl("app=core&amp;module=search&amp;do=viewNewContent&amp;search_app=" . $app, 'public');
				if ($this->request['do'] === "viewNewContent") {
					$active = "active";
				}
				$menus .=<<<EOF
					<li id='nav_explore' class='{$active}'>
						<a href='{$viewNew}' accesskey='2'>
							<span style="width:30px;height:30px;display:inline-block"></span>
							{$this->lang->words['view_new_posts']}
						</a>
					</li>
EOF;
				$output .= $this->registry->output->getTemplate('promenu_plus')->proResponsiveBody('primary_responsive',$menus,$cache['groups'],'primary_nav');
			
				return $output;
			}
	}
	/**
	 * RemovalTool
	 * @param array $header_items
	 * @param array $footer_items
	 * @param array $documentHeadItems
	 * @param array $css
	 * @param string $app
	 * @return mixed
	 */
	public function RemovalTool($header_items, $footer_items, $documentHeadItems, $css, $app) {
		$cache = $this->registry->profunctions->GetCaches();

		$primary = $cache['groups']['primary'];

		$headers = $cache['groups']['header'];

		$header = $cache['menus']['header'];
		
		$valid = 0;
		
		$skin = $this->caches['skinsets'][$this->registry->output->skin['set_id']]['set_key'];
		
		if($this->settings['promenu_check_validity']){
			$boo = $cache['groups'];
			if(count($boo) && is_array($boo))
			{
				foreach($boo as $k => $v)
				{
					if( in_array( $this->registry->output->skin['set_id'], explode(",", $v['promenu_groups_hide_skin'] ) ) )
					{
						$valid =  1;
					}
					else{
						$valid = $v['promenu_groups_enabled'] ? 0 : 1;
						if($valid == 0)
						{
							break;
						}
					}
				}
			}
		}
		if ($primary['promenu_groups_enabled'] && !in_array($this->registry->output->skin['set_id'], explode(",", $primary['promenu_groups_hide_skin'])) || $skin == "mobile" && $cache['groups']['mobile']['promenu_groups_enabled']) {
			if ($this->caches['vnums']['long'] >= 33011) {
				unset($header_items['applications']);

				unset($header_items['primary_navigation_menu']);
			} else {
				ipsRegistry::$applications['forums']['app_hide_tab'] = true;
				ipsRegistry::$applications['members']['app_hide_tab'] = true;
				ipsRegistry::$applications['core']['app_hide_tab'] = true;
			}
		}

		$output['menu'] = $header_items;

		if ($app !== "ccs" && IPSLib::appIsInstalled('ccs') && $this->settings['promenu_disable_content_css'] && $skin !== "mobile") {
			$css['import'][$this->settings['css_base_url'] . "style_css/" . $this->registry->output->skin['_csscacheid'] . "/ipcontent.css"] = array('attributes' => 'title="Main" media="screen,print"', 'content' => $this->settings['css_base_url'] . "style_css/" . $this->registry->output->skin['_csscacheid'] . "/ipcontent.css");
		}

		$output['css'] = $css;
		

		
		$version = $this->settings['ipb_display_version'] ? "&nbsp;" . $this->caches['app_cache']['promenu']['app_version'] : "";
		$type = $this->pro->proPlus ? "Plus" : "Basic";
		$remove = $this->pro->proper ? 1 : $valid ? 1 : 0;
		if( !$remove ){
                    if(!$this->settings['promenu_text_cp']){
		$c = <<<EOF
		<style>
			#tt a.menu_active {
				background: transparent !important;
				padding: 0px !important;
				border: 0px !important;
			}
		</style>
		<ul id="tt" class="ipsList_inline right" style="padding-left:5px;">
			<li>
				<p id='copyrightPromenu'>
					<a id="copyright_promenu" style="display: inline-block;position: relative;margin-top: -5px;" rel="nofollow" href="#" title="">
						<img alt="" src="" />
					</a>
				</p>
				<ul id="copyright_menucontent" class="ipbmenu_content" style="display: none; position: absolute; z-index: 9999;">
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="" target="_blank" style="z-index: 10000;"></a>
						</p>
					</li>
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="" target="_blank" style="z-index: 10000;"></a>
						</p>
					</li>
				</ul>
			</li>
		</ul>
EOF;
			$documentHeadItems['raw'][] = <<<EOF
			<script type="text/javascript">
				document.observe("dom:loaded", function() {
					new ipb.Menu( $('copyright_promenu'), $('copyright_menucontent') );
				});
			</script>
EOF;
                      $footer_items['copyright'] = $c .$footer_items['copyright'];  
                    }
                    else{
		 
		$c = <<<EOF
		<style>
			#tt a.menu_active {
				background: transparent !important;
				padding: 0px !important;
				border: 0px !important;
			}
		</style>
		<ul id="tt" class="ipsList_inline right" >
			<li>
				<p id='copyrightPromenu'>
					<a id="copyright_promenu" style="display: inline-block;position: relative;margin-top: -5px;" rel="nofollow" href="#" title="">
                                                
					</a>
				</p>
				<ul id="copyright_menucontent" class="ipbmenu_content" style="display: none; position: absolute; z-index: 9999;">
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="" style="z-index: 10000;"></a>
						</p>
					</li>
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="" style="z-index: 10000;"></a>
						</p>
					</li>
				</ul>
			</li>
		</ul>
		<script>
			new ipb.Menu( $('copyright_promenu'), $('copyright_menucontent') );
		</script>
EOF;
                        $footer_items['copyright'] = $footer_items['copyright'].$c;
                    }
			
		}
		else{
			$footer_items['copyright'] = $footer_items['copyright'];
		}
		
		$documentHeadItems['raw'][] = <<<EOF
			<script type="text/javascript">
			ipb.global.activateMainMenu = function() {};
			</script>
EOF;

		$output['document'] = $documentHeadItems;
		$output['footer_items'] = $footer_items;
		//no removal zone end

		if (count($header) && is_array($header) && !in_array($this->registry->output->skin['set_id'], explode(",", $headers['promenu_groups_hide_skin'])) && $skin !== "mobile") {
			$output['header_enabled'] = $headers['promenu_groups_enabled'];
		}

		return $output;
	}

}