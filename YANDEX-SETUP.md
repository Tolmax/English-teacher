# Яндекс AI в генераторе

Основные исходники: `windows-package-build/package/local-presentation-generator/`.
В форме выберите «Яндекс AI» и введите его API-ключ. Поля ключей OpenAI и Яндекса разделены.
Ключ можно сохранить локально; файлы `.env` исключены из Git.

Папка по умолчанию: `b1gun8kk36mc31tlbgpt`. Для другой папки задайте `YANDEX_FOLDER_ID` в `.env`.
Текст: YandexGPT 5.1 Pro через Text Generation API, JSON mode.
Изображения: Alice AI ART 3.0 через Images API. Описание ограничено 500 символами;
неподдерживаемые quality/output_format не отправляются. Ответ проверяется и преобразуется в PNG через GD.

Нужны права на генерацию текста и изображений в папке, действующий API-ключ и доступный баланс.
На Mac данные находятся в `~/Library/Application Support/EnglishPresentationGenerator/`.
Для изолированного тестового запуска можно задать `PRESENTATION_DATA_DIR`.

Проверка без платных запросов: `php tests/provider-test.php`.
Реальное сравнение: создать по одной презентации через каждый сервис с одинаковым списком;
оценить определения, IPA, соответствие картинок выражениям и предложения итогового теста.
Сетевые ошибки и модерация Яндекса отображаются пользователю; автоматического перехода на OpenAI нет.

Документация:
- https://aistudio.yandex.ru/ru/docs/ai-studio/text-generation/api-ref/TextGeneration/completion
- https://aistudio.yandex.ru/ru/docs/ai-studio/api/Images/createImage
- https://aistudio.yandex.ru/en/docs/ai-studio/api-ref/authentication
