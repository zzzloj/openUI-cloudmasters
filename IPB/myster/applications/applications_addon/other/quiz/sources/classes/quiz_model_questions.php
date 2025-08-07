<?php 
class quiz_model_questions extends quiz_model_quiz {
	
	public function findById($id)
	{
		if ($id) {
			$question = $this->DB->buildAndFetch(
				array(
					'select'		=> '*',
					'from'		=> array('quiz_questions' => 'q'),
					'add_join' 	=> array(
             			array(
             				'select' => 'qq.*',
            				'from' => array('quiz_quizzes' => 'qq'),
            				'where' => 'qq.quiz_id = q.quiz_id',
            				'type' => 'left',
             			),
             		),
					'where'			=> 'q.question_id='.$id,
				)
			);
			return $question;
		}
	}
	
	public function findByParentId($id)
	{
		if ($id) {
			$question = $this->DB->buildAndFetch(
				array(
					'select'		=> '*',
					'from'		=> array('quiz_questions' => 'q'),
					'add_join' 	=> array(
             			array(
             				'select' => 'qq.*',
            				'from' => array('quiz_quizzes' => 'qq'),
            				'where' => 'qq.quiz_id = q.quiz_id',
            				'type' => 'left',
             			),
             		),
					'where'			=> 'q.question_parent_id='.$id,
				)
			);
			return $question;
		}
	}
	
	public function findAllByQuiz($id, $rand = FALSE)
	{
		if ($id) {
			if ($rand == TRUE) {
				$order = "RAND()";
			} else {
				$order = "q.question_id ASC";
			}
			$questions = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_questions' => 'q'),
					'add_join' 	=> array(
             			array(
             				'select' => 'qq.*',
            				'from' => array('quiz_quizzes' => 'qq'),
            				'where' => 'qq.quiz_id = q.quiz_id',
            				'type' => 'left',
             			),
             		),
					'where'		=> 'q.quiz_id='.$id,
					'order'		=> $order, 
				)
			);
			$_question = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($question = $this->DB->fetch($_question)) {
	                	$questions[] = $question;
	            }
	            return $questions;
	        }
		}
	}
	
	public function findAllAnswersForQuestion($id, $rand = FALSE)
	{
		if ($id) {
			if ($rand == TRUE) {
				$order = "RAND()";
			} else {
				$order = "q.question_id ASC";
			}
			$questions = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_questions' => 'q'),
					'where'		=> 'q.question_parent_id='.$id,
					'order'		=> $order, 
				)
			);
			$_question = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($question = $this->DB->fetch($_question)) {
	                	$questions[] = $question;
	            }
	            return $questions;
	        }
		}		
	}
	
    public function multiSelect($id)
    {
    	$questions = $this->findAllByQuiz($id);
    	if (!empty($questions)) {
    		$question[] = array(
    				'','None - Create New Root Question'
    		);
    		foreach ($questions as $q) {
    			$question[] = array(
    				$q['question_id'], $q['question_name']
    			);
    		}
    		return $question;
    	}
    }
	
	public function save($data)
	{
		if ($data) {
			$question = array(
				'question_id'				=> '',
				'question_name'				=> $data['question_name'],
				'question_timestamp'		=> time(),
				'question'					=> $data['question'],
				'quiz_id'					=> $data['quiz_id'],
				'question_parent_id'		=> $data['question_parent_id'],
				'question_is_correct'		=> $data['question_is_correct'],
			);
			IPSLib::doDataHooks( $question, 'quizAddQuestionPreInsert' );
			$this->DB->insert( 'quiz_questions', $question );
			IPSLib::doDataHooks( $question, 'quizAddQuestionPostInsert' );
            $question_id	= $this->DB->getInsertId();
            return $question_id;
		}
	}
	
	public function update($data)
	{
		if ($data) {
			$question = array(
				'question_id'				=> $data['question_id'],
				'question_name'				=> $data['question_name'],
				'question'					=> $data['question'],
				'quiz_id'					=> $data['quiz_id'],
				'question_parent_id'		=> $data['question_parent_id'],
			);			
			$this->DB->update( "quiz_questions", $question, 'question_id='.$data['question_id'] );     
            return true;
		}
	}
	
	public function delete($id)
	{
		if ($id) {
			$this->DB->delete('quiz_questions', 'question_id='.$id);
			return true;
		}
	}	
}
	