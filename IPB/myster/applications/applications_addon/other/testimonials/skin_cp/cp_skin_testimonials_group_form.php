<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */
 
class cp_skin_testimonials_group_form extends output
{

public function __destruct()
{
}

//===========================================================================
// Testemunhos: GROUPS: LIST
//===========================================================================
function acp_group_form_main( $group, $tabId ) {

$form							= array();
$form['sostestemunhos_view']	= $this->registry->output->formYesNo( "sostestemunhos_view", $group['sostestemunhos_view'] );
$form['sostestemunhos_postar_testemunhos']	= $this->registry->output->formYesNo( "sostestemunhos_postar_testemunhos", $group['sostestemunhos_postar_testemunhos'] );
$form['sostestemunhos_postar_comentarios']	= $this->registry->output->formYesNo( "sostestemunhos_postar_comentarios", $group['sostestemunhos_postar_comentarios'] );
$form['sostestemunhos_max_time_edit']	= $this->registry->output->formSimpleInput( "sostestemunhos_max_time_edit", $group['sostestemunhos_max_time_edit'], 15 );
$form['sostestemunhos_remove_edit_time']	= $this->registry->output->formYesNo( "sostestemunhos_remove_edit_time", $group['sostestemunhos_remove_edit_time'] );
$form['sostestemunhos_aprovar']	= $this->registry->output->formYesNo( "sostestemunhos_aprovar", $group['sostestemunhos_aprovar'] );
$form['sostestemunhos_remover_testemunho']	= $this->registry->output->formYesNo( "sostestemunhos_remover_testemunho", $group['sostestemunhos_remover_testemunho'] );
$form['sostestemunhos_remover_comentario']	= $this->registry->output->formYesNo( "sostestemunhos_remover_comentario", $group['sostestemunhos_remover_comentario'] );
$form['sostestemunhos_banir_membros']	= $this->registry->output->formYesNo( "sostestemunhos_banir_membros", $group['sostestemunhos_banir_membros'] );
$form['sostestemunhos_destacar']	= $this->registry->output->formYesNo( "sostestemunhos_destacar", $group['sostestemunhos_destacar'] );
$form['sostestemunhos_fechar']	= $this->registry->output->formYesNo( "sostestemunhos_fechar", $group['sostestemunhos_fechar'] );
$form['sostestemunhos_editar']	= $this->registry->output->formYesNo( "sostestemunhos_editar", $group['sostestemunhos_editar'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF

<div id="tab_GROUPS_{$tabId}_content">
	<div>
		<table class='form_table' cellspacing='0'>
			<tr>
				<th colspan="2">
					{$this->lang->words['perms']}
				</th>
			</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_use']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_view']}  
				</td>
		 	</tr>
			<tr>
				<th colspan="2">
					{$this->lang->words['post']}
				</th>
			</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_postar_testemunhos']}</label><br />
					<span class="desctext">{$this->lang->words['g_testemunhos_use_desc']}</span>
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_postar_testemunhos']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_postar_comentarios']}</label><br />
					<span class="desctext">{$this->lang->words['g_testemunhos_use_desc']}</span>
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_postar_comentarios']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_postar_removereditby']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_remove_edit_time']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_edit_max_time']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_max_time_edit']}
				</td>
		 	</tr>
			<tr>
				<th colspan="2">
					{$this->lang->words['mod']}
				</th>
			</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_aprovar']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_aprovar']}
				</td>
		 	</tr>
 			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_editar']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_editar']}
				</td>
		 	</tr>
 			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_fechar']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_fechar']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_destacar']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_destacar']}
				</td>
		 	</tr> 	
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_remover_testemunho']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_remover_testemunho']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_remover_comentario']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_remover_comentario']}
				</td>
		 	</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_testemunhos_banir_membro']}</label><br />
				</td>
				<td style='width: 60%'>
		 			{$form['sostestemunhos_banir_membros']}
				</td>
		 	</tr>
		</table>
	</div>
</div>

EOF;

return $IPBHTML;
}

//===========================================================================
// UPLOADS: GROUPS: TABS
//===========================================================================
function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_GROUPS_{$tabId}'>{$this->caches['app_cache']['testimonials']['app_title']}</li>
EOF;

return $IPBHTML;
}

}