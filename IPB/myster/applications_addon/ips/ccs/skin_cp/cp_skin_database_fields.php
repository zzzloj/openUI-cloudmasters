<?php
/**
 * <pre>
 * Invision Power Services
 * Database fields skin file
 * Last Updated: $Date: 2012-02-21 08:12:11 -0500 (Tue, 21 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd September 2009
 * @version		$Revision: 10329 $
 */
 
class cp_skin_database_fields
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
 * Add or edit a field
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Field data
 * @param	array 		Field types
 * @param	array 		Validators
 * @return	string		HTML
 */
public function fieldForm( $type, $database, $field, $types, $validators )
{
$IPBHTML = "";
//--starthtml--//

$name			= $this->registry->output->formInput( 'field_name', $field['field_name'], 'field_name' );
$key			= $this->registry->output->formInput( 'field_key', $field['field_key'], 'field_key' );
$desc			= $this->registry->output->formTextarea( 'field_description', IPSText::br2nl( $field['field_description'] ), 40, 5, 'field_description', '', 'normal' );
$extra			= $this->registry->output->formTextarea( 'field_extra', $field['field_extra'], 40, 9, '', '', 'normal' );
$html			= $this->registry->output->formYesNo( 'field_html', $field['field_html'], 'field_html' );

$types			= $this->registry->output->formDropdown( 'field_type', $types, $field['field_type'], 'field_type' );

$required		= $this->registry->output->formYesNo( 'field_required', $field['field_required'], 'field_required' );
$userEdit		= $this->registry->output->formYesNo( 'field_user_editable', $field['field_user_editable'], 'field_user_editable' );
$length			= $this->registry->output->formInput( 'field_max_length', $field['field_max_length'], 'field_max_length' );
$numeric		= $this->registry->output->formYesNo( 'field_is_numeric', $field['field_is_numeric'], 'field_is_numeric' );
$truncate	 	= $this->registry->output->formInput( 'field_truncate', $field['field_truncate'], 'field_truncate' );
$default		= $this->registry->output->formTextarea( 'field_default_value', $field['field_default_value'], 40, 9, 'field_default_value', '', 'normal' );
$dis_list		= $this->registry->output->formYesNo( 'field_display_listing', $field['field_display_listing'], 'field_display_listing' );
$dis_dis		= $this->registry->output->formYesNo( 'field_display_display', $field['field_display_display'], 'field_display_display' );
$topicFormat	= $this->registry->output->formTextarea( 'field_topic_format', $field['field_topic_format'], 40, 9, 'field_topic_format', '', 'normal' );

$_available		= array(
						array( 'strtolower', $this->lang->words['ffo__strtolower'] ),
						array( 'strtoupper', $this->lang->words['ffo__strtoupper'] ),
						array( 'ucfirst', $this->lang->words['ffo__ucfirst'] ),
						array( 'ucwords', $this->lang->words['ffo__ucwords'] ),
						array( 'punct', $this->lang->words['ffo__removeexcesspunct'] ),
						array( 'numerical', $this->lang->words['ffo__numericalformat'] ),
						);
$formatopts		= $this->registry->output->formMultiDropdown( 'field_format_opts[]', $_available, explode( ',', $field['field_format_opts'] ) );

$_ex			= explode( ',', $field['field_extra'] );
$fieldtype	 	= $this->registry->output->formDropdown( 'field_rel_type', array( array( 'dropdown', $this->lang->words['relfield_type_d'] ), array( 'multiselect', $this->lang->words['relfield_type_m'] ), array( 'typeahead', $this->lang->words['relfield_type_t'] ) ), $_ex[2] );

$validatorOptions	= array( array( 'none', $this->lang->words['validator__none'] ) );
$_validator			= explode( ';_;', $field['field_validator'] );

foreach( $validators as $_valid )
{
	$validatorOptions[]	= array( $_valid['key'], $_valid['language'] );
}

$fieldvalid	 		= $this->registry->output->formDropdown( 'field_validator', $validatorOptions, $_validator[0] );
$fieldvalidCustom	= $this->registry->output->formInput( 'field_validator_custom', IPSText::htmlspecialchars( $_validator[1] ), 'field_validator_custom' );
$fieldvalidError	= $this->registry->output->formInput( 'field_validator_error', $_validator[2], 'field_validator_error' );
$rellink			= $this->registry->output->formYesNo( 'field_rel_link', $_ex[3], 'field_rel_link' );
$crosslink			= $this->registry->output->formYesNo( 'field_rel_crosslink', $_ex[4], 'field_rel_crosslink' );

$filter				= $this->registry->output->formYesNo( 'field_filter', $field['field_filter'], 'field_filter' );

$text			= $type == 'edit' ? $this->lang->words['editing_field_title'] . ' ' . $field['field_name'] : $this->lang->words['adding_field_title'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$_tChecked		= $field['_isTitle'] ? " checked='checked' disabled='disabled'" : '';
$_cChecked		= $field['_isContent'] ? " checked='checked' disabled='disabled'" : '';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

<div class='information-box'>
	{$this->lang->words['field_js_extra_info']}
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}&amp;id={$database['database_id']}' method='post' id='adminform' name='adform'>
	<input type='hidden' name='field' id='field_edit_id' value='{$field['field_id']}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['edit_field_details']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__name']}</strong></td>
				<td class='field_field'>
					{$name}
					&nbsp;&nbsp;&nbsp;<input type='checkbox' name='title_field' value='1'{$_tChecked} /> <a href='#' id='explain_title_field' style='font-size: 11px'>Title Field?</a>
					&nbsp;&nbsp;&nbsp;<input type='checkbox' name='content_field' value='1'{$_cChecked} /> <a href='#' id='explain_content_field' style='font-size: 11px'>Content Field?</a>
					<div class='field_subfield'>{$this->lang->words['fieldform__key']}: {$key}</div>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__desc']}</strong></td>
				<td class='field_field'>{$desc}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__type']}</strong></td>
				<td class='field_field'>{$types}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__useredit']}</strong></td>
				<td class='field_field'>
					{$userEdit}<br />
					<span class='desctext'>{$this->lang->words['fieldform__useredit_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__numeric']}</strong></td>
				<td class='field_field'>
					{$numeric}<br />
					<span class='desctext'>{$this->lang->words['fieldform__numeric_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__default']}</strong></td>
				<td class='field_field'>
					{$default}<br />
					<span class='desctext'>{$this->lang->words['fieldform__default_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_validator_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__validator']}</strong></td>
				<td class='field_field'>
					{$fieldvalid}
					<div id='validator_custom'>
						<strong>{$this->lang->words['fieldform_validator_custom']}</strong> {$fieldvalidCustom} <br />
						<strong>{$this->lang->words['fieldform_validator_error']}</strong> {$fieldvalidError}
					</div><br />
					<span class='desctext'>{$this->lang->words['fieldform__validator_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__dislist']}</strong></td>
				<td class='field_field'>
					{$dis_list}<br />
					<span class='desctext'>{$this->lang->words['fieldform__dislist_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__disdisplay']}</strong></td>
				<td class='field_field'>
					{$dis_dis}<br />
					<span class='desctext'>{$this->lang->words['fieldform__disdisplay_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__topicformat']}</strong></td>
				<td class='field_field'>
					{$topicFormat}<br />
					<span class='desctext'>{$this->lang->words['fieldform__topicformat_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_html_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__html']}</strong></td>
				<td class='field_field'>
					{$html}<br />
					<span class='desctext'>{$this->lang->words['fieldform__html_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_required_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__req']}</strong></td>
				<td class='field_field'>{$required}</td>
			</tr>
			<tr id='field_length_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__length']}</strong></td>
				<td class='field_field'>
					{$length}<br />
					<span class='desctext'>{$this->lang->words['fieldform__length_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_extra_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__extra']}</strong></td>
				<td class='field_field'>
					{$extra}<br />
					<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__extra_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_filter_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__filter']}</strong></td>
				<td class='field_field'>
					{$filter}
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_formatopts_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__formatopts']}</strong></td>
				<td class='field_field'>
					{$formatopts}<br />
					<span class='desctext'>{$this->lang->words['fieldform__formatopts_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_truncate_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__truncate']}</strong></td>
				<td class='field_field'>
					{$truncate}<br />
					<span class='desctext'>{$this->lang->words['fieldform__truncate_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_reltype_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__fieldtype']}</strong></td>
				<td class='field_field'>{$fieldtype}</td>
			</tr>
			<tr class='field_type_wrapper' id='field_rellink_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__rellink']}</strong></td>
				<td class='field_field'>{$rellink}<div class='desctext'>{$this->lang->words['fieldform__rellink_desc']}</div></td>
			</tr>
			<tr class='field_type_wrapper' id='field_crosslink_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__crosslink']}</strong></td>
				<td class='field_field'>{$crosslink}<div class='desctext'>{$this->lang->words['fieldform__crosslink_desc']}</div></td>
			</tr>
			<tr class='field_type_wrapper' id='field_databases_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__dbs']}</strong></td>
				<td class='field_field'>
					<select id='field_databases_select' name='field_database'></select><br />
					<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__dbs_desc']}</span>
				</td>
			</tr>
			<tr class='field_type_wrapper' id='field_fields_li'>
				<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__flds']}</strong></td>
				<td class='field_field'>
					<select id='field_fields_select' name='field_fields'></select><br />
					<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__flds_desc']}</span>
				</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
			<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;id={$database['database_id']}';" value='{$this->lang->words['button__cancel']}' />
		</div>
	</div>	
</form>
<script type='text/javascript'>
	var fieldDescriptions	= new Hash();
	fieldDescriptions.set( 'standard', "{$this->lang->words['fieldform__extra_desc']}" );
	fieldDescriptions.set( 'date', "{$this->lang->words['fieldform__extra_desc_date']}" );
	fieldDescriptions.set( 'upload', "{$this->lang->words['fieldform__extra_desc_upload']}" );

	ipb.lang['changejstitle']	= '{$this->lang->words['changejstitle']}';
	ipb.lang['changejsinit']	= '{$this->lang->words['changejsinit']}';
	
	var obj = new acp.liveedit( 
					$('field_name'),
					$('field_key'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=fields&do=checkKey&database={$database['database_id']}"
					}
			  );
</script>
<div id='title_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['title_field_help_h3']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['titlefield_helppage']}
	</div>
</div>
<div id='content_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['content_field_help_h3']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['contentfield_helppage']}
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of fields within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @return	string		HTML
 */
public function fields( $database, $fields )
{
$IPBHTML = "";
//--starthtml--//

$_showWarning	= true;

if( count($fields) )
{
	foreach( $fields as $field )
	{
		if( $database['database_field_title'] == 'field_' . $field['field_id'] )
		{
			$_showWarning	= false;
			break;
		}
	}
}

if( $_showWarning )
{
	$IPBHTML .= <<<HTML
<div class='warning'>
	{$this->lang->words['no_title_field_defined_db']}
</div>
<br />
HTML;
}

$_types	= $this->registry->ccsFunctions->getFieldsClass()->getTypes();

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbfields_title']} {$database['database_name']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;do=add&amp;id={$database['database_id']}' title='{$this->lang->words['add_field_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_field_button']}
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
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_mods']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_mods']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
HTML;

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['all_created_fields_t']}</h3>
	<table class='ipsTable' id='fields_list'>
		<tr>
			<th class='col_drag'&nbsp;</th>
			<th>{$this->lang->words['field_name_th']}</th>
			<!--<th>{$this->lang->words['field_key_th']}</th>-->
			<th>{$this->lang->words['field_type_th']}</th>
			<th>{$this->lang->words['field_required_th']}</th>
			<th>{$this->lang->words['field_useredit_th']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $fields ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' class='no_messages'>
				{$this->lang->words['no_fields_created_yet']} <a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;do=add&amp;id={$database['database_id']}' class='mini_buttons'>{$this->lang->words['create_field_now']}</a>
			</td>
		</tr>
HTML;
}
else
{
	foreach( $fields as $field )
	{
		$name	= "<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;do=edit&amp;id={$database['database_id']}&amp;field={$field['field_id']}'><strong>{$field['field_name']}</strong></a>";
		
		if( $field['field_description'] )
		{
			$name	.= "<div class='desctext'>{$field['field_description']}</div>";
		}
		
		$_required	= $field['field_required'] ? 'tick.png' : 'cross.png';
		$_useredit	= $field['field_user_editable'] ? 'tick.png' : 'cross.png';
		$_type		= '';

		foreach( $_types as $__type )
		{
			if( $__type[0] == $field['field_type'] )
			{
				$_type	= $__type[1];
				break;
			}
		}

		$_type		= $_type ? $_type : $this->lang->words['field_type__' . $field['field_type'] ];
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='field_{$field['field_id']}'>
			<td><div class='draghandle'></div></td>
			<td><span class='larger_text'>{$name}</span></td>
			<!--<td>{$field['field_key']}</td>-->
			<td>{$_type}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_required}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_useredit}' alt='{$this->lang->words['icon']}' /></td>
			<td>		
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;do=edit&amp;id={$database['database_id']}&amp;field={$field['field_id']}'>{$this->lang->words['edit_field_menu']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;do=delete&amp;id={$database['database_id']}&amp;field={$field['field_id']}' );">{$this->lang->words['delete_field_menu']}</a>
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

<script type='text/javascript'>
	jQ("#fields_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

HTML;
//--endhtml--//
return $IPBHTML;
}

}