<?php

class proPlusSkin {
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var     $registry
	 * @var     $DB
	 * @var     $settings
	 * @var     $request
	 * @var     $lang
	 * @var     $member
	 * @var     $memberData
	 * @var     $cache
	 * @var     $caches
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
	 * @param   object      $registry       Registry object
	 * @return  @e void
	 */
	public function __construct(ipsRegistry $registry) {
		$this->registry = $registry;
		$this->lang = $this->registry->getClass('class_localization');
		$this->DB = $this->registry->DB();
		$this->settings = &$this->registry->fetchSettings();
		$this->request = &$this->registry->fetchRequest();
		$this->member = $this->registry->member();
		$this->memberData = &$this->registry->member()->fetchMemberData();
		$this->cache = $this->registry->cache();
		$this->caches = & $this->registry->cache()->fetchCaches();		
	}
	
	public function skinCCS( $items='' ){
		$html = '';
		
		$html .= <<<EOF
			<tr class='ipsControlRow show' id="ipc_blocks">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_ipc_blocks']}</strong></td>
				<td class='field_field'>
					<div style="width:400px;float:left;">
						<textarea class="multitext" style="width:380px;height:400px;" name="ipc_blocks_code" id="ipc_blocks_code">{$items['promenu_menus_block']}</textarea>	
					</div>
					{$this->registry->proPlus->ipcBlocksList()}
					<br />
					<span class='desctext'>{$this->lang->words['promenu_ipc_blocks_desc']}</span>
				</td>
			<script>
			(function($){
				$(document).ready(function(){

					 ipchtml = CodeMirror.fromTextArea(document.getElementById("ipc_blocks_code"), {
						lineNumbers: true,
					    styleActiveLine: true,	
						highlightSelectionMatches: true,
						mode: 'text/html',
						autoCloseTags: true,
						lineNumbers: true,
				  		lineWrapping: true,
						extraKeys: {"Ctrl-Space": "autocomplete"}		
					});
					$('.insert_tag').click(function(e){
						e.preventDefault();
						obj = ipchtml.getCursor();
						ipchtml.replaceRange($(this).data("tag"),obj,obj);
					});
				})
			}(boo))
			</script>				
			</tr>
EOF;

		return $html;
	}
	
	public function skinEasyPages( $items='' ){
		$html = '';
		$html .=<<<EOF
			<tr class='ipsControlRow show' id="easypages_blocks">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_easypages_block']}</strong></td>
				<td class='field_field'>
					<div style="width:400px;float:left;">
						<textarea class="multitext" style="width:380px;height:400px;" name="easypages_blocks_code" id="easypage_blocks_code">{$items['promenu_menus_block']}</textarea>	
					</div>
					{$this->registry->proPlus->ipcBlocksList('easy')}
					<br />
					<span class='desctext'>{$this->lang->words['promenu_easypages_block_desc']}</span>
				</td>
			<script>
			(function($){
				$(document).ready(function(){

					easyhtml = CodeMirror.fromTextArea(document.getElementById("easypage_blocks_code"), {
						lineNumbers: true,
					    styleActiveLine: true,	
						highlightSelectionMatches: true,
						mode: 'text/html',
						autoCloseTags: true,
						lineNumbers: true,
				  		lineWrapping: true,
						extraKeys: {"Ctrl-Space": "autocomplete"}		
					});	
				$('.insert_tag').click(function(e){
					e.preventDefault();
					obj = easyhtml.getCursor();
					easyhtml.replaceRange($(this).data("tag"),obj,obj);
				});
				});
			}(boo))
			</script>
			</tr>
EOF;
		return $html;
	}
	
	public function skinWrapper( $items='' ){
		$html = '';
		$html .=<<<EOF
			<tr class='ipsControlRow show' style="display:none;" id="wurl">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menus_wrapper_url']}</strong></td>
				<td class='field_field'>{$this->registry->output->formInput('promenu_menus_wrapper', $items['promenu_menus_wrapper'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_menus_wrapper_desc']}!</span></td>
			</tr>
			<tr class='ipsControlRow show' style="display:none;" id="wurlh">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menus_wrapper_height']}</strong></td>
				<td class='field_field'>{$this->registry->output->formInput('promenu_menus_wrapper_height', $items['promenu_menus_wrapper_height'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_menus_wrapper_height_desc']}!</span></td>
			</tr>
			<tr class='ipsControlRow show' style="display:none;" id="wurlW">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menus_disable_wrapper_menu']}</strong></td>
				<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_disable_wrapper_menu', $items['promenu_menus_disable_wrapper_menu'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_menus_disable_wrapper_menu_desc']}.</span></td>
			</tr>
			<tr class='ipsControlRow show' style="display:none;" id="wurlW">
				<td class='field_title'><strong class='title'>{$this->lang->words['promenu_menus_wrapper_wrapped']}</strong></td>
				<td class='field_field'>{$this->registry->output->formYesNo('promenu_menus_wrapper_wrapped', $items['promenu_menus_wrapper_wrapped'])}<br />
				<span class='desctext'>{$this->lang->words['promenu_menus_wrapper_wrapped_desc']}.</span></td>
			</tr>
EOF;
		return $html;
	}	
}
