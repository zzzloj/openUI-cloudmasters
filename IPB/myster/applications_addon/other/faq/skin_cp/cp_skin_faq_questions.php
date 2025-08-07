<?php
/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

class cp_skin_faq_questions extends output
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

public function __construct()
{
    $this->registry = ipsRegistry::instance();
    $this->lang = $this->registry->getClass('class_localization');
    $this->request =& $this->registry->fetchRequest();
    $this->settings =& $this->registry->fetchSettings();
}

//==================================================================
// Name: showQuestions
//==================================================================
public function showQuestions($rows, $pages, $form) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
    <h2>{$this->lang->words['manage_questions']}</h2>
    <div class='ipsActionBar clearfix'>
        <ul>
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' /> {$this->lang->words['add_question']}</a>
            </li>
HTML;
if($this->request['filter']!='unused')
{
    $IPBHTML .= <<<HTML
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=filter&amp;filter=unused'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' />{$this->lang->words['filter_unused']}</a>
            </li>
HTML;
}
if($this->request['do']=='filter')
{
    $IPBHTML .= <<<HTML
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}{$this->form_code}'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' />{$this->lang->words['show_all']}</a>
            </li>
HTML;
}
$IPBHTML .= <<<HTML
        </ul>
    </div>
</div>
HTML;
if($this->request['do']=='filter')
{
    $IPBHTML .= <<<HTML
    <div class='information-box'>{$this->lang->words['filter_message']}</div>
    <br />
HTML;
}
$IPBHTML .= <<<HTML
<div class='acp-box'>
    <h3>{$this->lang->words['manage_questions']}</h3>
    <div class='acp-actionbar'>
		<form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=filter&amp;filter=collection'>
            {$this->lang->words['filter_by_collection']}&nbsp;
            {$form['collection_id']}
            &nbsp;<input type='submit' value='Go' class='button primary' />
        </form>
	</div>
    <table class='ipsTable'>
        <tr>
            <th width='80%'>{$this->lang->words['question']}</th>
            <th width='10%'>{$this->lang->words['approved']}</th>
            <th width='10%' class='col_buttons'>{$this->lang->words['actions']}</th>
        </tr>
HTML;
if(is_array($rows) && count($rows))
{
    foreach($rows as $row)
    {
        $IPBHTML .= <<<HTML
        <tr class='ipsControlRow'>
            <td>{$row['question']}</td>
            <td><img src='{$this->settings['skin_acp_url']}/images/icons/{$row['approved_img']}.png' /></td>
            <td>
                <ul class='ipsControlStrip'>
                    <li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$row['question_id']}' title='Edit'>Edit</a></li>
                    <li class='ipsControlStrip_more ipbmenu' id='menu_{$row['question_id']}'><a href='#'>{$this->lang->words['more']}</a></li>
                </ul>
                <ul class='acp-menu' id='menu_{$row['question_id']}_menucontent' style='display: none'>
                    <li class='icon delete'><a href='#' title='Delete' onclick='acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;id={$row['question_id']}");'>Delete</a></li>
HTML;
if($row['approved'])
{
        $IPBHTML .= <<<HTML
                    <li class='icon cancel'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=unapprove&amp;id={$row['question_id']}' title='Unapprove'>Unapprove</a></li>
HTML;
}
else
{
        $IPBHTML .= <<<HTML
                    <li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=approve&amp;id={$row['question_id']}' title='Approve'>Approve</a></li>
HTML;
}
$IPBHTML .= <<<HTML
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
            <td colspan='2' class='no_messages'>{$this->lang->words['no_questions']}</td>
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

//==================================================================
// Name: questionForm
//==================================================================
public function questionForm($formData) {
$IPBHTML = "";
//--starthtml--//
$header = $formData['id'] ? $this->lang->words['edit_question'] : $this->lang->words['add_question']; 
$IPBHTML .= <<<HTML
<div class='section_title'>
    <h2>{$header}</h2>
</div>
<div class='acp-box'>
    <h3>{$header}</h3>
    <form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=save'>
        <input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
        <input type='hidden' name='id' value='{$formData['id']}' />
        <table class='ipsTable double_pad'>
            <tr>
                <td class='field_title'><strong>{$this->lang->words['question']}</strong></td>
                <td class='field_field'>{$formData['question']}</td>
            </tr>
            <tr>
                <td class='field_title'><strong>{$this->lang->words['answer']}</strong></td>
                <td class='field_field'>{$formData['answer']}</td>
            </tr>
            <tr>
                <td class='field_title'><strong>{$this->lang->words['width']}</strong></td>
                <td class='field_field'>{$formData['width']}<br />
                <span class='desctext'>{$this->lang->words['width_desc']}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong>{$this->lang->words['collections']}</strong></td>
                <td class='field_field'>{$formData['collections']}</td>
            </tr>
        </table>
        <div class='acp-actionbar'>
			<input type='submit' value='Save' class='button primary' accesskey='s'> or
            <a href='{$this->settings['base_url']}{$this->form_code}' class='cancel'>Cancel</a>
		</div>
    </form>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

}