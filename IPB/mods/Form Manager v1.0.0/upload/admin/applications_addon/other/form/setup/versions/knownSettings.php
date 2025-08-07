<?php
/*
 * Returns known settings
 *
 * No, really it does!
 *
 * Remember: ipsRegistry::$settings probably won't be available.
 *
 *
 * IPSSetUp::getSavedData('admin_email')
 * IPSSetUp::getSavedData('install_dir')   [Example: /home/user/public_html/forums] - No trailing slash supplied
 * IPSSetUp::getSavedData('install_url')   [Example: http://www.domain.tld/forums]  - No trailing slash supplied
 */
 
# Lets just test this out
$knownSettings = array( 
	 'form_landing_page' => 1
);
