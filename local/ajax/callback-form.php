<?php

define('CALLBACK_FORM_AJAX', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

/** @var CMain $APPLICATION */
global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:form.result.new',
	'callback',
	array(
		'WEB_FORM_ID' => 'CALLBACK',
		'USE_EXTENDED_ERRORS' => 'Y',
		'IGNORE_CUSTOM_TEMPLATE' => 'Y',
		'CACHE_TYPE' => 'N',
		'LIST_URL' => '',
		'EDIT_URL' => '',
		// компонент после записи всегда редиректит, fetch пройдёт по 302 и заберёт JSON
		'SUCCESS_URL' => '/local/ajax/callback-form.php',
		'AJAX_URL' => '/local/ajax/callback-form.php',
		'AUTO_OPEN_DELAY' => 0,
	)
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
