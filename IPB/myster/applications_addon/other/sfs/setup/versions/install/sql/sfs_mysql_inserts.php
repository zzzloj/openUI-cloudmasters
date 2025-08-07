<?php

$INSERT[] = "INSERT INTO sfs_settings (`checkType`, `ipAtAll`, `ipNumTimes`, `ipDaysAgo`, `ipConfidence`, `emAtAll`, `emNumTimes`, `emDaysAgo`, `emConfidence`, `addBan`, `keepBanDays`, `errorMessage`, `blockCount`, `emailSub`, `statText`, `acpGraph`) VALUES (0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Your IP or Email Address is listed as a known spammer.', 0, 'A registration has been blocked', 'Spammers Stopped', '10');";

$y = date(Y);

$INSERT[] = "INSERT INTO sfs_tracking (`year`) VALUES ({$y});";

?>