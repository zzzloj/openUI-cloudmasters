<?php
class cp_skin_questions extends output
{ 
// Questions
	
	function allQuestions($questions, $parent) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
        //--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_questions']} > "{$parent}"</h2>
			<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=add&amp;id={$this->request['id']}"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addquestion']}</a>
					</li>
					<li class="ipsActionButton right">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add" class="right"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addquiz']}</a>
					</li>
				</ul>
			</div>
		</div>
		
        <div class="acp-box">
        	<h3 class="maintitle">{$this->lang->words['quiz_acp_allquestions']}</h3>
        	<table id="quiz_quizzes_overview" class="ipsTable ipsPad">
        		<tr>
        			<th>{$this->lang->words['quiz_acp_question']}</th>
        			<th>{$this->lang->words['quiz_acp_quizz']}</th>
        			<th></th>
        			<th></th>
        		</tr>
EOF;
if ($questions):
foreach ($questions as $question):
        $IPBHTML .= <<< EOF
        		<tr class="ipsControlRow isDraggable" id="question_{$question['question_id']}">
        			<td><a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=edit&amp;id={$question['question_id']}&amp;type=q'><strong class="larger_text">{$question['question_name']}</strong></a></td>
        			<td><a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=edit&amp;id={$question['quiz_id']}'><strong class="larger_text">{$question['quiz_name']}</strong></a></td>        			
        			<td><p><a href='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;id={$question['question_id']}' class='mini_button'>{$this->lang->words['quiz_manage_answers']}</a></p></td>
        			
        			<td>
						<ul class='ipsControlStrip'>
          					<li class='i_edit'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=edit&amp;id={$question['question_id']}'>Edit</a>
          					</li>
          					<li class='i_delete'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=delete&amp;id={$question['question_id']}' onclick='return confirm("Are you sure you want to delete this question?")'>Delete</a>
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
        	<td><p>{$this->lang->words['quiz_question_no_content']} <a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=add&amp;id={$this->request['id']}' class='mini_button'>{$this->lang->words['quiz_acp_addquestion']}</a></p></td>
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
	
	function addQuestion($quiz, $questions) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$question_name = $this->registry->output->formInput('question_name','','question_name','60','text','required','','140');
		$quiz_id = $this->registry->output->formInput('quiz_id',$quiz['quiz_id'],'quiz_id','60','hidden','','','140');
		if (count($questions) > 1) {
			$question_parent_id = $this->registry->output->formDropDown('question_parent_id', $questions, 'question_parent_id');
		} else {
			$question_parent_id = $this->registry->output->formDropDown('question_parent_id', array(array('0','---')), 'question_parent_id', '', '');
		}
		$question_is_correct = $this->registry->output->formCheckbox('question_is_correct',false,$question['question_is_correct'],'question_is_correct');
		
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_addquestion']} > "{$quiz['quiz_name']}"</h2>
		</div>
		
		<div class='ipsSteps clearfix' id='steps_bar'>
	      <ul>
	        <li id='step_1'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_addquiz']}</strong>
	          <span class='steps_desc'>{$quiz['category_name']}</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	        <li class='steps_active' id='step_2'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_addquestion']}</strong>
	          <span class='steps_desc'>{$quiz['quiz_name']}</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	        <li id='step_3'>
	          <strong class='steps_disabled'>{$this->lang->words['quiz_acp_addanswer']}s</strong>
	          <span class='steps_desc'><br />&nbsp;</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	      </ul>
    	</div>
    	
        <div class="acp-box">
        	<h3>{$this->lang->words['quiz_acp_addquestion']}</h2>
	        <form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=add&amp;id={$quiz['quiz_id']}' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_questiontitle']}</strong></td>
	        			<td class='field_field'>{$question_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'></strong></td>
	        			<td class='field_field'>{$quiz_id}</td>
	        		</tr>
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' id='gotoquestions' value='{$this->lang->words['quiz_acp_continuetoanswers']}' />      				
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	
	function editQuestion($question, $quiz, $type) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$question_name = $this->registry->output->formInput('question_name',$question['question_name'],'question_name','60','text','required','','140');
		$quiz_id = $this->registry->output->formInput('quiz_id',$quiz['quiz_id'],'quiz_id','60','hidden','','','140');
		$question_id = $this->registry->output->formInput('question_id',$question['question_id'],'question_id','60','hidden','','','140');
		$question_parent_id = $this->registry->output->formInput('question_parent_id',$question['question_parent_id'],'question_parent_id','60','hidden','','','140');
		$question_is_correct = $this->registry->output->formCheckbox('question_is_correct',false,$question['question_is_correct'],'question_is_correct');
		
		if ($type == 'q') {
			$title = "{$this->lang->words['quiz_acp_editquestion']}:";
		} else if ($type == 'a') {
			$title = "{$this->lang->words['quiz_acp_editanswer']}:";
		} else {
			$title = "{$this->lang->words['quiz_acp_edit']}:";
		}
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="acp-box">
        	<h3>{$title} {$question['question_name']}</h2>
	        <form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;do=edit&amp;id={$question['question_id']}' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_questiontitle']}</strong></td>
	        			<td class='field_field'>{$question_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'></strong></td>
	        			<td class='field_field'>{$quiz_id} {$question_id} {$question_parent_id}</td>
	        		</tr>
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' id='gotoquestions' value='Submit' />      				
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
}	