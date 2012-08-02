<?php

class FacebookLoginForm extends LoginForm {
	protected $authenticator_class = 'FacebookAuthenticator';
	
	public function __construct($controller, $method, $fields = null, $actions = null, $checkCurrentUser = true) {
		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}
		if($checkCurrentUser && Member::currentUser() && Member::logged_in_session_exists()) {
			$fields = new FieldList(
				new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this)
			);
			$actions = new FieldList(
				new FormAction("logout", _t('Member.BUTTONLOGINOTHER', "Log in as someone else"))
			);
		} else {
			if(!$fields) {
				$fields = new FieldList(
					new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this)
				);
				if(Security::$autologin_enabled) {
					$fields->push(new CheckboxField(
						"Remember", 
						_t('Member.REMEMBERME'),
						Session::get('SessionForms.FacebookLoginForm.Remember'),
						$this
					));
				}
			}
			if(!$actions) {
				$actions = new FieldList(
					FormAction::create('dologin', 'Sign in with Facebook')->setAttribute('src', 'facebook/Images/signin.png')
				);
			}
		}
		if(!empty($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}
		parent::__construct(
			$controller,
			$method,
			$fields,
			$actions
		);
	}
	
	protected function getMessageFromSession() {
		parent::getMessageFromSession();
		if(($member = Member::currentUser()) && !$this->message) {
			$this->message = sprintf(_t('Member.LOGGEDINAS'), $member->FirstName);
		}
	}
	
	protected function dologin($data) {
		if(!empty($data['BackURL'])) {
			Session::set('BackURL', $data['BackURL']);
		}
		Session::set('SessionForms.FacebookLoginForm.Remember', !empty($data['Remember']));
		return FacebookAuthenticator::authenticate($data, $this);
	}
	
	/**
	 * Log out form handler method
	 *
	 * This method is called when the user clicks on "logout" on the form
	 * created when the parameter <i>$checkCurrentUser</i> of the
	 * {@link __construct constructor} was set to TRUE and the user was
	 * currently logged in.
	 */
	public function logout() {
		$s = new Security();
		$s->logout();
	}
}
