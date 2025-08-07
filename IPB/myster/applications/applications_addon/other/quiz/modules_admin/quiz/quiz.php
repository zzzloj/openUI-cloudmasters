<?php
// Quiz! Quiz? Quiz.
class admin_quiz_quiz_quiz extends ipsCommand
{
	public function doExecute(ipsRegistry $registry)
	{
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');
        $this->questions = $this->registry->getClass('questions');
		$this->html = $this->registry->output->loadTemplate('cp_skin_quiz');
		$html = $this->registry->output->loadTemplate( 'cp_skin_quiz' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_quiz' ) );
		$this->form_code    = $this->html->form_code    = 'module=quiz&amp;section=quiz';
		$this->form_code_js = $this->html->form_code_js = 'module=quiz&section=quiz';
		#set up editor
		IPSText::getTextClass('bbcode')->parsing_section = 'quiz';
		IPSText::getTextClass('bbcode')->parse_smilies = TRUE;
		IPSText::getTextClass('bbcode')->parse_bbcode = TRUE;
		IPSText::getTextClass('bbcode')->parse_html = FALSE;
		IPSText::getTextClass('bbcode')->parse_nl2br = TRUE;
		IPSText::getTextClass('bbcode')->bypass_badwords = FALSE;
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup = $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->memberData['mgroup_others'];		
		//BEGONE breadcrumb
		$this->registry->output->ignoreCoreNav = TRUE;
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'], IPSLib::getAppTitle( 'quiz' ) );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Quiz Manager" );
		
		switch ($this->request['do'])
		{
			case 'overview':
				$this->allQuiz();
				break;
			case 'add':
				$this->addQuiz();
				break;
			case 'edit':
				$id = $this->request['id'];
				$this->editQuiz($id);
				break;
			case 'delete':
				$id = $this->request['id'];
				$this->deleteQuiz($id);
			case 'moderate':
				$id = $this->request['id'];
				$this->moderateQuiz($id, $type);
				break;
			case 'changecat':
				$id = $this->request['id'];
				$this->changeCategory($id);
				break;
			case 'view':
				$id = $this->request['id'];
				$this->manageQuiz($id);
				break;
			case '':
				$this->allQuiz();
				break;
			default:
				$this->allQuiz();
				break;
		}
		if (empty($this->request['section'])) {
			$this->request['section'] = 'quiz';
			$this->request['do'] = 'overview';
		}
		$this->registry->output->sendOutput();
	}
	
	public function allQuiz()
	{
		$quizzes = $this->quizzes->findAll();
        $this->registry->output->html .= $this->html->allQuiz($quizzes); 
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();		
	}
	
	public function addQuiz()
	{
		// first, build custom nav
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=add", "Add Quiz" );
		// lets get the data
		$data = $_POST;
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		$editor = $_editor->show( 'Quiz' );
		$categories = $this->categories->multiSelect();		
		if ($data) {
			$data['quiz'] = $_editor->process( $_POST['Quiz'] );
			$data['quiz'] = IPSText::getTextClass('bbcode')->preDbParse( $data['quiz'] );
			$qid = $this->quizzes->save($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=questions&amp;id='.$qid, $this->lang->words['quiz_acp_quiz_added'], 2, false, false );
		} else {
			//send template
        	$this->registry->output->html .= $this->html->addQuiz($editor, $categories); 
        	$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}		
	}
	
	public function editQuiz($id)
	{
		// first, get quiz data for custom nav and other stuffs
		$quiz	= $this->quizzes->findById($id);
		// now build custom nav
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=edit&amp;id=".$id, "Editing Quiz: ". $quiz['quiz_name'] );		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		$editor = $_editor->show( 'Quiz', array(), $quiz['quiz'] );
		$categories = $this->categories->multiSelect();
		$data		= $_POST;

		if ($data) {
			$data['quiz'] = $_editor->process( $_POST['Quiz'] );
			$data['quiz'] = IPSText::getTextClass('bbcode')->preDbParse( $data['quiz'] );
			$qid = $this->quizzes->update($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=questions&amp;id='.$qid, $this->lang->words['quiz_acp_quiz_updated'], 2, false, false );
		} else {
			$this->registry->output->html .= $this->html->editQuiz($quiz, $editor, $categories);
			$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}		
	}
	
	public function deleteQuiz($id)
	{
		$quiz	= $this->quizzes->findById($id);
		if (!empty($quiz)) {
			$this->quizzes->delete($id);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=quiz&amp;do=quiz', $this->lang->words['quiz_acp_quiz_deleted'], 2, false, false );
		} else {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_noquiz'] ), '11QUIZ04', ''); // 
			
		}		
	}
	
	public function changeCategory($id)
	{
		$data = $_POST;
		if ($id) {
			$quiz = $this->quizzes->findById($id);
			if ($quiz) {
				$this->DB->update( "quiz_quizzes", array('quiz_category_id' => $data['quiz_category_id']), 'quiz_id='.$quiz['quiz_id'] );     
				$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=quiz&amp;do=edit&amp;id='.$quiz['quiz_id'], $this->lang->words['quiz_acp_category_updated'], 2, false, false );
			}
		}
	}
	
	// one stop shop to do all quizzy stuff
	public function manageQuiz($id)
	{
	    $quiz = $this->quizzes->findById($id);
    	$_questions = $this->questions->findAllByQuiz($quiz['quiz_id']);
    	$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=view&amp;id=".$id, $quiz['quiz_name'] );
    	 
    	if ($_questions) {
	    	foreach ($_questions as $q) {
	    		$answers = $this->questions->findAllAnswersForQuestion($q['question_id']);
	    		$q['answers'] = $answers;
	    		$questions[] = $q;
	    	}
    	}
		$this->registry->output->html .= $this->html->manageQuiz($quiz, $questions);
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		//echo ( '<xmp>' ); print_r( $questions ); echo( '</xmp>' ); exit;
		
	}
	
	public function moderateQuiz($id, $type)
	{
		// $type = lock, pin
	}
	
}