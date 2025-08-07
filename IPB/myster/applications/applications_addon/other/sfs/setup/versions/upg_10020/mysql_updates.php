<?php

$SQL[] = "CREATE TABLE sfs_settings (
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
        errorMessage varchar(255) NOT NULL default 'Your IP or Email Address is listed as a known spammer.',
        apiKey varchar(100) NOT NULL default '',
        blockCount bigint(11) NOT NULL default '0',
        emailTo varchar(100) NOT NULL default '',
        emailSub varchar(100) NOT NULL default 'A registration has been blocked',
        statText varchar(100) NOT NULL default 'Spammers Stopped'
);";

$SQL[] = "INSERT INTO sfs_settings (`checkType`, `ipAtAll`, `ipNumTimes`, `ipDaysAgo`, `ipConfidence`, `emAtAll`, `emNumTimes`, `emDaysAgo`, `emConfidence`, `addBan`, `keepBanDays`, `errorMessage`, `blockCOunt`, `emailSub`, `statText`) VALUES (0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Your IP or Email Address is listed as a known spammer.', 0, 'A registration has been blocked', 'Spammers Stopped');";

?>