<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
class version_upgrade {

	protected $_output = '';

	public function fetchOutput() {
		return $this->_output;
	}

	public function doExecute(ipsRegistry $registry) {
		/* Make object */
		$this->registry = $registry;
		$this->DB = $this->registry->DB();
		$this->settings = & $this->registry->fetchSettings();
		$this->request = & $this->registry->fetchRequest();
		$this->cache = $this->registry->cache();
		$this->caches = & $this->registry->cache()->fetchCaches();

		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		switch ($this->request['workact']) {
			default:
			case 'step1':
				$this->doFirstThing();

				$this->request['workact'] = 'step2';
				break;

			case 'step2':
				$this->doSecondThing();

				$this->request['workact'] = 'step3';
				break;

			case 'step3':
				$this->doThirdThing();

				$this->request['workact'] = 'step4';
				break;

			case 'step4':
				$this->doForthThing();

				$this->request['workact'] = 'step5';
				break;

			case 'step5':
				$this->doFifthThing();

				$this->request['workact'] = 'step6';
				break;

			case 'step6':
				$this->doSixthThing();

				$this->request['workact'] = 'step7';
				break;

			case 'step7':
				$this->doSeventhThing();

				$this->request['workact'] = 'step8';
				break;

			case 'step8':
				$this->doEigthThing();

				$this->request['workact'] = 'step9';
				break;

			case 'step9':
				$this->doNinethThing();

				$this->request['workact'] = 'step10';
				break;
				
			case 'step10':
				$this->doTenthThing();

				$this->request['workact'] = '';
				break;
		}

		//-----------------------------------------
		// Return false if there's more to do
		//-----------------------------------------

		if ($this->request['workact']) {
			return false;
		} else {
			return true;
		}
	}

	public function doFirstThing() {
		$this->registry->output->addMessage("Converting Groups ...");
	}

	public function doSecondThing() {
		/* Start Group Conversion */

		$this->DB->build(array('select' => '*',
			'from' => 'promenu_groups'));
		$_groups = $this->DB->execute();

		while ($g = $this->DB->fetch($_groups)) {
			$gid = $g['promenu_group_id'];
			$gtitle = $g['promenu_group_title'];
			$gkey = $g['promenu_group_key'];
			$hash = '';
			$zdex = 10000;
			$default = 0;
			/* Lets define some templates and grab some settings for the new groups */

			if ($gkey == 'primary_menus') {
				$gtitle = 'primary';
				$template = 'proMain';
				$enabled = $this->settings['promenu_enable_primary_menus'];
				$zdex = 15000;
			} elseif ($gkey == 'bottom_bar_menus') {
				$gtitle = 'site';
				$template = 'proOther';
				$enabled = $this->settings['promenu_enable_bottom_bar_menus'];
			} elseif ($gkey == 'footer_menus') {
				$gtitle = 'footer';
				$template = 'proOther';
				$enabled = $this->settings['promenu_enable_footer_menus'];
			} elseif ($gkey == 'header_menus') {
				$gtitle = 'header';
				$template = 'proMain';
				$enabled = $this->settings['promenu_enable_header_menus'];
				$zdex = 20000;
			} elseif ($gkey == 'mobile_primary') {
				$gtitle = 'mobile';
				$template = 'proMain';
				$enabled = $this->settings['promenu_enable_mobile_primary'];
			} else {
				$gtitle = $gtitle;
				$template = 'proMain';
				$hash = md5(IPS_UNIX_TIME_NOW);
				$enabled = 1;
			}

			/* Time to grab the settings */

			$behavior = $this->settings['behavior'] + 1;
			$sopen = $this->settings['ShowSpeed'];
			$sclose = $this->settings['HideSpeed'];
			$aopen = $this->settings['animationShow'];
			$aclose = $this->settings['animationHiding'];
			$offset = $this->settings['TopOffSet'];
			$groupv = $this->settings['promenu_group_perm_view'];

			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_id'] = $gid;
			$addGItem['promenu_groups_name'] = $gtitle;
			$addGItem['promenu_groups_static'] = $default ? 1 : 0;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 1;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 1;
			$addGItem['promenu_groups_hover_speed'] = 500;
			$addGItem['promenu_groups_hide_skin'] = 2;
			$addGItem['promenu_groups_arrows_enabled'] = 1;
			$addGItem['promenu_groups_zindex'] = $zdex;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = 0;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = $template;
			$addGItem['promenu_groups_has_hook'] = 0;
			$addGItem['promenu_groups_hash'] = $hash;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}
		$this->registry->output->addMessage("Groups Converted Successfully ...");
	}

	public function doThirdThing() {
	
		$this->registry->output->addMessage("Verifying Groups ...");
		
		/* Start Group Conversion */		
		$static = 1;
		$enabled = 0;
		$behavior = 1;
		$sopen = 200;
		$sclose = 200;
		$aopen = 'show';
		$aclose = 'hide';
		$hoverspeed = 500;
		$hideskin = 0;
		$arrows = 0;
		$offset = 10;
		$lang = 0;
		$groupv = 0;
		$hook = 0;
		$hash = 0;
		$close = 1;		
		
		$this->DB->build(array('select' => 'promenu_groups_name',
			'from' => 'promenuplus_groups'));
		$_groups = $this->DB->execute();
		
		while ($g = $this->DB->fetch($_groups)) {		
			$group_list[] = $g['promenu_groups_name'];
		}
		
		if (!in_array('primary', $group_list)) {
		
			$this->registry->output->addMessage("Primary Group Missing ... Recreating");
			
			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_name'] = 'primary';
			$addGItem['promenu_groups_static'] = $static;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 1;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 1;
			$addGItem['promenu_groups_hover_speed'] = $hoverspeed;
			$addGItem['promenu_groups_hide_skin'] = $hideskin;
			$addGItem['promenu_groups_arrows_enabled'] = $arrows;
			$addGItem['promenu_groups_zindex'] = 15000;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = $lang;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = 'proMain';
			$addGItem['promenu_groups_has_hook'] = $hook;
			$addGItem['promenu_groups_hash'] = $hash;
			$addGItem['promenu_groups_preview_close'] = $close;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}

		if (!in_array('site', $group_list)) {
		
			$this->registry->output->addMessage("Site Group Missing ... Recreating");
			
			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_name'] = 'site';
			$addGItem['promenu_groups_static'] = $static;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 0;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 0;
			$addGItem['promenu_groups_hover_speed'] = $hoverspeed;
			$addGItem['promenu_groups_hide_skin'] = $hideskin;
			$addGItem['promenu_groups_arrows_enabled'] = $arrows;
			$addGItem['promenu_groups_zindex'] = 0;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = $lang;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = 'proOther';
			$addGItem['promenu_groups_has_hook'] = $hook;
			$addGItem['promenu_groups_hash'] = $hash;
			$addGItem['promenu_groups_preview_close'] = $close;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}
		
		if (!in_array('footer', $group_list)) {
		
			$this->registry->output->addMessage("Footer Group Missing ... Recreating");
			
			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_name'] = 'footer';
			$addGItem['promenu_groups_static'] = $static;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 0;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 0;
			$addGItem['promenu_groups_hover_speed'] = $hoverspeed;
			$addGItem['promenu_groups_hide_skin'] = $hideskin;
			$addGItem['promenu_groups_arrows_enabled'] = $arrows;
			$addGItem['promenu_groups_zindex'] = 0;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = $lang;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = 'proOther';
			$addGItem['promenu_groups_has_hook'] = $hook;
			$addGItem['promenu_groups_hash'] = $hash;
			$addGItem['promenu_groups_preview_close'] = $close;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}
		
		if (!in_array('header', $group_list)) {
		
			$this->registry->output->addMessage("Header Group Missing ... Recreating");
			
			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_name'] = 'header';
			$addGItem['promenu_groups_static'] = $static;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 1;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 1;
			$addGItem['promenu_groups_hover_speed'] = $hoverspeed;
			$addGItem['promenu_groups_hide_skin'] = $hideskin;
			$addGItem['promenu_groups_arrows_enabled'] = $arrows;
			$addGItem['promenu_groups_zindex'] = 20000;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = $lang;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = 'proMain';
			$addGItem['promenu_groups_has_hook'] = $hook;
			$addGItem['promenu_groups_hash'] = $hash;
			$addGItem['promenu_groups_preview_close'] = $close;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}
		
		if (!in_array('mobile', $group_list)) {
		
			$this->registry->output->addMessage("Mobile Group Missing ... Recreating");
			
			/* Lets build an array to feed to the database */
			$addGItem['promenu_groups_name'] = 'mobile';
			$addGItem['promenu_groups_static'] = $static;
			$addGItem['promenu_groups_enabled'] = $enabled;
			$addGItem['promenu_groups_tab_activation'] = 0;
			$addGItem['promenu_groups_behavoir'] = $behavior;
			$addGItem['promenu_groups_speed_open'] = $sopen;
			$addGItem['promenu_groups_speed_close'] = $sclose;
			$addGItem['promenu_groups_animation_open'] = $aopen;
			$addGItem['promenu_groups_animation_close'] = $aclose;
			$addGItem['promenu_groups_allow_effects'] = 0;
			$addGItem['promenu_groups_hover_speed'] = $hoverspeed;
			$addGItem['promenu_groups_hide_skin'] = $hideskin;
			$addGItem['promenu_groups_arrows_enabled'] = $arrows;
			$addGItem['promenu_groups_zindex'] = 0;
			$addGItem['promenu_groups_top_offset'] = $offset;
			$addGItem['promenu_groups_enable_alternate_lang_strings'] = $lang;
			$addGItem['promenu_groups_group_visibility_enabled'] = $groupv;
			$addGItem['promenu_groups_template'] = 'proOther';
			$addGItem['promenu_groups_has_hook'] = $hook;
			$addGItem['promenu_groups_hash'] = $hash;
			$addGItem['promenu_groups_preview_close'] = $close;

			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_groups", $addGItem
			);
		}
	}
	
	public function doForthThing() {
		$this->registry->output->addMessage("All Groups Verified ...");
		$this->registry->output->addMessage("Converting Menus ...");
	}

	public function doFifthThing() {
		/* Start Menu Conversion */

		$this->DB->build(array('select' => '*',
			'from' => 'promenu'));
		$_menus = $this->DB->execute();

		while ($m = $this->DB->fetch($_menus)) {
			/* Lets build a ame and description for each language */
			foreach ($this->caches['lang_data'] as $t => $z) {
				$menuname[$z['lang_id']] = $m['promenu_title'];
				$menudesc[$z['lang_id']] = $m['promenu_description'];
			}
			$name = serialize($menuname);
			$desc = serialize($menudesc);

			/* Time to convert those keys!!! */
			
			$group = '';
			
			if ($m['promenu_group_key'] == 'primary_menus') {
				$group = 'primary';
			} elseif ($m['promenu_group_key'] == 'bottom_bar_menus') {
				$group = 'site';
			} elseif ($m['promenu_group_key'] == 'footer_menus') {
				$group = 'footer';
			} elseif ($m['promenu_group_key'] == 'header_menus') {
				$group = 'header';
			} elseif ($m['promenu_group_key'] == 'mobile_primary') {
				$group = 'mobile';
			} else {
				$group = $m['promenu_group_key'];
			}

			/* Here we try to determine the type of link your menu has and convert it */

			$type = '';
			$app = '';
			$url = '';
			
			if ($m['promenu_url']) {
				$type = 'man';
				$app = '';
				$url = $m['promenu_url'];
				$block_content = '';
			} else if ($m['link_to_app'] == 1 AND !$m['promenu_url']) {
				$type = 'app';
				$app = $m['promenu_use_app'];
				$url = '';
				$block_content = '';
			} else if ($m['promenu_use_block'] == 1 AND $m['promenu_block_content']) {
				$type = 'html';
				$app = '';
				$url = $m['promenu_url'];
				$block_content = $m['promenu_block_content'];
			} else {
				$type = 'def';
				$app = '';
				$url = '';
				$block_content = '';
			}
			
			/* here we are converting several old menu settings into an attributes block */

			$attribute = '';

			if ($m['promenu_description'] || $m['promenu_open_new_window'] == 1) {

				$data = '';
				
				if($m['promenu_description']){
					if ($m['promenu_data_tooltip'] == 1) {
						$data = "data-tooltip='{promenu_desc}' ";
					} else {
						$data = "title='{promenu_desc}' ";
					}
				}
				
				$target = '';

				if ($m['promenu_open_new_window'] == 1) {
					$target = "target='_blank'";
				}

				$att['class'] = '';
				$att['style'] = '';
				$att['attr'] = $data . $target;

				$attribute = serialize($att);
			}

			/* time to find out if this menu has subs!!!! */

			$hassubs = 0;

			$c = $this->DB->buildAndFetch(array('select' => 'COUNT(*) as count', 'from' => 'promenu', 'where' => 'promenu_parent_id=' . $m['promenu_id']));

			if ($c['count'] >= 1) {
				$hassubs = 1;
			}

			/* we are created a new icon system, so we need to check to see which one to assign it too */

			if ($m['promenu_title_image'] AND $m['promenu_icon']) {
				$custom_check = 0;
				$title_check = 1;
				$ticonURL = $m['promenu_icon'];
				$iconURL = '';
			}
			elseif (!$m['promenu_title_image'] AND $m['promenu_icon']) {
				$custom_check = 1;
				$title_check = 0;
				$ticonURL = '';
				$iconURL = $m['promenu_icon'];
			}
			else {
				$custom_check = 0;
				$title_check = 0;
				$ticonURL = '';
				$iconURL = '';			
			}

			/* are we currently using mega? */

			$mega = '';

			if ($m['promenu_group_key'] == 'footer_menus' || $m['promenu_group_key'] == 'bottom_bar_menus' || $m['promenu_group_key'] == 'mobile_primary') {
				$mega = 0;
			}
			else {
				if ($m['promenu_Nomega'] == 1 AND $m['promenu_parent_id'] == 0) {
					$mega = $m['promenu_Nomega'];
				}
				else {
					$mega = 0;
				}
			}
			
			/* Lets build an array to feed to the database */
			$addMItem['promenu_menus_id'] = $m['promenu_id'];
			$addMItem['promenu_menus_name'] = $name;
			$addMItem['promenu_menus_parent_id'] = $m['promenu_parent_id'];
			$addMItem['promenu_menus_img_as_title_check'] = $title_check;
			$addMItem['promenu_menus_title_icon'] = $ticonURL;
			$addMItem['promenu_menus_img_as_title_w'] = 14;
			$addMItem['promenu_menus_img_as_title_h'] = 14;
			$addMItem['promenu_menus_desc'] = $desc;
			$addMItem['promenu_menus_icon'] = $iconURL;
			$addMItem['promenu_menus_icon_check'] = $custom_check;
			$addMItem['promenu_menus_icon_w'] = 14;
			$addMItem['promenu_menus_icon_h'] = 14;
			$addMItem['promenu_menus_group'] = $group;
			$addMItem['promenu_menus_link_type'] = $type;
			$addMItem['promenu_menus_url'] = $url;
			$addMItem['promenu_menus_app_link'] = $app;
			$addMItem['promenu_menus_forums_attatch'] = 0;
			$addMItem['promenu_menus_forums_parent_id'] = 0;
			$addMItem['promenu_menus_forums_id'] = 0;
			$addMItem['promenu_menus_forums_seo'] = 0;
			$addMItem['promenu_menus_block'] = $block_content;
			$addMItem['promenu_menus_attr'] = $attribute;
			$addMItem['promenu_menus_override'] = $m['promenu_view_override'];
			$addMItem['promenu_menus_view'] = $m['promenu_view_menu'];
			$addMItem['promenu_menus_order'] = $m['promenu_displayorder'];
			$addMItem['promenu_menus_has_sub'] = $hassubs;
			$addMItem['promenu_menus_is_open'] = 0;
			$addMItem['promenu_menus_is_mega'] = $mega;
			$addMItem['promenu_menus_mega_column_count'] = 3;

			if($m['promenu_use_app'] == "ccs"){
				$addMItem['promenu_menus_content_link'] = $m['promenu_app_page'];
			}
			else if($m['promenu_use_app'] == "easypages"){
				$addMItem['promenu_menus_easy_link'] = $m['promenu_app_epage'];
			}
			
			/* Lets insert the array and build a new menu */
			$this->DB->insert(
					"promenuplus_menus", $addMItem
			);
		}
	}

	public function doSixthThing() {
		$this->registry->output->addMessage("Menus Converted Successfully ...");
	}

	public function doSeventhThing() {
		$this->registry->output->addMessage("Conversion Process Complete ...");
	}

	public function doEigthThing() {
		$this->registry->output->addMessage("Starting Cleaning Process ...");
	}

	public function doNinethThing() {
		/* Let's remove the old settings */
		$this->DB->build(array('select' => 'conf_title_id',
			'from' => 'core_sys_settings_titles',
			'where' => 'conf_title_keyword="promenu_effects_settings" OR conf_title_keyword="promenu_settings" OR conf_title_keyword="promenu_group_display"'));
		$_setting = $this->DB->execute();

		while ($m = $this->DB->fetch($_setting)) {
			$this->DB->delete("core_sys_conf_settings", "conf_group={$m['conf_title_id']}");
		}

		$this->DB->delete("core_sys_settings_titles", "conf_title_keyword='promenu_effects_settings'");

		$this->DB->delete("core_sys_settings_titles", "conf_title_keyword='promenu_settings'");

		$this->DB->delete("core_sys_settings_titles", "conf_title_keyword='promenu_group_display'");

		/* Let's remove the old tables */
		$this->DB->droptable("promenu");
		$this->DB->droptable("promenu_bugs");
		$this->DB->droptable("promenu_groups");

		/* Let's remove the old files and folders to avoid confusion */

		/* lets build the paths */
		$sourcesPath = IPSLib::getAppDir('promenu') . '/sources/';
		$classpath = IPSLib::getAppDir('promenu') . '/sources/classes/';
		$overviewPath = IPSLib::getAppDir('promenu') . '/modules_admin/overview/';
		$skincpPath = IPSLib::getAppDir('promenu') . '/skin_cp/';
		$hooksPath = IPSLib::getAppDir('promenu') . '/xml/hooks/';

		/* time to unlink the files and remove the directories */
		@unlink($sourcesPath . 'hooks.php');
		@unlink($sourcesPath . 'news_update.php');
		@unlink($classpath . 'class_bugs.php');
		@unlink($classpath . 'class_functions.php');
		@unlink($classpath . 'class_groups.php');
		@unlink($classpath . 'class_menus.php');
		@unlink($classpath . 'class_perms.php');
		@unlink($classpath . 'index.html');
		@rmdir($sourcesPath . 'classes');
		@unlink($overviewPath . 'overview.php');
		@unlink($overviewPath . 'defaultSection.php');
		@unlink($overviewPath . 'index.html');
		@unlink($overviewPath . 'xml/menu.xml');
		@unlink($overviewPath . 'xml/permissions.xml');
		@unlink($overviewPath . 'xml/index.html');
		@rmdir($overviewPath . 'xml');
		@rmdir(IPSLib::getAppDir('promenu') . '/modules_admin/overview');
		@unlink($skincpPath . 'cp_skin_add_group.php');
		@unlink($skincpPath . 'cp_skin_add_menu.php');
		@unlink($skincpPath . 'cp_skin_edit_group.php');
		@unlink($skincpPath . 'cp_skin_edit_menu.php');
		@unlink($skincpPath . 'cp_skin_edit_perms.php');
		@unlink($skincpPath . 'cp_skin_edit_visibility.php');
		@unlink($skincpPath . 'cp_skin_groups.php');
		@unlink($skincpPath . 'cp_skin_menus.php');
		@unlink($skincpPath . 'cp_skin_overview.php');
		@unlink($hooksPath . 'Promenu.Bottom.Bar.Display.Tool.xml');
		@unlink($hooksPath . 'ProMenu.Footer.Display.Tool.xml');
		@unlink($hooksPath . 'ProMenu.Header.Display.Tool.xml');
		@unlink($hooksPath . 'ProMenu.Javascripts.xml');
		@unlink($hooksPath . 'ProMenu.Mobile.Primary.Display.Tool.xml');
		@unlink($hooksPath . 'ProMenu.Primary.Display.Tool.xml');
		@unlink($hooksPath . 'ProMenu.Removal.Tool.xml');
		@unlink(IPSLib::getAppDir('promenu') . '/modules_admin/menus/groups.php');

		return true;
	}

	public function doTenthThing() {
		$this->registry->output->addMessage("Cleaning Process Complete...");
	}

}