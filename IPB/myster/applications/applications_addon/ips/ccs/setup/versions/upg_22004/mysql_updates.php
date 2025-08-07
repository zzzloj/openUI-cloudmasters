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

$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
$DB  = ipsRegistry::DB();

/* IP.Content upgrade */

$SQL[] = "ALTER TABLE ccs_blocks CHANGE block_cache_ttl block_cache_ttl VARCHAR(10) NOT NULL DEFAULT '0';";
