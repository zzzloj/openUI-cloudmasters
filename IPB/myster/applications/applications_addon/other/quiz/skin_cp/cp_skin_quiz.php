<?php
class cp_skin_quiz extends output
{ 
	public function overview($quizzes, $categories)
	{
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
        //--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']}{$this->lang->words['quiz_acp_quizoverview']}</h2>
        	<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=categories&amp;section=categories"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/cog.png" alt=""> {$this->lang->words['quiz_acp_catmanager']} </a>
					</li>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=quiz"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/cog.png" alt=""> {$this->lang->words['quiz_acp_quizmanager']} </a>
					</li>
				</ul>
			</div>
        </div>
        <div class="acp-box" style="width:48%; float:right;">
        	<h3 class="maintitle">{$this->lang->words['quiz_acp_quizzes']}</h3>
        	<table class='ipsTable double_pad' id='quiz_quiz'>
        	<tr>
        		<th>{$this->lang->words['quiz_acp_quizname']}</th>
        		<th>{$this->lang->words['quiz_acp_quizcat']}</th>
        		<th></th>
        	</tr>
EOF;
if ($quizzes):
foreach ($quizzes as $quiz):
        $IPBHTML .= <<< EOF
<tr>
<td style="padding: 10px" class="ipsControlRow isDraggable ipsDraggableRow item"><a href="{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=view&amp;id={$quiz['quiz_id']}"><strong class="larger_text">{$quiz['quiz_name']}</strong></a></td>
<td style="padding: 10px" class="ipsControlRow isDraggable ipsDraggableRow item"><a href="{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$quiz['category_id']}"><strong class="larger_text">{$quiz['category_name']}</strong></a></td>
<td>
        <ul class='ipsControlStrip'>
          <li class='i_edit'><a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=edit&amp;id={$quiz['quiz_id']}'>Edit</a></li>
          <li class='i_delete'><a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=delete&amp;id={$quiz['quiz_id']}' onclick='return confirm("Are you sure you want to delete?")'>Delete</a></li>
		</ul>
</td>
</tr>
EOF;
endforeach;
$IPBHTML .= <<< EOF
<tr>
<td><p><a href="{$this->settings['base_url']}module=quiz&amp;section=quiz" class="mini_button">{$this->lang->words['quiz_acp_viewall']}</a></p></td>
</tr>
EOF;
else:
                $IPBHTML .= <<< EOF

<tr>
      <td  colspan='2' class='no_messages'>
        {$this->lang->words['quiz_acp_noquizzes']}  <a  href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add' class='mini_button'>{$this->lang->words['quiz_acp_addquiz']}</a>
      </td>
    </tr>
EOF;
endif;
        $IPBHTML .= <<< EOF
        	</table>
        </div>
        <div class="acp-box" style="width:48%; float:left;">
        	<h3 class="maintitle">Categories</h3>
        	<table class='ipsTable double_pad' id='quiz_cats'>
        	<tr>
        		<th>{$this->lang->words['quiz_acp_quizcat']}</th>
        		<th></th>
        	</tr>
EOF;
if ($categories):
foreach ($categories as $cat):
        $IPBHTML .= <<< EOF
<tr>
<td style="padding: 10px" class="ipsControlRow isDraggable ipsDraggableRow item"><a href="{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$cat['category_id']}"><strong class="larger_text">{$cat['category_name']}</strong></a></td>
<td>
        <ul class='ipsControlStrip'>
          <li class='i_edit'><a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$cat['category_id']}'>Edit</a></li>
          <li class='i_delete'><a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=delete&amp;id={$cat['category_id']}' onclick='return confirm("Are you sure you want to delete?")'>Delete</a></li>
		</ul>
</td>
</tr>
EOF;
endforeach;
$IPBHTML .= <<< EOF
<tr>
<td><p><a href="{$this->settings['base_url']}module=categories&amp;section=categories" class="mini_button">{$this->lang->words['quiz_acp_viewall']}</a></p></td>
</tr>
EOF;
else:
                $IPBHTML .= <<< EOF

<tr>
      <td  colspan='2' class='no_messages'>
        {$this->lang->words['quiz_acp_nocats']}  <a  href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=add' class='mini_button'>{$this->lang->words['quiz_acp_addcat']}</a>
      </td>
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
	
	function manageQuiz($quiz, $questions) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		//--starthtml--//
		$IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']}{$this->lang->words['quiz_acp_quizzes']}</h2>
        	<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addquiz']}</a>
					</li>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=edit&amp;id={$quiz['quiz_id']}"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/cog_edit.png" alt=""> {$this->lang->words['quiz_acp_editquiz']}</a>
					</li>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=questions&amp;id={$quiz['quiz_id']}"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/cog.png" alt=""> {$this->lang->words['quiz_manage_questions']}</a>
					</li>
				</ul>
			</div>
		</div>

        <div class="acp-box">
        	<h3 class="maintitle">{$quiz['quiz_name']}</h3>
        	<table class='ipsTable double_pad' id='quiz_managequiz'>
        	<tr>
        		<th style="width:35%">{$this->lang->words['quiz_acp_question']}</th>
        		<th>{$this->lang->words['quiz_acp_answers']}</th>
        		<th><a class="right" href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;id={$quiz['quiz_id']}' class='mini_button'>{$this->lang->words['quiz_manage_questions']}</a></th>
        	</tr>
        	
EOF;
		if ($questions):
		$IPBHTML .= <<< EOF
		<tr>
EOF;
		foreach ($questions as $question):
		$IPBHTML .= <<< EOF
		<td><b>{$question['question_name']}</b></td>
		<td></td>
		<td><a href='{$this->settings['base_url']}module=quiz&amp;section=answers&amp;id={$question['question_id']}' class='mini_button' class="right">{$this->lang->words['quiz_manage_answers']}</a></td>
		</tr>
EOF;
		if ($question['answers']):
		foreach ($question['answers'] as $answer):
		$IPBHTML .= <<< EOF
		<tr><td></td><td>
		<!--<li style="margin-left:15px;">-->
EOF;
		if ($answer['question_is_correct'] == 1):
		$IPBHTML .= <<< EOF
		<span style="font-weight:bold;color:green;">
EOF;
		endif;
		$IPBHTML .= <<< EOF
		{$answer['question_name']}
EOF;
		if ($answer['question_is_correct'] == 1):
		$IPBHTML .= <<< EOF
		</span>
EOF;
endif;
		$IPBHTML .= <<< EOF
</li></td><td></td>
		</tr>
EOF;
endforeach;
endif;
endforeach;
else:
if ($this->memberData['g_is_supmod'] == 1):
$IPBHTML .= <<< EOF
{$this->lang->words['quiz_no_questions_add_one']}
EOF;
endif;
endif;
$IPBHTML .= <<< EOF
        	</table>
        </div>
EOF;
		
		//--endhtml--//
		return $IPBHTML;		
	}
	
	function allQuiz($quizzes) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
        //--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']}{$this->lang->words['quiz_acp_quizzes']}</h2>
			<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_addquiz']}</a>
					</li>
				</ul>
			</div>
		</div>
		
        <div class="acp-box">
        	<h3 class="maintitle">{$this->lang->words['quiz_acp_allquizzes']}</h3>
        	<table id="quiz_quizzes_overview" class="ipsTable ipsPad">
        		<tr>
        			<th width="35%">{$this->lang->words['quiz_acp_quizname']}</th>
        			<th>{$this->lang->words['quiz_acp_quizcat']}</th>
        			<th>{$this->lang->words['quiz_acp_quizauthor']}</th>
        			<th></th>
        			<th></th>
        		</tr>
EOF;
if ($quizzes):
foreach ($quizzes as $quiz):
        $IPBHTML .= <<< EOF
        		<tr class="ipsControlRow isDraggable" id="quiz_{$quiz['quiz_id']}">
        			<td><a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=view&amp;id={$quiz['quiz_id']}'><strong class="larger_text">{$quiz['quiz_name']}</strong></a></td>
        			<td><img src='{$quiz['category_image']}' style='width:25px !important;height:25px !important;border:0px !important;' /> <a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$quiz['quiz_category_id']}'><strong class="larger_text">{$quiz['category_name']}</strong></a></td>
        			<td><a href='{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$quiz['member_id']}'><strong class="larger_text">{$quiz['members_display_name']}</strong></a></td>
        			<td><p><a href='{$this->settings['base_url']}module=quiz&amp;section=questions&amp;id={$quiz['quiz_id']}' class='mini_button'>{$this->lang->words['quiz_manage_questions']}</a></p></td>
        			
        			<td>
						<ul class='ipsControlStrip'>
							<li class='i_view'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=view&amp;id={$quiz['quiz_id']}'>Manage</a>
          					</li>
          					<li class='i_edit'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=edit&amp;id={$quiz['quiz_id']}'>Edit</a>
          					</li>
          					<li class='i_delete'>
          						<a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=delete&amp;id={$quiz['quiz_id']}' onclick='return confirm("Are you sure you want to delete this quiz?")'>Delete</a>
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
        	<td><p>{$this->lang->words['quiz_no_content']} <a href='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add' class='mini_button'>{$this->lang->words['quiz_acp_addquiz']}</a></p></td>
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
	
	function addQuiz($editor, $categories) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$quiz_name = $this->registry->output->formInput('quiz_name','','quiz_name','60','text','required','','140');
		$quiz_score = $this->registry->output->formInput('quiz_group_promo_score','0','quiz_group_promo_score','60','number','required min="0" max="100" step="25"','','140');
		$quiz_timelimit = $this->registry->output->formInput('quiz_timelimit','0','quiz_timelimit','60','number','required min="30" step="10"','','');
		$catlist = $this->registry->output->formDropDown('quiz_category_id', $categories, 'quiz_category_id');
		
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']}{$this->lang->words['quiz_acp_addquiz']}</h2>
        	
		</div>
		<div class='ipsSteps clearfix' id='steps_bar'>
	      <ul>
	        <li class='steps_active' id='step_1'>
	          <strong class='steps_title'>{$this->lang->words['quiz_acp_addquiz']}</strong>
	          <span class='steps_desc'>&nbsp;Adding Quiz</span>
	          <span class='steps_arrow'>&nbsp;</span>
	        </li>
	        <li id='step_2'>
	          <strong class='steps_disabled'>{$this->lang->words['quiz_acp_addquestion']}(s)</strong>
	          <span class='steps_desc'><br />&nbsp;</span>
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
        	<h3>{$this->lang->words['quiz_acp_addquiz']}</h2>
	        <form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=add' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizname']}</strong></td>
	        			<td class='field_field'>{$quiz_name}</td>
	        		</tr>
	        		<tr>
	        			<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizcat']}</td>
	        			<td class='field_field'>{$catlist}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizdesc']}</strong></td>
	        			<td class='field_field'>{$editor}</td>
	        		</tr>
EOF;
        if (ipsRegistry::$settings['quiz_enable_group_promo'] == '1'):
        $this->DB->build( array( 'select' => '*', 'from' => 'groups', 'order' => 'g_id ASC' ) );
        $this->DB->execute();
        $show_groups[] = array('0', 'None, Do Not Promote');
        while( $row = $this->DB->fetch() )
        {
        	if ( $row['g_access_cp'] )
        	{
        		$row['g_title'] .= ' ' . $this->lang->words['setting_staff_tag'] . ' ';
        	}
        		
        	$show_groups[] = array( $row['g_id'], $row['g_title'] );
        }
        
        $groups = $this->registry->output->formDropDown('quiz_promote_group_id', $show_groups, '0, ""');
        
        $IPBHTML .= <<< EOF
			        <tr>
			        	<th>{$this->lang->words['quiz_acp_extra_opts']}</th>
			        	<th></th>
			        </tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_grouppromo']}</strong></td>
	        			<td class='field_field'>{$groups}</td>
	        		</tr>      
        	        <tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_grouppromo_score']}</strong></td>
	        			<td class='field_field'>{$quiz_score}</td>
	        		</tr>  	
EOF;
        endif;
        if (ipsRegistry::$settings['quiz_enable_timers'] == '1'):
         $IPBHTML .= <<< EOF
                    <tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_timelimit']}</strong></td>
	        			<td class='field_field'>{$quiz_timelimit}<br /><span class="desctext">This is the time limit for the entire quiz in seconds. - minimum 30 seconds.</span></td>
	        		</tr>  	  
EOF;
        endif;
        $IPBHTML .= <<< EOF
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' id='gotoquestions' value='{$this->lang->words['quiz_acp_quizsubmitmanageqs']}' />      				
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	
	function editQuiz($quiz, $editor, $categories) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$quiz_name = $this->registry->output->formInput('quiz_name',$quiz['quiz_name'],'quiz_name','60','text','required','','140');		
		$quiz_score = $this->registry->output->formInput('quiz_group_promo_score',$quiz['quiz_group_promo_score'],'quiz_group_promo_score','60','number','required min="0" max="100" step="25"','','140');
		$quiz_timelimit = $this->registry->output->formInput('quiz_timelimit',$quiz['quiz_timelimit'],'quiz_timelimit','60','number','required min="30" step="10"','','');
		
		$catlist = $this->registry->output->formDropDown('quiz_category_id', $categories, 'quiz_category_id', 'quiz_category_id', '', '');
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_edit']} > "{$quiz['quiz_name']}"</h2>
		</div>
		<style>
		.catName {
			font-weight: bold;
			padding: 4px 10px;
			border: 1px solid #D5D5D5;
			background: #EEEEEE;
			-webkit-box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.2);
			-moz-box-shadow: 0px 2px 2px rgba(0,0,0,0.2);
			box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.2);
			display: inline-block;
			border-radius: 4px;
			margin: 0 3px;
		}
		</style>
        <div class="acp-box">
        	<h3>Edit Quiz</h2>
	        <form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=edit&amp;id={$quiz['quiz_id']}' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
			<input type='hidden' name='quiz_id' value='{$quiz['quiz_id']}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizname']}</strong></td>
	        			<td class='field_field'>{$quiz_name}</td>
	        		</tr>
	        		<tr>
	        			<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizcat']}</td>
	        			<td class='field_field'><div class="catName"><b>{$quiz['category_name']}</b></div> <a href="#" class="button" id="changeCat">{$this->lang->words['quiz_acp_quizchngcat']}</a></td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizdesc']}</strong></td>
	        			<td class='field_field'>{$editor}</td>
	        		</tr>
	        		
EOF;
        if (ipsRegistry::$settings['quiz_enable_group_promo'] == '1'):
        $this->DB->build( array( 'select' => '*', 'from' => 'groups', 'order' => 'g_id ASC' ) );
        $this->DB->execute();
        $show_groups[] = array('0', 'None, Do Not Promote');
        while( $row = $this->DB->fetch() )
        {
        	if ( $row['g_access_cp'] )
        	{
        		$row['g_title'] .= ' ' . $this->lang->words['setting_staff_tag'] . ' ';
        	}
        		
        	$show_groups[] = array( $row['g_id'], $row['g_title'] );
        }
        
        $groups = $this->registry->output->formDropDown('quiz_promote_group_id', $show_groups, $quiz['quiz_promote_group_id']);
        
        $IPBHTML .= <<< EOF
			        <tr>
			        	<th>{$this->lang->words['quiz_acp_extra_opts']}</th>
			        	<th></th>
			        </tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_grouppromo']}</strong></td>
	        			<td class='field_field'>{$groups}</td>
	        		</tr>      
        	        <tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_grouppromo_score']}</strong></td>
	        			<td class='field_field'>{$quiz_score}%</td>
	        		</tr>  	
	        		
EOF;
        endif;
        if (ipsRegistry::$settings['quiz_enable_timers'] == '1'):
         $IPBHTML .= <<< EOF
                    <tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_timelimit']}</strong></td>
	        			<td class='field_field'>{$quiz_timelimit}<br /><span class="desctext">This is the time limit for the entire quiz in seconds. - minimum 30 seconds.</span></td>
	        		</tr>  	  
EOF;
        endif;
        $IPBHTML .= <<< EOF
	        	</table>
	        	<script type="text/javascript">
	        	$('changeCat').observe( 'click', function(e) {
        			_var = new ipb.Popup( 'changeCatForm', { 
        				type: 'pane',
        				initial: $('changeCatForm').innerHTML,
                        hideAtStart: false,
                        w: '600px',
                        h: 310} 
                    );
        			Event.stop(e);
        			return false;
  				});
				</script>
			
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' id='gotoquestions' value='{$this->lang->words['quiz_acp_quizsubmitmanageqs']}' />      				
    			</div>
	        </form>
	        <div class="changeCatForm" id="changeCatForm" style="display:none;">
				<h3 class="bar" style="padding:7px;">Change Category</h3>
				<form id='adminform' action='{$this->settings['base_url']}module=quiz&amp;section=quiz&amp;do=changecat&amp;id={$quiz['quiz_id']}' method='post'>
					<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
					<input type='hidden' name='quiz_id' value='{$quiz['quiz_id']}' />
					<table class='ipsTable double_pad'>
						<tr>
		        			<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizcat']}</td>
		        			<td class='field_field'>{$catlist}</td>
		        		</tr>
		        	</table>
		        	<div class='acp-actionbar'>
      					<input type='submit' class='button' id='changeCat' value='{$this->lang->words['quiz_acp_submit']}' />      				
    				</div>
		        </form>
				</div>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	

	
	// Answers
	
	
}
?>