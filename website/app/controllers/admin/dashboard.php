<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/calendar-event.php';
require ROOT . 'app/models/material.php';
require ROOT . 'app/models/student-question.php';
require ROOT . 'app/models/extra-lesson-request.php';

renderTemplate('pages/admin/dashboard.tpl', [
    'classLessons' => getUpcomingLessonCalendarByClass(),
    'recentQuestions' => getRecentVisibleStudentQuestions(10),
    'recentExtraLessonRequests' => getVisibleExtraLessonRequests(10),
    'flash' => getFlash('admin'),
]);
