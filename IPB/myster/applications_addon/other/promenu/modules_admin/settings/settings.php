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

class admin_promenu_settings_settings extends ipsCommand {

	/**
	 * Skin HTML object
	 *
	 * @var		object
	 * @access	private
	 */
	private $html;

	/**
	 * Main entry point
	 *
	 * @param	object	$registry		ipsRegistry object
	 * @return	void
	 * @access	public
	 */
	public function doExecute(ipsRegistry $registry) {
		$this->form_code = $this->settings['base_url'] . 'module=settings&amp;section=settings';

		$this->registry->output->ignoreCoreNav = TRUE;

		switch ($this->request['do']) {
			case 'show':
			default:
				$this->showSettings();
				break;
		}
		
		if ($this->request['do']) {
			$this->registry->output->extra_nav[] = array($this->settings['base_url'] . "module=menus&amp;section=menus&amp;do=groups&amp;key=" . urldecode($key), "{$this->lang->words['promenu_menu_groups']}: " . urldecode($this->request['key']));
		} else {
			$this->registry->output->extra_nav[] = array('', "{$this->lang->words['promenu_menu_groups']}: " . urldecode($key));
		}
		$nonStatic = $this->registry->profunctions->getNonStaticGroups();
		if(count($nonStatic) && is_array($nonStatic)){
			$this->registry->output->sidebar_extra = '';
			$this->registry->output->sidebar_extra .= "<ul><li class='has_sub'>{$this->lang->words['promenu_custom_menus']}";
			foreach ($nonStatic as $k => $c) {
				$this->registry->output->sidebar_extra .= "<ul><li><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>" . ucwords($k)."&nbsp;".$this->lang->words['promenu_word_group']."</a></li></ul>";
			}
			$this->registry->output->sidebar_extra .= "</li></ul>";
		}
							
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Show Promenu settings
	 *
	 * @param	void
	 * @return	void
	 * @access	private
	 */
	private function showSettings() {
		$classToLoad = IPSLib::loadActionOverloader(IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings');

		$settings = new $classToLoad();
		$settings->makeRegistryShortcuts($this->registry);
		$settings->html = $this->registry->output->loadTemplate('cp_skin_settings', 'core');
		$settings->form_code = $settings->html->form_code = 'module=settings&amp;section=settings';
		$settings->form_code_js = $settings->html->form_code_js = 'module=settings&section=settings';
		$settings->return_after_save = $this->form_code . '&amp;do=show&amp;fromSettings=1';

		$this->lang->loadLanguageFile(array('admin_tools'), 'core');
		$this->request['conf_title_keyword'] = 'promenu_menus_settings';

		$settings->_viewSettings();
	}

}