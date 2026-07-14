# Код для файла .htaccess

Файл **.htaccess** должен находиться в корне проекта. Он нужен для работы роутера, который настроен на работу из одной точки входа в приложение `index.php`

```
Options -Indexes

RewriteEngine On

# Serve existing files and directories directly (assets, uploads, etc.)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route everything else through index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# Security: deny access to sensitive files
<FilesMatch "\.(sqlite|sql|log|md|tpl)$">
    Require all denied
</FilesMatch>

# Security: deny direct access to PHP files outside root
<FilesMatch "^(?!index).*\.php$">
    Require all denied
</FilesMatch>

# PHP settings
php_flag display_errors Off
php_value upload_max_filesize 20M
php_value post_max_size 25M
php_value max_file_uploads 20
```