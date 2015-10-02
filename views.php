<?php

namespace modules\userinfo;

use diversen\date;
use diversen\filter\markdown;
use diversen\gravatar;
use diversen\html;
use diversen\lang;


class views {
    
    
    /**
     * display a users profile page
     * @param array $account
     * @param array $info
     * @return string $html
     */
    public static function getHtml ($account, $info) {
        
        $description = markdown::filter($info['description']);
        $info = html::specialEncode($info);

        $str = '<div class="userinfo">';
        $str.= "<table>";
        $str.= "<tr>";
        
        $str.= "<td rowspan =\"3\">";
        $email = $account['email'];
        $size = 78;
        $str.= gravatar::getGravatarImg($email, $size);
        $str.= '</td>';
        
        $str.= "<td>" . lang::translate('Screen name') . MENU_SUB_SEPARATOR_SEC . "</td>";
        $str.= "<td>" . $info['screenname'] . "</td>";
        
        $str.= "</tr>";
        $str.= "<tr>";
        
        $str.= "<td>" . lang::translate('Website') . MENU_SUB_SEPARATOR_SEC . "</td>";
        if (!empty($info['website'])) {
            $link = html::createLink($info['website'], $info['website']) ;
        } else {
            $link = lang::translate('No website');
        }
        
        $str.= "<td>" . $link . "</td>";
        
        $str.= "</tr>";

        if (empty($info['birthday'])) {
            $birthday = lang::translate('Unknown');
        } else {
            $birthday = date::yearsOld($info['birthday']);
        }

        $str.= "<tr>";
        
        $str.= "<td>" . lang::translate('Age') . "</td>\n";
        $str.= "<td>" . $birthday . "</td>\n";
        
        $str.= "</tr>";
        $str.= "</table>\n";
        
        $str.= "<table><tr><td>";
        $str.= $description;
        $str.= "</td></tr></table>";
        $str.="</div>";
        return $str;
    }
}
