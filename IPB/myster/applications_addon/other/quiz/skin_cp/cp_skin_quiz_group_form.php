<?php 
class cp_skin_quiz_group_form
{
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}
	
	function acp_trac_group_form_main( $group, $tabId ) 
	{
		$form = array();
		$form['g_quiz_can_take_quiz'] = $this->registry->output->formYesNo("g_quiz_can_take_quiz", $group['g_quiz_can_take_quiz']);
		$form['g_quiz_can_view_quiz'] = $this->registry->output->formYesNo("g_quiz_can_view_quiz", $group['g_quiz_can_view_quiz']);
		$form['g_quiz_can_add_quiz'] = $this->registry->output->formYesNo("g_quiz_can_add_quiz", $group['g_quiz_can_add_quiz']);
		$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>Group Permissions</th>
		</tr>
		<tr>
        	<td class='field_title'>
            	<strong class='title'>Can View Quiz System</strong>
            </td>
            <td class='field_field'>
				{$form['g_quiz_can_view_quiz']}<br />
			</td>
		</tr>
        <tr>
        	<td class='field_title'>
            	<strong class='title'>Can Add Quizzes</strong>
            </td>
            <td class='field_field'>
				{$form['g_quiz_can_add_quiz']}<br />
			</td>
		</tr>
        <tr>
        	<td class='field_title'>
            	<strong class='title'>Can Take Quizzes</strong>
            </td>
            <td class='field_field'>
				{$form['g_quiz_can_take_quiz']}<br />
			</td>
		</tr>
   </table>
</div>
EOF;

return $IPBHTML;
	}
	function acp_trac_group_form_tabs( $group, $tabId ) 
	{
		$IPBHTML = "<li id='tab_GROUPS_{$tabId}'>" . IPSLib::getAppTitle('quiz') . "</li>";
		return $IPBHTML;
	}
		
}
?>