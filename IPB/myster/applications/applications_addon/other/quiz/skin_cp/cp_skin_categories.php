<?php

class cp_skin_categories extends output
{
	function allCategories($categories) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
        //--starthtml--//
        $IPBHTML .= <<< EOF
        
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_categories']}</h2>
			<div class="ipsActionBar clearfix">
				<ul>
					<li class="ipsActionButton">
						<a href="{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=add"><img src="{$this->settings['board_url']}/{$adminDir}/skin_cp/images/icons/add.png" alt=""> {$this->lang->words['quiz_acp_catform_title']}</a>
					</li>
				</ul>
			</div>
		</div>
		
        <div class="acp-box">
        	<h3 class="maintitle">{$this->lang->words['quiz_acp_allcategories']}</h3>
        	<table id="quiz_categories_overview" class="ipsTable ipsPad">
        		<tr>
        			<th class='col_drag'>&nbsp;</th>
        			<th>{$this->lang->words['quiz_acp_catform_catname']}</th>
        			<th></th>
        			<th></th>
        		</tr>
EOF;
if ($categories):
foreach ($categories as $cats):
        $IPBHTML .= <<< EOF
        		<tr class="ipsControlRow isDraggable" id="quiz_{$cats['category_id']}">
        			<td><span class='draghandle'>&nbsp;</span></td>
        			<td><a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$cats['category_id']}'><strong class="larger_text">{$cats['category_name']}</strong></a></td>
        			<td><img src='{$cats['category_image']}' style='width:25px !important;height:25px !important;border:0px !important;' /></td>
        			<td>
						<ul class='ipsControlStrip'>
          					<li class='i_edit'>
          						<a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$cats['category_id']}'>Edit</a>
          					</li>
          					<li class='i_delete'>
          						<a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=delete&amp;id={$cats['category_id']}' onclick='return confirm("Are you sure you want to delete?")'>Delete</a>
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
        	<td><p>{$this->lang->words['quiz_no_content']} <a href='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=add' class='mini_button'>{$this->lang->words['quiz_acp_catform_title']}</a></p></td>
        	<td></td>
        </tr>
EOF;
endif;
        $IPBHTML .= <<< EOF
        	</table>
<script type='text/javascript'>
  jQ("#quiz_categories_overview").ipsSortable(  'table',  { 
    url:  "{$this->settings['base_url']}&app=quiz&module=categories&section=categories&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
  });
</script>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	
	function addCategory() {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$category_name = $this->registry->output->formInput('category_name','','category_name','60','text','required','','140');
		//$category_image_alt = $this->registry->output->formInput('category_image','','category_image','60','text','required placeholder="'.$this->settings['board_url'].'/public/style_images/master/quiz/"','','140');
		$category_image = $this->registry->output->formUpload('category_image', 'category_image');
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="error warning message">
			{$this->lang->words['quiz_acp_catimg_notice']}        
		</div>
		<br /><br />
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']}{$this->lang->words['quiz_acp_catform_title']}</h2>
		</div>
        <div class="acp-box">
        	<h3>{$this->lang->words['quiz_acp_catform_title']}</h2>
	        <form id='adminform' enctype="multipart/form-data" action='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=add' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_catform_catname']}</strong></td>
	        			<td class='field_field'>{$category_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_catform_catimg']}</strong></td>
	        			<td class='field_field'>{$category_image}
	        			<span class="desctext"><br /><b>{$this->lang->words['quiz_acp_catform_catimgnote']}</b></span></td>
	        			
	        		</tr>
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' />
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
	
	function editCategory($category) {
		$IPBHTML = "";
		$adminDir = CP_DIRECTORY;
		$category_name = $this->registry->output->formInput('category_name',$category['category_name'],'category_name','60','text','required','','140');
		//$category_image_alt = $this->registry->output->formInput('category_image',$category['category_image'],'category_image','60','text','required placeholder="'.$this->settings['board_url'].'/public/style_images/master/quiz/"','','140');
		$category_image = $this->registry->output->formUpload('category_image', 'category_image');
		
		//--starthtml--//
        $IPBHTML .= <<< EOF
        <div class="section_title">
        	<h2>{$this->lang->words['quiz_acp_quiz']} {$this->lang->words['quiz_acp_catform_edit']} "{$category['category_name']}"</h2>
		</div>
        <div class="acp-box">
        	<h3>{$this->lang->words['quiz_acp_catform_editcat']}</h2>
	        <form id='adminform' enctype="multipart/form-data" action='{$this->settings['base_url']}module=categories&amp;section=categories&amp;do=edit&amp;id={$category['category_id']}' method='post'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
			<input type='hidden' name='category_id' value='{$category['category_id']}' />
	        	<table class='ipsTable double_pad'>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_catform_catname']}</strong></td>
	        			<td class='field_field'>{$category_name}</td>
	        		</tr>
	        		<tr>
	       				<td class='field_title'><strong class='title'>{$this->lang->words['quiz_acp_catform_catimg']}</strong></td>
	        			<td class='field_field'>{$category_image}
	        			<span class="desctext"><br /><b>{$this->lang->words['quiz_acp_catform_edit_imgnote']}</b></span></td>
	        			
	        		</tr>
	        	</table>
	        	<div class='acp-actionbar'>
      				<input type='submit' class='button' />
    			</div>
	        </form>
        </div>
EOF;

        //--endhtml--//
        return $IPBHTML;
	}
}

?>