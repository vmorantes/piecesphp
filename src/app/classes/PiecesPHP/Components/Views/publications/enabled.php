<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
//Si el grupo se sirve (ComponentProvider::viewGroups()): con Publicaciones apagado, responde como un grupo que no existe.
return \Publications\PublicationsRoutes::ENABLE;
