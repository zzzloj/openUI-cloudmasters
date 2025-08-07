<?php

# v1.4.0
$SQL[] = "DELETE FROM portal_blocks WHERE name='affiliates-block';";

# Remove old settings
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ('portal_show_fav', 'portal_fav');";