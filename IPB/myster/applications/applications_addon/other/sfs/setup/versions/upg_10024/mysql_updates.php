<?php

$SQL[] = "ALTER TABLE pfields_content ADD sfsMemInfo text NOT NULL, ADD sfsNextCheck int(10) NOT NULL;";

$SQL[] = "ALTER TABLE sfs_blocked ADD blockDate int(10) NOT NULL;";

$SQL[] = "ALTER TABLE sfs_settings CHANGE errorMessage errorMessage mediumtext NOT NULL;";

?>