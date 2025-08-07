<?php

class public_quiz_categories_categories extends ipsCommand
{
	
    public function doExecute(ipsRegistry $registry)
    {
        $this->lang->loadLanguageFile(array('public_lang'), 'quiz');
        $this->categories = $this->registry->getClass('categories');
        $this->quizzes = $this->registry->getClass('quizzes');

        // what are we doing	
        switch( $this->request['do'] )
		{
		  case 'view':
		  	$id = $this->request['id'];
		  	$this->showCategory($id);
          break;
		  default:
		  	$this->allCategories();
		  	break;
        }
        
        $this->registry->output->sendOutput();
    }
    
    public function showCategory($id)
    {
    	if ($id) {
    	    if ($this->memberData['g_quiz_can_view_quiz'] != 1) {
	    		$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_cannot_view_quiz'] ), '10QUIZ03', ''); // 
	    	}
	    	$filter = $this->request['filter'];
	    	if (empty($this->request['filter'])) {
	    		$this->request['filter'] = 'all';
	    	}
    		$category = $this->categories->findById($id);
    		if ($filter == 'taken') {
    			$_quizzes  = $this->quizzes->findByCategory($category['category_id']);#
    			// here we will filter through quizzes in a loop and if hasTaken == 1, show it.
    			foreach ($_quizzes as $q) {
    				$taken = $this->quizzes->hasTaken($this->memberData['member_id'], $q['quiz_id']);
    				if ($taken == 1) {
    					$quizzes[] = $q;
    				}
    			}
    		} else if ($filter == 'untaken') {
    			$_quizzes  = $this->quizzes->findByCategory($category['category_id']);
    			// here we will filter through quizzes in a loop and if hasTaken == 0, show it.
    			foreach ($_quizzes as $q) {
    				$taken = $this->quizzes->hasTaken($this->memberData['member_id'], $q['quiz_id']);
    				if ($taken == 0) {
    					$quizzes[] = $q;
    				}
    			}
    		} else if ($filter == 'mine') {
    			//$quizzes  = $this->quizzes->findByMemberId($this->memberData['member_id'], $category['category_id']);
    			$_quizzes  = $this->quizzes->findByCategory($category['category_id']);
    			foreach ($_quizzes as $q) {
    				if ($q['quiz_starter_id'] == $this->memberData['member_id']) {
    					$quizzes[] = $q;
    				}
    			}
    		} else {
    			$quizzes  = $this->quizzes->findByCategory($category['category_id']);
    		}
    		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
    		$sessions	= new $classToLoad( $this->registry );
    		$activeUsers = $sessions->getUsersIn('quiz', array( 'skipParsing' => true, 'addWhere' => array( "s.location_1_type='categories'", "s.location_1_id={$category['category_id']}" ) ));
    		$this->registry->output->setTitle($category['category_name'].' - '.$this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
			$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
			$this->registry->output->addNavigation($category['category_name'], 'app=quiz&amp;module=categories&amp;section=categories&amp;do=view&amp;id='.$category['category_id'], $category['category_seotitle'], 'quizcat');
    		$template = $this->registry->output->getTemplate('quiz')->showCategory($category, $quizzes, $activeUsers);
			$this->registry->output->addContent($template);
    	}
    }
    
    public function allCategories()
    {
    	if ($this->memberData['g_quiz_can_view_quiz'] != 1) {
	    	$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_cannot_view_quiz'] ), '10QUIZ03', ''); // 
	    }
    	$this->registry->output->setTitle($this->lang->words['quiz_title'].' - '.$this->settings['board_name']);
		$this->registry->output->addNavigation($this->lang->words['quiz_title'], 'app=quiz', 'quiz', 'quiz');
    	$_categories = $this->categories->findAll();
    	if ($_categories) {
	    	foreach ($_categories as $cat) {
	    		$count = $this->quizzes->catCount($cat['category_id']);
	    		$cat['count'] = $count;
	    		$categories[] = $cat;
	    	}
    	}
    	$pquizzes = $this->quizzes->findPopular(array(0,5));
    	$_quizzes = $this->quizzes->findAll(array(0,5));
    	foreach ($_quizzes as $q) {
    		$taken = $this->quizzes->hasTaken($this->memberData['member_id'], $q['quiz_id']);
    		if ($taken == 1) {
    			$q['taken'] = '1';
    		} else {
    			$q['taken'] = '0';
    		}
    		$quizzes[] = $q;
    	}
    	//echo ('<xmp>'); print_r($quizzes); echo ('</xmp>'); exit;
    	$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
    	$sessions	= new $classToLoad( $this->registry );
    	$activeUsers = $sessions->getUsersIn('quiz');
    	$template = $this->registry->output->getTemplate('quiz')->allCategories($categories, $quizzes, $pquizzes, $activeUsers);
		$this->registry->output->addContent($template);
    }
}
?>