<?php

/**
 * story time. a lot of quiz was written.. "under the influence."
 * http://www.urbandictionary.com/define.php?term=the%20buffalo%20theory
 *
 * A herd of buffalo can only move as fast as the slowest buffalo.
 * And when the herd is hunted, it is the slowest and weakest ones in the back that are killed first.
 * This natural selection is good for the herd as a whole,
 * because the general speed and health of the whole group keeps improving
 * by the regular killing of the weakest members.
 *
 * In much the same way, the human brain can only operate as fast as the slowest brain cells.
 * Excessive intake of alcohol, as we know, kills brain cells.
 * But naturally, it attacks the slowest and weakest brain cells first.
 * In this way, regular consumption of alcohol eliminates the weaker brain cells,
 * making the brain a faster and more efficient machine.
 *
 * That's why you always feel smarter after a few beers.
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_quiz
{
	/**
	 * Constructor
	 *
	 * @param       object          ipsRegistry
	 * @return      @e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		# light up
		if ( !ipsRegistry::isClassLoaded('quiz') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_quiz.php', 'quiz_model_quiz', 'quiz' );
			$registry->setClass( 'quiz', new $classToLoad( $registry ) );
		}
		if ( !ipsRegistry::isClassLoaded('categories') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_categories.php', 'quiz_model_categories', 'quiz' );
			$registry->setClass( 'categories', new $classToLoad( $registry ) );
		}
		if ( !ipsRegistry::isClassLoaded('quizzes') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_quizzes.php', 'quiz_model_quizzes', 'quiz' );
			$registry->setClass( 'quizzes', new $classToLoad( $registry ) );
		}
		if ( !ipsRegistry::isClassLoaded('questions') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_questions.php', 'quiz_model_questions', 'quiz' );
			$registry->setClass( 'questions', new $classToLoad( $registry ) );
		}
			if ( !ipsRegistry::isClassLoaded('answers') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_answers.php', 'quiz_model_answers', 'quiz' );
			$registry->setClass( 'answers', new $classToLoad( $registry ) );
		}
		if ( !ipsRegistry::isClassLoaded('leaders') ){
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/quiz_model_leaders.php', 'quiz_model_leaders', 'quiz' );
			$registry->setClass( 'leaders', new $classToLoad( $registry ) );
		}
		
		if( ! IN_ACP )
		{
			/* Load the language File */
			$registry->class_localization->loadLanguageFile( array( 'public_quiz' ), 'quiz' );
		} else {
			$registry->class_localization->loadLanguageFile( array( 'admin_quiz' ), 'quiz' );
		}
	}

	public function afterOutputInit( ipsRegistry $registry )
	{
		if ( ! IN_ACP )
		{
			if (!ipsRegistry::$settings['quiz_online'])
			{
				if (empty(ipsRegistry::$settings['quiz_offline_groups'])) {
					$registry->output->showError( ipsRegistry::$settings['quiz_offline_message'], '40QUIZ-OFFLINE', FALSE, 403);
				} else {
					if ((!in_array($this->memberData['member_group_id'], explode(",", ipsRegistry::$settings['quiz_offline_groups']))) AND (!in_array($this->memberData['mgroup_others'], explode(",", ipsRegistry::$settings['quiz_offline_groups'])))) {
						$registry->output->showError( ipsRegistry::$settings['quiz_offline_groups'], '40QUIZ-OFFLINE', FALSE, 403);
					}
				}
			}
		}
	}
}
?>