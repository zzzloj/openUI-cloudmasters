<?php


class trackMemberMapping
{
	public function functionRemapToPrettyList()
	{
		return array(
			'account_actions' => array(
				'onLogin'		=> 'sign_in',
				'onLogOut'		=> 'sign_out',
				'onNameChange'	=> 'change_dn',
				'onPassChange'	=> 'change_pw',
				'onEmailChange'	=> 'change_email',
			),
			'forum_actions' => array(
				'topicSetUp'	=> 'view_topic',
				'_save'			=> 'follow_topicforum',
				'addRate'		=> ( ipsRegistry::$settings['reputation_point_types'] == 'like' ) ? 'like_system' : 'reputation_system',
			),
			'profile_actions' => array(
				'sendNewPersonalTopic'	=> 'new_pm',
				'sendReply'				=> 'reply_pm',
				'_viewModern'			=> 'view_profile',
				'addFriend'				=> 'new_friend',
				'removeFriend'			=> 'removed_friend',
				'create'				=> 'new_su',
				'reply'					=> 'reply_su',
				'deleteReply'			=> 'delete_su_reply',
				'deleteStatus'			=> 'delete_su',
			),
		);
	}
	
	public function getDefaultSettings()
	{
		return array(
			'onLogin'				=> 1,
			'onLogOut'				=> 1,
			'onNameChange'			=> 1,
			'onPassChange'			=> 1,
			'onEmailChange'			=> 1,
			'topicSetUp'			=> 1,
			'_save'					=> 1,
			'addRate'				=> 1,
			'sendNewPersonalTopic'	=> 1,
			'sendReply'				=> 1,
			'_viewModern'			=> 1,
			'addFriend'				=> 1,
			'removeFriend'			=> 1,
			'create'				=> 1,
			'reply'					=> 1,
			'deleteReply'			=> 1,
			'deleteStatus'			=> 1,
		);
	}
	
	public function functionToLangStrings()
	{
		return array(
			'onLogin'				=> 'sign_in',
			'onLogOut'				=> 'sign_out',
			'onNameChange'			=> 'change_dn',
			'onPassChange'			=> 'change_pw',
			'onEmailChange'			=> 'change_email',
			'topicSetUp'			=> 'view_topic',
			'_save'					=> 'follow_topicforum',
			'addRate'				=> ( ipsRegistry::$settings['reputation_point_types'] == 'like' ) ? 'like_system' : 'reputation_system',
			'sendNewPersonalTopic'	=> 'new_pm',
			'sendReply'				=> 'reply_pm',
			'_viewModern'			=> 'view_profile',
			'addFriend'				=> 'new_friend',
			'removeFriend'			=> 'removed_friend',
			'create'				=> 'new_su',
			'reply'					=> 'reply_su',
			'deleteReply'			=> 'delete_su_reply',
			'deleteStatus'			=> 'delete_su',
			
		);
	}
}