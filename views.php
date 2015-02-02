<?php

class account_profile_views {

    public static function profilePage($id) {

        $account = account_profile::getAccount($id);

        if (empty($account)) {
            echo lang::translate('No such account');
            return;
        }

        $account_profile = account_profile::getAccountProfile($account['id']);
        $account_profile = html::specialEncode($account_profile);
        html::headline(lang::translate('Account Profile'));

        $title = lang::translate('View user profile');

        if (!empty($account_profile)) {

            $title.= MENU_SUB_SEPARATOR;
            if (empty($account_profile['screenname'])) {
                $title.= $account_profile['id'];
            } else {
                $title.= $account_profile['screenname'];
            }
        }

        //print_r($account_profile);
        template::setTitle($title);
        $str = '';
        $str.= "<table class=\"account_profile\">";
        $str.= "<tr><td rowspan =\"4\">";
        $email = $account['email'];
        $size = 120;
        $str.= html::createImage(
                        account_profile::getIdenticonImageUrl($email, config::getModuleIni('account_profile_image_size_large')), array('alt' => lang::translate('Identicon image')));


        $str.= "<td>" . lang::translate('Screen name') . MENU_SUB_SEPARATOR_SEC . "</td>";
        $str.= "<td>" . @$account_profile['screenname'] . "</td>";
        $str.= "</tr>";

        $str.= "<tr>";
        $str.= "<td>" . lang::translate('Website') . MENU_SUB_SEPARATOR_SEC . "</td>";
        $str.= "<td>" . html::createLink(@$account_profile['website'], @$account_profile['website']) . "</td>";
        $str.= "</tr>";

        $str.= "<tr>";
        $str.= "<td>" . lang::translate('Profile name') . MENU_SUB_SEPARATOR_SEC . "</td>\n";
        $str.= "<td>" . @$account_profile['firstname'] . " " . @$account_profile['lastname'] . "</td>\n";
        $str.= "</tr>";

        if (@$account_profile['birthday'] == '0000-00-00' ||
                empty($account_profile['birthday'])) {
            $birthday = '';
        } else {
            $birthday = date::yearsOld($account_profile['birthday']);
        }

        $str.= "<tr>";
        $str.= "<td>" . lang::translate('Age') . "</td>\n";
        $str.= "<td>" . $birthday . "</td>\n";
        $str.= "<tr>";
        $str.= "</table>\n";

        $account_profile['description'] = html::specialDecode(@$account_profile['description']);
        $filters = config::getModuleIni('account_profile_filters');
        $account_profile['description'] = moduleloader::getFilteredContent($filters, $account_profile['description']);

        if (!config::getModuleIni('account_profile_only_desc')) {
            echo $str;
            html::headline(lang::translate('Profile Description'));
        }

        echo $account_profile['description'] . "\n";

        // add edit link
        if ($account['id'] == session::getUserId()) {
            //echo "<br />\n";
            echo lang::translate('You are this profile') . MENU_SUB_SEPARATOR_SEC;
            echo html::createLink("/account_profile/edit/$account[id]", lang::translate('Edit profile'));
        }
        
        if (session::isAdmin()) {
            echo "<hr />";
            echo user::getProfileAdminLink($account['id']);
        }
    }

    public static function formEdit($vars) {


        if ($vars['birthday'] == '0000-00-00') {
            $vars['birthday'] = '';
        }

        if (config::getModuleIni('account_profile_jquery_markdown')) {
            moduleloader::includeTemplateCommon('jquery-markedit');
            jquery_markedit_load_assets();
        }

        $h = new html ();

        $h->init($vars, 'submit');

        $h->formStart('account_profile_form');
        $h->legend(lang::translate('Edit account profile'));

        $fields = config::getModuleIni('account_profile_fields');
        $fields = explode(',', $fields);

        if (in_array('screenname', $fields)) {
            $h->label('screenname', lang::translate('Screen name'));
            $h->text('screenname');
        }

        if (in_array('firstname', $fields)) {
            $h->label('firstname', lang::translate('First name'));
            $h->text('firstname');
        }

        if (in_array('lastname', $fields)) {
            $h->label('lastname', lang::translate('Last name'));
            $h->text('lastname');
        }

        if (in_array('email', $fields)) {
            if (isset($vars['use_email'])) {
                $h->label('email', lang::translate('Email. <br />Will not be displayed to users. Will be used for gravatars if any is set for the email. <br />We may send you a mail with some important messages.'));
                $h->text('email');
            }
        }

        if (in_array('website', $fields)) {
            $h->label('website', lang::translate('Website'));
            $h->text('website');
        }

        if (in_array('birthday', $fields)) {
            $h->label('birthday', lang::translate('Birthday. Format is: YYYY-MM-DD. Will only be used for displaying your age'));
            $h->text('birthday');
        }

        if (in_array('description', $fields)) {
            $label = lang::translate('Profile Description') . '<br />';
            $label.= moduleloader::getFiltersHelp(config::getModuleIni('account_profile_filters'));
            $h->label('description', $label);
            $h->textarea('description', null, array('class' => 'markdown'));
        }

        $h->submit('submit', lang::translate('submit'));
        $h->formEnd();

        echo $h->getStr();
    }

    public static function profileBox($vars) {
        $str = '';
        $str.= "<small>";
        $str.= "<div class=\"account_profile\">\n";
        $str.= "<table>\n";

        $str.= "<tr>\n";
        $str.= "<td  rowspan=\"2\">";
        $str.= $vars['image_link'];
        $str.= "</td>";

        $str.= "<td>\n";
        $str.= "<span class =\"smallfont\">$vars[text_link]</span>";
        $str.= "</td>";

        $str.= "</tr>\n";
        $str.= "<tr>\n";
        $str.= "<td>";
        $str.= "<span class =\"smallfont\">$vars[text]</span>";
        $str.= "</td>";
        $str.= "</tr>";

        $str.= "</table>\n";
        $str.= "</div>\n";
        $str.= "</small>";
        return $str;
    }
    
    public static function getSimpleAnonLink ($user, $text = ''){
        $str = '';
        $str.= '<div class="account_profile_small">';
        $str.= lang::translate('Submitted by: ');
        if (!empty($user['homepage'])){
            $str.= html::createLink($user['homepage'], $user['name']);
        } else {
            $str.= $user['name'];
        }
        if (!empty($text)){
            $str.= " ($text) ";
        }
        $str.= "</div>";
        return $str;
    }
    
    /**
     * @param  array $user  
     * @param  array $text
     * @return string
     */
    public static function getSimpleUserLink ($user, $text = ''){
        
        //$str = lang::translate('Submitted by: ');
        $str = '';
        $str.= '<div class="account_profile_small">';
        $str.= lang::translate('Submitted by: ') . account_profile::getProfileLink($user['id']);
       
        if (!empty($text)){
            $str.= " ($text) ";
        }
        $str.= '</div>';
        return $str;
    }
}
