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

class cp_skin_faq_collections extends output
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
// Name: showCollections
//==================================================================
public function showCollections($rows, $pages) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
    <h2>{$this->lang->words['manage_collections']}</h2>
    <div class='ipsActionBar clearfix'>
        <ul>
            <li class='ipsActionButton'>
                <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' /> {$this->lang->words['add_collection']}</a>
            </li>
        </ul>
    </div>
</div>
<div class='acp-box'>
    <h3>{$this->lang->words['manage_collections']}</h3>
    <div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
    <table class='ipsTable' id='faq_collections'>
        <tr>
            <th width='5%' class='col_drag'>&nbsp;</th>
            <th width='45%'>{$this->lang->words['name']}</th>
            <th width='40%'>{$this->lang->words['collection_key']}</th>
            <th width='10%' class='col_buttons'>{$this->lang->words['actions']}</th>
        </tr>
HTML;
if(is_array($rows) && count($rows))
{
    foreach($rows as $row)
    {
        $IPBHTML .= <<<HTML
        <tr class='ipsControlRow isDraggable' id='collections_{$row['collection_id']}'>
            <td><span class='draghandle'>&nbsp;</span></td>
            <td>{$row['name']}</td>
            <td>{$row['collection_key']}</td>
            <td>
                <ul class='ipsControlStrip'>
                    <li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$row['collection_id']}' title='Edit'>Edit</a></li>
                    <li class='i_delete'><a href='#' title='Delete' onclick='acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;id={$row['collection_id']}");'>Delete</a></li>
                    <li class='i_cog'><a href='{$this->settings['base_url']}app=faq&amp;module=collections&amp;section=questions&amp;collection_id={$row['collection_id']}' title='{$this->lang->words['questions']}'>{$this->lang->words['questions']}</a></li>
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
            <td colspan='4' class='no_messages'>{$this->lang->words['no_collections']}</td>
        </tr>
HTML;
}
$IPBHTML .= <<<HTML
    </table>
    <script type='text/javascript'>
jQ("#faq_collections").ipsSortable( 'table', {url: "{$this->settings['base_url']}app=faq&module=collections&section=collections&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )});
</script>
    <div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

//==================================================================
// Name: collectionForm
//==================================================================
public function collectionForm($formData) {
$IPBHTML = "";
//--starthtml--//
$header = $formData['id'] ? $this->lang->words['edit_collection'] : $this->lang->words['add_collection']; 
$IPBHTML .= <<<HTML
<div class='section_title'>
    <h2>{$header}</h2>
</div>
<div class='acp-box'>
    <h3>{$header}</h3>
    <form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=save'>
        <input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
        <input type='hidden' name='id' value='{$formData['id']}' />
        <div class='ipsSteps_wrap'>
            <div class='ipsSteps clearfix' id='steps_bar'>
                <ul>
                    <li class='steps_active' id='step_1'>
                        <strong class='steps_title'>{$this->lang->words['step_1']}</strong>
                        <span class='steps_desc'>{$this->lang->words['step_1_desc']}</span>
                        <span class='steps_arrow'>&nbsp;</span>
                    </li>
                    <li id='step_2'>
                        <strong class='steps_title'>{$this->lang->words['step_2']}</strong>
                        <span class='steps_desc'>{$this->lang->words['step_2_desc']}</span>
                        <span class='steps_arrow'>&nbsp;</span>
                    </li>
                </ul>
            </div>
            <div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
                <div class='steps_content' id='step_1_content'>
                    <table class='ipsTable double_pad'>
                        <tr>
                            <td class='field_title'><strong>{$this->lang->words['name']}</strong></td>
                            <td class='field_field'>{$formData['name']}</td>
                        </tr>
                        <tr>
                            <td class='field_title'><strong>{$this->lang->words['collection_key']}</strong></td>
                            <td class='field_field'>{$formData['key']}<br />
                            <span class='desctext'>{$this->lang->words['collection_key_desc']}</span></td>
                        </tr>
                        <tr>
                            <td class='field_title'><strong>{$this->lang->words['collection_heading']}</strong></td>
                            <td class='field_field'>{$formData['heading']}<br />
                            <span class='desctext'>{$this->lang->words['collection_heading_desc']}</span></td>
                        </tr>
                        <tr>
                            <td class='field_title'><strong>{$this->lang->words['description']}</strong></td>
                            <td class='field_field'>{$formData['description']}</td>
                        </tr>
                    </table>
                    <br />
                </div>
                <div>
                    <p class='right' style='padding-left: 5px;'>
                        <input type='submit' class='realbutton' name='Finish' id='Finish' value='Finish'/>
                    </p>
                    <p class='right'>
                        <input type='submit' name='Next' id='Next' class='realbutton' value='Next' />
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

//==================================================================
// Name: collectionQuestions
//==================================================================
public function collectionQuestions($collection, $rows, $formData) {
$IPBHTML = "";
//--starthtml--// 
$IPBHTML .= <<<HTML
<div class='section_title'>
    <h2>{$this->lang->words['edit_collection']}</h2>
</div>
<div class='acp-box'>
    <h3>{$this->lang->words['edit_collection']}</h3>
    <form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=save'>
        <input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
        <input type='hidden' name='collection_id' value='{$collection['collection_id']}' />
        <div class='ipsSteps_wrap'>
            <div class='ipsSteps clearfix' id='steps_bar'>
                <ul>
                    <li class='steps_done' id='step_1'>
                        <strong class='steps_title'>{$this->lang->words['step_1']}</strong>
                        <span class='steps_desc'>{$this->lang->words['step_1_desc']}</span>
                        <span class='steps_arrow'>&nbsp;</span>
                    </li>
                    <li class='steps_active' id='step_2'>
                        <strong class='steps_title'>{$this->lang->words['step_2']}</strong>
                        <span class='steps_desc'>{$this->lang->words['step_2_desc']}</span>
                        <span class='steps_arrow'>&nbsp;</span>
                    </li>
                </ul>
            </div>
            <div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
                <div class='steps_content' id='step_2_content'>
                    <table class='ipsTable' id='collection_questions'>
                        <tr>
                            <th width='5%' class='col_drag'>&nbsp;</th>
                            <th width='85%'>{$this->lang->words['question']}</th>
                            <th width='10%'>{$this->lang->words['delete']}</th>
                        </tr>
HTML;
if(is_array($rows) && count($rows))
{
    foreach($rows as $row)
    {
        $IPBHTML .= <<<HTML
            <tr class='isDraggable' id='questions_{$row['key']}'>
                <td><span class='draghandle'>&nbsp;</span></td>
                <td>{$row['question']}</td>
                <td>{$row['delete']}</td>
            </tr>
HTML;
    }
}
$IPBHTML .= <<<HTML
                        <tr>
                            <td>&nbsp;</td>
                            <td>{$formData['source']}&nbsp;&nbsp;&nbsp;
                            {$formData['questions']}
                            &nbsp;&nbsp;&nbsp;
                            <input type='submit' class='realbutton' value='Add' />
                            </td>
                            <td><input type='submit' class='realbutton' id='save' value='Update' /></td>
                        </tr>
                    </table>
                    <script type='text/javascript'>
jQ("#collection_questions").ipsSortable( 'table', {url: "{$this->settings['base_url']}app=faq&module=collections&section=questions&amp;collection_id={$collection['collection_id']}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )});
</script>
                    <br />
                </div>
                <div>
                    <p class='left' style='padding-left: 5px;'>
                        <input type='submit' class='realbutton' name='Previous' id='Previous' value='Previous'/>
                    </p>
                    <p class='right' style='padding-left: 5px;'>
                        <input type='submit' class='realbutton' name='Finish' id='Finish' value='Finish'/>
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
<script type='text/javascript'>
function getQuestionList()
{
    var source = jQ("#source").val();

    var url = "{$this->settings['base_url']}app=faq&module=collections&section=questions&do=get&collection_id={$collection['collection_id']}&source=" + source + "&md5check={$this->registry->adminFunctions->getSecurityKey()}";
    url = url.replace(/&amp;/g, '&');
    
    jQ.ajax({
        url: url,
        success: function(data, textStatus, jqXHR) {
            jQ("#question_id").html(data);
        }
    });
}
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

}