<?php 
class quiz_model_quizzes extends quiz_model_quiz {
	
	public function findAll($limit = array(0,25), $direction = 'DESC')
	{
		$quizzes = array();
		$this->DB->build(
			array(
				'select'	=> 'q.*',
				'from'		=> array('quiz_quizzes' => 'q'),
				'add_join' 	=> array(
             		array(
             			'select' => 'pp.*',
            			'from' => array('profile_portal' => 'pp'),
            			'where' => 'pp.pp_member_id = q.quiz_starter_id',
            			'type' => 'left',
             		),
             		array(
             			'select' => 'm.*',
            			'from' => array('members' => 'm'),
            			'where' => 'm.member_id = q.quiz_starter_id',
            			'type' => 'left',
             		),
             		array(
             			'select' => 'c.*',
            			'from' => array('quiz_categories' => 'c'),
            			'where' => 'c.category_id = q.quiz_category_id',
            			'type' => 'left',
             		),
             	),
				'order'		=> 'quiz_id '.$direction,
             	'limit'		=> $limit,
			)
		);
		$_quizzes = $this->DB->execute();
	    if ($this->DB->getTotalRows()) {
            while ($quiz = $this->DB->fetch($_quizzes)) {
                $quizzes[] = $quiz;
            }
            return $quizzes;
        }		
	}
	// probably not the right model for this..
	public function hasTaken($member_id, $quiz_id) 
	{
		if ((isset($member_id)) && ($member_id != 0) && (isset($quiz_id))) {
			$member = IPSMember::load($member_id, 'all', 'id');
			$quiz = $this->findById($quiz_id);
			if (($member) && ($quiz)) {
				$result = $this->DB->buildAndFetch(
					array(
						'select'	=>	'*',
						'from'		=>	'quiz_answers',
						'where'		=>	'quiz_id ='.$quiz['quiz_id'].' AND answer_user_id='.$member['member_id'],

					)
				);
				
				if (!$result) {
					$hasTaken = '0';	
				} else {
					$hasTaken = '1';
				}
			}
		}
		return $hasTaken;
	}

	public function findPopular($limit = array(0,25), $direction = 'DESC')
	{
		$quizzes = array();
		$this->DB->build(
			array(
				'select'	=> 'a.*',
				'from'		=> array('quiz_answers' => 'a'),
				'add_join' 	=> array(
             		array(
             			'select' => 'q.*',
            			'from' => array('quiz_quizzes' => 'q'),
            			'where' => 'q.quiz_id = a.quiz_id',
            			'type' => 'left',
             		), 			
             		array(
             			'select' => 'pp.*',
            			'from' => array('profile_portal' => 'pp'),
            			'where' => 'pp.pp_member_id = q.quiz_starter_id',
            			'type' => 'left',
             		),
             		array(
             			'select' => 'm.*',
            			'from' => array('members' => 'm'),
            			'where' => 'm.member_id = q.quiz_starter_id',
            			'type' => 'left',
             		),
             		array(
             			'select' => 'c.*',
            			'from' => array('quiz_categories' => 'c'),
            			'where' => 'c.category_id = q.quiz_category_id',
            			'type' => 'left',
             		),            		
             	),
				'where'		=> 'q.quiz_public=1',
             	'group'		=> 'a.quiz_id',
				'order'		=> 'a.quiz_id '.$direction,
             	'limit'		=> $limit,
			)
		);
		$_quizzes = $this->DB->execute();
	    if ($this->DB->getTotalRows()) {
            while ($quiz = $this->DB->fetch($_quizzes)) {
                $quizzes[] = $quiz;
            }
            return $quizzes;
        }		
	}

	public function catCount($category)
	{
		if ($category) {
			if(is_array($category))
			{
				$catId = intval($category['category_id']);
			}
			else
			{
				$catId = intval($category);
			}
			
			$count = $this->DB->buildAndFetch(
					array(
							'select'	=> 'COUNT(quiz_id) as total_quizzes',
							'as'		=> 'total_quizzes',
							'from'		=> 'quiz_quizzes',
							'where'		=> 'quiz_category_id='.$catId.' and quiz_public=1',
					)
			);
			return $count['total_quizzes'];
	
		}
		return 0;
	}
	
	public function oldCatCount($category)
	{
		if ($category) {
			$count = $this->DB->buildAndFetch(
				array(
    				'select' 	=> 'COUNT(*) as count',
					'as'		=> 'count',
    				'from'		=> 'quiz_quizzes',
    				'where'		=> 'quiz_category_id='.$category['category_id'].' and quiz_public=1',
				)
			);
			return $count;

		}
	}
	
	public function findById($id)
	{
		if ($id) {
			$quiz = $this->DB->buildAndFetch(
				array(
					'select'	=> 'q.*',
					'from'		=> array('quiz_quizzes' => 'q'),
					'where'		=> 'q.quiz_id='.$id,
				    'add_join' 	=> array(
             			array(
             				'select' => 'pp.*',
            				'from' => array('profile_portal' => 'pp'),
            				'where' => 'pp.pp_member_id = q.quiz_starter_id',
            				'type' => 'left',
             			),
             			array(
             				'select' => 'm.*',
            				'from' => array('members' => 'm'),
            				'where' => 'm.member_id = q.quiz_starter_id',
            				'type' => 'left',
             			),
             			array(
             				'select' => 'c.*',
            				'from' => array('quiz_categories' => 'c'),
            				'where' => 'c.category_id = q.quiz_category_id',
            				'type' => 'left',
             			),
             		),
				)
			);
			return $quiz;
		}
	}
	
	public function findByCategory($category)
	{
		if ($category) {
			$quizzes = array();
			$this->DB->build(
				array(
					'select'	=> 'q.*',
					'from'		=> array('quiz_quizzes' => 'q'),
					'add_join' 	=> array(
	             		array(
	             			'select' => 'pp.*',
	            			'from' => array('profile_portal' => 'pp'),
	            			'where' => 'pp.pp_member_id = q.quiz_starter_id',
	            			'type' => 'left',
	             		),
	             		array(
	             			'select' => 'm.*',
	            			'from' => array('members' => 'm'),
	            			'where' => 'm.member_id = q.quiz_starter_id',
	            			'type' => 'left',
	             		),
	             		array(
	             			'select' => 'c.*',
	            			'from' => array('quiz_categories' => 'c'),
	            			'where' => 'c.category_id = q.quiz_category_id',
	            			'type' => 'left',
	             		),
	             	),
	             	'where'		=> 'quiz_category_id='.$category,
					'order'		=> 'quiz_id ASC',
	             	'limit'		=> $limit,
				)
			);
			$_quizzes = $this->DB->execute();
		    if ($this->DB->getTotalRows()) {
	            while ($quiz = $this->DB->fetch($_quizzes)) {
	                $quizzes[] = $quiz;
	            }
	            return $quizzes;
	        }
		}
	}
    	
	public function save($data)
	{
		if ($data) {
			$quiz = array(
				'quiz_id'				=> '',
				'quiz_name'				=> $data['quiz_name'],
				'quiz_seotitle'			=> IPSText::makeSeoTitle($data['quiz_name']),
				'quiz_timestamp'		=> time(),
				'quiz_starter_id'		=> $this->memberData['member_id'],
				'quiz'					=> $data['quiz'],
				'quiz_category_id'		=> $data['quiz_category_id'],
				'quiz_approved'			=> 0, // unapproved/hidden until we add questions
				'quiz_promote_group_id'	=> $data['quiz_promote_group_id'],
				'quiz_group_promo_score'=> $data['quiz_group_promo_score'],
				'quiz_timelimit'		=> $data['quiz_timelimit'],
			);
			IPSLib::doDataHooks( $quiz, 'quizAddQuizPreInsert' );
			$this->DB->insert( 'quiz_quizzes', $quiz );
			IPSLib::doDataHooks( $quiz, 'quizAddQuizPostInsert' );
            $quiz_id	= $this->DB->getInsertId();
            return $quiz_id;
		}
	}
	
	public function update($data)
	{
		if ($data) {
			$quiz = array(
				'quiz_id'				=> $data['quiz_id'],
				'quiz_name'				=> $data['quiz_name'],
				'quiz_seotitle'			=> IPSText::makeSeoTitle($data['quiz_name']),
				'quiz_timestamp'		=> time(),
				'quiz_starter_id'		=> $this->memberData['member_id'],
				'quiz'					=> $data['quiz'],
				//'quiz_category_id'		=> $data['quiz_category_id'],
				'quiz_promote_group_id'	=> $data['quiz_promote_group_id'],
				'quiz_group_promo_score'=> $data['quiz_group_promo_score'],
				'quiz_timelimit'		=> $data['quiz_timelimit'],
			);			
			$this->DB->update( "quiz_quizzes", $quiz, 'quiz_id='.$data['quiz_id'] );
			$quiz_id	= $data['quiz_id'];
            return $quiz_id;     
		}
	}
	
	public function delete($id)
	{
		if ($id) {
			$this->DB->delete('quiz_quizzes', 'quiz_id='.$id);
			return true;
		}
	}
	
}
	