<?php 
class quiz_model_quiz {
	
    public function __construct(ipsRegistry $registry)
	{
		$this->registry = $registry;
		$this->DB = $registry->DB();
		$this->cache = $this->registry->cache();
		$this->caches = $this->cache->fetchCaches();
        $this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->lang = $this->registry->getClass('class_localization');
		$this->lang->loadLanguageFile(array('public_lang'), 'quiz');
	}
	
    public function doExecute( ipsRegistry $registry )
	{
		$this->registry =& $registry;
		$this->registry->class_localization->loadLanguageFile( array( 'public_quiz' ) );
	}
	
}

?>