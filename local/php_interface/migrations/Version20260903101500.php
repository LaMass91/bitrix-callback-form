<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Sprint\Migration\Exceptions\MigrationException;

class Version20260903101500 extends Version
{
    protected $description = 'Веб-форма «Обратный звонок» (SID: CALLBACK)';

    protected $moduleVersion = '5.14.0';

    private const PHONE_PATTERN = '^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$';

    /**
     * @throws MigrationException
     * @throws Exceptions\HelperException
     */
    public function up()
    {
        if (!Loader::includeModule('form')) {
            throw new MigrationException('Не установлен модуль «Веб-формы» (form).');
        }

        $this->checkRegexpValidator();

        $helper = $this->getHelperManager();

        $formId = $helper->Form()->saveForm([
            'SID' => 'CALLBACK',
            'NAME' => 'Обратный звонок',
            'BUTTON' => 'Жду звонка',
            'DESCRIPTION' => 'Оставьте номер — перезвоним в рабочее время в течение 15 минут.',
            'DESCRIPTION_TYPE' => 'text',
            'C_SORT' => 100,
            'USE_CAPTCHA' => 'N',
            'USE_DEFAULT_TEMPLATE' => 'Y',
            'arSITE' => [$helper->Site()->getDefaultSiteIdIfExists()],
        ]);

        $helper->Form()->saveStatuses($formId, [
            [
                'TITLE' => 'Новая заявка',
                'DESCRIPTION' => 'Заявка принята, менеджер ещё не звонил',
                'C_SORT' => 100,
                'ACTIVE' => 'Y',
                'DEFAULT_VALUE' => 'Y',
            ],
            [
                'TITLE' => 'Обработана',
                'DESCRIPTION' => 'Менеджер дозвонился до клиента',
                'C_SORT' => 200,
                'ACTIVE' => 'Y',
                'DEFAULT_VALUE' => 'N',
            ],
        ]);

        $helper->Form()->saveFields($formId, [
            [
                'SID' => 'NAME',
                'TITLE' => 'Имя',
                'FIELD_TYPE' => 'text',
                'REQUIRED' => 'Y',
                'C_SORT' => 100,
                'ANSWERS' => [
                    [
                        'MESSAGE' => 'Имя',
                        'FIELD_TYPE' => 'text',
                        'FIELD_PARAM' => 'placeholder="Как к вам обращаться" autocomplete="name" maxlength="60"',
                    ],
                ],
                'VALIDATORS' => [
                    [
                        'NAME' => 'regexp',
                        'PARAMS' => [
                            'PATTERN' => '^.{2,60}$',
                            'MESSAGE' => 'Имя должно быть длиной от 2 до 60 символов',
                        ],
                    ],
                ],
            ],
            [
                'SID' => 'PHONE',
                'TITLE' => 'Телефон',
                'FIELD_TYPE' => 'text',
                'REQUIRED' => 'Y',
                'C_SORT' => 200,
                'ANSWERS' => [
                    [
                        'MESSAGE' => 'Телефон',
                        'FIELD_TYPE' => 'text',
                        'FIELD_PARAM' => 'placeholder="+7 (___) ___-__-__" inputmode="tel" autocomplete="tel" data-mask="phone"',
                    ],
                ],
                'VALIDATORS' => [
                    [
                        'NAME' => 'regexp',
                        'PARAMS' => [
                            'PATTERN' => self::PHONE_PATTERN,
                            'MESSAGE' => 'Введите телефон в формате +7 (999) 123-45-67',
                        ],
                    ],
                ],
            ],
            [
                'SID' => 'COMMENT',
                'TITLE' => 'Комментарий',
                'FIELD_TYPE' => 'textarea',
                'REQUIRED' => 'N',
                'C_SORT' => 300,
                'COMMENTS' => 'Необязательно: удобное время звонка или вопрос',
                'ANSWERS' => [
                    [
                        'MESSAGE' => 'Комментарий',
                        'FIELD_TYPE' => 'textarea',
                        'FIELD_HEIGHT' => 3,
                        'FIELD_PARAM' => 'placeholder="Например: удобно с 10 до 12" maxlength="1000"',
                    ],
                ],
                'VALIDATORS' => [
                    [
                        'NAME' => 'text_len',
                        'PARAMS' => [
                            'LENGTH_FROM' => 0,
                            'LENGTH_TO' => 1000,
                        ],
                    ],
                ],
            ],
            [
                'SID' => 'CONSENT',
                'TITLE' => 'Согласие на обработку персональных данных',
                'FIELD_TYPE' => 'checkbox',
                'REQUIRED' => 'Y',
                'C_SORT' => 400,
                'IN_RESULTS_TABLE' => 'N',
                'ANSWERS' => [
                    [
                        'MESSAGE' => 'Согласен на обработку персональных данных',
                        'VALUE' => 'Y',
                        'FIELD_TYPE' => 'checkbox',
                    ],
                ],
            ],
        ]);

        $this->clearFormCache();
    }

    /**
     * @throws Exceptions\HelperException
     */
    public function down()
    {
        $this->getHelperManager()->Form()->deleteFormIfExists('CALLBACK');

        $this->clearFormCache();
    }

    // компонент кэширует состав полей по тегу forms
    private function clearFormCache(): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag('forms');
    }

    /**
     * @throws MigrationException
     */
    private function checkRegexpValidator(): void
    {
        $registered = \CFormValidator::GetAllList();
        while ($registered && $validator = $registered->Fetch()) {
            if ($validator['NAME'] === 'regexp') {
                return;
            }
        }

        throw new MigrationException(
            'Валидатор "regexp" не зарегистрирован. Подключите local/php_interface/include/form_validator_regexp.php в init.php.'
        );
    }
}
