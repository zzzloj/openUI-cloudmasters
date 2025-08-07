<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$TABLE[] = "CREATE TABLE faq_questions (
    question_id mediumint(11) not null auto_increment,
    question text,
    answer text,
    width smallint(3) default 0,
    approved tinyint(1) default 0,
    PRIMARY KEY (question_id),
    KEY approved (approved)
    );";
    
$TABLE[] = "CREATE TABLE faq_collections (
    collection_id mediumint(11) not null auto_increment,
    collection_key varchar(50) not null,
    name varchar(255),
    heading varchar(255),
    description text,
    sequence smallint(5) default 0,
    PRIMARY KEY (collection_id),
    KEY collection_key (collection_key)
    );";
    
$TABLE[] = "CREATE TABLE faq_collections_questions (
    collection_id mediumint(11) not null,
    question_id mediumint(11) not null,
    sequence smallint(5) default 0,
    source varchar(10),
    PRIMARY KEY (collection_id, question_id),
    KEY collection_id (collection_id),
    KEY question_id (question_id),
    KEY source (source)
    );";