<?php

return [
    'session_statuses' => [
        'pendiente' => 1,
        'confirmada' => 2,
        'en_curso' => 3,
        'realizada' => 4,
        'cancelada' => 5,
        'no_presentada' => 6,
    ],

    'sale_statuses' => [
        'pendiente' => 1,
        'pagada' => 2,
        'parcialmente_reembolsada' => 3,
        'reembolsada' => 4,
        'cancelada' => 5,
    ],

    'stock_movement_types' => [
        'entrada' => 1,
        'salida_venta' => 2,
        'uso_sesion' => 3,
        'ajuste_manual' => 4,
        'devolucion' => 5,
    ],
];
