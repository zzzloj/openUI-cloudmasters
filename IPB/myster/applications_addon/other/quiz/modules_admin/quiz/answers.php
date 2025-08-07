<?php

class admin_quiz_quiz_answers extends ipsCommand
{
	public function doExecute(ipsRegistry $registry)
	{
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');
        $this->questions = $this->registry->getClass('questions');
		$this->html = $this->registry->output->loadTemplate('cp_skin_answers');
		$html = $this->registry->output->loadTemplate( 'cp_skin_answers' );
		//$this->registry->class_localization->loadLanguageFile( array( 'admin_quiz' ), 'quiz' );
		$this->lang->loadLanguageFile(array('admin_quiz'), 'quiz');
		$this->form_code    = $this->html->form_code    = 'module=quiz&amp;section=answers';
		$this->form_code_js = $this->html->form_code_js = 'module=quiz&section=answers';	
		//BEGONE breadcrumb
		$this->registry->output->ignoreCoreNav = TRUE;
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'], IPSLib::getAppTitle( 'quiz' ) );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz", "Quiz Manager" );
				
		switch ($this->request['do'])
		{
			case 'overview':
				$question_id = $this->request['id'];
				$this->allAnswers($question_id);
				break;
			case 'add':
				$question_id = $this->request['id'];
				$this->addAnswer($question_id);
				break;
			case 'edit':
				$id = $this->request['id'];
				$this->editAnswer($id);
				break;
			case 'delete':
				$id = $this->request['id'];
				$this->deleteAnswer($id);
			case 'togglestate':
				$id = $this->request['id'];
				$this->toggleAnswerState($id);
				break;
			default:
				$question_id = $this->request['id'];
				$this->allAnswers($question_id);
				break;
		}
		$this->registry->output->sendOutput();
	}
	
	public function allAnswers($question_id)
	{
		$question = $this->questions->findById($question_id);
		$answers = $this->questions->findAllAnswersForQuestion($question_id);
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=view&amp;id=".$question['quiz_id'], $question['quiz_name'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=questions&amp;id=".$question['quiz_id'], "Answers for ". $question['question_name'] );
		
		
        $this->registry->output->html .= $this->html->allAnswers($answers, $question); 
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();		
	}
	
	public function addAnswer($question_id)
	{
		$data = $_POST;
		$question = $this->questions->findById($question_id);
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=quiz&amp;do=view&amp;id=".$question['quiz_id'], $question['quiz_name'] );		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=questions&amp;id=".$question['quiz_id'], $question['question_name'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url']."module=quiz&amp;section=answers&amp;do=add&amp;id=".$question['question_id'], "Add Answers" );
		
		if ($data) {
			$this->questions->save($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=overview&amp;id='.$question_id, $this->lang->words['quiz_acp_answer_added'], 2, false, false );
		} else {
			//send template
        	$this->registry->output->html .= $this->html->addAnswer($question); 
        	$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}		
	}
	
	public function editAnswer($id)
	{
		$question	= $this->questions->findById($id);
		$quiz = $this->quizzes->findById($question['quiz_id']);
		$data		= $_POST;
		if ($data) {
			$this->questions->update($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=overview', $this->lang->words['quiz_acp_answer_updated'], 2, false, false );
		} else {
			$this->registry->output->html .= $this->html->editAnswer($question, $quiz);
			$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}
	}
	
	public function toggleAnswerState($id) {
		$question = $this->questions->findById($id);
		if ($question['question_is_correct'] == 1) {
			// already correct answer, do nothing.
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=overview&amp;id='.$question['question_parent_id'], $this->lang->words['quiz_acp_answer_correctalready'], 2, false, false );
		} else {
			// find the current 'correct' answer and set it to 0. Cannot have more than one right answer at this time.
			$parent = $this->questions->findAllAnswersForQuestion($question['question_parent_id']);
			foreach ($parent as $p) {
				if ($p['question_is_correct'] == 1) {
					// update to 0
					// this is too specific for a model func, don't shoot me.
					$this->DB->update( "quiz_questions", array('question_is_correct' => 0), 'question_id='.$p['question_id'] );     
				}
			}
			// set this answer to be correct
			$this->DB->update( "quiz_questions", array('question_is_correct' => 1), 'question_id='.$question['question_id'] );
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=overview&amp;id='.$question['question_parent_id'], $this->lang->words['quiz_acp_answer_correct'], 2, false, false );
		}
		
	}
	
	public function deleteAnswer($id)
	{
		$question	= $this->questions->findById($id);
		if (!empty($question)) {
			$this->questions->delete($id);
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=answers&amp;do=overview', $this->lang->words['quiz_acp_answer_deleted'], 2, false, false );
		} else {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_noanswer'] ), '11QUIZ01', ''); // 
			
		}		
	}	
	
}