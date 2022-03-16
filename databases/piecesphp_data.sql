-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

TRUNCATE `pcsphp_users`;
INSERT INTO `pcsphp_users` (`id`, `username`, `password`, `firstname`, `secondname`, `first_lastname`, `second_lastname`, `email`, `meta`, `type`, `status`, `failed_attempts`, `created_at`, `modified_at`) VALUES
(1,	'root',	'$2y$10$LNWWnGirOd51j5FN5IKFr.U6BsejgWKUkRba6XnrQnroPyhGtTgrG',	'Root',	'',	'User',	'',	'root@mail.com',	'',	0,	1,	0,	'2018-06-20 14:11:54',	'2021-05-09 14:24:06'),
(2,	'admin',	'$2y$10$7FvFY8fXdq1F8wMy8bOUie2HGSMtH/Eh2yfwMuKLSw70C85ODIwa.',	'Admin',	'',	'User',	'',	'admin@mail.com',	'null',	1,	0,	0,	'2019-10-08 11:07:37',	'2021-05-09 14:23:37'),
(3,	'general',	'$2y$10$pPM4wtsC5Uu/RadMDhgncelnS0pvNpAld/ylJcleH5g3qS00ytwEm',	'General',	'',	'User',	'',	'general@mail.com',	'null',	2,	0,	0,	'2019-10-08 11:07:45',	'2021-05-09 14:21:52');

-- 2021-05-31 19:32:33
