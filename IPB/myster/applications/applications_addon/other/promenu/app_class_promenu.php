<?php
/**
 * ProMenu
 * Provisionists LLC
 *
 * @ Package :          ProMenu
 * @ File :             app_class_promenu.php
 * @ Last Updated :     Apr 17, 2012
 * @ Author :           Robert Simons
 * @ Copyright :        (c) 2011 Provisionists, LLC
 * @ Link    :          http://www.provisionists.com/
 * @ Revision :         2
 */

class app_class_promenu {
	
	/**
	 * Registry Object Shortcuts
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $cache;
	public $lang;
	public $member;
	public $memberData;
	
	/**
	 * Constructor
	 */
	function __construct(ipsRegistry $registry) {
		$this -> registry = $registry;
		$this -> DB = $this -> registry -> DB();
		$this -> settings = &$this -> registry -> fetchSettings();
		$this -> request = &$this -> registry -> fetchRequest();
		$this -> cache = $this -> registry -> cache();
		$this -> caches = &$this -> registry -> cache() -> fetchCaches();
		$this -> lang = $this -> registry -> getClass('class_localization');
		$this -> member = $this -> registry -> member();
		$this -> memberData = &$this -> registry -> member() -> fetchMemberData();
		$this->registry->class_localization->loadLanguageFile(array('admin_promenu'), 'promenu');
		
		if (IN_ACP) {
			try {
				if (!$this -> registry -> isClassLoaded('profunctions')) {
					$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/profunctions.php", 'profunctions', 'promenu');
					$this -> registry -> setClass('profunctions', new $classToLoad($this -> registry));
				}
			} catch( Exception $error ) {
				IPS_exception_error($error);
			}
		} else {
			try {
				if (!$this -> registry -> isClassLoaded('profunctions')) {
					$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/profunctions.php", 'profunctions', 'promenu');
					$this -> registry -> setClass('profunctions', new $classToLoad($this -> registry));
				}
				if (!$this -> registry -> isClassLoaded('promenuHooks')) {
					$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir("promenu") . "/sources/hooks/hooks.php", 'promenuHooks', 'promenu');
					$this -> registry -> setClass('promenuHooks', new $classToLoad($this -> registry));
				}				
			} catch( Exception $error ) {
				IPS_exception_error($error);
			}
		}
	}
}