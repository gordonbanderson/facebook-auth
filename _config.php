<?php

define('FACEBOOK_PATH', __DIR__);

Authenticator::register_authenticator('FacebookAuthenticator');

Deprecation::notification_version('2.0', 'facebook');

if(class_exists('MemberProfilePage')) {
	MemberProfilePage::$default_editable_member_fields['FacebookButton']  = true;
}
