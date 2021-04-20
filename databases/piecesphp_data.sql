-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 08-10-2019 a las 12:12:55
-- Versión del servidor: 5.7.27-0ubuntu0.16.04.1
-- Versión de PHP: 7.1.32-1+ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `piecesphp`
--

--
-- Truncar tablas antes de insertar `locations_points`
--

TRUNCATE TABLE `locations_points`;

--
-- Truncar tablas antes de insertar `locations_cities`
--

TRUNCATE TABLE `locations_cities`;

--
-- Truncar tablas antes de insertar `locations_states`
--

TRUNCATE TABLE `locations_states`;
--
-- Truncar tablas antes de insertar `locations_countries`
--

TRUNCATE TABLE `locations_countries`;
--
-- Truncar tablas antes de insertar `login_attempts`
--

TRUNCATE TABLE `login_attempts`;
--
-- Truncar tablas antes de insertar `messages`
--

TRUNCATE TABLE `messages`;
--
-- Truncar tablas antes de insertar `messages_responses`
--

TRUNCATE TABLE `messages_responses`;
--
-- Truncar tablas antes de insertar `pcsphp_app_config`
--

TRUNCATE TABLE `pcsphp_app_config`;
--
-- Truncar tablas antes de insertar `pcsphp_articles`
--

TRUNCATE TABLE `pcsphp_articles`;
--
-- Truncar tablas antes de insertar `pcsphp_articles_categories`
--

TRUNCATE TABLE `pcsphp_articles_categories`;
--
-- Volcado de datos para la tabla `pcsphp_articles_categories`
--

INSERT INTO `pcsphp_articles_categories` (`id`) VALUES
(1);
--
-- Truncar tablas antes de insertar `pcsphp_articles_categories_content`
--

TRUNCATE TABLE `pcsphp_articles_categories_content`;
--
-- Volcado de datos para la tabla `pcsphp_articles_categories_content`
--

INSERT INTO `pcsphp_articles_categories_content` (`id`, `content_of`, `lang`, `name`, `description`, `friendly_url`) VALUES
(1, 1, 'es', 'General', '', 'general');
INSERT INTO `pcsphp_articles_categories_content` (`id`, `content_of`, `lang`, `name`, `description`, `friendly_url`) VALUES
(2, 1, 'en', 'General', '', 'general-1');
INSERT INTO `pcsphp_articles_categories_content` (`id`, `content_of`, `lang`, `name`, `description`, `friendly_url`) VALUES
(3, 1, 'fr', 'Général', '', 'general-2');

--
-- Truncar tablas antes de insertar `pcsphp_blackboard_news_messages`
--

TRUNCATE TABLE `pcsphp_blackboard_news_messages`;
--
-- Truncar tablas antes de insertar `pcsphp_recovery_password`
--

TRUNCATE TABLE `pcsphp_recovery_password`;
--
-- Truncar tablas antes de insertar `pcsphp_tickets_log`
--

TRUNCATE TABLE `pcsphp_tickets_log`;
--
-- Truncar tablas antes de insertar `pcsphp_tokens`
--

TRUNCATE TABLE `pcsphp_tokens`;
--
-- Truncar tablas antes de insertar `pcsphp_users`
--

TRUNCATE TABLE `pcsphp_users`;
--
-- Volcado de datos para la tabla `pcsphp_users`
--

INSERT INTO `pcsphp_users` (`id`, `username`, `password`, `firstname`, `secondname`, `first_lastname`, `second_lastname`, `email`, `meta`, `type`, `status`, `failed_attempts`, `created_at`, `modified_at`) VALUES
(1, 'root', '$2y$10$7zoL4vBsF3FZ/73jSUrrD.yjYExwn3ZTIp4TsGGJr0xEaMQCmDBEK', 'Administrador', '', 'Root', '', 'vicsenmorantes@tejidodigital.com', '', 0, 1, 0, '2018-06-20 14:11:54', '2019-01-21 05:19:37'),
(2, 'admin', '$2y$10$hdmge3MNnbOcD5hp2OwCtuC/HzqKwkgibWGKc0hqpfn55mfRqDCfu', 'Lacey', 'Russo', 'Young', 'Curry', 'jonys@mailinator.net', NULL, 1, 1, 0, '2019-10-08 11:07:37', '2019-10-08 11:07:37'),
(3, 'general', '$2y$10$sIZ0qYha8A/6xIv2tsatL.sDPXlkmLMZt.QqUPvNZxnlcD4cgYiuW', 'Diana', 'Donovan', 'Bean', 'Buckley', 'wynim@mailinator.com', 'null', 2, 1, 0, '2019-10-08 11:07:45', '2019-10-08 11:08:01');

--
-- Truncar tablas antes de insertar `pcsphp_user_problems`
--

TRUNCATE TABLE `pcsphp_user_problems`;
--
-- Truncar tablas antes de insertar `time_on_platform`
--

TRUNCATE TABLE `time_on_platform`;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
