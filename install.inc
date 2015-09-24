<?php

use diversen\lang;

$_INSTALL['MAIN_MENU_ITEM'] = array (
    'title' => lang::translate('Profile', null ,array ('no_translate' => true)),
    'url' => '/userinfo/index',
    'module_name' => 'userinfo',
    'parent' => 0,
    'weight' => 2,
    'auth' => 'user'
);

$_INSTALL['PUBLIC_CLONE_URL'] = "https://github.com/diversen/userinfo.git";
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/userinfo.git';
