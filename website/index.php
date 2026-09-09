<?php

require __DIR__ . '/app/core/error-logger.php';
app_register_error_handlers();

require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';

require ROOT . 'app/core/router.php';
require ROOT . 'app/core/db.php';
require ROOT . 'app/core/auth.php';
require ROOT . 'app/core/renderer.php';
require ROOT . 'app/core/helpers.php';

require ROOT . 'config/routes.php';
