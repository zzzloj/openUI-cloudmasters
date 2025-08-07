<?php

/**
* Tracker 2.1.0
* 
* Skin templates
* Last Updated: $Date: 2012-09-11 21:58:07 +0100 (Tue, 11 Sep 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1386 $
*/

class cp_skin_module_versions_project_form extends output {

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

function versionRemove($versions)
{
$IPBHTML = <<<EOF
	<p>Before this version can be deleted, we need to know where you would like to send all existing issues to. Please select a new version to send issues to below.</p>
	<p>Please note that the version will <strong>NOT</strong> be deleted until you click 'Save Project' on the main form, clicking 'Remove' simply marks it for deletion for later.</p>
	<br /><br />
	<div style='text-align:center; font-weight:bold;'>New Version&nbsp;<select style='margin-left: 7px;' name='versionSend-#{id}' id='versionSend-#{id}'>
		<option value='nva'>No Version Assigned</option>
EOF;

	foreach( $versions as $v )
	{
		$IPBHTML .= "<option value='{$v['version_id']}'>{$v['human']}</option>\n";
	}

$IPBHTML .= <<<EOF
	</select></div>
	<br />
	<div style='text-align:center;' class='popup_buttons'>
		<a href='javascript:void(0);' id='versionDoRemove-#{id}'>Remove</a>
		<a href='javascript:void(0);' id='versionDoRemoveCancel-#{id}' class='cancel_button'>Cancel</a>
	</div>
EOF;

return $IPBHTML;
}

function versionItem()
{
return <<<EOF
<input type='hidden' name='versions[#{id}][save_type]' id='versionSaveType-#{id}' value='new' />
<input type='hidden' name='versions[#{id}][move_to]' id='versionMoveTo-#{id}' value='' />
<table width='100%' cellpadding='0' cellspacing='0'>
	<tr class='double_pad'>
		<td width='2%'>
			<div class='draghandle'>
				<img src='{$this->settings['img_url']}/tracker/drag_icon.png' alt='' />
			</div>
		</td>
		<td width='7%'>
			<input type='text' id='versionValue-#{id}' name='versions[#{id}][human]' value='' class='input_text' size='45' />
		</td>
		<td width='15%' align='center'>
			<input type='hidden' id='versionTypeRel-#{id}' name='versions[#{id}][type]' value='open' />
			<span id='versionType-#{id}' class='clickable version_types ipsBadge ipsBadge_grey' rel='open'><span rel='open'>Open</span><span rel='locked'>Locked</span><span rel='staff'>Staff-Only</span></span>
		</td>
		<td width='15%' align='center'>
			<input type='hidden' id='versionDefault-#{id}' name='versions[#{id}][default]' value='0' />
			<input type='radio' id='defaultVersion-#{id}' name='defaultVersion' />
		</td>
		<td width='5%' align='center' id='versionTDRemove-#{id}'>
			<img id='versionRemove-#{id}' rel='new' src='{$this->settings['img_url']}/delete.png' class='clickable version_remove' />
		</td>
	</tr>
</table>
EOF;
}

/**
 * Return the HTML form for our settings
 *
 * @access	public
 * @return	html
 */
function versionPopup($versions=array()) {
$content 	= $this->registry->tracker->parseJavascriptTemplate( $this->versionItem() );
$verCount	= count($versions);

$IPBHTML .= <<<EOF
	<ul style='width: 100%;' id='versions'>
		<li class='header'>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr class='version_header double_pad'>
					<th width='2%'>&nbsp;</th>
					<th width='33%'>Project Version</th>
					<th width='15%' style='text-align:center;'>Version Type&nbsp;&nbsp;<img src='{$this->settings['img_url']}/information.png' alt='Help' id='versionInfo' class='clickable' /></th>
					<th style='text-align:center;' width='15%'>Default</th>
					<th style='text-align:center;' width='5%'>&nbsp;</th>
				</tr>
			</table>
		</li>
EOF;

	$noversions = '';
	
	if ( count($versions) )
	{
		$noversions = "style='display:none;'";
		
		foreach( $versions as $k => $v )
		{
			$open	= $v['permissions'] == 'open' ? '' : "style='display:none;'";
			$locked	= $v['permissions'] == 'locked' ? '' : "style='display:none;'";
			$staff	= $v['permissions'] == 'staff' ? '' : "style='display:none;'";
			
			$default = $v['report_default'] ? "checked='checked'" : '';
			
			$IPBHTML .= <<<EOF
			<li id='version_{$v['version_id']}' class='isDraggable'>
				<input type='hidden' name='versions[{$v['version_id']}][save_type]' id='versionSaveType-{$v['version_id']}' value='save' />
				<input type='hidden' name='versions[{$v['version_id']}][move_to]' id='versionMoveTo-{$v['version_id']}' value='' />
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr class='double_pad'>
						<td width='2%'>
							<div class='draghandle'>
								<img src='{$this->settings['img_url']}/tracker/drag_icon.png' alt='' />
							</div>
						</td>
						<td width='7%'>
							<input type='text' id='versionValue-{$v['version_id']}' name='versions[{$v['version_id']}][human]' value='{$v['human']}' class='input_text' size='45' />
						</td>
						<td width='15%' align='center'>
							<input type='hidden' id='versionTypeRel-{$v['version_id']}' name='versions[{$v['version_id']}][type]' value='{$v['permissions']}' />
							<span id='versionType-{$v['version_id']}' class='clickable version_types ipsBadge ipsBadge_grey' rel='{$v['permissions']}'><span {$open} rel='open'>Open</span><span {$locked} rel='locked'>Locked</span><span {$staff} rel='staff'>Staff-Only</span></span>
						</td>
						<td width='15%' align='center'>
							<input type='hidden' id='versionDefault-{$v['version_id']}' name='versions[{$v['version_id']}][default]' value='0' />
							<input type='radio' id='defaultVersion-{$v['version_id']}' name='defaultVersion' {$default} />
						</td>
						<td width='5%' align='center' id='versionTDRemove-{$v['version_id']}'>
							<img id='versionRemove-{$v['version_id']}' rel='edit' src='{$this->settings['img_url']}/delete.png' class='clickable version_remove' />
						</td>
					</tr>
				</table>
			</li>
EOF;
		}
	}

$IPBHTML .= <<<EOF
			<li id='no_versions' {$noversions}>
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr class='double_pad light'>
						<td style='text-align:center; width:100%;'>There are no versions for this project, click 'Add Version' to create one now.</td>
					</tr>
				</table>
			</li>
	</ul>
<div id='javascript' style='display:none;'>
ipb.tracker.templates['version_li'] = new Template("{$content}");
ipb.tracker.liCount = $verCount;
</div>
EOF;

return $IPBHTML;
}

}

?>