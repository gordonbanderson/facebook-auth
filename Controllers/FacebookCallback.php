<?php

require_once FACEBOOK_PATH . '/lib/facebook.php';

class FacebookCallback extends Controller {
	
	public static function get_permissions() {
		Deprecation::notice('2.0', 'Use Config API instead.');
		$perms = Config::inst()->get('FacebookCallback', 'permissions');
		if(!is_array($perms)) return array($perms);
		return $perms;
	}
	
	public static function set_permissions($perms) {
		Deprecation::notice('2.0', 'Use Config API instead.');
		if(!is_array($perms)) $perms = array($perms);
		Config::inst()->update('FacebookCallback', 'permissions', $perms);
	}

	public static function get_facebook_secret() {
		Deprecation::notice('2.0', 'Use Config API instead.');
		return Config::inst()->get('FacebookCallback', 'facebook_secret');
	}
	
	public static function set_facebook_secret($secret) {
		Deprecation::notice('2.0', 'Use Config API instead.');
		Config::inst()->update('FacebookCallback', 'facebook_secret', $secret);
	}

	public static function get_facebook_id() {
		Deprecation::notice('2.0', 'Use Config API instead.');
		return Config::inst()->get('FacebookCallback', 'facebook_id');
	}
	
	public static function set_facebook_id($id) {
		Deprecation::notice('2.0', 'Use Config API instead.');
		Config::inst()->update('FacebookCallback', 'facebook_id', $id);
	}

	public static function get_email_fallback() {
		Deprecation::notice('2.0', 'Use Config API instead.');
		return Config::inst()->get('FacebookCallback', 'email_fallback');
	}
	
	public static function set_email_fallback($val) {
		Deprecation::notice('2.0', 'Use Config API instead.');
		Config::inst()->update('FacebookCallback', 'email_fallback', (bool)$val);
	}
	
	public static function get_current_user() {
		if(!Config::inst()->get('FacebookCallback', 'facebook_secret') || !Config::inst()->get('FacebookCallback', 'facebook_id')) {
			user_error('Cannot instigate a FacebookCallback object without an application secret and id', E_USER_ERROR);
		}
		$facebook = new Facebook(array(
			'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
			'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
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
		return $user ? $user_profile : null;
	}
	
	public static $allowed_actions = array(
		'Connect',
		'Login',
		'FinishFacebook',
		'FacebookConnect',
		'RemoveFacebook',
	);
	
	public function __construct() {
		if(!Config::inst()->get('FacebookCallback', 'facebook_secret') || !Config::inst()->get('FacebookCallback', 'facebook_id')) {
			user_error('Cannot instigate a FacebookCallback object without an application secret and id', E_USER_ERROR);
		}
		parent::__construct();
	}
	
	public function FinishFacebook($request) {
		$token = SecurityToken::inst();
		if(!$token->checkRequest($request)) return $this->httpError(400);
		
		$member = Member::currentUser();
		// var_dump($member);die();
		if($member && $member->FacebookID) {
			return '<script type="text/javascript">//<![CDATA[
			opener.FacebookResponse(' . \Convert::raw2json(array(
				'name' => $member->FacebookName,
				'pages' => $member->getFacebookPages(),
				'removeLink' => $token->addToUrl($this->Link('RemoveFacebook')),
			)) . ');
			window.close();
			//]]></script>';
		} else {
			return '<script type="text/javascript">window.close();</script>';
		}
	}
	
	public function FacebookConnect() {
		return $this->connectUser($this->Link('FinishFacebook'));
	}
	
	public function RemoveFacebook($request) {
		$token = SecurityToken::inst();
		if(!$token->checkRequest($request)) return $this->httpError(400);
		$m = Member::currentUser();
		if($m) {
			$m->FacebookID = $m->FacebookName = null;
			$m->write();
		}
	}
	
	public function connectUser($returnTo = '', Array $extra = array()) {
		$facebook = new Facebook(array(
			'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
			'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
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
		$token = SecurityToken::inst();
		if($returnTo) {
			$returnTo = $token->addToUrl($returnTo); 
			$returnTo = urlencode($returnTo);
		}
		$callback = $this->AbsoluteLink('Connect?ret=' . $returnTo);
		$callback = $token->addToUrl($callback);

		if(Config::inst()->get('FacebookCallback', 'permissions')) {
			$extra += array(
				'scope' => implode(', ', Config::inst()->get('FacebookCallback', 'permissions'))
			);
		}
		
		if($user && empty($extra)) {
			return self::curr()->redirect($callback);
		} else {
			return self::curr()->redirect($facebook->getLoginUrl(array(
				'redirect_uri' => $callback,
			) + $extra));
		}
	}
	
	public function loginUser(Array $extra = array(), $return = false) {
		$facebook = new Facebook(array(
			'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
			'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
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
		$token = SecurityToken::inst();
		if($return) {
			$return = $token->addToUrl($return);
			$return = urlencode($return);
		}
		$callback = $this->AbsoluteLink('Login' . ($return ? '?ret=' . $return : ''));
		$callback = $token->addToUrl($callback);
		if(Config::inst()->get('FacebookCallback', 'permissions')) {
			$perms = Config::inst()->get('FacebookCallback', 'permissions');
		} else {
			$perms = array();
		}
		if(Config::inst()->get('FacebookCallback', 'email_fallback')) {
			if(!$user || !isset($user_profile->email)) {
				if(!in_array('email', $perms)) {
					$perms[] = 'email';
				}
			}
		}

		if($perms) {
			$extra += array(
				'scope' => implode(', ', $perms)
			);
		}

		if($user && empty($extra)) {
			return self::curr()->redirect($callback);
		} else {
			return self::curr()->redirect($facebook->getLoginUrl(array(
				'redirect_uri' => $callback,
			) + $extra));
		}
	}
	
	public function index() {
		$this->httpError(403);
	}
	
	public function Login(SS_HTTPRequest $req) {
		$token = SecurityToken::inst();
		if(!$token->checkRequest($req)) return $this->httpError(400);
		if($req->getVar('ret')) {
			$facebook = new Facebook(array(
				'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
				'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
			));
			$user = $facebook->getUser();
			return $this->redirect($req->getVar('ret'));
		}
		if($req->getVar('denied') || $req->getVar('error_reason') == 'user_denied') {
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Login cancelled.');
			Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
			return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
		}
		if(!(Member::currentUser() && Member::logged_in_session_exists())) {
			$facebook = new Facebook(array(
				'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
				'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
			));

			$user = $facebook->getUser();
			if($user) {
				try {
					$data = $facebook->api('/me');
					if(isset($data->error)) {
						Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Login error: ' . $data->error->message);
						Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
						return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
					}
				} catch(FacebookApiException $e) {
					Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'Login error: ' . $e->message);
					Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
					return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
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
			if((!$u || !$u->exists()) && !isset($data->email)) {
				Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'No one found for Facebook user ' . $data->name . '.');
				Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
				return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
			} elseif(!$u || !$u->exists()) {
				$e = Convert::raw2sql($data->email);
				$u = DataObject::get_one('Member', '"Email" = \'' . $e . '\'');
				if(!$u || !$u->exists()) {
					Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.message', 'No one found for Facebook user ' . $data->name . '.');
					Session::set('FormInfo.FacebookLoginForm_LoginForm.formError.type', 'error');
					return $this->redirect('Security/login#FacebookLoginForm_LoginForm_tab');
				}
			}
			
			if($u->FacebookName != $data->name) {
				$u->FacebookName = $data->name;
				$u->FacebookID = $user;
				$u->write();
			}
			$u->login(Session::get('SessionForms.FacebookLoginForm.Remember'));
		}
		Session::clear('SessionForms.FacebookLoginForm.Remember');
		$backURL = Session::get('BackURL');
		Session::clear('BackURL');
		return $this->redirect($backURL);
	}
	
	public function Connect(SS_HTTPRequest $req) {
		$token = SecurityToken::inst();
		if(!$token->checkRequest($req)) return $this->httpError(400);
		if($req->getVars() && !$req->getVar('error')) {
			$facebook = new Facebook(array(
				'appId' => Config::inst()->get('FacebookCallback', 'facebook_id'),
				'secret' => Config::inst()->get('FacebookCallback', 'facebook_secret')
			));
			try {
				$data = $facebook->api('/me');

				if(isset($data->error)) {
					SS_Log::log($data->error->message, SS_Log::WARN);
				} elseif($m = Member::currentUser()) {
					$m->FacebookID = $data->id;
					$m->FacebookName = $data->name;
					$m->write();
				}
			} catch(FacebookApiException $e) {
				SS_Log::log($e, SS_Log::WARN);
			}
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
