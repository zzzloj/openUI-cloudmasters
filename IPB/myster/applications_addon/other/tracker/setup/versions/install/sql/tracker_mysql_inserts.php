<?php

// default data will go here nearer final release
$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app) VALUES(1, 'Tracker Plugin', 'This is the plugin for making reports with Tracker.', 'IPBTracker.com Project Developers', 'http://ipbtracker.com', 'v1.0.0', 'tracker', ',1,2,3,4,6,', ',4,6,', 'a:1:{s:15:"report_supermod";s:1:"1";}', 0, 'tracker');
EOF;

?>