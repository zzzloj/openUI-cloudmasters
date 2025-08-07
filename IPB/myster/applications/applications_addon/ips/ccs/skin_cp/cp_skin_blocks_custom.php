<?php
/**
 * <pre>
 * Invision Power Services
 * Custom block type
 * Last Updated: $Date: 2011-12-06 22:05:50 -0500 (Tue, 06 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 9961 $
 */
 
class cp_skin_blocks_custom
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
 * Wizard: Step 2 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @return	string		HTML
 */
public function custom__wizard_2( $session )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$steps		= $this->getSteps( 2, $sessionId, (bool) $session['config_data']['block_id'] );

$customType	= array( array( 'basic', $this->lang->words['block_custom_basic'] ), array( 'html', $this->lang->words['block_custom_html'] ), array( 'php', $this->lang->words['block_custom_php'] ) );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['block_wizard']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
	<input type='hidden' name='step' value='2' />
	<div class='ipsSteps_wrap'>
		{$steps}
		<div class='ipsSteps_wrapper'>
			<div class='steps_content'>
				<div class='acp-box'>
					<h3>{$this->lang->words['specify_custom_type']}</h3>
					<table class='ipsTable double_pad'>
HTML;
			
						foreach( $customType as $_type )
						{
							$IPBHTML .= "
						<tr onclick=\"$('custom_type_{$_type[0]}').checked = 'checked';\">
							<td class='field_title'>
								<strong class='title'>{$_type[1]}</strong>
							</td>
							<td class='field_field'>
								<input type='radio' name='custom_type' value='{$_type[0]}' id='custom_type_{$_type[0]}' " . ( $session['config_data']['type'] == $_type[0] ? "checked='checked' " : '' ) . "/>
								{$this->lang->words['block_custom_' . $_type[0] . '_desc']}
							</td>
						</tr>";
						}
						
			$IPBHTML .= <<<HTML
					</table>
				</div>
			</div>
			<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
				<input type='button' value='{$this->lang->words['button__cancel']}' class="button redbutton" onclick="window.location='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}';" />
				<input type='submit' value='{$this->lang->words['button__continue']}' class="button right" />
			</div>
		</div>
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 3 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Categories
 * @return	string		HTML
 */
public function custom__wizard_3( $session, $categories )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$steps		= $this->getSteps( 3, $sessionId, (bool) $session['config_data']['block_id'] );
$_edit		= $session['config_data']['block_id'] ? $this->getSaveButtons() : '';

$_hide			= $this->registry->output->formYesNo( 'hide_empty', $session['config_data']['hide_empty'] );
$_categories	= '';

if( count($categories) )
{
	$_categories	= $this->registry->output->formDropdown( 'category', $categories, $session['config_data']['category'] );
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['block_wizard']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
	<input type='hidden' name='step' value='3' />
	<div class='ipsSteps_wrap'>
		{$steps}
		<div class='ipsSteps_wrapper'>
			<div class='steps_content'>
				<div class='acp-box'>
					<h3>{$this->lang->words['block_title_desc']}</h3>
					<table class='ipsTable double_pad'>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['form__block_title']}</strong>
							</td>
							<td class='field_field'>
								<input type='text' class='textinput' name='custom_title' id='custom_title' value='{$session['config_data']['title']}' />
								<div class='field_subfield'>{$this->lang->words['form__block_key']}: <input type='text' class='textinput' name='custom_key' id='custom_key' value='{$session['config_data']['key']}' /></div>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['form__block_desc']}</strong>
							</td>
							<td class='field_field'>
								<textarea class='multitext' name='custom_description' cols='100' rows='2'>{$session['config_data']['description']}</textarea>
							</td>
						</tr>
HTML;
				
				if( $_categories )
				{
					$IPBHTML .= <<<HTML
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['select_category']}</strong>
							</td>
							<td class='field_field'>
								{$_categories}
							</td>
						</tr>
HTML;
				}
				
				$IPBHTML .= <<<HTML
						<tr>
							<th colspan='2'>{$this->lang->words['page_advanced_opts']}</th>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['cache_ttl_opt']}</strong>
							</td>
							<td class='field_field'>
								<input type='checkbox' id='cache_override' /> <label for='cache_override'>{$this->lang->words['cache_this_block']}</label> &nbsp;&nbsp;<a href='#' id='cache_help' style='font-size: 11px'>{$this->lang->words['cache_link']}</a>
								<p id='cache_edit' style='display: none'>
									<br />
									<input type='text' class='input_text' name='cache_ttl' id='cache_ttl' value='{$session['config_data']['cache_ttl']}' size='5' /> {$this->lang->words['cache_minutes']} <br />
									<span class='desctext'>{$this->lang->words['cache_ttl_desc']}</span>
								</p>
							</td>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['hide_block_no_content']}</strong>
							</td>
							<td class='field_field'>
								{$_hide}
							</td>
						</tr>
					</table>
				</div>	
			</div>
			<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
				{$_edit}
				<input type='button' value='{$this->lang->words['button__cancel']}' class="button redbutton" onclick="window.location='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}';" />
				<input type='submit' value='{$this->lang->words['button__continue']}' class="button right" />
			</div>
		</div>
	</div>
</form>
<div id='cache_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['cache_help_title']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['cache_help_block']}
	</div>
</div>
<script type='text/javascript'>
	ipb.lang['changejstitle']	= '{$this->lang->words['changejstitle']}';
	ipb.lang['changejsinit']	= '{$this->lang->words['changejsinit']}';
	
	var obj = new acp.liveedit( 
					$('custom_title'),
					$('custom_key'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=blocks&do=checkKey"
					}
			  );

</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 4 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @param	string		Editor HTML
 * @return	string		HTML
 */
public function custom__wizard_4( $session, $editor_area )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$steps		= $this->getSteps( 4, $sessionId, (bool) $session['config_data']['block_id'] );
$_edit		= $session['config_data']['block_id'] ? $this->getSaveButtons() : '';
$_class1	= IPSCookie::get('hideContentHelp') ? "" : "withSidebar";
$_class2	= IPSCookie::get('hideContentHelp') ? " closed" : "";
$_help		= $this->registry->ccsAcpFunctions->getBlockTags( 'custom', $session['config_data']['type'] );

$IPBHTML .= <<<HTML
<script type='text/javascript'>
	ipb.lang['insert_tag']	= '{$this->lang->words['insert']}';
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.tagsidebar.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['block_wizard']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
	<input type='hidden' name='step' value='4' />
	<div class='ipsSteps_wrap'>
		{$steps}
		<div class='ipsSteps_wrapper'>
			<div class='steps_content'>
				<div class='acp-box'>
					<h3>{$this->lang->words['edit_block_template']}</h3>
					<table class='ipsTable double_pad'>
						<tr>
							<td class='sidebarContainer'>
HTML;

if( $session['config_data']['type'] != 'php' )
{
	$IPBHTML .= <<<HTML
	<div id="template-tags" class="templateTags-container{$_class2}">{$_help}</div>
HTML;
}
else
{
	$_class1	= 'noSidebar';
}

$IPBHTML .= <<<HTML
								
								<div id='content-label' class="{$_class1}">{$editor_area}</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
				{$_edit}
				<input type='button' value='{$this->lang->words['button__cancel']}' class="button redbutton" onclick="window.location='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}';" />
				<input type='submit' value='{$this->lang->words['button__continue']}' class="button right" />
			</div>
		</div>
	</div>
</form>
HTML;

$IPBHTML .= $this->registry->output->loadTemplate('cp_skin_ccsglobal')->wysiwyg__ipbrte();

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 5 (Custom)
 *
 * @access	public
 * @param	array 		Block data
 * @return	string		HTML
 */
public function custom__wizard_DONE( $block )
{
$IPBHTML = "";
//--starthtml--//

$key		= '{parse block="' . $block['block_key'] . '"}';

$steps = $this->getSteps( 5, $block['block_id'], true );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['block_wizard']}</h2>
</div>

<div class='ipsSteps_wrap'>
	{$steps}
	<div class='ipsSteps_wrapper'>
		<div class='steps_content'>
			<div class='acp-box'>
			<h3>{$this->lang->words['congrats_block_done']}</h3>
			<table class='ipsTable'>
				<tr>
					<td>
						{$this->lang->words['custom_block_done_1']}
						<br /><br />
						{$key}
						<br /><br />
						{$this->lang->words['custom_block_done_2']}
					</td>
				</tr>		
			</table>
		</div>
		<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
			<input type='button' value='{$this->lang->words['button__finished']}' class="button primary" onclick='acp.redirect("{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks", 1 );' />
		</div>
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Get the wizard step bar
 *
 * @access	public
 * @param	int			Current step
 * @param	string		Wizard session id
 * @param	bool		Add block process (instead of editing)
 * @return	string		HTML
 */
public function getSteps( $currentStep, $wizardId, $isAddBlock=true )
{
	$_global	= $this->registry->output->loadTemplate( 'cp_skin_blocks' );

	return $_global->wizard_stepbar( array(
										2	=> $this->lang->words['stepbar__customtype'],
										3	=> $this->lang->words['stepbar__details'],
										4	=> $this->lang->words['stepbar__template'],
										5	=> $this->lang->words['stepbar__done']
									), $currentStep, $wizardId, !$isAddBlock );
}

/**
 * Get save and save/reload buttons
 *
 * @access	public
 * @return	string		HTML
 */
public function getSaveButtons()
{
	$_global	= $this->registry->output->loadTemplate( 'cp_skin_blocks' );

	return $_global->wizard_savebuttons();
}

}