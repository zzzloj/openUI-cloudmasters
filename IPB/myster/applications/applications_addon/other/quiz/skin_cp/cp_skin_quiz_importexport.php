<?php
class cp_skin_quiz_importexport extends output
{ 
	function import_overview($quizzes, $categories)
	{
		$catlist = $this->registry->output->formDropDown('quiz_category_id', $categories, 'quiz_category_id', 'quiz_category_id', '', '');
		
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		//--starthtml--//
		$IPBHTML .= <<< EOF
		<div class="section_title">
			<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_importexport']}</h2>
		</div>
		<div class='acp-box'>
			<h3>{$this->lang->words['quiz_acp_importexport']}</h3>
			<div class='ipsTabBar with_left with_right' id='tabstrip_mytabs'>
  				<span class='tab_left'>&laquo;</span>
 				<span class='tab_right'>&raquo;</span>
  				<ul>
   					<li id='tab_import'>{$this->lang->words['quiz_acp_import']}</li>
   					<li id='tab_export'>{$this->lang->words['quiz_acp_export']}</li>
  				</ul>
 			</div>
 		</div>
 		<div class="clear clearfix"><br /></div>
 		<div class='ipsTabBar_content' id='tabstrip_mytabs_content'>
 			<!-- :: IMPORT :: -->
  			<div id='tab_import_content'>
  			  	<form action="{$this->settings['base_url']}module=quiz&amp;section=importexport&amp;do=import" id="importquiz" method="POST" enctype="multipart/form-data">
  				<div class='acp-box'>
  					<h3>{$this->lang->words['quiz_acp_import']}</h3>
  					<table class="ipsTable double_pad">				
  						<tbody>
  							<tr>
								<td class="field_title">
									<strong class="title">{$this->lang->words['quiz_acp_uploadquiz']}</strong>
								</td>
								<td class="field_field">
									<input class="textinput" type="file" size="30" name="FILE_UPLOAD" id=""><br>
									<span class="desctext">{$this->lang->words['quiz_acp_uploadquiz_desc']}</span>
								</td>
							</tr>
							<!-- Looking through my source? I was going to include this but decided against it. Maybe in future, amigo.
							<tr>
								<td class="field_title">
									<strong class="title">{$this->lang->words['quiz_acp_uploadquiz_remote']}</strong>
								</td>
								<td class="field_field">
									<input type="text" name="importLocation" id="importLocation" value="" size="30" class="input_text"><br>
									<span class="desctext">{$this->lang->words['quiz_acp_uploadquiz_remote_desc']}</span>
								</td>
							</tr>
							<tr>
								<td class="field_title">
									<strong class="title">{$this->lang->words['quiz_acp_importasuser']}</strong>
								</td>
								<td class="field_field">
									<input type="text" name="importName" id="importName" value="" size="30" class="input_text"><br>
									<span class="desctext">{$this->lang->words['quiz_acp_importasuser_desc']}</span>
								</td>
							</tr> -->
							<tr>
		        				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_quizcat']}</td>
		        				<td class='field_field'>{$catlist}</td>
		        			</tr>
						</tbody>
					</table>
					<div class="acp-actionbar">
						<input type="submit" value="Import Quiz" class="realbutton">
					</div>
  				</div>
  				</form>
  			</div>
  				
  			<!-- :: EXPORT :: -->
  			<div id='tab_export_content'>
  				<div class='acp-box'>
  				<form action="{$this->settings['base_url']}module=quiz&amp;section=importexport&amp;do=export" id="exportquiz" method="POST">
  					<h3>{$this->lang->words['quiz_acp_export']}</h3>
  					<table class="ipsTable double_pad">				
  						<tbody>
  							<tr>
								<td class="field_title">
									<strong class="title">{$this->lang->words['quiz_acp_exportquiz']}</strong>
								</td>
								<td class="field_field">
									<select name="quiz">
EOF;
		foreach ($quizzes as $q):
		$IPBHTML .= <<< EOF
										<option id="quizID_{$q['quiz_id']}" value="{$q['quiz_id']}">{$q['quiz_name']}</option>
EOF;
		endforeach;
		$IPBHTML .= <<< EOF
									</select>
									<br />
									<span class="desctext">{$this->lang->words['quiz_acp_exportquiz_desc']}</span>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="acp-actionbar">
						<input type="submit" value="Export Quiz" class="realbutton">
					</div>
   				</div>
   				</form>
  			</div>		
  		</div>
		
		<script type='text/javascript'>
			jQ("#tabstrip_mytabs").ipsTabBar({ tabWrap: "#tabstrip_mytabs_content" });
		</script>
EOF;
		//--endhtml--//
		return $IPBHTML;
	}
	
}