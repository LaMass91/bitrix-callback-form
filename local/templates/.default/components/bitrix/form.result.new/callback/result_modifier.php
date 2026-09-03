<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 */

$grouppedTypes = array('radio', 'dropdown', 'checkbox', 'multiselect');
$multipleTypes = array('checkbox', 'multiselect');

$fields = array();

foreach ($arResult['QUESTIONS'] as $sid => $question)
{
	$answers = isset($arResult['arAnswers'][$sid]) ? $arResult['arAnswers'][$sid] : array();
	if (!$answers)
	{
		continue;
	}

	$first = reset($answers);
	$type = $first['FIELD_TYPE'];
	$isGroupped = in_array($type, $grouppedTypes, true);
	$isMultiple = in_array($type, $multipleTypes, true);

	// значения полей с выбором Битрикс ждёт под SID вопроса, остальные — под ID ответа
	$name = 'form_' . $type . '_' . ($isGroupped ? $sid : $first['ID']) . ($isMultiple ? '[]' : '');

	$field = array(
		'sid' => $sid,
		'type' => $type,
		'name' => $name,
			'label' => $question['CAPTION'],
		'required' => $question['REQUIRED'] === 'Y',
		'comment' => (string)($arResult['arQuestions'][$sid]['COMMENTS'] ?? ''),
		'multiple' => $isMultiple,
		'value' => $isGroupped ? '' : (string)$first['VALUE'],
		'attrs' => array(),
		'options' => array(),
		'rules' => array(),
	);

	if (preg_match_all('/([a-z][\w:-]*)\s*=\s*"([^"]*)"/i', (string)$first['FIELD_PARAM'], $matches, PREG_SET_ORDER))
	{
		foreach ($matches as $match)
		{
			$attribute = mb_strtolower($match[1]);
			if (mb_strpos($attribute, 'on') === 0)
			{
				continue;
			}
			$field['attrs'][$attribute] = $match[2];
		}
	}

	if ($type === 'textarea' && (int)$first['FIELD_HEIGHT'] > 0)
	{
		$field['attrs']['rows'] = (int)$first['FIELD_HEIGHT'];
	}

	if ($isGroupped)
	{
		foreach ($answers as $answer)
		{
			$field['options'][] = array(
				'value' => (int)$answer['ID'],
				'label' => $answer['MESSAGE'],
				'checked' => (bool)preg_match('/\b(checked|selected)\b/i', (string)$answer['FIELD_PARAM']),
			);
		}
	}

	$fieldId = (int)($arResult['arQuestions'][$sid]['ID'] ?? 0);
	if ($fieldId > 0)
	{
		$validators = CFormValidator::GetList($fieldId);
		while ($validator = $validators->Fetch())
		{
			if ($validator['ACTIVE'] !== 'Y')
			{
				continue;
			}

			$field['rules'][] = array(
				'name' => $validator['NAME'],
				'params' => is_array($validator['PARAMS']) ? $validator['PARAMS'] : array(),
			);
		}
	}

	$field['attrs'] = (object)$field['attrs'];

	$fields[] = $field;
}

$arResult['FORM_SCHEMA'] = array(
	'formId' => (int)$arResult['arForm']['ID'],
	'title' => $arResult['arForm']['NAME'],
	'description' => $arResult['FORM_DESCRIPTION'],
	'button' => trim($arResult['arForm']['BUTTON']) !== '' ? $arResult['arForm']['BUTTON'] : 'Отправить',
	'fields' => $fields,
);
