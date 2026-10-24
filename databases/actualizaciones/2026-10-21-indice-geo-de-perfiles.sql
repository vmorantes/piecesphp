-- Índice por posición en los perfiles de las personas (C11, el geovisor).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: el índice ya viene en
-- `piecesphp_structure.sql`.
--
-- Es UN paso y no hay tarea que ejecutar después. SE PUEDE HACER CON LA APLICACIÓN EN MARCHA: añadir un índice no
-- cambia ningún dato.
--
-- POR QUÉ: el geovisor pide los puntos del encuadre que se ve en pantalla, y el encuadre se filtra en WHERE sobre
-- `longitude` y `latitude`. Sin índice, cada petición recorre todos los perfiles aunque el encuadre sea un barrio.

ALTER TABLE `user_system_profile`
  ADD KEY `geo` (`longitude`, `latitude`);
