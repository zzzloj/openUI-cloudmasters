<?php

$SQL[] = "ALTER TABLE dp3_gs_sidebars ADD s_groups varchar(255) NOT NULL";
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key='dp3_gsidebars_groups';";