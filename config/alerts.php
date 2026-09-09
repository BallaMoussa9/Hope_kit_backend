<?php

return [

    // En dessous de ce nombre de kits "en stock", une alerte "stock
    // faible" est levée pour le centre concerné.
    'low_stock_threshold' => env('ALERT_LOW_STOCK_THRESHOLD', 5),

    // Nombre de jours après lesquels un kit "distribué" sans confirmation
    // d'utilisation déclenche une alerte (accouchement anormalement long
    // à confirmer, kit potentiellement perdu, etc.)
    'stale_distribution_days' => env('ALERT_STALE_DISTRIBUTION_DAYS', 60),

];
