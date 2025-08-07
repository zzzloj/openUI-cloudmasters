<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
$SQL[] = "DELETE FROM core_sys_lang_words WHERE word_app='promenu'";

$SQL[] = "DELETE FROM core_sys_module WHERE sys_module_application='promenu' AND sys_module_key='settings';";

$SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu';";

$SQL[] = "DELETE FROM skin_css WHERE css_app='promenu';";

$SQL[] = "DELETE FROM skin_css WHERE css_group='promenu_wrapperless';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_display_footer';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_display_header';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='display_promenu_java';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_display_primary';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_prim_nav_removal';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_display_mobile_primary';";

$SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_display_bottom_bar';";

$SQL[] = "CREATE TABLE promenuplus_groups (
  promenu_groups_id int(255) NOT NULL AUTO_INCREMENT,
  promenu_groups_name text,
  promenu_groups_static tinyint(1) NOT NULL DEFAULT '0',
  promenu_groups_enabled tinyint(1) NOT NULL DEFAULT '1',
  promenu_groups_tab_activation tinyint(1) NOT NULL DEFAULT '0',
  promenu_groups_behavoir tinyint(1) NOT NULL DEFAULT '1',
  promenu_groups_speed_open int(6) NOT NULL DEFAULT '200',
  promenu_groups_speed_close int(6) NOT NULL DEFAULT '200',
  promenu_groups_animation_open varchar(10) NOT NULL DEFAULT 'show',
  promenu_groups_animation_close varchar(10) NOT NULL DEFAULT 'hide',
  promenu_groups_allow_effects tinyint(1) NOT NULL DEFAULT '1',
  promenu_groups_hover_speed smallint(4) NOT NULL DEFAULT '500',
  promenu_groups_hide_skin text,
  promenu_groups_arrows_enabled tinyint(1) NOT NULL DEFAULT '1',
  promenu_groups_zindex mediumint(6) NOT NULL DEFAULT '10000',
  promenu_groups_top_offset tinyint(4) NOT NULL DEFAULT '10',
  promenu_groups_enable_alternate_lang_strings tinyint(1) NOT NULL DEFAULT '0',
  promenu_groups_group_visibility_enabled tinyint(1) NOT NULL DEFAULT '0',
  promenu_groups_template varchar(10) NOT NULL DEFAULT 'proMain',
  promenu_groups_has_hook text,
  promenu_groups_hash varchar(255) NOT NULL DEFAULT '0',
  promenu_groups_preview_close tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (promenu_groups_id)
);";

$SQL[] = "CREATE TABLE promenuplus_menus (
  promenu_menus_id int(255) NOT NULL AUTO_INCREMENT,
  promenu_menus_name text NOT NULL,
  promenu_menus_parent_id int(20) NOT NULL,
  promenu_menus_img_as_title_check tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_title_icon text,
  promenu_menus_img_as_title_w smallint(3) NOT NULL DEFAULT '14',
  promenu_menus_img_as_title_h smallint(3) NOT NULL DEFAULT '14',
  promenu_menus_desc text,
  promenu_menus_icon text,
  promenu_menus_icon_check tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_icon_w smallint(6) NOT NULL DEFAULT '14',
  promenu_menus_icon_h smallint(6) NOT NULL DEFAULT '14',
  promenu_menus_group varchar(255) DEFAULT 'primary',
  promenu_menus_link_type varchar(25) DEFAULT NULL,
  promenu_menus_url text,
  promenu_menus_app_link varchar(75) DEFAULT NULL,
  promenu_menus_forums_attatch tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_forums_parent_id varchar(10) NOT NULL DEFAULT '0',
  promenu_menus_forums_id tinyint(10) NOT NULL DEFAULT '0',
  promenu_menus_forums_seo varchar(255) NOT NULL DEFAULT '0',
  promenu_menus_block text,
  promenu_menus_content_link tinyint(6) NOT NULL DEFAULT '0',
  promenu_menus_easy_link varchar(255) DEFAULT '0',
  promenu_menus_attr text,
  promenu_menus_override tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_view varchar(100) NOT NULL DEFAULT '0',
  promenu_menus_order int(255) NOT NULL DEFAULT '0',
  promenu_menus_has_sub tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_is_open smallint(1) NOT NULL DEFAULT '0',
  promenu_menus_is_mega tinyint(1) NOT NULL DEFAULT '0',
  promenu_menus_mega_column_count tinyint(6) NOT NULL DEFAULT '3',
  PRIMARY KEY (promenu_menus_id)
);";