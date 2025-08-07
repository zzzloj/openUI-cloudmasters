<?php
/**
 * Bump Up Topics
 * @file		cp_skin_tb_but_group_form.php 	ACP group form template
 *
 * @copyright	(c) 2006 - 2013 Invision Byte
 * @link		http://www.invisionbyte.net/
 * @author		Terabyte
 * @since		08 Aug 2011
 * @updated 	05 May 2013
 * @version		2.0.2 (20002)
 */

class cp_skin_tb_but_group_form
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
 * Show forums group form
 *
 * @param	array		$group		Group data
 * @param	integer		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_group_form_main( $group, $tabId ) {

/* Build form drop downs */
require_once( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php' );/*noLibHook*/
$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );
$aff = new $classToLoad( $this->registry );
$aff->forumsInit();
$forum_dropdown = $aff->adForumsForumList( 1 );

/* Check for forums */
$group['g_tb_but_forums'] = IPSText::cleanPermString( trim( empty($this->request['g_tb_but_forums']) ? $group['g_tb_but_forums'] : $this->request['g_tb_but_forums'] ) );

$chosenForums = array();
$choseAll     = false;
if ( $group['g_tb_but_forums'] == '*' )
{
	$choseAll = true;
}
elseif ( $group['g_tb_but_forums'] )
{
	$chosenForums = explode(',', $group['g_tb_but_forums']);
}

$form							= array();
$form['g_tb_but_use']			= $this->registry->output->formYesNo( "g_tb_but_use", $group['g_tb_but_use'] );
$form['g_tb_but_forums']		= $this->registry->output->formMultiDropdown( "g_tb_but_forums[]", $forum_dropdown, $chosenForums, 5, 'g_tb_but_forums' );
$form['g_tb_but_forums_all']	= $this->registry->output->formCheckbox( "g_tb_but_forums_all", $choseAll );
$form['g_tb_but_bumpall']		= $this->registry->output->formYesNo( "g_tb_but_bumpall", $group['g_tb_but_bumpall'] );
$form['g_tb_but_day_limit']		= $this->registry->output->formSimpleInput( "g_tb_but_day_limit", $group['g_tb_but_day_limit'] );
$form['g_tb_but_time_limit']	= $this->registry->output->formSimpleInput( "g_tb_but_time_limit", $group['g_tb_but_time_limit'] );
$form['g_tb_but_last_limit']	= $this->registry->output->formSimpleInput( "g_tb_but_last_limit", $group['g_tb_but_last_limit'] );


$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<div>
		<table class='ipsTable'>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_access_permissions']}</strong></th>
			</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_use']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_use']}</td>
		 	</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_forums']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_forums_all']} <label for='g_tb_but_forums_all'>{$this->lang->words['gf_tb_but_forums_all']}</label><br /><br />{$form['g_tb_but_forums']}</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_restrictions']}</strong></th>
			</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_bumpall']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_bumpall']}<br /><span class='desctext'>{$this->lang->words['gf_tb_but_bumpall_desc']}</span></td>
		 	</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_day_limit']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_day_limit']}<br /><span class='desctext'>{$this->lang->words['gf_tb_but_day_limit_desc']}</span></td>
		 	</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_time_limit']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_time_limit']} <span class='desctext'>{$this->lang->words['gf_tb_but_quick_values']}<br />{$this->lang->words['gf_tb_but_time_limit_desc']}</span></td>
		 	</tr>
			<tr>
		 		<td class='field_title'><strong class='title'>{$this->lang->words['gf_tb_but_last_limit']}</strong></td>
				<td class='field_field'>{$form['g_tb_but_last_limit']} <span class='desctext'>{$this->lang->words['gf_tb_but_quick_values']}<br />{$this->lang->words['gf_tb_but_last_limit_desc']}</span></td>
		 	</tr>
		</table>
		<script type="text/javascript">
			$('g_tb_but_forums_all').observe( 'click', function() { $('g_tb_but_forums').toggle(); } );
			
			if ( $('g_tb_but_forums_all').checked )
			{
				$('g_tb_but_forums').hide();
			}
		</script>
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Display group form tabs
 *
 * @param	array		$group		Group data
 * @param	string		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_GROUPS_{$tabId}'>{$this->lang->words['tb_but_tab_title']}</li>
EOF;

return $IPBHTML;
}

}