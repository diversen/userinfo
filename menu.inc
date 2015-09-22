<?php

use diversen\lang;
use diversen\session;
use modules\userinfo\config;

$_MODULE_MENU = array();
if (session::isUser()) {
     
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Your profile'),
        'url' => '/userinfo/index',
        'auth' => 'user'
    );
    
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Edit your profile'),
        'url' => '/userinfo/edit/' . session::getUserId(),
        'auth' => 'user'
    );
    
    $extra = config::getMenuExtras();
    $_MODULE_MENU = array_merge($_MODULE_MENU, $extra); 
}