<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
$_LOAD['promenu_menus'] = 1;
$_LOAD['promenu_groups'] = 1;

$CACHE['promenu_menus'] = array('array' => 1,
	'allow_unload' => 0,
	'default_load' => 1,
	'recache_file' => IPSLib::getAppDir('promenu') . '/sources/profunctions.php',
	'recache_class' => 'profunctions',
	'recache_function' => 'kerching'
);

$CACHE['promenu_groups'] = array('array' => 1,
	'allow_unload' => 0,
	'default_load' => 1,
	'recache_file' => IPSLib::getAppDir('promenu') . '/sources/profunctions.php',
	'recache_class' => 'profunctions',
	'recache_function' => 'buildGroupCache'
);