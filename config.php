<?php

namespace modules\userinfo;
use diversen\lang;
class config {

    public static function getMenuExtras() {

        $menu = array();
        $menu[] = array(
            'title' => lang::translate('Email settings'),
            'url' => '/emailparse/settings?no_action=true',
            'auth' => 'user'
        );
        
        return $menu;
    }
}
