<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

class cp_skin_form_group_form extends output
{

function __destruct()
{
}

function acp_group_form_main( $group, $tabId ) {
    
ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_external' ), 'form' );

$form = array();


# Basic Permissions
$form['g_fs_view_offline']	 = $this->registry->output->formYesNo( "g_fs_view_offline", $group['g_fs_view_offline'] );
$form['g_fs_submit_wait']	 = $this->registry->output->formSimpleInput( "g_fs_submit_wait", $group['g_fs_submit_wait'] );
$form['g_fs_bypass_captcha'] = $this->registry->output->formYesNo( "g_fs_bypass_captcha", $group['g_fs_bypass_captcha'] );
$form['g_fs_allow_attach']   = $this->registry->output->formYesNo( "g_fs_allow_attach", $group['g_fs_allow_attach'] );
$form['g_fs_view_logs']		 = $this->registry->output->formYesNo( "g_fs_view_logs", $group['g_fs_view_logs'] );
$form['g_fs_moderate_logs']	 = $this->registry->output->formYesNo( "g_fs_moderate_logs", $group['g_fs_moderate_logs'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id="tab_GROUPS_{$tabId}_content">
		<table class="ipsTable">
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['basic_permissions']}</strong></th>
			</tr>		
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_fs_view_offline']}</label>
				</td>
				<td style='width: 60%'>
		 			{$form['g_fs_view_offline']}
				</td>
		 	</tr>
			<tr>
				<td>
					<label>{$this->lang->words['g_fs_submit_wait']}</label>
				</td>
				<td>
		 			{$form['g_fs_submit_wait']} {$this->lang->words['seconds']}
                    <br /><span class='desctext'>{$this->lang->words['enter_zero_disable']}</span>
				</td>
		 	</tr>
			<tr>
				<td>
					<label>{$this->lang->words['g_fs_bypass_captcha']}</label>
				</td>
				<td>
		 			{$form['g_fs_bypass_captcha']}
				</td>
		 	</tr>
			<tr>
				<td>
					<label>{$this->lang->words['g_fs_allow_attach']}</label>
				</td>
				<td>
		 			{$form['g_fs_allow_attach']}
				</td>
		 	</tr>            
			<tr>
				<td>
					<label>{$this->lang->words['g_fs_view_logs']}</label>
				</td>
				<td>
		 			{$form['g_fs_view_logs']}
				</td>
		 	</tr>            
			<tr>
				<td>
					<label>{$this->lang->words['g_fs_moderate_logs']}</label>
				</td>
				<td>
		 			{$form['g_fs_moderate_logs']}
				</td>
		 	</tr>             			 			 		 			 			 					 			 			 			 						 			 			 			 			 					 	
		</table>
</div>
EOF;

return $IPBHTML;
}

function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
    <li id='tab_GROUPS_{$tabId}'>{$this->caches['app_cache']['form']['app_title']}</li>
EOF;

return $IPBHTML;
}

}