# START_HERE — запуск нового проекта с Codex

Этот файл — первая точка входа для программиста. Если начинаешь новый проект из этой папки, иди по шагам ниже.

---

## 1. Начало новой сессии Codex

В новом чате отправь Codex промпт:

- `.docs/codex/prompts/01-start-session.md`

После этого Codex будет знать, что нужно работать по правилам проекта.

---

## 2. Заполни базовую информацию о проекте

Перед активной разработкой обнови:

- `AGENTS.md` — название и краткое описание проекта;
- `.docs/project-status.md` — текущий статус, цель проекта и следующий шаг;
- `.docs/product/product-overview.md` — если уже знаешь продукт;
- `.docs/product/features.md` — если уже знаешь backlog.

Если продукт ещё не описан, используй промпт:

- `.docs/codex/prompts/02-create-product-spec.md`

---

## 3. Создай дизайн-основу

Если нужен визуальный frontend:

1. Положи референсы в `.docs/design/refs/`.
2. Используй `.docs/codex/prompts/03-create-design-direction.md`.
3. Используй `.docs/codex/prompts/04-create-design-system.md`.
4. Используй `.docs/codex/prompts/05-create-prototypes.md`.
5. Используй `.docs/codex/prompts/06-create-prototype-map.md`.

Если проект без frontend или дизайн уже есть — отметь это в `.docs/project-status.md`.

---

## 4. Спланируй разработку

Создай фазу:

- `.docs/codex/prompts/07-create-phase.md`

Создай TASK для конкретной работы:

- `.docs/codex/prompts/08-create-task.md`

Реализуй TASK:

- `.docs/codex/prompts/09-implement-task.md`

---

## 5. Закрывай работу документированно

После завершения фазы:

- `.docs/codex/prompts/10-create-phase-report.md`

В конце рабочей сессии:

- `.docs/codex/prompts/11-update-dev-log.md`

После любого значимого шага обновляй:

- `.docs/project-status.md`

---

## Главные файлы

- `AGENTS.md` — правила проекта для Codex.
- `START_HERE.md` — инструкция для человека.
- `.docs/project-status.md` — текущее состояние проекта.
- `pipeline.md` — общий процесс разработки.
- `.docs/codex/commands/` — подробные инструкции для Codex.
- `.docs/codex/prompts/` — готовые промпты для чата.
- `.docs/codex/rules/` — режимы работы Codex.
- `.docs/templates/` — шаблоны фаз, задач, отчётов и записей.
- `.docs/checklists/` — чеклисты для ручного контроля.

---

## Минимальный маршрут

Если нужен самый короткий путь:

1. Отправь `01-start-session.md`.
2. Отправь `02-create-product-spec.md`.
3. Отправь `07-create-phase.md`.
4. Отправь `08-create-task.md`.
5. Отправь `09-implement-task.md`.
6. Обнови `.docs/project-status.md`.
