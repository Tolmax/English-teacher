<?php

declare(strict_types=1);

set_time_limit(600);

require __DIR__ . '/../src/bootstrap.php';
require APP_ROOT . 'src/openai.php';
require APP_ROOT . 'src/pptx-presentation-builder.php';

ensureLocalDirectory('storage/output');
ensureLocalDirectory('storage/images');
ensureLocalDirectory('public/downloads');
cleanupOldGeneratedFiles(30);
$storageStats = generatedStorageStats();

if (($_GET['action'] ?? '') === 'open-output-folder') {
    openLocalFolder(APP_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'output');
    header('Location: /');
    exit;
}

if (in_array(($_GET['action'] ?? ''), ['open-presentation', 'reveal-presentation'], true)) {
    $presentationPath = generatedPresentationPath(
        (string)($_GET['run'] ?? ''),
        (string)($_GET['file'] ?? '')
    );

    if ($presentationPath !== '') {
        ($_GET['action'] === 'open-presentation')
            ? openLocalPresentation($presentationPath)
            : revealLocalPresentation($presentationPath);
    }

    header('Location: /');
    exit;
}

$error = '';
$result = null;
$old = [
    'title' => 'English vocabulary presentation',
    'class_title' => '',
    'words' => "apple\nbook\nschool",
    'api_key' => '',
];

$envHasKey = openAiApiKey('') !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['title'] = normalizeText((string)($_POST['title'] ?? ''));
    $old['class_title'] = normalizeText((string)($_POST['class_title'] ?? ''));
    $old['words'] = trim((string)($_POST['words'] ?? ''));
    $old['api_key'] = $envHasKey ? '' : trim((string)($_POST['api_key'] ?? ''));

    try {
        if (($_POST['cleanup_storage'] ?? '') === '1') {
            cleanupGeneratedStorage();
            $storageStats = generatedStorageStats();
        }

        $apiKey = openAiApiKey($old['api_key']);
        if ($apiKey === '') {
            throw new RuntimeException('Укажите OPENAI_API_KEY в .env или вставьте ключ в поле формы.');
        }

        if (!$envHasKey && $old['api_key'] !== '' && ($_POST['save_api_key'] ?? '') === '1') {
            saveEnvValue('OPENAI_API_KEY', $old['api_key']);
            $envHasKey = true;
        }

        $title = $old['title'] !== '' ? $old['title'] : 'English vocabulary presentation';
        $classTitle = $old['class_title'];
        $sourceWords = sourceWordsFromText($old['words']);

        if ($sourceWords === []) {
            throw new RuntimeException('Добавьте хотя бы одно английское слово.');
        }

        if (count($sourceWords) > 20) {
            throw new RuntimeException('За один раз можно создать презентацию максимум на 20 слов. Разбейте большой список на несколько презентаций.');
        }

        $runId = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $runDir = APP_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . $runId . DIRECTORY_SEPARATOR;
        $imageDir = APP_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $runId . DIRECTORY_SEPARATOR;
        if (!is_dir($runDir) && !mkdir($runDir, 0775, true)) {
            throw new RuntimeException('Не удалось создать папку результата.');
        }
        if (!is_dir($imageDir) && !mkdir($imageDir, 0775, true)) {
            throw new RuntimeException('Не удалось создать папку изображений.');
        }

        $generated = generatePresentationCardsWithOpenAi($apiKey, $title, $classTitle, $sourceWords);
        $cards = [];

        foreach ($generated['cards'] as $index => $card) {
            $imagePath = $imageDir . 'card_' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) . '.png';
            generateCardImageWithOpenAi($apiKey, $card, $imagePath);
            $card['image_path'] = 'storage/images/' . $runId . '/card_' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) . '.png';
            $card['image_mime'] = 'image/png';
            $cards[] = $card;
        }

        $fileName = safeFilePart($title) . '_' . $runId . '.pptx';
        $pptxPath = $runDir . $fileName;
        buildWordPresentationPptx([
            'title' => $title,
            'class_title' => $classTitle,
        ], $cards, $pptxPath);

        $publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . $runId . DIRECTORY_SEPARATOR;
        if (!is_dir($publicDir) && !mkdir($publicDir, 0775, true)) {
            throw new RuntimeException('Не удалось создать папку для скачивания.');
        }
        $publicPath = $publicDir . $fileName;
        copy($pptxPath, $publicPath);

        $result = [
            'title' => $title,
            'words_count' => count($cards),
            'model' => (string)$generated['model'],
            'download_url' => appUrlPath($publicPath),
            'open_url' => '/?action=open-presentation&run=' . rawurlencode($runId) . '&file=' . rawurlencode($fileName),
            'reveal_url' => '/?action=reveal-presentation&run=' . rawurlencode($runId) . '&file=' . rawurlencode($fileName),
        ];
        $storageStats = generatedStorageStats();
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $storageStats = generatedStorageStats();
    }
}
$recentPresentations = recentGeneratedPresentations();
$fileManagerName = PHP_OS_FAMILY === 'Darwin' ? 'Finder' : 'Проводнике';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Генератор презентаций</title>
  <style>
    :root { color-scheme: light; --bg: #f4f6fb; --panel: #fff; --text: #172033; --muted: #667085; --line: #d8deea; --accent: #2f66d0; --danger: #b42318; --ok: #067647; --busy: #9a6700; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: var(--text); }
    main { width: min(1040px, calc(100% - 32px)); margin: 32px auto; }
    header { margin-bottom: 24px; }
    h1 { margin: 0 0 8px; font-size: 28px; line-height: 1.2; }
    p { margin: 0; color: var(--muted); line-height: 1.45; }
    .layout { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }
    .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; padding: 20px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    input, textarea { width: 100%; border: 1px solid var(--line); border-radius: 6px; padding: 11px 12px; font: inherit; background: #fff; color: var(--text); }
    textarea { min-height: 220px; resize: vertical; }
    .field { margin-bottom: 16px; }
    .hint { margin-top: 6px; font-size: 13px; color: var(--muted); }
    .button { border: 0; border-radius: 6px; background: var(--accent); color: white; padding: 12px 16px; font-weight: 700; cursor: pointer; width: 100%; }
    .button:hover { filter: brightness(0.96); }
    .button:disabled { cursor: wait; opacity: .75; }
    .alert { border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; border: 1px solid var(--line); background: #fff; }
    .alert--error { color: var(--danger); border-color: #f3b8b2; background: #fff4f2; }
    .alert--ok { color: var(--ok); border-color: #a9dec7; background: #ecfdf3; }
    .alert--busy { color: var(--busy); border-color: #f4d98b; background: #fffbeb; display: none; }
    .download { display: inline-block; margin-top: 12px; color: white; background: var(--ok); text-decoration: none; border-radius: 6px; padding: 11px 14px; font-weight: 700; }
    .result-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .result-actions .download { margin-top: 0; }
    .download--secondary { background: var(--accent); }
    .recent { margin-top: 20px; }
    .recent h2 { margin: 0 0 14px; font-size: 21px; }
    .recent-item { padding: 14px 0; border-top: 1px solid var(--line); }
    .recent-item:first-of-type { border-top: 0; }
    .recent-item__name { display: block; overflow-wrap: anywhere; font-weight: 700; }
    .recent-item__meta { margin-top: 5px; font-size: 13px; color: var(--muted); }
    .storage-note { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); line-height: 1.45; }
    .storage-note strong { color: var(--text); }
    dl { margin: 0; }
    dt { color: var(--muted); font-size: 13px; margin-top: 14px; }
    dd { margin: 4px 0 0; font-weight: 700; }
    @media (max-width: 820px) { .layout { grid-template-columns: 1fr; } main { margin: 20px auto; } }
  </style>
</head>
<body>
<main>
  <header>
    <h1>Локальный генератор презентаций</h1>
    <p>Создаёт PPTX со словарными карточками и изображениями. Готовый файл можно скачать и загрузить на сайт как обычную презентацию.</p>
  </header>

  <div id="busyAlert" class="alert alert--busy">Запрос получен. Презентация генерируется, это может занять несколько минут. Не закрывайте это окно.</div>

  <?php if ($error !== ''): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (is_array($result)): ?>
    <div class="alert alert--ok">
      Презентация готова: <?= e((string)$result['title']) ?>, <?= (int)$result['words_count'] ?> слов.
      <div class="result-actions">
        <a class="download" href="<?= e((string)$result['open_url']) ?>">Открыть презентацию</a>
        <a class="download download--secondary" href="<?= e((string)$result['reveal_url']) ?>">Показать файл в Проводнике</a>
        <a class="download download--secondary" href="<?= e((string)$result['download_url']) ?>">Скачать PPTX</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="layout">
    <form id="generatorForm" class="panel" method="post">
      <input id="cleanupStorage" name="cleanup_storage" type="hidden" value="0">
      <div class="field">
        <label for="title">Название презентации</label>
        <input id="title" name="title" value="<?= e($old['title']) ?>" required>
      </div>
      <div class="field">
        <label for="class_title">Класс или группа</label>
        <input id="class_title" name="class_title" value="<?= e($old['class_title']) ?>" placeholder="Например: 5 класс">
      </div>
      <div class="field">
        <label for="words">Английские слова</label>
        <textarea id="words" name="words" required><?= e($old['words']) ?></textarea>
        <div class="hint">До 20 слов за один запуск. Можно вводить с новой строки, через запятую или точку с запятой.</div>
      </div>
      <?php if (!$envHasKey): ?>
        <div class="field">
          <label for="api_key">OpenAI API key</label>
          <input id="api_key" name="api_key" value="" type="password" autocomplete="off" placeholder="sk-...">
          <div class="hint">Можно использовать ключ один раз или сохранить его в .env на этом компьютере.</div>
        </div>
        <div class="field">
          <label>
            <input name="save_api_key" value="1" type="checkbox" checked style="width:auto;margin-right:8px;">
            Сохранить ключ на этом компьютере
          </label>
          <div class="hint">Ключ будет записан в файл .env в папке установленной программы.</div>
        </div>
      <?php else: ?>
        <div class="alert alert--ok">Ключ OpenAI уже найден в .env. Вводить его в форме не нужно.</div>
      <?php endif; ?>
      <button id="submitButton" class="button" type="submit">Сгенерировать PPTX</button>
    </form>

    <aside class="panel">
      <dl>
        <dt>Текстовая модель</dt>
        <dd><?= e(openAiModelName()) ?></dd>
        <dt>Модель изображений</dt>
        <dd><?= e(openAiImageModelName()) ?></dd>
        <dt>Ключ в .env</dt>
        <dd><?= $envHasKey ? 'найден' : 'не найден' ?></dd>
        <dt>Результаты</dt>
        <dd>storage/output и public/downloads</dd>
      </dl>
      <div class="storage-note">
        Занято генерациями:
        <strong><?= e((string)$storageStats['total_label']) ?></strong>
        из <?= e((string)$storageStats['limit_label']) ?>.
      </div>
      <p style="margin-top:16px;">
        <a class="download" href="/?action=open-output-folder">Открыть папку с презентациями в <?= e($fileManagerName) ?></a>
      </p>
    </aside>
  </div>

  <section class="panel recent">
    <h2>Последние презентации</h2>
    <?php if ($recentPresentations === []): ?>
      <p>Созданных презентаций пока нет.</p>
    <?php else: ?>
      <?php foreach ($recentPresentations as $presentation): ?>
        <div class="recent-item">
          <span class="recent-item__name"><?= e((string)$presentation['name']) ?></span>
          <div class="recent-item__meta"><?= e((string)$presentation['date']) ?> · <?= e((string)$presentation['size']) ?></div>
          <div class="result-actions">
            <a class="download" href="<?= e((string)$presentation['open_url']) ?>">Открыть презентацию</a>
            <a class="download download--secondary" href="<?= e((string)$presentation['reveal_url']) ?>">Показать в <?= e($fileManagerName) ?></a>
            <a class="download download--secondary" href="<?= e((string)$presentation['download_url']) ?>">Скачать копию</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>
<script>
  const form = document.getElementById('generatorForm');
  const busyAlert = document.getElementById('busyAlert');
  const submitButton = document.getElementById('submitButton');
  const cleanupStorage = document.getElementById('cleanupStorage');
  const storageIsOverLimit = <?= $storageStats['is_over_limit'] ? 'true' : 'false' ?>;
  const storageTotalLabel = <?= json_encode((string)$storageStats['total_label'], JSON_UNESCAPED_UNICODE) ?>;
  const storageLimitLabel = <?= json_encode((string)$storageStats['limit_label'], JSON_UNESCAPED_UNICODE) ?>;

  form.addEventListener('submit', () => {
    if (storageIsOverLimit && cleanupStorage.value !== '1') {
      const shouldClean = confirm(
        'Старые картинки и презентации занимают ' + storageTotalLabel +
        ', лимит ' + storageLimitLabel + '.\n\n' +
        'Удалить старые сгенерированные файлы перед созданием новой презентации?\n\n' +
        'Если нажать "Отмена", новая презентация будет создана без очистки.'
      );

      if (shouldClean) {
        cleanupStorage.value = '1';
      }
    }

    busyAlert.style.display = 'block';
    submitButton.disabled = true;
    submitButton.textContent = 'Генерируется...';
    busyAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
</script>
</body>
</html>
