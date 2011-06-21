<?php

class FacebookAuthenticator extends Authenticator {
	public static function get_name() {
		return 'Facebook';
	}
	
	public static function get_login_form(Controller $controller) {
		return new FacebookLoginForm(
			$controller,
			'LoginForm'
		);
	}
	
	public static function authenticate($RAW_data, Form $form = null) {
		return singleton('FacebookCallback')->loginUser();
	}
}
