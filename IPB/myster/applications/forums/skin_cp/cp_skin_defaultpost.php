<?php
/*
+--------------------------------------------------------------------------
|   [HSC] Default Post Content 2.0.0.0
|   =============================================
|   by Esther Eisner
|   Copyright 2010 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

class cp_skin_defaultpost extends output
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

public function showTemplates ($rows, $pages) {
$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_templates']}</h2>
    <div class='ipsActionBar clearfix'>
        <ul>
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png'/> {$this->lang->words['add_template']}</a>
            </li>
        </ul>
    </div>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['manage_templates']}</h3>
    <div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
	<table class="ipsTable">
		<tr>
			<th width='30%'>{$this->lang->words['name']}</th>
			<th width='60%'>{$this->lang->words['content']}</th>
			<th width='10%' class='col_buttons'>{$this->lang->words['actions']}</th>
		</tr>
HTML;

if ( count($rows) AND is_array($rows) )
{
	foreach ( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
            <td>{$row['name']}</td>
            <td>{$row['content']}</td>
			<td>
                <ul class='ipsControlStrip'>
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$row['id']}'>Edit</a></li>
                    <li class='i_delete'><a href='#' onclick='acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;id={$row['id']}");'>Delete</a></li>
                </ul>
			</td>
		</tr>
HTML;

	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3' class='no_messages'><strong>{$this->lang->words['no_records']}</strong></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		{$pages}
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;

}

public function templateForm($formData)
{
$IPBHTML = "";

$header = $formData['id'] > 0 ? $this->lang->words['edit_template'] : $this->lang->words['add_template'];
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$header}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=save' method='post' name='templateForm'  id='templateForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
    <input type='hidden' name='id' value='{$formData['id']}'/>

	<div class='acp-box'>
		<h3>{$header}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong>{$this->lang->words['name']}</strong></td>
				<td class='field_field'>{$formData['name']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong>{$this->lang->words['content']}</strong></td>
                <td class='field_field'>{$formData['content']}<br/>
                <span class='desctext'>{$this->lang->words['content_desc']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='Save' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function showForums ($rows, $pages) {
$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_forums']}</h2>
        <div class='ipsActionBar clearfix'>
        <ul>
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png'/> {$this->lang->words['configure_forum']}</a>
            </li>
        </ul>
    </div>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['manage_forums']}</h3>
    <div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
	<table class="ipsTable">
		<tr>
			<th width='20%'>{$this->lang->words['forum_name']}</th>
			<th width='35%'>{$this->lang->words['new_template']}</th>
            <th width='35%'>{$this->lang->words['reply_template']}</th>
			<th width='10%' class='col_buttons'>{$this->lang->words['actions']}</th>
		</tr>
HTML;

if ( count($rows) AND is_array($rows) )
{
	foreach ( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
            <td>{$row['name']}</td>
            <td>{$row['newContent']}</td>
            <td>{$row['replyContent']}</td>
			<td>
                <ul class='ipsControlStrip'>
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;forumId={$row['forum_id']}'>Edit</a></li>
                    <li class='i_delete'><a href='#' onclick='acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;forumId={$row['forum_id']}");'>Delete</a></li>
                </ul>
			</td>
		</tr>
HTML;

	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' class='no_messages'><strong>{$this->lang->words['no_records']}</strong></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;

}

public function noTemplates(){
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['configure_forum']}</h2>
</div>
<div class="acp-box">
    <h3>{$this->lang->words['configure_forum']}</h3>
    <table class='ipsTable'>
        <tr>
            <th>&nbsp;</th>
        </tr>
        <tr>
            <td class='no_messages'><strong>{$this->lang->words['no_templates_yet']}</strong><a href='{$this->settings['base_url']}app=forums&amp;module=defaultpost&amp;section=templates&amp;do=edit' class='mini_button'>{$this->lang->words['add_template']}</a></td>
        </tr>
    </table>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

public function forumForm($formData)
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['configure_forum']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=save' method='post' name='forumForm'  id='forumForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['configure_forum']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong>{$this->lang->words['forum_name']}</strong></td>
				<td class='field_field'>{$formData['forumId']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong>{$this->lang->words['new_template']}</strong></td>
                <td class='field_field'>{$formData['newTemplateId']}<br/>
                <span class='desctext'>{$this->lang->words['new_template_desc']}</span></td>
			</tr>
            <tr>
				<td class='field_title'><strong>{$this->lang->words['reply_template']}</strong></td>
                <td class='field_field'>{$formData['replyTemplateId']}<br/>
                <span class='desctext'>{$this->lang->words['reply_template_desc']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='Save' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}
?>