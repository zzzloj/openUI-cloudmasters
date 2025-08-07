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
		$key = $this->request['key'] ? $this->request['key'] : "primary";

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
		$nonStatic = $this->registry->profunctions->getNonStaticGroups();
		if (count($nonStatic) && is_array($nonStatic)) {
			$this->registry->output->sidebar_extra = '';
			$this->registry->output->sidebar_extra .= "<ul><li class='has_sub'>{$this->lang->words['promenu_custom_menus']}";
			foreach ($nonStatic as $k => $c) {
				$this->registry->output->sidebar_extra .= "<ul><li><a href='" . $this->settings['base_url'] . "module=menus&amp;section=menus&amp;key=" . urlencode($k) . "'>" . ucwords($k) . "&nbsp;" . $this->lang->words['promenu_word_group'] . "</a></li></ul>";
			}
			$this->registry->output->sidebar_extra .= "</li></ul>";
		}
		/* Which do */
		switch ($this->request['do']) {
			case 'addForums':
				$this->addForums();
				break;
			case "addMenu" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_add_menu');
				$this->addMenu();
				break;
			case "editMenu" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_edit_menu');
				$this->editMenu();
				break;
			case "status" :
				$this->status();
				break;
			case "clone" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_clone_menu');
				$this->cloner();
				break;
			case "kerching" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_refresh_cache');
				$this->kerching();
				break;
			case "EditGroups" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_edit_group');
				$this->ManageGroups();
				break;
			case "AddGroup":
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_add_group');
				$this->ManageGroups();
				break;
			case "ExportGroup" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_export_group');
				$this->html = $this->registry->output->loadTemplate('skin_hooks', 'promenu');
				$this->ExportGroup();
				break;
			case "importApps" :
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_import_group');
				$this->importApps();
				break;
			case 'groupSettings':
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_add_group');
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_edit_group');
				$this->groupSettings();
				break;
			case "delgroup":
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_delete_group');
				$this->deletegroups();
				exit;
			case 'save':
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_add_menu');
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_edit_menu');
				$this->save();
				break;
			case 'deleteMenus':
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_delete_menu');
				$this->deleteMenus();
				break;
			case 'clones':
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_clone_menu');
				$this->attack_of_the_clones();
				break;
			case "export":
				$this->registry->class_permissions->checkPermissionAutoMsg('promenu_export_group');
				$this->export();
				break;
			case 'move':
				$this->move();
				break;
			case 'cloneGroup':
				$this->cloneGroup();
				break;
			default :
				$this->groups();
				break;
		}

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	protected function addForums() {
		if ($this->request['cancel']) {
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['group']));
		}
		$permsToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/classes/proPerms.php', 'proPerms', 'promenu');

		$perms = new $permsToLoad($this->registry);
		
		require_once( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php' ); /* noLibHook */
		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('forums') . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums');

		$aff = new $classToLoad($this->registry);
		$aff->forumsInit();

		$group = $this->request['group'];
		
		$fc = $aff->adForumsForumList(1);
		
		$langer = $this->caches['lang_data'];
		$addjoin = array(
							array(
									'select' => 'p.perm_view',
									'from' => array('permission_index' => 'p'),
									'where' => "p.perm_type = 'forum' AND p.app = 'forums' AND p.perm_type_id = m.id",
									'type' => 'left'
							)
		);		
		$c = $this->DB->buildAndFetch(array(
											'select' => "m.*",
											'from' => array("forums" =>"m"),
											'where' => "m.id={$this->request['new_key']}",
											'add_join' => $addjoin
		));

		foreach ($langer as $ks => $cs) {
			$lang[$cs['lang_id']] = $c['name'];
			if ($c['description']) {
				$langdes[$cs['lang_id']] = $c['description'];
			}
		}

		$a['promenu_menus_name'] = serialize($lang);
		$a['promenu_menus_parent_id'] = 0;
		if ($cs['description']) {
			$a['promenu_menus_desc'] = serialize($langdes);
		}
		$a['promenu_menus_group'] = $group;
		$a['promenu_menus_link_type'] = "man";
		$a['promenu_menus_url'] = 'showforum=' . $c['id'];
		$a['promenu_menus_view'] = $this->registry->profunctions->perm2group($c['perm_view']);
		$cd = $this->DB->buildAndFetch(array('select' => "COUNT(*) as count",'from' => "promenuplus_menus","where" => "promenu_menus_group='{$this->request['group']
		}'"));
		$a['promenu_menus_order'] = $cd['count']+1;
		$a['promenu_menus_forums_seo'] = $c['name_seo'];

		if (count($fc[$c['id']]) && is_array($fc[$c['id']])) {
			$a['promenu_menus_has_sub'] = 1;
		} else {
			$a['promenu_menus_has_sub'] = 0;
		}

		$a['promenu_menus_is_open'] = 0;

		$this->DB->insert("promenuplus_menus", $a);

		$ids = $this->DB->getInsertId();

		$p = $this->registry->profunctions->group2perm(explode(",", $a['promenu_menus_view']));

		$perms->savePermissionsMatrix($ids, $p);

		if ($a['promenu_menus_has_sub']) {
			$this->DB->update("promenuplus_menus", array("promenu_menus_has_sub" => 1), 'promenu_menus_id=' . $ids);
			$this->registry->profunctions->BuildForumChildren($ids, $group, $c['id']);
		}
		$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_menu_item_saved']}!");

		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($a['promenu_menus_group']));
	}

	protected function groups() {
		$this->registry->output->html .= $this->html->main_container($this->registry->profunctions->getMenusByParent());
	}

	protected function addMenu() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_add_menu']);
		$this->registry->output->html .= $this->html->containers(array());
	}

	protected function editMenu() {
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_edit_menu']);
		$this->registry->output->html .= $this->html->containers($this->registry->profunctions->getSingleMenu(intval($this->request['id'])));
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
		$this->registry->output->html .= $this->html->clone_wars(intval($this->request['id']));
	}

	protected function cloneGroup() {
		if ($this->request['cancel']) {
			//if they click save and reload, lets take them back to the edit page.
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_cloning_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['old_key']));
		}
		$new = $this->request['new_key'];
		$old = $this->request['old_key'];
		$data = $this->registry->profunctions->getMenusByParent(0, $old);

		if (count($data) && is_array($data)) {
			foreach ($data as $k => $v) {
				$this->send_in_the_clones($v['promenu_menus_id'], $new, 0);
			}
		}
		$this->registry->profunctions->kerching();
		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . $this->request['new_key'] . "&amp;id=" . $this->request['id']);
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

		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_manage_groups']);

		$this->registry->output->html .= $this->html->group_settings();
	}

	protected function ExportGroup() {
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		$this->registry->output->extra_nav[] = array('', $this->lang->words['promenu_menus_export_group']);
		$this->registry->output->html .= $this->html->hookForm();
	}

	protected function importApps() {
		if ($this->request['cancel']) {
			//if they click save and reload, lets take them back to the edit page.
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_importing_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['key']));
		}
		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}
		if (!$this->request['importAll']) {
			$this->registry->profunctions->buildMissingApps($this->request['key']);
		} else {
			$this->registry->profunctions->BuildDefaultMenus($this->request['key']);
		}
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
		} else {
			$a['promenu_groups_name'] = $this->DB->addSlashes(IPSText::alphanumericalClean(trim($this->request['promenu_groups_name'])));
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_group_add'], $a['promenu_groups_name']));
		}

		if (is_numeric($a['promenu_groups_name'][0])) {
			$a['promenu_groups_name'] = "Pro" . $a['promenu_groups_name'];
		}

		$a['promenu_groups_enabled'] = intval($this->request['promenu_groups_enabled']);

		$a['promenu_groups_tab_activation'] = intval($this->request['promenu_groups_tab_activation']);

		$a['promenu_groups_template'] = $this->request['promenu_groups_template'];

		$a['promenu_groups_is_vertical'] = $this->request['promenu_groups_is_vertical'];

		$a['promenu_groups_border'] = $this->request['promenu_groups_border'];

		$a['promenu_groups_make_super'] = $this->request['promenu_groups_make_super'] ? 1 : 0;
		$a['promenu_groups_add_other'] = intval($this->request['promenu_groups_add_other']);
		if ($this->request['promenu_groups_allow_effects']) {
			$a ['promenu_groups_promore'] = intval($this->request['promenu_groups_promore']);
			$a['promenu_groups_behavoir'] = intval($this->request['promenu_groups_behavoir']);

			$a['promenu_groups_speed_open'] = intval($this->request['promenu_groups_speed_open']);

			$a['promenu_groups_speed_close'] = intval($this->request['promenu_groups_speed_close']);

			$a['promenu_groups_animation_open'] = $this->request['promenu_groups_animation_open'];

			$a['promenu_groups_animation_close'] = $this->request['promenu_groups_animation_close'];

			$a['promenu_groups_hover_speed'] = $this->request['promenu_groups_hover_speed'];

			$a['promenu_groups_top_offset'] = $this->request['promenu_groups_top_offset'];

			$a['promenu_groups_allow_docking'] = $this->request['promenu_groups_allow_docking'] ? $this->request['promenu_groups_allow_docking'] : 0;

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
			$this->registry->output->showError(sprintf($this->lang->words['promenu_error_numeric'], $this->lang->words['promenu_index_limit']), '20Promenu3');
		}

		$a['promenu_groups_default_mobile'] = intval($this->request['promenu_groups_default_mobile']);
		$a['promenu_groups_default_position'] = intval($this->request['promenu_groups_default_position']);
		$a['promenu_groups_min_width'] = intval($this->request['promenu_groups_min_width']);
		if (!$this->request['original_key']) {
			$check = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenuplus_groups', 'where' => 'promenu_groups_name="' . $a['promenu_groups_name'] . '"'));
		} else {
			$check['count'] = 0;
		}

		if (!intval($check['count'])) {
			if ($a['promenu_groups_make_super']) {
				$this->DB->update("promenuplus_groups", array("promenu_groups_make_super" => 0));
				$a['promenu_groups_allow_docking'] = 0;
			}
			if ($a['promenu_groups_allow_docking']) {
				$this->DB->update("promenuplus_groups", array("promenu_groups_allow_docking" => 0));
			}
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
				$this->DB->delete("permission_index", 'perm_type_id=' . $d['promenu_menus_id'] . ' AND app="promenu"');
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
		$this->registry->profunctions->getClass("postClassPromenu")->save();
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
		$names = $this->DB->buildAndFetch(array('select' => 'promenu_menus_name', 'from' => 'promenuplus_menus', 'where' => 'promenu_menus_id=' . $id));
		foreach (unserialize($names['promenu_menus_name']) as $k => $v) {
			if ($v) {
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
			$this->DB->delete("permission_index", 'perm_type_id=' . $id . ' AND app="promenu"');
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
			foreach (unserialize($names['promenu_menus_name']) as $k => $v) {
				if ($v) {
					$name = $v;
					break;
				}
			}
			$this->registry->adminFunctions->saveAdminLog(sprintf($this->lang->words['promenu_admin_menu_cloned'], $name, $this->request['new_key']));

			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_word_menu']}: " . $name . " {$this->lang->words['promenu_word_and']} {$this->lang->words['promenu_word_children']} {$this->lang->words['promenu_been_cloned']}.");
			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . $this->request['new_key']);
		}
	}

	protected function move() {
		if ($this->request['cancel']) {
			//if they click save and reload, lets take them back to the edit page.
			$this->registry->output->global_message = sprintf("{$this->lang->words['promenu_moving_cancelled']}!");

			$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . urlencode($this->request['old_key']));
		}

		if ($this->request['postkey'] != $this->member->form_hash) {
			$this->registry->output->showError($this->lang->words['promenu_error_security'], '50Promenu1');
		}

		$id = $this->request['id'];

		if (count($id) && is_array($id)) {
			foreach ($id as $k => $v) {
				$ids = $this->registry->profunctions->gatherIdForDel($v);
				foreach ($ids as $ks => $vs) {
					$this->DB->update("promenuplus_menus", array("promenu_menus_group" => $this->request['new_key']), "promenu_menus_id=" . $vs);
				}
			}
		}
		$this->registry->profunctions->kerching();
		$this->registry->output->global_message = $this->lang->words['promenu_mover'];
		$this->registry->output->silentRedirectWithMessage($this->settings['base_url'] . "module=menus&amp;section=menus&key=" . $this->request['new_key']);
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

		$replace = array($c['hook_classname'], $this->request['group'] . "_menu", $template, $this->request['group']);

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

		$content = str_replace(array("{menu_id}", "{group}"), array($this->request['group'] . "_menu", $this->request['group']), $content);

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
