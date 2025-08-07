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
		
		foreach ($this->registry->profunctions->buildGroups() as $k => $c) {
			if ($key == $k) {
				$sidebar .= "<ul><li class='active'><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>{$this->lang->words['promenu_word_group']}: " . $k . "</a></li></ul>";
			} else {
				$sidebar .= "<ul><li><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>{$this->lang->words['promenu_word_group']}: " . $k . "</a></li></ul>";
			}
		}
		$this->registry->output->html_main .= <<<EOF
					<script>
						jQ(document).ready(function(){
							url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
							jQ("#section_navigation").find("a").each(function(){
								if(jQ(this).attr('href') == url)
								{
									jQ(this).parent().append("{$sidebar}");
								}
							})
						})
					</script>
EOF;
									
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