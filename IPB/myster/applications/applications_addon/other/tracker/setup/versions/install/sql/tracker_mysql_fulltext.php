<?php

/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/
$INDEX[] = "ALTER TABLE tracker_posts ADD FULLTEXT KEY post (post);";
$INDEX[] = "ALTER TABLE tracker_issues ADD FULLTEXT KEY title (title);";

?>