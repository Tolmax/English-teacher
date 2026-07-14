# New Project Checklist

Используй этот чеклист при старте нового проекта из шаблона.

---

## Подготовка

- [ ] Открыть `START_HERE.md`.
- [ ] В новом чате Codex отправить `.docs/codex/prompts/01-start-session.md`.
- [ ] Обновить название и описание проекта в `AGENTS.md`.
- [ ] Обновить `.docs/project-status.md`.

---

## Продукт

- [ ] Создать `.docs/product/product-overview.md`.
- [ ] Создать `.docs/product/features.md`.
- [ ] Проверить, что backlog не является планом разработки, а только списком возможностей.

---

## Дизайн и прототипы

- [ ] Добавить дизайн-референсы в `.docs/design/refs/` или явно отметить, что дизайн не нужен.
- [ ] Создать `.docs/design/direction.md`.
- [ ] Создать `prototypes/design-system/ui-design-system.html`.
- [ ] Создать HTML-прототипы ключевых страниц.
- [ ] Создать `.docs/product/prototype-map.md`.
- [ ] Проверить прототипы вручную на ширинах 320px, 768px, 1024px, 1440px.

---

## План разработки

- [ ] Создать `.docs/_phases/phase-1.md`.
- [ ] Убедиться, что Phase 1 даёт один полный end-to-end сценарий.
- [ ] Создать `TASK.md` для первого таска.
- [ ] Проверить, что TASK содержит конкретные файлы и проверяемые acceptance criteria.

---

## Реализация

- [ ] Реализовать задачу из `TASK.md`.
- [ ] Проверить DoD вручную.
- [ ] Обновить `.docs/database-schema.md` и `database/schema.sql`, если менялась БД.
- [ ] Создать отчёт фазы, если фаза завершена.
- [ ] Обновить `.docs/dev-log.md`.
- [ ] Обновить `.docs/project-status.md`.
