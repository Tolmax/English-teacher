import { initModal } from './modules/modal.js';
import { initTabs } from './modules/tabs.js';
import { initAccordion } from './modules/accordion.js';
import { initMobileMenu } from './modules/mobile-menu.js';
import { initAiTest } from './modules/ai-test.js';
import { initTeacherLoginConfirm } from './modules/teacher-login-confirm.js';
import { initAdminSidebar } from './modules/admin-sidebar.js';
import { initConfirmAction } from './modules/confirm-action.js';
import { initArchivePicker } from './modules/archive-picker.js';
import { initRowLink } from './modules/row-link.js';
import { initPresentationShow } from './modules/presentation-show.js';
import { initFormLoading } from './modules/form-loading.js';
import { initFlashcardDecks } from './modules/flashcard-deck.js';

document.addEventListener('DOMContentLoaded', () => {
  initModal();
  initTabs();
  initAccordion();
  initMobileMenu();
  initAiTest();
  initTeacherLoginConfirm();
  initAdminSidebar();
  initConfirmAction();
  initArchivePicker();
  initRowLink();
  initPresentationShow();
  initFormLoading();
  initFlashcardDecks();
});
