# Current Task

## Current phase

Phase 13 — Гигачат-генерация и проверка на хостинге

## Task

Реализовать генерацию изображений для ИИ-презентаций через GigaChat как альтернативу OpenAI Image API, сохранив mock-режим и защиту от потери уже созданных картинок.

## Scope

- `config/app.php`
- `.env.example`
- `app/services/gigachat-service.php`
- `app/services/openai-teacher-assistant.php`
- `app/services/ai-word-presentation-images.php`
- `app/controllers/admin/ai-presentations/build.php`
- `templates/pages/admin/ai-presentations/form.tpl`
- `templates/components/presentation-player.tpl`
- `app/services/pptx-presentation-builder.php`
- `.docs/project-status.md`

## Out of scope

- Не менять текстовую генерацию ИИ-тестов и карточек презентаций, кроме использования уже готовых `image_prompt`.
- Не менять структуру таблиц БД без явной необходимости.
- Не публиковать ИИ-презентации на страницах классов как тесты или обычные ученические задания.
- Не добавлять внешние PHP/JS-библиотеки и CDN.
- Не выводить реальные `OPENAI_API_KEY` и `GIGACHAT_AUTH_KEY` в HTML, логи, flash-сообщения или диагностику.
- Не делать деплой на Timeweb в этом таске; это следующий таск фазы.

## Acceptance criteria

- [ ] В `.env.example` есть актуальные настройки для выбора провайдера изображений: OpenAI / GigaChat / mock.
- [ ] Если выбран OpenAI и задан `OPENAI_API_KEY`, сборка презентации использует текущую OpenAI-генерацию изображений.
- [ ] Если выбран GigaChat и задан `GIGACHAT_AUTH_KEY`, сборка презентации отправляет генерацию изображений в GigaChat-сервис.
- [ ] Если ключ выбранного image-провайдера не задан, сайт мягко использует mock-картинки и не падает.
- [ ] Для каждой карточки презентации создаётся PNG/JPG-файл в `uploads/materials/{materialId}/images/`.
- [ ] Если у карточки уже есть рабочая картинка, повторная сборка не удаляет и не перезаписывает её без необходимости.
- [ ] Если GigaChat не смог вернуть картинку для одной карточки, уже созданные рабочие картинки не затираются.
- [ ] Учитель видит понятное русское сообщение об ошибке генерации картинки без секретов и технического дампа.
- [ ] HTML-показ `/presentation/{id}` отображает реальные картинки, если они созданы.
- [ ] PPTX-сборка использует те же сохранённые картинки, что и HTML-показ.
- [ ] Удаление ИИ-презентации по-прежнему удаляет связанные файлы и папку `uploads/materials/{materialId}/`.

## Important rules

- Следовать `AGENTS.md`.
- Следовать `.docs/tech-stack.md`.
- Следовать `.docs/architecture-rules.md`.
- Следовать `.docs/specs/` для PHP, HTML и CSS.
- Не изменять файлы вне Scope без явной необходимости.
- SQL только в models, бизнес-логика не в templates.
- Все пути к файлам строить от `ROOT`, публичные ссылки — от `HOST`.
- Генерируемые изображения хранить только в `uploads/`, не в `assets/`.
- Не удалять существующую картинку карточки, пока новая картинка не создана и не проверена.
- Все новые и исправленные русские строки сохранять в UTF-8.

## Verification

- [ ] `php -l config/app.php`
- [ ] `php -l app/services/gigachat-service.php`
- [ ] `php -l app/services/openai-teacher-assistant.php`
- [ ] `php -l app/services/ai-word-presentation-images.php`
- [ ] `php -l app/controllers/admin/ai-presentations/build.php`
- [ ] `php -l templates/pages/admin/ai-presentations/form.tpl`
- [ ] `php -l templates/components/presentation-player.tpl`
- [ ] `php -l app/services/pptx-presentation-builder.php`
- [ ] Локально открыть `/admin/ai-presentations`, создать презентацию из 2-3 английских слов и сгенерировать карточки.
- [ ] Собрать презентацию с image-провайдером `mock` без ключей и убедиться, что картинки появились.
- [ ] Собрать презентацию с image-провайдером `gigachat` при заданном `GIGACHAT_AUTH_KEY` и убедиться, что картинки сохранены в `uploads/materials/{materialId}/images/`.
- [ ] Открыть `/presentation/{id}` и проверить, что картинки видны.
- [ ] Скачать PPTX и проверить, что картинки есть на слайдах.
- [ ] Удалить презентацию и проверить, что папка `uploads/materials/{materialId}/` удалена.
