<?php
$pageTitle = 'Р”РёР°РіРЅРѕСЃС‚РёРєР° С…РѕСЃС‚РёРЅРіР° вЂ” English Teacher';
$activeNav = 'system-check';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="system-check-title">
        <div class="section__header">
          <h1 id="system-check-title">Р”РёР°РіРЅРѕСЃС‚РёРєР° С…РѕСЃС‚РёРЅРіР°</h1>
          <p class="section__lead">РџСЂРѕРІРµСЂРєР° РѕРєСЂСѓР¶РµРЅРёСЏ Timeweb: PHP, SQLite, РїСЂР°РІР° РЅР° Р·Р°РїРёСЃСЊ, .env Рё СЂРµР¶РёРј OpenAI Р±РµР· СЂР°СЃРєСЂС‹С‚РёСЏ СЃРµРєСЂРµС‚РѕРІ.</p>
        </div>

        <div class="alert alert--info" role="note">
          Р—РЅР°С‡РµРЅРёРµ OPENAI_API_KEY РЅР° СЌС‚РѕР№ СЃС‚СЂР°РЅРёС†Рµ РЅРµ РІС‹РІРѕРґРёС‚СЃСЏ. РџРѕРєР°Р·С‹РІР°РµС‚СЃСЏ С‚РѕР»СЊРєРѕ СЂРµР¶РёРј: OpenAI API РёР»Рё mock.
        </div>

        <section class="card" aria-labelledby="environment-title">
          <div class="card__header">
            <h2 id="environment-title">РћРєСЂСѓР¶РµРЅРёРµ</h2>
          </div>
          <ul class="system-check-list">
            <?php foreach ($checks as $check): ?>
              <li class="system-check-list__item">
                <div>
                  <h3><?= e($check['label']) ?></h3>
                  <p class="card__text"><?= e($check['note']) ?></p>
                </div>
                <span class="status-pill <?= $check['ok'] ? 'status-pill--success' : 'status-pill--danger' ?>">
                  <?= e($check['value']) ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="card" aria-labelledby="paths-title">
          <div class="card__header">
            <h2 id="paths-title">Р¤Р°Р№Р»С‹ Рё РїР°РїРєРё</h2>
          </div>
          <ul class="system-check-list">
            <?php foreach ($paths as $path): ?>
              <li class="system-check-list__item system-check-list__item--path">
                <div>
                  <h3><?= e($path['label']) ?></h3>
                  <p class="card__text"><?= e($path['path']) ?></p>
                </div>
                <div class="status-group" aria-label="РЎС‚Р°С‚СѓСЃС‹">
                  <span class="status-pill <?= $path['exists'] ? 'status-pill--success' : 'status-pill--danger' ?>">
                    <?= $path['exists'] ? 'РµСЃС‚СЊ' : 'РЅРµС‚' ?>
                  </span>
                  <span class="status-pill <?= $path['readable'] ? 'status-pill--success' : 'status-pill--danger' ?>">
                    <?= $path['readable'] ? 'С‡С‚РµРЅРёРµ' : 'РЅРµС‚ С‡С‚РµРЅРёСЏ' ?>
                  </span>
                  <span class="status-pill <?= $path['writable'] ? 'status-pill--success' : 'status-pill--warning' ?>">
                    <?= $path['writable'] ? 'Р·Р°РїРёСЃСЊ' : 'РЅРµС‚ Р·Р°РїРёСЃРё' ?>
                  </span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>

