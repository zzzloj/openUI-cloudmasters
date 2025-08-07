<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
class skin_hooks extends output {
	
	public function __destruct() {	
	}
	
	public function jQueryInit() {
		if (IPB_LONG_VERSION < 34006) {

			$html .=<<<EOF
			<script src="{$this->settings['public_dir']}js/3rd_party/jquery-1.8.3.min.js"></script>
			<script src="{$this->settings['public_dir']}js/3rd_party/jquery-ui-1.9.2.custom.min.js"></script>
			<script>
				boo = jQuery.noConflict();
			</script>
EOF;
		} else {
			$html .= '<script> boo = jQuery; </script>';
		}
		return $html;
	}

	public function hookForm() {
		$group = $this->caches['promenu_groups'][$this->request['key']];
		$html .= $this->jQueryInit();
		$html .=<<<EOF
		<style>
			.goodbye{
				width:100%;
			}
			.err,.errs{
				background:#FFDFEF !important;
			}
		</style>
		<script src="{$this->settings['js_app_url']}acp.promenuplus.js"></script>
		<script>
			url = ipb.vars['app_url'].replace(/&amp;/g, '&')+"module=menus&section=menus";
			jQuery("#section_navigation").find("a").each(function(){
				if(jQuery(this).attr('href') == url)
				{
					jQuery(this).parent().remove();
				}
			})
			jQ(document).ready(function(){
				jQuery("#theForm").hookCheck();
			})
			ipb.templates['hook_pointTypes']      = new Template("<tr id='tr_type[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['promenu_hooks_type']}</strong></td><td class='field_field'><select name='type[#{index}]' onchange='getHookIds(#{index});' id='type[#{index}]' class='dropdown'><option value='0'>{$this->lang->words['promenu_select_one']}</option><option value='foreach'>{$this->lang->words['promenu_foreach_loop']}</option><option value='if'>{$this->lang->words['promenu_if_statement']}</option></select></td></tr>");		
			ipb.templates['hook_pointLocation']   = new Template("<tr id='tr_position[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['promenu_hooks_position']}</strong></td><td class='field_field'><select name='position[#{index}]' id='position[#{index}]'>#{hookPoints}</select></td></tr>");
		</script>
		<form name="theForm" id="theForm" method='post' enctype="multipart/form-data" action='{$this->settings['base_url']}module=menus&amp;section=menus&amp;do=export&amp;postkey={$this->member->form_hash}'>
			<input type="hidden" name="group" value="{$this->request['key']}" />
EOF;
		if (!empty($group['promenu_groups_has_hook'])) {
			$html .= $this->editHook(unserialize($group['promenu_groups_has_hook']));
		} else {
			$html .= $this->newHook();
		}
		$html .=<<<EOF
			<div class='acp-actionbar'>
				<input type='submit' class='button primary' value="{$this->lang->words['promenu_word_export']}" id="exportSub"/>
				<input type='submit' class='button redbutton' name='cancel'  value="cancel" />
			</div>				
		</form>
EOF;

		return $html;
	}

	protected function newHook() {

		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('core') . '/sources/classes/hooksFunctions.php', 'hooksFunctions');

		$this->hooksFunctions = new $classToLoad($this->registry);

		$_skinFiles = $this->hooksFunctions->getSkinGroups();

		$html = '';

		$html .=<<<EOF
		<div class='acp-box'>
			<h3>{$this->lang->words['promenu_hooks_title']}</h3>
			<table class='ipsTable double_pad' id='fileTableContainer'>
				<tr id="fileRow_1">
					<td style="margin:0px; padding:0px;">
						<table class="ipsTable" id="fileTable_1">
							<tr>
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_skin_group']}</strong>
								</td>
								<td class='field_field'>
									{$this->registry->output->formDropdown("skinGroup[1]", $_skinFiles, null, "skinGroup[1]", "onchange='getTemplatesForAdd();'")}
								</td>
							</tr>
						</table>
							
						<table id="skin_group" style="display:none;" class="ipsTable goodbye" id="fileTable_1">
							<tr style="width:100%;">
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_skin_function']}</strong>
								</td>
								<td class='field_field' id="skin_group_content">
								</td>
							</tr>
						</table>
							
						<table class="ipsTable goodbye" id="skin_type" style="display:none;">
						</table>
							
						<table class="ipsTable goodbye" id="skin_id" style="display:none;">
							<tr>
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_id']}</strong>
								</td>
								<td class='field_field'  id="skin_id_content">
								</td>
							</tr>
						</table>
							
						<table class="ipsTable goodbye" id="skin_position" style="display:none;">

						</table>
					</td>
				</tr>
			</table>
		</div>
EOF;

		return $html;
	}

	protected function editHook($group) {

		$cs = $group['loc'];

		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('core') . '/sources/classes/hooksFunctions.php', 'hooksFunctions');

		$this->hooksFunctions = new $classToLoad($this->registry);

		$_skinFiles = $this->hooksFunctions->getSkinGroups();

		$_strings = $this->hooksFunctions->getSkinMethods($cs['skinGroup'], true);

		$_type[] = array(0, "Select one");
		$_type[] = array("foreach", "foreach loop");
		$_type[] = array("if", "if statement");

		$return = $this->hooksFunctions->getHookIds($cs['skinFunction'], $cs['type'], $cs['skinGroup']);

		$output = ( count($return) > 1 ) ? $this->registry->output->formDropdown("id[1]", $return, $cs['id'], "id[1]", "onchange='getHookEntryPoints(1);'") : $this->lang->words['hook_no_hook_ids_found'];

		$entryPoints = array('foreach' => array(array('outer.pre', $this->lang->words['h_outerpre']),
				array('inner.pre', $this->lang->words['h_innerpre']),
				array('inner.post', $this->lang->words['h_innerpost']),
				array('outer.post', $this->lang->words['h_outerpost'])
			),
			'if' => array(array('pre.startif', $this->lang->words['h_prestartif']),
				array('post.startif', $this->lang->words['h_poststartif']),
				array('pre.else', $this->lang->words['h_preelse']),
				array('post.else', $this->lang->words['h_postelse']),
				array('pre.endif', $this->lang->words['h_preendif']),
				array('post.endif', $this->lang->words['h_postendif'])
			)
		);
		$html = '';

		$html .=<<<EOF
		<div class='acp-box'>
			<h3>{$this->lang->words['promenu_hooks_title']}</h3>
			<table class='ipsTable double_pad' id='fileTableContainer'>
				<tr id="fileRow_1">
					<td style="margin:0px; padding:0px;">
						<table class="ipsTable" id="fileTable_1">
							<tr>
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_skin_group']}</strong>
								</td>
								<td class='field_field'>
									{$this->registry->output->formDropdown("skinGroup[1]", $_skinFiles, $cs['skinGroup'], "skinGroup[1]", "onchange='getTemplatesForAdd();'")}
								</td>
							</tr>
						</table>
							
						<table id="skin_group" class="ipsTable goodbye" id="fileTable_1">
							<tr style="width:100%;">
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_skin_function']}</strong>
								</td>
								<td class='field_field' id="skin_group_content">
								{$this->registry->output->formDropdown("skinFunction[1]", $_strings, $cs['skinFunction'], "skinFunction[1]", "onchange='getTypeOfHook(1);'")}
								</td>
							</tr>
						</table>
							
						<table class="ipsTable goodbye" id="skin_type">
							<tr id='tr_type[1]'>
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_type']}</strong>
								</td>
								<td class='field_field'>
								{$this->registry->output->formDropdown("type[1]", $_type, $cs['type'], "type[1]", "onchange='getHookIds(1);'")}
								</td>
							</tr>
						</table>
							
						<table class="ipsTable goodbye" id="skin_id">
							<tr>
								<td class='field_title'>
									<strong class='title'>{$this->lang->words['promenu_hooks_id']}</strong>
								</td>
								<td class='field_field'  id="skin_id_content">
								{$output}
								</td>
							</tr>
						</table>

						<table class="ipsTable goodbye" id="skin_position">
							<tr id="tr_position[1]">
								<td class="field_title">
									<strong class="title">{$this->lang->words['promenu_hooks_position']}</strong>
								</td>
								<td class="field_field">
								{$this->registry->output->formDropdown("position[1]", $cs['type'] == 'foreach' ? $entryPoints['foreach'] : $entryPoints['if'], $cs['position'], "position[1]")}
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
EOF;

		return $html;
	}

}