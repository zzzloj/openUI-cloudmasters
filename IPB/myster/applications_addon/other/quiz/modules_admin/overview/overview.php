<?php
class admin_quiz_overview_overview extends ipsCommand
{

	public function doExecute(ipsRegistry $registry)
	{
		$this->categories = $this->registry->getClass('categories');
		$this->quizzes = $this->registry->getClass('quizzes');
		$this->html = $this->registry->output->loadTemplate('cp_skin_quiz');
		$html = $this->registry->output->loadTemplate( 'cp_skin_quiz' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_categories' ) );
		$this->form_code    = $this->html->form_code    = 'module=overview&amp;section=overview';
		$this->form_code_js = $this->html->form_code_js = 'module=overview&section=overview';
		
		switch ($this->request['do'])
		{
			case 'overview':
				$this->overview();
				break;
			default:
				$this->overview();
				break;
		}
		
		$this->registry->output->sendOutput();
	}
	
	public function overview()
	{
		$quizzes = $this->quizzes->findAll(array(0,10));
		$categories = $this->categories->findAll(array(0,10), ASC);
		$this->registry->output->html .= $this->html->overview($quizzes, $categories); 
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
	}

}


?>