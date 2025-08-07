<?php

class cp_skin_custom extends output {

function showForm( $field=array(), $perms=array(), $code='' )
{
if ( $code == 'edit' )
{
	$extraCSS = "style='margin-left: 5px;'";
	$title	= "Editing {$field['title']}";
}
else
{
	$extraCSS = "style='display: none;'";
	$title 	= 'Creating New Custom Field';
}

// Form inputs
$form['title']					= $this->registry->output->formInput( 'title', ( isset( $this->request['title'] ) AND $this->request['title'] ) ? $this->request['title'] : $field['title'] );

$form['description']			= $this->registry->output->formInput( 'description', ( isset( $this->request['description'] ) AND $this->request['description'] ) ? $this->request['description'] : $field['description'] );

if ( count( $this->registry->tracker->projects()->getCache() ) > 0 )
{
	$form['projects']			= $this->registry->output->formMultiDropdown( 'projectsData', $this->registry->tracker->projects()->makeAdminDropdown(FALSE), ( isset( $this->request['projects'] ) AND $this->request['projects'] ) ? $this->request['projects'] : explode(',', $field['projects'] ) );
}

$form['type']					= $this->registry->output->formDropdown( 'type', array(
									0 => array( 'text' , 'Text Input' ),
									1 => array( 'drop' , 'Drop Down Box' ),
									2 => array( 'area' , 'Text Area' ),
								), $field['type'] );
								
// Display options
$form['project_display']		= $this->registry->output->formYesNo( 'project_display', $field['project_display'] );
$form['project_filter']			= $this->registry->output->formYesNo( 'project_filter', $field['project_filter'] );
$form['issue_display_type']		= $this->registry->output->formDropdown( 'issue_display_type', array(
									0 => array( 'info' , 'Issue Information Block' ),
									1 => array( 'post' , 'First Post' )
								), $field['issue_display_type'] );
							
// Advanced inputs
$form['max_input']				= $this->registry->output->formInput( 'max_input', ( isset( $this->request['max_input'] ) AND $this->request['max_input'] ) ? intval( $this->request['max_input'] ) : $field['max_input'] );
$form['not_null']				= $this->registry->output->formYesNo( "not_null",		$field['not_null'] );
$form['input_format']			= $this->registry->output->formInput( 'input_format', ( isset( $this->request['input_format'] ) AND $this->request['input_format'] ) ? $this->request['input_format'] : $field['input_format'] );
$form['options']				= $this->registry->output->formTextArea( 'options', ( isset( $this->request['options'] ) AND $this->request['options'] ) ? $this->request['options'] : $field['options'] );

$options						= $this->registry->output->formDropdown( 'test-help', array( 0 => array( 'm', 'Male' ), 1 => array( 'f', 'Female' ), 2 => array( 'u', 'Not Telling' ) ) );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}../modules/custom/js/acp.custom.js'></script>
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript'>
custom.type = "{$code}";
</script>

<div class='ipsSteps_wrap'>
	<form name='adform' id='adminform' action="{$this->settings['base_url']}{$this->form_code}&amp;do=do_{$code}&amp;type={$code}&amp;field_id={$field['field_id']}" method='POST'>
		<input type='hidden' id='projects_input' name='projects' value='' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
		<div class='ipsSteps clearfix' id='steps_bar'>
			<ul>
				<li id='steps_basic' class='steps_active'>
					<strong class='steps_title'>Step 1</strong>
					<span class='steps_desc'>Basic Settings</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_display'>
					<strong class='steps_title'>Step 2</strong>
					<span class='steps_desc'>Display Options</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_additional'>
					<strong class='steps_title'>Step 3</strong>
					<span class='steps_desc'>Additional Settings</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_perms'>
					<strong class='steps_title'>Step 4</strong>
					<span class='steps_desc'>Permissions</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
			</ul>
		</div>
		
		<div id='custom_fields' class='acp-box'>
			<div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
				<div id='steps_basic_content' class='steps_content'>
					<div class='acp-box'>
						<h3>Basic Settings</h3>
						<table class='ipsTable double_pad'>
							<tr>
								<td class='field_title'>
									<strong class='title'>Field Type</strong>
								</td>
								<td class='field_field'>
									{$form['type']}
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Title</strong>
								</td>
								<td class='field_field'>
									{$form['title']}
									<br /><span class='desctext'>Max 200 characters</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Description</strong>
								</td>
								<td class='field_field'>
									{$form['description']}
									<br /><span class='desctext'>You can enter notes to identify this field here, only visible in the Admin CP</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Projects</strong>
								</td>
								<td class='field_field'>
									{$form['projects']}
									<br /><span class='desctext'>Select the fields this project is available in, providing the user has appropriate permissions.</span>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div id='steps_display_content' class='steps_content' style='display: none;'>
					<div class='acp-box'>
						<h3>Display Options</h3>
						<table class='ipsTable double_pad'>
							<tr>
								<td class='field_title'>
									<strong class='title'>Show in Project View</strong>
								</td>
								<td class='field_field'>
									{$form['project_display']}
									<br /><span class='desctext'>If <strong>yes</strong>, a column will appear in the Project View for this field.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Show in Project Filters</strong>
								</td>
								<td class='field_field'>
									{$form['project_filter']}
									<br /><span class='desctext'>If <strong>yes</strong>, users will be able to filter issues dependant on this field.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Issue Display Type</strong>
								</td>
								<td class='field_field'>
									{$form['issue_display_type']}
									<br /><span class='desctext'>Choose whether to display this field in the 'Issue Information' block, or at the top of the first post.<br /><strong>Text-area fields will always show at the top of the first post.</strong></span>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div id='steps_additional_content' class='steps_content' style='display: none;'>
					<div class='acp-box'>
						<h3>Advanced Settings</h3>
						<table class='ipsTable double_pad'>
							<tr>
								<td class='field_title'>
									<strong class='title'>Maximum Input</strong>
								</td>
								<td class='field_field'>
									{$form['max_input']}
									<br /><span class='desctext'>For text-input and text-area fields only</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Field Required</strong>
								</td>
								<td class='field_field'>
									{$form['not_null']}
									<br /><span class='desctext'>If <strong>yes</strong>, an error will be shown if this field is left blank.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Expected Input Format</strong>
								</td>
								<td class='field_field'>
									{$form['input_format']}
									<br /><span class='desctext'>Use: a for alpha characters<br />Use: n for numerics.<br />Example, Date: nn-nn-nnnn<br />Leave blank to accept any input.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Dropdown Options</strong>
								</td>
								<td class='field_field'>
									{$form['options']}
									<br /><span class='desctext'>For dropdowns only, one set per line. <br />Example for 'Gender' field:<br />m=Male<br />f=Female<br />u=Not Telling<br />Will produce: {$options}</span>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div id='steps_perms_content' class='steps_content' style='display: none;'>
					<div class='acp-box'>
						<h3>Permissions</h3>
						{$field['custom_perms']}
					</div>
				</div>
		</div>
		<div id='steps_navigation' style='margin: 10px 0; padding: 10px; padding-top: 0;'>
			<p class='right' {$extraCSS} id='finish'>
				<input type='submit' class='realbutton' id='save_field' value='Save Field' />
			</p>
			
			<input type='button' class='realbutton left' value='{$this->lang->words['wiz_prev']}' id='prev' />
			<input type='button' class='realbutton right' value='{$this->lang->words['wiz_next']}' id='next' />
			<a href='{$this->settings['base_url']}{$this->form_code}' class='realbutton right cancel' id='cancel' style='margin-right: 7px;'>Cancel</a>
		</div>
		<br class='clear' />
	</form>
</div>
<script type='text/javascript'>
jQ("#steps_bar").ipsWizard( { allowJumping: true, allowGoBack: true } );
custom.init();
</script>
EOF;

return $IPBHTML;
}

function customOverview( $fields=array() )
{

if ( ! count($fields) )
{
	$hide = "style='display:none;'";
}

$IPBHTML = "";

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}../modules/custom/js/acp.custom.js'></script>

<div class='section_title'>
	<h2>Manage Custom Fields</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='add_status' title='Create a Field'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				Create a Field
			</a>
		</li>
		<li {$hide}>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recache'><img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />Recache Custom Fields</a>
		</li>
	</ul>
</div>

<div class='acp-box adv_controls'>
	<h3 class='padded'>Custom Fields</h3>
	<table id='custom_fields' class='ipsTable'>
		<tr>
			<th style='width:5%;'>&nbsp;</th>
			<th style='width:45%;'>Field</th>
			<th style='width:15%;'>Type</th>
			<th style='width:20%;'>Rules</th>
			<th style='width:15%;'>&nbsp;</th>
		</tr>
EOF;

	if ( count($fields) > 0 )
	{
		foreach( $fields as $k => $v )
		{
			$IPBHTML .= <<<EOF
			<tr class='ipsControlRow isDraggable' id='field_{$v['field_id']}'>
				<td style='width:5%;'>
					<span class='draghandle'>&nbsp;</span>
					<!-- <div class='draghandle'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''>
					</div> -->
				</td>
				<td style='width:45%;' class='altrow'><strong>{$v['title']}</strong><div class='desc'>{$v['description']}</div></td>
				<td class='desc' style='width:15%;'>{$v['_type']}</td>
				<td style='width:20%;'>
					<ul class='ipsList desc'>{$v['_rules']}</ul>
				</td>			
				<td style='width:15%; text-align:center;'>
					<ul class='ipsControlStrip desc'>
						<li class='i_edit'>
							<a class="edit edit_field" href="{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;field_id={$v['field_id']}" title="Edit Field">Edit Field</a>
						</li>
						<li class='i_delete'>
							<a class="delete delete_field" href="{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;field_id={$v['field_id']}" title="Delete Field">Delete Field</a>
						</li>
					</ul>
				</td>
			</tr>
EOF;
		}
	}
	else
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td colspan='7' style='width:100%;text-align:center;'>There are no custom fields currently set up. <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button' title='Create a Custom Field'>Click here to create one</a></td>
			</tr>
EOF;
	}
	
$IPBHTML .= <<<EOF
	</table>
</div>
<script type='text/javascript'>
jQ("#custom_fields").ipsSortable( 'table', {
	url: "{$this->settings['base_url']}{$this->form_code}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: START FIELDSET
//===========================================================================
function start_fieldset( $title ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <tr>
  <td width='100%' class='tablerow1' colspan='2'>
   <fieldset>
    <legend><strong>{$title}</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: END FIELDSET
//===========================================================================
function end_fieldset() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
    </table>
   </fieldset>
  </td>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

}

?>