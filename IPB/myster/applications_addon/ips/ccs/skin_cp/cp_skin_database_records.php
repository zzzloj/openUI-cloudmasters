<?php
/**
 * <pre>
 * Invision Power Services
 * Database records skin file
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
 
class cp_skin_database_records
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
 * Add or edit a record
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Fields
 * @param	array 		Record data
 * @param	object		Field abstraction class
 * @param	string 		Categories
 * @param	bool		This is a revision
 * @return	string		HTML
 */
public function recordForm( $type, $database, $fields, $record, $fieldClass, $categories='', $revision=false )
{
$IPBHTML = "";
//--starthtml--//
$text			= $revision ? $this->lang->words['editing_revision_title'] : ( $type == 'edit' ? $this->lang->words['editing_record_title'] . ' ' . $record[ $database['database_field_title'] ] : $this->lang->words['adding_record_title'] );
$action			= $revision ? 'doEditRevision' : ( $type == 'edit' ? 'doEdit' : 'doAdd' );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>
<link rel='stylesheet' href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />

HTML;

if( $this->request['preview'] )
{
	$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['preview_record_details']}</h3>
	<table class='ipsTable double_pad'>
HTML;

foreach( $fields as $field )
{
	$_field	= $fieldClass->getFieldValuePreview( $field, $record );
	
	$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$field['field_name']}</strong></td>
			<td class='field_field'>{$_field}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}&amp;id={$database['database_id']}' method='post' id='adminform' name='adform' enctype='multipart/form-data'>
	<input type='hidden' name='record' value='{$record['primary_id_field']}' />
	<input type='hidden' name='post_key' value='{$record['post_key']}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['edit_record_details']}</h3>
		<table class='ipsTable double_pad'>
HTML;
	
	if( $categories )
	{
		$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['record_form_cat']}</strong></td>
				<td class='field_field'><select name='category_id' id='category_id'>{$categories}</select></td>
			</tr>
HTML;
	}
	
	if( $record['_tagBox'] )
	{
		$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['record_form_tags']}</strong></td>
				<td class='field_field'>{$record['_tagBox']}</td>
			</tr>
HTML;
	}
	
	$furl		= $this->registry->output->formInput( 'record_static_furl', $record['record_static_furl'] );
	$metak		= $this->registry->output->formTextarea( 'record_meta_keywords', $record['record_meta_keywords'], 40, 5, '', '', 'normal' );
	$metad		= $this->registry->output->formTextarea( 'record_meta_description', $record['record_meta_description'], 40, 5, '', '', 'normal' );
	
	foreach( $fields as $field )
	{
		$_default	= $record['field_' . $field['field_id'] ];
		
		if( isset($_POST['field_' . $field['field_id'] ]) )
		{
			if( is_array($_POST['field_' . $field['field_id'] ]) )
			{
				$_default	= ',' . implode( ',', $_POST['field_' . $field['field_id'] ] ) . ',';
			}
			else
			{
				$_default	= $field['field_type'] == 'input' ? IPSText::textToForm( $_POST['field_' . $field['field_id'] ] ) : $_POST['field_' . $field['field_id'] ];
			}
		}
	
		$_field	= $fieldClass->getAcpField( $field, $_default );
		$_desc	= $field['field_description'] ? " <div class='desctext'>{$field['field_description']}</div>" : '';
		
		$_furl	= '';
		
		if( 'field_' . $field['field_id'] == $database['database_field_title'] )
		{
			$_furl	= <<<HTML
			<div class='field_subfield'><div class='field_subfield_inner' id='furl_wrap'>{$this->lang->words['record_form_furl']}: {$furl} <a href='#' class='action_link' id='cancel_furl_wrap'>{$this->lang->words['furltitlejs_cancel']}</a></div></div>
HTML;
		}
		
		$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$field['field_name']}</strong></td>
				<td class='field_field'>
					{$_field}
					{$_desc}
					{$_furl}
				</td>
			</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_keywords']}</strong></td>
				<td class='field_field'>
					{$metak}<br />
					<span class='desctext'>{$this->lang->words['meta_tags__override']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_description']}</strong></td>
				<td class='field_field'>
					{$metad}<br />
					<span class='desctext'>{$this->lang->words['meta_tags__override']}</span>
				</td>
			</tr>
		
HTML;
	
	$IPBHTML .= <<<HTML
		</table>
	
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
			<input type='submit' name='save_and_reload' value='{$this->lang->words['button__reload']}' class="button primary" />
HTML;
	
	if( $type == 'add' )
	{
		$IPBHTML .= <<<HTML
			<input type='submit' name='save_and_another' value='{$this->lang->words['button__saveplus']}' class="button primary" />
HTML;
	}
	
	$IPBHTML .= <<<HTML
			<input type='submit' name='preview' value='{$this->lang->words['button__preview']}' class="button primary" />
			<input type='button' class='button redbutton' onclick="history.go(-1);" value='{$this->lang->words['button__cancel']}' />
		</div>
	</div>	
</form>
<script type='text/javascript'>
	var obj = new acp.liveedit( 
					$('{$database['database_field_title']}'),
					$('record_static_furl'),
					{
						template:	new Template( "<div id='#{id}' class='field_subfield_wrap'>{$this->lang->words['furl_url_ttitle']} <span id='#{displayid}'><em>{$this->lang->words['changejsinit']}</em></span> &nbsp;&nbsp;<a href='#' class='action_link' id='#{changeid}'>{$this->lang->words['changejstitle']}</a> &nbsp;&nbsp;<a href='#' class='action_link' id='furl_help'>{$this->lang->words['changejshelp']}</a></div>" ),
						wrapper:	'furl_wrap',
						isFurl:		true
						//url: "{$this->settings['base_url']}app=ccs&module=ajax&section=databases&do=checkCategoryKey&database={$database['database_id']}"
					}
			  );
</script>
<div id='furl_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['furl_help_title']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['record_form_furl_desc']}
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of records within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Records
 * @param	string		Pages
 * @param	object		Field abstraction class
 * @param	string		Query string for page links
 * @param	string		Category filter HTML (if categories defined)
 * @param	int			Number of queued comments
 * @return	string		HTML
 */
public function records( $database, $fields, $records, $pages, $fieldClass, $queryString, $categoryFilter='', $qc=0 )
{
$IPBHTML = "";
//--starthtml--//

$_fieldSt	= intval($this->request['field_st']) ? intval($this->request['field_st']) : 0;
$_maxFields	= count($fields);
$_limit		= 5;
$_skipped	= 0;
$_drawn		= 0;
$_previous	= '';
$_next		= '&nbsp;';

if( $_fieldSt > 0 )
{
	$_prevCnt	= $_fieldSt - $_limit;
	$_previous	= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;st={$this->request['st']}&amp;field_st={$_prevCnt}{$queryString}' title='{$this->lang->words['view_prev_rec_fields']}'>{$this->lang->words['_laquo']}</a> ";
}


if( $qc )
{
	$this->registry->output->setMessage( sprintf( $this->lang->words['queued_comm_link'], $this->settings['base_url'] . $this->form_code, $qc ), true );
}

//-----------------------------------------
// Sort options
//-----------------------------------------

$_sort	= array(
				'primary_id_field'	=> $this->request['sort_col'] == 'primary_id_field' ? " selected='selected'" : '',
				'member_id'			=> $this->request['sort_col'] == 'member_id' ? " selected='selected'" : '',
				'record_saved'		=> $this->request['sort_col'] == 'record_saved' ? " selected='selected'" : '',
				'record_updated'	=> $this->request['sort_col'] == 'record_updated' ? " selected='selected'" : '',
				'rating_real'		=> $this->request['sort_col'] == 'rating_real' ? " selected='selected'" : '',
				'record_views'		=> $this->request['sort_col'] == 'record_views' ? " selected='selected'" : '',
				'record_approved'	=> $this->request['sort_col'] == 'record_approved' ? " selected='selected'" : '',
				'record_comments'	=> $this->request['sort_col'] == 'record_comments' ? " selected='selected'" : '',
				'record_locked'		=> $this->request['sort_col'] == 'record_locked' ? " selected='selected'" : '',
				);

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbrecords_title']} {$database['database_name']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=add&amp;id={$database['database_id']}' title='{$this->lang->words['add_record_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_record_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_fields']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/layout_content.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_fields']}
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

<form action='{$this->settings['base_url']}{$this->form_code}&amp;filter=1' method='post'>
<div class='filter_box'>
	<div>
	<div class='right'><input value="{$this->lang->words['dbrecord_resort']}" class="button primary" accesskey="s" type="submit" /></div>
	<strong>{$this->lang->words['db_records__search']}</strong>
	<input type='text' name='search_value' length='12' value='{$this->request['search_value']}' />
HTML;

if( $categoryFilter )
{
	$IPBHTML .= <<<HTML
	{$this->lang->words['db_records__catfilter']}
	<select name='category' id='category'>
		<option value='0'>{$this->lang->words['db_records__cf_all']}</option>
		{$categoryFilter}
	</select>
HTML;
}

$IPBHTML .= <<<HTML
	{$this->lang->words['db_records__sortby']} 
	<select name='sort_col'>
		<option value='primary_id_field'{$_sort['primary_id_field']}>{$this->lang->words['field__id']}</option>
		<option value='member_id'{$_sort['member_id']}>{$this->lang->words['field__member']}</option>
		<option value='record_saved'{$_sort['record_saved']}>{$this->lang->words['field__saved']}</option>
		<option value='record_updated'{$_sort['record_updated']}>{$this->lang->words['field__updated']}</option>
		<option value='rating_real'{$_sort['rating_real']}>{$this->lang->words['field__rating']}</option>
		<option value='record_views'{$_sort['record_views']}>{$this->lang->words['field__views']}</option>
		<option value='record_approved'{$_sort['record_approved']}>{$this->lang->words['field__approved']}</option>
		<option value='record_comments'{$_sort['record_comments']}>{$this->lang->words['field__comments']}</option>
		<option value='record_locked'{$_sort['record_locked']}>{$this->lang->words['field__locked']}</option>
				
HTML;

foreach( $fields as $field )
{
	if( !in_array( $field['field_type'], array( 'input', 'textarea', 'radio', 'select', 'editor' ) ) )
	{
		continue;
	}
	
	$selected	= '';
	
	if( $this->request['sort_col'] == 'field_' . $field['field_id'] )
	{
		$selected	= " selected='selected'";
	}

	$IPBHTML .= <<<HTML
				<option value='field_{$field['field_id']}'{$selected}>{$field['field_name']}</option>
HTML;
}

$_asc	= $this->request['sort_order'] == 'asc' ? " selected='selected'" : '';
$_desc	= $this->request['sort_order'] == 'desc' ? " selected='selected'" : '';

$_opt10	= $this->request['per_page'] == 10 ? " selected='selected'" : '';
$_opt25	= $this->request['per_page'] == 25 ? " selected='selected'" : '';
$_opt50	= $this->request['per_page'] == 50 ? " selected='selected'" : '';
$_opt75	= $this->request['per_page'] == 75 ? " selected='selected'" : '';
$_opthu	= $this->request['per_page'] == 100 ? " selected='selected'" : '';

$IPBHTML .= <<<HTML
		
	</select> {$this->lang->words['db_records__in']}
	<select name='sort_order'>
		<option value='asc'{$_asc}>{$this->lang->words['db_records__asc']}</option>
		<option value='desc'{$_desc}>{$this->lang->words['db_records__desc']}</option>
	</select> {$this->lang->words['db_records__order']}
	<select name='per_page'>
		<option value='10'{$_opt10}>10</option>
		<option value='25'{$_opt25}>25</option>
		<option value='50'{$_opt50}>50</option>
		<option value='75'{$_opt75}>75</option>
		<option value='100'{$_opthu}>100</option>
	</select> {$this->lang->words['db_records__results']}
	</div>
</div>
</form>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['all_created_records_t']}</h3>
	<table class='form_table'>
		<tr>
			<th>{$_previous}{$this->lang->words['th__record_id']}</th>
HTML;

foreach( $fields as $field )
{
	if( $_fieldSt > 0 AND $_skipped < $_fieldSt )
	{
		$_skipped++;
		continue;
	}
	
	if( $_drawn >= $_limit )
	{
		break;
	}
	
	$_drawn++;

	$IPBHTML .= <<<HTML
			<th>{$field['field_name']}</th>
HTML;
}

if( $_drawn + $_fieldSt < $_maxFields )
{
	$_nextCnt	= $_fieldSt + $_limit;
	$_next		= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;st={$this->request['st']}&amp;field_st={$_nextCnt}{$queryString}' title='{$this->lang->words['view_more_rec_fields']}'>{$this->lang->words['_raquo']}</a> ";
}

$IPBHTML .= <<<HTML
			<th class='col_buttons'>{$_next}</th>
		</tr>
HTML;

if ( ! count( $records ) )
{
	$colspan	= 2 + count($fields);
	
	$IPBHTML .= <<<HTML
	<tr>
		<td colspan='{$colspan}' class='no_messages'>
HTML;

if( $this->request['filter'] )
{
	$IPBHTML .= <<<HTML
	{$this->lang->words['no_records_found']}
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	{$this->lang->words['no_records_created_yet']} <a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=add&amp;id={$database['database_id']}' class='mini_button'>{$this->lang->words['create_record_now']}</a>
HTML;
}

	$IPBHTML .= <<<HTML
		</td>
	</tr>
HTML;
}
else
{
	foreach( $records as $record )
	{
		$_cssClass		= $record['record_approved'] == 1 ? ( $_cssClass == 'row1' ? 'row2' : 'row1' ) : '_red';
	
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow {$_cssClass}'>
			<td><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=edit&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}' title='{$this->lang->words['edit_record_menu']}'>{$record['primary_id_field']}</a></td>
HTML;

		$_skipped	= 0;
		$_drawn		= 0;
		
		foreach( $fields as $field )
		{
			if( $_fieldSt > 0 AND $_skipped < $_fieldSt )
			{
				$_skipped++;
				continue;
			}
			
			if( $_drawn >= $_limit )
			{
				break;
			}
			
			$_drawn++;
	
			$_display	= $fieldClass->getFieldValue( $field, $record, 100 );
			
			$_pinned	= '';
			
			if( $_drawn == 1 )
			{
				if( $record['record_pinned'] )
				{
					$_pinned	= $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->lang->words['pre_pinned'] );
				}

				if( !empty($record['tags']['formatted']['prefix']) )
				{
					$_pinned	.= $record['tags']['formatted']['prefix'] . ' ';
				}
				
				if( isset($record['tags']) AND $record['tags'] )
				{
					$_pinned	= "<div class='right'><img src='{$this->settings['img_url']}/icon_tag.png' /> <span class='desc lighter blend_links'>{$record['tags']['formatted']['truncatedWithLinks']}</span></div>" . $_pinned;
				}
				
				$_pinned	= preg_replace( '#<!--hook\.([^\>]+?)-->#', '', ipsRegistry::getClass('output')->templateHooks( $_pinned ) );
			}
			
			$IPBHTML .= <<<HTML
			<td>{$_pinned}{$_display}</td>
HTML;
		}
		
		$revisions	= '';
		$comments	= '';
		
		if( $database['database_revisions'] )
		{
			$revisions	= "<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=revisions&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>" . sprintf( $this->lang->words['record_revisions_menu'], $record['_revisions'] ) . "</a></li>";
		}
		
		if( trim( $database['perm_5'], ' ,' ) )
		{
			$comments	= "<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=comments&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>" . sprintf( $this->lang->words['record_comments_menu'], $record['record_comments'] ) . "</a></li>";
		}
		
		if( $record['record_locked'] )
		{
			$locked	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=lock&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['unlock_record__menu']}</a></li>";
		}
		else
		{
			$locked	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=lock&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['lock_record__menu']}</a></li>";
		}
		
		if( $record['record_pinned'] )
		{
			$pinned	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=pin&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['unpin_record__menu']}</a></li>";
		}
		else
		{
			$pinned	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=pin&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['pin_record__menu']}</a></li>";
		}
		
		if( $record['record_approved'] == 1 )
		{
			$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=approve&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['hide_record__menu']}</a></li>";
		}
		else if( $record['record_approved'] == -1 )
		{
			$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=approve&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['unhide_record__menu']}</a></li>";
		}
		else
		{
			$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=approve&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['app_record__menu']}</a></li>";
		}
		
		$IPBHTML .= <<<HTML
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=edit&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}'>{$this->lang->words['edit_record_menu']}</a>
					</li>
					<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$record['primary_id_field']}'>
						<a href='#'>{$this->lang->words['folder_options_alt']}</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu_folder{$record['primary_id_field']}_menucontent'>
					<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=delete&amp;id={$database['database_id']}&amp;record={$record['primary_id_field']}' );">{$this->lang->words['delete_record_menu']}</a></li>
					{$approved}
					{$locked}
					{$pinned}
					{$revisions}
					{$comments}
				</ul>
			</td>
		</tr>
HTML;
	}
}


				
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
{$pages}

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of revisions for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Record data
 * @param	array 		Revisions for this record
 * @return	string		HTML
 */
public function revisions( $database, $fields, $record, $revisions )
{
$IPBHTML = "";
//--starthtml--//

$this->lang->words['all_saved_revisions']	= sprintf( $this->lang->words['all_saved_revisions'], $record['title'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbrevisions_title']} {$record['title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;record={$this->request['record']}&amp;do=clearRevisions' );" title='{$this->lang->words['delete_all_revisions']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['delete_all_revisions']}</a></li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['all_saved_revisions']}</h3>
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['th__revision_date']}</th>
			<th>{$this->lang->words['th__revision_member']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( !count( $revisions ) )
{
	$IPBHTML .= <<<HTML
	<tr>
		<td colspan='3' class='no_messages'>
			{$this->lang->words['no_revisions_saved_yet']}
		</td>
	</tr>
HTML;
}
else
{
	foreach( $revisions as $revision )
	{
		$data	= unserialize($revision['revision_data']);
		$date	= $this->registry->class_localization->getDate( $revision['revision_date'], 'LONG' );
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>{$date}</td>
			<td><span class='larger_text'><a href='{$this->settings['board_url']}/index.php?showuser={$revision['member_id']}'>{$revision['members_display_name']}</a></span></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a class='edit' href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=editRevision&amp;id={$database['database_id']}&amp;revision={$revision['revision_id']}'>{$this->lang->words['edit_revision_menu']}</a>
					</li>
					<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$revision['revision_id']}'>
						<a href='#'>{$this->lang->words['folder_options_alt']}</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu_folder{$revision['revision_id']}_menucontent'>
					<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=deleteRevision&amp;id={$database['database_id']}&amp;revision={$revision['revision_id']}' );">{$this->lang->words['delete_revision_menu']}</a></li>
					<li class='icon manage'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=restoreRevision&amp;id={$database['database_id']}&amp;revision={$revision['revision_id']}', '{$this->lang->words['restore_are_you_sure']}' );">{$this->lang->words['restore_revision_menu']}</a></li>
					<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=compareRevision&amp;id={$database['database_id']}&amp;revision={$revision['revision_id']}'>{$this->lang->words['compare_revision_menu']}</a></li>
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

/**
 * Show the list of revisions for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Differences data
 * @return	string		HTML
 */
public function compareRevisions( $database, $fields, $differences )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbview_revision']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['revision_comparison_list']}</h3>
	<table class='ipsTable'>
HTML;

if( count( $fields ) )
{
	foreach( $fields as $_field )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$_field['field_name']}</strong></td>
			<td class='field_field'>{$differences[ $_field['field_id'] ]}</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div style='padding:4px;margin:4px;'>
	<span class='diffred'>{$this->lang->words['rev_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['rev_addedhtml']}</span>
</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of comments for a particular record (stored as replies to a topic)
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Record data
 * @param	array 		Comments
 * @param	string		Page links
 * @return	string		HTML
 */
public function topicComments( $database, $record, $comments, $pagelinks )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title']} {$record['title']}</h2>
	</div>
HTML;

$IPBHTML .= <<<HTML
<div>{$pagelinks}</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<link rel="stylesheet" href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />
<div class='acp-box clear'>
	<h3>{$this->lang->words['all_saved_comments']}</h3>
	<table class='form_table'>
HTML;

if( !count( $comments ) )
{
	$IPBHTML .= <<<HTML
	<tr>
		<td class='no_messages'>
			{$this->lang->words['no_comments_saved_yet']}
		</td>
	</tr>
HTML;
}
else
{
	foreach( $comments as $comment )
	{
		$date			= $this->registry->class_localization->getDate( $comment['post_date'], 'LONG' );
		$_commentText	= !$comment['queued'] ? $this->lang->words['unapp_comment_menu'] : ( $comment['queued'] == 2 ? $this->lang->words['unhide_comment_menu'] : $this->lang->words['app_comment_menu'] );
		$_cssClass		= !$comment['queued'] ? ( $_cssClass == 'row1' ? 'row2' : 'row1' ) : '_red';

		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow {$_cssClass}'>
			<td style='width: 170px; text-align: right;'>
				<img src='{$comment['pp_small_photo']}' width='{$comment['pp_small_width']}' height='{$comment['pp_small_height']}' class='photo' />
				<br />
				<span class='larger_text'>
HTML;

		if( $comment['member_id'] )
		{
			$IPBHTML .= <<<HTML
					<a href="{$this->settings['board_url']}/index.php?showuser={$comment['member_id']}">{$comment['members_display_name']}</a>
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
					{$comment['author_name']}
HTML;
		}

		$IPBHTML .= <<<HTML
				</span>
				<br />
				<br />
				<div class='posted_date desctext ipsType_smaller'>
					{$date}
					<br />
					{$this->lang->words['comment_ip_addy']} <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$comment['ip_address']}'>{$comment['ip_address']}</a>
				</div>
			</td>
			<td class='col_buttons' style='vertical-align:top;'>
				<div class='right'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=editComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}'>{$this->lang->words['edit_comment_menu']}</a>
						</li>
						<li class='i_cog'>
							<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=toggleComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}'>{$_commentText}</a>
						</li>
						<li class='i_delete'>
							<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=deleteComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}' );">{$this->lang->words['delete_comment_menu']}</a>
						</li>
					</ul>
				</div>
				<div id='comment_{$comment['comment_id']}' class='comment_content'>
					{$comment['post']}
				</div>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div>{$pagelinks}</div>

HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * Show the list of comments for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Record data
 * @param	array 		Comments
 * @param	string		Page links
 * @return	string		HTML
 */
public function comments( $database, $record, $comments, $pagelinks )
{
$IPBHTML = "";
//--starthtml--//

if( $this->request['filter'] == 'queued' )
{
	$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title_q']}</h2>
	</div>
HTML;

	$subtitle	= $this->lang->words['all_q_comm_f'];
}
else
{
	$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title']} {$record['title']}</h2>
	</div>
HTML;
	
	$subtitle	= $this->lang->words['all_saved_comments'];
}

$IPBHTML .= <<<HTML
<div>{$pagelinks}</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<link rel="stylesheet" href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />
<div class='acp-box clear'>
	<h3>{$subtitle}</h3>
	<table class='form_table'>
HTML;

if( !count( $comments ) )
{
	$_noneText	= $this->request['filter'] == 'queued' ? $this->lang->words['nomore_q_comments'] : $this->lang->words['no_comments_saved_yet'];
	
	$IPBHTML .= <<<HTML
	<tr>
		<td class='no_messages'>
			{$_noneText}
		</td>
	</tr>
HTML;
}
else
{
	foreach( $comments as $comment )
	{
		$date			= $this->registry->class_localization->getDate( $comment['comment_date'], 'LONG' );
		$_commentText	= ( $comment['comment_approved'] > 0 ) ? $this->lang->words['unapp_comment_menu'] : ( $comment['comment_approved'] == -1 ? $this->lang->words['unhide_comment_menu'] : $this->lang->words['app_comment_menu'] );
		$_cssClass		= ( $comment['comment_approved'] > 0 ) ? ( $_cssClass == 'row1' ? 'row2' : 'row1' ) : '_red';
		
		$_extraLink		= '';
		
		if( $this->request['filter'] == 'queued' )
		{
			$_extraLink	= "<div class='q-c-link'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;record={$comment['comment_record_id']}'>{$this->lang->words['qc_edit_record']}</a></div>";
		}
	
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow {$_cssClass}'>
			<td style='width: 170px; text-align: right;'>
				<img src='{$comment['pp_small_photo']}' width='{$comment['pp_small_width']}' height='{$comment['pp_small_height']}' class='photo' />
				<br />
				<span class='larger_text'>
HTML;

		if( $comment['member_id'] )
		{
			$IPBHTML .= <<<HTML
					<a href="{$this->settings['board_url']}/index.php?showuser={$comment['member_id']}">{$comment['members_display_name']}</a>
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
					{$comment['comment_author']}
HTML;
		}

		$IPBHTML .= <<<HTML
				</span>
				<br />
				<br />
				<div class='posted_date desctext ipsType_smaller'>
					{$date}
					<br />
					{$this->lang->words['comment_ip_addy']} <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$comment['comment_ip_address']}'>{$comment['comment_ip_address']}</a>
				</div>
			</td>
			<td class='col_buttons' style='vertical-align:top;'>
				<div class='right'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}'>{$this->lang->words['edit_comment_menu']}</a>
						</li>
						<li class='i_cog'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}'>{$_commentText}</a>
						</li>
						<li class='i_delete'>
							<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=deleteComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}' );">{$this->lang->words['delete_comment_menu']}</a>
						</li>
					</ul>
				</div>
				<div id='comment_{$comment['comment_id']}' class='comment_content'>
					{$comment['comment_post']}
				</div>
				{$_extraLink}
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div>{$pagelinks}</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Edit a comment
 *
 * @access	public
 * @param	array		Database data
 * @param	array 		Comment data
 * @param	string		Editor HTML
 * @return	string		HTML
 */
public function commentForm( $database, $comment, $editor )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editing_a_comment']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doEditComment' method='post' id='adminform' name='adform'>
<input type='hidden' name='filter' value='{$this->request['filter']}' />
<input type='hidden' name='comment_id' value='{$comment['comment_id']}' />
<input type='hidden' name='pid' value='{$comment['pid']}' />
<div class='acp-box'>
	{$editor}

	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="history.go(-1);" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}