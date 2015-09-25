<?php

## require connection parametrs
require_once '../common.php'; 

## require library
require_once '../../SchemaDB.php';

## retrive main class to enstablish connection
use SourceForge\SchemaDB\SchemaDB;

## Connect to MySQL database
$Schema = new SchemaDB(array(
	'host' => $host,
	'user' => $user,
	'pass' => $pass,
	'name' => $name,
	'pref' => 't100_',
));

## Apply schema create or update database tables
$Schema->apply(array(
	
	## define users table
	'User' => array(
		'userid'   => 0,
		'username' => '',
		'password' => '',
		'type'	=> 1
	),
	
	## define articles table
	'Article' => array(
		'articleid' => 0,
		'title'		=> '',
		'content'	=> '',
	),	
));

##
$Schema->dump();