<?php

class FacebookIdentifier extends DataObjectDecorator {
	public function extraStatics() {
		return array(
			'db' => array(
				'FacebookID' => 'Varchar',
				'FacebookName' => 'Varchar(255)',
			)
		);
	}
}
