# design-system-init — Генерация дизайн-системы

Ты разработчик UI. Твоя задача — сгенерировать полную дизайн-систему проекта: один HTML-файл с визуальной демонстрацией всех компонентов и полную структуру CSS/JS в папке `assets/`.

Общайся с разработчиком на **русском языке**.

---

## Шаг 1 — Проверь наличие нужных файлов

Проверь что существуют:
- `.docs/design/direction.md` — описание дизайн-направления
- `.docs/design/system/variables.json` — переменные дизайн-системы
- `.docs/design/system/common-elements.json` — общие элементы
- `.docs/design/system/app-components.json` — компоненты проекта

Если `.docs/design/direction.md` отсутствует — скажи:

> Файл `.docs/design/direction.md` не найден.
> Сначала используй инструкцию `.docs/codex/commands/design-direction-init.md` чтобы описать дизайн-направление.

И **остановись**.

Если все файлы на месте — прочитай их все, а также:
- `.docs/specs/css.md`
- `.docs/specs/html.md`
- `.docs/specs/js.md`

Затем переходи к шагу 2.

---

## Шаг 2 — Сгенерируй CSS-файлы

Создай следующую структуру CSS-файлов в `/assets/css/`:

```
assets/css/
├── main.css              ← точка входа: импортирует все файлы ниже через @import
├── base/
│   ├── reset.css         ← сброс браузерных стилей
│   ├── vars.css          ← CSS-переменные дизайн-системы (цвета, типографика, отступы, радиусы, тени)
│   └── base.css          ← базовые стили: body, типографика, ссылки, img
├── blocks/               ← BEM-блоки, один файл на блок
│   ├── button.css        ← кнопки: все варианты и состояния
│   ├── input.css         ← поля ввода, select, textarea
│   ├── card.css          ← карточки
│   ├── badge.css         ← бейджи и статусы
│   ├── table.css         ← таблицы
│   ├── modal.css         ← модальные окна
│   ├── alert.css         ← уведомления
│   └── nav.css           ← навигация
├── sections/             ← стили секций страниц
│   └── hero.css          ← секция-герой (пример)
└── utils/
    └── utils.css         ← утилиты: .sr-only, .container, вспомогательные классы
```

Правила написания CSS:
- Следуй `.docs/specs/css.md`
- BEM-нейминг: `block__element`, `block--modifier`
- Все значения через CSS-переменные из `vars.css`
- Медиа-запросы вложены в селектор
- Никакого inline-стиля
- Никакого !important без крайней необходимости

В `vars.css` определи все переменные на основе `.docs/design/direction.md` и `.docs/design/system/variables.json`:
- цвета: `--color-primary`, `--color-secondary`, `--color-accent`, `--color-bg`, `--color-surface`, `--color-text`, `--color-text-muted`, `--color-border`, `--color-danger`, `--color-success`
- типографика: `--font-heading`, `--font-body`, `--text-base`, `--text-sm`, `--text-lg`, `--text-xl`, `--text-2xl`, `--text-3xl`
- отступы: `--space-1` … `--space-12`
- радиусы: `--radius-sm`, `--radius-md`, `--radius-lg`
- тени: `--shadow-sm`, `--shadow-md`, `--shadow-lg`
- переходы: `--transition-base`

---

## Шаг 3 — Сгенерируй JS-файлы

Создай следующую структуру JS в `prototypes/assets/js/`:

```
assets/js/
├── main.js               ← точка входа: импортирует модули, инициализирует при DOMContentLoaded
└── modules/
    ├── modal.js          ← открытие/закрытие модальных окон
    └── tabs.js           ← переключение табов (если есть в компонентах)
```

Правила написания JS:
- Следуй `.docs/specs/js.md`
- ES modules: `export function init...()`, импорт в `main.js`
- Никакого jQuery, никаких глобальных переменных
- Один модуль = один файл = одна ответственность

---

## Шаг 4 — Создай папку для изображений

Создай структуру (пустые папки с `.gitkeep`):

```
assets/img/
└── icons/
```

---

## Шаг 5 — Сгенерируй ui-design-system.html

Создай файл `prototypes/design-system/ui-design-system.html` — единую HTML-страницу, которая демонстрирует все элементы дизайн-системы.

### Источники данных — обязательно читай перед генерацией

Страница строится строго на основе трёх JSON-файлов. Перед написанием HTML убедись, что ты уже прочитал их на Шаге 1. Каждый файл отвечает за свой блок:

**`.docs/design/system/variables.json`** — визуальные токены:
- `typography` → секции «Цвета», «Типографика», «Отступы»: шрифты (primary, accent, secondary), шкала размеров (xs–4xl), насыщенность, межбуквенный интервал
- `colors.light` → секция «Цвета»: группы background, text, accent, secondary_accent, semantic (success/warning/error/info), border
- `colors.product` → секция «Бейджи»: sale, new, hit, outOfStock
- `spacing` → секция «Отступы»: шкала 0–24
- `borderRadius` → секция «Радиусы»: none, sm, md, lg, xl, 2xl, full + семантические (button, card, badge, input, image)
- `shadows` → секция «Тени»: xs, sm, md, lg, xl, card
- `transitions` → секция «Переходы»: fast, normal, slow

**`.docs/design/system/common-elements.json`** — универсальные UI-элементы:
- `headings`, `text` → секция «Типографика»
- `buttons` → секция «Кнопки»: типы (primary, secondary, ghost, danger, link), размеры (small, medium, large), все состояния из `states`, модификаторы из `modifiers`
- `form_elements.inputs` → секция «Поля ввода»: text, email, password, number, textarea
- `form_elements.select` → секция «Поля ввода»: basic, search_suggestion, multi_select
- `form_elements.controls` → секция «Поля ввода»: checkbox, radio, toggle
- `form_elements.sliders` → секция «Поля ввода»: single_range, double_range
- `form_elements.special` → секция «Специальные элементы форм»: tags_input, counter_input, file_dropzone, color_picker_dots, size_badges
- `badges_chips` → секция «Бейджи»: badge (sm/md/lg), chip, counter_badge
- `icons` → секция «Иконки»: все размеры xs–xl
- `divider` → секция «Разделители»: horizontal, vertical, with_label
- `avatar` → секция «Аватары»: все размеры xs–xl, все варианты
- `tooltip` → секция «Тултипы»: все позиции из `positions`
- `modal` → секция «Модальные окна»: все размеры (sm, md, lg, full), все части из `parts`
- `notification.toast` → секция «Уведомления»: все варианты из `variants`
- `notification.alert` → секция «Уведомления»: все варианты из `variants`, dismissible
- `pagination` → секция «Пагинация»: все варианты
- `breadcrumbs` → секция «Навигация»
- `tabs` → секция «Табы»: все варианты, размеры, withIcons
- `accordion` → секция «Аккордеон»: все варианты
- `skeleton` → секция «Скелетоны»: все варианты
- `auth_forms` → секция «Формы авторизации»: login, register, forgot_password

**`.docs/design/system/app-components.json`** — специфичные компоненты проекта (секция «Компоненты приложения»):
- Прочитай файл и для **каждого ключа верхнего уровня** создай отдельную секцию в HTML
- Для каждого компонента отобрази все его `variants`, `parts` и `states`, которые описаны в JSON
- Не пропускай компоненты и не придумывай то, чего нет в файле — строго следуй его содержимому

### Правила генерации HTML

- Следуй `.docs/specs/html.md`
- Подключает CSS: `<link rel="stylesheet" href="../../assets/css/main.css">`
- Подключает JS: `<script type="module" src="../../assets/js/main.js"></script>`
- Шрифты подключаются локально или через Google Fonts (если указано в direction.md)

### Структура страницы `ui-design-system.html`

```
1.  Навигация по секциям (якорные ссылки на все секции ниже)
--- Токены (из variables.json) ---
2.  Цвета        — все группы из colors.light + colors.product
3.  Типографика  — шрифты, шкала размеров, насыщенность, межбуквенный интервал
4.  Отступы      — визуальная шкала spacing 0–24
5.  Радиусы      — визуальная шкала borderRadius
6.  Тени         — визуальная шкала shadows
7.  Переходы     — демонстрация transitions fast/normal/slow
--- Общие элементы (из common-elements.json) ---
8.  Кнопки       — все типы, размеры, состояния, модификаторы
9.  Поля ввода   — inputs, select, controls, sliders, special
10. Специальные элементы форм — tags_input, counter_input, file_dropzone, color_picker_dots, size_badges
11. Бейджи и чипы — badge, chip, counter_badge
12. Иконки       — все размеры
13. Разделители  — horizontal, vertical, with_label
14. Аватары      — все размеры и варианты
15. Тултипы      — все позиции
16. Модальные окна — все размеры
17. Уведомления  — toast (все варианты), alert (все варианты, dismissible)
18. Пагинация    — все варианты
19. Навигация    — breadcrumbs
20. Табы         — все варианты, размеры
21. Аккордеон    — все варианты
22. Скелетоны    — все варианты
23. Формы авторизации — login, register, forgot_password
--- Компоненты приложения (из app-components.json) ---
24+. По одной секции на каждый ключ верхнего уровня из app-components.json —
     отображай все variants, parts и states, описанные для каждого компонента
```

Каждая секция:
- Имеет `id` для якорной навигации
- Имеет заголовок `<h2>` с названием секции
- Показывает живые примеры компонентов (не картинки, а реальный HTML+CSS)
- Для интерактивных компонентов (модалки, табы, аккордеон) — добавь рабочий пример с JS
- Если в JSON-файле для данного компонента есть `variants` или `states` — показывай **все** перечисленные варианты, не пропускай

---

## Шаг 6 — Подтверди результат

После создания всех файлов выведи:

```
✅ Дизайн-система сгенерирована.

Созданные файлы:
prototypes/design-system/
├── ui-design-system.html
assets/
├── css/
│   ├── main.css
│   ├── base/ (reset.css, vars.css, base.css)
│   ├── blocks/ (button.css, input.css, card.css, badge.css, table.css, modal.css, alert.css, nav.css)
│   ├── sections/ (hero.css)
│   └── utils/ (utils.css)
├── js/
│   ├── main.js
│   └── modules/ (modal.js, tabs.js)
└── img/
	└── icons/

Следующий шаг:
Открой prototypes/design-system/ui-design-system.html в браузере и проверь результат.
Если всё хорошо — Используй инструкцию `.docs/codex/commands/prototype-init.md` чтобы сгенерировать прототипы страниц.
```



