-- Adminer 4.8.1 MySQL 5.5.5-10.6.18-MariaDB-ubu2004 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

TRUNCATE `locations_cities`;
INSERT INTO `locations_cities` (`id`, `code`, `state`, `name`, `active`) VALUES
(78,	NULL,	2,	'Baranoa',	1),
(88,	NULL,	2,	'Barranquilla',	1),
(152,	NULL,	2,	'Campo de la Cruz',	1),
(156,	NULL,	2,	'Candelaria',	1),
(362,	NULL,	2,	'Galapa',	1),
(449,	NULL,	2,	'Juan de Acosta',	1),
(513,	NULL,	2,	'Luruaco',	1),
(527,	NULL,	2,	'Malambo',	1),
(529,	NULL,	2,	'Manatí',	1),
(637,	NULL,	2,	'Palmar de Varela',	1),
(668,	NULL,	2,	'Piojo',	1),
(677,	NULL,	2,	'Polonuevo',	1),
(678,	NULL,	2,	'Ponedera',	1),
(698,	NULL,	2,	'Puerto Colombia',	1),
(740,	NULL,	2,	'Repelón',	1),
(767,	NULL,	2,	'Sabanagrande',	1),
(769,	NULL,	2,	'Sabanalarga',	1),
(876,	NULL,	2,	'Santa Lucía',	1),
(893,	NULL,	2,	'Santo Tomás',	1),
(925,	NULL,	2,	'Soledad',	1),
(938,	NULL,	2,	'Suan',	1),
(1009,	NULL,	2,	'Tubará',	1),
(1036,	NULL,	2,	'Usiacuri',	1);

TRUNCATE `locations_countries`;
INSERT INTO `locations_countries` (`id`, `code`, `name`, `active`) VALUES
(1,	'COL',	'Colombia',	1);

TRUNCATE `locations_points`;

TRUNCATE `locations_states`;
INSERT INTO `locations_states` (`id`, `code`, `country`, `name`, `active`) VALUES
(2,	'8',	1,	'Atlántico',	1);

TRUNCATE `organizations_elements`;
INSERT INTO `organizations_elements` (`id`, `preferSlug`, `name`, `nit`, `size`, `actionLines`, `esal`, `state`, `city`, `address`, `phone`, `informativeEmail`, `billingEmail`, `contactName`, `contactPhone`, `contactEmail`, `logo`, `rut`, `folder`, `createdAt`, `updatedAt`, `createdBy`, `modifiedBy`, `status`, `meta`) VALUES
(-10,	'pJyoncXPoN7DpcugpKSQ',	'Organización base',	'0000000001',	'SMALL',	'[\"1\",\"2\",\"3\",\"Personalizada #1\"]',	'NO',	2,	88,	'Sin información',	'0000000000',	'no-mail@domain.tld',	'no-mail@domain.tld',	'Sin información',	'0000000000',	'no-mail@domain.tld',	'',	'',	'66da7db19102b',	'2024-09-05 22:57:37',	NULL,	1,	NULL,	1,	'{\"langData\":{}}');

TRUNCATE `pcsphp_users`;
INSERT INTO `pcsphp_users` (`id`, `organization`, `username`, `password`, `firstname`, `secondname`, `first_lastname`, `second_lastname`, `email`, `meta`, `type`, `status`, `failed_attempts`, `created_at`, `modified_at`) VALUES
(1,	NULL,	'root',	'$2y$10$LNWWnGirOd51j5FN5IKFr.U6BsejgWKUkRba6XnrQnroPyhGtTgrG',	'Root',	'',	'User',	'',	'root@domain.tld',	'',	0,	1,	0,	'2018-06-20 14:11:54',	'2021-05-09 14:24:06'),
(2,	-10,	'admin',	'$2y$10$7FvFY8fXdq1F8wMy8bOUie2HGSMtH/Eh2yfwMuKLSw70C85ODIwa.',	'Admin',	'',	'User',	'',	'admin@domain.tld',	'null',	1,	0,	0,	'2019-10-08 11:07:37',	'2021-05-09 14:23:37'),
(3,	-10,	'general',	'$2y$10$pPM4wtsC5Uu/RadMDhgncelnS0pvNpAld/ylJcleH5g3qS00ytwEm',	'General',	'',	'User',	'',	'general@domain.tld',	'null',	2,	0,	0,	'2019-10-08 11:07:45',	'2021-05-09 14:21:52');

-- 2024-09-06 03:58:27
