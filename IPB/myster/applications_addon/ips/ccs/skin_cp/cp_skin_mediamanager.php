<?php
/**
 * <pre>
 * Invision Power Services
 * Media manager skin file
 * Last Updated: $Date: 2012-02-21 08:12:11 -0500 (Tue, 21 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10329 $
 */
 
class cp_skin_mediamanager
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

public function sidebarFolders( $name, $folder, $root = false )
{
$IPBHTML = "";
//--starthtml--//

if( is_array( $folder['subfolders'] ) && count( $folder['subfolders'] ) )
{
	$style = 'expandable closed';
}

if( !$root )
{
	$title = "title='" . $this->lang->words['shiftclick_to_rename'] . "'";
}

$IPBHTML .= <<<HTML

<ul style='display: none'>
	<li data-path='{$folder['path']}' data-name='{$name}' class='{$style}'>
		<a href='#'><span {$title}>{$name}</span></a>
HTML;
		if( !$root )
		{
			$IPBHTML .= "<a href='#' class='folder_delete'>&times;</a>";
		}

		if( is_array( $folder['subfolders'] ) && count( $folder['subfolders'] ) )
		{
			foreach( $folder['subfolders'] as $n => $f )
			{
				$IPBHTML .= $this->sidebarFolders( $n, $f );
			}
		}

$IPBHTML .= <<<HTML
		
	</li>
</ul>
HTML;
//--endhtml--//
return $IPBHTML;
}

public function moveToDropdown( $name, $folder, $depth = '' )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<option value="{$folder['path']}">{$depth}{$name}</option>
HTML;

	if( is_array( $folder['subfolders'] ) && count( $folder['subfolders'] ) )
	{
		foreach( $folder['subfolders'] as $n => $f )
		{
			$IPBHTML .= $this->moveToDropdown( $n, $f, $depth . '|---' );
		}
	}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the main screen
 *
 * @access	public
 * @param	string		Current path
 * @param	array 		Folders in the path
 * @param	array 		Files in the path
 * @return	string		HTML
 */
public function overview( Array $folders, $initialDir = false )
{
$IPBHTML = "";
//--starthtml--//

$this->registry->output->addToDocumentHead('importcss', $this->settings['skin_app_url'] . 'css/mediamanager.css');
$rootDir = ( $initialDir ) ? $initialDir : CCS_MEDIA;

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/swfupload/swfupload.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/swfupload/plugins/swfupload.swfobject.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/swfupload/plugins/swfupload.cookies.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/swfupload/plugins/swfupload.queue.js'></script>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.jquery.mediamanager.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='section_title'>
	<h2>{$this->lang->words['media_manager']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['media_helpblurb']}
		</div>
	</div>
</div>


<div class='acp-box' style='border: 0;'>
	<h3>{$this->lang->words['media_browser']}</h3>
</div>

<div id='media_wrap'>
	<div id='media_popups'>
		<div id='media_popup_move' style='display: none'>
			<div>
				<h5>{$this->lang->words['popupt_choose_move_loc']}</h5>

				<p class='popup_field'>
					<select id='move_files'>
HTML;
				$IPBHTML .= $this->moveToDropdown( $this->lang->words['root_folder'], $folders['root'] );

$IPBHTML .= <<<HTML
					</select>
				</p>

				<div class='buttons'>
					<input type='button' class='button' value='{$this->lang->words['move_files']}' id='do_move_files' /> <input type='button' class='button popup_cancel' value='{$this->lang->words['cancel']}' />
				</div>
			</div>
		</div>
		<div id='media_popup_newfolder' style='display: none'>
			<div>
				<h5>{$this->lang->words['popupt_choose_folder_name']}</h5>

				<p class='popup_field'>
					<input type='text' class='input_text' id='folder_name' size='45' placeholder='{$this->lang->words['folder_name']}' />
				</p>
				
				<div class='buttons'>
					<input type='button' class='button' value='{$this->lang->words['create_folder']}' id='do_new_folder' /> <input type='button' class='button popup_cancel' value='{$this->lang->words['cancel']}' />
				</div>
			</div>
		</div>
		<div id='media_popup_renamefolder' style='display: none'>
			<div>
				<h5>{$this->lang->words['popupt_rename_folder_name']}</h5>

				<p class='popup_field'>
					<input type='text' class='input_text' id='rename_folder_name' size='45' value='' />
				</p>
				
				<div class='buttons'>
					<input type='button' class='button' value='{$this->lang->words['rename_folder']}' id='do_rename_folder' /> <input type='button' class='button popup_cancel' value='{$this->lang->words['cancel']}' />
				</div>
			</div>
		</div>
		<div id='media_popup_upload' style='display: none'>
			<div>

				<div id='upload_progress' style='display: none'>
					<h5>{$this->lang->words['mediamanage_uploading_files']}</h5>
					<div class='progress'><span> </span></div>
					<div class='status'></div>
				</div>

				<div id='upload_select'>
					<h5>{$this->lang->words['popupt_upload_file']}</h5>

					<p class='popup_field'>
						<a href='#' id='choose_files'><small>{$this->lang->words['mediamange_clickuploadfiles']}</small></a>
					</p>
					
					<div class='buttons'>
						<input type='submit' class='button' value='{$this->lang->words['upload']}' id='do_upload' disabled='disabled' style='opacity: 0.3' /> <input type='button' class='button popup_cancel' value='{$this->lang->words['cancel']}' />
					</div>
				</div>
				<span id='buttonPlaceholder'></span>

			</div>
		</div>
	</div>
	<div id='media_sidebar'>
		<h4 class='header'>{$this->lang->words['folders']}</h4>
HTML;
		$IPBHTML .= $this->sidebarFolders( $this->lang->words['ipcontent_media'], $folders['root'], true );

$IPBHTML .= <<<HTML
	</div>

	<div id='media_browser_wrap'>
		<div id='media_toolbar'>
			<ul class='left' id='media_actions'>
				<li data-role='upload' class='disabled'><a href='#'>{$this->lang->words['file_action_upload']}</a></li>
				<li data-role='new'><a href='#'>{$this->lang->words['file_action_newfolder']}</a></li>
				<li data-role='delete' class='disabled'><a href='#'>{$this->lang->words['file_action_delete']}</a></li>
				<li data-role='move' class='disabled'><a href='#'>{$this->lang->words['file_action_move']}</a></li>
			</ul>
			<p class='right' style='padding: 8px 10px 0 0;'>
				<input type='text' id='media_search' class='input_text' size='30' placeholder='{$this->lang->words['search_all_files']}' />
			</p>
		</div>
		<div id='media_loading' style='display: none'><img src='{$this->settings['skin_acp_url']}/images/loading_dark.gif' /></div>
		<div id='media_no_results' style='display: none'></div>
		<div id='media_browser'></div>
		<div id='media_path'></div>
	</div>
</div>
<script type='text/javascript'>
	
	ipb.lang['no_files'] = "{$this->lang->words['no_files']}";
	ipb.lang['no_search_files'] = "{$this->lang->words['no_search_results']}";
	ipb.lang['some_failed_delete'] = "{$this->lang->words['failed_delete']}";
	ipb.lang['some_failed_move'] = "{$this->lang->words['failed_move']}";
	ipb.lang['confirm_file_delete'] = "{$this->lang->words['confirm_file_delete']}";
	ipb.lang['confirm_delete_folder'] = "{$this->lang->words['confirm_delete_folder']}";
	ipb.lang['mm_error_fetchingfolder']	= '{$this->lang->words['mm_error_fetchingfolder']}';
	ipb.lang['mm_not_uploaded_suc']	= '{$this->lang->words['mm_not_uploaded_suc']}';
	ipb.lang['mm_upload_template']	= new Template( '{$this->lang->words['mm_upload_template']}' );

	jQ(document).ready(function() {
		setTimeout( function() {
			jQ('li[data-role="upload"]').removeClass('disabled');
			
		 	mediamanager.init("{$rootDir}", {
		 		file: function( bits ){
		 			return "<a href='" + bits['url'] + "' style='display: none' class='file' data-path=\"" + bits['full_path'] + "\" data-fileid='" + bits['fileid'] + "'><div><img src='" + bits['img'] + "' /></div><span>" + bits['name'] + "</span></a>";
		 		},
		 		info: function( bits ){
		 			return "<strong>" + bits['name'] + "</strong> &nbsp;&nbsp;&middot;&nbsp;&nbsp; <strong>{$this->lang->words['file_size']}</strong> " + bits['_size'] + " &nbsp;&nbsp;&middot;&nbsp;&nbsp; <strong>{$this->lang->words['file_modified']}</strong> " + bits['_last_modified'];
		 		}
		 	},
		 	{
		 		url: "{$this->settings['base_url']}module=ajax&section=media&do=upload&secure_key=" + ipb.vars['md5_hash'],
		 		swf_url: "{$this->settings['public_dir']}js/3rd_party/swfupload/swfupload.swf"
		 	});
		 }, 100 );
	});
</script>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Form to specify where to move items to
 *
 * @access	public
 * @param	string		Start point for items we are moving
 * @param	array 		Folders we can omit as option to move to
 * @param	array 		Folders we can move to
 * @param	array 		Pages we are moving
 * @return	string		HTML
 */
public function moveToForm( $startPoint, $ignorable, $folders, $pages )
{
$IPBHTML = "";
//--starthtml--//

$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['move_to_form_header']}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=multi&amp;action=move' method='post'>
	<input type='hidden' name='return' value='{$this->request['return']}' />
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['move_to_form_header']}</strong></td>
			<td class='field_field'>
				<input type='radio' name='moveto' value='/' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> /<br />
HTML;

	foreach( $folders as $folder )
	{
		if( $folder == $startPoint )
		{
			continue;
		}
		else
		{
			//-----------------------------------------
			// Can't move a folder into itself, or a child folder
			//-----------------------------------------
			
			foreach( $ignorable as $ignoreMe )
			{
				if( strpos( $folder, $ignoreMe ) !== false )
				{
					continue 2;
				}
			}
		}

		$display	= str_replace( $defaultPath, '', $folder );
		$display	= $display ? $display : '/';
		
		if( $display == '/' )
		{
			continue;
		}

		$IPBHTML .= <<<HTML
			<input type='radio' name='moveto' value='{$folder}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$display}<br />
HTML;
	}
	
$IPBHTML .= <<<HTML
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['moved_files_summary']}</strong></td>	
			<td class='field_field'>
HTML;

if( is_array($this->request['folders']) AND count($this->request['folders']) )
{
	foreach( $this->request['folders'] as $folder )
	{
		$paths	= explode( '/', urldecode($folder) );
		$path	= array_pop( $paths );
		
		$IPBHTML .= <<<HTML
			<input type='checkbox' checked='checked' name='folders[]' value='{$folder}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$path}<br />
HTML;

	}
}

if( is_array($pages) AND count($pages) )
{
	foreach( $pages as $page )
	{
		$paths	= explode( '/', urldecode($page) );
		$path	= array_pop( $paths );
		
		
		$IPBHTML .= <<<HTML
			<input type='checkbox' checked='checked' name='pages[]' value='{$page}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/file.png' alt='{$this->lang->words['file_alt']}' /> {$path}<br />
HTML;

	}
}
	
$IPBHTML .= <<<HTML
			</td>
		</tr>	
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['move__button']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}module=media&amp;section=list';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Create or edit directory
 *
 * @access	public
 * @param	string		Add/edit
 * @return	string		HTML
 */
public function directoryForm( $type )
{
$IPBHTML = "";
//--starthtml--//

$text	= $type == 'add' ? $this->lang->words['adding_a_folder'] : $this->lang->words['renaming_a_folder'];

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$text}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
HTML;

if( $type == 'add' )
{
	$formField		= $this->registry->output->formInput( 'folder_name' );
	
	$IPBHTML .= "		<input type='hidden' name='do' value='doCreateFolder' />
		<input type='hidden' name='parent' value='{$this->request['in']}' />";
}
else
{
	$folders		= explode ( '/', urldecode($this->request['dir']) );
	$folderName		= array_pop( $folders );
	$formField		= $this->registry->output->formInput( 'folder_name', $folderName );

	$IPBHTML .= "		<input type='hidden' name='do' value='doRenameFolder' />
		<input type='hidden' name='current' value='{$this->request['dir']}' />";
}

$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['set_folder_name']}</strong></td>
			<td class='field_field'>{$formField}</td>
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

}