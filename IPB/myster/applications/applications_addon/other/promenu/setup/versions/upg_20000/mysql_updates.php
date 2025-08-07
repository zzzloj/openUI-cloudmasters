<?php
/**
 * ProMenu
 * Provisionists LLC
 *  
 * @ Package : 			ProMenu
 * @ File : 			mysql_updates.php
 * @ Last Updated : 	Apr 17, 2012
 * @ Author :			Robert Simons
 * @ Copyright :		(c) 2011 Provisionists, LLC
 * @ Link	 :			http://www.provisionists.com/
 * @ Revision : 		2
 */

 $SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu';";
 
 $SQL[] = "DELETE FROM skin_css WHERE css_app='promenu';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_enable_sec_nav';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_enable_pri_nav';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_enable_nav_app';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_enable_default_app_view';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_disable_desc_hover';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_data_tooltip';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_default_app_desc';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_default_app_icon';";
 
 $SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='promenu_default_app_title';";
 
 $SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_default';";
 
 $SQL[] = "DELETE FROM core_hooks WHERE hook_key='display_promenu';";
 
 $SQL[] = "DELETE FROM core_hooks WHERE hook_key='promenu_prim_nav_removal';";
 
// $SQL[] = "DELETE FROM core_hook_files WHERE hook_classname='globalProMenuPrimNavApp';";
// 
// $SQL[] = "DELETE FROM core_hook_files WHERE hook_classname='globalProMenuSecNavApp';";
// 
// $SQL[] = "DELETE FROM core_hook_files WHERE hook_classname='globalProMenuJava';";
// 
// $SQL[] = "DELETE FROM core_hook_files WHERE hook_classname='globalProMenuNavDisplay';";
 
 $SQL[] = "DELETE FROM core_sys_settings_titles WHERE conf_title_app='promenu';";

 $SQL[] = "ALTER TABLE promenu ADD promenu_as_cat int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_title_image int(1) NOT NULL DEFAULT 0";

 $SQL[] = "ALTER TABLE promenu ADD promenu_use_block int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_block_content mediumtext";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_group_mega int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_new_row int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_group_id int(10) NOT NULL DEFAULT 1";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_group_key varchar(50) NOT NULL DEFAULT 'primary_menus'";
 
 $SQL[] = "CREATE TABLE promenu_groups (
	promenu_group_id				int(10) NOT NULL auto_increment,
	promenu_group_title				varchar(225) NOT NULL,
	promenu_group_description		varchar(255) NOT NULL,
	promenu_group_displayorder		int(10) NOT NULL,
	promenu_group_key				varchar(50) NOT NULL,
	promenu_group_mega				int(10) NOT NULL DEFAULT 0,
	PRIMARY KEY (promenu_group_id)
);";
 
 $SQL[] = "INSERT INTO promenu_groups (`promenu_group_id`, `promenu_group_title`, `promenu_group_description`, `promenu_group_displayorder`, `promenu_group_key`, `promenu_group_mega`) VALUES
(1, 'Primary Navigation Menus', 'Primary Replacement Navigation', 2, 'primary_menus', 0)";
 
 $SQL[] = "ALTER TABLE promenu_groups AUTO_INCREMENT= 21";

