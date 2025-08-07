<?php

/**
 * Upgrade to Beta2
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

$SQL = 'ALTER TABLE member_map MODIFY lat FLOAT( 10, 6 ), MODIFY lon FLOAT( 10, 6 );';

?>