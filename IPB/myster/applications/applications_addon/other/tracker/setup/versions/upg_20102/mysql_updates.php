<?php

if( ipsRegistry::DB()->checkForTable( 'tracker_module_custom' ) )
{
	$SQL[] = "ALTER TABLE tracker_module_custom ADD position int(11) DEFAULT NULL;";
	$SQL[] = "ALTER TABLE tracker_module_custom ADD projects text;";
	$SQL[] = "ALTER TABLE tracker_module_custom ADD custom_perms text;";
	$SQL[] = "ALTER TABLE tracker_module_custom ADD issue_display_type varchar(10) DEFAULT 'info';";
}