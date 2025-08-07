<?php
/**
 * <pre>
 * Invision Power Services
 * Blocks skin file
 * Last Updated: $Date: 2012-02-01 10:55:10 -0500 (Wed, 01 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10227 $
 */
 
class cp_skin_blocks
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
 * Show the block tags helper
 *
 * @access	public
 * @param	array 		Tags
 * @return	string		HTML
 */
public function inlineBlockTags( $tags )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='templateTags'>
	<h4>
		<div class='right'>
			<span id='close-tags-link' title='{$this->lang->words['template_tag_help_close']}'>&times;</span>
		</div>
		{$this->lang->words['block_tag_header']}
	</h4>
	<div class='ipsTabBar' id='tag_tabbar'>
		<ul id='tags_tabs'>
			<li id='tab_templates' class='active'>{$this->lang->words['block_tag_header']}</li>
			<li id='tab_media'>{$this->lang->words['tags_media']}</li>
		</ul>
	</div>
	<div id='templateTags_inner'>
		<div id='tab_media_pane' class='tag_pane' style='display: none'>
				
		</div>
		<div id='tab_templates_pane' class='tag_pane'>
HTML;

$IPBHTML .= $this->inlineBlockTagsContent( $tags );

$IPBHTML .= <<<HTML
		</div>
	</div>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the block tags helper
 *
 * @access	public
 * @param	array 		Tags
 * @param	string		Message to show
 * @return	string		HTML
 */
public function inlineBlockTagsContent( $tags, $message='' )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
			<ul>
HTML;

if( is_array($tags) AND count($tags) )
{
	foreach( $tags as $category => $_tags )
	{
		$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$category}
			</li>
HTML;
		if( is_array($_tags) AND count($_tags) )
		{
			foreach( $_tags as $_tag )
			{
				if( isset($_tag[2]) AND is_array($_tag[2]) )
				{
					$_key	= md5( uniqid( microtime(),true ) );
					
					$IPBHTML .= <<<HTML
						<li class='tag_row expandable closed ipsControlRow' id='{$_key}'>
							<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
							<h5>{$_tag[0]}</h5>
							<div id='{$_key}_desc' class='tag_help always_open'>{$_tag[1]}</div>
							<ul id='{$_key}_children' style='display:none;' class='child-description'>
HTML;
					foreach( $_tag[2] as $_itag )
					{
						$_dataTag	= '{' . $_itag[0] . '}';
						
						$IPBHTML .= <<<HTML
								<li class='tag_row ipsControlRow' data-tag="{$_dataTag}">
									<ul class='ipsControlStrip'>
										<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
									</ul>
									<h5>{$_itag[0]}</h5>
									<div class='tag_help'>{$_itag[1]}</div>
								</li>
HTML;
					}
					
					$IPBHTML .= <<<HTML
							</ul>
						</li>
HTML;
				}
				else
				{
					$_dataTag	= '{' . $_tag[0] . '}';
					
					$IPBHTML .= <<<HTML
						<li class='tag_row ipsControlRow' data-tag="{$_dataTag}">
							<ul class='ipsControlStrip'>
								<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
							</ul>
							<h5>{$_tag[0]}</h5>
							<div class='tag_help'>{$_tag[1]}</div>
						</li>
HTML;
				}
			}
		}
	}
}
else if( $this->request['module'] == 'templates' AND $this->request['section'] == 'blocks' )
{
	$text	= $this->request['template'] ? $this->lang->words['define_block_tags_load'] : $this->lang->words['define_block_tags_select'];

	$IPBHTML .= <<<HTML
		<li class='tag_row ipsControlRow'>
			<h5>{$text}</h5>
		</li>
HTML;
}
else if( $message )
{
	$IPBHTML .= <<<HTML
		<li class='tag_row ipsControlRow'>
			<h5>{$message}</h5>
		</li>
HTML;
}

$IPBHTML .= <<<HTML
			</ul>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show AJAX add category form
 *
 * @access	public
 * @param	string		Add or edit
 * @param	array 		Block data for edit
 * @return	string		HTML
 */
public function ajaxCategoryForm()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=doAddCategory' method='post' id='adform' name='adform'>
<div>
	<h3 class='ipsBlock_title'>{$this->lang->words['specify_cat_details']}</h3>
	<div style='padding: 10px'>
		<strong>{$this->lang->words['block_cat__title']}:</strong>
		&nbsp;&nbsp;<input type='text' class='textinput no_width' name='category_title' />
		&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show category form
 *
 * @access	public
 * @param	string		Add or edit
 * @param	array 		Block data for edit
 * @return	string		HTML
 */
public function categoryForm( $type, $category=array() )
{
$IPBHTML = "";
//--starthtml--//

$title	= $type == 'add' ? $this->lang->words['add_block_cat__title'] : $this->lang->words['edit_block_cat__title'] . ' ' . $category['container_name'];
$do 	= $type == 'add' ? 'doAddCategory' : 'doEditCategory';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do={$do}' method='post' id='adform' name='adform'>
	<input type='hidden' name='id' value='{$category['container_id']}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['specify_cat_details']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['block_cat__title']}</strong></td>
				<td class='field_field'><input type='text' class='textinput' name='category_title' value='{$category['container_name']}' /></td>
			</tr>
		</table>
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

/**
 * Draw iframe for block preview
 *
 * @access	public
 * @param	array 		Block data
 * @return	string		HTML
 */
public function blockPreview( $block )
{
$IPBHTML = "";
//--starthtml--//

$_key		= md5( $block['block_id'] . $block['block_key'] );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['block_preview_header']} {$block['block_name']}</h3>
	<iframe src='{$this->settings['board_url']}/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;do=blockPreview&amp;id={$block['block_id']}&amp;k={$_key}' class='blockPreview'></iframe>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the listing
 *
 * @access	public
 * @param	array		Current blocks
 * @param	array 		Categories
 * @param	array 		Exportable blocks
 * @return	string		HTML
 */
public function listBlocks( $blocks=array(), $categories=array(), $exportable=array() )
{
$IPBHTML = "";
//--starthtml--//

$_externalExists	= is_file( DOC_IPS_ROOT_PATH . 'external.php' );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript'>
HTML;

if( $_externalExists )
{
	$IPBHTML .= <<<HTML
	ipb.templates['block_widget']	= new Template("<div class='acp-box block-widget-code'><h3>{$this->lang->words['block_widget_header']}</h3><div class='information-box'>{$this->lang->words['block_widget_code_desc']}</div><div class='widget-code'>&lt;script type=&#39;text/javascript&#39; src=&#39;{$this->settings['board_url']}/external.php?id=#{blockid}&amp;amp;k=#{blockkey}<span class='widget-method'></span>&#39; id=&#39;block-#{blockkey}&#39;&gt;&lt;/script&gt;</div><br /><div class='information-box'>{$this->lang->words['block_internal_widget_code']}</div><div class='widget-code'>{parse block=&quot;#{realblockkey}&quot;}</div></div>");
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	ipb.templates['block_widget']	= new Template("<div class='acp-box block-widget-code'><h3>{$this->lang->words['block_widget_header']}</h3><div class='information-box'>{$this->lang->words['block_internal_widget_code']}</div><div class='widget-code'>{parse block=&quot;#{realblockkey}&quot;}</div></div>");
HTML;
}
	
$IPBHTML .= <<<HTML
</script>
<div class='section_title'>
	<h2>{$this->lang->words['blocks_h2_header']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['blocks_helpblurb']}
		</div>
	</div>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=blocks&amp;section=wizard' title='{$this->lang->words['add_block']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_block']}' />
					{$this->lang->words['add_block']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=addCategory' title='{$this->lang->words['add_block_category']}' id='block-add-category'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
					{$this->lang->words['add_block_category']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='#' title='{$this->lang->words['export_block']}' class='ipbmenu' id="menu_export_block">
					<img src='{$this->settings['skin_acp_url']}/images/icons/plugin.png' alt='{$this->lang->words['export_block']}' />
					{$this->lang->words['export_block']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=recacheAll' title='{$this->lang->words['recache_all_block']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='{$this->lang->words['recache_all_block']}' />
					{$this->lang->words['recache_all_block']}
				</a>
			</li>
		</ul>
	</div>
</div>
<!--Content has to be outside section_title div or it gets styled differently-->
<ul class='acp-menu' id='menu_export_block_menucontent'>
HTML;

if( IN_DEV )
{
	foreach( $exportable as $block )
	{
		$IPBHTML .= "<li class='icon export'><a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=export&amp;block={$block['key']}'>{$block['name']}</a></li>";
	}
	
	$IPBHTML .= "<li class='icon manage' style='border-top: 3px solid #e1e1e1;'><a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=export&amp;block=_all_'><span style='font-weight: bold;'>{$this->lang->words['export_templates_release']}</span></a></li>";
	$IPBHTML .= "<li class='icon manage'><a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=import&amp;dev=1'><span style='font-weight: bold;'>{$this->lang->words['import_templates_release']}</span></a></li>";
}

$IPBHTML .= <<<HTML
</ul>
HTML;

if( !$_externalExists )
{
	$IPBHTML	.= <<<HTML
<div class='information-box'>
	{$this->lang->words['external_upload_for_widgets']}
</div>
<br />
HTML;
}

$IPBHTML	.= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['blocks']}</h3>
	<div id='category-containers'>
HTML;

	$types	= array(
				'plugin'	=> $this->lang->words['block_type__plugin'],
				'feed'		=> $this->lang->words['block_type__feed'],
				'custom'	=> $this->lang->words['block_type__custom']
				);

	$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_blocks_header'], 'noEdit' => true );

	if( is_array($categories) AND count($categories) )
	{
		$dragNDrop	= array();

		foreach ( $categories as $category )
		{
			$_class			= "isDraggable";
			
			//-----------------------------------------
			// Don't show "other blocks" category if it's
			// empty, but we have blocks in other cats
			//-----------------------------------------
			
			if( $category['container_id'] == 0 AND !(is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ])) AND count($blocks) )
			{
				continue;
			}
			else if( $category['container_id'] == 0 )
			{
				$_class		= '';
			}
			
			$dragNDrop[]	= "sortable_handle_{$category['container_id']}";

			$IPBHTML .= <<<HTML
			<div class='{$_class}' id='container_{$category['container_id']}'>
				<table class='ipsTable no_background'>
					<tr class='ipsControlRow isDraggable'>
HTML;
					if ( $category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
						<th class='subhead' width='4%'>&nbsp;</th>
HTML;
					}
					else
					{
						$IPBHTML .= <<<HTML
						<th class='subhead col_drag' width='4%'><span class='draghandle'>&nbsp;</span></th>
HTML;
					}
						$IPBHTML .= <<<HTML
						<th class='subhead' style='width: 78%'><span class='larger_text'>{$category['container_name']}</span></th>
						<th class='subhead' style='width: 10%'>&nbsp;</th>
						<th class='subhead col_buttons' style='width: 8%'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=editCategory&amp;id={$category['container_id']}'>{$this->lang->words['edit_block_category']}</a>
								</li>
								<li class='i_delete'>
									<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=deleteCategory&amp;id={$category['container_id']}' );">{$this->lang->words['delete_block_category']}</a>
								</li>
							</ul>
						</th>
					</tr>
				</table>
				<ul id='sortable_handle_{$category['container_id']}' class='alternate_rows'>
				
HTML;
	
				if( is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ]) )
				{
					foreach( $blocks[ $category['container_id'] ] as $block )
					{
						$_key		= md5( $block['block_id'] . $block['block_key'] );
						$revisions	= sprintf( $this->lang->words['page_managerevisions'], $block['revisions'] );
						
						$IPBHTML .= <<<HTML
					<li class='record' id='record_{$block['block_id']}'>
						<table class='ipsTable no_background'>
							<tr class='ipsControlRow isDraggable'>
								<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
								<td style='width: 78%'>
									<a href='#' class='block-preview-link' id='{$block['block_id']}-blockPreview' title='{$this->lang->words['block_preview_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['block_preview_alt']}' /></a> 
									<a href='#' class='block-widget-link' id='{$block['block_id']}-blockWidget' rel='{$_key}' krel='{$block['block_key']}' title='{$this->lang->words['block_widget_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/page_white_code.png' alt='{$this->lang->words['block_widget_alt']}' /></a> 
									&nbsp;<span class='larger_text'><a href='{$this->settings['base_url']}module=blocks&amp;section=wizard&amp;do=editBlockTemplate&amp;block={$block['block_id']}'>{$block['block_name']}</a></span>
									<div class='desctext clear'>{$block['block_description']}</div>
								</td>
								<td style='width: 10%'>
									<div class='info' style='max-width: 50px'>
										<div class='block {$block['block_type']}'>{$types[ $block['block_type'] ]}</div>
									</div>
								</td>
								<td class='col_buttons' style='width: 8%'>
									<ul class='ipsControlStrip'>
										<li class='i_edit'>
											<a href='{$this->settings['base_url']}module=blocks&amp;section=wizard&amp;do=editBlockTemplate&amp;block={$block['block_id']}'>{$this->lang->words['edit_block']}</a>
										</li>
										<li class='i_delete'>
											<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=delete&amp;block={$block['block_id']}' );">{$this->lang->words['delete_block']}</a>
										</li>
										<li class='ipsControlStrip_more ipbmenu' id='menu_{$block['block_id']}'>
											<a href='#'>...</a>
										</li>
									</ul>
									<ul class='acp-menu' id='menu_{$block['block_id']}_menucontent'>
										<li class='icon export'><a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=exportBlock&amp;block={$block['block_id']}'>{$this->lang->words['export_single_block']}</a></li>
										<li class='icon refresh'><a href='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=recache&amp;block={$block['block_id']}'>{$this->lang->words['recache_block']}</a></li>
										<li class='icon view'><a href='{$this->settings['base_url']}module=blocks&amp;section=revisions&amp;type=block&amp;id={$block['block_id']}'>{$revisions}</a></li>
									</ul>
								</td>
							</tr>
						</table>
					</li>
HTML;
					}
				}
				else
				{
					$IPBHTML .= <<<HTML
					<li id='record_00{$category['container_id']}'>
						<table class='ipsTable no_background'>
							<tr>
								<td style='width: 4%'>&nbsp;</td>
								<td colspan='3' class='no_messages'>{$this->lang->words['no_blocks_yet']}</td>
							</tr>
						</table>
					</li>
HTML;
				}
				
				$IPBHTML .= <<<HTML
				</ul>
			</div>		
HTML;
		}
		$IPBHTML .= <<<HTML
	</div>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
	<table class='ipsTable'>
		<tr>
			<td class='no_messages'>
				{$this->lang->words['no_blocks_yet']}
			</td>
		</tr>
	</table>
HTML;
	}
	
	$_dragNDrop_Categories	= implode( "', '", $dragNDrop );
	
	$IPBHTML .= <<<HTML
</div>

<script type='text/javascript'>
	acp.ccs.initBlockCategorization( new Array('{$_dragNDrop_Categories}') );
	ipb.templates['cat_empty']		= new Template("<li id='record_00#{category}'><table class='ipsTable no_background'><tr><td style='width: 4%'>&nbsp;</td><td colspan='3' class='no_messages'>{$this->lang->words['no_blocks_yet']}</td></tr></table></li>");
</script>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=import' method='post' enctype='multipart/form-data'>
	<div class="acp-box">
		<h3>{$this->lang->words['import_new_block']}</h3>
		<table class="ipsTable double_pad">
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['upload_block_xml']}</strong></td>
				<td class='field_field'>
					<input type='file' name='FILE_UPLOAD' /><br />
					<span class='desctext'>{$this->lang->words['upload_block_desc']}</span>
				</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['block_install']}' class="button primary" />
		</div>
	</div>
</form>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 1
 *
 * @access	public
 * @param	string		Session ID
 * @param	array 		Block types
 * @return	string		HTML
 */
public function wizard_step_1( $sessionId, $_blockTypes )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['block_wizard']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='1' />
<div class='acp-box'>
	<h3>{$this->lang->words['block_create_header']}</h3>
	<table class='ipsTable double_pad'>
HTML;

	foreach( $_blockTypes as $type )
	{
		$IPBHTML .= "
		<tr onclick=\"$('block_type_{$type[0]}').checked = 'checked';\">
		<td class='field_title'>
			<strong class='title'>{$type[1]}</strong>
		</td>
		<td class='field_field'>
			<input type='radio' name='type' value='{$type[0]}' id='block_type_{$type[0]}' />
			<span class='desctext'><strong>{$this->lang->words['block__types_' . $type[0] . '_basic']}</strong>
			<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$this->lang->words['block__types_' . $type[0] . '_full']}</span>
		</td>
		</tr>";
	}
	
	$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__continue']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for block template preview
 *
 * @access	public
 * @return	string		HTML
 */
public function inline_preview_wrapper( $content )
{
$IPBHTML = "";
$IPBHTML .= <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset={$this->settings['gb_char_set']}" />
		<title>Preview</title>
		<style type='text/css'>
			a {
				text-decoration: none;
				color: #285587;
			}
		</style>
	</head>
	<body>
		{$content}
	</body>
</html>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Get save and save/reload buttons
 *
 * @access	public
 * @return	string		HTML
 */
public function wizard_savebuttons()
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<input type='submit' value='{$this->lang->words['button__save']} ' class="button primary" name='save_button' /> <input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']}' class="button primary" />
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard step bar
 *
 * @access	public
 * @param	array		Steps (each entry should be step number => step title)
 * @param	int 		Current step number
 * @param	string		Wizard session id
 * @param	bool		Add block process (instead of editing)
 * @param	array 		Array of steps to block/grey out
 * @return	string		HTML
 */
public function wizard_stepbar( $steps, $currentStep, $sessionId, $isAddBlock=false, $empty=array() )
{
$IPBHTML = "";
//--starthtml--//

if( !count($steps) OR !is_array($steps) )
{
	return '';
}

$_step = 0;
$_link		= "do=continue&amp;wizard_session=" . $sessionId . '&amp;_jump=1&amp;step=';
if( strlen( $sessionId ) != 32 )
{
	$_link		= "do=editBlock&amp;block=" . $sessionId . "&amp;_jumpstep=";
}

$IPBHTML .= <<<HTML

	<div class='ipsSteps clearfix'>
		<ul>
HTML;
	foreach ( $steps as $number => $step )
	{
		$_step++;
		$class = ( $number == $currentStep ) ? 'steps_active' : '';
		$_unlinked	= false;
		$js = '';
		
		if( $isAddBlock )
		{
			if( $number >= $currentStep )
			{
				$_unlinked	= true;
			}
		}
		
		if( $number == $currentStep )
		{
			$_unlinked	= true;
		}
	
		if( is_array($empty) AND in_array( $number, $empty ) )
		{
			$class		= 'steps_disabled';
			$_unlinked	= true;
		}
		
		if ( !$_unlinked )
		{
			$class .= ' clickable';
			$js = "onclick='window.location = \"{$this->settings['base_url']}module=blocks&amp;section=wizard&amp;{$_link}{$number}\";'";
		}
	
		$IPBHTML .= <<<HTML
			<li class='{$class}' {$js}>
				<strong class='steps_title'>{$this->registry->getClass('class_localization')->words['step_single']} {$_step}</strong>
				<span class='steps_desc'>{$step}</span>
				<span class='steps_arrow'>&nbsp;</span>
			</li>
HTML;
	}
		$IPBHTML .= <<<HTML
		</ul>
	</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

}