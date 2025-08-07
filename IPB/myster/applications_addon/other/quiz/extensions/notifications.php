<?php
ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'quiz' );

class quiz_notifications
{
	public function getConfiguration()
	{
		$_NOTIFY	= array(
							array(
								'key'		=>	'quiz__member_taken_quiz',	
								'default'	=>	array( 'inline'	),
								'disabled'	=>	array(),
								'icon'		=>	'notify_newtopic',
								)
							);
							
		return $_NOTIFY;
	}
}





?>