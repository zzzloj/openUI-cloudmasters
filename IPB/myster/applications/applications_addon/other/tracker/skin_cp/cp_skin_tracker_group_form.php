<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Group Plugin skin templates
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class cp_skin_tracker_group_form extends output
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
function __destruct()
{
}

/**
 * Return the HTML form for our group settings
 *
 * @access	public
 * @return	html
 */
function acp_tracker_group_form_main( $group, $tabId ) {

$form								= array();
$form['g_tracker_view_offline']		= $this->registry->output->formYesNo( "g_tracker_view_offline", $group['g_tracker_view_offline'] );


$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<table class='ipsTable double_pad' cellspacing='0'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gperm_offline']}</strong></td>
			<td class='field_content'>{$form['g_tracker_view_offline']}</td>
	 	</tr>
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Return the HTML for the TAB of our group settings
 *
 * @access	public
 * @return	html
 */
function acp_tracker_group_form_tabs( $group, $tabId ) {

$IPBHTML = "<li id='tab_GROUPS_{$tabId}'>" . IPSLib::getAppTitle('tracker') . "</li>";

return $IPBHTML;
}

}