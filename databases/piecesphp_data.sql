-- phpMyAdmin SQL Dump
-- version 4.8.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 18-04-2019 a las 19:05:28
-- Versión del servidor: 5.7.25-0ubuntu0.16.04.2
-- Versión de PHP: 7.1.28-1+ubuntu16.04.1+deb.sury.org+3

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
-- Truncar tablas antes de insertar `messages`
--

TRUNCATE TABLE `messages`;
--
-- Truncar tablas antes de insertar `messages_responses`
--

TRUNCATE TABLE `messages_responses`;
--
-- Truncar tablas antes de insertar `pcsphp_blackboard_news_messages`
--

TRUNCATE TABLE `pcsphp_blackboard_news_messages`;
--
-- Truncar tablas antes de insertar `pcsphp_recovery_password`
--

TRUNCATE TABLE `pcsphp_recovery_password`;
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
(1, 'root', '$2y$10$7zoL4vBsF3FZ/73jSUrrD.yjYExwn3ZTIp4TsGGJr0xEaMQCmDBEK', 'Administrador', '', 'Root', '', 'vicsenmorantes@tejidodigital.com', '', 0, 1, 0, '2018-06-20 14:11:54', '2019-01-21 05:19:37');

--
-- Truncar tablas antes de insertar `pcsphp_user_problems`
--

TRUNCATE TABLE `pcsphp_user_problems`;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
