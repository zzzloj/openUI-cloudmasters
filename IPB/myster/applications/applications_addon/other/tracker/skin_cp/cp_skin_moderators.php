<?php

/**
* Tracker 2.1.0
* 
* Moderator Plugin skin templates
* Last Updated: $Date: 2012-09-02 21:38:05 +0100 (Sun, 02 Sep 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1384 $
*/
 
class cp_skin_moderators extends output
{
/**
 * Shortcut for url
 *
 * @var string URL shortcut
 * @access public
 * @since 2.0.0
 */
public $form_code;
/**
 * Shortcut for url (javascript)
 *
 * @var string JS URL shortcut
 * @access public
 * @since 2.0.0
 */
public $form_code_js;
/**
 * Check for the setup tab of the form
 *
 * @var bool the check
 * @access public
 * @since 2.0.0
 */
public $groupOne = FALSE;
/**
 * Execution check to enable form wrapper
 *
 * @var bool the check
 * @access public
 * @since 2.0.0
 */
public $moduleCheck = FALSE;
/**
 * Execution check for if this is a template
 *
 * @var bool the check
 * @access public
 * @since 2.0.0
 */
public $templateCheck = FALSE;

/**
 * Prevent our main destructor being called by this class
 *
 * @return void
 * @access public
 * @since 2.0.0
 */
public function __destruct()
{
}

/**
 * Moderator form
 *
 * @param string $type Type (add|edit)
 * @param array $moderator Group data
 * @param array $blocks Extra blocks for the modules
 * @return string HTML
 * @access public
 * @since 2.0.0
 */
public function moderatorForm( $type, $moderator, $blocks, $data ) {

//-----------------------------------------
// Format some of the data
//-----------------------------------------

$form['group_id']				= $this->registry->output->formDropdown( 'group_id', $data );

if ( count( $this->registry->tracker->projects()->getCache() ) > 0 )
{
	$form['projects']			= $this->registry->output->formMultiDropdown( 'projectsData', $this->registry->tracker->projects()->makeAdminDropdown(FALSE) );
}

if ( count( $this->registry->tracker->moderators()->getTemplates() ) > 0 )
{
	$form['template_id']		= $this->registry->output->formDropdown( 'template_id', $this->registry->tracker->moderators()->makeAdminTemplateDropdown() );
}

$disabled = '';

if ( $moderator['template_id'] && $moderator['mode'] == 'template' )
{
	$disabled = "disabled='disabled'";
}

$form['can_edit_posts']			= $this->registry->output->formYesNo( "can_edit_posts",		$moderator['can_edit_posts'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_edit_titles']		= $this->registry->output->formYesNo( "can_edit_titles",	$moderator['can_edit_titles'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_del_posts']			= $this->registry->output->formYesNo( "can_del_posts",		$moderator['can_del_posts'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_del_issues']			= $this->registry->output->formYesNo( "can_del_issues",		$moderator['can_del_issues'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_lock']				= $this->registry->output->formYesNo( "can_lock",			$moderator['can_lock'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_unlock']				= $this->registry->output->formYesNo( "can_unlock",			$moderator['can_unlock'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_move']				= $this->registry->output->formYesNo( "can_move",			$moderator['can_move'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_merge']				= $this->registry->output->formYesNo( "can_merge",			$moderator['can_merge'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['can_massmoveprune']		= $this->registry->output->formYesNo( "can_massmoveprune",	$moderator['can_massmoveprune'], '', array( 'yes' => $disabled, 'no' => $disabled ) );
$form['is_super']				= $this->registry->output->formYesNo( "is_super",			$moderator['is_super'], '', array( 'yes' => $disabled, 'no' => $disabled ) );

$isTemplate						= $moderator['mode'] == 'template' ? 1 : 0;
$form['use_template']			= $this->registry->output->formYesNo( "use_template",		$isTemplate, '', array( 'yes' => $disabled, 'no' => $disabled ) );
$style							= 'display:none;';

if ( $isTemplate )
{
	$style = '';
}

$IPBHTML = "";
//--starthtml--//

$memberActive	= " active";
$memberRadio	= " checked='checked'";
$groupActive	= '';
$groupRadio		= '';

/* Keith, double check variables on everything */
if ( $form['type'] == 'group' )
{
	$memberActive	= '';
	$memberRadio	= '';
	$groupActive	= " active";
	$groupRadio		= " checked='checked'";
}

if ( ! $this->templateCheck && ( $type == 'add' || ( isset( $form['template_id'] ) && strlen( $form['template_id'] ) > 0 ) ) ) {

$this->groupOne = TRUE;

$IPBHTML .= <<<HTML
	<input type='hidden' id='projects_input' name='projects' value='' />
	<div id='tabpane-PROJECTS|1'>
HTML;

	if ( $type == 'add' ) {

$IPBHTML .= <<<HTML
	<div class='inner'>
		<h3>Moderator Type</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>Type</strong></td>
				<td class='field_content'>
					<ul id='mg_list' class='radio_wrap'>
						<li style='display:inline-block;' id='li_member' class='mg_list{$memberActive}'>
							<input id='form_type_member' type='radio' name='type' value='member'{$memberRadio} />
							<label for='form_type_member'>Member</label>
						</li>
						<li style='display:inline-block;' id='li_group' class='mg_list{$groupActive}'>
							<input id='form_type_group' class='mg_radio' type='radio' name='type' value='group'{$groupRadio} />
							<label for='form_type_group'>Group</label>
						</li>
					</ul><br />
					<span class='desctext'>Please select whether this template is for a member or a group.</span>
				</tr>
			</tr>
			<tr>
				<td class='field_title'>
					<div id='form_group_title' style='display:none;'><strong class='title'>Group</strong></div>
					<div id='form_member_title'><strong class='title'>Members Display Name</strong></div>
				</td>
				<td class='field_content'>
					<div id='form_group' style='display:none;'>{$form['group_id']}</div>
					<div id='form_member'><input type='text' id='moderator_name' name='member_id' class='input_text autocomplete' value='' /></div>
				</td>
			</tr>
		</table>
	</div>
HTML;

	}

	if ( $type == 'add' && $this->moduleCheck && isset( $form['projects'] ) && strlen( $form['projects'] ) > 0 ) {

$IPBHTML .= <<<HTML
	<br />
	<div class='inner'>
		<h3>Project Controls</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_issupmod']}</strong></td>
				<td class='field_content'>
					{$form['is_super']}<br />
					<span class='desctext'>{$this->lang->words['m_f_issupmod_desc']}</span>
				</tr>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['m_f_projects']}</strong></td>
				<td class='field_content'>
					{$form['projects']}<br />
					<span class='desctext'>{$this->lang->words['m_f_projects_desc']}</span>
				</td>
			</tr>
		</table>
	</div>

HTML;

	}

	if ( ! $this->templateCheck && isset( $form['template_id'] ) && strlen( $form['template_id'] ) > 0 ) {

$IPBHTML .= <<<HTML
	<br />
	<div class='inner'>
		<h3>Permissions Mask</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>Use Template?</strong></td>
				<td class='field_content'>
					{$form['use_template']}<br />
					<span class='desctext'>Templates fill in the rest of the form for you, select no if you want to override certain settings.</span>
				</tr>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>Template to Use</strong></td>
				<td class='field_content'>
					{$form['template_id']}&nbsp;&nbsp;&nbsp;<a id='populate_form' class='populate_form' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}images/table_go.png' alt='' /></a>
				</td>
			</tr>
		</table>
	</div>

HTML;

	}

$IPBHTML .= <<<HTML
</div>

HTML;

}
else if ( $this->templateCheck == TRUE ) {

$this->groupOne = TRUE;

$IPBHTML .= <<<HTML
<div id='tabpane-PROJECTS|1' class='inner'>
	<h3>Permission Mask Settings</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title' valign='middle'><strong class='title'>Mask Name</strong></td>
			<td class='field_content'>
				<input type='text' name='name' value='{$moderator['name']}' id='template_name' size='50' /><br />
				<span class='desctext'>Please enter a name to identify this permission mask.</span>
			</tr>
		</tr>
	</table>
</div>
HTML;
}

$IPBHTML .= <<<HTML
	<div id='tabpane-PROJECTS|2' class='inner'>
		<h3>Main Permissions</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_edit_posts']}</strong></td>
				<td class='field_content'>{$form['can_edit_posts']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_edit_titles']}</strong></td>
				<td class='field_content'>{$form['can_edit_titles']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_del_posts']}</strong></td>
				<td class='field_content'>{$form['can_del_posts']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_del_issues']}</strong></td>
				<td class='field_content'>{$form['can_del_issues']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_lock']}</strong></td>
				<td class='field_content'>{$form['can_lock']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_unlock']}</strong></td>
				<td class='field_content'>{$form['can_unlock']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_move']}</strong></td>
				<td class='field_content'>{$form['can_move']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_merge']}</strong></td>
				<td class='field_content'>{$form['can_merge']}</tr>
			</tr>
			<tr>
				<td class='field_title' valign='middle'><strong class='title'>{$this->lang->words['m_f_can_massmoveprune']}</strong></td>
				<td class='field_content'>{$form['can_massmoveprune']}</tr>
			</tr>
		</table>
	</div>
HTML;

// Got blocks from other apps?
if ( is_array( $blocks ) && count( $blocks ) > 0 )
{
$count = 3;

foreach( $blocks as $data )
{
$IPBHTML .= <<<HTML
	<div id='tabpane-PROJECTS|{$count}' class='inner'>
		<h3>{$data['data']['title']} {$this->lang->words['m_fieldhead']}</h3>
{$data['content']}
	</div>

HTML;

$count++;
}

}

//--endhtml--//
return $IPBHTML;
}

private function conflictHTML() {
	return <<<EOF
<p>We have detected a conflict in your setup: the moderator you are trying to add already has individual permissions for the following projects.</p>
<ul id='conflict_list'>#{projects}</ul>
<p>Please click continue if you wish to overwrite these existing permissions with your new ones, otherwise click cancel.</p>
<div style='text-align:center;padding-top: 25px;' class='popup_buttons'>
	<a href='javascript:void(0);' id='continueConflicts'>Continue & Overwrite</a>&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='javascript:void(0);' id='cancelConflicts' class='cancel_button'>Cancel</a>
</div>
EOF;
}

/**
 * Moderator Form Wrapper
 *
 * @param string $type Type (add|edit)
 * @param array $moderator Group data
 * @param array $content the HTML for the form
 * @return string HTML
 * @access public
 * @since 2.0.0
 */
public function moderatorFormWrapper( $type, $moderator, $content, $blocks ) {

if( $type == 'edit' )
{
	$title = $this->lang->words['m_editing'];

	if ( $moderator['type'] == 'group' )
	{
		$title .= "Group: {$moderator['g_title']}";
	}
	else
	{
		$title .= $moderator['members_display_name'];
	}
}
else
{
	$title	= $this->lang->words['m_adding'];
}

$form_code    = $type == 'edit' ? 'doedit' : 'doadd';
$secure_key   = ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$conflictHTML = $this->registry->tracker->parseJavascriptTemplate($this->conflictHTML());

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.moderators.js'></script>
<script type="text/javascript">
document.observe("dom:loaded",function() 
	{
		ipbAcpTabStrips.register('moderator_form_menu');
	}
);

/* Tell the javascript where we are */
ACPModerators.type = '{$type}';
ACPModerators.conflictHTML = new Template("{$conflictHTML}");
</script>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post' id='adform' name='adform'>
<input type='hidden' name='moderate_id' value='{$moderator['moderate_id']}' />

<div class='section_title'>
	<h2>{$title}</h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a href='{$this->settings['base_url']}{$this->form_code}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />Cancel</a>
		</li>
		<li class='submit_form' id='submit_mod_form'>
			<a href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}images/accept.png' alt='' />Save Moderator</a>
		</li>
	</ul>
</div>

<div id='moderator_form' class='form_wrap acp-box row2'>
	<div class='form_menu_left left'>
		<ul id='moderator_form_menu' class='form_menu'>

HTML;

if ( $this->groupOne ) {

$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|1' module='setup' class='tab active'><img src='{$this->settings['skin_app_url']}/images/basic.png' alt='Moderator Setup' />Moderator Setup</li>

HTML;

}

$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|2' module='core' class='tab'><img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='Main Permissions' />Main Permissions</li>

HTML;

// Got blocks from other apps?
if ( is_array( $blocks ) && count( $blocks ) > 0 )
{
$count = 3;

foreach( $blocks as $data )
{
$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|{$count}' module='{$data['module']['directory']}' class='tab'><img src='{$this->settings['base_acp_url']}/applications_addon/other/tracker/modules/{$data['module']['directory']}/skin_cp/moduleIcon.png' alt='{$this->lang->words['m_fieldtab']}{$data['data']['title']}' />{$this->lang->words['m_fieldtab']}{$data['data']['title']}</li>

HTML;

$count++;
}

}
$IPBHTML .= <<<HTML
		</ul>
	</div>
	<div class='form_menu_right right'>
		{$content}
	</div>
	<br class='clear' />
</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Moderator row
 *
 * @access	public
 * @param	array 		Moderator
 * @return	string		HTML
 */
public function moderatorsOverviewRow( $r=array() ) {
$r['_description']		= '';
$r['_globalPerms']		= "";
$r['_superCss']			= '';

if ( $r['is_super'] == 1 && $r['num_projects'] > 0 )
{
	$r['_description']	= "Super Moderator with additional permissions for <strong>{$r['num_projects']}</strong> projects";
}
else if ( $r['is_super'] == 1 )
{
	$r['_description']	= "Super Moderator";
}
else if ( $r['num_projects'] > 1 )
{
	$r['_globalPerms']	= '';
	$r['_superCss']		= 'display:none;';
	$r['_description']	= "Moderates <strong>{$r['num_projects']}</strong> projects";
}
else
{
	$r['_globalPerms']	= '';
	$r['_description']	= "Moderates <strong>{$r['project']['title']}</strong>";
}

$display = "style='display:none;' ";

if ( $r['num_projects'] > 1 && ! $r['is_super'] )
{
	$display			= '';
}
else
{
	$r['_superCss'] 	= '';
}

$r['_title']			= ($r['type'] == 'group') ? "Group: " . $r['name'] : $r['name'];

$image					= ($r['type'] == 'group') ? 'group' : 'member';

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
				<li class='ipsControlRow' id='moderator_group_{$r['mg_id']}'>
					<table class='ipsTable'>
					<tr>
					<td style='width:5%;text-align:center;'><img src='{$this->settings['skin_app_url']}/images/moderate_{$image}.png' /></td>
					<td style='min-width:20px;padding-right:0;padding-left:0;'>
						<img src='{$this->settings['skin_app_url']}images/folder_closed.png' alt='' class='collapsehandle' {$display}/>
					</td>
					<td style='width:78%;'>
						<span style='font-weight:bold;'>{$r['_title']}</span><br />
						<span class='desc'>{$r['_description']}</span>
						{$r['_globalPerms']}
					</td>
					<td style='width:15%;text-align:right;'>
						<ul class='ipsControlStrip'>
HTML;
						if(empty($r['_superCss']))
						{
$IPBHTML .= <<<HTML
							<li class='i_edit' style='{$r['_superCss']}'>
								<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['moderate_id']}" title="Edit Moderator">&nbsp;</a>
							</li>
HTML;
						}
$IPBHTML .= <<<HTML
						<li class='i_delete'>
							<a class="delete_mod" href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete&amp;secure_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;id={$r['mg_id']}&amp;type={$r['type']}&amp;del=main" title="Delete Moderator">&nbsp;</a>
							</li>
						</ul>
					</td>
					</tr>
					</table>
				</li>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function moderatorChildRow( $id, $data )
{

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<table class='ipsTable'>
	<tr class='ipsControlRow'>
		<td style='width:85%;padding-left: 2.5%;'>
			<a style='text-decoration:none; font-weight: bold; font-size: 0.9em;' href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$id}" title="Edit Moderator">{$data['title']}</a>
		</td>
		<td style='width:15%;text-align:right;padding-left: 10px; padding-right: 10px;'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$id}" title="Edit Moderator">&nbsp;</a>
				</li>
				<li class='i_delete'>
					<a class="delete_mod" href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete&amp;secure_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;id={$id}" title="Delete Moderator">&nbsp;</a>
				</li>
			</ul>
		</td>
	</tr>
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview of moderators
 *
 * @access	public
 * @param	string		Moderators HTML
 * @param	array 		Moderators
 * @return	string		HTML
 */
public function moderatorsOverviewWrapper( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.moderators.js'></script>
<div class='section_title'>
	<h2>{$this->lang->words['m_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />Add Moderator</a>
		</li>
	</ul>
</div>

<div class='acp-box adv_controls'>
	<h3>{$this->lang->words['m_usergroupman']}</h3>
	<ul>
		{$content}
	</ul>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * No rows in database
 *
 * @access	public
 * @param	string		Language string for area we are in
 * @return	string		HTML
 */
public function noRowsInDatabase( $area ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
			<tr>
				<td class='no_messages'>There are no {$area} for Tracker. <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button' title='Add {$area}'>Click here to create one</a></td>
			</tr>
HTML;

//--endhtml--//
return $IPBHTML;

}

/**
 * Template Form Wrapper
 *
 * @param string $type Type (add|edit)
 * @param array $moderator Group data
 * @param array $content the HTML for the form
 * @return string HTML
 * @access public
 * @since 2.0.0
 */
public function templateFormWrapper( $type, $moderator, $content, $blocks ) {

if( $type == 'edit' )
{
	$title	= 'Editing a Permission Mask';
}
else
{
	$title	= 'Creating a Permission Mask';
}

$form_code    = $type == 'edit' ? 'doedit' : 'doadd';
$button       = $type == 'edit' ? $this->lang->words['m_compedit'] : $this->lang->words['m_addmod'];
$secure_key   = ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.moderators.js'></script>
<script type="text/javascript">
document.observe("dom:loaded",function() 
	{
		ipbAcpTabStrips.register('template_form_menu');
	}
);

/* Tell the javascript where we are */
ACPModerators.type = '{$type}';
</script>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$moderator['moderate_id']}' />

<div class='section_title'>
	<h2>{$title}</h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a href='{$this->settings['base_url']}{$this->form_code}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />Cancel</a>
		</li>
		<li class='submit_form' id='submit_template_form'>
			<a href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}images/accept.png' alt='' />Save Permission Mask</a>
		</li>
	</ul>
</div>

<div id='template_form' class='form_wrap acp-box row2'>
	<div class='form_menu_left left'>
		<ul id='template_form_menu' class='form_menu'>
HTML;

if ( $this->groupOne ) {

$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|1' class='active'><img src='{$this->settings['skin_app_url']}/images/basic.png' alt='Mask Setup' />Mask Setup</li>

HTML;

}

$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|2'><img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='Main Permissions' />Main Permissions</li>

HTML;

// Got blocks from other apps?
if ( is_array( $blocks ) && count( $blocks ) > 0 )
{
$count = 3;

foreach( $blocks as $data )
{
$IPBHTML .= <<<HTML
			<li id='tabtab-PROJECTS|{$count}'><img src='{$this->settings['base_acp_url']}/applications_addon/other/tracker/modules/{$data['module']['directory']}/skin_cp/moduleIcon.png' alt='{$this->lang->words['m_fieldtab']}{$data['data']['title']}' />{$this->lang->words['m_fieldtab']}{$data['data']['title']}</li>

HTML;

$count++;
}

}
$IPBHTML .= <<<HTML
		</ul>
	</div>
	<div class='form_menu_right right'>
		{$content}
	</div>
	<br class='clear' />
</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Moderator row
 *
 * @access	public
 * @param	array 		Moderator
 * @return	string		HTML
 */
public function templatesOverviewRow( $r=array() ) {

$r['_description'] ="";

if ( $r['num_uses'] > 0 )
{
	$r['_description'] = "<span class='desc'>In use by <strong>{$r['num_uses']}</strong> moderators</span>";
}
else
{
	$r['_description'] = "<span class='desc'><em>Not in use</em></span>";
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
				<td style='width:5%;text-align:center;'><img src='{$this->settings['skin_app_url']}images/templates.png' /></td>
				<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['moderate_id']}' style='font-weight:bold;text-decoration:none;'>{$r['name']}</a><br />{$r['_description']}<br /><span class='desc'><a class='show_popup' id='show_popup_{$r['moderate_id']}' href='javascript:void(0);'>View Permissions</a></span></td>
				<td class='nowrap'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'><a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['moderate_id']}" title="Edit Permissions Mask">Edit</a></li>
						<li class='ipsControlStrip_more'><a id='menu_{$v['directory']}' class='ipbmenu' href='#'>More</a></li>
					</ul>
					<ul class='acp-menu' id='menu_{$v['directory']}_menucontent'>
						<li class='icon delete'><a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete&amp;secure_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;id={$r['moderate_id']}" title="Delete Permissions Mask">Delete</a></li>
					</ul>
				</td>
			</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview of moderators
 *
 * @access	public
 * @param	string		Moderators HTML
 * @param	array 		Moderators
 * @return	string		HTML
 */
public function templatesOverviewWrapper( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.moderators.js'></script>
<script type='text/javascript'>
ACPModerators.template = true;
</script>
<div class='section_title'>
	<h2>Manage Permission Masks</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />Add Permission Mask</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>Permission Masks</h3>
	<table class='ipsTable'>
		{$content}
	</table>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

}

?>