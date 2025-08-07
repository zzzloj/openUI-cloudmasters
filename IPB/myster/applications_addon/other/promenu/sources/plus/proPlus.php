<?php

class proPlus {

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

	/**
	 * Constructor
	 *
	 * @param   object      $registry       Registry object
	 * @return  @e void
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
		$this->caches = &$this->registry->cache()->fetchCaches();
	}

	/**
	 * ipcBlocksGet
	 * gets a list of available blocks from ipc.
	 * @return array
	 */
	public function ipcBlocksGet() {
		$addjoin = array(array('select' => 'p.container_name', 'from' => array('ccs_containers' => 'p'), 'where' => "p.container_type='block' AND p.container_id=m.block_category", 'type' => 'left'));

		$this->DB->build(array('select' => 'm.*', 'from' => array('ccs_blocks' => 'm'), 'where' => 'm.block_active=1', 'order' => 'm.block_category ASC, m.block_position ASC', 'add_join' => $addjoin));

		$q = $this->DB->execute();

		while ($b = $this->DB->fetch($q)) {
			$block[$b['container_name']][$b['block_id']] = $b;
		}
		return $block;
	}

	/**
	 * ipcBlockList
	 * builds the block list for click and insert into the text area
	 * @param string
	 * @return string
	 */
	public function ipcBlocksList($type = 'ccs') {
		if ($type === 'ccs') {
			$blocks = $this->ipcBlocksGet();

			$html .= <<<EOF
					<div style="width:350px;float:left;height:400px;overflow-y:scroll;">
EOF;
			if (count($blocks) && is_array($blocks)) {
				foreach ($blocks as $k => $c) {
					$html .= <<<EOF
				<ul>
					<li style="font-size: 14px;line-height: 1.6;padding: 4px;background-color: #DEE7F1;color: #454545;">
						{$k}
					</li>
EOF;
					foreach ($c as $ks => $cs) {
						$html .= <<<EOF
					<li class="tag_row ipsControlRow" title="{$cs['block_description']}">
						<ul class="ipsControlStrip">
							<li class="i_add"><a href="#" title="{$this->lang->words['promenu_insert_tag']}" class="insert_tag"  data-tag="{parse block=&quot;{$cs['block_key']}&quot;}">{$this->lang->words['promenu_insert_tag']}</a></li>
						</ul>
						<h5 style="font-size: 14px;color: black;font-weight: bold;">
							{$cs['block_name']}
						</h5>
						<p style="font-size: 11px;font-family: "Monaco", "Andale Mono", "Courier New", monospace;color: #5D5D5D;">
							{parse block="{$cs['block_key']}"}
						</p>
					</li>
EOF;
					}
					$html .= <<<EOF
				</ul>
EOF;
				}
			}

			$html .= <<<EOF

		</div>
EOF;
		} else {
			$blocks = $this->easyBlockGet();
			$html .= <<<EOF
			<div style="width:350px;float:left;height:400px;overflow-y:scroll;">
EOF;
			$html .= <<<EOF
				<ul>
					<li style="font-size: 14px;line-height: 1.6;padding: 4px;background-color: #DEE7F1;color: #454545;">
						Easy Page Blocks
					</li>
EOF;
			if (count($blocks) && is_array($blocks)) {
				foreach ($blocks as $ks => $cs) {
					$html .= <<<EOF
					<li class="tag_row ipsControlRow" title="{$cs['block_title']}">
						<ul class="ipsControlStrip">
							<li class="i_add"><a href="#" title="{$this->lang->words['promenu_insert_tag']}" class="insert_tag" data-tag="{parse static_block=&quot;{$cs['block_key']}&quot;}">{$this->lang->words['promenu_insert_tag']}</a></li>
						</ul>
						<h5 style="font-size: 14px;color: black;font-weight: bold;">
							{$cs['block_title']}
						</h5>
						<p style="font-size: 11px;font-family: "Monaco", "Andale Mono", "Courier New", monospace;color: #5D5D5D;">
							{parse static_block="{$cs['block_key']}"}
						</p>
					</li>
EOF;
				}
			}
			$html .= <<<EOF
				</ul>
		</div> 
EOF;
		}
		return $html;
	}

	/**
	 * easyBlockGet
	 * gets a list of available blocks from easypages
	 * @return array
	 */
	public function easyBlockGet() {
		$this->DB->build(array('select' => '*', 'from' => 'ep_blocks', 'order' => 'block_title ASC'));

		$q = $this->DB->execute();

		while ($b = $this->DB->fetch($q)) {
			$block[] = $b;
		}
		return $block;
	}

	/**
	 * parseCblock
	 * parses the content blocks for the front end template
	 * @param string $data contains the content for promenu_menus_block
	 * @return string
	 */
	public function parseCblock($data) {
		if (IPSLib::appIsInstalled('ccs')) {
			$content = $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($data)));

			preg_match_all('#\{parse block=\"(.+?)\"\}#', $content, $ccs);

			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs');

			$pageBuilder = new $classToLoad($this->registry);

			$pageBuilder->loadSkinFile();

			$this->lang->loadlanguageFile(array('public_lang'), "ccs");

			foreach ($ccs[1] as $index => $key) {
				$content = str_replace($ccs[0][$index], $pageBuilder->getBlock($key), $content);
			}
		} else {
			$content = '';
		}
		return $content;
	}

	/**
	 * parseEblock
	 * builds the output for easypage blocks
	 * @param string
	 * @return string
	 */
	public function parseEblock($data) {
		if (IPSLib::appIsInstalled('easypages')) {
			$content = $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($data)));

			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('easypages') . "/sources/classes/common.php", 'sldEasyPages_common', 'easypages');

			$common = new $classToLoad($this->registry);

			$content = $common->parseStaticBlocks($content);
		} else {
			$content = '';
		}
		return $content;
	}

	/**
	 * parsePHP
	 * does what is says, eval's php for the front end...yay!
	 * @param $iiasdfkjlasdfkk the php data, funky name to prevent any thing funky going on with eval data
	 * @return string
	 */
	public function parsePHP($iiasdfkjlasdfkk) {
		ob_start();
		eval($iiasdfkjlasdfkk);
		$iiasdfkjlasdfkk = ob_get_contents();
		ob_end_clean();
		return $iiasdfkjlasdfkk;
	}

	/**
	 * Iteval
	 * makes sure the php being saved is valid!
	 * @param string php being checked
	 * @return bool
	 */
	public function Iteval($iiasdfkjlasdfkk) {
		//$iiasdfkjlasdfkk .= "\n return TRUE;";
		ob_start();
		if (FALSE === @eval($iiasdfkjlasdfkk)) {
			return FALSE;
		}
		ob_end_clean();
		return true;
	}

	public function getSuper() {
		$cache = $this->registry->profunctions->GetCaches();
		$data = array();
		if (count($cache['groups']) && is_array($cache['groups'])) {
			foreach ($cache['groups'] as $k => $v) {
				if ($v['promenu_groups_make_super']) {
					$data = $this->registry->profunctions->GetCaches($v['promenu_groups_name']);
					break;
				}
			}
		}

		return $data;
	}

	public function wrapper() {

		$id = intval($this->request['id']);

		$c = $this->registry->profunctions->getSingleMenu($id);

		$cache = $this->registry->profunctions->GetCaches($c['promenu_menus_group']);
		$group = $cache['groups'];
		$menus = $cache['menus'];

		$c['promenu_menus_name'] = unserialize($c['promenu_menus_name']);
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

		foreach ($c['promenu_menus_name'] as $ks => $cs) {
			if ($cs) {
				$default = $cs;
				break;
			}
		}

		$c['promenu_menus_name'] = $c['promenu_menus_name'][$lang['lang_default']];

		if (!$c['promenu_menus_name']) {
			$c['promenu_menus_name'] = $default;
		}

		if (!$c['promenu_menus_wrapper_wrapped']) {
			$height = $c['promenu_menus_wrapper_height'] ? $c['promenu_menus_wrapper_height'] : 500;
			$this->output .= <<<EOF
			<iframe src="{$c['promenu_menus_wrapper']}" width="100%" height="{$height}px" marginheight="0" frameborder="0" id="framed"></iframe>	
EOF;
			$this->registry->output->setTitle($this->settings['board_name'] . " - " . $c['promenu_menus_name']);

			$this->registry->output->addNavigation($c['promenu_menus_name'], "");

			$this->registry->getClass('output')->addContent($this->output);

			//generates the magic
			$this->registry->getClass('output')->sendOutput();
		} else {

			$cache['menus'] = $this->registry->profunctions->ParseMenus($cache['menus'], 0, $cache['groups']);

			if (count($cache['menus']) && is_array($cache['menus']) && $cache['groups']['promenu_groups_enabled']) {
				if (!$c['disable_wrapper_menu'])
					$display = 'style="display:none;"';
			}
            $d = $this->registry->promenuHooks->menus(array('cache' => $cache, 'template' => 'proMain', 'ulID' => 'super_menu_app_menu', 'jsMenuEnabled' => true));
			$outputs .= <<<EOF
			<div id="super_menu" {$display}>
				<div class="main_widths">
					<ul class="ipsList_inline" id="super_menu_app_menu">
EOF;
			$outputs .= $d['html'];
			$outputs .= <<<EOF
			</ul>
EOF;

			$outputs .= <<<EOF
			</div>
		</div>
        {$d['rhtml']}
EOF;
			if (!$c['disable_wrapper_menu'] && !$group['promenu_groups_default_mobile']) {
				$outputs .= <<<EOF
		<div id="superMegaClickParent">
			<div id="superMegaClick"><a href="#" id="superClickMegas">{$this->lang->words['promenu_wrapper_anchor']}</a></div>
		</div>
EOF;
			} else {
				$outputs .= <<<EOF
			<div id="superClickMegas" data-isOpen="0"></div>		
EOF;
			}
			$this->output .= <<<EOF
		<!DOCTYPE html>
			<html lang="en" >
				<head>
					<title>{$this->settings['board_name']} - {$c['promenu_menus_name']}</title>
					{$this->registry->promenuHooks->Jquery_load(1)}
EOF;
			if (!$c['disable_wrapper_menu']) {
				$this->output .= <<<EOF
					<script>
						projQ(document).ready(function(){
							h =	projQ(window).height() - 5;
							projQ("#framed").attr("height",h+"px");
						});
						projQ(window).resize(function() {
							if(projQ("#superClickMegas").data("isOpen")){
								m = projQ("#super_menu").height()+5;
								h =	projQ(window).height() - m;
								projQ("#framed").attr("height",h+"px");
							}
							else{
								h =	projQ(window).height() - 5;
								projQ("#framed").attr("height",h+"px");							
							}
						});
					</script>
EOF;
			} else {
				$this->output .= <<<EOF
					<script>
						projQ(document).ready(function(){
							m = projQ("#super_menu").height() + 5;
							h =	projQ(window).height() - m;
							projQ("#framed").attr("height",h+"px");
						});
						projQ(window).resize(function() {
							m = projQ("#super_menu").height()+5;
							h =	projQ(window).height() - m;
							projQ("#framed").attr("height",h+"px");
						});
					</script>
EOF;
			}
			$this->output .= <<<EOF
				</head>
				<body>
					{$outputs}
						<iframe src="{$c['promenu_menus_wrapper']}" width="100%" scrolling="scrolling" marginheight="0" frameborder="0" id="framed" style="vertical-align:bottom;"></iframe>
				</body>
				</head>
			</html>
EOF;
			if (IPSLib::appIsInstalled('ccs') && $this->settings['promenu_disable_content_css']) {
				if (!$this->registry->isClassLoaded('ccsFunctions')) {
					$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs');
					$this->registry->setClass('ccsFunctions', new $classToLoad($this->registry));
				}
				$this->output = $this->registry->ccsFunctions->injectBlockFramework($this->output);
			}
			echo $this->output;
		}
	}

}
