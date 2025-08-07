<?php

// Revert old settings
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_tdfontcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_tdrbgcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_tdbgcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_thfontcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_thbgcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_ulbgcolor';";
$SQL[]="DELETE from core_sys_conf_settings where conf_key = 'bbcodeTables_ulfontcolor';";

// Revert old templates
//$SQL[]="DELETE from skin_templates where template_name = 'bbcode_cell';";

?>