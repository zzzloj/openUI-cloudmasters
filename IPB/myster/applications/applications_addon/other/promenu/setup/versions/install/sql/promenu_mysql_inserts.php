<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
$INSERT[] .= "INSERT INTO promenuplus_groups (promenu_groups_id, promenu_groups_name, promenu_groups_static, promenu_groups_enabled, promenu_groups_tab_activation, promenu_groups_behavoir, promenu_groups_speed_open, promenu_groups_speed_close, promenu_groups_animation_open, promenu_groups_animation_close, promenu_groups_allow_effects, promenu_groups_hover_speed, promenu_groups_hide_skin, promenu_groups_arrows_enabled, promenu_groups_zindex, promenu_groups_top_offset, promenu_groups_enable_alternate_lang_strings, promenu_groups_group_visibility_enabled, promenu_groups_template, promenu_groups_has_hook, promenu_groups_hash, promenu_groups_preview_close) VALUES
(1, 'primary', 1, 1, 1, 1, 200, 200, 'show', 'hide', 1, 500, '0', 1, 15000, 10, 0, 0, 'proMain', '0', '0', 1),
(7, 'site', 1, 0, 0, 1, 200, 200, 'show', 'hide', 0, 500, '0', 0, 0, 10, 0, 0, 'proOther', '0', '0', 1),
(5, 'footer', 1, 0, 0, 1, 200, 200, 'show', 'hide', 0, 500, '0', 0, 0, 10, 0, 0, 'proOther', '0', '0', 1),
(2, 'header', 1, 0, 0, 1, 200, 200, 'show', 'hide', 1, 500, '0', 0, 20000, 10, 0, 0, 'proMain', '0', '0', 1),
(6, 'mobile', 1, 0, 0, 1, 200, 200, 'show', 'hide', 0, 500, '0', 0, 0, 10, 0, 0, 'proMain', '0', '0', 1);";

$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/sources/profunctions.php', 'profunctions', 'promenu');
$profunctions = new $classToLoad(ipsRegistry::instance());

$profunctions->BuildDefaultMenus('primary');
