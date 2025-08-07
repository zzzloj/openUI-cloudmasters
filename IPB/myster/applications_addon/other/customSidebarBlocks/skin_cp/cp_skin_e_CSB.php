<?php
/**
 * Custom Sidebar Blocks
  * 1.2.0
 */
 
class cp_skin_e_CSB extends output
{

/**
 * Prevent our main destructor being called by this class
 */
public function __destruct()
{
}
	
//*************************//
//($%^ Blocks^%$)//
//*************************//

/**
 * Block form
 */
public function blockForm( $block, $perm_matrix ) {

#init some vars
$form = array();

$form['csb_title']			= $this->registry->output->formInput( "csb_title", $block['csb_title'] );
$form['csb_image']			= $this->registry->output->formInput( "csb_image", $block['csb_image'] );
$form['csb_on']				= $this->registry->output->formYesNo( "csb_on", $block['csb_on'] );
$form['csb_hide_block']		= $this->registry->output->formYesNo( "csb_hide_block", $block['csb_hide_block'] );
$form['csb_use_perms']  	= $this->registry->output->formYesNo( "csb_use_perms", $block['csb_use_perms'] );
$form['csb_use_box']    	= $this->registry->output->formYesNo( "csb_use_box", $block['csb_use_box'] );
$form['csb_raw']    		= $this->registry->output->formYesNo( "csb_raw", $block['csb_raw'] );
$form['csb_php']    		= $this->registry->output->formYesNo( "csb_php", $block['csb_php'] );
$form['csb_no_collapse']	= $this->registry->output->formYesNo( "csb_no_collapse", $block['csb_no_collapse'] );

$value 	= $block['csb_content'];
$key	= "csb_content";
$autoSaveKey = $key.'-'. intval( $block['csb_id'] );

IPSText::getTextClass('bbcode')->parse_html			= 1;
IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
IPSText::getTextClass('bbcode')->parse_smilies  	= 1;
IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
IPSText::getTextClass('bbcode')->parsing_section	= 'global';

#format block contents
if (!$block['csb_raw'] && !$block['csb_php'])
{
	$value = IPSText::getTextClass('bbcode')->preEditParse( $value );
}
else
{
	$value = str_replace("&amp;", "&amp;amp;", $value );
}

if ($this->settings['e_CSB_use_plain_text_area'] || $block['csb_raw'] || $block['csb_php'])
{
	$form['csb_content'] = $this->registry->output->formTextarea( "csb_content", $value, 100, 20 );
}
else
{
	/* Load editor stuff */
	$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
	$editor = new $classToLoad();
	
	$editor->setContent( $value );
	$form['csb_content'] = $editor->show( 'csb_content', array( 'autoSaveKey' => $autoSaveKey, 'height' => 350 ) );
	
	
	//$form['csb_content'] = IPSText::getTextClass('editor')->showEditor( $value, $key );
}

$title 	= ( $block ) ? $this->lang->words['editing']." : ".$block['csb_title'] : $this->lang->words['adding_block'];
$button = ( $block ) ? $this->lang->words['edit_block'] : $this->lang->words['add_block'];
	
if ($block)
{
$mainWidth = "70%";
$sidebar =		"<div style='float: right; width: 30%;background:#849cb7' class='acp-sidebar'>
				{$this->preview_sidebar($block)} 
			</div>";
}
else
{
$mainWidth = "100%";
}

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

{$this->includeJS4ImagePopup()}
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=add_block&amp;secure_key={$secure_key}' method='post' id='adform' name='adform' onsubmit='return checkform();'>
<input type='hidden' name='csb_id' value='{$block['csb_id']}' />

<div class='acp-box'>
	<h3>{$this->lang->words['block_details']}</h3>
	<div class='ipsTabBar with_left with_right' id='tabstrip_manage_csb'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_1'>{$this->lang->words['general_config']}</li>
			<li id='tab_2'>{$this->lang->words['perms']}</li>
		</ul>
	</div>
	<div class='ipsTabBar_content' id='tabstrip_manage_csb_content'>
		<div id='tab_1_content'>
			<div style='float: left; width: {$mainWidth}'>
			<table class='ipsTable'>
				<tr>
					<th colspan='2'>{$this->lang->words['block_details']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['block_title']}</strong><br />{$this->lang->words['title_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_title']}
					</td>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['enabled']}</strong><br />{$this->lang->words['enabled_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_on']}
					</td>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['hide_from_main_block']}</strong><br />{$this->lang->words['hide_from_main_block_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_hide_block']}
					</td>
				</tr>				
				<tr>
					<th colspan='2'>{$this->lang->words['parsing_options']}</th>
				</tr>				
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['do_raw_html']}</strong><br />{$this->lang->words['do_raw_html_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_raw']}
					</td>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['php_based']}</strong><br />{$this->lang->words['php_based_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_php']}
					</td>
				</tr>				
				<tr>
					<th colspan='2'>{$this->lang->words['display_content']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['image_name']}</strong><br />{$this->lang->words['image_name_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_image']}
					</td>
				</tr>				
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['use_box']}</strong><br />{$this->lang->words['use_box_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_use_box']}
					</td>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['hide_collapse_ability']}</strong><br />{$this->lang->words['hide_collapse_ability_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_no_collapse']}
					</td>
				</tr>				
				<tr>
					<td colspan='2' style='width: 100%'>
						<strong class='title'>{$this->lang->words['enter_content_below']}</strong><br />{$this->lang->words['enter_content_below_exp']}
					</td>

				</tr>
				<tr>
					<td colspan='2' style='width: 100%'>
						{$form['csb_content']}
					</td>
				</tr>
				</table>
			</div>
			{$sidebar}
			<div style='clear: both;'></div>
		</div>
		<div id='tab_2_content'>
			<table class='ipsTable'>
				<tr>
					<td style='width: 40%'>
						<strong class='title'>{$this->lang->words['use_perms']}</strong><br />{$this->lang->words['use_perms_exp']}
					</td>
					<td style='width: 60%'>
						{$form['csb_use_perms']}
					</td>
				</tr>
				<tr>
					<td style='width: 100%' colspan='2'>
						{$perm_matrix}
					</td>
				</tr>				
			</table>
		</div>		
	</div>
	<div class='acp-actionbar'>
		<input type='submit' class='button' value='{$button}' />
		<input type='submit' name='reload' class='button' value='{$button} {$this->lang->words['and_reload']}' />
		<!-- NOT WORKING<input type='submit' name='reload' id='MF__newphoto' class='button' value='{$this->lang->words['preview']}' />-->
	</div>
	</form>
	
	<script type='text/javascript'> 
		$('MF__newphoto').observe('click', acp.members.newPhoto.bindAsEventListener( this, "app=customSidebarBlocks&amp;module=ajax&amp;section=preview&amp;do=show&amp;name=modules&amp;id=148" ) );
	</script> 
	 
</div>

<script type='text/javascript'>
jQ("#tabstrip_manage_csb").ipsTabBar({ tabWrap:
"#tabstrip_manage_csb_content" });
</script>
	
HTML;

//--endhtml--//

return $IPBHTML;
}

/**
 * Preview the custom block
 */
public function preview_sidebar( $block )
{

#format content
#eval PHP (added in 1.5.2+)
if (!$block['csb_raw'])
{
	$block['csb_content'] = (!$block['csb_php']) ? IPSText::getTextClass('bbcode')->preDisplayParse( $block['csb_content'] ) : eval($block['csb_content']);				
}

$IPBHTML = "";																	
			
$IPBHTML .= <<<EOF
			  <style type='text/css'>
				.ipsSideBlock {
					background: #F7FBFC;
					padding: 10px;
					margin-bottom: 10px;
				}	
				.ipsSideBlock h3 {
					font: normal 14px helvetica, arial, sans-serif;
					color: #204066;
					padding: 5px 10px;
					background: #DBE2EC;
					margin: -10px -10px 10px;
					max-height:17px;
					font-weight: normal;
					text-shadow: none;
					border-bottom: 0px;
					-moz-border-radius-topleft: 0px;
					-moz-border-radius-topright: 0px;
					border-top-left-radius: 0px;
					border-top-right-radius: 0px;
				}
				.ipsList_withminiphoto > li { margin-bottom: 8px; }
				.ipsList_withmediumphoto > li .list_content { margin-left: 60px; }
				.ipsList_withminiphoto > li .list_content { margin-left: 40px; }
				.ipsList_withtinyphoto > li .list_content { margin-left: 30px; }
				.ipsType_small { font-size: 12px; }
				.ipsType_smaller, .ipsType_smaller a { font-size: 11px !important; }
				.ipsUserPhoto {
					padding: 1px;
					border: 1px solid #d5d5d5;
					background: #fff;
					-webkit-box-shadow: 0px 2px 2px rgba(0,0,0,0.1);
					-moz-box-shadow: 0px 2px 2px rgba(0,0,0,0.1);
					box-shadow: 0px 2px 2px rgba(0,0,0,0.1);
				}
				.ipsUserPhotoLink:hover .ipsUserPhoto {
					border-color: #7d7d7d;
				}
				
				.ipsUserPhoto_variable { max-width: 155px; }
				.ipsUserPhoto_large { max-width: 90px; max-height: 90px; }
				.ipsUserPhoto_medium { width: 50px; height: 50px; }
				.ipsUserPhoto_mini { width: 30px; height: 30px; }
				.ipsUserPhoto_tiny { width: 20px; height: 20px;	}
				.ipsUserPhoto_icon { width: 16px; height: 16px;	}
			  </style>
			  
			<div style='border:1px solid #369;background:#FFF;max-width:275px; padding:10px; margin: 10px auto;'>
				<div style='max-width:271px;'>
					{$this->registry->getClass('output')->getTemplate('boards')->customSidebarBlock( $block )}
				 </div>
			</div> 
EOF;

return $IPBHTML;
}


/**
 * Delete image dhtml window
 */
public function includeJS4ImagePopup()
{

$IPBHTML = "";

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->

<!--this is needed for image popups-->

<script type="text/javascript" src="{$this->settings['js_main_url']}acp.members.js"></script> 
<ul class='ipbmenu_content' id='member_tasks_menucontent' style='display: none'> 
</ul> 

HTML;

EOF;

return $IPBHTML;
}

/**
 * Preview Block
 * New as of version 1.5.2+
 */
public function preview_block( )
{
//$theBlockHTML = $this->registry->getClass('output')->getTemplate('boards')->customSidebarBlock( $block );
$block = array();

$IPBHTML = "";
													
$IPBHTML .= <<<EOF

<script type='text/javascript'>
var csbTitle = document.getElementById('csbTitle');
csbTitle.innerHTML = document.forms["adform"].elements["csb_title"].value;
var csbContent = document.getElementById('csbContent');
csbContent.innerHTML = document.forms["adform"].elements["csb_content"].value;

var csbUseBox = document.forms["adform"].elements["csb_use_box"].value;

if (csbUseBox == 1) {
	var boxed = document.getElementById('boxed');
	boxed.class = 'ipsSideBlock clearfix';
}

return false; // <-- this stops the form on the original page from being submitted
</script>
   
<div class='acp-box'>
	<h3>{$this->lang->words['preview']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td style='width:271px;'>
			</td>		
			<td>
				<div id='boxed' style='text-align:left; width:271px;'>
					<h3 id='csbTitle'></h3>
					<div class='_sbcollapsable' id='csbContent'>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>

EOF;

return $IPBHTML;
}

/**
 * Overview of Blocks
 */
public function blocksOverviewWrapper( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<div class='section_title'> 
	<h2>{$this->lang->words['custom_sidebar_blocks']}</h2>
		<div class='ipsActionBar clearfix'> 
		<ul  style='float:right'> 
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&{$this->form_code_js}&do=block_form'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/application_add.png' alt='' />
					{$this->lang->words['add_block']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&{$this->form_code_js}&do=recache&amp;human=yes'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/database_refresh.png' alt='' />
					{$this->lang->words['recache_blocks']}
				</a>
			</li>
		</ul> 
	</div> 
</div> 

<div class='acp-box'>
 	<h3>{$this->lang->words['custom_sidebar_blocks']}</h3>
		<table class='ipsTable' id='reorderable_table'>	
			<tr>
				<th width="5%"class='col_drag'>&nbsp;</th>
				<th width="10%">{$this->lang->words['image']}</th>
				<th width="20%">{$this->lang->words['block_title']}</th>
				<th width="10%">{$this->lang->words['boxed']}</th>
				<th width="10%">{$this->lang->words['raw_mode']}</th>
				<th width="10%">{$this->lang->words['php_mode']}</th>
				<th width="10%">{$this->lang->words['collapsible']}</th>
				<th width="10%">{$this->lang->words['enabled']}</th>
				<th width="15%">&nbsp;</th>
			</tr>
			{$content}
		</table>			
	<div class='acp-actionbar'>
		<form id='adminform' method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=block_form&amp;type=add' onsubmit='return ACPForums.submitModForm()'>
			<input type='submit' class='button' value='{$this->lang->words['add_new_block']}' />
		</form>
	</div>
</div>

<script type='text/javascript'>
	jQ("#reorderable_table").ipsSortable( 'table', {
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Block row
 */
public function blockRow( $r="" ) {

$r['csb_on_title']          = $r['csb_on'] 			? $this->lang->words['block_enabled'] : $this->lang->words['block_disabled'];
$r['csb_use_box_title']     = $r['csb_use_box'] 	? $this->lang->words['block_boxed'] : $this->lang->words['block_unboxed'];

$r['csb_on_img']            = $r['csb_on'] 			? 'accept.png' 		: 'cross.png';
$r['csb_use_box_img']       = $r['csb_use_box'] 	? 'accept.png' 		: 'cross.png';
$r['csb_image']             = $r['csb_image'] 		? $r['csb_image'] 	: 'cross.png';
$r['csb_raw']            	= $r['csb_raw'] 		? 'accept.png' 		: 'cross.png';
$r['csb_no_collapse']       = $r['csb_no_collapse'] ? 'cross.png' 		: 'accept.png';
$r['csb_php']       		= $r['csb_php'] 		? 'accept.png' 		: 'cross.png';

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='blocks_{$r['csb_id']}'>
			<td><span class='draghandle'>&nbsp;</span></td>
			<td><img src='{$this->settings['img_url']}/{$r['csb_image']}' border='0' /></td>
			<td><span class='larger_text'><a style='text-decoration:none;' href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_form&amp;type=edit&amp;csb_id={$r['csb_id']}'>{$r['csb_title']}</a></span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['csb_use_box_img']}' border='0' alt='-' class='ipd' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['csb_raw']}' border='0' alt='-' class='ipd' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['csb_php']}' border='0' alt='-' class='ipd' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['csb_no_collapse']}' border='0' alt='-' class='ipd' /></td>	
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['csb_on_img']}' border='0' alt='-' class='ipd' /></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit' title='{$this->lang->words['edit_block']}'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_form&amp;type=edit&amp;csb_id={$r['csb_id']}'>{$this->lang->words['edit_block']}</a></li>
					<li class='i_delete' title='{$this->lang->words['delete_block']}'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;csb_id={$r['csb_id']}' onclick="return acp.confirmDelete('');">{$this->lang->words['delete_block']}</a></li>
					<li class='i_export' title='{$this->lang->words['export_block']}'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=export&amp;csb_id={$r['csb_id']}'>{$this->lang->words['export_block']}</a></li>
				</ul>
			</td>
		</tr>
HTML;



//--endhtml--//
return $IPBHTML;
}


//*************************//
//($%^ FOOTER^%$)//
//*************************//

/**
 * copyright
 */
public function footer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
		<br /><br />
		<div style='float:right; margin:10px;'>
			<tr style='text-align:center;'>
				<td style='padding:5px;'>Custom Sidebar Blocks {$this->caches['app_cache']['customSidebarBlocks']['app_version']} &copy; 2011 &nbsp;
				<a style='text-decoration:none;' href='http://emoneycodes.com/forums/' title='emoneyCodes.com - (e$) Mods'><span class='ipsBadge badge_green'  style='text-decoration:none;'>(e$) Mods</span></a></td>
			</tr>
		</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}