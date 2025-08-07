<?php
class {class_name} {

	public $registry;
	
	public function __construct() {
		$this -> registry = ipsRegistry::instance();
	}

	public function getOutput() {
		if( IPSLib::appIsInstalled('promenu') ){
			if (!$this->registry->isClassLoaded('app_class_promenu')) {
				$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/app_class_promenu.php', 'app_class_promenu', 'promenu');
				$this -> registry -> setClass('app_class_promenu', new $classToLoad($this -> registry));
			}
			$cache 	= $this->registry->profunctions->GetCaches('{group}');
			
			$cache['menus'] 	= $this->registry->profunctions->ParseMenus( $cache['menus'], 0, $cache['groups']);	

			if( count($cache['menus']) && is_array($cache['menus']) && $cache['groups']['promenu_groups_enabled'] && !in_array($this->registry->output->skin['set_id'],explode(",",$cache['groups']['promenu_groups_hide_skin'] ) ) ){

				$output .= <<<EOF
				<div id="{menu_id}" class="clear">
					<div class="main_widths">
EOF;
				$output .= $this->registry->promenuHooks->menus( array( 'cache' => $cache, 'template' => '{template}', 'ulID' => '{menu_id}', 'jsMenuEnabled' => FALSE) );
				$output .= <<<EOF
					</div>
				</div>
EOF;
				return $output;
			}			
		}
	}
}