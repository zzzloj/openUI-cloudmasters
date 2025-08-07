<?php

/**
 * Member Map - Core Variables
 * Thanks to Michael McCune (IPB.Dev) for contributing the following code
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

/* Resets */
$_RESET = array();

/* Make sure our module and section are set */
if ( !isset( $_REQUEST['module'] ) && ( isset( $_REQUEST['app'] ) && $_REQUEST['app'] == 'membermap' ) )
{
    $_RESET['module']  = 'membermap';
    $_RESET['section'] = 'map';
}
?>
