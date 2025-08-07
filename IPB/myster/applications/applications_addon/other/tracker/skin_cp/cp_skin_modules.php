<?php

/**
* Tracker 2.1.0
* 
* Moderator Plugin skin templates
* Last Updated: $Date: 2012-05-10 05:06:08 +0100 (Thu, 10 May 2012) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
* @link			http://ipbtracker.com
* @version		$Revision: 1364 $
*/

class cp_skin_modules extends output
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

public function field_splash( $fields ) {
$IPBTHML = "";

$IPBHTML .= <<<HTML
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.fields.js'></script>
<div class='section_title'>
	<h2>Manage Fields</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a id='recacheFields' href='javascript:void(0);'>
					<img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />
					Recache Fields
				</a>
			</li>
			<li class='ipsActionButton' style='display:none;'>
				<a id='recacheSuccess'>
					<img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />
					Recache successful
				</a>
			</li>
		</ul>
	</div>
</div>
<div class='information-box'>
	Fields with an edit icon are global, and changes here will reflect all over your projects.<br />
	Fields without an edit icon (such as Versions) need to be setup per-project.
</div>
<br />
<div class='acp-box adv_controls'>
	<h3>Manage Fields</h3>
	<table id='fields' class='ipsTable'>
HTML;

foreach( $fields as $k => $v )
{
	$IPBHTML .= <<<HTML
		<tr id='field_{$v['field_id']}' class='ipsControlRow item'>
			<td width='16'><img src="{$this->settings['skin_app_url']}../modules/{$v['module']['directory']}/skin_cp/moduleIcon.png" alt='' /></td>
			<td>
				<strong>{$v['title']}</strong>
				<div class='desc'><span class='desc'>Module: {$v['module']['title']}<br />{$v['description']}</div>
			</td>
			<td nowrap='nowrap'>
				{$v['nav']}
			</td>
		</tr>
HTML;
}

if ( !count($fields) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3' class='no_messages'>
				There are no fields currently available for Tracker. 
				<a href="{$this->settings['base_url']}module=modules&amp;section=modules&amp;do=overview" class='mini_button'>Manage Modules</a>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

return $IPBHTML;
}

// private function popupTemplateHTML() {
// 	return <<<HTML
// 	<p>To begin the template wizard, please enter a name for the template below. This name will be used to identify the template in other areas of Tracker.</p>
// 	<br />
// 	<div style='text-align:center;' class='input_wrap'><label>Template Name</label><input type='text' class='input_text' size='50' id='template_name' value='#{name}' /></div>
// 	<br /><br />
// 	<div class='popup_buttons' style='text-align:center;'>
// 		<a href='javascript:void(0);' id='saveTemplateName' class='disabled'>Continue</a>
// 	</div>
// HTML;
// }

// public function template_splash( $templates ) {
// $IPBTHML = "";

// $IPBHTML .= <<<HTML
// <script type="text/javascript" src='{$this->settings['js_app_url']}acp.templates.js'></script>
// <script type="text/javascript">
// 	ACPTemplates.addHTML		= new Template("{$this->registry->tracker->parseJavascriptTemplate($this->popupTemplateHTML())}");
// </script>
// <div class='section_title'>
// 	<h2>Manage Project Templates</h2>
// 	<ul class='context_menu'>
// 		<li>
// 			<a id='addTemplate' class='add_template' href='javascript:void(0);'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' />Create a Template</a>
// 		</li>
// 		<li>
// 			<a id='recacheTemplates' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />Recache Templates</a>
// 		</li>
// 		<li class='disabled' style='display:none;'><span id='recacheSuccess'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Templates Recached</span></li>
// 	</ul>
// </div>
// <div class='acp-box adv_controls'>
// 	<h3>Manage Project Templates</h3>
// 	<ul id='fields' class='alternate_rows'>
// HTML;

// foreach( $templates as $k => $v )
// {
// 	$IPBHTML .= <<<HTML
// 		<li id='field_{$v['field_id']}' class='isDraggable'>
// 			<table style='width: 100%;' class='double_pad'>
// 				<tr class='control_row'>
// 					<td style='width:2.5%;'>
// 						<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='' /></div>
// 					</td>
// 					<td style='width:2.5%; padding:0;'>
// 						<img src="{$this->settings['skin_app_url']}../modules/{$v['module']['directory']}/skin_cp/moduleIcon.png" alt=''>
// 					</td>
// 					<td style='width:92%;'>
// 						<div class='title'><strong>{$v['title']}</strong></div><span class='desc'>Module: {$v['module']['title']}<br />{$v['description']}</span>
// 					</td>
// 					<td style='width:3%;'>

// HTML;

// 	if ( isset( $v['nav']['row'] ) && strlen( $v['nav']['row'] ) > 0 )
// 	{
// 		$IPBHTML .= $v['nav']['row'];
// 	}

// $IPBHTML .= <<<HTML
// 					</td>
// 				</tr>
// 			</table>
// 		</li>
// HTML;
// }

// if ( !count($templates) )
// {
// 	$IPBHTML .= <<<HTML
// 		<li id='no_templates'>
// 			<table style='width: 100%;' class='double_pad'>
// 				<tr class='control_row'>
// 					<td style='width:100%; text-align:center;'>There are no Project Templates in the system, <a href='javascript:void(0);' class='add_template'>click here</a> to create one now!</td>
// 				</tr>
// 			</table>
// 		</li>
// HTML;
// }

// $IPBHTML .= <<<HTML
// 	</ul>
// </div>
// HTML;

// return $IPBHTML;
// }

/**
 * Return the HTML form for our group settings
 *
 * @access	public
 * @return	html
 */
public function module_splash( $installed, $available ) {

$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Manage Modules</h2>
</div>
<div class='acp-box adv_controls'>
	<h3>Installed Modules</h3>

	<table id='installed_modules' class='ipsTable'>
HTML;

if ( !count($installed) )
{
$IPBHTML .= <<<HTML
	<tr>
		<td class='no_messages'>
			There are no installed modules for Tracker. Installable modules are listed below.
		</td>
	</tr>
HTML;
}

$lastRow = '';
usort( $installed, array('cp_skin_modules', 'moduleSort') );
foreach( $installed as $k => $v )
{
	/* Print headers */
	if( $lastRow != $v['enabled'] )
	{
		$title = $v['enabled'] ? 'Enabled Modules' : 'Disabled Modules';
		$IPBHTML .= "<tr><th colspan='3'>{$title}</th></tr>";
		$lastRow = $v['enabled'];
	}

	/* Toggle on or off */
	$enabled	= 'disable';
	$text		= 'Disable';
	
	if ( ! $v['enabled'] )
	{
		$enabled	= 'add';
		$text		= 'Enable';
	}
	
$IPBHTML .= <<<HTML
	<tr id='module_{$v['directory']}' module='{$v['title']}' class='ipsControlRow item'>
		<td width='16'>
			<img src="{$this->settings['skin_app_url']}../modules/{$v['directory']}/skin_cp/moduleIcon.png" alt='' />
		</td>
		<td>
			<strong>{$v['title']}</strong>				
			<div class='desc'>by <strong>{$v['author']}</strong><br />Versions: {$v['version']}</div>
		</td>
		<td class='nowrap'>
			<ul class='ipsControlStrip'>
				<li class='i_{$enabled}'><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=toggle&amp;module_directory={$v['directory']}" title="{$text} Module">{$text} Module</a></li>
				<li class='ipsControlStrip_more'><a id='menu_{$v['directory']}' class='ipbmenu' href='#'>More</a></li>
			</ul>
			<ul class='acp-menu' id='menu_{$v['directory']}_menucontent'>
				<li class='icon delete'><a href="{$this->settings['base_url']}module=modules&amp;section=setup&amp;directory={$v['directory']}&amp;do=remove" title="Uninstall Module">Uninstall Module</a></li>
			</ul>
		</td>
	</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br /><br />
<div class='acp-box adv_controls'>
	<h3>Available Modules Not Installed</h3>
	<table id='uninstalled_modules' class='ipsTable'>
HTML;

if ( !count($available) )
{
$IPBHTML .= <<<HTML
	<tr>
		<td class='no_messages'>
			There are no available modules to install.
		</td>
	</tr>
HTML;
}

foreach( $available as $k => $v )
{
$IPBHTML .= <<<HTML
	<tr id='module_{$v['directory']}' class='ipsControlRow item'>
		<td width='16'>
			<img src="{$this->settings['skin_app_url']}../modules/{$v['directory']}/skin_cp/moduleIcon.png" alt='' />
		</td>
		<td>
			<strong>{$v['title']} {$v['human_version']}</strong>				
			<div class='desc' style='margin-right: 150px;'>by <strong>{$v['author']}</strong></div>
		</td>
		<td class='nowrap'>
			<ul class='ipsControlStrip'>
				<li class='i_add'>
					<a href='{$this->settings['base_url']}module=modules&amp;section=setup&amp;directory={$v['directory']}' title='Install Module'>Install Module</a>
				</li>
			</ul>
		</td>
	</tr>

HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>

HTML;

return $IPBHTML;
}

/**
 * Sort modules by enabled/disabled and title. (see usort)
 *
 * @access	public
 * @return	int
 */
static public function moduleSort($a, $b)
{
	return $a['enabled'] >= $b['enabled'] ? strcmp($a['title'], $b['title']) : 1;
}

}