<?php

$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app) VALUES(1, 'Gallery Plugin', 'This is the plugin for making reports for the <a href=''http://www.invisionpower.com/products/gallery/'' target=''_blank''>IP.Gallery</a>.', 'Invision Power Services, Inc', 'http://www.invisionpower.com', 'v1.0', 'gallery', ',1,2,3,4,6,', ',4,6,', 'a:2:{s:15:"report_supermod";s:1:"1";s:13:"report_bypass";s:1:"1";}', 0, 'gallery');
EOF;

$INSERT[] = <<<EOF
INSERT INTO gallery_categories ( category_name, category_name_seo, category_description, category_count_imgs, category_count_comments, category_count_imgs_hidden, category_count_comments_hidden, category_cover_img_id, category_last_img_id, category_last_img_date, category_type, category_sort_options, category_allow_comments, category_allow_rating, category_approve_img, category_approve_com, category_rules, category_rating_aggregate, category_rating_count, category_rating_total, category_after_forum_id, category_watermark, category_position, category_can_tag, category_preset_tags) VALUES('Members Albums Category', 'members-albums-category', 'This is the special members albums category', 0, 0, 0, 0, 0, 0, 0, 1, 'a:2:{s:3:"key";s:10:"album_name";s:3:"dir";s:3:"ASC";}', 1, 1, 0, 0, 'a:2:{s:5:"title";s:0:"";s:4:"text";s:0:"";}', 0, 0, 0, 0, 0, 1, 1, '');
EOF;

/* Figure out admin and mod groups, and try to guess member groups, in order to set default category permissions */
$DB  = ipsRegistry::DB();

$_normal	= array();
$_bypass	= array();

$DB->build( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_is_supmod=1 OR g_access_cp=1" ) );
$DB->execute();
while( $r = $DB->fetch() )
{
	$_bypass[ $r['g_id'] ]	= $r['g_id'];
	$_normal[ $r['g_id'] ]	= $r['g_id'];
}

$DB->build( array( 'select' => 'g_id', 'from' => 'groups', 'where' => "g_edit_profile=1" ) );
$DB->execute();
while( $r = $DB->fetch() )
{
	$_normal[ $r['g_id'] ]	= $r['g_id'];
}

$_normal	= count($_normal) ? ',' . implode( ',', $_normal ) . ',' : '';
$_bypass	= count($_bypass) ? ',' . implode( ',', $_bypass ) . ',' : '';

$INSERT[] = <<<EOF
INSERT INTO permission_index ( app, perm_type, perm_type_id, perm_view, perm_2, perm_3, perm_4, perm_5 ) VALUES ( 'gallery', 'categories', 1, '*', '{$_normal}', '{$_normal}', '{$_normal}', '{$_bypass}' );
EOF;


/* Try to guess some default group permissions */
$INSERT[] = <<<EOF
UPDATE groups SET g_max_diskspace='0', g_max_upload='0', g_max_transfer='0', g_max_views='0', g_create_albums=0, g_create_albums_private=0, g_create_albums_fo=0, g_album_limit='0', g_img_album_limit='0',
	g_edit_own=0, g_del_own=0, g_img_local=0, g_movies=0, g_movie_size='0', g_gallery_use=1, g_delete_own_albums=0;
EOF;

$INSERT[] = <<<EOF
UPDATE groups SET g_max_diskspace='-1', g_max_upload='-1', g_max_transfer='-1', g_max_views='-1', g_create_albums=1, g_create_albums_private=1, g_create_albums_fo=1, g_album_limit='-1', g_img_album_limit='-1',
	g_edit_own=1, g_del_own=0, g_img_local=0, g_movies=1, g_movie_size='-1', g_gallery_use=1, g_delete_own_albums=0 WHERE g_edit_profile=1;
EOF;

$INSERT[] = <<<EOF
UPDATE groups SET g_max_diskspace='-1', g_max_upload='-1', g_max_transfer='-1', g_max_views='-1', g_create_albums=1, g_create_albums_private=1, g_create_albums_fo=1, g_album_limit='-1', g_img_album_limit='-1',
	g_edit_own=1, g_del_own=1, g_img_local=0, g_movies=1, g_movie_size='-1', g_gallery_use=1, g_delete_own_albums=1 WHERE g_is_supmod=1 OR g_access_cp=1;
EOF;
