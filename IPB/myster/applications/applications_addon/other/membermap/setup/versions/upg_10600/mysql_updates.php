<?php

/**
 * Upgrade to 1.0.6
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

$SQL = 'ALTER TABLE member_map_cmarkers CHANGE description description TEXT;';
?>