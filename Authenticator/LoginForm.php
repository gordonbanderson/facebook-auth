<?php

class FacebookLoginForm extends LoginForm {
	protected $authenticator_class = 'FacebookAuthenticator';
	
	public function __construct($controller, $method) {
		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}
		$fields = new FieldSet(
			new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this),
			new CheckboxField("Remember", _t('Member.REMEMBERME'), Session::get('SessionForms.FacebookLoginForm.Remember'), $this)
		);
		if(!empty($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}
		parent::__construct(
			$controller,
			$method,
			$fields,
			new FieldSet(
				new ImageFormAction('dologin', 'Sign in with Facebook', 'facebook/Images/signin.png')
			)
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
}
