<?php

// Drop fields from groups table - Beta 2 refresh
/*$QUERY[] = "ALTER TABLE groups DROP g_tracker_view_offline;";
$QUERY[] = "ALTER TABLE groups DROP g_tracker_attach_max;";
$QUERY[] = "ALTER TABLE groups DROP g_tracker_attach_per_post;";*/

if ( @file_exists( DOC_IPS_ROOT_PATH . 'cache/tracker/tracker_mysql_tables.php' ) )
{
	@unlink( DOC_IPS_ROOT_PATH . 'cache/tracker/tracker_mysql_tables.php' );
}

?>