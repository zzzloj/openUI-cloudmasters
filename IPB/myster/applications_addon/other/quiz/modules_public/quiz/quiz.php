<?php
// Quiz Quiz Quiz!!!
class public_quiz_quiz_quiz extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        $this->lang->loadLanguageFile(array('public_lang'), 'quiz');
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');
		$this->questions = $this->registry->getClass('questions');
		$this->answers = $this->registry->getClass('answers');
		$this->leaders = $this->registry->getClass('leaders');
        // what are we doing	
        switch( $this->request['do'] )
		{
		  case 'view':
		  	$id = $this->request['id'];
		  	$this->showQuiz($id);
		  	break;
		  case 'takequiz':
		  	$id = $this->request['id'];
		  	$this->takeQuiz($id);
		  	break;
		  case 'viewresults':
		  	$key = $this->request['key'];
		  	$quizid = $this->request['id'];
		  	$this->getResults($key, $quizid);
		  	break;
		  case 'addQuiz':
		  	$this->addQuiz();
		  	break;
		  case 'addQuestions':
		  	$quiz_id = $this->request['qid'];
		  	$this->addQuestions($quiz_id);
		  	break;
		  case 'addAnswers':
		  	$question_id = $this->request['qid'];
		  	$this->addAnswers($question_id);
		  	break;
		  case 'manage':
		  	$quiz_id = $this->request['qid'];
		  	$this->manageQuiz($quiz_id);
		  	break;
		case 'togglestate':
			$id = $this->request['id'];
			$this->toggleAnswerState($id);
			break;
		case 'approve':
			$id = $this->request['qid'];
			$this->publishQuiz($id);
			break;
		case 'publish':
			$id = $this->request['qid'];
			$this->publishQuiz($id);
			break;
		case 'deleteanswer':
			$id = $this->request['id'];
			$this->deleteAnswer($id);
			break;
		case 'nojs':
			$this->noJS();
			break;
        }
        
        $this->registry->output->sendOutput();
    }
    
    public function publishQuiz($quiz_id)
    {
    	$quiz = $this->quizzes->findById($quiz_id);
    	if ($quiz) {
    		if ($quiz['member_id'] == $this->memberData['member_id'] || $this->memberData['g_is_supmod'] == '1') {
	    		// lock to further questions and disable the "Manage" screen.
				$this->DB->update( "quiz_quizzes", array('quiz_public' => 1), 'quiz_id='.$quiz['quiz_id'] ); 
    			// post the topic
				if (($this->settings['quiz_post_new_topic'] == '1') && (!empty($this->settings['quiz_post_to_forum_id']))) {
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/topics.php', 'topics', 'quiz' );
					$_topics		= new $classToLoad( $this->registry );
					$_topics->postNewTopic( $quiz );
				}
				// approve the quiz
				if ($quiz['quiz_approved'] == '0') {
	    			$this->toggleQuizState($quiz['quiz_id'], FALSE);
				}
				// redirect to quiz
				$this->registry->output->redirectScreen( $this->lang->words['quiz_published'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz' );
    		}
    	}
    }
    
    public function addQuiz()
    {
    	if ($this->memberData['g_quiz_can_add_quiz'] != 1) {
	    	$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_cannot_add_quizzes'] ), '10QUIZ04', ''); // 
	    }
    	$data = $_POST;
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		$editor = $_editor->show( 'Quiz' );
		$categories = $this->categories->findAll();
		if ($categories) {
			if ($data) {
				$ipbVersion = ipsRegistry::$applications['core']['app_version'];
				if ($ipbVersion > 33015) {
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/text/parser.php', 'classes_text_parser' );
					$parser = new $classToLoad();
					$data['quiz'] = $_editor->process( $_POST['Quiz'] );
					// The new parsing engine removes the 'preEditParse', 'preDisplayParse' and 'preDBParse' methods in favour of the following:
				} else {
					$data['quiz'] = $_editor->process( $_POST['Quiz'] );
					$data['quiz'] = IPSText::getTextClass('bbcode')->preDbParse( $data['quiz'] );
				}
				
				if ($data['quiz']) {
					$qid = $this->quizzes->save($data);
				} else {
					$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_bad_empty_description'] ), '10QUIZ01', ''); // 
				}
				$this->registry->output->redirectScreen( $this->lang->words['quiz_added'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=manage&amp;qid='.$qid );
			} else {
				// title and nav
		    	$this->registry->output->setTitle($this->lang->words['quiz_add_quiz'] .' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
		    	$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
		    	$this->registry->output->addNavigation($this->lang->words['quiz_add_quiz'], 'app=quiz', 'quiz', 'quiz');
		    	// send template
		    	$template = $this->registry->output->getTemplate('quiz')->addQuiz($editor, $categories);
		    	$this->registry->output->addContent($template);
			}
		} else {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_no_categories_you_cannot'] ), '10QUIZ07', ''); // 
		}
    }
    
    public function manageQuiz($quiz_id)
    {
    	$quiz = $this->quizzes->findById($quiz_id);
    	if ($quiz['quiz_public'] == '1') {
    		$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_locked_to_editting'] ), '10QUIZ06', ''); // 
    	}
    	$_questions = $this->questions->findAllByQuiz($quiz['quiz_id']);
    	$quiz['quiz'] = IPSText::getTextClass('bbcode')->preDisplayParse( $quiz['quiz'] );
    	if ($_questions) {
	    	foreach ($_questions as $q) {
	    		$answers = $this->questions->findAllAnswersForQuestion($q['question_id']);
	    		$q['answers'] = $answers;
	    		$questions[] = $q;
	    	}
    	}
    	if ($quiz['member_id'] == $this->memberData['member_id'] || $this->memberData['g_is_supmod'] == '1') {
	    	$this->registry->output->setTitle($quiz['quiz_name'].' - '.$quiz['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
		    $this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');	    	
			$this->registry->output->addNavigation($quiz['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$quiz['category_id'], $quiz['category_seotitle'], 'quizcat');
		    $this->registry->output->addNavigation($quiz['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz');
		    $this->registry->output->addNavigation($this->lang->words['quiz_manage_quiz']);
		    $template = $this->registry->output->getTemplate('quiz')->manageQuiz($quiz, $questions, $answer);
		    $this->registry->output->addContent($template);
    	} else {
    		$this->registry->showError(	'no_permission'	);
    	}
    }
    
    public function addQuestions($quiz_id)
    {
    	$data = $_POST;
		$quiz = $this->quizzes->findById($quiz_id);
		$questions = $this->questions->findAllByQuiz($quiz['quiz_id']);
		if ($data) {
			$qid = $this->questions->save($data);
			$this->registry->output->redirectScreen( $this->lang->words['quiz_question_added'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addAnswers&amp;qid='.$qid );
		} else {
			// title and nav
	    	$this->registry->output->setTitle($quiz['quiz_name'].' - '.$quiz['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
	    	$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');	    	
			$this->registry->output->addNavigation($quiz['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$quiz['category_id'], $quiz['category_seotitle'], 'quizcat');
	    	$this->registry->output->addNavigation($quiz['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz');
	    	$this->registry->output->addNavigation($this->lang->words['quiz_questions']);
	    	// send template
	    	$template = $this->registry->output->getTemplate('quiz')->addQuestion($quiz, $questions);
	    	$this->registry->output->addContent($template);
		}    	
    }
    
    public function addAnswers($question_id)
    {
    	$data = $_POST;
		$question = $this->questions->findById($question_id);
    	$questions = $this->questions->findAllAnswersForQuestion($question_id);
		if ($data) {
			$this->questions->save($data);
			$this->registry->output->redirectScreen( 'Answer Added!', $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addAnswers&amp;qid='.$question_id);
		} else {
			// title and nav
	    	$this->registry->output->setTitle($question['question_name'].' - '.$question['quiz_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
	    	$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
	    	$this->registry->output->addNavigation($question['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$question['quiz_id'], $question['quiz_seotitle'], 'quizquiz');
	    	$this->registry->output->addNavigation($question['question_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addQuestions&amp;qid='.$question['quiz_id']);
	    	$this->registry->output->addNavigation($this->lang->words['quiz_answers']);
	    	
	    	// send template
	    	$template = $this->registry->output->getTemplate('quiz')->addAnswer($question, $questions);
	    	$this->registry->output->addContent($template);
		}    	
    }
    
    public function getResults($key, $id) 
    {
    	$results = $this->answers->findAllByKey($key);
    	//echo '<xmp>'; print_r($results); echo '</xmp>'; exit;
    	$total = count($results);
    	$z = 0;
    	if ($results) {
	    	foreach ($results as $r) {
	    		if ($r['is_correct_answer'] == 1) {
	    			$z++;
	    		}
	    	}
	    	
	    	$count1 = $z / $total;
	    	$count2 = $count1 * 100;
	    	$percentage = number_format($count2, 0);
    	}
    	$quiz = $this->quizzes->findById($id);
    	// title and nav
    	$this->registry->output->setTitle($quiz['quiz_name'].' - '.$quiz['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
    	$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
    	$this->registry->output->addNavigation($quiz['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$quiz['category_id'], $quiz['category_seotitle'], 'quizcat');
    	$this->registry->output->addNavigation($quiz['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz');
    	$this->registry->output->addNavigation($this->lang->words['quiz_results']);
    	$leaders = $this->leaders->findAllByQuiz($quiz['quiz_id']);
    	// send template
    	$template = $this->registry->output->getTemplate('quiz')->showResults($results, $percentage, $key, $leaders);
    	$this->registry->output->addContent($template);
    }
    
    public function takeQuiz($id)
    {
    	if ($this->memberData['g_quiz_can_take_quiz'] != 1) {
	    	$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_cannot_take_quizzes'] ), '10QUIZ02', ''); // 
	    }
    	$data = $_POST;
   		$starttime = time(); 
    	if ($data['quiz_id']) {
    		// a unique key per quiz "session" to allow us to find results by userid and quiz id, but also allow users to 'play' a quiz more than once..
    		// @todo fffv
    		$key = md5($this->memberData['member_id'] . time());
    		// separate question and answer for usability.
    		foreach ($data as $_question => $answer) {
    			if ($answer != $id) { // exclude quiz_id
    				// get raw question id
    				$_question = str_replace('question_', '', $_question);
    				// find all answers for that question id
    				$question = $this->questions->findAllAnswersForQuestion($_question, TRUE);
    				// loop through answers for correct one
    				foreach ($question as $_answer) {
    					// got correct answer
    					if ($_answer['question_is_correct'] == 1) {
    						// check against our answer id
    						if ($_answer['question_id'] == $answer) {
    							// is correct?
    							$correct = true;
    							
    						} else {
    							$correct = false;
    						}
    						$correct_answer_name = $_answer['question_name'];
    					}
    				}  				
			    	
    				$you_answered = $this->questions->findById($answer);
    				$the_question = $this->questions->findById($_question);
    				// build our answers array for saving
    				$answer = array(
    					'answer_user_id'		=> $this->memberData['member_id'],
    					'quiz_id'				=> $data['quiz_id'],
    					'question_id'			=> $_question,
    					'is_correct_answer'		=> $correct,
    					'correct_answer_name'	=> $correct_answer_name,
    					'answer_key'			=> $key,
    					'timestamp'				=> $starttime,
    					'answer_name'			=> $the_question['question_name'],
    					'you_answered'			=> $you_answered['question_name'],
    					//'answer_score'		=> $percentage,
    					
    				);

       				// save
    				$aid = $this->answers->insert($answer);
    				if ($this->settings['quiz_approve_quiz_upon_answers_add'] == '1') {
    					$this->toggleQuizState($data['quiz_id']);
    				}
					// on to the next one... 
    			}
    		}
    		$results = $this->answers->findAllByKey($key);
    		$total = count($results);
    		$z = 0;
    		foreach ($results as $r) {
    			if ($r['is_correct_answer'] == 1) {
    				$z++;
    			}
    		}
    		$count1 = $z / $total;
    		$count2 = $count1 * 100;
    		$percentage = number_format($count2, 0);

    		if (empty($percentage)) {
    			$percentage = '0';
    		}    		
    		$board = array(
    				'quiz_id'		=>	$data['quiz_id'],
    				'user_id'		=>	$this->memberData['member_id'],
    				'score'			=>	$percentage,
    				'date'			=>	$starttime,
    				'answer_key'	=>	$key,
    		);
			// save leaderboard
    		$this->leaders->insert($board);
    		// load quiz stuff for stuff below. stuff.
    		$quiz = $this->quizzes->findById($id);
    		// promote stuff
    		if ($this->settings['quiz_enable_group_promo'] == '1') {
    			if (($quiz['quiz_promote_group_id'] != 0) && ($quiz['quiz_group_promo_score'] != 0)) {
    				if ($percentage >= $quiz['quiz_group_promo_score']) { // if the percentage is greater than or equal to what we have set.. promote
						// use the IPSMember hijinks.
    					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_group_id' => intval($quiz['quiz_promote_group_id']) ) ) );
    				}
    			}
    		}
    		// send notification
    		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/notifications.php', 'quiz__notifications_wrapper', 'quiz' );
    		$notifications  = new $classToLoad( $this->registry );
    		$_url = $this->registry->output->buildSEOUrl( 'app=quiz&module=quiz&section=quiz&do=view&id='.$quiz['quiz_id'], 'public', $quiz['quiz_seotitle'], 'quizquiz' );
    		$_rurl = $this->registry->output->buildSEOUrl( 'app=quiz&module=quiz&section=quiz&do=viewresults&key='.$key.'&id='.$quiz['quiz_id']);
    		$_title = $this->memberData['members_display_name'].' just scored <a href="'.$_rurl.'">'.$percentage.'</a>&#37; on your quiz: <a href="'.$_url.'">'.$quiz['quiz_name'].'</a>';
    		//$_member = IPSMember::load($quiz['quiz_starter_id'],'all','id');
    		$notifications->buildAndSendNotification($_title, $_title, $quiz['quiz_starter_id'], 'quiz__member_taken_quiz', $_url);
    		// redirect to showResults template. Pass an array of answers found by $key so we can display results / percentages etc.
			$this->registry->output->redirectScreen( $this->lang->words['quiz_answers_submitted'], $this->settings['base_url'] .'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=viewresults&amp;key='.$key.'&amp;id='.$id, false, 'quizresults' );

    	} else {
	    	if ($id) {
	    		$quiz = $this->quizzes->findById($id);
	    		$_questions = $this->questions->findAllByQuiz($quiz['quiz_id'], TRUE);
	    		if ($this->settings['quiz_takequiz_more_than_once'] == 0) {
	    			$quiz['memberHasTakenQuiz'] = $this->quizzes->hasTaken($this->memberData['member_id'], $quiz['quiz_id']);
	    			if ($quiz['memberHasTakenQuiz'] == 1) {
	    				$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_already_taken'] ), '10QUIZ02', ''); //
	    			}
	    		}
	    		if ($_questions) {
		    		foreach ($_questions as $q) {
		    			$answers = $this->questions->findAllAnswersForQuestion($q['question_id'], TRUE);
		    			if ($answers) {
		    				$q['answers'] = $answers;
		    				$questions[] = $q;
		    			} else {
		    				$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_no_answers_added_yet'] ), '10QUIZ08', ''); //
		    			}
		    		}
	    		}    		
	    		$this->registry->output->setTitle($quiz['quiz_name'].' - '.$quiz['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
				$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
				$this->registry->output->addNavigation($quiz['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$quiz['category_id'], $quiz['category_seotitle'], 'quizcat');
				$this->registry->output->addNavigation($quiz['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz');
				$this->registry->output->addNavigation($this->lang->words['quiz_taking_quiz'] . $quiz['quiz_name']);
				$template = $this->registry->output->getTemplate('quiz')->takeQuiz($quiz, $questions);
				$this->registry->output->addContent($template);
	    	}
    	}
    }

	public function toggleAnswerState($id) {
		$question = $this->questions->findById($id);
		if ($question['question_is_correct'] == 1) {
			// already correct answer, do nothing.
			$this->registry->output->redirectScreen( 'Answer Is Already Correct!', $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addAnswers&amp;qid='.$question['question_parent_id'], 2, false, false );
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
			$this->registry->output->redirectScreen( $this->lang->words['quiz_marked_correct'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addAnswers&amp;qid='.$question['question_parent_id'] );
		}
		
	}
	
	public function toggleQuizState($id, $noredirect = FALSE)
	{
		$quiz = $this->quizzes->findById($id);
		if ($quiz['quiz_approved'] == '1') {
			$this->DB->update( "quiz_quizzes", array('quiz_approved' => 0), 'quiz_id='.$quiz['quiz_id'] );     
			if ($noredirect == FALSE) {
				$this->registry->output->redirectScreen( $this->lang->words['quiz_unapproved'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz' );				
			}
		} else {
			$this->DB->update( "quiz_quizzes", array('quiz_approved' => 1), 'quiz_id='.$quiz['quiz_id'] ); 
			if ($noredirect == FALSE) {    
				$this->registry->output->redirectScreen( $this->lang->words['quiz_unapproved'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz' );			
			}
		}
	}
	
	public function toggleQuizQuestionsLock($id, $noredirect = FALSE)
	{
		$quiz = $this->quizzes->findById($id);	
		if ($quiz['quiz_public'] == '1') {
			$this->DB->update( "quiz_quizzes", array('quiz_public' => 0), 'quiz_id='.$quiz['quiz_id'] );     
			if ($noredirect == FALSE) {
				$this->registry->output->redirectScreen( $this->lang->words['quiz_open_for_questions'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz' );				
			}
		} else {
			$this->DB->update( "quiz_quizzes", array('quiz_public' => 1), 'quiz_id='.$quiz['quiz_id'] ); 
			if ($noredirect == FALSE) {    
				$this->registry->output->redirectScreen( $this->lang->words['quiz_closed_for_questions'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz' );			
			}
		}		
	}
	
	public function deleteAnswer($id)
	{
		if ($id) {
			$_question = $this->questions->findById($id);
			$question = $this->questions->findById($_question['question_parent_id']);
			$this->questions->delete($id);
			$this->registry->output->redirectScreen($this->lang->words['quiz_answer_deleted'], $this->settings['base_url'].'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=addAnswers&amp;qid='.$question['question_id']);
		}
	}
    
    public function showQuiz($id) 
    {
    	if ($id) {
    		if ($this->memberData['g_quiz_can_view_quiz'] == 1) {
	    		$quiz = $this->quizzes->findById($id);
	    		$takers = $this->leaders->findAllByQuiz($quiz['quiz_id']);
	    		if ($this->settings['quiz_takequiz_more_than_once'] == 0) {
	    			$quiz['memberHasTakenQuiz'] = $this->quizzes->hasTaken($this->memberData['member_id'], $quiz['quiz_id']);
	    		}
	    		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
				$editor = new $classToLoad();
				$ipbVersion = ipsRegistry::$applications['core']['app_version'];
				if ($ipbVersion > 33015) {
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/text/parser.php', 'classes_text_parser' );
					$parser = new $classToLoad();
					$data['quiz'] =  $parser->display( $quiz['quiz'] );
				} else {
					$quiz['quiz'] = IPSText::getTextClass('bbcode')->preDisplayParse( $quiz['quiz'] );
				}
	    		
	    		$quiz['avatar'] = IPSMember::buildDisplayData($quiz['quiz_starter_id']);
	    		$quiz['avatar']['signature'] = IPSText::getTextClass('bbcode')->preDisplayParse($quiz['avatar']['signature']);
	    		$_questions = $this->questions->findAllByQuiz($quiz['quiz_id']);
	    		if ($_questions) {
		    		foreach ($_questions as $q) {
		    			$answers = $this->questions->findAllAnswersForQuestion($q['question_id']);
		    			if ($answers) {
			    			$q['answers'] = $answers;
			    			$questions[] = $q;
		    			} else {
		    				$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_no_answers_added_yet'] ), '10QUIZ08', '');
		    			}
		    		}    			
	    		}
	    		// yes I'm using $this->DB outside of my model. So shoot me, this is a specific purpose damnit! *sob* I'll move it to a model func later. @todo
	    		$quiz['key'] = $this->DB->buildAndFetch(
			    					array(
			    						'select'	=>	'answer_key as "key"',
			    						'from'		=>	'quiz_answers',
			    						'where'		=>	'quiz_id='.$quiz['quiz_id'].' and answer_user_id='.$this->memberData['member_id'],
			    						'limit'		=>	array(0,1), // get only one result
			    						'order'		=>	'timestamp DESC', // get only the latest result
			    					)
	    						);
	    		
	    		if (($quiz['quiz_approved'] != '1') && ($quiz['quiz_starter_id'] != $this->memberData['member_id']) && ($this->memberData['g_is_supmod'] == '0')) {
	    			$this->registry->output->showError(	'no_permission'	);
	    		}
	    		
	    		$this->registry->output->setTitle($quiz['quiz_name'].' - '.$quiz['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
				$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
				$this->registry->output->addNavigation($quiz['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$quiz['category_id'], $quiz['category_seotitle'], 'quizcat');
				$this->registry->output->addNavigation($quiz['quiz_name'], 'app=quiz&amp;module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz['quiz_id'], $quiz['quiz_seotitle'], 'quizquiz');
	    		$template = $this->registry->output->getTemplate('quiz')->showQuiz($quiz, $questions, $takers);
				$this->registry->output->addContent($template);
	    	} else {
	    		$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_cannot_view_quiz'] ), '10QUIZ03', ''); // 
	    	}
    	}
    }
    
    public function noJS()
    {
    	$this->registry->output->setTitle($this->lang->words['quiz_nojs']. ' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
    	$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
    	$this->registry->output->addNavigation($this->lang->words['quiz_nojs'], 'app=quiz', 'quiz', 'quiz');
    	$template = $this->registry->output->getTemplate('quiz')->noJS();
    	$this->registry->output->addContent($template);
    }
    
}