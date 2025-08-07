<?php

class cp_skin_forms extends output
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

public function mainFormScreen( $forms ) {

$IPBHTML = "";

$title = ( $this->request['form_id'] ) ? $this->registry->getClass('formForms')->form_data_id[ intval( $this->request['form_id'] ) ]['form_name']." {$this->lang->words['fields']}" : $this->lang->words['manage_forms'];

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.form.js'></script>
<div class='section_title'>
	<h2>{$title}</h2>    
    
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rebuild_cache'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['rebuild_cache']}</a></li>
            </ul>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=forms_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['add_form']}</a></li>
            </ul>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=fields_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['add_field']}</a></li>
            </ul>
            <ul>
                <li class='ipsActionButton'><a href='#' class='viewquicktags'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['view_quick_tags']}</strong></a></li>
            </ul>                                     
        </div>
</div>

EOF;

if( !count( $forms ) )
{
	$IPBHTML .= <<<EOF
blahblah
EOF;
}
else
{
	$IPBHTML .= <<<EOF
    <div id='form_list'>
	{$forms}
    </div>
EOF;
}

$IPBHTML .= <<<EOF
<script type='text/javascript'>
	jQ("#form_list").ipsSortable('div', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=forms_move&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
EOF;

return $IPBHTML;
}

public function formFields( $form, $fields ) {

$sub		= array();
$IPBHTML	= "";

$IPBHTML .= <<<EOF

	<table class='ipsTable' id='fields_{$form['form_id']}'>

EOF;

foreach( $fields as $id => $field )
{
    
$IPBHTML .= <<<EOF
    <tr id='field_{$field['field_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>
			<td>
				<strong>{$field['field_title']}</strong><br />
                <span class='desctext'><strong>{$this->lang->words['quick_tags']}:</strong> {field_name_{$field['field_id']}} &middot; {field_value_{$field['field_id']}}</span>
			</td>                     
            <td class='col_buttons'>
                <ul class='ipsControlStrip'>                
                    <li class='i_view'><a href='#' id='FE__view{$field['field_id']}' title='{$this->lang->words['view_example_title']}'>{$this->lang->words['view_example']}</a></li>
					<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=fields_edit&amp;id={$field['field_id']}'>{$this->lang->words['edit_field']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=fields_delete&amp;id={$field['field_id']}");'>{$this->lang->words['delete_field']}</a></li>
				</ul>                        
			</td>
    </tr>

<script type='text/javascript'>
$('FE__view{$field['field_id']}').observe('click', ACPForm.viewExample.bindAsEventListener( this, "app=form&amp;module=ajax&amp;section=fields&amp;do=view_example&amp;id={$field['field_id']}" ) );
</script>
EOF;
}

$IPBHTML .= <<<EOF
</table>

<script type='text/javascript'>
	jQ("#fields_{$form['form_id']}").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=fields_move&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
EOF;

return $IPBHTML;
}

public function renderForm( $content, $form )
{
    if( !$content )
    {
        $content = "<tr>
			<td class='no_messages' colspan='6'>
				{$this->lang->words['no_fields_display']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=fields_add&amp;form_id={$form['form_id']}' class='mini_button'>{$this->lang->words['add_field']}</a>
			</td>
		</tr>";
    }    

$IPBHTML .= <<<EOF
<div class='acp-box' id='form_{$form['form_id']}'>
    <table class='ipsTable'>
        <tr class='ipsControlRow isDraggable'>
    			<th class='col_drag'>
    				<div class='draghandle'>&nbsp;</div>
    			</th>            
    			<th width='50%'>
    			     <strong><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=forms&amp;form_id={$form['form_id']}'>{$form['form_name']}</a></strong>
    			</th>
                <th class='col_buttons'>
                    <ul class='ipsControlStrip'>
    					<li class='i_add'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=fields_add&amp;form_id={$form['form_id']}'>{$this->lang->words['add_field']}</a></li>
                        <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=forms_edit&amp;id={$form['form_id']}'>{$this->lang->words['edit_form']}</a></li>
    					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=forms_delete&amp;id={$form['form_id']}");'>{$this->lang->words['delete_form']}</a></li>
    				</ul>
    			</th> 
        </tr>
        {$content}
    </table>
</div>
<br class='clear' />
EOF;

return $IPBHTML;
}

public function formsForm( $form, $data ) {

$IPBHTML = "";


$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.form.js'></script>
<div class='section_title'>
	<h2>{$this->lang->words['manage_forms']}</h2>
    
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='#' class='viewquicktags'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['view_quick_tags']}</strong></a></li>
            </ul>                      
        </div>
</div>

<form id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form['url']}&amp;id={$data['form_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$form['title']}</h3>
        
	<div id='tabstrip_formForm' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_general'>{$this->lang->words['tab_general']}</li>
			<li id='tab_pm'>{$this->lang->words['tab_pm']}</li>
			<li id='tab_email'>{$this->lang->words['tab_email']}</li>            
			<li id='tab_topic'>{$this->lang->words['tab_topic']}</li> 
			<li id='tab_perms'>{$this->lang->words['tab_permissions']}</li>            
		</ul>
	</div>        
        
	<div id='tabstrip_formForm_content' class='ipsTabBar_content'>       
        
		<div id='tab_general_content'>        
     		<table class='ipsTable double_pad'>
                <tr>
                    <th colspan='2'>{$this->lang->words['general_settings']}</th>
		        </tr>            
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['form_name']}</strong></td>
                    <td class='field_field'>{$data['form_name']}</td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['description']}</strong></td>
                    <td class='field_field'>{$data['description']}</td>
                </tr>                
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['log_message']}</strong></td>
                    <td class='field_field'>{$data['log_message']}<br /><span class='desctext'>{$this->lang->words['log_message_desc']}</span></td>
                </tr>  
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['enable_logs_rss']}</strong></td>
                    <td class='field_field'>{$data['enable_rss']}<br /><span class='desctext'>{$this->lang->words['enable_logs_rss_desc']}</span></td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['attachments']}</strong></td>
                    <td class='field_field'>{$data['attachments']}<br /><span class='desctext'>{$this->lang->words['attachments_desc']}</span></td>
                </tr>  
                <tr>
                    <th colspan='2'>{$this->lang->words['confirmation_settings']}</th>
		        </tr>                               
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['confirm_type']}</strong></td>
                    <td class='field_field'>{$data['confirm_type']}<br /><span class='desctext'>{$this->lang->words['confirm_type_desc']}</span></td>
                </tr>
                
                
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['confirm_data']}</strong></td>
                    <td class='field_field'>{$data['confirm_data']}<br /><span class='desctext'>{$this->lang->words['confirm_data_desc']}</span></td>
                </tr>
  
EOF;

if( $data['confirm_email'] )
{
	$IPBHTML .= <<<EOF
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['confirm_email']}</strong></td>
                    <td class='field_field'>{$data['confirm_email']}<br /><span class='desctext'>{$this->lang->words['confirm_email_desc']}</span></td>
                </tr>                     
EOF;
  
}
else
{

	$IPBHTML .= <<<EOF
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['confirm_email']}</strong></td>
                    <td class='field_field'><i>{$this->lang->words['confirm_email_no_fields']}</i><br /><span class='desctext'>{$this->lang->words['confirm_email_desc']}</span></td>
                </tr>   
EOF;
}

$IPBHTML .= <<<EOF

               
                <tr>
                    <th colspan='2'>{$this->lang->words['form_rules']}</th>
		        </tr>                                                 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['form_rules']}</strong></td>
                    <td class='field_field'>{$data['form_rules']}<br /><span class='desctext'>{$this->lang->words['form_rules_desc']}</span></td>
                </tr>                 
            </table>
        </div>

		<div id='tab_pm_content'>        
     		<table class='ipsTable double_pad'>
                <tr>
                    <th colspan='2'>{$this->lang->words['pm_settings']}</th>
		        </tr>            
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['enable_pm']}</strong></td>
                    <td class='field_field'>{$data['pm_enable']}</td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['pm_subject']}</strong></td>
                    <td class='field_field'>{$data['pm_subject']}<br /><span class='desctext'>{$this->lang->words['pm_subject_desc']}</span></td>
                </tr> 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['sender']}</strong></td>
                    <td class='field_field'>{$data['pm_sender']}<br /><span class='desctext'>{$this->lang->words['sender_desc']} <i>{$this->lang->words['sender_disable']}</i></span></td>
                </tr>                 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['send_method']}</strong></td>
                    <td class='field_field'>{$data['pm_type']}<br /><span class='desctext'>{$this->lang->words['send_method_desc']}</span></td>
                </tr>
                <tr id='send_method_pm_1'>
                    <td class='field_title'><strong class='title'>{$this->lang->words['individual']}</strong></td>
                    <td class='field_field'>{$data['pm_receiver']}<br /><span class='desctext'>{$this->lang->words['individual_desc']}</span></td>
                </tr>            
                <tr id='send_method_pm_2'>
                    <td class='field_title'><strong class='title'>{$this->lang->words['groups']}</strong></td>
                    <td class='field_field'>{$data['pm_groups']}<br /><span class='desctext'>{$this->lang->words['groups_desc']}<br />{$this->lang->words['groups_desc_ctrl']}</span></td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['pm_message']}</strong></td>
                    <td class='field_field'>{$data['pm_message']}<br /><span class='desctext'>{$this->lang->words['pm_message_desc']}</span></td>
                </tr>                               
            </table> 
        </div> 
        
		<div id='tab_email_content'>        
     		<table class='ipsTable double_pad'>
                <tr>
                    <th colspan='2'>{$this->lang->words['email_settings']}</th>
		        </tr>            
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['enable_email']}</strong></td>
                    <td class='field_field'>{$data['email_enable']}</td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['email_subject']}</strong></td>
                    <td class='field_field'>{$data['email_subject']}<br /><span class='desctext'>{$this->lang->words['email_subject_desc']}</span></td>
                </tr> 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['sender']}</strong></td>
                    <td class='field_field'>{$data['email_sender']}<br /><span class='desctext'>{$this->lang->words['sender_desc']}</span></td>
                </tr>                  
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['send_method']}</strong></td>
                    <td class='field_field'>{$data['email_type']}<br /><span class='desctext'>{$this->lang->words['send_method_desc']}</span></td>
                </tr>               
                <tr id='send_method_email_1'>
                    <td class='field_title'><strong class='title'>{$this->lang->words['individual']}</strong></td>
                    <td class='field_field'>{$data['email_receiver']}<br /><span class='desctext'>{$this->lang->words['individual_email_desc']}</span></td>
                </tr> 
                <tr id='send_method_email_2'>
                    <td class='field_title'><strong class='title'>{$this->lang->words['groups']}</strong></td>
                    <td class='field_field'>{$data['email_groups']}<br /><span class='desctext'>{$this->lang->words['groups_desc']}<br />{$this->lang->words['groups_desc_ctrl']}</span></td>
                </tr> 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['email_message']}</strong></td>
                    <td class='field_field'>{$data['email_message']}<br /><span class='desctext'>{$this->lang->words['email_message_desc']}</span></td>
                </tr>                               
            </table> 
        </div> 
        
		<div id='tab_topic_content'>        
     		<table class='ipsTable double_pad'>
                <tr>
                    <th colspan='2'>{$this->lang->words['topic_settings']}</th>
		        </tr>            
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['enable_topic']}</strong></td>
                    <td class='field_field'>{$data['topic_enable']}</td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['topic_author']}</strong></td>
                    <td class='field_field'>{$data['topic_author']}<br /><span class='desctext'>{$this->lang->words['topic_author_desc']}</span></td>
                </tr>
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['topic_title']}</strong></td>
                    <td class='field_field'>{$data['topic_title']}</td>
                </tr> 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['topic_forum']}</strong></td>
                    <td class='field_field'>{$data['topic_forum']}</td>
                </tr> 
                <tr>
                    <td class='field_title'><strong class='title'>{$this->lang->words['topic_post']}</strong></td>
                    <td class='field_field'>{$data['topic_post']}<br /><span class='desctext'>{$this->lang->words['topic_post_desc']}</span></td>
                </tr>                               
            </table> 
        </div> 
        
		<div id='tab_perms_content'>        
     		{$form['permissions']}  
        </div>                       
        
		<script type='text/javascript'>
			jQ("#tabstrip_formForm").ipsTabBar({ tabWrap: "#tabstrip_formForm_content" });
		</script>           
        
    </div>    
 		
	<div class='acp-actionbar'>
		<input type='submit' class='button primary' value='{$form['title']}' />
	</div>
    
    </div>
</form>

EOF;

//--endhtml--//
return $IPBHTML;
}

public function fieldsForm( $form, $field ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.form.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.form_fields.js'></script>
<div class='section_title'>
	<h2>{$this->lang->words['manage_fields']}</h2>
    
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='#' class='viewquicktags'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['view_quick_tags']}</strong></a></li>
            </ul>                      
        </div>    
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form['url']}&amp;id={$field['field_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$form['title']}</h3> 

		<table class='ipsTable double_pad'>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['form_name']}</strong></td>
                <td class='field_field'>{$form['field_form_id']}</td>
            </tr>        
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_title']}</strong></td>
                <td class='field_field'>{$form['field_title']}</td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['selected_value']}</strong></td>
                <td class='field_field'>{$form['field_value']}<br /><span class='desctext'>{$this->lang->words['selected_value_desc']}</span><br /><span id='selected_value' style='font-size: 0.8em;'>{$this->lang->words['selected_value_desc_multi']}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_description']}</strong></td>
                <td class='field_field'>{$form['field_text']}</td>
            </tr> 
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_type']}</strong></td>
                <td class='field_field'>{$form['field_type']}</td>
            </tr> 
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_required']}</strong></td>
                <td class='field_field'>{$form['field_required']}<br /><span class='desctext'>{$this->lang->words['field_required_desc']}</span></td>
            </tr> 
            <tr id='field_extras'>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_extras']}</strong></td>
                <td class='field_field'><span id='field_extras_container'></span></td>
            </tr> 
            <tr id='add_field_options'>
                <td class='field_title'><strong class='title'>{$this->lang->words['field_options']}</strong></td>
                <td class='field_field'><a href='#' id='add_field' title='{$this->lang->words['add_option']}' style='color:green;font-weight:bold'>{$this->lang->words['add_option']}</a><br /><br />
	                <ul id='fields_container'></ul></td>
            </tr>                                                                            
        </table>     
        
        <script type='text/javascript'>
        //<![CDATA[ 
        	ACPFormFields.fields = \$H(
        		{$field['field_values']}
        	);
            
            ipb.lang['delete_confirm']    = '{$this->lang->words['please_confirm']}';    
            ipb.templates['field_box']    = new Template("<li id='field_#{field_id}_wrap' style='display: none'>  <input type='text' id='options_#{field_id}' name='options[#{field_id}]' size='40' class='input_text' value='#{field_value}' maxlength='254' style='margin-bottom:2px;' />  [<a href='#' id='remove_field_#{field_id}' title='{$this->lang->words['remove_option']}' style='color:red;font-weight:bold'>X</a>]  </li>");    
            ipb.templates['input_box']    = "<strong>{$this->lang->words['size']}:</strong> <input type='text' size='5' value='{$field['size']}' name='size' />";
            ipb.templates['multi_box']    = "<strong>{$this->lang->words['multiselect_size']}:</strong> <input type='text' size='5' value='{$field['multi_size']}' name='multi_size' />";
            ipb.templates['dropdown_box'] = "<strong>{$this->lang->words['rows']}:</strong> <input type='text' size='5' value='{$field['rows']}' name='rows' /><br /><strong>{$this->lang->words['columns']}:</strong> <input type='text' size='5' value='{$field['cols']}' name='cols' />";
        //]]>
        </script>        
 		
 		<div class='acp-actionbar'>
 				<input type='submit' class='button primary' value='{$form['title']}' />
 		</div>
	</div>
</form>
EOF;


return $IPBHTML;
}

public function viewExample( $field=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['view_example']}: {$field['field_title']}</h3>
    
		<table class='ipsTable double_pad'>
            <tr>
                <td colspan'2'><strong class='title'>{$this->lang->words['html']}</strong><br />
                {$field['field_data']}<br /><span class='desctext'>{$this->lang->words['html_desc']}</span></td>
            </tr>
            <tr>
                <td colspan'2'><strong class='title'>{$this->lang->words['code']}</strong><br />
                <textarea name='example' cols='60' rows='6'>{$field['field_data']}</textarea><br /><span class='desctext'>{$this->lang->words['code_desc']}</span></td>
            </tr>
        </table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function quicktags() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['view_quick_tags']}</h3>
    
		<table class='ipsTable '>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_member_name']}</strong></td>
                <td class='field_field'>{member_name}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_member_id']}</strong></td>
                <td class='field_field'>{member_id}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_member_email']}</strong></td>
                <td class='field_field'>{member_email}</span></td>
            </tr> 
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_member_ip']}</strong></td>
                <td class='field_field'>{member_ip}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_board_name']}</strong></td>
                <td class='field_field'>{board_name}</span></td>
            </tr> 
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_board_url']}</strong></td>
                <td class='field_field'>{board_url}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_form_id']}</strong></td>
                <td class='field_field'>{form_id}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_form_name']}</strong></td>
                <td class='field_field'>{form_name}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_form_url']}</strong></td>
                <td class='field_field'>{form_url}</span></td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_field_name']}</strong></td>
                <td class='field_field'>{field_name_#}</span></td>
            </tr>
             <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_field_value']}</strong></td>
                <td class='field_field'>{field_value_#}</span></td>
            </tr>
             <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['tag_field_list']}</strong></td>
                <td class='field_field'>{field_list}</span></td>
            </tr>                                                                                                                                       
        </table>    
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}