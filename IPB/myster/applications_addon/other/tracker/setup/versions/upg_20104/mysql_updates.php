<?php

$SQL[] = "UPDATE rc_classes SET class_title='Tracker',
	class_desc	= 'This is the plugin for making reports with Tracker.',
	author		= 'IPBTracker.com Project Developers',
	author_url	= 'http://ipbtracker.com'
	WHERE app='tracker'";

$SQL[] = "UPDATE tracker_module SET author	= 'IPBTracker.com Project Developers'";

$SQL[] = "UPDATE tracker_module SET
	description	= 'This module allows issues to be marked private, they will only be visible to developers and the author.'
	WHERE directory='privacy'";

$SQL[] = "UPDATE tracker_module SET
	description	= 'Severities allow reports in your Tracker system to be assigned a level of importance ranging from 1 (not very important), up until 5 (critical).'
	WHERE directory='severity'";

$DB  = ipsRegistry::DB();

/* Clean up old fields */
if( $DB->checkForField( 'project_show_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_show_perms;";
}

if( $DB->checkForField( 'project_read_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_read_perms;";
}

if( $DB->checkForField( 'project_start_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_start_perms;";
}

if( $DB->checkForField( 'project_reply_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_reply_perms;";
}

if( $DB->checkForField( 'project_manage_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_manage_perms;";
}

if( $DB->checkForField( 'project_upload_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_upload_perms;";
}

if( $DB->checkForField( 'project_download_perms', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_download_perms;";
}

if( $DB->checkForField( 'project_versions', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_versions;";
}

if( $DB->checkForField( 'project_version_display', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_version_display;";
}

if( $DB->checkForField( 'project_severity_add', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_severity_add;";
}

if( $DB->checkForField( 'project_severity_col', 'tracker_projects' ) )
{
	$SQL[] = "ALTER TABLE tracker_projects DROP project_severity_col;";
}