<?php

$MODERATOR[] = 'versions_field_version_show';
$MODERATOR[] = 'versions_field_version_submit';
$MODERATOR[] = 'versions_field_version_update';
$MODERATOR[] = 'versions_field_version_developer';
$MODERATOR[] = 'versions_field_version_alter';

$MODERATOR[] = 'versions_field_fixed_in_show';
$MODERATOR[] = 'versions_field_fixed_in_submit';
$MODERATOR[] = 'versions_field_fixed_in_update';
$MODERATOR[] = 'versions_field_fixed_in_developer';
$MODERATOR[] = 'versions_field_fixed_in_report';

$MAPPING['versions_field_version'] = array(
	'show'      => 'versions_field_version_show',
	'submit'    => 'versions_field_version_submit',
	'update'    => 'versions_field_version_update',
	'developer' => 'versions_field_version_developer',
	'alter'		=> 'versions_field_version_alter'
);

$MAPPING['versions_field_fixed_in'] = array(
	'show'      => 'versions_field_fixed_in_show',
	'submit'    => 'versions_field_fixed_in_submit',
	'update'    => 'versions_field_fixed_in_update',
	'developer' => 'versions_field_fixed_in_developer',
	'report'    => 'versions_field_fixed_in_report'
);

?>