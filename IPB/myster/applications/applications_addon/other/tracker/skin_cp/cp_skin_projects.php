<?php

/**
* Tracker 2.1.0
* 
* Projects page skin
* Last Updated: $Date: 2012-07-25 01:31:40 +0100 (Wed, 25 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1382 $
*/
 
class cp_skin_projects extends output
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

function project_statistics()
{
$IPBHTML = '';

//--starthtml--//
$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['public_dir']}js/tracker_3rd_party/excanvas.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/tracker_3rd_party/canvas2image.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/tracker_3rd_party/canvastext.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/tracker_3rd_party/flotr.debug-0.2.0-alpha.js'></script>
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.project-stats.js'></script>
<style type='text/css'>
.flotr-mouse-value, .flotr-grid-label { display:none !important; }

div#linegraph-rateoffix .flotr-grid-label { display: block !important; };
</style>
<div style='float: left; width:49%;'>
	<h2>Status Overview (Last 30 Days)</h2>
	<noscript>You must be using JavaScript in order to see these graphs</noscript>
	<div id='piechart-statuses' style="height:300px;"></div>
</div>
<div style='float: left; width:49%;'>
	<h2>Severity Overview (Last 30 Days)</h2>
	<noscript>You must be using JavaScript in order to see these graphs</noscript>
	<div id='piechart-severities' style="height:300px;"></div>
</div>
<br class='clear' />
<h2>Fixed vs. Reported (Last 30 Days)</h2>
	<noscript>You must be using JavaScript in order to see these graphs</noscript>
<div id='linegraph-rateoffix' style='height: 200px; width: 97.5%;'></div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List projects
 *
 * @access	public
 * @param	array 		Project list
 * @return	string		HTML
 */
function project_overview( $rows )
{
$IPBHTML 		= "";

//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['p_manageproject']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />Create a Project</a>
		</li>
		<li>
			<a id='recacheProjects' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />Recache Projects</a>
		</li>
		<li class='disabled' style='display:none;'><a id='recacheSuccess'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Projects Recached</a></li>
	</ul>
</div>

<script type="text/javascript" src='{$this->settings['js_app_url']}acp.projects.js'></script>
<script type='text/javascript'>
ACPProjectListing.init();
</script>

<div class='acp-box adv_controls' id='projects'>
 	<h3>Your {$this->lang->words['p_projects']}</h3>
	<ul id='sortable_handle' class='alternate_rows sortable'>
	{$rows}
	</ul>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Project overview rows
 *
 * @access	public
 * @param	array		project info
 * @param	string 		subproject
 * @return	string		HTML
 */
function project_row( $r )
{
	$hasChildren	= " style='display:none;'";
	$className		= '';

	if ( $r['has_children'] )
	{
		$hasChildren	= '';
		$className		= " class='parent'";
	}

	$IPBHTML = "";

if( count( $r ) )
{
	//--starthtml--//
	$IPBHTML .= <<<EOF
		<li id='project_{$r['project_id']}' class='project isProject'>
			<table id='project_{$r['project_id']}_maintable' width='100%' height='50' cellpadding='0' cellspacing='0' class='double_pad ipsTable'{$className}>
				<tr class='ipsControlRow'>
					<td class="col_drag">
						<span class="draghandle">&nbsp;</span>
					</td>
					<td style='padding-left:0!important;'>
						<strong class='larger_text'><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;project_id={$r['project_id']}" title="Edit Project">{$r['title']}</a></strong>
						<div class='desc'>{$r['description']}</div>
					</td>
					<td style='width:150px'>
						<div class='descShow'>{$r['num_issues']} issues; {$r['num_posts']} posts</div>
					</td>
					<td style='width: 50px'>
						<ul class='ipsControlStrip'>
							<li class='i_edit'><a class="edit" href="{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;project_id={$r['project_id']}" title="Edit Project">Edit Project</a></li>
							<li class='ipsControlStrip_more ipbmenu' id="menu{$r['project_id']}"><a href='#'>{$this->lang->words['frm_options']}</a></li>
						</ul>
						<ul class='acp-menu' id='menu{$r['project_id']}_menucontent'>
							<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;parent={$r['project_id']}&amp;do=add">Create Sub-project</a></li>
							<li class='icon delete'><a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;project_id={$r['project_id']}">Delete Project</a></li>
						</ul>
					</td>
				</tr>
			</table>

			<ul id='project_{$r['project_id']}_children' class='sortable' style='margin-left: 2.5%;'>
				<li class='subproject noSubProjects' id='project_{$r['project_id']}_nochildren' style='display:none'>
					<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
						<tr class='control_row'>
							<td style='width: 100%;'>
								<p style='text-align:center;'>Drag a project here to make it a sub-project.</p>
							</td>
						</tr>
					</table>
				</li>
				{$this->recursiveChildren($r)}
			</ul>
		</li>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<div class='item double_pad' style='text-align: center;'>{$this->lang->words['p_noprojects']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button' title='Create a Project'>Click here to create one</a></div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

public function recursiveChildren($child)
{
	$IPBHTML = "";
	
	foreach( $this->registry->tracker->projects()->getChildren($child['project_id']) as $subChild )
	{
		$childHasChildren	= " style='display:none;'";
		$childHtml			= '';
	
		if ( is_array( $this->registry->tracker->projects()->getChildren( $subChild['project_id'] ) ) && count( $this->registry->tracker->projects()->getChildren( $subChild['project_id'] ) ) > 0 )
		{
			$childHasChildren	= '';
			$childHtml 			= $this->recursiveChildren($subChild);
		}
		
		$IPBHTML .= <<<EOF
				<li class='subproject isProject' id='project_{$subChild['project_id']}'>
					<table width='100%' height='50' cellpadding='0' cellspacing='0' class='double_pad ipsTable'>
						<tr class='ipsControlRow'>
							<td class="col_drag">
								<span class="draghandle">&nbsp;</span>
							</td>
							<td style='padding-left:0!important;'>
								<strong class='larger_text'><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;project_id={$subChild['project_id']}" title="Edit Project">{$subChild['title']}</a></strong>
								<div class='desc'>{$subChild['description']}</div>
							</td>
							<td style='width:150px'>
								<div class='descShow'>{$subChild['num_issues']} issues; {$subChild['num_posts']} posts</div>
							</td>
							<td style='width: 50px'>
								<ul class='ipsControlStrip'>
									<li class='i_edit'><a class="edit" href="{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;project_id={$subChild['project_id']}" title="Edit Project">Edit Project</a></li>
									<li class='ipsControlStrip_more ipbmenu' id="menu{$subChild['project_id']}"><a href='#'>{$this->lang->words['frm_options']}</a></li>
								</ul>
								<ul class='acp-menu' id='menu{$subChild['project_id']}_menucontent'>
									<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;parent={$subChild['project_id']}&amp;do=add">Create Sub-project</a></li>
									<li class='icon delete'><a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;project_id={$subChild['project_id']}">Delete Project</a></li>
								</ul>
							</td>
						</tr>
					</table>
					<ul id='project_{$subChild['project_id']}_children' class='sortable' style='margin-left: 2.5%;'>
						<li class='subproject noSubProjects' id='project_{$subChild['project_id']}_nochildren' style='display:none'>
							<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
								<tr class='control_row'>
									<td style='width: 100%;'>
										<p style='text-align:center;'>Drag a project here to make it a sub-project.</p>
									</td>
								</tr>
							</table>
						</li>
						{$childHtml}
					</ul>
				</li>
EOF;
	}
	
	return $IPBHTML;
}

/**
 * Add/edit project form
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
public function project_form( $form, $button, $code, $title, $project, $project_perms, $cache ) 
{
if ( $code == 'edit_save' )
{
	$action = 'edit';
	$title	= "Editing {$project['title']}";
}
else
{
	$action = 'new';
	$title 	= 'Creating New Project';
}

if ( $project['project_id'] )
{
	$extraCSS = "style='margin-left: 5px;'";
	$project_id_javascript = "projects.project_id	= {$project['project_id']};";
	$quickDone = 1;
}
else
{
	$extraCSS = "style='display:none;'";
	$quickDone = 0;
	$project_id_javascript = "projects.project_id = 'new';";
EOF;
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= $this->fieldsJavascript($cache);

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.projects.js'></script>
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.projects.fields.js'></script>
<script type="text/javascript">
{$project_id_javascript}
</script>
<script id="fieldjQTemplate" type="text/x-jquery-tmpl">
	<div id='\${Field}_wrap'>
		<h4 class='trackerFieldTitle'>\${Title} Management</h4>
		<div class='ipsTabBar'>
			<ul id='\${Field}_menu' class='trackerMenu'></ul>
		</div>
		<div id='\${Field}_content'></div>
	</div>
</script>
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='ipsSteps_wrap'>
	<form name='theAdminForm' id='adminform' action="{$this->settings['base_url']}&amp;app=tracker&amp;module=projects&amp;section=projects&amp;do={$code}&amp;project_id={$project['project_id']}" method='POST'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
		<input type='hidden' name='convert' id='convert' value='0' />
		<input type='hidden' name='json_data' id='json_data' value='' />
		<input type='hidden' name='enable_data' id='enable_data' value='' />
		<input type='hidden' name='quick_done' id='quick_done' value='{$quickDone}' />
		
		<div class='ipsSteps clearfix' id='steps_bar'>
			<ul>
				<li id='steps_basic' class='steps_active'>
					<strong class='steps_title'>Step 1</strong>
					<span class='steps_desc'>Basic Settings</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_additional'>
					<strong class='steps_title'>Step 2</strong>
					<span class='steps_desc'>Additional Settings</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_fields'>
					<strong class='steps_title'>Step 3</strong>
					<span class='steps_desc'>Project Fields</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='steps_perms'>
					<strong class='steps_title'>Step 4</strong>
					<span class='steps_desc'>Permissions</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
			</ul>
		</div>
		
		<div id='project_form' class='acp-box'>
			<div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
				<div id='steps_basic_content' class='steps_content'>
					<div class='acp-box'>
						<h3>Basic Settings</h3>
						<table class='ipsTable double_pad'>
							<tr>
								<td class='field_title'>
									<strong class='title'>Project Name</strong>
								</td>
								<td class='field_field'>
									{$form['title']}
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Project Description</strong>
									<span class='desctext'><em>Optional</em></span>
								</td>
								<td class='field_field'>
									{$form['description']}<br />
									<span class='desctext'>You may use HTML - linebreaks are automatically converted to &lt;br /&gt;</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Project Parent</strong>
								</td>
								<td class='field_field'>
									{$form['parent']}
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Category Only</strong>
								</td>
								<td class='field_field'>
									{$form['category']}<br />
									<span class='desctext'>Do you wish to disable issue reporting in this project? Existing issues will be hidden.
						If yes, please remember to set the <strong>Permissions</strong> in <strong>Step 4</strong>.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Enable RSS Feeds</strong>
								</td>
								<td class='field_field'>
									{$form['rss']}
								</td>
							</tr>
						</table>
					</div>
				</div>
				<!-- ADDITIONAL SETTINGS -->
				<div id='steps_additional_content' class='steps_content' style='display: none;'>
					<div class='acp-box'>
						<h3>Additional Settings</h3>
						<table class='ipsTable double_pad'>
							<tr>
								<td class='field_title'>
									<strong class='title'>Enable HTML Posting</strong>
								</td>
								<td class='field_field'>
									{$form['html']}<br />
									<span class='desctext'>This will allow groups that <strong>CAN</strong> post HTML to use it in this project, other groups will not be able to use HTML.</span>
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Enable BBCode</strong>
								</td>
								<td class='field_field'>
									{$form['bbcode']}
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Enable project suggestions</strong>
									<span class='desctext'>With this enabled, users will be able to vote on suggestions in the issue view.</span>
								</td>
								<td class='field_field'>
									{$form['suggestions']}
								</td>
							</tr>
							<tr>
								<td class='field_title'>
									<strong class='title'>Enable Tagging</strong>
									<span class='desctext'>With this enabled, users will be able to tag their issues.</span>
								</td>
								<td class='field_field'>
									{$form['tagging']}
								</td>
							</tr>
						</table>
					</div>
				</div>
				<!-- FIELDS -->
EOF;
			if ( count($cache) OR ! $project['project_id'] )
			{
			
	$IPBHTML .= <<<EOF
				<div id='steps_fields_content' class='steps_content' style='display: none;'>
					<div class='acp-box'>
						<h3>Project Fields</h3>
						<div class='form_menu_left left'>
							<ul id='fields' class='form_menu'>
EOF;

	$IPBHTML .= $this->fieldsCache($cache);
	
	$IPBHTML .= <<<EOF
							</ul>
						</div>
						<div class='form_menu_right right' id='field_content'></div>
						<br class='clear' />
					</div>
				</div>
EOF;
			}
			
	$IPBHTML .= <<<EOF
				<!-- PERMISSIONS -->
				<div id='steps_perms_content' class='steps_content' style='display:none;'>
					<h3>Project Permissions</h3>
					{$project_perms}
				</div>
			</div>
			<div id='steps_navigation' style='margin: 10px 0; padding: 10px; padding-top: 0;'>
				<p class='right' {$extraCSS} id='finish'>
					<input type='submit' class='realbutton' id='save_project' value='Save Project' />
				</p>
				
				<input type='button' class='realbutton left' value='{$this->lang->words['wiz_prev']}' id='prev' />
				<input type='button' class='realbutton right' value='{$this->lang->words['wiz_next']}' id='next' />
				<a href='{$this->settings['base_url']}{$this->form_code}' class='realbutton right cancel' id='cancel' style='margin-right: 7px;'>Cancel</a>
			</div>
			<script type='text/javascript'>
				projects.init();
			</script>
			<br class='clear' />
		</div>
	</form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

public function fieldsJavascript($cache=array()) {
$IPBHTML = '';
$storedJavascript = array();

	foreach( $cache as $k => $v )
	{
		if ( file_exists( $this->registry->tracker->modules()->getModuleFolder($v['module']['directory']) . 'js/project_form.js' ) )
		{
			if ( ! in_array( $v['module']['directory'], $storedJavascript ) )
			{
				$storedJavascript[] = $v['module']['directory'];
				$IPBHTML .= "<script type='text/javascript' src='" . $this->registry->tracker->modules()->getModuleURL( $v['module']['directory'] ) . "js/project_form.js'></script>\n";
			}
		}
	}
	
return $IPBHTML;
}

public function fieldsCache($cache=array()) {
$IPBHTML = '';

	foreach( $cache as $k => $v )
	{
		if ( $v['field_keyword'] != 'custom' )
		{
			if ( $v['record']['enabled'] ) { $checked = "checked='checked'"; $class = ''; }
			
			$IPBHTML .= <<<EOF
			<li id='field_{$v['field_id']}' class='isDraggable ipsExpandable field'>
				<div class='right'>
					<input type='checkbox' class='field_enable' name='field_{$v['field_id']}_enabled' id='enabled_{$v['field_id']}' {$checked} />
				</div>
				<div class='draghandle'>&nbsp;</div>
				<div class='item_info'>{$v['title']}</div>
			</li>
EOF;
		}
	}
	
	return $IPBHTML;
}

/**
 * Permissions matrix
 *
 * @access	public
 * @param	array 		Permission names
 * @param	array		Compiled grids
 * @param	array 		Permissions checked
 * @param	array 		Colors
 * @return	string		HTML
 */
public function permissionMatrix( $perm_names, $perm_matrix, $perm_checked, $colors, $app, $type )
{
$IPBHTML = "";
//--starthtml--//

$cols = count( $perm_names ) + 1;
$width = ceil( 83 / count( $perm_names ) );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<table class='permission_table' id='{$type}_perm_matrix'>
		<tr>
			<td class='off'>&nbsp;</td>
HTML;
		$col_num = 0;
		foreach( $perm_names as $k => $v )
		{
			$col_num++;
			$IPBHTML .= <<<HTML
					<!-- Check an entire column -->
					<td style='background-color:{$colors[$k]}' class='perm column' id='{$type}_column_{$col_num}'>
						{$v}<br />
						<input type='checkbox' name='perms[{$app}][{$type}{$k}][*]' id='{$type}_col_{$col_num}' value='1'{$perm_checked['*'][$k]}>
					</td>
HTML;
		}
		$IPBHTML .= <<<HTML
		</tr>
HTML;
		$row_num = 0;
		
		foreach( $perm_matrix as $set => $row )
		{
			$set = explode( '%', $set );
			$row_num++;
			$col_num = 0;
			$IPBHTML .= <<<HTML
			<tr>	
				<td class='section' colspan='{$cols}'><strong>{$set[1]}</strong></td>
			</tr>
			<tr id='{$type}_row_{$row_num}'>
				<td class='off'>
					<input type='button' id='{$type}_select_row_1_{$row_num}' value=' + ' class='select_row' />&nbsp;
					<input type='button' id='{$type}_select_row_0_{$row_num}' value=' &ndash; ' class='select_row' />
				</td>
HTML;
				foreach( $row as $key => $perm )
				{
					$col_num++;
					$IPBHTML .= <<<HTML
						<td class='perm' id='{$type}_clickable_{$col_num}' style='background-color:{$colors[$key]}'>
							{$perm}<br />
							<input type='checkbox' name='perms[{$app}][{$type}{$key}][{$set[0]}]' id='{$type}_perm_{$row_num}_{$col_num}' value='1'{$perm_checked[$set[0]][$key]}>
						</td>
HTML;
				}
			
		$IPBHTML .= <<<HTML
			</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;	
}

/**
 * Delete project form
 *
 * @access	public
 * @param	array		Form markup
 * @param	array 		Project data
 * @param	integer		Reports
 * @return	string		HTML
 */
public function delete_project( $form, $project, $reports ) 
{

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 	<h3>Delete Project '{$project['title']}'</h3>
 	<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete' method='post'>
		<input type='hidden' name='project_id' value='{$project['project_id']}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
		<table class='ipsTable double_pad'>
			<tr>
				<td class='error' colspan='2'>
					Warning: This will delete a project forever.
				</td>
			</tr>
HTML;

if ( $form['moveprojects'] )
{
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<label for='moveprojects' class='title'>Move subprojects to</label>
				</td>
				<td class='field_field'>
					{$form['moveprojects']} <div class='desctext'>The project you wish to move subprojects to</div>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<strong class='title'>Issues in this project</strong>
				</td>
				<td class='field_field'>{$reports}</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>Delete Issues</strong>
				</td>
				<td class='field_field'>
					{$form['delete_issues']}
					<div class='desctext'>This will delete the issues forever, no going back.</div>
				</td>
			</tr>
HTML;
if ( $form['moveto'] ) 
{
$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<label for='moveto' class='title'>Move issues to</label>
				</td>
				<td class='field_field'>
					{$form['moveto']} <div class='desctext'>If you don't choose to delete the issues, this is the project they will be moved to.</div>
				</td>
			</tr>
HTML;
}
$IPBHTML .= <<<HTML
		</table>
		<div class="acp-actionbar">
			<div class="centeraction">
				<input class="button redbutton" type="submit" value="Delete Project">
			</div>
		</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}