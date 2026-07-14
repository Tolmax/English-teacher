## Pipeline разработки проекта для Codex

Используй `START_HERE.md` как главный вход, а `.docs/project-status.md` как текущую карту состояния проекта.

---

### Старт сессии

```text
Отправь промпт `.docs/codex/prompts/01-start-session.md`
→ Codex принимает правила проекта и ждёт конкретную задачу
```

---

### Продукт (делается один раз и обновляется по мере развития)

```text
Отправь промпт `.docs/codex/prompts/02-create-product-spec.md`
→ `.docs/product/product-overview.md`
→ `.docs/product/features.md`
→ обновление `.docs/project-status.md`
```

---

### Дизайн (делается один раз, если проекту нужен frontend)

```text
Отправь `.docs/codex/prompts/03-create-design-direction.md`
→ `.docs/design/direction.md`

Отправь `.docs/codex/prompts/04-create-design-system.md`
→ `prototypes/design-system/ui-design-system.html`
→ `assets/css/`, `assets/js/`, `assets/img/`

Отправь `.docs/codex/prompts/05-create-prototypes.md`
→ HTML-прототипы страниц → `prototypes/*.html`

Отправь `.docs/codex/prompts/06-create-prototype-map.md`
→ `.docs/product/prototype-map.md`
```

---

### Цикл разработки (повторяется для каждой фазы)

```text
Отправь `.docs/codex/prompts/07-create-phase.md`
→ `.docs/_phases/phase-N.md`

Отправь `.docs/codex/prompts/08-create-task.md`
→ `TASK.md`

Отправь `.docs/codex/prompts/09-implement-task.md`
→ Codex реализует задачу
→ проверка acceptance criteria
→ обновление `.docs/project-status.md`

Если в фазе есть следующий таск:
  снова `.docs/codex/prompts/08-create-task.md`
  затем `.docs/codex/prompts/09-implement-task.md`

Когда фаза завершена:
  `.docs/codex/prompts/10-create-phase-report.md`
  → `.docs/_phases/phase-N-report.md`
```

---

### Конец рабочей сессии

```text
Отправь `.docs/codex/prompts/11-update-dev-log.md`
→ `.docs/dev-log.md`
→ обновление `.docs/project-status.md`
```

---

### Дополнительные режимы

```text
Ревью кода:
  `.docs/codex/prompts/12-review-code.md`

Исправление бага:
  `.docs/codex/prompts/13-fix-bug.md`
```
