-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-11-2025 a las 23:38:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_clinico`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `tipo_cita` enum('consulta','control','emergencia','otro') DEFAULT 'consulta',
  `estado` enum('programada','confirmada','en_atencion','atendida','cancelada','ausente') DEFAULT 'programada',
  `recordatorio_enviado` tinyint(1) DEFAULT 0,
  `motivo_consulta` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `hora_llegada` datetime DEFAULT NULL,
  `hora_atencion` datetime DEFAULT NULL,
  `hora_finalizacion` datetime DEFAULT NULL,
  `usuario_registro_id` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `paciente_id`, `medico_id`, `fecha`, `hora`, `tipo_cita`, `estado`, `recordatorio_enviado`, `motivo_consulta`, `observaciones`, `hora_llegada`, `hora_atencion`, `hora_finalizacion`, `usuario_registro_id`, `fecha_registro`, `fecha_modificacion`) VALUES
(1, 1, 1, '2025-11-21', '09:00:00', 'consulta', 'confirmada', 0, 'Dolor de cabeza persistente', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(2, 2, 3, '2025-11-21', '15:00:00', 'control', 'programada', 0, 'Control de vacunas', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(3, 3, 2, '2025-11-21', '10:00:00', 'consulta', 'confirmada', 0, 'Dolor en el pecho', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(4, 4, 1, '2025-11-21', '10:00:00', 'consulta', 'programada', 0, 'Fiebre y malestar general', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(5, 5, 6, '2025-11-21', '11:00:00', 'consulta', 'confirmada', 0, 'Manchas en la piel', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(6, 6, 4, '2025-11-22', '09:00:00', 'control', 'programada', 0, 'Control prenatal', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(7, 7, 5, '2025-11-22', '16:00:00', 'consulta', 'programada', 0, 'Dolor en rodilla', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(8, 8, 1, '2025-11-23', '08:00:00', 'consulta', 'programada', 0, 'Chequeo general', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(9, 9, 3, '2025-11-23', '15:30:00', 'consulta', 'programada', 0, 'Consulta pediátrica', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(10, 10, 2, '2025-11-24', '10:00:00', 'control', 'programada', 0, 'Control cardiológico', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(11, 11, 6, '2025-11-24', '11:30:00', 'consulta', 'programada', 0, 'Acné facial', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(12, 12, 1, '2025-11-25', '09:30:00', 'consulta', 'programada', 0, 'Malestar estomacal', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(13, 13, 5, '2025-11-25', '17:00:00', 'consulta', 'programada', 0, 'Esguince de tobillo', NULL, NULL, NULL, NULL, 9, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(14, 14, 4, '2025-11-26', '09:30:00', 'consulta', 'programada', 0, 'Consulta ginecológica', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(15, 15, 3, '2025-11-26', '16:00:00', 'control', 'programada', 0, 'Vacunación infantil', NULL, NULL, NULL, NULL, 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(16, 1, 1, '2025-11-14', '09:00:00', 'consulta', 'atendida', 0, 'Gripe común', NULL, '2025-11-14 00:00:00', '2025-11-14 00:00:00', '2025-11-14 00:00:00', 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(17, 3, 2, '2025-11-16', '10:00:00', 'consulta', 'atendida', 0, 'Hipertensión arterial', NULL, '2025-11-16 00:00:00', '2025-11-16 00:00:00', '2025-11-16 00:00:00', 8, '2025-11-21 16:17:09', '2025-11-21 16:17:09'),
(18, 10, 6, '2025-11-26', '10:00:00', 'control', 'programada', 0, 'emergencia', '', NULL, NULL, NULL, 11, '2025-11-21 21:29:23', '2025-11-21 21:29:23'),
(19, 3, 6, '2025-11-21', '13:30:00', 'control', 'cancelada', 0, 'n', '', NULL, NULL, NULL, 11, '2025-11-21 21:30:58', '2025-11-21 21:31:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diagnosticos`
--

CREATE TABLE `diagnosticos` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `codigo_cie10` varchar(10) DEFAULT NULL,
  `diagnostico` text NOT NULL,
  `tipo` enum('presuntivo','definitivo') DEFAULT 'presuntivo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_historia`
--

CREATE TABLE `documentos_historia` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_documento` varchar(100) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tamaño_archivo` int(11) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `subido_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 'Medicina General', 'Atención médica general y preventiva', 'activo', '2025-11-21 16:17:09'),
(2, 'Cardiología', 'Especialista en enfermedades del corazón', 'activo', '2025-11-21 16:17:09'),
(3, 'Pediatría', 'Atención médica para niños', 'activo', '2025-11-21 16:17:09'),
(4, 'Ginecología', 'Atención de salud femenina', 'activo', '2025-11-21 16:17:09'),
(5, 'Traumatología', 'Especialista en lesiones y enfermedades del sistema musculoesquelético', 'activo', '2025-11-21 16:17:09'),
(6, 'Dermatología', 'Especialista en enfermedades de la piel', 'activo', '2025-11-21 16:17:09'),
(7, 'Oftalmología', 'Especialista en salud visual', 'activo', '2025-11-21 16:17:09'),
(8, 'Neurología', 'Especialista en enfermedades del sistema nervioso', 'activo', '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evoluciones`
--

CREATE TABLE `evoluciones` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha_evolucion` datetime NOT NULL,
  `nota_evolucion` text NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_fisicos`
--

CREATE TABLE `examenes_fisicos` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `sistema` varchar(100) NOT NULL,
  `hallazgos` text NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `excepciones_horario`
--

CREATE TABLE `excepciones_horario` (
  `id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `tipo` enum('vacacion','reunion','emergencia','otro') DEFAULT 'otro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_citas`
--

CREATE TABLE `historial_citas` (
  `id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `accion` enum('creacion','reprogramacion','cancelacion','cambio_estado') NOT NULL,
  `fecha_anterior` date DEFAULT NULL,
  `hora_anterior` time DEFAULT NULL,
  `medico_anterior_id` int(11) DEFAULT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `fecha_nueva` date DEFAULT NULL,
  `hora_nueva` time DEFAULT NULL,
  `medico_nuevo_id` int(11) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_citas`
--

INSERT INTO `historial_citas` (`id`, `cita_id`, `accion`, `fecha_anterior`, `hora_anterior`, `medico_anterior_id`, `estado_anterior`, `fecha_nueva`, `hora_nueva`, `medico_nuevo_id`, `estado_nuevo`, `motivo`, `usuario_id`, `fecha_cambio`) VALUES
(1, 18, 'creacion', NULL, NULL, NULL, NULL, '2025-11-26', '10:00:00', 6, 'programada', NULL, 11, '2025-11-21 21:29:23'),
(2, 19, 'creacion', NULL, NULL, NULL, NULL, '2025-11-21', '13:30:00', 6, 'programada', NULL, 11, '2025-11-21 21:30:58'),
(3, 19, 'cambio_estado', NULL, NULL, NULL, 'programada', NULL, NULL, NULL, 'cancelada', NULL, 11, '2025-11-21 21:31:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historias_clinicas`
--

CREATE TABLE `historias_clinicas` (
  `id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha_atencion` datetime NOT NULL,
  `motivo_consulta` text NOT NULL,
  `sintomas` text DEFAULT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `frecuencia_cardiaca` int(11) DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` decimal(5,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `codigo_cie10` varchar(10) DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `proxima_cita` date DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historias_clinicas`
--

INSERT INTO `historias_clinicas` (`id`, `cita_id`, `paciente_id`, `medico_id`, `fecha_atencion`, `motivo_consulta`, `sintomas`, `presion_arterial`, `frecuencia_cardiaca`, `temperatura`, `peso`, `talla`, `imc`, `diagnostico`, `codigo_cie10`, `tratamiento`, `observaciones`, `proxima_cita`, `fecha_registro`) VALUES
(1, 16, 1, 1, '2025-11-14 00:00:00', 'Gripe común', 'Tos, fiebre, malestar general', '120/80', 78, 38.50, 75.00, 1.75, 24.49, 'Rinofaringitis aguda (Resfriado común)', 'J00', 'Reposo, abundantes líquidos, paracetamol 500mg cada 8 horas', 'Paciente debe regresar si los síntomas empeoran', NULL, '2025-11-21 16:17:09'),
(2, 17, 3, 2, '2025-11-16 00:00:00', 'Hipertensión arterial', 'Dolor de cabeza, mareos', '150/95', 85, 36.80, 82.00, 1.70, 28.37, 'Hipertensión esencial (primaria)', 'I10', 'Enalapril 10mg al día, dieta baja en sal, ejercicio regular', 'Control en 15 días', NULL, '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historia_clinica`
--

CREATE TABLE `historia_clinica` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha_consulta` date NOT NULL,
  `motivo_consulta` text NOT NULL,
  `antecedentes_personales` text DEFAULT NULL,
  `antecedentes_familiares` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `medicamentos_actuales` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_medicos`
--

CREATE TABLE `horarios_medicos` (
  `id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `cupos_por_hora` int(11) DEFAULT 2,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios_medicos`
--

INSERT INTO `horarios_medicos` (`id`, `medico_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `cupos_por_hora`, `estado`) VALUES
(1, 1, 'lunes', '08:00:00', '14:00:00', 2, 'activo'),
(2, 1, 'martes', '08:00:00', '14:00:00', 2, 'activo'),
(3, 1, 'miercoles', '08:00:00', '14:00:00', 2, 'activo'),
(4, 1, 'jueves', '08:00:00', '14:00:00', 2, 'activo'),
(5, 1, 'viernes', '08:00:00', '14:00:00', 2, 'activo'),
(6, 2, 'lunes', '09:00:00', '13:00:00', 1, 'activo'),
(7, 2, 'miercoles', '09:00:00', '13:00:00', 1, 'activo'),
(8, 2, 'viernes', '09:00:00', '13:00:00', 1, 'activo'),
(9, 3, 'lunes', '14:00:00', '18:00:00', 2, 'activo'),
(10, 3, 'martes', '14:00:00', '18:00:00', 2, 'activo'),
(11, 3, 'miercoles', '14:00:00', '18:00:00', 2, 'activo'),
(12, 3, 'jueves', '14:00:00', '18:00:00', 2, 'activo'),
(13, 3, 'viernes', '14:00:00', '18:00:00', 2, 'activo'),
(14, 4, 'martes', '08:00:00', '12:00:00', 2, 'activo'),
(15, 4, 'jueves', '08:00:00', '12:00:00', 2, 'activo'),
(16, 5, 'lunes', '15:00:00', '19:00:00', 2, 'activo'),
(17, 5, 'martes', '15:00:00', '19:00:00', 2, 'activo'),
(18, 5, 'miercoles', '15:00:00', '19:00:00', 2, 'activo'),
(19, 5, 'jueves', '15:00:00', '19:00:00', 2, 'activo'),
(20, 5, 'viernes', '15:00:00', '19:00:00', 2, 'activo'),
(21, 6, 'lunes', '10:00:00', '14:00:00', 2, 'activo'),
(22, 6, 'miercoles', '10:00:00', '14:00:00', 2, 'activo'),
(23, 6, 'viernes', '10:00:00', '14:00:00', 2, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicos`
--

CREATE TABLE `medicos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `especialidad_id` int(11) NOT NULL,
  `numero_colegiatura` varchar(50) NOT NULL,
  `consultorio` varchar(50) DEFAULT NULL,
  `duracion_consulta` int(11) DEFAULT 30 COMMENT 'Duración en minutos',
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medicos`
--

INSERT INTO `medicos` (`id`, `usuario_id`, `especialidad_id`, `numero_colegiatura`, `consultorio`, `duracion_consulta`, `estado`) VALUES
(1, 2, 1, 'CMP-12345', 'Consultorio 101', 30, 'activo'),
(2, 3, 2, 'CMP-12346', 'Consultorio 201', 40, 'activo'),
(3, 4, 3, 'CMP-12347', 'Consultorio 102', 30, 'activo'),
(4, 5, 4, 'CMP-12348', 'Consultorio 202', 35, 'activo'),
(5, 6, 5, 'CMP-12349', 'Consultorio 103', 30, 'activo'),
(6, 7, 6, 'CMP-12350', 'Consultorio 203', 25, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `enlace` varchar(500) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_lectura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_examenes`
--

CREATE TABLE `ordenes_examenes` (
  `id` int(11) NOT NULL,
  `historia_clinica_id` int(11) NOT NULL,
  `tipo` enum('laboratorio','imagen','otro') NOT NULL,
  `examen` varchar(200) NOT NULL,
  `indicaciones` text DEFAULT NULL,
  `urgente` tinyint(1) DEFAULT 0,
  `estado` enum('pendiente','realizado','cancelado') DEFAULT 'pendiente',
  `resultado` text DEFAULT NULL,
  `fecha_resultado` datetime DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ordenes_examenes`
--

INSERT INTO `ordenes_examenes` (`id`, `historia_clinica_id`, `tipo`, `examen`, `indicaciones`, `urgente`, `estado`, `resultado`, `fecha_resultado`, `fecha_registro`) VALUES
(1, 2, 'laboratorio', 'Perfil lipídico completo', 'En ayunas de 12 horas', 0, 'pendiente', NULL, NULL, '2025-11-21 16:17:09'),
(2, 2, 'laboratorio', 'Glucosa en sangre', 'En ayunas', 0, 'pendiente', NULL, NULL, '2025-11-21 16:17:09'),
(3, 2, 'imagen', 'Electrocardiograma', 'Examen de rutina', 0, 'pendiente', NULL, NULL, '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('M','F') NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `seguro_id` int(11) DEFAULT NULL,
  `numero_poliza` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `dni`, `nombre`, `apellido`, `fecha_nacimiento`, `sexo`, `telefono`, `email`, `direccion`, `seguro_id`, `numero_poliza`, `estado`, `fecha_registro`) VALUES
(1, '72345678', 'Carlos', 'Rodríguez Pérez', '1985-03-15', 'M', '987123456', 'carlos.rodriguez@email.com', 'Av. Arequipa 1234, Lima', 1, 'ES-123456', 'activo', '2025-11-21 16:17:09'),
(2, '45678912', 'Laura', 'Fernández Torres', '1990-07-22', 'F', '987123457', 'laura.fernandez@email.com', 'Jr. Lima 567, Lima', 2, 'PAC-789012', 'activo', '2025-11-21 16:17:09'),
(3, '78912345', 'Miguel', 'Sánchez Rojas', '1978-11-08', 'M', '987123458', 'miguel.sanchez@email.com', 'Av. Brasil 2345, Lima', 3, 'RIM-345678', 'activo', '2025-11-21 16:17:09'),
(4, '12345679', 'Patricia', 'González Vega', '1995-05-30', 'F', '987123459', 'patricia.gonzalez@email.com', 'Calle Los Pinos 890, Lima', 1, 'ES-234567', 'activo', '2025-11-21 16:17:09'),
(5, '67891234', 'Ricardo', 'Mendoza Luna', '1988-09-14', 'M', '987123460', 'ricardo.mendoza@email.com', 'Av. Universitaria 456, Lima', 7, NULL, 'activo', '2025-11-21 16:17:09'),
(6, '34567891', 'Andrea', 'Castro Díaz', '1992-12-25', 'F', '987123461', 'andrea.castro@email.com', 'Jr. Cusco 123, Lima', 2, 'PAC-901234', 'activo', '2025-11-21 16:17:09'),
(7, '89123456', 'Fernando', 'Ramírez Silva', '1980-04-18', 'M', '987123462', 'fernando.ramirez@email.com', 'Av. Tacna 789, Lima', 4, 'POS-567890', 'activo', '2025-11-21 16:17:09'),
(8, '23456789', 'Gabriela', 'Torres Morales', '1993-08-05', 'F', '987123463', 'gabriela.torres@email.com', 'Calle Las Flores 234, Lima', 5, 'MAP-123456', 'activo', '2025-11-21 16:17:09'),
(9, '56789123', 'Roberto', 'Flores Campos', '1975-01-20', 'M', '987123464', 'roberto.flores@email.com', 'Av. Colonial 567, Lima', 6, 'SIS-678901', 'activo', '2025-11-21 16:17:09'),
(10, '91234567', 'Mariana', 'Ruiz Herrera', '1998-06-12', 'F', '987123465', 'mariana.ruiz@email.com', 'Jr. Ancash 890, Lima', 1, 'ES-345678', 'activo', '2025-11-21 16:17:09'),
(11, '45671234', 'Diego', 'Vargas Ortiz', '1987-10-28', 'M', '987123466', 'diego.vargas@email.com', 'Av. Javier Prado 1234, Lima', 2, 'PAC-012345', 'activo', '2025-11-21 16:17:09'),
(12, '78123456', 'Valeria', 'Paredes Núñez', '1991-02-16', 'F', '987123467', 'valeria.paredes@email.com', 'Calle San Martín 456, Lima', 7, NULL, 'activo', '2025-11-21 16:17:09'),
(13, '12378945', 'Andrés', 'Gutiérrez Ríos', '1983-07-09', 'M', '987123468', 'andres.gutierrez@email.com', 'Av. Salaverry 789, Lima', 3, 'RIM-456789', 'activo', '2025-11-21 16:17:09'),
(14, '67845123', 'Carolina', 'Jiménez Cruz', '1994-11-23', 'F', '987123469', 'carolina.jimenez@email.com', 'Jr. Quilca 123, Lima', 1, 'ES-456789', 'activo', '2025-11-21 16:17:09'),
(15, '34512678', 'Javier', 'Medina Soto', '1989-03-07', 'M', '987123470', 'javier.medina@email.com', 'Av. Venezuela 234, Lima', 4, 'POS-678901', 'activo', '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `modulo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `nombre`, `descripcion`, `modulo`) VALUES
(1, 'ver_dashboard', 'Ver panel principal', 'dashboard'),
(2, 'gestionar_citas', 'Crear, editar y cancelar citas', 'citas'),
(3, 'ver_citas', 'Ver listado de citas', 'citas'),
(4, 'gestionar_pacientes', 'Crear y editar pacientes', 'pacientes'),
(5, 'ver_pacientes', 'Ver listado de pacientes', 'pacientes'),
(6, 'gestionar_medicos', 'Crear y editar médicos', 'medicos'),
(7, 'ver_medicos', 'Ver listado de médicos', 'medicos'),
(8, 'gestionar_usuarios', 'Crear y editar usuarios', 'usuarios'),
(9, 'gestionar_permisos', 'Asignar permisos a usuarios', 'usuarios'),
(10, 'ver_historia_clinica', 'Ver historias clínicas', 'historia'),
(11, 'editar_historia_clinica', 'Crear y editar historias clínicas', 'historia'),
(12, 'ver_reportes', 'Ver reportes y estadísticas', 'reportes'),
(13, 'gestionar_horarios', 'Configurar horarios de médicos', 'configuracion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedimientos`
--

CREATE TABLE `procedimientos` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_realizacion` date DEFAULT NULL,
  `resultado` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id` int(11) NOT NULL,
  `historia_clinica_id` int(11) NOT NULL,
  `medicamento` varchar(200) NOT NULL,
  `dosis` varchar(100) NOT NULL,
  `frecuencia` varchar(100) NOT NULL,
  `duracion` varchar(100) NOT NULL,
  `indicaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`id`, `historia_clinica_id`, `medicamento`, `dosis`, `frecuencia`, `duracion`, `indicaciones`, `fecha_registro`) VALUES
(1, 1, 'Paracetamol', '500mg', 'Cada 8 horas', '5 días', 'Tomar con alimentos', '2025-11-21 16:17:09'),
(2, 1, 'Loratadina', '10mg', 'Cada 24 horas', '5 días', 'Tomar en la noche', '2025-11-21 16:17:09'),
(3, 2, 'Enalapril', '10mg', 'Una vez al día', '30 días', 'Tomar en ayunas por la mañana', '2025-11-21 16:17:09'),
(4, 2, 'Atorvastatina', '20mg', 'Una vez al día', '30 días', 'Tomar en la noche', '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguros`
--

CREATE TABLE `seguros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seguros`
--

INSERT INTO `seguros` (`id`, `nombre`, `tipo`, `telefono`, `email`, `direccion`, `estado`, `fecha_registro`) VALUES
(1, 'EsSalud', 'Público', '01-4808000', 'atencion@essalud.gob.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(2, 'Pacífico Seguros', 'Privado', '01-5130000', 'contacto@pacifico.com.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(3, 'Rímac Seguros', 'Privado', '01-4119000', 'consultas@rimac.com.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(4, 'La Positiva Seguros', 'Privado', '01-5139000', 'info@lapositiva.com.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(5, 'Mapfre Seguros', 'Privado', '01-2115900', 'servicio@mapfre.com.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(6, 'SIS (Sistema Integral de Salud)', 'Público', '01-2037777', 'consultas@sis.gob.pe', NULL, 'activo', '2025-11-21 16:17:09'),
(7, 'Particular (Sin seguro)', 'Particular', NULL, NULL, NULL, 'activo', '2025-11-21 16:17:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `signos_vitales`
--

CREATE TABLE `signos_vitales` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `frecuencia_cardiaca` int(11) DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `frecuencia_respiratoria` int(11) DEFAULT NULL,
  `saturacion_oxigeno` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` decimal(5,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tratamientos`
--

CREATE TABLE `tratamientos` (
  `id` int(11) NOT NULL,
  `historia_id` int(11) NOT NULL,
  `medicamento` varchar(255) NOT NULL,
  `dosis` varchar(100) NOT NULL,
  `frecuencia` varchar(100) NOT NULL,
  `duracion` varchar(100) NOT NULL,
  `indicaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('admin','medico','recepcionista') NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `apellido`, `email`, `telefono`, `rol`, `estado`, `fecha_creacion`, `ultimo_acceso`) VALUES
(2, 'maria.lopez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María', 'López García', 'maria.lopez@clinica.com', '987654321', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(3, 'juan.perez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Pérez Sánchez', 'juan.perez@clinica.com', '987654322', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(4, 'carmen.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carmen', 'Silva Torres', 'carmen.silva@clinica.com', '987654323', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(5, 'roberto.mendoza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Roberto', 'Mendoza Ruiz', 'roberto.mendoza@clinica.com', '987654324', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(6, 'ana.castro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana', 'Castro Flores', 'ana.castro@clinica.com', '987654325', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(7, 'luis.ramirez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luis', 'Ramírez Vega', 'luis.ramirez@clinica.com', '987654326', 'medico', 'activo', '2025-11-21 16:17:09', NULL),
(8, 'sofia.martinez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sofía', 'Martínez Díaz', 'sofia.martinez@clinica.com', '987654327', 'recepcionista', 'activo', '2025-11-21 16:17:09', NULL),
(9, 'pedro.garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro', 'García Luna', 'pedro.garcia@clinica.com', '987654328', 'recepcionista', 'activo', '2025-11-21 16:17:09', NULL),
(11, 'admin', '$2y$10$SU/lSvvoagHKUXC3eK.WFuAr/3UiojZFFCYtMdlz2GZfUY4bTJbJC', 'Administrador', 'Sistema', 'admin@clinica.com', '999999999', 'admin', 'activo', '2025-11-21 16:32:48', '2025-11-21 17:18:13'),
(12, 'Lucia', '$2y$10$Ohz9XNW9NRr/4fnvsY.8vO9PxPebQfpt4ZXeObLOH9fJntvLcX5Vi', 'Lucia', 'Tiravanti', 'brendaleontiravanti@upeu.edu.pe', '961817878', 'recepcionista', 'activo', '2025-11-21 17:12:47', '2025-11-21 12:13:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permisos`
--

CREATE TABLE `usuario_permisos` (
  `usuario_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_permisos`
--

INSERT INTO `usuario_permisos` (`usuario_id`, `permiso_id`) VALUES
(2, 1),
(2, 3),
(2, 5),
(2, 10),
(2, 11),
(3, 1),
(3, 3),
(3, 5),
(3, 10),
(3, 11),
(4, 1),
(4, 3),
(4, 5),
(4, 10),
(4, 11),
(5, 1),
(5, 3),
(5, 5),
(5, 10),
(5, 11),
(6, 1),
(6, 3),
(6, 5),
(6, 10),
(6, 11),
(7, 1),
(7, 3),
(7, 5),
(7, 10),
(7, 11),
(8, 1),
(8, 2),
(8, 3),
(8, 4),
(8, 5),
(8, 7),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(9, 5),
(9, 7),
(12, 2),
(12, 3),
(12, 4),
(12, 5),
(12, 6);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_registro_id` (`usuario_registro_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_medico_fecha` (`medico_id`,`fecha`),
  ADD KEY `idx_paciente` (`paciente_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `diagnosticos`
--
ALTER TABLE `diagnosticos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `documentos_historia`
--
ALTER TABLE `documentos_historia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subido_por` (`subido_por`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `evoluciones`
--
ALTER TABLE `evoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `idx_historia` (`historia_id`),
  ADD KEY `idx_fecha` (`fecha_evolucion`);

--
-- Indices de la tabla `examenes_fisicos`
--
ALTER TABLE `examenes_fisicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `excepciones_horario`
--
ALTER TABLE `excepciones_horario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medico_fecha` (`medico_id`,`fecha`);

--
-- Indices de la tabla `historial_citas`
--
ALTER TABLE `historial_citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_id` (`cita_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `historias_clinicas`
--
ALTER TABLE `historias_clinicas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cita_id` (`cita_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `idx_paciente` (`paciente_id`),
  ADD KEY `idx_fecha` (`fecha_atencion`);

--
-- Indices de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cita_id` (`cita_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `idx_paciente` (`paciente_id`),
  ADD KEY `idx_fecha` (`fecha_consulta`);

--
-- Indices de la tabla `horarios_medicos`
--
ALTER TABLE `horarios_medicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medico_dia` (`medico_id`,`dia_semana`);

--
-- Indices de la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`),
  ADD UNIQUE KEY `numero_colegiatura` (`numero_colegiatura`),
  ADD KEY `idx_especialidad` (`especialidad_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_leida` (`leida`),
  ADD KEY `idx_fecha` (`fecha_creacion`);

--
-- Indices de la tabla `ordenes_examenes`
--
ALTER TABLE `ordenes_examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `historia_clinica_id` (`historia_clinica_id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `seguro_id` (`seguro_id`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_nombre` (`nombre`,`apellido`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `procedimientos`
--
ALTER TABLE `procedimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `historia_clinica_id` (`historia_clinica_id`);

--
-- Indices de la tabla `seguros`
--
ALTER TABLE `seguros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historia` (`historia_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_rol` (`rol`);

--
-- Indices de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD PRIMARY KEY (`usuario_id`,`permiso_id`),
  ADD KEY `permiso_id` (`permiso_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `diagnosticos`
--
ALTER TABLE `diagnosticos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_historia`
--
ALTER TABLE `documentos_historia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `evoluciones`
--
ALTER TABLE `evoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examenes_fisicos`
--
ALTER TABLE `examenes_fisicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `excepciones_horario`
--
ALTER TABLE `excepciones_horario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_citas`
--
ALTER TABLE `historial_citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `historias_clinicas`
--
ALTER TABLE `historias_clinicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios_medicos`
--
ALTER TABLE `horarios_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `ordenes_examenes`
--
ALTER TABLE `ordenes_examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `procedimientos`
--
ALTER TABLE `procedimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `seguros`
--
ALTER TABLE `seguros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`usuario_registro_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `diagnosticos`
--
ALTER TABLE `diagnosticos`
  ADD CONSTRAINT `diagnosticos_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documentos_historia`
--
ALTER TABLE `documentos_historia`
  ADD CONSTRAINT `documentos_historia_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentos_historia_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `evoluciones`
--
ALTER TABLE `evoluciones`
  ADD CONSTRAINT `evoluciones_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evoluciones_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Filtros para la tabla `examenes_fisicos`
--
ALTER TABLE `examenes_fisicos`
  ADD CONSTRAINT `examenes_fisicos_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `excepciones_horario`
--
ALTER TABLE `excepciones_horario`
  ADD CONSTRAINT `excepciones_horario_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_citas`
--
ALTER TABLE `historial_citas`
  ADD CONSTRAINT `historial_citas_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_citas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `historias_clinicas`
--
ALTER TABLE `historias_clinicas`
  ADD CONSTRAINT `historias_clinicas_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  ADD CONSTRAINT `historias_clinicas_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `historias_clinicas_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Filtros para la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD CONSTRAINT `historia_clinica_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historia_clinica_ibfk_2` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historia_clinica_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Filtros para la tabla `horarios_medicos`
--
ALTER TABLE `horarios_medicos`
  ADD CONSTRAINT `horarios_medicos_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `medicos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicos_ibfk_2` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ordenes_examenes`
--
ALTER TABLE `ordenes_examenes`
  ADD CONSTRAINT `ordenes_examenes_ibfk_1` FOREIGN KEY (`historia_clinica_id`) REFERENCES `historias_clinicas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`seguro_id`) REFERENCES `seguros` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `procedimientos`
--
ALTER TABLE `procedimientos`
  ADD CONSTRAINT `procedimientos_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`historia_clinica_id`) REFERENCES `historias_clinicas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  ADD CONSTRAINT `signos_vitales_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tratamientos`
--
ALTER TABLE `tratamientos`
  ADD CONSTRAINT `tratamientos_ibfk_1` FOREIGN KEY (`historia_id`) REFERENCES `historia_clinica` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
