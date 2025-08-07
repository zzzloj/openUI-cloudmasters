<?php 
class quiz_model_leaders extends quiz_model_quiz {
	
	public function buildLeaderBoard() 
	{
		
	}
	
	public function findAllByKey($key)
	{
		if ($key) {
			$answers = array();
			$this->DB->build(
				array(
					'select'	=> '*',
					'from'		=> array('quiz_leaders' => 'l'),
					'where'		=> 'l.answer_key="'.$key.'"',
					'add_join'	=> array(
						array(
							'select'	=>	'*',
							'from'		=>	array('quiz_answers' => 'a'),
							'where'		=>	'a.answer_key = l.answer_key',
							'type'		=>	'left',
						)
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
	
	public function findAllByQuiz($quiz_id)
	{
		if ($quiz_id) {
			$answers = array();
			$this->DB->build(
				array(
					'select'	=> 'l.*',
					'from'		=> array('quiz_leaders' => 'l'),
					'where'		=> 'l.quiz_id="'.$quiz_id.'"',
					'add_join' 	=> array(
	             		array(
	             			'select'	=>	'pp.*',
	            			'from'		=>	array('profile_portal' => 'pp'),
	            			'where'		=>	'pp.pp_member_id = l.user_id',
	            			'type' 		=>	'left',
	             		),
	             		array(
	             			'select'	=>	'm.*',
	            			'from'		=>	array('members' => 'm'),
	            			'where'		=>	'm.member_id = l.user_id',
	            			'type'		=>	'left',
	             		),
	             		/** array(
							'select'	=>	'a.*',
							'from'		=>	array('quiz_answers' => 'a'),
							'where'		=>	'a.quiz_id = l.quiz_id',
							'type'		=>	'left',
						),
						array(
							'select'	=>	'q.*',
							'from'		=>	array('quiz_quizzes' => 'q'),
							'where'		=>	'q.quiz_id = l.quiz_id',
							'type'		=>	'left',
						) **/
             		),
					'order'		=>	'l.score DESC, l.date ASC', // order by highest scores on the oldest dates
             		'group'		=>	'l.id',
				)
			);
			$_answer = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($answer = $this->DB->fetch($_answer)) {
	            		$answer['avatar'] = IPSMember::buildDisplayData($answer['user_id']);
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
					'select'	=> 'l.*',
					'from'		=> array('quiz_leaders' => 'l'),
					'where'		=> 'l.user_id="'.$member_id.'"',
					'add_join' 	=> array(
	             		array(
	             			'select'	=>	'pp.*',
	            			'from'		=>	array('profile_portal' => 'pp'),
	            			'where' 	=>	'pp.pp_member_id = l.user_id',
	            			'type'		=>	'left',
	             		),
	             		array(
	             			'select'	=>	'm.*',
	            			'from'		=>	array('members' => 'm'),
	            			'where'		=>	'm.member_id = l.user_id',
	            			'type'		=>	'left',
	             		),
	             		array(
							'select'	=>	'*',
							'from'		=>	array('quiz_answers' => 'a'),
							'where'		=>	'a.answer_user_id = l.user_id',
							'type'		=>	'left',
						),
             		),
					'order'		=> 'l.score DESC, l.date ASC', 
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
			IPSLib::doDataHooks( $data, 'quizLeadersPreInsert' );
			$this->DB->insert( 'quiz_leaders', $data );
			IPSLib::doDataHooks( $data, 'quizLeadersPostInsert' );
			$lastId	= $this->DB->getInsertId();
            return $lastId;
		}
	}
	
}