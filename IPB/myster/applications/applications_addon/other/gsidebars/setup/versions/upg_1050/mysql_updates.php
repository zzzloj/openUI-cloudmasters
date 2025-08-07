<?php

$SQL[] = "ALTER TABLE dp3_gs_sidebars ADD s_wrapper tinyint(1) NOT NULL";

$SQL[] = "ALTER TABLE dp3_gs_sidebars CHANGE s_name s_name VARCHAR(255) NOT NULL DEFAULT ''";
$SQL[] = "ALTER TABLE dp3_gs_sidebars CHANGE s_type s_type VARCHAR(50) NOT NULL DEFAULT ''";
$SQL[] = "ALTER TABLE dp3_gs_sidebars CHANGE s_groups s_groups VARCHAR(255) NOT NULL DEFAULT ''";
$SQL[] = "ALTER TABLE dp3_gs_sidebars CHANGE s_adv_adverts s_adv_adverts VARCHAR(255) NOT NULL DEFAULT ''";
$SQL[] = "ALTER TABLE dp3_gs_sidebars CHANGE s_custom s_custom VARCHAR(255) NOT NULL DEFAULT ''";

$SQL[] = "ALTER TABLE dp3_gs_adverts CHANGE a_name a_name VARCHAR(255) NOT NULL DEFAULT ''";