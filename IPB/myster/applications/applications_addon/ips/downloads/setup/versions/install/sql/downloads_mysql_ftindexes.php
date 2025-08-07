<?php

$INDEX[] = "ALTER TABLE downloads_files ADD FULLTEXT(file_desc)";
$INDEX[] = "ALTER TABLE downloads_files ADD FULLTEXT(file_name)";
