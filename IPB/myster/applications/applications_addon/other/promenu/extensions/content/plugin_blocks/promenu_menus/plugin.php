<?php

class plugin_promenu_menus implements pluginBlockInterface {
	/*	 * #@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */

	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $registry;
	protected $request;

	/*	 * #@- */

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct(ipsRegistry $registry) {
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry = $registry;
		$this->DB = $registry->DB();
		$this->settings = $registry->fetchSettings();
		$this->member = $registry->member();
		$this->memberData = & $registry->member()->fetchMemberData();
		$this->cache = $registry->cache();
		$this->caches = & $registry->cache()->fetchCaches();
		$this->request = $registry->fetchRequest();
		$this->lang = $registry->class_localization;
		$this->registry->class_localization->loadLanguageFile(array('admin_promenu'), 'promenu');
		if (!$this -> registry -> isClassLoaded('profunctions')) {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/profunctions.php", 'profunctions', 'promenu');
			$this -> registry -> setClass('profunctions', new $classToLoad($this -> registry));
		}
		if (!$this -> registry -> isClassLoaded('promenuHooks')) {
			$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/hooks/hooks.php", 'promenuHooks', 'promenu');
			$this -> registry -> setClass('promenuHooks', new $classToLoad($this -> registry));
		}		
	}

	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags() {
		return array();
	}

	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo() {
		return array(
			'key' => 'promenu_menus',
			'name' => IPSLib::getAppTitle('promenu'),
			'description' => $this->lang->words['promenu_plugin_desc'],
			'hasConfig' => true,
			'templateBit' => 'block__custom',
		);
	}

	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig($session) {
		foreach ($this->caches['promenu_groups'] as $k => $_type) {
			if (!in_array($k, array("header", "primary", "mobile", "site", "footer"))) {
				$b[] = array($k, $k);
			}
		}

		if (!count($b) && !is_array($b)) {
			$b[] = array("0", $this->lang->words['promenu_plugin_no_custom']);
		}

		return array(
			array(
				'label' => $this->lang->words['promenu_plugin_label'],
				'description' => $this->lang->words['promenu_plugin_label_desc'],
				'field' => $this->registry->output->formDropdown('plugin_menus', $b),
			)
		);
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig($data) {

		return array($data['plugin_menus'] ? true : false, array('menus' => $data['plugin_menus']));
	}

	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin($block) {

		$config = unserialize($block['block_config']);

		$cache = $this->registry->profunctions->GetCaches($config['custom']['menus']);

		$cache['menus'] = $this->registry->profunctions->ParseMenus($cache['menus'], 0, $cache['groups']);

		if ( count($cache['menus']) && is_array($cache['menus']) && $cache['groups']['promenu_groups_enabled'] && !in_array( $this->registry->output->skin['set_id'], explode(",", $cache['groups']['promenu_groups_hide_skin'] ) ) ) {
			if ($cache['groups']['promenu_groups_template'] === 'proMain') {
				ob_start();
				$html .= <<<HTML
			<div id="{$config['custom']['menus']}">
				<div class="main_widths">
					<ul class="ipsList_inline" id="{$config['custom']['menus']}_app_menu">
HTML;

				$html .= $this->registry->promenuHooks->menus(array('cache' => $cache, 'template' => 'proMain', 'ulID' => $config['custom']['menus'] . '_app_menu', 'jsMenuEnabled' => TRUE));

				$html .= <<<HTML
					</ul>
				</div>
			</div>
HTML;
				ob_end_clean();
			} else {
				ob_start();
				$html .= <<<HTML
			<div id="d{$config['custom']['menus']}" class="clear">
				<div class="main_widths">
HTML;
				$html .= $this->registry->promenuHooks->menus(array('cache' => $cache, 'template' => 'proOther', 'ulID' => 'd' . $config['custom']['menus'] . '_app_menu', 'jsMenuEnabled' => FALSE));
				$html .= <<<HTML
				</div>
			</div>
HTML;
				ob_end_clean();
			}
		}

		return $html;
	}

}