-- Adminer 5.3.0 MariaDB 10.11.8-MariaDB-0ubuntu0.24.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `actions_log`;
CREATE TABLE `actions_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `textMessage` text NOT NULL,
  `textMessageVariables` longtext NOT NULL,
  `referenceColumn` text DEFAULT NULL,
  `referenceValue` text DEFAULT NULL,
  `referenceSource` text DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `createdAt` datetime NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  CONSTRAINT `actions_log_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `app_presentations`;
CREATE TABLE `app_presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `name` text NOT NULL,
  `order` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `images` longtext NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  CONSTRAINT `app_presentations_ibfk_1` FOREIGN KEY (`category`) REFERENCES `app_presentations_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `app_presentations_categories`;
CREATE TABLE `app_presentations_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `name` text NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `built_in_banner_elements`;
CREATE TABLE `built_in_banner_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `desktopImage` text NOT NULL,
  `mobileImage` text DEFAULT NULL,
  `orderPosition` int(11) NOT NULL,
  `folder` text NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `built_in_banner_elements_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `built_in_banner_elements_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `documents_elements`;
CREATE TABLE `documents_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `documentType` int(11) NOT NULL,
  `documentName` text NOT NULL,
  `description` text NOT NULL,
  `document` text NOT NULL,
  `documentImage` text NOT NULL,
  `folder` text NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documentType` (`documentType`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `documents_elements_ibfk_1` FOREIGN KEY (`documentType`) REFERENCES `forms_document_types` (`id`),
  CONSTRAINT `documents_elements_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `documents_elements_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `forms_categories`;
CREATE TABLE `forms_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `categoryName` text NOT NULL,
  `folder` text NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `forms_categories_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `forms_categories_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `forms_document_types`;
CREATE TABLE `forms_document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `documentTypeName` text NOT NULL,
  `folder` text NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `forms_document_types_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `forms_document_types_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `application_calls_attachments`;
CREATE TABLE `application_calls_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicationCall` int(11) NOT NULL,
  `attachmentName` text NOT NULL,
  `fileLocation` text NOT NULL,
  `lang` text NOT NULL,
  `folder` text NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicationCall` (`applicationCall`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `application_calls_attachments_ibfk_1` FOREIGN KEY (`applicationCall`) REFERENCES `application_calls_elements` (`id`),
  CONSTRAINT `application_calls_attachments_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `application_calls_attachments_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `application_calls_elements`;
CREATE TABLE `application_calls_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `contentType` text NOT NULL,
  `financingType` text NOT NULL,
  `currency` text NOT NULL,
  `amount` double NOT NULL,
  `participatingInstitutions` longtext NOT NULL,
  `applicationLink` text NOT NULL,
  `content` text NOT NULL,
  `mainImage` text NOT NULL,
  `thumbImage` text NOT NULL,
  `folder` text NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `application_calls_elements_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `application_calls_elements_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `image_repository_images`;
CREATE TABLE `image_repository_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `city` int(11) NOT NULL,
  `author` text NOT NULL,
  `description` text NOT NULL,
  `image` text NOT NULL,
  `authorization` text DEFAULT NULL,
  `folder` text NOT NULL,
  `resolution` text NOT NULL,
  `size` double NOT NULL,
  `coordinates` longtext DEFAULT NULL,
  `captureDate` datetime NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  KEY `city` (`city`),
  CONSTRAINT `image_repository_images_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `image_repository_images_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `image_repository_images_ibfk_5` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `interest_research_area`;
CREATE TABLE `interest_research_area` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `areaName` text DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `interest_research_area_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `interest_research_area_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `locations_cities`;
CREATE TABLE `locations_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `state` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  CONSTRAINT `locations_cities_ibfk_1` FOREIGN KEY (`state`) REFERENCES `locations_states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `locations_countries`;
CREATE TABLE `locations_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `region` text DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `locations_points`;
CREATE TABLE `locations_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` int(11) NOT NULL,
  `address` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `city` (`city`),
  CONSTRAINT `locations_points_ibfk_1` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `locations_states`;
CREATE TABLE `locations_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `country` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `country` (`country`),
  CONSTRAINT `locations_states_ibfk_1` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `username_attempt` varchar(255) NOT NULL,
  `success` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `date` datetime NOT NULL,
  `extra_data` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_from` bigint(20) NOT NULL,
  `message_to` bigint(20) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `subject` text NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` text DEFAULT NULL,
  `readed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `message_from` (`message_from`),
  KEY `message_to` (`message_to`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`message_to`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `messages_responses`;
CREATE TABLE `messages_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `message_from` bigint(20) NOT NULL,
  `message` text DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `attachment` text DEFAULT NULL,
  `readed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_from` (`message_from`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `messages_responses_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `messages_responses_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `newsletter_sucribers`;
CREATE TABLE `newsletter_sucribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `acceptUpdates` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `news_categories`;
CREATE TABLE `news_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `name` text NOT NULL,
  `iconImage` text NOT NULL,
  `color` text NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `news_elements`;
CREATE TABLE `news_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `newsTitle` text NOT NULL,
  `profilesTarget` longtext NOT NULL,
  `content` text NOT NULL,
  `category` int(11) NOT NULL,
  `folder` text NOT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `news_elements_ibfk_1` FOREIGN KEY (`category`) REFERENCES `news_categories` (`id`),
  CONSTRAINT `news_elements_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `news_elements_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `organizations_elements`;
CREATE TABLE `organizations_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `name` text NOT NULL,
  `nit` text NOT NULL,
  `size` text DEFAULT NULL,
  `activitySector` text DEFAULT NULL,
  `actionLines` longtext DEFAULT NULL,
  `esal` text DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `linkedinLink` text DEFAULT NULL,
  `websiteLink` text DEFAULT NULL,
  `informativeEmail` text DEFAULT NULL,
  `billingEmail` text DEFAULT NULL,
  `logo` text DEFAULT NULL,
  `rut` text DEFAULT NULL,
  `folder` text NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `city` (`city`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  KEY `country` (`country`),
  CONSTRAINT `organizations_elements_ibfk_1` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`),
  CONSTRAINT `organizations_elements_ibfk_2` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`),
  CONSTRAINT `organizations_elements_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `organizations_elements_ibfk_4` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `organization_previous_experiences`;
CREATE TABLE `organization_previous_experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `profile` int(11) NOT NULL,
  `experienceName` text NOT NULL,
  `experienceType` text NOT NULL,
  `researchAreas` longtext NOT NULL,
  `institutionsParticipated` longtext NOT NULL,
  `country` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `description` text NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile` (`profile`),
  KEY `country` (`country`),
  KEY `city` (`city`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `organization_previous_experiences_ibfk_1` FOREIGN KEY (`profile`) REFERENCES `organizations_elements` (`id`),
  CONSTRAINT `organization_previous_experiences_ibfk_2` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`),
  CONSTRAINT `organization_previous_experiences_ibfk_3` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`),
  CONSTRAINT `organization_previous_experiences_ibfk_4` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `organization_previous_experiences_ibfk_5` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_app_config`;
CREATE TABLE `pcsphp_app_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_recovery_password`;
CREATE TABLE `pcsphp_recovery_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_tickets_log`;
CREATE TABLE `pcsphp_tickets_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `information` longtext DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_tokens`;
CREATE TABLE `pcsphp_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` text NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_users`;
CREATE TABLE `pcsphp_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `organization` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `secondname` varchar(255) DEFAULT NULL,
  `first_lastname` varchar(255) NOT NULL,
  `second_lastname` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `meta` text DEFAULT NULL,
  `type` int(3) NOT NULL,
  `status` int(3) NOT NULL DEFAULT 1,
  `failed_attempts` int(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `organization` (`organization`),
  CONSTRAINT `pcsphp_users_ibfk_1` FOREIGN KEY (`organization`) REFERENCES `organizations_elements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_users_otp_secrets`;
CREATE TABLE `pcsphp_users_otp_secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL,
  `secret` text NOT NULL,
  `intervalTOTP` int(11) NOT NULL,
  `oneUseCode` text NOT NULL,
  `maxDate` datetime DEFAULT NULL,
  `method` text NOT NULL,
  `twoAuthFactor` text NOT NULL,
  `twoAuthFactorQRViewed` int(1) NOT NULL DEFAULT 0,
  `twoAuthFactorAlias` text DEFAULT NULL,
  `twoAuthFactorSecurityCode` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  CONSTRAINT `pcsphp_users_otp_secrets_ibfk_1` FOREIGN KEY (`user`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `pcsphp_user_problems`;
CREATE TABLE `pcsphp_user_problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `persons`;
CREATE TABLE `persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `documentType` text NOT NULL,
  `documentNumber` text NOT NULL,
  `personName1` text NOT NULL,
  `personName2` text NOT NULL,
  `personLastName1` text NOT NULL,
  `personLastName2` text NOT NULL,
  `folder` text NOT NULL,
  `status` int(11) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `persons_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `persons_ibfk_2` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `previous_experiences`;
CREATE TABLE `previous_experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `profile` int(11) NOT NULL,
  `experienceName` text NOT NULL,
  `experienceType` text NOT NULL,
  `researchAreas` longtext NOT NULL,
  `institutionsParticipated` longtext NOT NULL,
  `country` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `description` text NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile` (`profile`),
  KEY `country` (`country`),
  KEY `city` (`city`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `previous_experiences_ibfk_1` FOREIGN KEY (`profile`) REFERENCES `user_system_profile` (`id`),
  CONSTRAINT `previous_experiences_ibfk_2` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`),
  CONSTRAINT `previous_experiences_ibfk_3` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`),
  CONSTRAINT `previous_experiences_ibfk_4` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `previous_experiences_ibfk_5` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `publications_attachments`;
CREATE TABLE `publications_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication` int(11) NOT NULL,
  `attachmentName` text NOT NULL,
  `fileLocation` text NOT NULL,
  `lang` text NOT NULL,
  `folder` text NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `publication` (`publication`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `publications_attachments_ibfk_1` FOREIGN KEY (`publication`) REFERENCES `publications_elements` (`id`),
  CONSTRAINT `publications_attachments_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_attachments_ibfk_3` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `publications_categories`;
CREATE TABLE `publications_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `name` text NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `publications_elements`;
CREATE TABLE `publications_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `seoDescription` text DEFAULT NULL,
  `author` bigint(20) NOT NULL,
  `category` int(11) NOT NULL,
  `mainImage` text NOT NULL,
  `thumbImage` text NOT NULL,
  `ogImage` text NOT NULL,
  `folder` text NOT NULL,
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
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author` (`author`),
  KEY `category` (`category`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `publications_elements_ibfk_1` FOREIGN KEY (`author`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_elements_ibfk_2` FOREIGN KEY (`category`) REFERENCES `publications_categories` (`id`),
  CONSTRAINT `publications_elements_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `publications_elements_ibfk_4` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `system_approvals_elements`;
CREATE TABLE `system_approvals_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referenceAlias` text NOT NULL,
  `referenceValue` text NOT NULL,
  `referenceTable` text NOT NULL,
  `referenceDate` text NOT NULL,
  `reason` text DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `approvalAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `approvalBy` bigint(20) DEFAULT NULL,
  `status` text NOT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `createdBy` (`createdBy`),
  KEY `approvalBy` (`approvalBy`),
  CONSTRAINT `system_approvals_elements_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `system_approvals_elements_ibfk_2` FOREIGN KEY (`approvalBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `time_on_platform`;
CREATE TABLE `time_on_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `minutes` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `time_on_platform_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


DROP TABLE IF EXISTS `user_system_profile`;
CREATE TABLE `user_system_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preferSlug` text DEFAULT NULL,
  `jobPosition` text DEFAULT NULL,
  `phoneCode` text DEFAULT NULL,
  `phoneNumber` text DEFAULT NULL,
  `nationality` text DEFAULT NULL,
  `linkedinLink` text DEFAULT NULL,
  `websiteLink` text DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `belongsTo` bigint(20) NOT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `createdBy` bigint(20) NOT NULL,
  `modifiedBy` bigint(20) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country` (`country`),
  KEY `city` (`city`),
  KEY `belongsTo` (`belongsTo`),
  KEY `createdBy` (`createdBy`),
  KEY `modifiedBy` (`modifiedBy`),
  CONSTRAINT `user_system_profile_ibfk_1` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`),
  CONSTRAINT `user_system_profile_ibfk_2` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`),
  CONSTRAINT `user_system_profile_ibfk_3` FOREIGN KEY (`belongsTo`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `user_system_profile_ibfk_4` FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`),
  CONSTRAINT `user_system_profile_ibfk_5` FOREIGN KEY (`modifiedBy`) REFERENCES `pcsphp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- 2025-06-02 03:25:47 UTC