<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */

$SQL[] = "ALTER TABLE promenuplus_groups ADD promenu_groups_is_vertical tinyint(1) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE promenuplus_groups ADD promenu_groups_border tinyint(1) NOT NULL DEFAULT '0';";