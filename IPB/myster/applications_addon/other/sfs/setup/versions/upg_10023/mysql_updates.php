<?php

$SQL[] = "ALTER TABLE sfs_blocked CHANGE blockReason blockUN varchar(75), ADD blockEM varchar(75), ADD blockIP varchar(75);";

$SQL[] = "ALTER TABLE sfs_settings ADD acpGraph tinyint(3) NOT NULL DEFAULT '10';";

$SQL[] = "CREATE TABLE sfs_tracking (
        `year` smallint(4) NOT NULL,
        `Jan` smallint(5) NOT NULL default '0',
        `Feb` smallint(5) NOT NULL default '0',
        `Mar` smallint(5) NOT NULL default '0',
        `Apr` smallint(5) NOT NULL default '0',
        `May` smallint(5) NOT NULL default '0',
        `Jun` smallint(5) NOT NULL default '0',
        `Jul` smallint(5) NOT NULL default '0',
        `Aug` smallint(5) NOT NULL default '0',
        `Sep` smallint(5) NOT NULL default '0',
        `Oct` smallint(5) NOT NULL default '0',
        `Nov` smallint(5) NOT NULL default '0',
        `Dec` smallint(5) NOT NULL default '0',
        `yearTot` int(10) NOT NULL default '0',
        PRIMARY KEY (year)
);";

$y = date(Y);

$SQL[] = "INSERT INTO sfs_tracking (`year`) VALUES ({$y});";

?>