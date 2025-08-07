<?php

$SQL[] = "UPDATE core_applications SET
	app_website			= 'http://ipbtracker.com',
	app_update_check	= 'http://ipbtracker.com/page/updates.html?'
	WHERE app_directory='tracker'";