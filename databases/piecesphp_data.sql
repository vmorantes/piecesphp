-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 09-05-2021 a las 15:17:47
-- Versión del servidor: 10.5.10-MariaDB-1:10.5.10+maria~focal
-- Versión de PHP: 7.3.28-1+ubuntu20.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
-- Truncar tablas antes de insertar `app_presentations`
--

TRUNCATE TABLE `app_presentations`;
--
-- Truncar tablas antes de insertar `app_presentations_categories`
--

TRUNCATE TABLE `app_presentations_categories`;

--
-- Truncar tablas antes de insertar `locations_cities`
--

TRUNCATE TABLE `locations_cities`;

--
-- Truncar tablas antes de insertar `locations_countries`
--

TRUNCATE TABLE `locations_countries`;

--
-- Truncar tablas antes de insertar `locations_points`
--

TRUNCATE TABLE `locations_points`;
--
-- Truncar tablas antes de insertar `locations_states`
--

TRUNCATE TABLE `locations_states`;

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
-- Truncar tablas antes de insertar `pcsphp_blackboard_news_messages`
--

TRUNCATE TABLE `pcsphp_blackboard_news_messages`;
--
-- Truncar tablas antes de insertar `pcsphp_dynamic_images`
--

TRUNCATE TABLE `pcsphp_dynamic_images`;

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
(1, 'root', '$2y$10$LNWWnGirOd51j5FN5IKFr.U6BsejgWKUkRba6XnrQnroPyhGtTgrG', 'Root', '', 'User', '', 'root@mail.com', '', 0, 1, 0, '2018-06-20 14:11:54', '2021-05-09 14:24:06'),
(2, 'admin', '$2y$10$7FvFY8fXdq1F8wMy8bOUie2HGSMtH/Eh2yfwMuKLSw70C85ODIwa.', 'Admin', '', 'User', '', 'admin@mail.com', 'null', 1, 0, 0, '2019-10-08 11:07:37', '2021-05-09 14:23:37'),
(3, 'general', '$2y$10$pPM4wtsC5Uu/RadMDhgncelnS0pvNpAld/ylJcleH5g3qS00ytwEm', 'General', '', 'User', '', 'general@mail.com', 'null', 2, 0, 0, '2019-10-08 11:07:45', '2021-05-09 14:21:52');

--
-- Truncar tablas antes de insertar `pcsphp_user_problems`
--

TRUNCATE TABLE `pcsphp_user_problems`;
--
-- Truncar tablas antes de insertar `publications_categories`
--

TRUNCATE TABLE `publications_categories`;

--
-- Truncar tablas antes de insertar `publications_elements`
--

TRUNCATE TABLE `publications_elements`;

--
-- Truncar tablas antes de insertar `time_on_platform`
--

TRUNCATE TABLE `time_on_platform`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
