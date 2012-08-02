<?php

class FacebookIdentifier extends DataExtension {
	public static $db = array(
		'FacebookID' => 'Varchar',
		'FacebookName' => 'Varchar(255)',
	);
	
	public function updateMemberFormFields(FieldSet $fields) {
		$fields->removeByName('FacebookID');
		$fields->removeByName('FacebookName');
		
		if(Member::CurrentUser() && Member::CurrentUser()->exists()) {
			$fields->push($f = new ReadonlyField('FacebookButton', 'Facebook'));
			$f->dontEscape = true;
		} else {
			$fields->push(new HiddenField('FacebookButton', false));
		}
	}
	
	public function getFacebookButton() {
		if($this->owner->exists()) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::javascript('facebook/javascript/facebook.js');
			if($this->hasFacebook()) {
				$token = SecurityToken::inst();
				$removeURL = Controller::join_links('FacebookCallback', 'RemoveFacebook');
				$removeURL = $token->addToUrl($removeURL);
				return 'Connected to Facebook user ' . $this->owner->FacebookName . '. <a href="' . $removeURL . '" class="unconnect-facebook">Disconnect</a>';
			} else {
				return '<img src="facebook/Images/connect.png" class="connect-facebook" alt="Connect to Facebook" />';
			}
		}
	}

	public function hasFacebook() {
		return (bool)$this->owner->FacebookID;
	}

	public function isConnected() {

		require_once FACEBOOK_PATH . '/lib/facebook.php';

		$facebook = new Facebook(array(
			'appId' => FacebookCallback::get_facebook_id(),
			'secret' => FacebookCallback::get_facebook_secret()
		));

		return (bool)$facebook->getUser();
	}

	public function getFacebookPages() {
		if(!$this->hasFacebook()) {
			return array();
		}

		$pages = array(
			'me/feed' => 'Personal Page'
		);

		$facebook = new Facebook(array(
			'appId' => FacebookCallback::get_facebook_id(),
			'secret' => FacebookCallback::get_facebook_secret()
		));

		$user = $facebook->getUser();

		if($user) {
			try {
				$resp = $facebook->api('/me/accounts', 'GET');

				if(isset($resp->data)) {
					foreach($res->data as $app) {
						if($app->category != 'Application') {
							$pages[$app->id] = $app->name . ' <small>(' . $app->category . ')</small>';
						}
					}
				}
			} catch(FacebookApiException $e) {
				SS_Log::log($e, SS_Log::ERR);
			}
		}

		return $pages;
	}
}
