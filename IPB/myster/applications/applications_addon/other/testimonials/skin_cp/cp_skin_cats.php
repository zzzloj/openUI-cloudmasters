<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

class cp_skin_cats extends output
{

public function __destruct()
{
}

//===========================================================================
// Testemunhos Overview Index
//===========================================================================
function add($form)
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
    <form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doAdd'']}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
    <div class="section_title">
	<h2>{$this->lang->words['sbBot_addmsg']}Add Category</h2>
    </div>
    <table class="form_table">
	<tr>
	<td style="width: 50%;" valign="top">
	<div class="acp-box">
	<h3>{$this->lang->words['sbBot_addmsg']}Add Category</h3>
	<table class="ipsTable">
	<tr>
	<td style="width: 40%;"><strong>{$this->lang->words['sbBot_message']}Title</strong></td>
	<td style="width: 60%;">{$form['c_name']}</td>
	</tr>
	<tr>
	<td style="width: 40%;"><strong>{$this->lang->words['sbBot_enableq']}Description</strong></td>
	<td style="width: 60%;">{$form['c_desc']}</td>
	</tr>
	</table>
    </div>
	<div class='acp-actionbar'>
	<input type='submit' value='{$this->lang->words['sbBot_addnewmsg']}Add Category' class='button primary' accesskey='s'>
	</div>
    </div>
    </form>
<script type="text/javascript">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('entered_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

function manage( $rows, $pagination ) 
{

		$IPBHTML = "";
		//--starthtml--//



$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sbBot_managemsg']}Manage Categories</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'>
    <img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' title='' />{$this->lang->words['sbBot_addnewmsg']}Add New Category</a></li>
			
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['sbBot_messages']}Categories</h3>
   
	<table class='ipsTable' id='testimonials_cats'>
HTML;
		
if( count( $rows ) AND is_array( $rows ) )
{
						$IPBHTML .= <<<HTML


									<tr> 
                    <th style="width: 5%;"></th>                 
					<th style="width: 40%;">{$this->lang->words['sbBot_msg']}Name</th>
                    <th style="width: 50%;">{$this->lang->words['sbBot_enable']}Description</th>
					<th style="width: 5%;" text-align: center;></th>
					</tr>

HTML;

		
	foreach( $rows as $row )
	{
        
		if($row['shout_enable'] == 1)
        {
            $img = "<img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' title='' />";
        }
        else
        {
            $img = "<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='' title='' />";  
        }
					$IPBHTML .= <<<HTML
                    
					<tr id='testimonials_{$row['c_id']}' class="ipsControlRow isDraggable">
			<td class='col_drag' style="width: 5%;">
				<div class='draghandle'>&nbsp;</div>
			</td>                           						
						<td style="width: 40%;">{$row['c_name']}</td>
                        <td style="width: 50%;">{$row['c_desc']}</td>
						<td style="width: 5%; text-align: center;" class="col_buttons">
                        
	<ul class='ipsControlStrip' style="float:right;">
	<li class="i_edit">
    <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$row['c_id']}'>
    <img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' title='Edit' /></a>
    </li>
    <li class="i_delete">    
    <a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;id={$row['c_id']}");' title=''>
    <img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='' title='' /></a>
    </li>
    </ul>
						</td>
					</tr>

HTML;

$IPBHTML .= <<<HTML
<script type='text/javascript'>
	jQ("#testimonials_cats").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=catMove&id={$row['c_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>  

HTML;

				}
}
else
{
    	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' class='no_messages'>
				{$this->lang->words['sbBot_nomsg']}
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['sbBot_addmsgq']}Add New Category</a>
            </td>			
		</tr>
              
HTML;
}				
				$IPBHTML .= <<<HTML
				</table>
			</div>
HTML;
if( count( $rows ) AND is_array( $rows ) )
{
	$IPBHTML .= <<<HTML
{$pagination}
HTML;
}
		//--endhtml--//
		return $IPBHTML;
	}


function edit( ) {

        	    $d = $this->DB->buildAndFetch( array( 'select' => '*',
                                                         'from'   => 'testemunhos_cats',
                                                         'where'  => "c_id='".$this->request['id']."'",)	);   
  
         $form['c_name']    = $this->registry->output->formInput( 'c_name', $d['c_name'] );                                                                                     
        $form['c_desc']    = $this->registry->output->formTextarea( 'c_desc', $d['c_desc'] );                                                       
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
    <form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doedit&amp;id={$d['c_id']}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
    <div class="section_title">
	<h2>{$this->lang->words['sbBot_addmsg']}Add Category</h2>
    </div>
    <table class="form_table">
	<tr>
	<td style="width: 50%;" valign="top">
	<div class="acp-box">
	<h3>{$this->lang->words['sbBot_addmsg']}Add Category</h3>
	<table class="ipsTable">
	<tr>
	<td style="width: 40%;"><strong>{$this->lang->words['sbBot_message']}Title</strong></td>
	<td style="width: 60%;">{$form['c_name']}</td>
	</tr>
	<tr>
	<td style="width: 40%;"><strong>{$this->lang->words['sbBot_enableq']}Description</strong></td>
	<td style="width: 60%;">{$form['c_desc']}</td>
	</tr>
	</table>
    </div>
	<div class='acp-actionbar'>
	<input type='submit' value='{$this->lang->words['sbBot_addnewmsg']}Add Category' class='button primary' accesskey='s'>
	</div>
    </div>
    </form>
<script type="text/javascript">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('entered_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

function toolSearchBanned( $users, $pagination ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_banned']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='search'  id='search'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='members' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class="acp-box">
		<h3>{$this->lang->words['search_users']}</h3>
		<p align="center">{$this->lang->words['search_where']}
		<select name="tipo">
			<option value="1">{$this->lang->words['search_user']}</option>
			<option value="2">{$this->lang->words['search_userid']}</option>
		</select>&nbsp;		
		<input type='text' name='string' id='string' value='' size='30'  class='textinput' />
		</p>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['search_search']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

	        <div class="acp-box" style='margin-top: 120px;'>
			    <h3>{$this->lang->words['resultado_users']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<th width='5%' style='text-align:center; padding: 5px;'>{$this->lang->words['search_r_userid']}</th>
						<th width='29%' style='padding: 5px;'>{$this->lang->words['search_r_username']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_grupo']}</th>
						<th width='5%' style='text-align:center;  padding: 5px;'>{$this->lang->words['posts']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_joined']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_bannedon']}</th>
						<th width='1%'></th>
					</tr>
					<tr>
						{$users}
					</tr>
				</table>
			</div>

	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

function toolSearchResults( $logs, $pagination ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_logs']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&do=logs' method='post' name='search'  id='search'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='logs' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class="acp-box">
		<h3>{$this->lang->words['search_logs']}</h3>
		<p align="center">{$this->lang->words['search_where']}
		<select name="tipo">
			<option value="1">{$this->lang->words['search_name']}</option>
			<option value="2">{$this->lang->words['search_id']}</option>
			<option value="3">{$this->lang->words['search_tid']}</option>
			<option value="4">{$this->lang->words['search_title']}</option>
			<option value="5">{$this->lang->words['search_action']}</option>
		</select>&nbsp;		
		<input type='text' name='string' id='string' value='' size='30'  class='textinput' />
        
		</p>
<div class="acp-actionbar"><input value="{$this->lang->words['search_search']}" class="button primary" accesskey="s" type="submit" /></div>
	</div>      
</form>


	        <div class="acp-box" style='margin-top: 120px;'>
			    <h3>{$this->lang->words['resultado']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr style='padding: 10px;'>
						<th width='5%'  style='text-align:center; padding: 5px;'>{$this->lang->words['search_r_tid']}</th>
						<th width='30%' style='padding: 5px;'>{$this->lang->words['search_r_title']}</th>
						<th width='7%'  style='text-align:center; padding: 5px;'>{$this->lang->words['search_id']}</th>
						<th width='15%' style='padding: 5px;'>{$this->lang->words['search_name']}</th>
						<th width='10%' align='center' style='padding: 5px;'>{$this->lang->words['search_r_ip']}</th>
						<th width='15%' style='padding: 5px;'>{$this->lang->words['search_action']}</th>
					  	<th width='18%' align='center' style='text-align:center; padding: 5px;'>{$this->lang->words['data']}</th>
					</tr>
					<tr>
						{$logs}
					</tr>
				</table>
			</div>
   	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}


}
?>