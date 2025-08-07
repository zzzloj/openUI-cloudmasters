<?php
$PRE = ipsRegistry::dbFunctions()->getPrefix();

$QUERY[] = "DROP TABLE IF EXISTS {$PRE}blog_upgrade_history;";