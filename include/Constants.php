<?php

/**
 * Created by PhpStorm.
 * User: felima
 * Date: 24/07/2018
 * Time: 11:48
 */

//Constants to connect with the database
define('DB_USERNAME', 'notepic');
define('DB_PASSWORD', 'at)e}dtd2A$^');
define('DB_HOST', 'notepic.com.br');
define('DB_NAME', 'notepic');

define('USER_CREATED', 101);
define('USER_EXISTS', 102);
define('USER_FAILURE', 103);

define('USER_AUTHENTICATED', 201);
define('USER_NOT_FOUND', 202);
define('USER_PASSWORD_DO_NOT_MATCH', 203);

define('PASSWORD_CHANGED', 301);
define('PASSWORD_NOT_CHANGED', 303);
define('PASSWORD_DO_NOT_MATCH', 302);

define('DISCIPLINE_CREATED', 111);
define('DISCIPLINE_EXISTS', 112);
define('DISCIPLINE_FAILURE', 113);

define('PHOTO_SAVED', 121);
define('PHOTO_EXISTS', 122);
define('PHOTO_FAILURE', 123);

define('REMINDER_CREATED', 131);
define('REMINDER_EXISTS', 132);
define('REMINDER_FAILURE', 133);

define('LOG_ADD', 601);
define('LOG_FAILURE', 602);

define("PROJECT_HOME", "https://notepic.com.br/ws/");
