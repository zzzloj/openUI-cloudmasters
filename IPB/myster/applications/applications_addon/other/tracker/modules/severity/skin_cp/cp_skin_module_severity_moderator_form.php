<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Moderator Plugin skin templates
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class cp_skin_module_severity_moderator_form extends output
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

/**
 * Return the HTML form for our group settings
 *
 * @access	public
 * @return	html
 */
public function acp_moderator_form_main( $moderator ) {

$disabled = '';

if ( $moderator['template_id'] && $moderator['mode'] == 'template' )
{
	$disabled = "disabled='disabled'";
}

$form                           = array();
$form['severity_field_severity_show']      = $this->registry->output->formYesNo( "severity_field_severity_show",     $moderator['severity_field_severity_show'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['severity_field_severity_submit']    = $this->registry->output->formYesNo( "severity_field_severity_submit",   $moderator['severity_field_severity_submit'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['severity_field_severity_update']    = $this->registry->output->formYesNo( "severity_field_severity_update",   $moderator['severity_field_severity_update'], '', array( 'yes' => $disabled, 'no' => $disabled ) );


$IPBHTML = "";

$IPBHTML .= <<<EOF
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['severity_can_see']}</strong></td>
				<td class='field_content'>{$form['severity_field_severity_show']}<br /><span class='desctext'>{$this->lang->words['severity_can_see_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['severity_can_submit']}</strong></td>
				<td class='field_content'>{$form['severity_field_severity_submit']}<br /><span class='desctext'>{$this->lang->words['severity_can_submit_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['severity_can_update']}</strong></td>
				<td class='field_content'>{$form['severity_field_severity_update']}<br /><span class='desctext'>{$this->lang->words['severity_can_update_desc']}</span></tr>
			</tr>
		</table>
EOF;

return $IPBHTML;
}

}