<?php

// Валидатор веб-форм по регулярному выражению — в модуле form такого нет.
class CFormValidatorRegexp
{
	public static function GetDescription()
	{
		return array(
			'NAME' => 'regexp',
			'DESCRIPTION' => 'Регулярное выражение',
			'TYPES' => array('text', 'textarea', 'password', 'email', 'url'),
			'SETTINGS' => array('CFormValidatorRegexp', 'GetSettings'),
			'CONVERT_TO_DB' => array('CFormValidatorRegexp', 'ToDB'),
			'CONVERT_FROM_DB' => array('CFormValidatorRegexp', 'FromDB'),
			'HANDLER' => array('CFormValidatorRegexp', 'DoValidate'),
		);
	}

	public static function GetSettings()
	{
		return array(
			'PATTERN' => array(
				'TITLE' => 'Шаблон (PCRE без ограничителей, внутри используется ~)',
				'TYPE' => 'TEXT',
				'DEFAULT' => '',
			),
			'MESSAGE' => array(
				'TITLE' => 'Текст ошибки',
				'TYPE' => 'TEXT',
				'DEFAULT' => 'Значение не соответствует формату',
			),
		);
	}

	public static function ToDB($arParams)
	{
		return serialize(array(
			'PATTERN' => (string)$arParams['PATTERN'],
			'MESSAGE' => (string)$arParams['MESSAGE'],
		));
	}

	public static function FromDB($strParams)
	{
		$arParams = unserialize($strParams, array('allowed_classes' => false));
		return is_array($arParams) ? $arParams : array();
	}

	public static function DoValidate($arParams, $arQuestion, $arAnswers, $arValues)
	{
		global $APPLICATION;

		$pattern = trim((string)$arParams['PATTERN']);
		if ($pattern === '')
		{
			return true;
		}

		$regexp = '~' . $pattern . '~u';
		if (@preg_match($regexp, '') === false)
		{
			$APPLICATION->ThrowException('Некорректный шаблон валидатора для поля ' . $arQuestion['SID']);
			return false;
		}

		foreach ($arValues as $value)
		{
			if ((string)$value === '')
			{
				continue;
			}

			if (!preg_match($regexp, $value))
			{
				$APPLICATION->ThrowException($arParams['MESSAGE'] ?: 'Значение не соответствует формату');
				return false;
			}
		}

		return true;
	}
}

AddEventHandler('form', 'onFormValidatorBuildList', array('CFormValidatorRegexp', 'GetDescription'));
