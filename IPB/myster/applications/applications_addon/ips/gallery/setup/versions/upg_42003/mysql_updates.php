<?php

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();


if ( $DB->checkForField( 'g_album_private', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_album_private;";
}
