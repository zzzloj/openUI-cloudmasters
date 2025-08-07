<?php
class cp_skin_answers extends output
{ 
	
	function allAnswers($answers, $question) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
        //--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="error warning message">
			{$this->lang->words['quiz_acp_answers_notice']}        
		</div>
        <br /><br />
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_answersfor']} "{$question['question_name']}"</h2>
			<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=answers&amp;do=add&amp;id={$this->request['id']}"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addanswer']}</a>						
					</li>
					<li class="ipsActionButton right">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=add&amp;id={$question['quiz_id']}" class="right"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addquestion']}</a>
					</li>
				</ul>
			</div>
		</div>
		
        <div class="acp-box">
        	<h3 class="maintitle">{$this->lang->words['quiz_acp_allanswers']}</h3>
        	<table id="quiz_quizzes_overview" class="ipsTable ipsPad">
        		<tr>
        			<th class='col_drag'>&nbsp;</th>
        			<th>{$this->lang->words['quiz_acp_answer']}</th>
        			<th>{$this->lang->words['quiz_acp_correctanswer']}</th>
        			<th></th>
        		</tr>
EOF;
if ($answers):
foreach ($answers as $answer):
        $IPBHTML .= <<< EOF
        		<tr class="ipsControlRow isDraggable" id="answer_{$answer['question_id']}">
        			<td><span class='draghandle'>&nbsp;</span></td>
        			<td><a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=edit&amp;id={$answer['question_id']}&amp;type=a'><strong class="larger_text">{$answer['question_name']}</strong></a></td>
EOF;
if ($answer['question_is_correct'] == 0) {
	$image = 'cross';
} else {
	$image = 'tick';
}
        $IPBHTML .= <<< EOF
        			<td><a href='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;do=togglestate&amp;id={$answer['question_id']}' title='Toggle Answer State (Correct/Incorrect)'><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/{$image}.png" alt=""></a></td>
        			
        			<td>
						<ul class='ipsControlStrip'>
          					<li class='i_edit'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=edit&amp;id={$answer['question_id']}&amp;type=a'>Edit</a>
          					</li>
          					<li class='i_delete'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;do=delete&amp;id={$answer['answer_id']}' onclick='return confirm("Are you sure you want to delete this answer?")'>Delete</a>
          					</li>
          				</ul>
    				</td>
        		</tr>
EOF;
endforeach;
else:
        $IPBHTML .= <<< EOF
        <tr>
        	<td></td>
        	<td><p>{$this->lang->words['quiz_answer_no_content']} <a href='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;do=add&amp;id={$this->request['id']}' class='mini_button'>{$this->lang->words['quiz_acp_addanswer']}</a></p></td>
        	<td></td>
        </tr>
EOF;
endif;
        $IPBHTML .= <<< EOF
     
        	</table>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	
	function addAnswer($question) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$answer_name = $this->registry->output->formInput('question_name','','question_name','60','text','required','','140');
		$question_id = $this->registry->output->formInput('question_parent_id',$question['question_id'],'question_parent_id','60','hidden','','','140');
		$question_name = $this->registry->output->formInput('question_name',$question['question_name'],'question_name','60','text','disabled','','140');
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_correctanswer']} > "{$question['question_name']}"</h2>
		</div>
		
	    <div class='ipsSteps clearfix' id='steps_bar'>
	      <ul>
	        <li id='step_1'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_addquiz']}</strong>
	          <span class='steps_desc'>&nbsp;</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	        <li id='step_2'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_addquestionfor']}</strong>
	          <span class='steps_desc'>{$question['quiz_name']}</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	        <li class='steps_active' id='step_3'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_answersfor']}</strong>
	          <span class='steps_desc'>{$question['question_name']}<br /></span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	      </ul>
    	</div>
    
        <div class="acp-box">
        	<h3>{$this->lang->words['quiz_acp_addanswer']}</h2>
	        <form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;do=add&amp;id={$question['question_id']}' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	        			<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_question']}</td>
	        			<td class='field_field'>{$question_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_answertitle']}</strong></td>
	        			<td class='field_field'>{$answer_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'></strong></td>
	        			<td class='field_field'>{$question_id}</td>
	        		</tr>
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' id='gotoanswers' value='Submit' />      				
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
}