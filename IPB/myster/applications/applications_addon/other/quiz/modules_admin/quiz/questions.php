<?php

class admin_quiz_quiz_questions extends ipsCommand
{
	public function doExecute(ipsRegistry $registry)
	{
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');
        $this->questions = $this->registry->getClass('questions');
		$this->html = $this->registry->output->loadTemplate('cp_skin_questions');
		$html = $this->registry->output->loadTemplate( 'cp_skin_questions' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_quiz' ) );
		$this->form_code    = $this->html->form_code    = 'module=quiz&amp;section=questions';
		$this->form_code_js = $this->html->form_code_js = 'module=quiz&section=questions';	
		//BEGONE breadcrumb
		$this->registry->output->ignoreCoreNav = TRUE;
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'], IPSLib::getAppTitle( 'quiz' ) );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Quiz Manager" );
		
		switch ($this->request['do'])
		{
			case 'overview':
				$quiz_id = $this->request['id'];
				$this->allQuestions($quiz_id);
				break;
			case 'add':
				$quiz_id = $this->request['id'];
				$this->addQuestion($quiz_id);
				break;
			case 'edit':
				$id = $this->request['id'];
				$this->editQuestion($id);
				break;
			case 'delete':
				$id = $this->request['id'];
				$this->deleteQuestion($id);
			default:
				$quiz_id = $this->request['id'];
				$this->allQuestions($quiz_id);
				break;
		}
		$this->registry->output->sendOutput();
	}
	
	public function allQuestions($quiz_id)
	{
		if ($quiz_id == '0') {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_noquiz'] ), '11QUIZ02', '');
		}
		$questions = $this->questions->findAllByQuiz($quiz_id);
		$quiz = $this->quizzes->findById($quiz_id);
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Questions for ". $quiz['quiz_name'] );
		$parent = $quiz['quiz_name'];
	    $this->registry->output->html .= $this->html->allQuestions($questions, $parent); 
	    $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
	}
	
	public function addQuestion($quiz_id)
	{
		$data = $_POST;
		$quiz = $this->quizzes->findById($quiz_id);
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=add", "Add Quiz" );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Adding Questions to ". $quiz['quiz_name'] );
		
		$questions = $this->questions->multiSelect($quiz_id);
		if ($data) {
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor = new $classToLoad();
			$data['question_name'] = $editor->process( $data['question_name'] );
			$data['question_name'] = IPSText::getTextClass('bbcode')->preDbParse( $data['question_name'] );
			$qid = $this->questions->save($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=add&amp;id='.$qid, $this->lang->words['quiz_acp_question_added'], 2, false, false );
		} else {
			//send template
        	$this->registry->output->html .= $this->html->addQuestion($quiz, $questions); 
        	$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}		
	}
	
	public function editQuestion($id)
	{
		$question	= $this->questions->findById($id);
		$quiz = $this->quizzes->findById($question['quiz_id']);
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Editing Question: ". $question['question_name'] );
		
		$type = $this->request['type'];
		$data		= $_POST;
		if ($data) {
			$this->questions->update($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;id='.$question['question_parent_id'], $this->lang->words['quiz_acp_answer_updated'], 2, false, false );
		} else {
			$this->registry->output->html .= $this->html->editQuestion($question, $quiz, $type);
			$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}
	}
	
	public function deleteQuestion($id)
	{
		$question	= $this->questions->findById($id);
		if (!empty($question)) {
			$this->questions->delete($id);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=questions&amp;id='.$question['quiz_id'], $this->lang->words['quiz_acp_question_deleted'], 2, false, false );
		} else {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_noquestion'] ), '11QUIZ03', ''); // 
			
		}		
	}	
	
}