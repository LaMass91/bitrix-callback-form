# Форма обратного звонка

Всплывающее окно, открывается через 20 секунд после захода на страницу.
Поля настраиваются в админке в «Веб-формах», шаблон `bitrix:form.result.new`
отдаёт их описание в JSON, Vue 3 рисует. Форма создаётся миграцией.

Проверено на Bitrix 26.150, PHP 8.2, sprint.migration 5.14.

## Установка

1. Скопировать `local/` и `callback-demo/` в корень сайта.

2. В `local/php_interface/init.php` подключить валидатор (файл есть в репозитории,
   если свой init.php уже есть — просто добавить строку):

   ```php
   require_once __DIR__ . '/include/form_validator_regexp.php';
   ```

3. Поставить [sprint.migration](https://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
   и накатить миграцию:

   ```bash
   php local/modules/sprint.migration/tools/migrate.php up
   ```

4. Смотреть на `/callback-demo/`. Для всех страниц — перенести вызов компонента
   из демо-страницы в `footer.php` шаблона сайта.

Заявки — в «Сервисы → Веб-формы → Результаты».

## Заметки

* `form.result.new` после успешной записи всегда делает редирект, поэтому `SUCCESS_URL`
  указывает на сам ajax-обработчик: fetch проходит по 302 и забирает JSON.
* Валидатора по регулярке в модуле нет, добавил свой через `onFormValidatorBuildList`.
  Один и тот же шаблон телефона уходит и в браузер, и на сервер.
* Vue лежит файлом (`vue.global.prod.js`), сборщика в проекте нет.
* Задержка открытия — параметр `AUTO_OPEN_DELAY`, открыть вручную — `window.callbackForm.open()`.
