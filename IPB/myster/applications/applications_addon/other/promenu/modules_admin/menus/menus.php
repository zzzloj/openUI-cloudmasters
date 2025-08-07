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

class admin_promenu_menus_menus extends ipsCommand {

	public $html;
	public $sidebar;

	public function doExecute(ipsRegistry $registry) {
		$key = $this->request['key'] ? urldecode($this->request['key']) : "primary";

		//lets build some basics
		$this->html = $this->registry->output->loadTemplate('skin_menus', 'promenu');

		$this->registry->class_localization->loadLanguageFile(array('admin_applications'), 'core');

		//BEGONE breadcrumb
		$this->registry->output->ignoreCoreNav = TRUE;

		// Lets build the new breadcrumb
		//$this->registry->output->extra_nav[] = array($this->settings['base_url'], IPSLib::getAppTitle('promenu'));

		if ($this->request['do']) {
			$this->registry->output->extra_nav[] = array($this->settings['base_url'] . "module=menus&amp;section=menus&amp;do=groups&amp;key=" . urldecode($key), "{$this->lang->words['promenu_menu_groups']}: " . urldecode($this->request['key']));
		} else {
			$this->registry->output->extra_nav[] = array('', "{$this->lang->words['promenu_menu_groups']}: " . urldecode($key));
		}

		foreach ($this->registry->profunctions->buildGroups() as $k => $c) {
			if ($key == $k) {
				$this->sidebar .= "<ul><li class='active'><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>" . $this->lang->words['promenu_word_group'] . ": " . $k . "</a></li></ul>";
			} else {
				$this->sidebar .= "<ul><li><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>" . $this->lang->words['promenu_word_group'] . ": " . $k . "</a></li></ul>";
			}
		}
		/* Which do */
		switch ($this->request['do']) {
			case "addMenu" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_add_menu' );
				$this->addMenu();
				break;
			case "editMenu" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_edit_menu' );
				$this->editMenu();
				break;
			case "status" :
				$this->status();
				break;
			case "clone" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_clone_menu' );
				$this->cloner();
				break;
			case "kerching" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_refresh_cache' );
				$this->kerching();
				break;
			case "EditGroups" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_edit_group' );
				$this->ManageGroups();
				break;
			case "AddGroup":
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_add_group' );
				$this->ManageGroups();
				break;
			case "ExportGroup" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_export_group' );
				$this->html = $this->registry->output->loadTemplate('skin_hooks', 'promenu');
				$this->ExportGroup();
				break;
			case "importApps" :
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_import_group' );
				$this->importApps();
				break;
			case 'groupSettings':
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_add_group' );
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_edit_group' );
				$this->groupSettings();
				break;
			case "delgroup":
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_delete_group' );
				$this->deletegroups();
				exit;
			case 'save':
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_add_menu' );
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_edit_menu' );
				$this->save();
				break;
			case 'deleteMenus':
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_delete_menu' );
				$this->deleteMenus();
				break;
			case 'clones':
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_clone_menu' );
				$this->attack_of_the_clones();
				break;
			case "export":
				$this->registry->class_permissions->checkPermissionAutoMsg( 'promenu_export_group' );
				$this->export();
				break;			
			default :
				$this->groups();
				break;
		}

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	protected function groups() {
		$this->registry->output->html .= $this->html->main_container($this->registry->profunctions->getMenusByParent(), $this->sidebar);
	}

	protected function addMenu() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_add_menu']);
		$this->registry->output->html .= $this->html->containers(array(), $this->sidebar);
	}

	protected function editMenu() {
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_edit_menu']);
		$this->registry->output->html .= $this->html->containers($this->registry->profunctions->getSingleMenu(intval($this->request['id'])), $this->sidebar);
	}

	protected function status() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		if ($this->request['state']) {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 1), 'promenu_menus_id=' . intval($this->request['id']));
		} else {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 0), 'promenu_menus_id=' . intval($this->request['id']));
		}

		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . $this->request['key'] . "&amp;id=" . $this->request['id']);
	}

	protected function cloner() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_cloning']);
		$this->registry->output->html .= $this->html->clone_wars(intval($this->request['id']), $this->sidebar);
	}

	protected function kerching() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->profunctions->buildGroupCache();

		$this->registry->profunctions->kerching();

		$this->registry->output->global_message = sprintf($this->lang->words['promenu_menus_cache_refresh']);

		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($this->request['key']));
	}

	protected function ManageGroups() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$key = $this->request['key'];

		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_manage_groups']);

		if ($key) {
			$this->registry->output->html .= $this->html->group_settings($this->caches['promenu_groups'][$key], $this->sidebar);
		} else {
			$this->registry->output->html .= $this->html->group_settings(array(), $this->sidebar);
		}
	}

	protected function ExportGroup() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_export_group']);
		$this->registry->output->html .= $this->html->hookForm($this->sidebar);
	}

	protected function importApps() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->profunctions->BuildDefaultMenus($this->request['key']);
		$this->registry->output->global_message = sprintf($this->lang->words['promenu_menus_application_imported']);
		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($this->request['key']));
	}

	/**
	 * groupSettings
	 * saves group data for new or edit.
	 * @return @e void
	 */
	protected function groupSettings() {

		if (!$this->request['postkey'] == $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		if (!$this->request['promenu_groups_name']) {
			$this->registry->output->showError($this->lang->words['promenu_error_blank_group'], '10Promenu2');
		}

		if ($this->request['original_key']) {
			$a['promenu_groups_name'] = $this->DB->addSlashes(IPSText::alphanumericalClean(trim($this->request['promenu_groups_name'])));
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_group_edit'], $a['promenu_groups_name']));
		}
		else{
			$a['promenu_groups_name'] = $this->DB->addSlashes(IPSText::alphanumericalClean(trim($this->request['promenu_groups_name'])));
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_group_add'], $a['promenu_groups_name']));
		}
		
		if(is_numeric($a['promenu_groups_name'][0]))
		{
			$a['promenu_groups_name'] = "Pro".$a['promenu_groups_name'];
		}

		$a['promenu_groups_enabled'] = intval($this->request['promenu_groups_enabled']);

		$a['promenu_groups_tab_activation'] = intval($this->request['promenu_groups_tab_activation']);

		$a['promenu_groups_template'] = $this->request['promenu_groups_template'];
		
		$a['promenu_groups_is_vertical'] = $this->request['promenu_groups_is_vertical'];
		$a['promenu_groups_border'] = $this->request['promenu_groups_border'];

		if ($this->request['promenu_groups_allow_effects']) {
			$a['promenu_groups_behavoir'] = intval($this->request['promenu_groups_behavoir']);

			$a['promenu_groups_speed_open'] = intval($this->request['promenu_groups_speed_open']);

			$a['promenu_groups_speed_close'] = intval($this->request['promenu_groups_speed_close']);

			$a['promenu_groups_animation_open'] = $this->request['promenu_groups_animation_open'];

			$a['promenu_groups_animation_close'] = $this->request['promenu_groups_animation_close'];

			$a['promenu_groups_hover_speed'] = $this->request['promenu_groups_hover_speed'];

			$a['promenu_groups_top_offset'] = $this->request['promenu_groups_top_offset'];

			if (!is_numeric($a['promenu_groups_hover_speed'])) {
				$this->registry->output->showError(sprintf($this->lang->words['promenu_error_numeric'], $this->lang->words['promenu_hover']), '20Promenu3');
			}

			if (!is_numeric($a['promenu_groups_top_offset'])) {
				$this->registry->output->showError(sprintf($this->lang->words['promenu_error_numeric'], $this->lang->words['promenu_top_offset_limit']), '20Promenu3');
			}
		}

		if ($this->request['promenu_groups_hide_skin']) {
			$a['promenu_groups_hide_skin'] = implode(",", $this->request['promenu_groups_hide_skin']);
		} else {
			$a['promenu_groups_hide_skin'] = 0;
		}
		
		$a['promenu_groups_by_url'] = intval($this->request['promenu_groups_by_url']);
		
		$a['promenu_groups_enable_alternate_lang_strings'] = $this->request['promenu_groups_enable_alternate_lang_strings'];

		$a['promenu_groups_zindex'] = $this->request['promenu_groups_zindex'];

		$a['promenu_groups_arrows_enabled'] = $this->request['promenu_groups_arrows_enabled'];

		$a['promenu_groups_group_visibility_enabled'] = $this->request['promenu_groups_group_visibility_enabled'];

		if (!is_numeric($a['promenu_groups_zindex'])) {
			$this->registry->output->showError(sprintf($this->lang->words['promenu_error_numeric'],$this->lang->words['promenu_index_limit']), '20Promenu3');
		}

		if (!$this->request['original_key']) {
			$check = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_groups', 'where' => 'promenu_groups_name="' . $a['promenu_groups_name'] . '"'));
		} else {
			$check['count'] = 0;
		}
		
		if (!intval($check['count'])) {
			if ($this->request['original_key']) {
				$this->DB->update("promenuplus_groups", $a, 'promenu_groups_name="' . $this->request['original_key'] . '"');
				$this->registry->profunctions->buildGroupCache();

				$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_word_group']}:" . urldecode($a['promenu_groups_name']) . " {$this->lang->words['promenu_word_changed']}");
			} else {

				$a['promenu_groups_hash'] = md5(IPS_UNIX_TIME_NOW);

				$this->DB->insert("promenuplus_groups", $a);

				$this->registry->profunctions->buildGroupCache();

				//$this->registry->profunctions->BuildDefaultMenus($a['promenu_groups_name']);

				$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_word_group']}:" . urldecode($a['promenu_groups_name']) . " {$this->lang->words['promenu_word_added']}");
			}

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($a['promenu_groups_name']));
		} else {
			$this->registry->output->showError("{$this->lang->words['promenu_group_exists']}!", '10Promenu4');
		}
	}

	/**
	 * deletegroups
	 * delete groups from database
	 * @return @e void
	 */
	protected function deletegroups() {
		if (!$this->request['postkey'] == $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		$key = urldecode($this->request['key']);
		
		$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_group_deleted'], $this->request['key']));
		
		$this->DB->delete('promenuplus_groups', 'promenu_groups_name="' . $key . '"');
		//$this->DB->delete('promenuplus_menus','promenu_menus_group="'.$key.'"');

		$this->DB->build(array(
			'select' => '*',
			'from' => 'promenuplus_menus',
			'where' => 'promenu_menus_group="' . $key . '" AND promenu_menus_parent_id=0'));

		$q = $this->DB->execute();

		while ($d = $this->DB->fetch($q)) {
			$del = $this->registry->profunctions->gatherIdForDel($d['promenu_menus_id']);

			if (count($del) && is_array($del)) {
				foreach ($del as $k => $c) {
					$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $c);
					$this->DB->delete("permission_index", 'perm_type_id=' . $c . ' AND app="promenu"');
				}

				$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menus_deleted']}.");
			} else {
				$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $d['promenu_menus_id']);
				$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_deleted']}.");
				$this->DB->delete("permission_index", 'perm_type_id=' . $d['promenu_menus_id'].' AND app="promenu"');
			}
			$c = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=' . $d['promenu_menus_parent_id']));

			if ($c['count'] <= 0) {
				$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 0), 'promenu_menus_id=' . $d['promenu_menus_parent_id']);
			}
		}

		$this->registry->profunctions->buildGroupCache();
		$this->registry->profunctions->kerching();

		$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_word_group']}:" . urldecode($this->request['key']) . " {$this->lang->words['promenu_word_deleted']}");
		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus");
	}

	/**
	 * save
	 * saves menu items data for new or edit
	 * @return @e void
	 */
	public function save() {

		if($this->request['promenu_menus_id'])
		{
			$itAll = $this->registry->profunctions->getSingleMenu(intval($this->request['promenu_menus_id']));
			
		}
		
		if ($this->request['cancel']) {
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['promenu_menus_group']));
		}
		
		if (!$this->request['postkey'] == $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		$d = 0;

		foreach ($this->request['promenu_menus_name'] as $k => $c) {
			if ($c) {
				$d = 1;
				$name = $c;
				break;
			}
		}

		if (!$d) {
			$this->registry->output->showError("{$this->lang->words['promenu_title_no_blank']}!", '10Promenu2');
		}
		
		if($this->request['promenu_menus_id']){
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_menu_edited'], $name));
		}
		else{
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_menu_added'], $name));
		}
		
		$a['promenu_menus_name'] = serialize($this->request['promenu_menus_name']);

		$a['promenu_menus_desc'] = serialize($this->request['promenu_menus_desc']);

		if ($this->request['promenu_menus_img_as_title_check']) {
			$a['promenu_menus_img_as_title_check'] = $this->request['promenu_menus_img_as_title_check'];

			$a['promenu_menus_title_icon'] = $this->request['promenu_menus_title_icon'];

			if (!$a['promenu_menus_title_icon']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_title_img_url_blank']}!", '10Promenu2');
			}

			$a['promenu_menus_img_as_title_w'] = intval($this->request['promenu_menus_img_as_title_w']);

			if (!intval($a['promenu_menus_img_as_title_w'])) {
				$this->registry->output->showError("{$this->lang->words['promenu_title_img_icon_width_only_numeric']}. {$this->lang->words['promenu_correct_error']}.", '20Promenu3');
			}

			if (!$a['promenu_menus_img_as_title_w']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_title_img_icon_width_blank']}.", '10Promenu2');
			}

			$a['promenu_menus_img_as_title_h'] = intval($this->request['promenu_menus_img_as_title_h']);

			if (!$a['promenu_menus_img_as_title_h']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_title_img_icon_height_blank']}.", '10Promenu2');
			}

			if (!intval($a['promenu_menus_img_as_title_h'])) {
				$this->registry->output->showError("{$this->lang->words['promenu_title_img_icon_height_only_numeric']}. {$this->lang->words['promenu_correct_error']}.", '20Promenu3');
			}
		} else {
			$a['promenu_menus_img_as_title_check'] = 0;
		}

		if ($this->request['promenu_menus_icon_check']) {
			$a['promenu_menus_icon_check'] = $this->request['promenu_menus_icon_check'];

			$a['promenu_menus_icon'] = $this->request['promenu_menus_icon_custom'];

			if (!$a['promenu_menus_icon']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_icon_url_blank']}!", '10Promenu2');
			}

			$a['promenu_menus_icon_w'] = $this->request['promenu_menus_icon_w'];

			if (!intval($a['promenu_menus_icon_w'])) {
				$this->registry->output->showError("{$this->lang->words['promenu_icon_width_only_numeric']}.", '10Promenu3');
			}

			if (!$a['promenu_menus_icon_w']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_icon_width_blank']}.", '10Promenu2');
			}

			$a['promenu_menus_icon_h'] = $this->request['promenu_menus_icon_h'];

			if (!$a['promenu_menus_icon_h']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_icon_height_blank']}.", '10Promenu2');
			}

			if (!intval($a['promenu_menus_icon_h'])) {
				$this->registry->output->showError("{$this->lang->words['promenu_icon_height_only_numeric']}.", '10Promenu3');
			}
		} else {
			$a['promenu_menus_icon_check'] = 0;
			$a['promenu_menus_icon'] = $this->request['promenu_menus_icon'];
		}

		$a['promenu_menus_group'] = $this->request['promenu_menus_group'];

		$groups = $this->caches['promenu_groups'][$a['promenu_menus_group']];

		//if not a root cat, lets tell the parent it has children, its stupid like that!
		if ($this->request['promenu_menus_parent_id']) {
			$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 1), 'promenu_menus_id=' . intval($this->request['promenu_menus_parent_id']));
		}

		//if the child gets adopted to another parent, lets tell the old parent it doesn't need to worry anymore, especially if it has no other children to worry about!
		if (intval($this->request['current_parent_id']) && intval($this->request['promenu_menus_id'])) {
			if ($this->request['current_parent_id'] != $this->request['promenu_menus_parent_id']) {
				$c = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=' . $this->request['current_parent_id']));

				if ($c['count'] - 1 <= 0) {
					$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 0), 'promenu_menus_id=' . intval($this->request['current_parent_id']));
				}
			}
		}

		$a['promenu_menus_parent_id'] = $this->request['promenu_menus_parent_id'];

		if (!$a['promenu_menus_parent_id']) {
			$a['promenu_menus_is_mega'] = $this->request['promenu_menus_is_mega'];

			$a['promenu_menus_mega_column_count'] = $this->request['promenu_menus_mega_column_count'];

			if (!$a['promenu_menus_mega_column_count']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_number_columns_blank']}.", '10Promenu2');
			}

			if (!intval($a['promenu_menus_mega_column_count'])) {
				$this->registry->output->showError("{$this->lang->words['promenu_number_columns_only_numeric']}.", '10Promenu3');
			}
		} else {

			$a['promenu_menus_is_mega'] = 0;
		}

		if (!$this->request['promenu_menus_id']) {
			//okay lets put it low man on the totem for now, this should suffice, if not will look into other options
			$order = $this->DB->buildAndFetch(array('select' => 'promenu_menus_order', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id='. $this->request['promenu_menus_parent_id'],'order' => 'promenu_menus_order DESC'));

			$a['promenu_menus_order'] = $order['promenu_menus_order'] + 1;

		}

		if ($_POST['promenu_menus_attr']) {
			$extras = str_replace("'", '"', $_POST['promenu_menus_attr']);
			preg_match('/class="([^"]+)?"/msU', $extras, $classes);
			$extras = str_replace($classes[0], '', $extras);

			preg_match('/style="([^"]+)?"/msU', $extras, $styles);
			$extras = str_replace($styles[0], '', $extras);

			$ext['class'] = str_replace('"', '', str_replace('class="', '', $classes[0]));
			$ext['style'] = str_replace('"', '', str_replace('style="', '', $styles[0]));
			$ext['attr'] = trim($extras);

			$a['promenu_menus_attr'] = serialize($ext);
		} else {
			$ext['class'] = '';
			$ext['style'] = '';
			$ext['attr'] = '';

			$a['promenu_menus_attr'] = serialize($ext);
		}

		$a['promenu_menus_block'] = '';

		$a['promenu_menus_url'] = '';

		$a['promenu_menus_app_link'] = '';

		$a['promenu_menus_content_link'] = 0;

		$a['promenu_menus_easy_link'] = '';

		if ($this->request['promenu_menus_link_type'] === "man") {
			$a['promenu_menus_link_type'] = $this->request['promenu_menus_link_type'];
			$a['promenu_menus_url'] = $this->request['promenu_menus_url'];
		} else if ($this->request['promenu_menus_link_type'] === "app") {
			$a['promenu_menus_link_type'] = $this->request['promenu_menus_link_type'];
			$a['promenu_menus_app_link'] = $this->request['promenu_menus_app_link'];
			if ($a['promenu_menus_app_link'] === "ccs") {
				$a['promenu_menus_content_link'] = $this->request['promenu_menus_content_link'];
				if(!$a['promenu_menus_content_link'])
				{
					$this->registry->output->showError("{$this->lang->words['promenu_select_content_page']}", "10Promenu2");
				}
			}
			if ($a['promenu_menus_app_link'] === "easypages") {
				$a['promenu_menus_easy_link'] = $this->request['promenu_menus_easy_link'];
				if(!$a['promenu_menus_easy_link'])
				{
					$this->registry->output->showError("{$this->lang->words['promenu_select_eaypage_page']}", "10Promenu2");
				}
			}
		} else if ( $this->request['promenu_menus_link_type'] === "html" || $this->request['promenu_menus_link_type'] === 'pblock') {
			$a['promenu_menus_link_type'] = $this->request['promenu_menus_link_type'];
			$a['promenu_menus_url'] = $this->request['promenu_menus_url'];
			$a['promenu_menus_block'] = $_POST['promenu_menus_block'];
			if(!$this->request['promenu_menus_parent_id'])
			{
				$a['promenu_menus_by_url'] = $this->request['promenu_menus_by_url'];
			}
			else{
				$a['promenu_menus_by_url'] = 0;
			}

			if (!$a['promenu_menus_block']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_block_code_blank']}!", "10Promenu2");
			}

			if ($a['promenu_menus_link_type'] === 'pblock' && $this->registry->profunctions->proPlus === true ) {
				//$a['promenu_menus_block'] = $_POST['promenu_menus_pblock'];
				$phpIt = $_POST['promenu_menus_pblock'];
				if (!$phpIt) {
					$this->registry->output->showError("{$this->lang->words['promenu_no_block_code_blank']}!", "10Promenu2");
				}
				
				$phpIt = $this->registry->profunctions->replaceFirst($phpIt, "<?php", "");
				$phpIt = $this->registry->profunctions->replaceFirst($phpIt, "<?", "");
				$phpIt = $this->registry->profunctions->replaceLast("?>","",$phpIt);
				$phpIt = trim($phpIt);

				$asdeqwerewrasdfasd = $this->registry->proPlus->Iteval($phpIt);

				if (!$asdeqwerewrasdfasd) {
					$this->registry->output->showError("{$this->lang->words['promenu_php_code_not_valid']}", "10Promenu5");
				}

				$a['promenu_menus_block'] = $phpIt;
			}
		} else if ($this->request['promenu_menus_link_type'] === "cblock" && $this->registry->profunctions->proPlus === true ) {
			if(!$this->request['promenu_menus_parent_id'])
			{
				$a['promenu_menus_by_url'] = $this->request['promenu_menus_by_url'];
			}
			else{
				$a['promenu_menus_by_url'] = 0;
			}
			$a['promenu_menus_link_type'] = $this->request['promenu_menus_link_type'];

			$a['promenu_menus_url'] = $this->request['promenu_menus_url'];

			$a['promenu_menus_block'] = $_POST['ipc_blocks_code'];

			if (!$a['promenu_menus_block']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_block_code_blank']}!", "10Promenu2");
			}
		} else if ($this->request['promenu_menus_link_type'] === "eblock" && $this->registry->profunctions->proPlus === true ) {
			if(!$this->request['promenu_menus_parent_id'])
			{
				$a['promenu_menus_by_url'] = $this->request['promenu_menus_by_url'];
			}
			else{
				$a['promenu_menus_by_url'] = 0;
			}
			$a['promenu_menus_link_type'] = $this->request['promenu_menus_link_type'];

			$a['promenu_menus_url'] = $this->request['promenu_menus_url'];

			$a['promenu_menus_block'] = $_POST['easypages_blocks_code'];

			if (!$a['promenu_menus_block']) {
				$this->registry->output->showError("{$this->lang->words['promenu_no_block_code_blank']}!", "10Promenu2");
			}
		} else {
			$a['promenu_menus_link_type'] = "def";
		}

		$a['promenu_menus_override'] = $this->request['promenu_menus_override'] ? $this->request['promenu_menus_override'] : 0;

		if (count($this->request['promenu_menus_view']) && is_array($this->request['promenu_menus_view']) && $groups['promenu_groups_group_visibility_enabled']) {
			$a['promenu_menus_view'] = implode(",", $this->request['promenu_menus_view']);
		} else if(!$groups['promenu_groups_group_visibility_enabled']){
			$a['promenu_menus_view'] = $this->registry->profunctions->perm2group($this->registry->profunctions->perm2groupArray($this->request['perms']['menuview']));
		}
		else{
			$a['promenu_menus_view'] = '';
		}
		
		$a['promenu_menus_forums_attatch'] = $this->request['promenu_menus_forums_attatch'];

		if ($a['promenu_menus_forums_attatch'] && $a['promenu_menus_app_link'] === "forums") {
			$a['promenu_menus_has_sub'] = 1;
		} else {
			$a['promenu_menus_forums_attatch'] = 0;
		}

		if ($this->request['promenu_menus_id']) {
			$this->DB->update('promenuplus_menus', $a, 'promenu_menus_id=' . intval($this->request['promenu_menus_id']));
			$ids = intval($this->request['promenu_menus_id']);
		} else {
			$this->DB->insert("promenuplus_menus", $a);
			$ids = $this->DB->getInsertId();
		}


		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $classToLoad($this->registry);

		if (!$groups['promenu_groups_group_visibility_enabled']) {
			$perms->savePermissionsMatrix($ids, $this->request['perms']);
		} else {
			if (!$a['promenu_menus_override']) {
				if (count($this->request['promenu_menus_view']) && is_array($this->request['promenu_menus_view'])) {
					$groupIt = $this->request['promenu_menus_view'];
				} else {
					$groupIt = array();
				}
				$perm['perms'] = $this->registry->profunctions->group2perm($groupIt);
			} else {
				$perm = '';
			}
			$perms->savePermissionsMatrix($ids, $perm['perms']);
		}

		if ($a['promenu_menus_forums_attatch'] && $this->request['promenu_menus_app_link'] == 'forums') {
			if(!$itAll['promenu_menus_forums_attatch']){
				$this->registry->profunctions->BuildForumChildren($ids, $a['promenu_menus_group']);
			}
		} else if ($this->request['promenu_menus_id'] && !$a['promenu_menus_forums_attatch']) {
			$del = $this->registry->profunctions->gatherIdForDel($ids);

			$del = $this->registry->profunctions->check4ForumsLink($del, $groups);

			if (count($del) && is_array($del)) {
				foreach ($del as $k => $c) {
					if ($c != $ids) {
						$cd = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_id=' . $c . ' AND promenu_menus_forums_id !=0'));
						if ($cd['promenu_menus_id']) {
							$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $c);
							$this->DB->delete("permission_index", 'perm_type_id=' . $c .' AND app="promenu"');
						}
					}
				}
			}
			$cs = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=' . $ids));

			if ($cs['count'] <= 0) {
				$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 0), 'promenu_menus_id=' . $ids);
			}
		}

		$this->registry->profunctions->kerching();

		if ($this->request['return']) {
			//if they click save and reload, lets take them back to the edit page.
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_saved']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&do=editMenu&id=" . intval($ids) . "&key=" . $a['promenu_menus_group'] . "&parent=" . $a['promenu_menus_parent_id'] . "&check=" . $this->member->form_hash);
		} else {
			//if not a save and reload, just take them back to the landing page
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_saved']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($a['promenu_menus_group']));
		}
	}

	/**
	 * deleteMenus
	 * deletes menu items, both parent and subs
	 * @return @e void
	 */
	protected function deleteMenus() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$id = intval($this->request['id']);
		$names =	$this->DB->buildAndFetch(array('select' => 'promenu_menus_name', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_id=' . $id));
		foreach(unserialize($names['promenu_menus_name']) as $k => $v)
		{
			if($v){
				$name = $v;
				break;
			}
		}
		$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_menu_deleted'], $name));
		
		$del = $this->registry->profunctions->gatherIdForDel($id);

		if (count($del) && is_array($del)) {
			foreach ($del as $k => $c) {
				$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $c);
				$this->DB->delete("permission_index", 'perm_type_id=' . $c . ' AND app="promenu"');
			}

			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menus_deleted']}.");
		} else {
			$this->DB->delete("promenuplus_menus", 'promenu_menus_id=' . $id);
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_deleted']}.");
			$this->DB->delete("permission_index", 'perm_type_id=' . $id.' AND app="promenu"');
		}
		$c = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_parent_id=' . intval($this->request['pid'])));

		if ($c['count'] <= 0) {
			$this->DB->update('promenuplus_menus', array('promenu_menus_has_sub' => 0), 'promenu_menus_id=' . intval($this->request['pid']));
		}

		$this->registry->profunctions->kerching();

		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urldecode($this->request['key']));
	}

	/**
	 * attack_of_the_clones
	 * gathers the data to start the cloning process
	 * @return @e void
	 */
	protected function attack_of_the_clones() {
		if ($this->request['cancel']) {
			//if they click save and reload, lets take them back to the edit page.
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_cloning_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['old_key']));
		}

		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		$a = $this->send_in_the_clones($this->request['current_id'], $this->request['new_key'], 0);

		$this->registry->profunctions->kerching();

		if ($a) {
			$names = $this->registry->profunctions->getSingleMenu($this->request['current_id']);
			foreach(unserialize($names['promenu_menus_name']) as $k => $v)
			{
				if($v){
					$name = $v;
					break;
				}
			}
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_menu_cloned'], $name, $this->request['new_key']));
			
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_word_menu']}: " . $name . " {$this->lang->words['promenu_word_and']} {$this->lang->words['promenu_word_children']} {$this->lang->words['promenu_been_cloned']}.");
			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . $this->request['new_key']);
		}
	}

	/**
	 * send_in_the_clones
	 * process the cloned data
	 * @return @e void
	 */
	protected function send_in_the_clones($currentid, $newkey, $newparent) {
		$cache = $this->registry->profunctions->getSingleMenu($currentid);

		$current_id = $this->registry->profunctions->getMenusByParent($cache['promenu_menus_id'], $cache['promenu_menus_group']);

		$permed['perm_view'] = $cache['perm_view'];

		$permed['app'] = "promenu";

		$permed['perm_type'] = "menu";

		unset($cache['promenu_menus_id']);

		unset($cache['perm_view']);

		$cache['promenu_menus_group'] = $newkey;

		$cache['promenu_menus_parent_id'] = $newparent;

		$cache['promenu_menus_is_open'] = 0;

		$this->DB->insert("promenuplus_menus", $cache);

		$ids = $this->DB->getInsertId();

		$permed['perm_type_id'] = $ids;

		$this->DB->insert("permission_index", $permed);

		$this->DB->update("promenuplus_menus", array("promenu_menus_order" => $ids), "promenu_menus_id=" . $ids);

		if ($cache['promenu_menus_has_sub']) {
			foreach ($current_id as $k => $c) {
				$this->send_in_the_clones($c['promenu_menus_id'], $newkey, $ids);
			}
		}

		return $ids;
	}

	/**
	 * export
	 * begins the export process for hook creation
	 * @return @e void
	 */
	protected function export() {
		
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		
		if ($this->request['cancel']) {
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['promenu_menus_group']));
		}

		$group = $this->caches['promenu_groups'][$this->request['group']];
		
		$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_group_export'], $group['promenu_groups_name']));
		
		$data['dataLocation'] = '';

		$data['classToOverload'] = '';

		$data['skinGroup'] = $this->request['skinGroup'][1];

		$data['skinFunction'] = $this->request['skinFunction'][1];

		$data['type'] = $this->request['type'][1];

		$data['id'] = $this->request['id'][1];

		$data['position'] = $this->request['position'][1];

		$bob['loc'] = $data;


		unset($bob['loc']['dataLocation']);

		unset($bob['loc']['classToOverload']);

		//-----------------------------------------
		// Get XML class
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classXML.php' ); /* noLibHook */

		$xml = new classXML(IPS_DOC_CHAR_SET);

		$xml->newXMLDocument();

		$xml->addElement('hookexport');

		//-----------------------------------------
		// Put hook data in export
		//-----------------------------------------

		$xml->addElement('hookdata', 'hookexport');

		$c = array();

		$c['hook_name'] = "promenu custom group: " . $this->request['group'];

		$c['hook_desc'] = "custom menu hook for promenu, group: " . $this->request['group'];

		$c['hook_author'] = $this->memberData['name'];

		$c['hook_email'] = $this->memberData['email'];

		$c['hook_website'] = $this->settings['base_url'];

		$c['hook_update_check'] = '';

		$c['hook_requirements'] = 'a:3:{s:21:"required_applications";a:0:{}s:20:"hook_php_version_min";s:0:"";s:20:"hook_php_version_max";s:0:"";}';

		if (empty($group['promenu_groups_has_hook'])) {
			$c['hook_version_human'] = "1.0.0";

			$c['hook_version_long'] = "10000";
		} else {
			$ver = unserialize($group['promenu_groups_has_hook']);

			$ver = $ver['other']['hook_version_long'][0] + 1;

			$c['hook_version_human'] = $ver . ".0.0";

			$c['hook_version_long'] = $ver . "0000";
		}

		$c['hook_extra_data'] = 'a:1:{s:7:"display";N;}';

		$c['hook_key'] = "promenu_hook_" . $this->request['group'];

		$c['hook_global_caches'] = "promenu_groups,promenu_menus";

		$xml->addElementAsRecord('hookdata', 'config', $c);

		$bob['other'] = $c;

		$bob = serialize($bob);

		$this->DB->update("promenuplus_groups", array("promenu_groups_has_hook" => $bob), "promenu_groups_name='" . $group['promenu_groups_name'] . "'");

		$this->registry->profunctions->buildGroupCache();

		//-----------------------------------------
		// Put hook files in export
		//-----------------------------------------

		$xml->addElement('hookfiles', 'hookexport');

		$c = array();

		$c['hook_file_real'] = "promenu" . ucwords($this->request['group']) . "Hook.php";

		$c['hook_type'] = "templateHooks";

		$c['hook_classname'] = "promenu" . ucwords($this->request['group']) . "Hook";

		$c['hook_data'] = serialize($data);


		$template = $group['promenu_groups_template'];

		//$source = file_get_contents(IPSLib::getAppDir("promenu") . "/sources/hooks/creation_hook.php");

		if ($template == "proMain") {
			//$source = $this->settings['promenu_hook_php_main'];
			$source = $this->registry->profunctions->getHookData('proMain.php');
		} else {
			$source = $this->registry->profunctions->getHookData('proOther.php');
		}

		$pattern = array("{class_name}", "{menu_id}", "{template}", "{group}");

		$replace = array($c['hook_classname'], $this->request['group'], $template, $this->request['group']);

		$source = str_replace($pattern, $replace, $source);

		$c['hooks_source'] = $source;

		$xml->addElementAsRecord('hookfiles', 'file', $c);

		//-----------------------------------------
		// Custom install/uninstall script?
		//-----------------------------------------
		$xml->addElement('hookextras_custom', 'hookexport');

		//-----------------------------------------
		// Settings or setting groups?
		//-----------------------------------------
		$content = array();

		$xml->addElement('hookextras_settings', 'hookexport');
		//-----------------------------------------
		// Language strings/files
		//-----------------------------------------				
		$xml->addElement('hookextras_language', 'hookexport');

		//-----------------------------------------
		// Modules
		//-----------------------------------------
		$xml->addElement('hookextras_modules', 'hookexport');

		//-----------------------------------------
		// Help files
		//-----------------------------------------
		$xml->addElement('hookextras_help', 'hookexport');

		//-----------------------------------------
		// Skin templates
		//-----------------------------------------
		$xml->addElement('hookextras_templates', 'hookexport');

		//-----------------------------------------
		// CSS
		//-----------------------------------------
		$xml->addElement('hookextras_css', 'hookexport');

		$c = array();

		$content = array();

		if ($template == "proMain") {
			$content = $this->registry->profunctions->getHookData('proMainCss.css');
		} else {
			$content = $this->registry->profunctions->getHookData('proOtherCss.css');
		}

		$content = str_replace("{menu_id}", $this->request['group'], $content);

		$c['css_updated'] = IPS_UNIX_TIME_NOW;

		$c['css_group'] = $this->request['group'];

		$c['css_content'] = $content;

		$c['css_position'] = 0;

		$c['css_added'] = 0;

		$c['css_app'] = "promenu";

		$c['css_app_hide'] = 0;

		$c['css_attributes'] = 'title="Main" media="screen"';

		$c['css_modules'] = '';

		$c['css_removed'] = 0;

		$c['css_master_key'] = "root";

		$xml->addElementAsRecord('hookextras_css', 'css', $c);

		//-----------------------------------------
		// Replacements
		//-----------------------------------------
		$xml->addElement('hookextras_replacements', 'hookexport');

		//-----------------------------------------
		// Tasks
		//-----------------------------------------
		$xml->addElement('hookextras_tasks', 'hookexport');

		//-----------------------------------------
		// Database changes
		//-----------------------------------------
		$xml->addElement('hookextras_database_create', 'hookexport');

		$xml->addElement('hookextras_database_alter', 'hookexport');

		$xml->addElement('hookextras_database_update', 'hookexport');

		$xml->addElement('hookextras_database_insert', 'hookexport');

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		$this->registry->output->showDownload($xml->fetchDocument(), $this->request['group'] . '.xml', '', 0);
	}
}
