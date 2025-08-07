<?php
/**
* Tracker 2.1.0
* 
* Custom fields skin
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/
 
class cp_skin_custom extends output
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

/**
 * List custom fields
 *
 * @access	public
 * @param	array 		Project list
 * @return	string		HTML
 */
function custom_overview( $rows )
{
$IPBHTML = "";

//--starthtml--//
$IPBHTML .= <<<EOF

<div class='section_title'>
	<h2>Manage Custom Fields</h2>		
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='Create a Custom Field'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' />
				Create a Custom Field
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript'>

window.onload = function() {
	Sortable.create( 'sortable_handle', { revert: true, format: 'project_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'projects' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}do=move&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};
</script>

<div class='acp-box'>
 	<h3>Manage Custom Fields</h3>
	<div>
		<table width='100%' border='0' cellspacing='0' cellpadding='0' class='double_pad'>
			<tr>
				<td class='tablesubheader' style='width: 2%'>&nbsp;</td>
				<td class='tablesubheader' style='width: 78%'>Field Title</td>
				<td class='tablesubheader' style='width: 15%; text-align: center; '>Type</td>
				<td class='tablesubheader' style='width: 5%; text-align: center;'>&nbsp;</td>
			</tr>
		</table>
	</div>
	<ul id='sortable_handle' class='alternate_rows'>
	{$rows}
	</ul>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Custom fields rows
 *
 * @access	public
 * @param	array		fields info
 * @param	string 		subproject
 * @return	string		HTML
 */
function custom_row( $r )
{
$IPBHTML = "";
if( count( $r ) )
{
	//--starthtml--//
	$IPBHTML .= <<<EOF
		<li id='project_{$r['project_id']}'>
			<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
				<tr>
					<td style='width: 2%'>
						<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div>
					</td>
					<td style='width: 78%'>
						<strong><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;parent={$r['project_id']}'>{$r['project_title']}</a></strong>								
						<div class='desctext'>{$r['project_description']}<br /><em>{$sub}</em></div>
					</td>
					<td style='width:15%; text-align:center;'>
						{$r['project_num_issues']}
					</td>
					<td style='width: 5%'>
						<img id="menu_{$r['field_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='Options' class='ipbmenu' />
						<ul class='acp-menu' id='menu_{$r['field_id']}_menucontent'>
							<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;field_id={$r['field_id']}'>Edit Field</a></li>
							<li class='icon delete'><a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;field_id={$r['field_id']}">Delete Field</a></li>
						</ul>
					</td>
				</tr>
			</table>
		</li>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<li class='no_items' style='text-align: center;'>You have no custom fields added.</li>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Add/edit custom field form
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form button title
 * @param	string		Action code
 * @param	string		Form title
 * @param	array 		Project information
 * @param	array 		Project permissions
 * @return	string		HTML
 */
public function custom_form( $form, $button, $code, $title, $custom, $custom_perms ) 
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>Create New Custom Field</h2>
</div>
		
<form name='theAdminForm' id='adminform' action="{$this->settings['base_url']}{$this->form_code}&amp;do={$code}" method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<input type='hidden' name="field_id" value="{$custom['field_id']}" />
	<input type='hidden' name='convert' id='convert' value='0' />

	<div class='acp-box'>
		<h3>Create New Custom Field</h3>
 
		<ul class='acp-form alternate_rows'>
			<li><label class='head'>Basic Options</label></li>
			<li>
				<label>Field Keyword<span class='desctext'>Used by the system in the background for tracking. e.g. 'date', 'svn_commit', 'category'</span></label>
				{$form['keyword']}
			</li>
			<li>
				<label>Field Title<span class='desctext'>Max characters: 200</span></label>
				{$form['title']}
			</li>
			<li>
				<label>Field Title: Plural Form<span class='desctext'>Used for drop down fields when an 'ALL' selection is needed.<br />Max characters: 200</span></label>
				{$form['plural']}
			</li>
			<li>
				<label>Description</label>
				{$form['description']}
			</li>
			<li>
				<label>Projects<span class='desctext'>Select the projects this custom field will be available in. Use 'Ctrl' and/or 'Shift' to select the combination of projects required.</span></label>
				{$form['projects']}
			</li>
			<li>
				<label>Field Type</label>
				{$form['type']}
			</li>
			<li>
				<label>Maximum Input<span class='desctext'>For text input and text areas (in characters)</span></label>
				{$form['max_input']}
			</li>
			
			<li><label class='head'>Advanced Options</label></li>
			<li>
				<label>Global Options?<span class='desctext'>If 'yes', the following settings will apply to this field in all enabled projects. If 'no', these setting will need to be setup for each enabled project using the 'Manage Projects' area.</span></label>
				{$form['global']}
			</li>
			<li>
				<label>Show in Project View<span class='desctext'>If yes, field will be displayed as a column in the bug list of the projects above. Does not function if field is private.</span></label>
				{$form['project_view']}
			</li>
			<li>
				<label>Field Required<span class='desctext'>If 'yes', an error will be shown if this field is not completed.</span></label>
				{$form['required']}
			</li>
			<li>
				<label>Expected Input Format<span class='desctext'>Use: a for alpha characters Use: n for numerics. Example, Date: nn-nn-nnnn Leave blank to accept any input</span></label>
				{$form['format']}
			</li>
			<li>
				<label>Option Content (for drop downs)<div class="desctext">In sets, one set per line<br />Example for 'Gender' field:<br/>m=Male<br/>f=Female<br/>u=Not Telling<br/>Will produce:<br/><select name="pants"><option value="m">Male</option><option value="f">Female</option><option value="u">Not Telling</option></select><br/>m,f or u stored in database. When showing field in profile, will use value from pair (f=Female, shows 'Female')</div></label>
				{$form['content']}
			</li>
			<li>
				<label>Multimod Updateable (for drop downs)<span class='desctext'>If yes, those with MODIFY permissions and access to multimod will be able to update this field.</span></label>
				{$form['multimod']}
			</li>
			<li>
				<label>Project View and Report Filter (for drop downs)<span class='desctext'>Will display a dropdown in the filter area of the project view as well as in the report area for those with proper VIEW and project permissions.</span></label>
				{$form['filter']}
			</li>
		</ul>
EOF;

if ( $project_perms )
{
$IPBHTML .= <<<EOF
<br />
$project_perms
<br />
EOF;
}

$IPBHTML .= <<<EOF
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='$button' class='button primary' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}