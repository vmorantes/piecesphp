-- phpMyAdmin SQL Dump
-- version 4.9.4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 15-05-2020 a las 12:02:03
-- Versión del servidor: 10.3.22-MariaDB-1:10.3.22+maria~bionic-log
-- Versión de PHP: 7.3.17-1+ubuntu18.04.1+deb.sury.org+1

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations_cities`
--

DROP TABLE IF EXISTS `locations_cities`;
CREATE TABLE `locations_cities` (
  `id` int(11) NOT NULL,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `state` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations_countries`
--

DROP TABLE IF EXISTS `locations_countries`;
CREATE TABLE `locations_countries` (
  `id` int(11) NOT NULL,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations_points`
--

DROP TABLE IF EXISTS `locations_points`;
CREATE TABLE `locations_points` (
  `id` int(11) NOT NULL,
  `city` int(11) NOT NULL,
  `address` text COLLATE utf8_bin NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations_states`
--

DROP TABLE IF EXISTS `locations_states`;
CREATE TABLE `locations_states` (
  `id` int(11) NOT NULL,
  `code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `country` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `username_attempt` varchar(255) COLLATE utf8_bin NOT NULL,
  `success` int(11) NOT NULL,
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `date` datetime NOT NULL,
  `extra_data` longtext COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `message_from` bigint(20) NOT NULL,
  `message_to` bigint(20) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text COLLATE utf8_bin NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `attachment` text COLLATE utf8_bin DEFAULT NULL,
  `readed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages_responses`
--

DROP TABLE IF EXISTS `messages_responses`;
CREATE TABLE `messages_responses` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `message_from` bigint(20) NOT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment` text COLLATE utf8_bin DEFAULT NULL,
  `readed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_app_config`
--

DROP TABLE IF EXISTS `pcsphp_app_config`;
CREATE TABLE `pcsphp_app_config` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `value` longtext COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_blackboard_news_messages`
--

DROP TABLE IF EXISTS `pcsphp_blackboard_news_messages`;
CREATE TABLE `pcsphp_blackboard_news_messages` (
  `id` int(11) NOT NULL,
  `author` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `text` longtext COLLATE utf8_bin NOT NULL,
  `type` bigint(20) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_dynamic_images`
--

DROP TABLE IF EXISTS `pcsphp_dynamic_images`;
CREATE TABLE `pcsphp_dynamic_images` (
  `id` int(11) NOT NULL,
  `title` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin DEFAULT NULL,
  `link` text COLLATE utf8_bin DEFAULT NULL,
  `image` text COLLATE utf8_bin NOT NULL,
  `meta` longtext COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_recovery_password`
--

DROP TABLE IF EXISTS `pcsphp_recovery_password`;
CREATE TABLE `pcsphp_recovery_password` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_tickets_log`
--

DROP TABLE IF EXISTS `pcsphp_tickets_log`;
CREATE TABLE `pcsphp_tickets_log` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `message` text COLLATE utf8_bin DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `information` longtext COLLATE utf8_bin DEFAULT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_tokens`
--

DROP TABLE IF EXISTS `pcsphp_tokens`;
CREATE TABLE `pcsphp_tokens` (
  `id` int(11) NOT NULL,
  `token` text COLLATE utf8_bin NOT NULL,
  `type` varchar(255) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_users`
--

DROP TABLE IF EXISTS `pcsphp_users`;
CREATE TABLE `pcsphp_users` (
  `id` bigint(20) NOT NULL,
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pcsphp_user_problems`
--

DROP TABLE IF EXISTS `pcsphp_user_problems`;
CREATE TABLE `pcsphp_user_problems` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `expired` datetime NOT NULL,
  `type` varchar(255) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `time_on_platform`
--

DROP TABLE IF EXISTS `time_on_platform`;
CREATE TABLE `time_on_platform` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `minutes` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `locations_cities`
--
ALTER TABLE `locations_cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `state` (`state`);

--
-- Indices de la tabla `locations_countries`
--
ALTER TABLE `locations_countries`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `locations_points`
--
ALTER TABLE `locations_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city` (`city`);

--
-- Indices de la tabla `locations_states`
--
ALTER TABLE `locations_states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country` (`country`);

--
-- Indices de la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_from` (`message_from`),
  ADD KEY `message_to` (`message_to`);

--
-- Indices de la tabla `messages_responses`
--
ALTER TABLE `messages_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_from` (`message_from`),
  ADD KEY `message_id` (`message_id`);

--
-- Indices de la tabla `pcsphp_app_config`
--
ALTER TABLE `pcsphp_app_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `pcsphp_blackboard_news_messages`
--
ALTER TABLE `pcsphp_blackboard_news_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author` (`author`);

--
-- Indices de la tabla `pcsphp_dynamic_images`
--
ALTER TABLE `pcsphp_dynamic_images`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pcsphp_recovery_password`
--
ALTER TABLE `pcsphp_recovery_password`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pcsphp_tickets_log`
--
ALTER TABLE `pcsphp_tickets_log`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pcsphp_tokens`
--
ALTER TABLE `pcsphp_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pcsphp_users`
--
ALTER TABLE `pcsphp_users`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pcsphp_user_problems`
--
ALTER TABLE `pcsphp_user_problems`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `time_on_platform`
--
ALTER TABLE `time_on_platform`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `locations_cities`
--
ALTER TABLE `locations_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `locations_countries`
--
ALTER TABLE `locations_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `locations_points`
--
ALTER TABLE `locations_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `locations_states`
--
ALTER TABLE `locations_states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `messages_responses`
--
ALTER TABLE `messages_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_app_config`
--
ALTER TABLE `pcsphp_app_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_blackboard_news_messages`
--
ALTER TABLE `pcsphp_blackboard_news_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_dynamic_images`
--
ALTER TABLE `pcsphp_dynamic_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_recovery_password`
--
ALTER TABLE `pcsphp_recovery_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_tickets_log`
--
ALTER TABLE `pcsphp_tickets_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_tokens`
--
ALTER TABLE `pcsphp_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_users`
--
ALTER TABLE `pcsphp_users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pcsphp_user_problems`
--
ALTER TABLE `pcsphp_user_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `time_on_platform`
--
ALTER TABLE `time_on_platform`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `locations_cities`
--
ALTER TABLE `locations_cities`
  ADD CONSTRAINT `locations_cities_ibfk_1` FOREIGN KEY (`state`) REFERENCES `locations_states` (`id`);

--
-- Filtros para la tabla `locations_points`
--
ALTER TABLE `locations_points`
  ADD CONSTRAINT `locations_points_ibfk_1` FOREIGN KEY (`city`) REFERENCES `locations_cities` (`id`);

--
-- Filtros para la tabla `locations_states`
--
ALTER TABLE `locations_states`
  ADD CONSTRAINT `locations_states_ibfk_1` FOREIGN KEY (`country`) REFERENCES `locations_countries` (`id`);

--
-- Filtros para la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`);

--
-- Filtros para la tabla `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`message_to`) REFERENCES `pcsphp_users` (`id`);

--
-- Filtros para la tabla `messages_responses`
--
ALTER TABLE `messages_responses`
  ADD CONSTRAINT `messages_responses_ibfk_1` FOREIGN KEY (`message_from`) REFERENCES `pcsphp_users` (`id`),
  ADD CONSTRAINT `messages_responses_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`);

--
-- Filtros para la tabla `pcsphp_blackboard_news_messages`
--
ALTER TABLE `pcsphp_blackboard_news_messages`
  ADD CONSTRAINT `pcsphp_blackboard_news_messages_ibfk_1` FOREIGN KEY (`author`) REFERENCES `pcsphp_users` (`id`);

--
-- Filtros para la tabla `time_on_platform`
--
ALTER TABLE `time_on_platform`
  ADD CONSTRAINT `time_on_platform_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pcsphp_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
