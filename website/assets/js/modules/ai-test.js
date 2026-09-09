export function initAiTest() {
  const forms = document.querySelectorAll('[data-ai-test]');

  forms.forEach((form) => {
    const groupNames = new Set();
    const options = form.querySelectorAll('[data-ai-test-option]');

    options.forEach((option) => {
      const input = option.querySelector('input[type="radio"]');
      if (!input || groupNames.has(input.name)) {
        return;
      }

      groupNames.add(input.name);

      const groupInputs = Array.from(form.querySelectorAll(`input[name="${input.name}"]`));

      const updateGroup = (shouldLock = false) => {
        const selectedInput = groupInputs.find((groupInput) => groupInput.checked);
        const isLocked = shouldLock || groupInputs.some((groupInput) => groupInput.dataset.aiTestLocked === '1');

        if (selectedInput && isLocked) {
          groupInputs.forEach((groupInput) => {
            groupInput.dataset.aiTestLocked = '1';
            groupInput.disabled = groupInput !== selectedInput;
          });
        }

        groupInputs.forEach((groupInput) => {
          const optionElement = groupInput.closest('[data-ai-test-option]');
          if (!optionElement) {
            return;
          }

          const feedback = optionElement.querySelector('[data-ai-test-feedback]');
          const isSelected = groupInput.checked;
          const isCorrect = optionElement.dataset.correct === '1';

          optionElement.classList.toggle('ai-test__option--selected', isSelected);
          optionElement.classList.toggle('ai-test__option--correct', isSelected && isCorrect);
          optionElement.classList.toggle('ai-test__option--wrong', isSelected && !isCorrect);
          optionElement.classList.toggle('ai-test__option--locked', isLocked);

          if (feedback) {
            feedback.textContent = isSelected ? (isCorrect ? 'Правильно' : 'Ошибка') : '';
          }
        });
      };

      updateGroup(groupInputs.some((groupInput) => groupInput.checked));

      groupInputs.forEach((groupInput) => {
        groupInput.addEventListener('change', () => updateGroup(true));
      });
    });
  });
}
