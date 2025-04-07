-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Servidor: db5017571234.hosting-data.io
-- Tiempo de generación: 06-04-2025 a las 11:37:06
-- Versión del servidor: 8.0.36
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbs14071098`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hora_apertura` time NOT NULL,
  `hora_cierre` time NOT NULL,
  `dias_operacion` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creador_id` int NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logo_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banner_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `direccion`, `ciudad`, `codigo_postal`, `telefono`, `email`, `hora_apertura`, `hora_cierre`, `dias_operacion`, `creador_id`, `estado`, `fecha_registro`, `logo_url`, `banner_url`) VALUES
(5, 'Franco Resurgente S.A', 'Calle Caudillo', 'Madrid', '11540', '659000663', 'franquito@gmail.com', '08:00:00', '14:30:00', 'Lun, Mar, Mié, Jue', 1, 'aprobada', '2025-04-02 10:53:35', '/api/uploads/empresas/foto_perfil/samuel.jpg', '/api/uploads/empresas/foto_perfil/samuel.jpg'),
(7, 'Mariscos Recio', 'Contubernio 49', 'Madrid', '10001', '600 000 000', 'mayoristanolimpiopescado@gmail.com', '07:00:00', '21:00:00', 'Lun, Mar, Mié, Jue, Vie', 1, 'aprobada', '2025-04-02 10:59:40', NULL, NULL),
(8, 'PruebaSinImagenes', 'Calle Calva Rio', 'Caceres', '11400', '659884734', 'caillou@gmail.com', '07:30:00', '14:30:00', 'Lun, Mar, Mié', 1, 'aprobada', '2025-04-03 11:10:59', NULL, NULL),
(12, 'Alejandro No Workea', 'sanluka', 'parado', '11540', '123456789', 'aleelgay@gmail.com', '08:00:00', '20:00:00', 'Mar, Jue', 1, 'aprobada', '2025-04-04 08:32:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_04_05_184639_create_personal_access_tokens_table', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int NOT NULL,
  `reserva_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('tarjeta','transferencia','efectivo') COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('pendiente','completado','reembolsado') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `referencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_pago` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `sala_id` int NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `proposito` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` enum('pendiente','confirmada','cancelada','completada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `fecha_reserva` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salas`
--

CREATE TABLE `salas` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('conferencia','reuniones','eventos','capacitacion') COLLATE utf8mb4_general_ci NOT NULL,
  `capacidad` int NOT NULL,
  `precio_hora` decimal(10,2) NOT NULL,
  `equipamiento` text COLLATE utf8mb4_general_ci,
  `disponible` tinyint(1) DEFAULT '1',
  `imagen_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `salas`
--

INSERT INTO `salas` (`id`, `empresa_id`, `nombre`, `tipo`, `capacidad`, `precio_hora`, `equipamiento`, `disponible`, `imagen_url`) VALUES
(5, 5, 'Valle de los caidos', 'eventos', 1000, '30.00', 'Fusiles y reliquias de franco', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `payload` text COLLATE utf8mb4_general_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('SsonJNUVN4Zo6DkBxV8JrxmnfEOKOaCA5q2RfWjq', NULL, '80.31.199.239', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWmJBNW5BZmVObEIwMXFrVXI5OEdocFhoMEs2bVZINEMzMjZrYjlReiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTg6Imh0dHA6Ly93ZXNwYWNlcy5lcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1743935682);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rol` enum('admin','supervisor','usuario') COLLATE utf8mb4_general_ci DEFAULT 'usuario',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `rol`, `fecha_registro`, `foto_url`) VALUES
(1, 'Admin', 'Sistema', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 'admin', '2025-03-31 06:52:22', NULL),
(2, 'Supervisor', 'Empresa', 'supervisor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1231231234', 'supervisor', '2025-03-31 06:52:22', NULL),
(3, 'Usuario', 'Prueba', 'usuario@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', 'usuario', '2025-03-31 06:52:22', NULL),
(4, 'nombre', 'apellido', 'email', '$2y$10$IJlZoNkPSQGKYI3mlZlVF.oqfCURHZVC962i8lQWnwhxEOL2tXcQC', NULL, 'usuario', '2025-04-01 07:16:44', NULL),
(5, 'hola', 'a', 'asda@fas.es', '$2y$10$k4H/V9iBA4t99QcbRPy.i.NSG04C9zdD5.o07cjkUYbUboaVFFMYa', NULL, 'usuario', '2025-04-01 07:38:22', NULL),
(6, 'Diego', 'Garcia', 'diego@gmail.com', '$2y$10$D.2u5Op1Eu0KuZr9so/9Aer/v1nfjZ0R3uYW.wlvKtrSk3eBZb/UG', NULL, 'usuario', '2025-04-01 07:42:00', NULL),
(7, 'Pablo', 'Cortes', 'pablo@gmail.com', '$2y$10$EHuO9fl6HdbhdzXbHuPvb.8qksrKLd5Z4TW5Wo/CVoG2rJEJtC50O', NULL, 'usuario', '2025-04-01 07:49:35', NULL),
(8, 'Alex', 'Rodriguez Mera', 'alex@gmail.com', '$2y$10$DQwKlxCCD/fSiCdqfHElZOQhKgIj5SNqDYveSaJtSsiJWistKAfLO', '665765847', 'usuario', '2025-04-01 07:53:05', NULL),
(9, 'Fernando', 'García Buz', 'fer@gmail.com', '$2y$10$NVes0HZTmU1GtUSnD6Zw2.hda3CT54EkVDP1.CL6phkQ1bmm6MhpO', '', 'usuario', '2025-04-01 07:55:11', NULL),
(10, 'Antonio', 'Guisado', 'antonio@gmail.com', '$2y$10$Fgy06DCh67w6DQY4GqMaCer9a19gluR2c4hyLc0AGdrg9sea97Svu', '644436005', 'usuario', '2025-04-01 07:55:51', NULL),
(11, 'Luis', 'Alcón', 'luisalcon100@gmail.com', '$2y$10$FCnKU./2lYaXqjcOXzmiVO5LeyofZ72cDjrO1iuR3YC2qf3VaKi3u', '644725419', 'usuario', '2025-04-01 07:57:20', NULL),
(12, 'Sebastian', 'Gonzalez', 'sebas@gmail.com', '$2y$10$kdk.7f8MRdfx541a8iC5CewrTQUVrpXuKUkvos0Ii4ytF2h7xsoKa', '659000993', 'usuario', '2025-04-01 07:57:27', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `validaciones_empresas`
--

CREATE TABLE `validaciones_empresas` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `comentarios` text COLLATE utf8mb4_general_ci,
  `estado` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `validaciones_empresas`
--

INSERT INTO `validaciones_empresas` (`id`, `empresa_id`, `admin_id`, `comentarios`, `estado`, `fecha_solicitud`, `fecha_resolucion`) VALUES
(5, 5, NULL, NULL, 'pendiente', '2025-04-02 10:53:35', NULL),
(7, 7, NULL, NULL, 'pendiente', '2025-04-02 10:59:40', NULL),
(8, 8, NULL, NULL, 'pendiente', '2025-04-03 11:10:59', NULL),
(12, 12, NULL, NULL, 'pendiente', '2025-04-04 08:32:21', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creador_id` (`creador_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserva_id` (`reserva_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `sala_id` (`sala_id`);

--
-- Indices de la tabla `salas`
--
ALTER TABLE `salas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `validaciones_empresas`
--
ALTER TABLE `validaciones_empresas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salas`
--
ALTER TABLE `salas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `validaciones_empresas`
--
ALTER TABLE `validaciones_empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `salas`
--
ALTER TABLE `salas`
  ADD CONSTRAINT `salas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `validaciones_empresas`
--
ALTER TABLE `validaciones_empresas`
  ADD CONSTRAINT `validaciones_empresas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `validaciones_empresas_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
