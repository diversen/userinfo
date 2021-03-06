<?php

namespace modules\userinfo;

use diversen\conf;
use diversen\date;
use diversen\db;
use diversen\db\q;
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
use diversen\view;

use modules\userinfo\views as userinfoViews;

view::includeOverrideFunctions('userinfo', 'views.php');

class module {
    
        
    /**
     * Var holding errors
     * @var array $errors
     */
    public $errors = array ();
    
    /**
     * Var holding pretext - text after user link
     * @var type 
     */
    public $preText = null;
    
    /**
     * Set pretext
     * @param string $text
     */
    public function setPreText ($text) {
        $this->preText = $text;
    } 
    
    /**
     * Fields to enter into DB
     * @var array $array
     */
    public $fields = array ('screenname', 'website', 'birthday', 'description');

    /**
     * Construct. Set CSS
     */
    public function __construct () { 
        assets::setInlineCss(conf::getModulePath('userinfo') . "/assets.css");
    }
        
    /**
     * Get pretext
     * @return string $str
     */
    public function getPreText() {
        if (!$this->preText) {
            return lang::translate('Submitted by: ');
        }
        return $this->preText;
    }
    
    /**
     * Get logout html
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
        
        $str.= "<br /> " . $this->getLink($user_id);
        $str.= "<hr />";
        
        $logout = lang::translate('Logout');
        $str.= $logout_link = html::createLink("/account/logout", $logout);
        $str.= MENU_SUB_SEPARATOR;
        
        $logout_all = lang::translate('Logout from all devices');
        $str.= $logout_link = html::createLink("/account/logoutall", $logout_all);
        
        return $str;
    }
    
    /**
     * Get profile row
     * @param int $user_id
     * @return array $row
     */
    public function get ($user_id) {
        return q::select('userinfo')->filter('user_id =', $user_id)->fetchSingle();
    }
    
    /**
     * Get a profile edit link
     * @return string $html
     */
    public function getProfileEditLink() {
        $user_id = session::getUserId();
        if ($user_id == 0) {
            return lang::translate("Anonymous user");
        }
        
        return html::createLink("/userinfo/edit/$user_id", 'Edit profile');
    }

    
    /**
     * Get profile link from account array with text and link
     * @param array $account
     *              $user[id] = 0 if anonymous submission
     * @param string $text text to notice smething about the user
     * @param array $options
     * @return string $str html profile 
     */
    public function getProfile ($account, $text = '', $options = array ()) {

        if (empty($account)) {
            $link = lang::translate('Anonymous user');
        } else if ($account['id'] == 0) {
            $link = $this->getAnonAccount($account);
        } else {
            $link = $this->getLink($account['id']);
        }
        
        
        

        
        $str = '';
        $str.= '<span class="uk-article-meta"> ';     
        $str.= $link;
        $str.= " ($text)";
        $str.= '</span>';
        return $str;
    }
    
    /**
     * Get anon account link
     * @param array $account
     * @return string $str html
     */
    public function getAnonAccount($account) {
        $str = '';
        $options = array ('class' => 'module_link');
        $str.= html::createLink($account['homepage'], html::specialEncode($account['name']), $options);
        return $str;
    }
    
    /**
     * Get profile link without text from account array
     * @param array $account
     * @return string $html link
     */
    public function getProfileLink ($account) {
        
        if (empty($account) || $account['id'] == 0) {
            return lang::translate('Anonymous user');
        }
        $link = $this->getLink($account['id']);
        return '<div class="userinfo">' . $link . '</div>';
    }
    
    /**
     * Get profile link
     * @param int $user_id
     * @return string $html 
     */
    public function getLink ($user_id) {

        if ($user_id == 0) {
            return lang::translate('Anonymous user');
        }
        
        $options = array ('class' => 'module_link');
        $str = '<i class="fa fa-user" title="' . lang::translate('User profile') . '"></i>&nbsp;';
        $info = $this->get($user_id);
        if (empty($info)) {  
            $t_user = lang::translate('User');
            $str.= 
                    html::createLink("/userinfo/view/$user_id", 
                            $t_user . " $user_id", $options);
        } else {
            $str.= 
                    html::createLink("/userinfo/view/$user_id", 
                            html::specialEncode($info['screenname']), $options);
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
        $str.= userinfoViews::getHtml($account, $info);
        $str.= $this->getAdminLink($account['id']);
        echo $str;

    }
    
    /**
     * Get a link to edit user if in admin mode
     * @param int $user_id
     * @return string $html
     */
    public function getAdminLink ($user_id) {
        if ($user_id == 0) {
            return lang::translate('Anonymous user');
        }
        if (session::isAdmin()) {
            return html::createLink("/account/admin/edit/$user_id", "Admin: Edit user");
        }
    }
    
    /**
     * Default profile
     * @return array $profile
     */
    public function getDefaultInfo () {
        return array (
            'screenname' => 'John Doe',
            'website' => '',
            'birthday' => '',
            'description' => lang::translate('No bio yet') ,
            );
    }
    
    /**
     * /userinfo/index action
     * @return void
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

        $account = user::getAccount($id);
        $info = $this->get($id);
       
        if (empty($info)) {
            $str.= lang::translate('Your profile is empty!');
            $str.= "<br />";
            $info = $this->getDefaultInfo();
        }
        
        $str.= userinfoViews::getHtml($account, $info);
        echo $str;
    }

    
    /**
     * Update or add to database
     * @return int $res
     */
    public function update () {
        $values = db::prepareToPost($this->fields);
        if (empty($values['screenname'])) {
            $values['screenname'] = 'John Doe';
        }
        
        // Make sure user only edits his personal info
        $values['user_id'] = session::getUserId();
        $values = html::specialDecode($values);
        
        $db = new db();
        return $db->replace(
                'userinfo', 
                $values, 
                array ('user_id' => $values['user_id']));
    }
    
    /**
     * Edit action - check for correct user is
     * only done in update
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
        
        // echo html::getHeadline(lang::translate('Edit your profile'));
        echo $this->form($info);
    }

    
    /**
     * Validate form submission and set errors
     * @return void
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
    
    /**
     * uUserinfo Form
     * @param array $values
     */
    public function form ($values = array ()) {
        
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
        $f->label('description', lang::translate('Write a few words about yourself. You may use markdown.'));
        $f->textareaMed('description', null , array ('class' => 'markdown'));
        $f->submit('submit', lang::translate('Update'));
        echo $f->getStr();
    }
}
