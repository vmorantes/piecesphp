-- Adminer 4.8.1 MySQL 10.11.8-MariaDB-0ubuntu0.24.04.1 dump

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
(1036,	NULL,	2,	'Usiacuri',	1),
(1101,	NULL,	34,	'París',	1);

TRUNCATE `locations_countries`;
INSERT INTO `locations_countries` (`id`, `code`, `name`, `region`, `active`) VALUES
(1,	'COL',	'Colombia',	NULL,	1),
(2,	'FR',	'Francia',	NULL,	1);

TRUNCATE `locations_points`;

TRUNCATE `locations_states`;
INSERT INTO `locations_states` (`id`, `code`, `country`, `name`, `active`) VALUES
(2,	'8',	1,	'Atlántico',	1),
(34,	NULL,	2,	'Isla de Francia',	1);

TRUNCATE `organizations_elements`;
INSERT INTO `organizations_elements` (`id`, `preferSlug`, `name`, `nit`, `size`, `activitySector`, `actionLines`, `esal`, `country`, `city`, `address`, `phone`, `linkedinLink`, `websiteLink`, `informativeEmail`, `billingEmail`, `logo`, `rut`, `folder`, `createdAt`, `updatedAt`, `createdBy`, `modifiedBy`, `status`, `meta`) VALUES
(-10,	'pJyoncXPoN7DpcugpKSQ',	'Organización base',	'00000000001',	'SMALL',	'Sin información',	'[\"Sin informaci\\u00f3n\"]',	'NO',	1,	88,	'Sin información',	'0000000000',	'https://linkedin.com/',	'https://domain.tld/',	'organizacion@domain.tld',	'facturacion@domain.tld',	NULL,	NULL,	'66da7db19102b',	'2024-09-05 22:57:37',	NULL,	1,	NULL,	1,	'{\"langData\":{},\"phoneCode\":\"+57\",\"longitude\":0,\"latitude\":0,\"administrator\":3}');

TRUNCATE `pcsphp_users`;
INSERT INTO `pcsphp_users` (`id`, `organization`, `username`, `password`, `firstname`, `secondname`, `first_lastname`, `second_lastname`, `email`, `meta`, `type`, `status`, `failed_attempts`, `created_at`, `modified_at`) VALUES
(1,	NULL,	'root',	'$2y$10$5KEzolPgoFt/ZwykXvzJ9usmCzFgcY8H5UiyJV5rmHPJkrZoHl20u',	'Root',	'',	'User',	'',	'root@domain.tld',	'{}',	0,	1,	0,	'2018-06-20 14:11:54',	'2025-05-26 15:51:17'),
(2,	NULL,	'admin-general',	'$2y$10$w0ptfgrcDujRuMO9RE01geDvfGTAQDEUJvX19BJLRuuOEurWRh/Xq',	'Admin',	'',	'General',	'',	'admin-general@localhost',	'{}',	1,	1,	0,	'2019-10-08 11:07:37',	'2025-05-26 15:13:37'),
(3,	-10,	'org-admin',	'$2y$10$oyzA87BZSMuRDT2su4FGn.w.I0Rgl8Ph.TuDBe0CjS6kMh3ByspDi',	'Administrador',	'',	'Organización',	'',	'org-admin@localhost',	'{}',	12,	1,	0,	'2019-10-08 11:07:45',	'2025-05-26 15:13:49'),
(4,	-10,	'general',	'$2y$10$teOKUKpo9KuvvM84mwdNsek7s0gbXfRPOftEovQ4oGAIXFRU8Loma',	'Usuario',	'',	'General',	'',	'general@localhost',	'{}',	2,	1,	0,	'2025-04-10 14:48:32',	'2025-05-26 15:14:34'),
(8,	-10,	'institucional',	'$2y$10$hvHLHGnp/ZYnxbjgc2kJfOHGe8Ke8iOISeDFncoYTyumsadG9rWZ6',	'Usuario',	'',	'Institucional',	'',	'institucional@localhost',	'{}',	3,	1,	0,	'2025-05-15 22:38:57',	'2025-05-26 15:15:03'),
(9,	-10,	'comunicaciones',	'$2y$10$JKvSwBc6SsXvOjy0Y6eGn.bRQ/XNASWAvPwK04y79dJL5fmGvMt4y',	'Usuario',	'',	'Comunicaciones',	'',	'comunicaciones@localhost',	'{}',	4,	1,	0,	'2025-05-15 22:39:00',	'2025-05-26 15:15:22');

-- 2025-05-26 20:29:10