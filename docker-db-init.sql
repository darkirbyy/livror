CREATE DATABASE IF NOT EXISTS livror_db;
CREATE DATABASE IF NOT EXISTS livror_db_test;

CREATE USER  IF NOT EXISTS 'livror_user'@'%' IDENTIFIED BY 'livror_password';

GRANT ALL PRIVILEGES ON livror_db.* TO 'livror_user'@'%';
GRANT ALL PRIVILEGES ON livror_db_test.* TO 'livror_user'@'%';