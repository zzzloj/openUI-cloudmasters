<?php

/**
* Tracker 2.1.0
* 
* Moderator Plugin skin templates
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class cp_skin_module_versions_moderator_form extends output
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
public function acp_moderator_form_main_version( $moderator ) {

$disabled = '';

if ( $moderator['template_id'] && $moderator['mode'] == 'template' )
{
	$disabled = "disabled='disabled'";
}

$form                           = array();
$form['versions_field_version_show']       = $this->registry->output->formYesNo( "versions_field_version_show",      $moderator['versions_field_version_show'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_version_submit']     = $this->registry->output->formYesNo( "versions_field_version_submit",    $moderator['versions_field_version_submit'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_version_update']     = $this->registry->output->formYesNo( "versions_field_version_update",    $moderator['versions_field_version_update'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_version_developer']  = $this->registry->output->formYesNo( "versions_field_version_developer", $moderator['versions_field_version_developer'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_version_alter']  = $this->registry->output->formYesNo( "versions_field_version_alter", $moderator['versions_field_version_alter'], '', array( 'yes' => $disabled, 'no' => $disabled ) );


$IPBHTML = "";

$IPBHTML .= <<<EOF
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['version_is_developer']}</strong></td>
				<td class='field_content'>{$form['versions_field_version_developer']}<br /><span class='desctext'>{$this->lang->words['version_is_developer_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['version_can_see']}</strong></td>
				<td class='field_content'>{$form['versions_field_version_show']}<br /><span class='desctext'>{$this->lang->words['version_can_see_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['version_can_submit']}</strong></td>
				<td class='field_content'>{$form['versions_field_version_submit']}<br /><span class='desctext'>{$this->lang->words['version_can_submit_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['version_can_update']}</strong></td>
				<td class='field_content'>{$form['versions_field_version_update']}<br /><span class='desctext'>{$this->lang->words['version_can_update_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['version_can_alter']}</strong></td>
				<td class='field_content'>{$form['versions_field_version_alter']}<br /><span class='desctext'>{$this->lang->words['version_can_alter_desc']}</span></tr>
			</tr>
		</table>
EOF;

return $IPBHTML;
}

/**
 * Return the HTML form for our group settings
 *
 * @access	public
 * @return	html
 */
public function acp_moderator_form_main_fixed_in( $moderator ) {

$disabled = '';

if ( $moderator['template_id'] && $moderator['mode'] == 'template' )
{
	$disabled = "disabled='disabled'";
}

$form                           = array();
$form['versions_field_fixed_in_show']       = $this->registry->output->formYesNo( "versions_field_fixed_in_show",      $moderator['versions_field_fixed_in_show'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_fixed_in_submit']     = $this->registry->output->formYesNo( "versions_field_fixed_in_submit",    $moderator['versions_field_fixed_in_submit'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_fixed_in_update']     = $this->registry->output->formYesNo( "versions_field_fixed_in_update",    $moderator['versions_field_fixed_in_update'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_fixed_in_developer']  = $this->registry->output->formYesNo( "versions_field_fixed_in_developer", $moderator['versions_field_fixed_in_developer'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['versions_field_fixed_in_report']     = $this->registry->output->formYesNo( "versions_field_fixed_in_report",    $moderator['versions_field_fixed_in_report'], '', array( 'yes' => $disabled, 'no' => $disabled ) );


$IPBHTML = "";

$IPBHTML .= <<<EOF
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['fixed_is_developer']}</strong></td>
				<td class='field_content'>{$form['versions_field_fixed_in_developer']}<br /><span class='desctext'>{$this->lang->words['fixed_is_developer_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['fixed_can_see']}</strong></td>
				<td class='field_content'>{$form['versions_field_fixed_in_show']}<br /><span class='desctext'>{$this->lang->words['fixed_can_see_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['fixed_can_submit']}</strong></td>
				<td class='field_content'>{$form['versions_field_fixed_in_submit']}<br /><span class='desctext'>{$this->lang->words['fixed_can_submit_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['fixed_can_update']}</strong></td>
				<td class='field_content'>{$form['versions_field_fixed_in_update']}<br /><span class='desctext'>{$this->lang->words['fixed_can_update_desc']}</span></tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['fixed_can_see_reports']}</strong></td>
				<td class='field_content'>{$form['versions_field_fixed_in_report']}<br /><span class='desctext'>{$this->lang->words['fixed_can_see_reports_desc']}</span></tr>
			</tr>
		</table>
EOF;

return $IPBHTML;
}

}