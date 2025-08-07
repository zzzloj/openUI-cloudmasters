<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */

$SQL[] = "ALTER TABLE promenuplus_groups ADD promenu_groups_by_url tinyint(1) NOT NULL DEFAULT '1';";
$SQL[] = "ALTER TABLE promenuplus_menus ADD promenu_menus_by_url tinyint(1) NOT NULL DEFAULT '1';";