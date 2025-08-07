<?php

$_SEOTEMPLATES = array(
	'quizresults' => array(
		'app' => 'quiz',
		'allowRedirect' => 1,
		'out' => array( '/app=quiz(&amp;|&)module=quiz(&amp;|&)section=quiz(&amp;|&)do=viewresults(&amp;|&)key=(.+?)(&amp;|&)id=(.+?)(&|$)/i', 'quiz/results/$6/$7' ),
		'in' => array(
			'regex' => "#/quiz/results/(.+?)/(.+?)#i",
			'matches' => array(
				array('app', 'quiz'),
				array('module', 'quiz'),
				array('section', 'quiz'),
				array('do', 'viewresults'),
				array('key', '$1'),
				array('id', '$2')
			)
		)
	),
	'quiztakequiz' => array(
		'app' => 'quiz',
		'allowRedirect' => 1,
		'out' => array( '/app=quiz(&amp;|&)module=quiz(&amp;|&)section=quiz(&amp;|&)do=takequiz(&amp;|&)id=(.+?)(&|$)/i', 'quiz/takequiz/$5-#{__title__}/$6' ),
		'in' => array(
			'regex' => "#/quiz/takequiz/(\d+?)-(.+?)#i",
			'matches' => array(
				array('app', 'quiz'),
				array('module', 'quiz'),
				array('section', 'quiz'),
				array('do', 'takequiz'),
				array('id', '$1')
			)
		)
	),
	'quizquiz' => array(
		'app' => 'quiz',
		'allowRedirect' => 1,
		'out' => array( '/app=quiz(&amp;|&)module=quiz(&amp;|&)section=quiz(&amp;|&)do=view(&amp;|&)id=(.+?)(&|$)/i', 'quiz/$5-#{__title__}/$6' ),
		'in' => array(
			'regex' => "#/quiz/(\d+?)-(.+?)#i",
			'matches' => array(
				array('app', 'quiz'),
				array('module', 'quiz'),
				array('section', 'quiz'),
				array('do', 'view'),
				array('id', '$1')
			)
		)
	),
	'quizcat' => array(
		'app' => 'quiz',
		'allowRedirect' => 1,
		'out' => array( '/app=quiz(&amp;|&)module=categories(&amp;|&)section=categories(&amp;|&)do=view(&amp;|&)id=(.+?)(&|$)/i', 'quiz/categories/$5-#{__title__}/$6' ),
		'in' => array(
			'regex' => "#/quiz/categories/(\d+?)-(.+?)#i",
			'matches' => array(
				array('app', 'quiz'),
				array('module', 'categories'),
				array('section', 'categories'),
				array('do', 'view'),
				array('id', '$1')
			)
		)
	),
	'app=quiz' => array(
		'app' => 'quiz',
		'allowRedirect' => 1,
		'out' => array( '#app=quiz$#i', 'quiz/' ),
		'in' => array(
			'regex' => "#/quiz($|\/)#i",
			'matches' => array(
				array('app', 'quiz')
			)
		)
	)
);
?>