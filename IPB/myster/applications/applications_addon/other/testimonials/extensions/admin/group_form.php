<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

class admin_group_form__testimonials implements admin_group_form
{
	public $tab_name = '';

	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{


		/* Load skin */
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_testimonials_group_form', 'testimonials');
		
		/* Load lang */
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_groups' ), 'testimonials' );
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}

	public function getForSave()
	{
		$return = array(
			'sostestemunhos_view'				=> intval(ipsRegistry::$request['sostestemunhos_view']),
			'sostestemunhos_postar_testemunhos'	=> intval(ipsRegistry::$request['sostestemunhos_postar_testemunhos']),
			'sostestemunhos_postar_comentarios'	=> intval(ipsRegistry::$request['sostestemunhos_postar_comentarios']),
			'sostestemunhos_remove_edit_time'   => intval(ipsRegistry::$request['sostestemunhos_remove_edit_time']),
			'sostestemunhos_max_time_edit'		=> intval(ipsRegistry::$request['sostestemunhos_max_time_edit']),
			'sostestemunhos_aprovar'			=> intval(ipsRegistry::$request['sostestemunhos_aprovar']),
			'sostestemunhos_remover_testemunho'	=> intval(ipsRegistry::$request['sostestemunhos_remover_testemunho']),
			'sostestemunhos_remover_comentario'	=> intval(ipsRegistry::$request['sostestemunhos_remover_comentario']),
			'sostestemunhos_banir_membros'		=> intval(ipsRegistry::$request['sostestemunhos_banir_membros']),
			'sostestemunhos_editar'				=> intval(ipsRegistry::$request['sostestemunhos_editar']),
			'sostestemunhos_fechar'				=> intval(ipsRegistry::$request['sostestemunhos_fechar']),
			'sostestemunhos_destacar'			=> intval(ipsRegistry::$request['sostestemunhos_destacar']),
		);

		return $return;
	}
}

?>