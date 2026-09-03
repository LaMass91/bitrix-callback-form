<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @var string $templateFolder
 * @var CBitrixComponentTemplate $this
 */

if (defined('CALLBACK_FORM_AJAX'))
{
	if ($arResult['isFormNote'] === 'Y')
	{
		$response = array(
			'status' => 'ok',
			'message' => nl2br($arResult['FORM_NOTE']),
		);
	}
	elseif ($arResult['isFormErrors'] === 'Y')
	{
		$errors = is_array($arResult['FORM_ERRORS']) ? $arResult['FORM_ERRORS'] : array($arResult['FORM_ERRORS']);

		$response = array(
			'status' => 'error',
			'fields' => array(),
			'common' => array(),
		);

		foreach ($errors as $key => $message)
		{
			if (is_string($key) && isset($arResult['QUESTIONS'][$key]))
			{
				$response['fields'][$key] = $message;
			}
			else
			{
				$response['common'][] = $message;
			}
		}
	}
	else
	{
		$response = array(
			'status' => 'error',
			'fields' => array(),
			'common' => array(GetMessage('CALLBACK_FORM_UNEXPECTED')),
		);
	}

	header('Content-Type: application/json; charset=' . LANG_CHARSET);
	echo json_encode($response, JSON_UNESCAPED_UNICODE);
	die();
}

$this->addExternalJs($templateFolder . '/vue.global.prod.js');
$this->addExternalJs($templateFolder . '/script.js');
$this->addExternalCss($templateFolder . '/style.css');

$schema = $arResult['FORM_SCHEMA'];
$schema['action'] = $arParams['AJAX_URL'] ?? '/local/ajax/callback-form.php';
$schema['sessid'] = bitrix_sessid();
$schema['autoOpenDelay'] = (int)($arParams['AUTO_OPEN_DELAY'] ?? 20000);
$schema['messages'] = array(
	'close' => GetMessage('CALLBACK_FORM_CLOSE'),
	'sending' => GetMessage('CALLBACK_FORM_SENDING'),
	'required' => GetMessage('CALLBACK_FORM_REQUIRED'),
	'requiredCheck' => GetMessage('CALLBACK_FORM_REQUIRED_CHECK'),
	'email' => GetMessage('CALLBACK_FORM_EMAIL'),
	'minLength' => GetMessage('CALLBACK_FORM_MIN_LENGTH'),
	'maxLength' => GetMessage('CALLBACK_FORM_MAX_LENGTH'),
	'invalid' => GetMessage('CALLBACK_FORM_INVALID'),
	'sendError' => GetMessage('CALLBACK_FORM_SEND_ERROR'),
	'networkError' => GetMessage('CALLBACK_FORM_NETWORK_ERROR'),
	'requiredHint' => GetMessage('CALLBACK_FORM_REQUIRED_HINT'),
);

?>
<div class="cbf-root" data-callback-form>
	<script type="application/json" data-callback-schema><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</div>
