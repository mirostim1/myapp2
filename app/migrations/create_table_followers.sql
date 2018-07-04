CREATE TABLE followers (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int(10) NOT NULL,
  following_id int(70) NOT NULL
);