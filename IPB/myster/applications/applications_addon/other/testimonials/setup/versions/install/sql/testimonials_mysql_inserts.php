<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonial System
 * @version 1.1.0
 */

$INSERT[] = "ALTER TABLE members ADD sostestemunhos_banned tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE members ADD sostestemunhos_banned_date int(10) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_view tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_postar_testemunhos tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_remove_edit_time tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_max_time_edit int(10) default '30';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_postar_comentarios tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_aprovar tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_remover_testemunho tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_remover_comentario tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_fechar tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_editar tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_destacar tinyint(1) DEFAULT '0';";
$INSERT[] = "ALTER TABLE groups  ADD sostestemunhos_banir_membros tinyint(1) DEFAULT '0';";


/* Init our variables */
$reporters  = array();
$editors    = array();
$_reporters = ",";
$_editors   = ",";

/* Query for potential groups who could report or deal with reports */
ipsRegistry::DB()->build( array( 'select' => 'g_id, g_is_supmod', 'from' => 'groups', 'where' => 'g_view_board=1 AND g_id NOT IN('.implode( ',', array( ipsRegistry::$settings['auth_group'], ipsRegistry::$settings['guest_group'] ) ).')' ) );
$o = ipsRegistry::DB()->execute();

/* Loop... */
while ( $row = ipsRegistry::DB()->fetch( $o ) )
{
	if ( $row['g_is_supmod'] )
	{
		$editors[] = $row['g_id'];
	}
	
	$reporters[] = $row['g_id'];
}

/* Set up the strings */
$_reporters = count( $reporters ) ? ',' . implode( ',', $reporters ) . ',' : $_reporters;
$_editors   = count( $editors )   ? ',' . implode( ',', $editors ) . ','   : $_editors;

$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd) VALUES(1, 'Testimonials Plugin', 'This plugin is for reporting content in the (SOS30) Testimonials application.', 'Adriano Faria', 'http://forum.sosinvision.com.br/', 'v1.0.0', 'testimonials', '{$_reporters}', '{$_editors}', 'N;', 1);
EOF;

?>