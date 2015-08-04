<?php

use diversen\conf;
use diversen\date;
use diversen\db;
use diversen\db\q;
use diversen\filter\markdown;
use diversen\gravatar;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\template;
use diversen\template\assets;
use diversen\template\meta;
use diversen\uri;
use diversen\user;
use diversen\valid;
use diversen\sendfile;

class userinfo {
    
    public function __construct () { 
        assets::setInlineCss(conf::getModulePath('userinfo') . "/assets.css");
    }

    
    /**
     * get logout html
     * @return string $html
     */
    public function getLogoutHTML () {

        $user_id = session::getUserId();
        $user = user::getAccount($user_id);
        
        $info = $this->get($user_id);
        
        $str = '';
        $str.= lang::translate('You are logged in using email: ');
        $str.= $user['email'] . "<br />";
        
        if (empty($info)) {
            $str.= lang::translate('Your screenname is not set yet');
        } else {
            $str.= lang::translate('Your screenname is: ');

        }
        //$str.= "" . $this->getProfileLink($) . "<br />";
        $str.= "<br /> " . $this->getLink($user_id);
        $str.= "<hr />";
        
        $logout = lang::translate('Logout');
        $str.= $logout_link = html::createLink("/account/logout", $logout);
        return $str;
    }
    
    /**
     * get profile based on account id
     * @param int $user_id
     * @return array $row
     */
    public function get ($user_id) {
        return q::select('userinfo')->filter('user_id =', $user_id)->fetchSingle();
    }
    
    /**
     * get profile edit link
     * @return string $html
     */
    public function getProfileEditLink() {
        $user_id = session::getUserId();
        return html::createLink("/userinfo/edit/$user_id", 'Edit profile');
    }
    
    public static $preText = null;
    public function setPreText ($text) {
        self::$preText = $text;
    } 
    
    public function getPreText() {
        if (!self::$preText) {
            return lang::translate('Submitted by: ');
        }
        return self::$preText;
    }
    
    /**
     * 
     * get profile link from account array with text and link
     * @param array $account
     *              $user[id] = 0 if anonymous submission
     * @param string $text text to notice smething about the user
     * @param array $options
     * @return string $str html profile 
     */
    public function getProfile ($account, $text = '', $options = array ()) {
        
        $str = '';
        $str.= '<div class="userinfo"> '; 
        $str.= $this->getPreText();        
        $str.= $this->getLink($account['id']);
        $str.= " ($text)";
        $str.= '</div>';
        return $str;
    }
    
    /**
     * get profile link without text from account array
     * @param type $account
     * @return type
     */
    public function getProfileLink ($account) {
        $link = $this->getLink($account['id']);
        return '<div class="userinfo">' . $link . '</div>';
    }
    
    /**
     * get profile link
     * @param array $account
     * @return string $html 
     */
    public function getLink ($user_id) {

        $str = '';
        $info = $this->get($user_id);
        if (empty($info)) {  
            $t_user = lang::translate('User');
            $str.= 
                    html::createLink("/userinfo/view/$user_id", 
                            $t_user . " $user_id");
        } else {
            $str.= 
                    html::createLink("/userinfo/view/$user_id", 
                            html::specialEncode($info['screenname']));
        }
        return $str;
    }
    
    /**
     * view action
     * @return void
     */
    public function viewAction () {
        
        $user_id = uri::fragment(2);
        $info = $this->get($user_id);
        
        $account = user::getAccount($user_id);
        if (empty($account)) {
            moduleloader::setStatus(404);
            return;
        }
        
        
        
        if (empty($info)) {
            $info = $this->getDefaultInfo();
        }
        
        $title = lang::translate('Profile page ');
        $title.= $user_id. " ($info[screenname])";
        template::setTitle($title);
        

        $str = '';
        $str.= html::getHeadline('Profile');
        
        if ($account['id'] == session::getUserId()) {
            $str.= lang::translate('This is your profile.') . ' ';
            $str.=$this->getProfileEditLink() . "<br />";
        }
        
        $str.= $this->getHtml($account, $info);
        $str.= $this->getAdminLink($account['id']);
        echo $str;

    }
    
    public function getAdminLink ($user_id) {
        if (session::isAdmin()) {
            return html::createLink("/account/admin/edit/$user_id", "Admin: Edit");
        }
    }
    
    /**
     * default profile
     * @return array $profile
     */
    public function getDefaultInfo () {
        return array (
            'screenname' => 'John Doe',
            'website' => '',
            'birthday' => '',
            'description' => lang::translate('No bio yet'),
            );
    }
    
    /**
     * intex action
     * @return type
     */
    public function indexAction () {
        $str = '';
        $str.= html::getHeadline(lang::translate('Your profile'));
        $id = session::getUserId();
        if (!$id) {
            moduleloader::setStatus(403);
            return;
        }
        
        meta::setMetaAll(lang::translate('Your profile'));
        
        $str.=$this->getProfileEditLink() . "<br />";
        $account = user::getAccount($id);
        $info = $this->get($id);
       
        if (empty($info)) {
            $str.= lang::translate('Your profile is empty!');
            $str.= "<br />";
            $info = $this->getDefaultInfo();
        }
        
        $str.=$this->getHtml($account, $info);
        echo $str;
    }

    /**
     * display a users profile page
     * @param array $account
     * @param array $info
     * @return string $html
     */
    public function getHtml ($account, $info) {
        

        $description = markdown::filter($info['description']);
        $info = html::specialEncode($info);

        $str = '';
        $str.= "<table class=\"account_profile\">";
        $str.= "<tr><td rowspan =\"3\">";
        $email = $account['email'];
        $size = 78;
        $str.= gravatar::getGravatarImg($email, $size);

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
        $str.= "<tr>";
        $str.= "</table>\n";
        $str.= "<table><tr><td>";
        $str.= $description;
        $str.= "</td></tr></table>";
        return $str;
    }
    
    /**
     * fileds to enter into db
     * @var array $array
     */
    public $fields = array ('screenname', 'website', 'birthday', 'description');
    
    /**
     *  updat or add to database
     * @return int $res
     */
    public function update () {
        $values = db::prepareToPost($this->fields);
        if (empty($values['screenname'])) {
            $values['screenname'] = 'John Doe';
        }
        
        $values['user_id'] = session::getUserId();
        $values = html::specialDecode($values);
        
        $db = new db();
        return $db->replace(
                'userinfo', 
                $values, 
                array ('user_id' => $values['user_id']));
    }
    
    /**
     * edit action
     * @return type
     */
    public function editAction () {
        if (!session::checkAccess('user')) {
            moduleloader::setStatus(403);
            return;
        }
        
        if (!empty($_POST)) {
            $this->validate();
            if (empty($this->errors)) {
                $this->update();
                http::locationHeader('/userinfo/index', lang::translate('Your profile has been updated'));
            } else {
                echo html::getErrors($this->errors);
            }
        }
        
        $info = $this->get(session::getUserId());
        if (empty($info)) {
            $info = $this->getDefaultInfo();
        }
        echo $this->form($info);
    }
    
    /**
     * var holding errors
     * @var array $errors
     */
    public $errors = array ();
    
    /**
     * validate form submission
     * @return type
     */
    public function validate () {
        
        if (!empty($_POST['website'])) {
            $v = new valid();
            if (!$v->url($_POST['website'])) {
                $this->errors['website'] = lang::translate('Not a valid website');
            }
        }
        
       
        if (!empty($_POST['birthday'])) {
            
            if (!date::isValid($_POST['birthday'])) {
                $this->errors['birthday'] = lang::translate('Not a valid birthday');
                return;
            }
            
            if (date::yearsOld($_POST['birthday']) > 120) {
                $this->errors['birthday'] = lang::translate('You are not that old');
                return;
            }
        }
    }
    
    public function form ($values = array ()) {
        moduleloader::includeTemplateCommon('jquery-markedit');
        jquery_markedit_load_assets();   
        
        $f = new html();
        $f->init($values, 'submit', true);
        
        $f->formStart();
        $f->legend(lang::translate('Edit your profile information'));
        
        $f->label('screenname', lang::translate('Screen name'));
        $f->text('screenname');
        $f->label('website', lang::translate('Your website'));
        $f->text('website');
        
        $f->label('birthday', lang::translate('Your birthday. Format is yyyy-mm-dd'));
        $f->text('birthday');
        $f->label('description', lang::translate('Write a few words about yourself'));
        $f->textareaMed('description', null , array ('class' => 'markdown'));
        $f->submit('submit', lang::translate('Update'));
        echo $f->getStr();
    }
}

class userinfo_module extends userinfo {}
