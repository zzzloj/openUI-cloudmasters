<?php

/**
* Tracker 2.1.0
* 
* Global skin
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/
 
class cp_skin_tracker_global extends output
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

public function addGlobalJavascriptAndCSS()
{
	return <<<EOF
	<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}admin.css' />
	<script type='text/javascript' src='{$this->settings['public_dir']}js/ips.tracker.js'></script>
	<script type="text/javascript" src='{$this->settings['js_app_url']}jquery.tmpl.js'></script>
	<div id='loadingAjax' style='display:none;'><img src='{$this->settings['img_url']}/ajax_loading.gif' alt='Loading...' /></div>
EOF;
}

}