<?php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

//La pieza los pinta: secundario (ui icon button) y destructivo (ui negative basic icon button), con su texto en aria-label y
//en el tooltip, y un espacio entre botones.
echo RowActions::cell(
    RowActions::action(__($langGroup, 'Editar'), '#', 'edit'),
    [RowActions::delete(__($langGroup, 'Eliminar'), '#', __($langGroup, '¿Seguro de eliminar el elemento?'))]
);
