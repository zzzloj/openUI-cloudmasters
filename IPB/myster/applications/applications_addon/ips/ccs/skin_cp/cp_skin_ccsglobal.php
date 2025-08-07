<?php
/**
 * <pre>
 * Invision Power Services
 * Global CCS functions
 * Last Updated: $Date: 2012-01-16 17:52:17 -0500 (Mon, 16 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10142 $
 */
 
class cp_skin_ccsglobal
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
 * Get javascript lang strings
 *
 * @access	public
 * @return	@e void
 */
public function getJsLangs()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript'>
ipb.lang['js__nofilterperm']		= "{$this->lang->words['js__nofilterperm']}";
ipb.lang['js__nosearchperm']		= "{$this->lang->words['js__nosearchperm']}";
ipb.lang['js__folderempty']			= "{$this->lang->words['js__folderempty']}";
ipb.lang['js__deletefolder']		= "{$this->lang->words['js__deletefolder']}";
ipb.lang['js__foldername']			= "{$this->lang->words['js__foldername']}";
ipb.lang['js__folderexists']		= "{$this->lang->words['js__folderexists']}";
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Return inline CSS for the images.  Done this way to preserve
 * image paths when minify is enabled.
 *
 * @access	public
 * @return	@e void
 */
public function getCss()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

.filter_box {
	background: #efefef url( {$this->settings['skin_acp_url']}/images/ccs/filterbar_bg.png ) repeat-x bottom;
}

#main_content table.sortable tr th.sort.sorting {
	background: #528f6c;
}

#main_content table.sortable.desc tr th.sort:hover .sort_order,
#main_content table.sortable tr th.sort:hover .sort_order {
	background: url( {$this->settings['skin_acp_url']}/images/ccs/sort_desc_off.png ) no-repeat right;
}

	#main_content table.sortable.asc tr th.sort:hover .sort_order {
		background: url( {$this->settings['skin_acp_url']}/images/ccs/sort_asc_off.png ) no-repeat right;
	}

	#main_content table.sortable.desc tr th.sort.sorting .sort_order,
	#main_content table.sortable tr th.sort.sorting .sort_order {
		background: url( {$this->settings['skin_acp_url']}/images/ccs/sort_desc_on.png ) no-repeat right;
	}
	
	#main_content table.sortable.asc tr th.sort.sorting .sort_order {
		background: url( {$this->settings['skin_acp_url']}/images/ccs/sort_asc_on.png ) no-repeat right;
	}

.article_expander h4 {
	background: url( {$this->settings['skin_acp_url']}/images/ccs/folder_closed.png ) no-repeat 2px 2px;
}

	.article_expander.open h4 {
		background: url( {$this->settings['skin_acp_url']}/images/ccs/folder_open.png ) no-repeat 2px 2px;
	}

.date_button {
	background: url( {$this->settings['skin_acp_url']}/images/icons/date.png ) no-repeat;
}

.icon.ccs-file {
	background-image: url( {$this->settings['skin_acp_url']}/images/ccs/page.png );
}

.icon.ccs-css {
	background-image: url( {$this->settings['skin_acp_url']}/images/ccs/css.png );
}

.icon.ccs-js {
	background-image: url( {$this->settings['skin_acp_url']}/images/ccs/js.png );
}

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Return WYSIWYG HTML
 *
 * @access	public
 * @param	string		Field name
 * @param	string		Language type
 * @return	@e void
 */
public function getWysiwyg( $fieldname, $language='html' )
{
	switch( $this->settings['ccs_template_type'] )
	{
		case 'tinymce':
			return $this->wysiwyg__tinymce( $fieldname, $language );
		break;
		
		case 'ckeditor':
			return $this->wysiwyg__ckeditor( $fieldname, $language );
		break;

		case 'editarea':
			return $this->wysiwyg__editarea( $fieldname, $language );
		break;

		case 'none':
			return $this->wysiwyg__none( $fieldname, $language );
		break;
	}
	
	return '';
}

/**
 * TinyMCE template editing
 *
 * @access	public
 * @param	string		Fieldname
 * @param	string		Language type
 * @return	HTML
 */
public function wysiwyg__none( $fieldname, $language='html' )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['skin_app_url']}/editors/none/rangyinputs.js"></script>
<script type='text/javascript'>
	function insertTag( tag )
	{
		\$('{$fieldname}').focus();
		rangyInputs.replaceSelectedText( \$('{$fieldname}'), tag );
	}
</script>

HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * TinyMCE template editing
 *
 * @access	public
 * @param	string		Fieldname
 * @param	string		Language type
 * @return	HTML
 */
public function wysiwyg__tinymce( $fieldname, $language='html' )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['skin_app_url']}/editors/tinymce/tiny_mce.js"></script>
<script type="text/javascript">
	var tinyMCEConfigs = {
		mode : "exact",
		elements: "{$fieldname}",
		theme : "advanced",
		width: '100%',
		oninit : "postInitWork",
		plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,forecolor,backcolor,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "save,newdocument,help,cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,code",
		theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media",
		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,advhr,|,ltr,rtl,|,cleanup,fullscreen",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true
	};
	
	tinyMCE.init( tinyMCEConfigs );
	
	function postInitWork()
	{
		if( $('page_content_tbl') )
		{
			//$('page_content_tbl').setStyle('width: 100%');
		}
	}
	
	function resizeEditor()
	{
		tinyMCEConfigs.width = '70%';
		tinyMCE.settings = tinyMCEConfigs;
		tinyMCE.execCommand( 'mceRemoveControl', true, "{$fieldname}" );
		tinyMCE.execCommand( 'mceAddControl', true, "{$fieldname}" );
	}
	
	function restoreResizeEditor()
	{
		tinyMCEConfigs.width = '100%';
		tinyMCE.settings = tinyMCEConfigs;
		tinyMCE.execCommand( 'mceRemoveControl', true, "{$fieldname}" );
		tinyMCE.execCommand( 'mceAddControl', true, "{$fieldname}" );
	}

	function insertTag( tag )
	{
		tinyMCE.execCommand( 'mceInsertContent', false, tag );
	}
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * CKEditor template editing
 *
 * @access	public
 * @param	string		Fieldname
 * @param	string		Language type
 * @return	HTML
 */
public function wysiwyg__ckeditor( $fieldname, $language='html' )
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/ckeditor/ckeditor_source.js"></script>
<script type="text/javascript">
	var _ckeditor;

	document.observe("dom:loaded", function(){
		_ckeditor = CKEDITOR.replace( '{$fieldname}' );
	});

	function insertTag( tag )
	{
		_ckeditor.insertText( tag );
	}
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * EditArea template editing
 *
 * @access	public
 * @param	string		Fieldname
 * @param	string		Language type
 * @return	HTML
 */
public function wysiwyg__editarea( $fieldname, $language='html' )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['skin_app_url']}/editors/editarea/edit_area_full.js"></script>
<script type="text/javascript">
	editAreaLoader.init({
		id: "{$fieldname}"	// id of the textarea to transform		
		,start_highlight: true	// if start with highlight
		,allow_resize: "both"
		,allow_toggle: true
		,word_wrap: false
		,language: "en"
		,syntax: "{$language}"	
	});

	function insertTag( tag )
	{
		try {
			editAreaLoader.setSelectedText("{$fieldname}", tag);
		} catch(err){ }
	}
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Define the insertTag function for IPB RTE
 *
 * @access	public
 * @param	string		Fieldname
 * @param	string		Language type
 * @return	HTML
 */
public function wysiwyg__ipbrte()
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<script type="text/javascript">
	if( !Object.isUndefined(ipb.textEditor) )
	{
		function insertTag( tag )
		{
			ipb.textEditor.getEditor().insert( tag );
		}
	}
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

}