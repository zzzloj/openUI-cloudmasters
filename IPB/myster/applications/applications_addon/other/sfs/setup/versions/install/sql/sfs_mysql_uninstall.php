<?php

$m = 0;

if (ipsRegistry::DB()->checkForField('sfsMemInfo', 'pfields_content'))
{
    if ($m > 0) {
        $mcols .= ", ";
    }
    $mcols .= "DROP sfsMemInfo";
    $m++;
}

if (ipsRegistry::DB()->checkForField('sfsNextCheck', 'pfields_content'))
{
    if ($m > 0) {
        $mcols .= ", ";
    }
    $mcols .= "DROP sfsNextCheck";
    $m++;
}

if ($m > 0)
{
    $QUERY[] = "ALTER TABLE pfields_content $mcols;";
}

if (ipsRegistry::DB()->checkForTable('sfs_blocked'))
{
    $QUERY[] = "DROP TABLE sfs_blocked;";
}

if (ipsRegistry::DB()->checkForTable('sfs_settings'))
{
    $QUERY[] = "DROP TABLE sfs_settings;";
}

if (ipsRegistry::DB()->checkForTable('sfs_tracking'))
{
    $QUERY[] = "DROP TABLE sfs_tracking;";
}

if (ipsRegistry::DB()->checkForTable('sfs_whitelist'))
{
    $QUERY[] = "DROP TABLE sfs_whitelist;";
}

?>