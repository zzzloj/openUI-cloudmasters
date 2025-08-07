<?php
$TABLE[] = "CREATE TABLE sfs_blocked (
        blockID mediumint(8) NOT NULL auto_increment,
        blockedBy varchar(6) NOT NULL,
        blockDate int(10) NOT NULL,
        blockUN varchar(75) NOT NULL,
        blockEM varchar(75) NOT NULL,
        blockIP varchar(75) NOT NULL,
        timesBlocked mediumint(8) NOT NULL,
        sfsFreq mediumint(8) NOT NULL,
        sfsLast int(10) NOT NULL,
        sfsConf smallint(3) NOT NULL,
        PRIMARY KEY (blockID)
);";

$TABLE[] = "CREATE TABLE sfs_whitelist (
        wlID mediumint(8) NOT NULL auto_increment,
        wlInfo varchar(75) NOT NULL,
        wlEntry int(10) NOT NULL,
        PRIMARY KEY (wlID)
);";

$TABLE[] = "CREATE TABLE sfs_settings (
        checkType tinyint(1) NOT NULL default '0',
        ipAtAll tinyint(1) NOT NULL default '0',
        ipNumTimes smallint(6) NOT NULL default '0',
        ipDaysAgo smallint(6) NOT NULL default '0',
        ipConfidence smallint(6) NOT NULL default '0',
        emAtAll tinyint(1) NOT NULL default '0',
        emNumTimes smallint(6) NOT NULL default '0',
        emDaysAgo smallint(6) NOT NULL default '0',
        emConfidence smallint(6) NOT NULL default '0',
        addBan tinyint(1) NOT NULL default '0',
        keepBanDays smallint(6) NOT NULL default '0',
        errorMessage mediumtext NOT NULL,
        apiKey varchar(100) NOT NULL default '',
        blockCount bigint(11) NOT NULL default '0',
        emailTo varchar(100) NOT NULL default '',
        emailSub varchar(100) NOT NULL default 'A registration has been blocked',
        statText varchar(100) NOT NULL default 'Spammers Stopped',
        acpGraph tinyint(3) NOT NULL default '10'
);";

$TABLE[] = "CREATE TABLE sfs_tracking (
        `year` SMALLINT( 4 ) NOT NULL,
        `Jan` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Feb` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Mar` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Apr` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `May` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Jun` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Jul` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Aug` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Sep` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Oct` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Nov` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `Dec` SMALLINT( 5 ) NOT NULL DEFAULT '0',
        `yearTot` INT( 10 ) NOT NULL DEFAULT '0',
        PRIMARY KEY ( `year` ) 
);";

$TABLE[] = "ALTER TABLE pfields_content ADD sfsMemInfo text NOT NULL, ADD sfsNextCheck int(10) NOT NULL;";
?>