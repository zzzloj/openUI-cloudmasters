<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.4.5
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

/* IP.Content upgrade */

$SQL[] = "UPDATE ccs_database_fields SET field_is_numeric = '1' WHERE field_key='article_date';";

/* Delete old block revisions, but make sure there are blocks first */

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_blocks' ) );

if( $count['total'] )
{
	$SQL[]	= "DELETE FROM ccs_revisions WHERE revision_type='block' AND revision_type_id NOT IN ( SELECT block_id FROM {$PRE}ccs_blocks );";
}