-- Adminer 4.8.1 MySQL 5.5.5-10.5.13-MariaDB-1:10.5.13+maria~focal dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `actions_log`;
CREATE TABLE `actions_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `textMessage` text COLLATE utf8_bin NOT NULL,
  `textMessageVariables` longtext COLLATE utf8_bin NOT NULL,
  `referenceColumn` text COLLATE utf8_bin DEFAULT NULL,
  `referenceValue` text COLLATE utf8_bin DEFAULT NULL,
  `referenceSource` text COLLATE utf8_bin DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `createdAt` datetime NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `actions_log_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `app_presentations`;
CREATE TABLE `app_presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `order` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `images` longtext COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  CONSTRAINT `app_presentations_ibfk_1` FOREIGN KEY (`category`) REFERENCES `app_presentations_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `app_presentations_categories`;
CREATE TABLE `app_presentations_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `forms_categories`;
CREATE TABLE `forms_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `categoryName` text COLLATE utf8_bin NOT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `forms_categories_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `forms_categories_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `image_repository_images`;
CREATE TABLE `image_repository_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `author` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `image` text COLLATE utf8_bin NOT NULL,
  `authorization` text COLLATE utf8_bin DEFAULT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `resolution` text COLLATE utf8_bin NOT NULL,
  `size` double NOT NULL,
  `coordinates` longtext COLLATE utf8_bin DEFAULT NULL,
  `captureDate` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `image_repository_images_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `image_repository_images_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `locations_cities`;
CREATE TABLE `locations_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `state` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  CONSTRAINT `locations_cities_ibfk_1` FOREIGN KEY (`state`) REFERENCES `locations_states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `locations_countries`;
CREATE TABLE `locations_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `locations_points`;
CREATE TABLE `locations_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` int(11) NOT NULL,
  `address` text COLLATE utf8_bin NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `city` (`city`),
  CONSTRAINT `locations_points_ibfk_1` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `locations_states`;
CREATE TABLE `locations_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `country` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `country` (`country`),
  CONSTRAINT `locations_states_ibfk_1` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `username_attempt` varchar(255) COLLATE utf8_bin NOT NULL,
  `success` int(11) NOT NULL,
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `date` datetime NOT NULL,
  `extra_data` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_from` bigint(20) NOT NULL,
  `message_to` bigint(20) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `subject` text COLLATE utf8_bin NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `attachment` text COLLATE utf8_bin DEFAULT NULL,
  `readed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `message_from` (`message_from`),
  KEY `message_to` (`message_to`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`message_to`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `messages_responses`;
CREATE TABLE `messages_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `message_from` bigint(20) NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `attachment` text COLLATE utf8_bin DEFAULT NULL,
  `readed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_from` (`message_from`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `messages_responses_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `messages_responses_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `newsletter_sucribers`;
CREATE TABLE `newsletter_sucribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_bin NOT NULL,
  `email` text COLLATE utf8_bin NOT NULL,
  `acceptUpdates` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `news_categories`;
CREATE TABLE `news_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `iconImage` text COLLATE utf8_bin NOT NULL,
  `color` text COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `news_elements`;
CREATE TABLE `news_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `newsTitle` text COLLATE utf8_bin NOT NULL,
  `profilesTarget` longtext COLLATE utf8_bin NOT NULL,
  `content` text COLLATE utf8_bin NOT NULL,
  `category` int(11) NOT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `news_elements_ibfk_1` FOREIGN KEY (`category`) REFERENCES `news_categories` (`id`),
  CONSTRAINT `news_elements_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `news_elements_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_app_config`;
CREATE TABLE `pcsphp_app_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `value` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_dynamic_images`;
CREATE TABLE `pcsphp_dynamic_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin DEFAULT NULL,
  `link` text COLLATE utf8_bin DEFAULT NULL,
  `image` text COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_recovery_password`;
CREATE TABLE `pcsphp_recovery_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_tickets_log`;
CREATE TABLE `pcsphp_tickets_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `information` longtext COLLATE utf8_bin DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_tokens`;
CREATE TABLE `pcsphp_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` text COLLATE utf8_bin NOT NULL,
  `type` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_users`;
CREATE TABLE `pcsphp_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
  `firstname` varchar(255) COLLATE utf8_bin NOT NULL,
  `secondname` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `first_lastname` varchar(255) COLLATE utf8_bin NOT NULL,
  `second_lastname` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `meta` text COLLATE utf8_bin DEFAULT NULL,
  `type` int(3) NOT NULL,
  `status` int(3) NOT NULL DEFAULT 1,
  `failed_attempts` int(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `pcsphp_user_problems`;
CREATE TABLE `pcsphp_user_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL,
  `type` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `persons`;
CREATE TABLE `persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `documentType` text COLLATE utf8_bin NOT NULL,
  `documentNumber` text COLLATE utf8_bin NOT NULL,
  `personName1` text COLLATE utf8_bin NOT NULL,
  `personName2` text COLLATE utf8_bin NOT NULL,
  `personLastName1` text COLLATE utf8_bin NOT NULL,
  `personLastName2` text COLLATE utf8_bin NOT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `persons_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `persons_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `publications_attachments`;
CREATE TABLE `publications_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication` int(11) NOT NULL,
  `attachmentType` text COLLATE utf8_bin NOT NULL,
  `fileLocation` text COLLATE utf8_bin NOT NULL,
  `lang` text COLLATE utf8_bin NOT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `publication` (`publication`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `publications_attachments_ibfk_1` FOREIGN KEY (`publication`) REFERENCES `publications_elements` (`id`),
  CONSTRAINT `publications_attachments_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_attachments_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `publications_categories`;
CREATE TABLE `publications_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `publications_elements`;
CREATE TABLE `publications_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text COLLATE utf8_bin DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `content` text COLLATE utf8_bin NOT NULL,
  `seoDescription` text COLLATE utf8_bin DEFAULT NULL,
  `author` bigint(20) NOT NULL,
  `category` int(11) NOT NULL,
  `mainImage` text COLLATE utf8_bin NOT NULL,
  `thumbImage` text COLLATE utf8_bin NOT NULL,
  `ogImage` text COLLATE utf8_bin NOT NULL,
  `folder` text COLLATE utf8_bin NOT NULL,
  `visits` int(11) NOT NULL,
  `publicDate` datetime NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `featured` int(11) NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author` (`author`),
  KEY `category` (`category`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `publications_elements_ibfk_1` FOREIGN KEY (`author`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_elements_ibfk_2` FOREIGN KEY (`category`) REFERENCES `publications_categories` (`id`),
  CONSTRAINT `publications_elements_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_elements_ibfk_4` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `time_on_platform`;
CREATE TABLE `time_on_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `minutes` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `time_on_platform_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- 2022-05-20 01:25:39
