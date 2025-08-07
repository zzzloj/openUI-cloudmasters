<?php
/**
 * <pre>
 * Invision Power Services
 * Database moderators skin file
 * Last Updated: $Date: 2012-02-21 08:12:11 -0500 (Tue, 21 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		24th September 2009
 * @version		$Revision: 10329 $
 */
 
class cp_skin_database_moderators
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
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

/**
 * Add or edit a moderator
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Moderator data
 * @return	string		HTML
 */
public function moderatorForm( $type, $database, $moderator )
{
$IPBHTML = "";
//--starthtml--//

$types			= array( array( 'group', $this->lang->words['mod_type_group'] ), array( 'member', $this->lang->words['mod_type_member'] ) );
$groups			= array();

foreach( $this->caches['group_cache'] as $id => $data )
{
	$groups[]	= array( $id, $data['g_title'] );
}

$_type			= $this->registry->output->formDropdown( 'moderator_type', $types, $moderator['moderator_type'] );
$group			= $this->registry->output->formDropdown( 'moderator_group', $groups, $moderator['moderator_type'] == 'group' ? $moderator['moderator_type_id'] : 0 );
$username		= $this->registry->output->formInput( 'moderator_member', $moderator['moderator_member'] );

$add			= $this->registry->output->formYesNo( 'moderator_add_record', $moderator['moderator_add_record'] );
$edit			= $this->registry->output->formYesNo( 'moderator_edit_record', $moderator['moderator_edit_record'] );
$delete			= $this->registry->output->formYesNo( 'moderator_delete_record', $moderator['moderator_delete_record'] );
$deletec		= $this->registry->output->formYesNo( 'moderator_delete_comment', $moderator['moderator_delete_comment'] );
$lock			= $this->registry->output->formYesNo( 'moderator_lock_record', $moderator['moderator_lock_record'] );
$unlock			= $this->registry->output->formYesNo( 'moderator_unlock_record', $moderator['moderator_unlock_record'] );
$approve		= $this->registry->output->formYesNo( 'moderator_approve_record', $moderator['moderator_approve_record'] );
$approvec		= $this->registry->output->formYesNo( 'moderator_approve_comment', $moderator['moderator_approve_comment'] );
$pin			= $this->registry->output->formYesNo( 'moderator_pin_record', $moderator['moderator_pin_record'] );
$editc			= $this->registry->output->formYesNo( 'moderator_edit_comment', $moderator['moderator_edit_comment'] );
$restorer		= $this->registry->output->formYesNo( 'moderator_restore_revision', $moderator['moderator_restore_revision'] );
$extras			= $this->registry->output->formYesNo( 'moderator_extras', $moderator['moderator_extras'] );
	
$text			= $type == 'edit' ? $this->lang->words['editing_moderator'] : $this->lang->words['adding_moderator'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post' id='adminform' name='adform'>
<input type='hidden' name='moderator' value='{$moderator['moderator_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['moderator_perms_head']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__type']}</strong></td>
			<td class='field_field'>
				{$_type}
				<div class='field_subfield_wrap' id='mod_group'>{$group}</div>
				<div class='field_subfield_wrap' id='mod_user'>{$username}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__add']}</strong></td>
			<td class='field_field'>{$add}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__edit']}</strong></td>
			<td class='field_field'>{$edit}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__delete']}</strong></td>
			<td class='field_field'>{$delete}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__editc']}</strong></td>
			<td class='field_field'>{$editc}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__deletec']}</strong></td>
			<td class='field_field'>{$deletec}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__lock']}</strong></td>
			<td class='field_field'>{$lock}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__unlock']}</strong></td>
			<td class='field_field'>{$unlock}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__pin']}</strong></td>
			<td class='field_field'>{$pin}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__approve']}</strong></td>
			<td class='field_field'>{$approve}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__approvec']}</strong></td>
			<td class='field_field'>{$approvec}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__restorer']}</strong></td>
			<td class='field_field'>{$restorer}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__extras']}</strong></td>
			<td class='field_field'>{$extras} <div class='desctext'>{$this->lang->words['modform__extras_desc']}</div></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of moderators within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Moderators
 * @param	array 		Member data (for member moderators)
 * @return	string		HTML
 */
public function moderators( $database, $moderators, $members=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbmods_title']} {$database['database_name']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;do=add&amp;id={$database['database_id']}' title='{$this->lang->words['add_mod_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_mod_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_fields']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/layout_content.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_fields']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_records']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/table_add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_records']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_cats']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_cats']}
				</a>
			</li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='acp-box adv_controls'>
	<h3>{$this->lang->words['all_moderators_t']}</h3>
	<table class='ipsTable'>
		<tr>
			<th style='width: 20%'>{$this->lang->words['editorname_th']}</th>
			<th style='width: 8%'>{$this->lang->words['moderator_col_add']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_edit_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_del_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_del_c_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_lock_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_pin_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_unlock_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_approve_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_approvec_th']}</th>
			<th style='width: 8%' class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $moderators ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='11' class='no_messages'>
				{$this->lang->words['no_mods_created_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['create_moderator_now']}</a>
			</td>
		</tr>
HTML;
}
else
{
	foreach( $moderators as $moderator )
	{
		$_add		= $moderator['moderator_add_record'] ? 'tick.png' : 'cross.png';
		$_edit		= $moderator['moderator_edit_record'] ? 'tick.png' : 'cross.png';
		$_delete	= $moderator['moderator_delete_record'] ? 'tick.png' : 'cross.png';
		$_deleteC	= $moderator['moderator_delete_comment'] ? 'tick.png' : 'cross.png';
		$_lock		= $moderator['moderator_lock_record'] ? 'tick.png' : 'cross.png';
		$_pin		= $moderator['moderator_pin_record'] ? 'tick.png' : 'cross.png';
		$_unlock	= $moderator['moderator_unlock_record'] ? 'tick.png' : 'cross.png';
		$_approve	= $moderator['moderator_approve_record'] ? 'tick.png' : 'cross.png';
		$_approvec	= $moderator['moderator_approve_comment'] ? 'tick.png' : 'cross.png';

		if( $moderator['moderator_type'] == 'group' )
		{
			$name	= $this->lang->words['mod_g_prefix'] . ' ' . $this->caches['group_cache'][ $moderator['moderator_type_id'] ]['g_title'];
		}
		else
		{
			$name	= "<a href='{$this->settings['board_url']}/index.php?showuser={$moderator['moderator_type_id']}' target='_blank'>{$members[ $moderator['moderator_type_id'] ]['members_display_name']}</a>";
		}
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><span class='larger_text'>{$name}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_add}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_edit}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_delete}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_deleteC}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_lock}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_pin}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_unlock}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_approve}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_approvec}' alt='{$this->lang->words['icon']}' /></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;do=edit&amp;id={$database['database_id']}&amp;moderator={$moderator['moderator_id']}'>{$this->lang->words['edit_moderator_menu']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;do=delete&amp;id={$database['database_id']}&amp;moderator={$moderator['moderator_id']}' );">{$this->lang->words['delete_moderator_menu']}</a>
					</li>
				</ul>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

}