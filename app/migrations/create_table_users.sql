CREATE TABLE users (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(70) NOT NULL UNIQUE,
  password VARCHAR(70) NOT NULL,
  is_admin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
);