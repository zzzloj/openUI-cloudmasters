<?php 
class quiz_model_answers extends quiz_model_quiz {
	
	public function findAllByKey($key)
	{
		if ($key) {
			$answers = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_answers' => 'a'),
					'where'		=> 'a.answer_key="'.$key.'"',
					'order'		=> 'a.answer_id ASC', 
				)
			);
			$_answer = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($answer = $this->DB->fetch($_answer)) {
	                	$answers[] = $answer;
	            }
	            return $answers;
	        }
		}		
	}
	
	public function findAllByQuiz($quiz_id)
	{
		if ($quiz_id) {
			$answers = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_answers' => 'a'),
					'where'		=> 'a.quiz_id="'.$quiz_id.'"',
					'add_join' 	=> array(
	             		array(
	             			'select' => 'pp.*',
	            			'from' => array('profile_portal' => 'pp'),
	            			'where' => 'pp.pp_member_id = a.answer_user_id',
	            			'type' => 'left',
	             		),
	             		array(
	             			'select' => 'm.*',
	            			'from' => array('members' => 'm'),
	            			'where' => 'm.member_id = a.answer_user_id',
	            			'type' => 'left',
	             		),
             		),
					'order'		=> 'a.answer_id ASC',
             		'group'		=> 'a.answer_id DESC',
				)
			);
			$_answer = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($answer = $this->DB->fetch($_answer)) {
	            		$answer['avatar'] = IPSMember::buildDisplayData($answer['answer_user_id']);
	                	$answers[] = $answer;
	            }
	            return $answers;
	        }			
			
		}		
	}
	
	public function findAllByMember($member_id)
	{
		if ($member_id) {
			$answers = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_answers' => 'a'),
					'where'		=> 'answer_user_id="'.$member_id.'"',
					'add_join' 	=> array(
	             		array(
	             			'select' => 'pp.*',
	            			'from' => array('profile_portal' => 'pp'),
	            			'where' => 'pp.pp_member_id = a.answer_user_id',
	            			'type' => 'left',
	             		),
	             		array(
	             			'select' => 'm.*',
	            			'from' => array('members' => 'm'),
	            			'where' => 'm.member_id = a.answer_user_id',
	            			'type' => 'left',
	             		),
             		),
					'order'		=> 'a.answer_id ASC',
				)
			);
			$_answer = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($answer = $this->DB->fetch($_answer)) {
	                	$answers[] = $answer;
	            }
	            return $answers;
	        }			
			
		}
	}
	
	
	public function insert($data)
	{
		if ($data) {
			IPSLib::doDataHooks( $data, 'quizTakeQuizPreInsert' );
			$this->DB->insert( 'quiz_answers', $data );
			IPSLib::doDataHooks( $data, 'quizTakeQuizPostInsert' );
			$lastId	= $this->DB->getInsertId();
            return $lastId;
		}
	}
	
}