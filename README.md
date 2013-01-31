# Facebook Authentication Module 2.1 For SilverStripe

Developed and maintained by PocketRent
  support@pocketrent.com/@PocketRent

Some alterations by Gordon Anderson, Web of Talent / gordon@weboftalent.asia

# Requirements:
* Facebook application id/secret
	- https://www.facebook.com/developers/apps.php

# Installation:
- Extract facebook folder to your site root
- Provide your application id/secret using the Config API
	(FacebookCallback->facebook_id/secret)
- Run /dev/build

This adds two extra fields to the Member table, FacebookID and FacebookName.

This module adds a button to MemberFormFields, but not FrontendField. You can
 your own button as well. For an example of how to do so, have a look at
FacebookIdentifier, facebook.js and the three related methods in FacebookCallback.
