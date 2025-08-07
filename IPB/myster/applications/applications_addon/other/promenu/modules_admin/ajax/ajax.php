<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_promenu_ajax_ajax extends ipsAjaxCommand {

	/**
	 * 
	 * @param ipsRegistry $registry
	 */
	public function doExecute(ipsRegistry $registry) {
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		switch ($this->request['do']) {
			case 'state':
				$this->state();
				break;
			case 'reorder':
				$this->reorder();
				break;
			case 'status':
				$this->status();
				break;
			case 'css':
				$this->css();
				break;
		}
	}

	/**
	 * @return @e void
	 */
	public function status() {
		$a['promenu_groups_preview_close'] = $this->request['status'];
		$group = $this->request['group'];

		$this->DB->update("promenuplus_groups", $a, "promenu_groups_name='" . $group . "'");
		$this->registry->profunctions->buildGroupCache();
	}

	/**
	 * @return @e void
	 */
	public function reorder() {
		$this->registry->getClass('class_permissions')->return = true;

		if(!$this->registry->class_permissions->checkPermission( 'promenu_add_group' ))
		{
			$this->returnHtml("error");
		}
		/* Define the position */
		$position = 1;
		/* Check if the array is present and count the menus */
		if (is_array($this->request['menus']) && count($this->request['menus'])) {
			/* Time to update the database with the new position */
			foreach ($this->request['menus'] as $this_id) {
				$this->DB->update('promenuplus_menus', array('promenu_menus_order' => $position), 'promenu_menus_id=' . $this_id);
				$position++;
			}
		}
		$this->registry->profunctions->kerching();
		$this->returnJsonArray($this->request['menus']);
	}

	/**
	 * @return @e void
	 */
	protected function state() {
		if ($this->request['state']) {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 1), 'promenu_menus_id=' . intval($this->request['id']));
		} else {
			$this->DB->update("promenuplus_menus", array('promenu_menus_is_open' => 0), 'promenu_menus_id=' . intval($this->request['id']));
		}
	}

	// /**
	 // * @return string $this->returnHtml()
	 // */
	// public function css() {
		// $group = $this->caches['promenu_groups'][$this->request['key']];
// 
		// if ($group['promenu_groups_template'] === "proMain") {
			// $style = $this->registry->profunctions->getHookData('proMainCss.css');
		// } else {
			// $style = $this->registry->profunctions->getHookData('proOtherCss.css');
		// }
// 
		// $style = str_replace("{menu_id}", $group['promenu_groups_name'], $style);
		// $style = str_replace("\n", "<br>", $style);
		// $html .=<<<EOF
		// <div>
		// <h3>{$this->lang->words['promenu_default_css']}</h3>
			// <div style="width:800px;height:500px;overflow-y:auto;padding:20px;word-wrap:break-word;">
				// {$style}
			// </div>
		// </div>
// EOF;
		// $this->returnHtml($html);
	// }

}