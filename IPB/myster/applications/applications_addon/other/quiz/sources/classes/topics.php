<?php 
class topics {
	
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	public function postNewTopic($data)
	{
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php', 'classPost', 'forums' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
		$this->post		= new $classToLoad( $this->registry );
		$this->post->setBypassPermissionCheck( true );
		$this->post->setIsAjax( true );
		$this->post->setPublished( true );
		$this->post->setForumID( $this->settings['quiz_post_to_forum_id'] );
		$this->post->setForumData( ipsRegistry::instance()->class_forums->forum_by_id[$this->settings['quiz_post_to_forum_id']] );
		$member  = IPSMember::load( $data['quiz_starter_id'], 'all', 'id' );
		$content = $this->buildTopic($data);
		$this->post->setAuthor( $member );
		$this->post->setPostContentPreFormatted( $content );
		$this->post->setTopicTitle( $data['quiz_name'] );
		$this->post->incrementUsersPostCount( 1 );
		$this->post->setSettings(
			array(
				'enableSignature' => 1,
				'enableEmoticons' => 1,
				'post_htmlstatus' => 0 
			)
		);

		if ($this->post->addTopic() === false) {
			if ($this->debugMode) {
				print_r($this->post->getPostError());exit;
			}
		}
			
		$topic	= $this->post->getTopicData();
		$post	= $this->post->getPostData();
		$this->DB->update( "quiz_quizzes", array('quiz_support_topic' => $topic['tid']), 'quiz_id='.$data['quiz_id'] ); 		
	}

	public function buildTopic($data)
	{
		$content = '';
		$content .= '[b]'.$this->lang->words['quiz_name'].'[/b]: '.$data['quiz_name'].'<br />';
		$content .= '[b]'.$this->lang->words['quiz_category'].'[/b]: '.$data['category_name'].'<br />';
		$content .= '[b]'.$this->lang->words['quiz_description'].'[/b]: '.$data['quiz'].'<br /><br />';
		$url = $this->registry->output->buildSEOUrl( 'app=quiz&module=quiz&section=quiz&do=view&id='.$data['quiz_id'], 'public', $data['quiz_seotitle'], 'quizquiz' );
		$content .= '[url='.$url.']'.$this->lang->words['quiz_view_full_quiz'].'[/url]';
		return $content;
	}
	
}
?>