<?php

unset($_SESSION['is_admin'], $_SESSION['admin_user_id'], $_SESSION['admin_name']);

setFlash('admin', 'Вы вышли из панели учителя.');
redirectTo('admin/login');
