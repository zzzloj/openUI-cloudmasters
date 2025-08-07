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

		$js = <<<EOF
		Step:{$group['promenu_groups_border']},MenuBehavior: {$group['promenu_groups_behavoir']},OpenSpeed: {$group['promenu_groups_speed_open']}, CloseSpeed: {$group['promenu_groups_speed_close']}, OpenAnimation: '{$group['promenu_groups_animation_open']}', CloseAnimation: '{$group['promenu_groups_animation_close']}', TopOffSet: {$group['promenu_groups_top_offset']}, HoverSensitivity: {$group['promenu_groups_hover_speed']}, zindex: {$group['promenu_groups_zindex']}
EOF;
		if ($this->registry->profunctions->CanItouch() && !$this->settings['promenu_disable_tab_pop']) {
			$group['promenu_groups_behavoir'] = 3;

			$js = <<<EOF
			Step:{$group['promenu_groups_border']},isTouch : 1,MenuBehavior: {$group['promenu_groups_behavoir']},OpenSpeed: {$group['promenu_groups_speed_open']}, CloseSpeed: {$group['promenu_groups_speed_close']}, OpenAnimation: '{$group['promenu_groups_animation_open']}', CloseAnimation: '{$group['promenu_groups_animation_close']}', TopOffSet: {$group['promenu_groups_top_offset']}, HoverSensitivity: {$group['promenu_groups_hover_speed']}, zindex: {$group['promenu_groups_zindex']}
EOF;
		}

		if (count($menu[0]) && is_array($menu[0])) {
			foreach ($menu[0] as $k => $c) {
				if ($group['promenu_groups_name'] !== "mobile") {
					$html .= $this->registry->output->getTemplate('promenu_plus')->$settings['template']($c, $menu, $group);
				} else {
					$html .= $this->registry->output->getTemplate('promenu_plus')->proMain($c, $group);
				}
			}
		}

		if ($settings['jsMenuEnabled']) {
			$html .=<<<EOF
			<script>
				projQ(document).ready(function() {
					projQ("#{$settings['ulID']},#more_apps_menucontent").ProActivation({ Activation: {$group['promenu_groups_tab_activation']}}).ProMenuJs({ {$js} });
				});
			</script>
EOF;
		}

		return $html;
	}

	/**
	 * Jquery_load
	 * loads all the needed information for initing jquery
	 * @return string
	 */
	public function Jquery_load() {
		$output = '';

		if (IPSLib::appIsInstalled('promenu')) {
			$output .= $this->registry->output->getTemplate('promenu_plus')->proJS();

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

		$skin = $this->caches['skinsets'][$this->registry->output->skin['set_id']]['set_key'];

		if ($primary['promenu_groups_enabled'] && !in_array( $this->registry->output->skin['set_id'], explode(",", $primary['promenu_groups_hide_skin'] ) ) || $skin == "mobile" && $cache['groups']['mobile']['promenu_groups_enabled']) {
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
				
		// if(!$css['import'][$this->settings['css_base_url'] . "style_css/" . $this->registry->output->skin['_csscacheid'] . "/promenu_plus_theme.css"])
		// {
			// if($this->registry->output->skin['_css']['promenu_plus_theme']){
				// $skinData = $this->registry->output->skin['_css']['promenu_plus_theme']['attributes'];
				// $css['import'][$this->settings['css_base_url'] . "style_css/" . $this->registry->output->skin['_csscacheid'] . "/promenu_plus_theme.css"] = array('attributes' => 'title="Main" media="'.$skinData.'"', 'content' => $this->settings['css_base_url'] . "style_css/" . $this->registry->output->skin['_csscacheid'] . "/promenu_plus_theme.css");
			// }	
		// }
		
		//print_r($this->registry->output->skin);
		
		
		$output['css'] = $css;

		if ($this->settings['promenu_disable_more'] && $skin !== "mobile") {
			$documentHeadItems['raw'][] = "<script type='text/javascript'>
				ipb.global.activateMainMenu = function() {};
			</script>";
		}

		$output['document'] = $documentHeadItems;

		/**
		 * no removal zone begin. 
		 * any tampering or removal of this is prohibited without express permission of Robert Simons or Michael S. Edwards
		 * if you wish this to be removed, contact Robert Simons @ robertsimons@provisionists.com or Michael S. Edwards at
		 * codingjungle@gmail.com. there is an additional fee for the removal.
		 * if this is removed without permission, it voids your warranty, and we will offer no support, and possible action 
		 * will be taken to remove you from recieving further updates.
		 */
		 
		$version = $this->settings['ipb_display_version'] ? "&nbsp;".$this->caches['app_cache']['promenu']['app_version'] : "";
		 
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
				<p id='copyright'>
					<a id="copyright_promenu" style="display: inline-block;position: relative;margin-top: -5px;" rel="nofollow" href="#" title="{$this->lang->words['promenu_created_by']} Provisionists &amp; CodingJungle">
						Menu Software by ProMenu{$version}
					</a>
				</p>
				<ul id="copyright_menucontent" class="ipbmenu_content" style="display: none; position: absolute; z-index: 9999;">
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="http://codingjungle.com" style="z-index: 10000;">CodingJungle</a>
						</p>
					</li>
					<li style="z-index: 10000;">
						<p id='copyright'>
							<a href="http://provisionists.com" style="z-index: 10000;">Provisionists</a>
						</p>
					</li>
				</ul>
			</li>
		</ul>
		<script>
			new ipb.Menu( $('copyright_promenu'), $('copyright_menucontent') );
		</script>
EOF;

		$footer_items['copyright'] = $footer_items['copyright'] . $c;

		$output['footer_items'] = $footer_items;
		//no removal zone end

		if (count($header) && is_array($header) && !in_array($this->registry->output->skin['set_id'], explode(",", $headers['promenu_groups_hide_skin'])) && $skin !== "mobile") {
			$output['header_enabled'] = $headers['promenu_groups_enabled'];
		}

		return $output;
	}

}