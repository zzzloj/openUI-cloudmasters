<?php

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

/* Clean up from previous versions */
if ( $DB->checkForTable( 'gallery_albums' ) AND $DB->checkForTable( 'gallery_albums_main' ) )
{
	$SQL[] = "DROP TABLE gallery_albums;";
}

if ( $DB->checkForTable( 'gallery_albums_temp' ) )
{
	$SQL[] = "DROP TABLE gallery_albums_temp;";
}

if ( $DB->checkForTable( 'gallery_categories' ) )
{
	$SQL[] = "DROP TABLE gallery_categories;";
}

if ( $DB->checkForTable( 'gallery_ecardlog' ) )
{
	$SQL[] = "DROP TABLE gallery_ecardlog;";
}

if ( $DB->checkForTable( 'gallery_favorites' ) )
{
	$SQL[] = "DROP TABLE gallery_favorites;";
}

if ( $DB->checkForTable( 'gallery_subscriptions' ) )
{
	$SQL[] = "DROP TABLE gallery_subscriptions;";
}

if ( $DB->checkForTable( 'gallery_upgrade_history' ) )
{
	$SQL[] = "DROP TABLE gallery_upgrade_history;";
}

if ( $DB->checkForField( 'category_id', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP category_id;";
}

/* This old index laying around? */
if( $DB->checkForIndex( 'date', 'gallery_bandwidth' ) )
{
	$SQL[] = "ALTER TABLE gallery_bandwidth DROP INDEX `date`;";
}

/* Old settings potentially still around */
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ( 'gallery_display_category', 'gallery_display_album', 'gallery_default_view', 'gallery_enable_both_views', 
	'gallery_full_image', 'gallery_display_block_row', 'gallery_web_accessible', 'gallery_display_photostrip', 'gallery_last_updated', 'gallery_show_lastpic', 
	'gallery_display_subcats', 'gallery_dir_images', 'gallery_cache_albums', 'gallery_stats_where', 'gallery_images_per_block', 'gallery_last5_images', 
	'gallery_random_images', 'gallery_stats', 'gallery_feature_image', 'gallery_thumbnail_link', 'gallery_thumb_width', 'gallery_thumb_height', 'gallery_allowed_domains',
	'gallery_antileech_image', 'gallery_use_rate', 'gallery_rate_display', 'gallery_bandwidth_thumbs', 'gallery_use_ecards', 'display_hotlinking', 'gallery_guests_ecards',
	'gallery_comment_order', 'gallery_allow_usercopyright', 'gallery_copyright_default', 'gallery_exif', 'gallery_iptc', 'gallery_exif_sections', 'gallery_notices_cat',
	'gallery_notices_album', 'gallery_notices_img', 'gallery_idx_num_col', 'gallery_idx_num_row', 'gallery_stats_col', 'gallery_stats_row', 'gallery_stats_cols', 'gallery_stats_rows' );";

/* Convert ratings table (easy) */
$SQL[] = "ALTER TABLE gallery_ratings CHANGE id rate_id BIGINT NOT NULL AUTO_INCREMENT,
			CHANGE member_id rate_member_id INT NOT NULL DEFAULT 0,
			CHANGE rating_where rate_type VARCHAR(32) NOT NULL DEFAULT 'image',
			CHANGE rating_foreign_id rate_type_id BIGINT NOT NULL DEFAULT 0,
			CHANGE rdate rate_date INT NOT NULL DEFAULT 0,
			CHANGE rate rate_rate INT NOT NULL DEFAULT 0,
			DROP INDEX rating_find_me,
			ADD INDEX rating_find_me ( rate_member_id, rate_type, rate_type_id );";

/* Convert comments table (easy) - ignore keyword is added to prevent warnings about truncating the edit time */
$SQL[] = "ALTER IGNORE TABLE {$PRE}gallery_comments CHANGE pid comment_id INT NOT NULL AUTO_INCREMENT,
			CHANGE edit_time comment_edit_time INT NOT NULL DEFAULT 0,
			CHANGE author_id comment_author_id INT NOT NULL DEFAULT 0,
			CHANGE author_name comment_author_name VARCHAR(255) NULL DEFAULT NULL,
			CHANGE ip_address comment_ip_address VARCHAR(46) NULL DEFAULT NULL,
			CHANGE post_date comment_post_date INT NOT NULL DEFAULT 0,
			CHANGE comment comment_text TEXT NULL DEFAULT NULL,
			CHANGE approved comment_approved TINYINT NOT NULL DEFAULT 0,
			CHANGE img_id comment_img_id BIGINT NOT NULL DEFAULT 0,
			ADD INDEX (comment_ip_address),
			DROP INDEX img_id,
			ADD INDEX img_id (comment_img_id,comment_post_date);";

if ( $DB->checkForField( 'use_sig', 'gallery_comments' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP use_sig;";
}

if ( $DB->checkForField( 'use_emo', 'gallery_comments' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP use_emo;";
}

if ( $DB->checkForField( 'edit_name', 'gallery_comments' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP edit_name;";
}

if ( $DB->checkForField( 'append_edit', 'gallery_comments' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP append_edit;";
}

if ( $DB->checkForIndex( 'img_id_2', 'gallery_comments' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP INDEX img_id_2;";
}

/* Add new column to temp uploads table (easy) */
$SQL[] = "ALTER TABLE gallery_images_uploads ADD upload_category_id INT NOT NULL DEFAULT 0 AFTER upload_album_id;";

/* And then convert images - not too difficult */
if ( $DB->checkForIndex( 'album_id', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX album_id;";
}

if ( $DB->checkForIndex( 'approved', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX approved;";
}

if ( $DB->checkForIndex( 'album_id_2', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX album_id_2;";
}

if ( $DB->checkForIndex( 'gb_select', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX gb_select;";
}

if ( $DB->checkForIndex( 'image_feature_flag', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX image_feature_flag;";
}

if ( $DB->checkForIndex( 'lastcomment', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX lastcomment;";
}

if ( $DB->checkForIndex( 'rnd_lookup', 'gallery_images' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX rnd_lookup;";
}

$SQL[] = "ALTER TABLE gallery_images CHANGE id image_id BIGINT NOT NULL AUTO_INCREMENT,
			CHANGE member_id image_member_id INT NOT NULL DEFAULT 0,
			ADD image_category_id INT NOT NULL DEFAULT 0 AFTER image_member_id,
			CHANGE img_album_id image_album_id BIGINT NOT NULL DEFAULT 0,
			CHANGE caption image_caption VARCHAR(255) NOT NULL,
			CHANGE description image_description TEXT NULL DEFAULT NULL,
			CHANGE directory image_directory VARCHAR(255) NULL DEFAULT NULL,
			CHANGE masked_file_name image_masked_file_name VARCHAR(255) NULL DEFAULT NULL,
			CHANGE file_name image_file_name VARCHAR(255) NULL DEFAULT NULL,
			CHANGE medium_file_name image_medium_file_name VARCHAR(255) NULL DEFAULT NULL,
			CHANGE original_file_name image_original_file_name VARCHAR(255) NULL DEFAULT NULL,
			CHANGE file_size image_file_size INT NOT NULL DEFAULT 0,
			CHANGE file_type image_file_type VARCHAR(50) NULL DEFAULT NULL,
			CHANGE approved image_approved TINYINT NOT NULL DEFAULT 0,
			CHANGE thumbnail image_thumbnail TINYINT NOT NULL DEFAULT 0,
			CHANGE views image_views INT NOT NULL DEFAULT 0,
			CHANGE comments image_comments INT NOT NULL DEFAULT 0,
			CHANGE comments_queued image_comments_queued INT NOT NULL DEFAULT 0,
			CHANGE idate image_date INT NOT NULL DEFAULT 0,
			CHANGE ratings_total image_ratings_total INT NOT NULL DEFAULT 0,
			CHANGE ratings_count image_ratings_count INT NOT NULL DEFAULT 0,
			CHANGE rating image_rating INT NOT NULL DEFAULT 0,
			CHANGE lastcomment image_last_comment INT NOT NULL DEFAULT 0,
			CHANGE pinned image_pinned TINYINT NOT NULL DEFAULT 0,
			CHANGE media image_media TINYINT NOT NULL DEFAULT 0,
			CHANGE credit_info image_credit_info TEXT NULL DEFAULT NULL,
			CHANGE copyright image_copyright VARCHAR(255) NULL DEFAULT NULL,
			CHANGE metadata image_metadata TEXT NULL DEFAULT NULL,
			CHANGE media_thumb image_media_thumb VARCHAR(255) NULL DEFAULT NULL,
			CHANGE caption_seo image_caption_seo VARCHAR(255) NOT NULL,
			CHANGE image_feature_flag image_feature_flag TINYINT NOT NULL DEFAULT 0,
			CHANGE image_gps_show image_gps_show TINYINT NOT NULL DEFAULT 0,
			ADD INDEX album_id (image_album_id, image_approved, image_date),
			ADD INDEX image_feature_flag (image_feature_flag, image_date),
			ADD INDEX gb_select (image_approved, image_parent_permission, image_date),
			ADD INDEX lastcomment (image_last_comment, image_date);";

/* Add our new tables */
$SQL[] = "CREATE TABLE gallery_categories (
  category_id int(11) NOT NULL AUTO_INCREMENT,
  category_parent_id int(11) NOT NULL DEFAULT '0',
  category_name varchar(255) DEFAULT NULL,
  category_name_seo varchar(255) DEFAULT NULL,
  category_description text,
  category_count_imgs int(11) NOT NULL DEFAULT '0',
  category_count_comments int(11) NOT NULL DEFAULT '0',
  category_count_imgs_hidden int(11) NOT NULL DEFAULT '0',
  category_count_comments_hidden int(11) NOT NULL DEFAULT '0',
  category_cover_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_date int(11) NOT NULL DEFAULT '0',
  category_type int(11) NOT NULL DEFAULT '0',
  category_sort_options text,
  category_allow_comments tinyint(4) NOT NULL DEFAULT '0',
  category_allow_rating tinyint(4) NOT NULL DEFAULT '0',
  category_approve_img tinyint(4) NOT NULL DEFAULT '0',
  category_approve_com tinyint(4) NOT NULL DEFAULT '0',
  category_rules mediumtext,
  category_rating_aggregate int(11) NOT NULL DEFAULT '0',
  category_rating_count int(11) NOT NULL DEFAULT '0',
  category_rating_total int(11) NOT NULL DEFAULT '0',
  category_after_forum_id int(11) NOT NULL DEFAULT '0',
  category_watermark tinyint(4) NOT NULL DEFAULT '0',
  category_position int(11) NOT NULL DEFAULT '0',
  category_can_tag tinyint(4) NOT NULL DEFAULT '0',
  category_preset_tags text,
  category_public_albums int(11) NOT NULL DEFAULT '0',
  category_nonpublic_albums int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (category_id),
  KEY category_last_img_id (category_last_img_id)
);";

$SQL[] = "CREATE TABLE gallery_moderators (
  mod_id int(11) NOT NULL AUTO_INCREMENT,
  mod_type varchar(32) NOT NULL DEFAULT 'group',
  mod_type_id int(11) NOT NULL DEFAULT '0',
  mod_type_name varchar(255) DEFAULT NULL,
  mod_categories text,
  mod_can_approve tinyint(4) NOT NULL DEFAULT '0',
  mod_can_edit tinyint(4) NOT NULL DEFAULT '0',
  mod_can_hide tinyint(4) NOT NULL DEFAULT '0',
  mod_can_delete tinyint(4) NOT NULL DEFAULT '0',
  mod_can_approve_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_edit_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_delete_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_move tinyint(4) NOT NULL DEFAULT '0',
  mod_set_cover_image tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (mod_id),
  KEY mod_type (mod_type,mod_type_id)
);";

/* Groups table changes? */
if ( $DB->checkForField( 'g_ecard', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_ecard;";
}

if ( $DB->checkForField( 'g_rate', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_rate;";
}

if ( $DB->checkForField( 'g_slideshows', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_slideshows;";
}

if ( $DB->checkForField( 'g_favorites', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_favorites;";
}

if ( $DB->checkForField( 'g_comment', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_comment;";
}

if ( $DB->checkForField( 'g_move_own', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_move_own;";
}

if ( $DB->checkForField( 'g_mod_albums', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_mod_albums;";
}

if ( $DB->checkForField( 'g_album_private', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_album_private;";
}

if ( $DB->checkForField( 'g_gal_avatar', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_gal_avatar;";
}

if ( $DB->checkForField( 'g_max_notes', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_max_notes;";
}

if ( $DB->checkForField( 'g_gallery_cat_cover', 'groups' ) )
{
	$SQL[] = "ALTER TABLE groups DROP g_gallery_cat_cover;";
}

$SQL[] = "ALTER TABLE groups ADD g_create_albums_private TINYINT( 1 ) UNSIGNED default '0' NOT NULL,
	ADD g_create_albums_fo TINYINT( 1 ) UNSIGNED default '0' NOT NULL,
	ADD g_delete_own_albums TINYINT( 1 ) NOT NULL DEFAULT '0';";

/* Albums handled by the upgrader script */