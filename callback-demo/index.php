<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Обратный звонок');

?>
<p>Всплывающее окно с формой открывается само через 20 секунд после загрузки страницы.</p>
<p>Открыть сразу: <a href="javascript:void(0)" onclick="window.callbackForm.open()">заказать звонок</a>.</p>
<?php

$APPLICATION->IncludeComponent(
	'bitrix:form.result.new',
	'callback',
	array(
		'WEB_FORM_ID' => 'CALLBACK',
		'USE_EXTENDED_ERRORS' => 'Y',
		'IGNORE_CUSTOM_TEMPLATE' => 'Y',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '3600',
		'LIST_URL' => '',
		'EDIT_URL' => '',
		'SUCCESS_URL' => '',
		'AJAX_URL' => '/local/ajax/callback-form.php',
		'AUTO_OPEN_DELAY' => 20000,
	)
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
