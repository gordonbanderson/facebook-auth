<?php

require_once FACEBOOK_PATH . '/lib/facebook.php';

class FacebookCallback extends Controller {
	
	private static $facebook_secret = null;
	private static $facebook_id = null;
	
	public static function set_facebook_secret($secret) {
		self::$facebook_secret = $secret;
	}
	
	public static function set_facebook_id($id) {
		self::$facebook_id = $id;
	}
	
	public static $allowed_actions = array(
		'Connect',
		'Login',
	);
	
	public function __construct() {
		if(self::$facebook_secret == null || self::$facebook_id == null) {
			user_error('Cannot instigate a FacebookCallback object without an application secret and id', E_USER_ERROR);
		}
		parent::__construct();
	}
	
	public function connectUser($returnTo = '') {
		$facebook = new Facebook(array(
			'appId' => self::$facebook_id,
			'secret' => self::$facebook_secret
		));
		$user = $facebook->getUser();
		if($user) {
			try {
				$user_profile = $facebook->api('/me');
				if(isset($user_profile->error)) {
					$user = null;
				}
			} catch(FacebookApiException $e) {
				$user = null;
			}
		}
		$callback = $this->AbsoluteLink('Connect?ret=' . $returnTo);
		if($user) {
			return self::curr()->redirect($callback);
		} else {
			return self::curr()->redirect($facebook->getLoginUrl(array(
				'redirect_uri' => $callback,
			)));
		}
	}
	
	public function loginUser() {
		$facebook = new Facebook(array(
			'appId' => self::$facebook_id,
			'secret' => self::$facebook_secret
		));
		$user = $facebook->getUser();
		if($user) {
			try {
				$user_profile = $facebook->api('/me');
				if(isset($user_profile->error)) {
					$user = null;
				}
			} catch(FacebookApiException $e) {
				$user = null;
			}
		}
		$callback = $this->AbsoluteLink('Login');
		if($user) {
			return self::curr()->redirect($callback);
		} else {
			return self::curr()->redirect($facebook->getLoginUrl(array(
				'redirect_uri' => $callback,
			)));
		}
	}
	
	public function index() {
		$this->httpError(403);
	}
	
	public function Login(SS_HTTPRequest $req) {
		if($req->getVar('denied')) {
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Login cancelled.');
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
			return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
		}
		$facebook = new Facebook(array(
			'appId' => self::$facebook_id,
			'secret' => self::$facebook_secret
		));
		$user = $facebook->getUser();
		if($user) {
			try {
				$data = $facebook->api('/me');
				if(isset($data->error)) {
					$user = null;
				}
			} catch(FacebookApiException $e) {
				$user = null;
			}
		}
		if(!$user) {
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Login cancelled.');
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
			return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
		}
		if(!is_numeric($user)) {
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Invalid user id received from Facebook.');
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
			return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
		}
		$u = DataObject::get_one('Member', '"FacebookID" = \'' . Convert::raw2sql($user) . '\'');
		if(!$u || !$u->exists()) {
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'No one found for Facebook user ' . $data->name . '.');
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
			return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
		}
		
		if($u->FacebookName != $data->name) {
			$u->FacebookName = $data->name;
			$u->write();
		}
		$u->login(Session::get('SessionForms.FacebookLoginForm.Remember'));
		Session::clear('SessionForms.FacebookLoginForm.Remember');
		$backURL = Session::get('BackURL');
		Session::clear('BackURL');
		return $this->redirect($backURL);
	}
	
	public function Connect(SS_HTTPRequest $req) {
		if(!$req->getVars()) {
			$this->httpError(412);
		}
		$facebook = new Facebook(array(
			'appId' => self::$facebook_id,
			'secret' => self::$facebook_secret
		));
		$user = $facebook->getUser();
		if($user) {
			try {
				$data = $facebook->api('/me');
				if(isset($data->error)) {
					$user = null;
				}
			} catch(FacebookApiException $e) {
				$user = null;
			}
		}
		if(!$user) {
			$this->httpError(412);
		}
		if($m = $this->CurrentMember()) {
			$m->FacebookID = $data->id;
			$m->FacebookName = $data->name;
			$m->write();
		}
		$ret = $req->getVar('ret');
		if($ret) {
			return $this->redirect($ret);
		} else {
			return $this->redirect(Director::baseURL());
		}
	}
	
	public function AbsoluteLink($action = null) {
		return Director::absoluteURL($this->Link($action));
	}
	
	public function Link($action = null) {
		return self::join_links('FacebookCallback', $action);
	}
}
