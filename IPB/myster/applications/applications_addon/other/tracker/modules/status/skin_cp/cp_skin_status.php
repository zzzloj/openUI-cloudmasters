<?php

class cp_skin_status extends output {

function createHTML()
{
	return <<<EOF
<div id='editor_name' style='display:none;'>editor-#{random}</div>
<ul class='menu'>
	<li id='save_status' class='save disabled'>Save Status</li>
</ul>
<div id='status_id' style='display:none;'>#{id}</div>
<table width='100%' class='single_pad'>
	<tr class='light'>
		<td width='40%'>Status Name</td>
		<td width='60%;'><input type='text' id='status_title' name='title' class='input_text' size='50' value='#{title}' /></td>
	</tr>
	<tr class='dark'>
		<td width='40%'>Allow New Reports?<br /><span class='desc'>If enabled, this category can be selected by members who do not have 'allow all' access.</span></td>
		<td width='60%;'>
			<span class='yesno_yes'>
				<input type='radio' name='allownew' id='allownew_yes' value='1' #{allownew_yes} />
				<label for='allownew_yes'>Yes</label>
			</span>
			<span class='yesno_no'>
				<input type='radio' name='allownew' id='allownew_no' value='0' #{allownew_no} />
				<label for='allownew_no'>No</label>
			</span>
		</td>
	</tr>
	<tr class='light'>
		<td width='40%'>Closed Status?<br /><span class='desc'>If enabled, no issues can be posted in this status.</span></td>
		<td width='60%;'>
			<span class='yesno_yes'>
				<input type='radio' name='closed' id='closed_yes' value='1' #{closed_yes} />
				<label for='default_yes'>Yes</label>
			</span>
			<span class='yesno_no'>
				<input type='radio' name='closed' id='closed_no' value='0' #{closed_no} />
				<label for='default_no'>No</label>
			</span>
		</td>
	</tr>
	<tr class='light'>
		<td width='100%' colspan='2'>Default Reply Text<br /><span class='desc'>Place any text you would like to be attached to your post when updating an issue to this status. Note that this text will only appear if you do not enter a reply.</span></td>
	</tr>
	<tr class='dark'>
		<td width='100%' colspan='2' id='popup_ed_wrap'>
			<textarea id="editor-#{random}" name="Post" class='ipsEditor_textarea input_text mini'>#{content}</textarea>
		</td>
	</tr>
</table>
EOF;
}

function deleteHTML()
{
	return <<<EOF
<p>You have chosen to delete the status <strong>#{title}</strong> from Tracker. #{text}</p>
<div style='text-align: center;'>
	<br /><br />
	<select id='move_to_new_status'>
		<option value='-1'>----</option>
		#{options}
	</select>
	<br /><br />
	<div class='popup_buttons'>
		<a href='javascript:void(0);' id='delete_status_go' rel='#{id}'>Delete Status</a>
		<a href='javascript:void(0);' id='delete_close' class='cancel_button'>Cancel</a>
	</div>
</div>
EOF;
}

function statusRow()
{
	return <<<EOF
<table style='width:100%;' class='double_pad'>
	<tr class='ipsControlRow'>
		<td style='width:5%;'>
			<div class='draghandle'>&nbsp;</div>
		</td>
		<td style='width:5%; text-align: center;'><input type='radio' name='default' class='default_status' value='' title='Mark as Default Status' #{disabled} #{default}/></td>
		<td style='width:40%;' class='altrow'>#{title}</td>
		<td style='width:15%; text-align:center;'>#{autoreply}</td>
		<td style='width:15%; text-align:center;'>#{closed}</td>
		<td style='width:10%; text-align:center;'>0</td>			
		<td style='width:15%; text-align:center;'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a class="edit_status" href="javascript:void(0);" id='edit_#{id}' title="Edit Status">Edit Status</a>
				</li>
				<li class='i_delete'>
					<a class="delete_status" href="javascript:void(0);" id='delete_#{id}' title="Delete Status">Delete Status</a>
				</li>
			</ul>
		</td>
	</tr>
</table>
EOF;
}

function statusOverview( $status=array() )
{
/* Load editor stuff */
$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
$this->editor = new $classToLoad();

$editor = $this->editor->show( 'Post', array( 'height' => 150, 'type' => 'mini' ) );

$this->registry->output->addToDocumentHead( 'importcss', "{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_ckeditor.css" );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}../modules/status/js/acp.status.js'></script>
<script type='text/javascript'>
	ACPMStatus.addStatusHTML	= new Template("{$this->registry->tracker->parseJavascriptTemplate($this->createHTML())}");
	ACPMStatus.deleteStatusHTML	= new Template("{$this->registry->tracker->parseJavascriptTemplate($this->deleteHTML())}");
	ACPMStatus.statusRow		= new Template("{$this->registry->tracker->parseJavascriptTemplate($this->statusRow())}");
</script>
<div id='editor_wrap' style='display:none;'><div id='editor_wrap_wrap'>{$editor}</div></div>
<div class='section_title'>
	<h2>Manage Statuses</h2>
	<ul class='context_menu'>
		<li>
			<a href='javascript:void(0);' class='add_status' title='Create a Status'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				Create a Status
			</a>
		</li>
		<li>
			<a id='recacheStatuses' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />Recache Statuses</a>
		</li>
		<li class='disabled' style='display:none;'><span id='recacheSuccess'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Statuses Recached</span></li>
	</ul>
</div>

<div class='acp-box adv_controls'>
	<h3 class='padded'>Statuses</h3>
	<ul id='status' class='alternate_rows'>
		<li class='header'>
			<table style='width:100%;' class='double_pad'>
				<tr>
					<td style='width:5%;'>&nbsp;</td>
					<td style='width:5%;'>&nbsp;</td>
					<td style='width:40%;'>Status Title</td>
					<td style='width:15%; text-align:center;'>Auto-Reply</td>
					<td style='width:15%; text-align:center;'>Closed Status</td>
					<td style='width:10%; text-align:center;'>Issues Filed</td>
					<td style='width:15%;'>&nbsp;</td>
				</tr>
			</table>
		</li>
EOF;

	if ( count($status) > 0 )
	{
		foreach( $status as $k => $v )
		{
			$default = '';
			
			if ( $v['default_open'] )
			{
				$default = "checked='checked' ";
			}
			
			if ( $v['allow_new'] )
			{
				$defaultHTML = "<input type='radio' name='default' class='default_status' value='' title='Mark as Default Status' {$default}/>";
			}
			else
			{
				$defaultHTML = "<input type='radio' name='default' class='default_status' value='' title='Mark as Default Status' disabled='disabled' />";
			}
			
			$IPBHTML .= <<<EOF
	<li id='status_{$v['status_id']}' class='isDraggable'>
		<table style='width:100%;' class='double_pad'>
			<tr class='ipsControlRow'>
				<td style='width:5%;'>
					<div class='draghandle'>&nbsp;
					</div>
				</td>
				<td style='width:5%; text-align: center;'>{$defaultHTML}</td>
				<td style='width:40%;' class='altrow'>{$v['title']}</td>
				<td style='width:15%; text-align:center;'>{$v['_auto_reply']}</td>
				<td style='width:15%; text-align:center;'>{$v['_closed']}</td>
				<td style='width:10%; text-align:center;'>{$v['_count']}</td>			
				<td style='width:15%; text-align:center;'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a class="edit_status" href="javascript:void(0);" id='edit_{$v['status_id']}' title="Edit Status">Edit Status</a>
						</li>
						<li class='i_delete'>
							<a class="delete_status" href="javascript:void(0);" id='delete_{$v['status_id']}' title="Delete Status">Delete Status</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>
	</li>
EOF;
		}
	}
	else
	{
		$IPBHTML .= <<<EOF
	<li id='no_status'>
		<table style='width:100%;' class='double_pad'>
			<tr>
				<td style='width:100%;text-align:center;'>There are no statuses currently set up. <a href='javascript:void(0);' class='add_status' title='Create a Status'>Create</a> one now!</td>
			</tr>
		</table>
	</li>
EOF;
	}
	
$IPBHTML .= <<<EOF
	</ul>
</div>
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