<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonial System
 * @version 1.1.0
 */

$QUERY[] = "ALTER TABLE members DROP sostestemunhos_banned;";
$QUERY[] = "ALTER TABLE members DROP sostestemunhos_banned_date;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_view;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_postar_testemunhos;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_postar_comentarios;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_max_time_edit;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_aprovar;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_remover_testemunho;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_remover_comentario;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_banir_membros;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_fechar;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_editar;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_destacar;";
$QUERY[] = "ALTER TABLE groups  DROP sostestemunhos_remove_edit_time;";
$QUERY[] = "DELETE FROM rc_classes WHERE my_class='testemunhos';";
$QUERY[] = "DELETE FROM reputation_cache WHERE app='testemunhos';";
$QUERY[] = "DELETE FROM reputation_index WHERE app='testemunhos';";

?>