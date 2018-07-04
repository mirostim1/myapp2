CREATE TABLE posts (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) UNSIGNED NOT NULL,
  email VARCHAR(70) NOT NULL,
  content VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (email) REFERENCES users(email)
);