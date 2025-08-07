<?php
class admin_quiz_quiz_importexport extends ipsCommand
{
	public function doExecute(ipsRegistry $registry)
	{
        $this->lang->loadLanguageFile(array('public_lang'), 'quiz');
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');
		$this->questions = $this->registry->getClass('questions');
		$this->answers = $this->registry->getClass('answers');
		$this->leaders = $this->registry->getClass('leaders');
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_quiz_importexport');
		$html = $this->registry->output->loadTemplate( 'cp_skin_quiz_importexport' );
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
	
		switch ($this->request['do'])
		{
			case 'overview':
				$this->overview();
				break;
			case 'import':
				$this->importQuiz();
				break;
			case 'export':
				$this->exportQuiz();
				break;
			default:
				$this->overview();
				break;
		}
		if (empty($this->request['section'])) {
			$this->request['section'] = 'importexport';
			$this->request['do'] = 'overview';
		}
		$this->registry->output->sendOutput();
	}
    //http://community.invisionpower.com/resources/doxygen/classadmin_output.html#ae6e4f228eaee437d7411d164417d24c1
    //http://community.invisionpower.com/resources/documentation/index.html/_/developer-resources/api-methods/kernel-xml-archives-classxmlarchivephp-r705
	
	public function overview() 
	{
		$categories = $this->categories->multiSelect();
		$quizzes = $this->quizzes->findAll();
		$this->registry->output->html .= $this->html->import_overview($quizzes, $categories);
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
	}
	
	public function importQuiz()
	{
		$filename = $_FILES['FILE_UPLOAD']['name'];
		$filename = preg_replace( "#\.xml$#", "", $filename );
		
		$data  = ipsRegistry::getClass('adminFunctions')->importXml( $filename );
		if ($data) {
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
			$xml->loadXML( $data );
			$_quizzes = array();
			$_XML = $xml->fetchXMLAsArray();
			
			$quiz = $_XML['quizexport']['quizzes']['quiz']['quiz_id'];
			// build out the SHIT from the XML, Jesus fucking christ why am I doing this shit
			$quiz_data['quiz_name'] 		= $quiz['quiz_name']['#alltext'];
			$quiz_data['quiz_seotitle'] 	= $quiz['quiz_seotitle']['#alltext'];
			$quiz_data['quiz'] 				= $quiz['quiz']['#alltext'];
			$quiz_data['quiz_timelimit'] 	= $quiz['quiz_timelimit']['#alltext'];
			$quiz_data['quiz_category_id'] 	= $_POST['quiz_category_id']; 
			// we have the quiz data, so save it.
			$quiz_lastid = $this->quizzes->save($quiz_data);
			// now get the question 
			$questions = $_XML['quizexport']['quizzes']['quiz']['questions']['question_id'];
			foreach ($questions as $question) {
				$question_data['original_question_id']	= $question['@attributes']['id'];
				$question_data['question_name'] 		= $question['question_name']['#alltext'];
				$question_data['quiz_id']				= $quiz_lastid; 
				//echo ('<xmp>'); print_r( $question_data );echo ('</xmp>'); exit;
				$question_lastid = $this->questions->save($question_data);
				// now for the answers.
				$answers = $_XML['quizexport']['quizzes']['quiz']['questions']['answers']['answer_id'];
				foreach ($answers as $answer) {
					if ($answer['question_parent_id']['#alltext'] == $question_data['original_question_id']) {
						$answer_data['question_name']		=	$answer['question_name']['#alltext'];
						$answer_data['question_parent_id']	=	$question_lastid; 
						$answer_data['question_is_correct']	=	$answer['question_is_correct']['#alltext'];
						$answer_lastid = $this->questions->save($answer_data);
					}
					
				}
			}
			//echo ('<xmp>'); print_r( $xml->fetchXMLAsArray() );echo ('</xmp>'); exit;
			// baddabing, baddaboom, we have a mothafuckin quiz.
			$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=quiz&amp;do=view&amp;id='.$quiz_lastid, 'Quiz Imported. Redirecting you to management screen.', 2, false, false );
					
		}
	}
	
	public function exportQuiz()
	{
		$data = $_POST;
		if ($data) {
			require_once( IPS_KERNEL_PATH.'classXML.php' );
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();

			$quiz = $this->quizzes->findById($data['quiz']);
			$questions = $this->questions->findAllByQuiz($quiz['quiz_id']); // etc
			
			$xml->addElement( 'quizexport' );
			$xml->addElement( 'quizzes', 'quizexport' );
			$xml->addElement( 'quiz', 'quizzes' );
			$quiz['quiz_formatted'] = '';
			$quiz['quiz_formatted'] .= $quiz['quiz'];
			$quiz['quiz_formatted'] .= '<br /><br /> Quiz originally created by '.$quiz['members_display_name'].' of '.$this->settings['board_url'];
			// quiz here
			$xml->addElementAsRecord( 'quiz',
					array( 'quiz_id', array( 'id' => $quiz['quiz_id'] ) ),
					array( 	'quiz_name' 	=> array( $quiz['quiz_name'] ),
							'quiz_seotitle'	=> array( $quiz['quiz_seotitle'] ),
							'quiz'		 	=> array( $quiz['quiz_formatted'] ),	
							'quiz_timelimit'=> array( $quiz['quiz_timelimit'] )
					)
			);
			$xml->addElement( 'questions', 'quiz' );
			// questions here
			foreach ($questions as $question) {
				$xml->addElementAsRecord( 'questions',
						array( 'question_id', array( 'id' => $question['question_id'] ) ),
						array( 	'question_name' 		=> array( $question['question_name'] ),
								'quiz_id'				=> array( $question['quiz_id'] ),
								//'question_parent_id'	=> array( $question['question_parent_id'] ),
								//'question_is_correct'	=> array( $question['question_is_correct'] ),
						)
				);
			}
			$xml->addElement( 'answers', 'questions' );
			// answers here
			foreach ($questions as $question) {
				$answers = $this->questions->findAllAnswersForQuestion($question['question_id']);
				foreach ($answers as $answer) {
					$xml->addElementAsRecord( 'answers',
							array( 'answer_id', array( 'id' => $answer['question_id'] ) ),
							array( 	'question_name' 		=> array( $answer['question_name'] ),
									//'quiz_id'				=> array( $answer['quiz_id'] ),
									'question_parent_id'	=> array( $answer['question_parent_id'] ),
									'question_is_correct'	=> array( $answer['question_is_correct'] ),
							)
					);
				}
			}
			
			$xmlData = $xml->fetchDocument();
			//echo ('<xmp>'); print_r($xmlData); echo ('</xmp>'); exit;
			header('Content-Disposition: attachment; filename="quiz-'.$quiz['quiz_seotitle'].'.xml"');
			header("Content-type: text/xml; charset=utf-8");
			echo $xmlData;
			exit;
			//$this->registry->output->redirect( $this->settings['base_url'].'module=quiz&amp;section=importexport', 'Quiz Exported', 2, false, false );
		}
	}

}