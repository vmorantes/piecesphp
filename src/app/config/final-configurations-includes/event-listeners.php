<?php

use PiecesPHP\Core\BaseEventDispatcher;
use Terminal\Mappers\QueueJobMapper;

BaseEventDispatcher::defaultListen(BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, function () {
    //──── Migraciones ───────────────────────────────────────────────────────────────────────
    if (is_local()) {
        QueueJobMapper::migrate();
    }
});