<?php

return [
    'session_statuses' => [
        'confirmada' => 1,
        'en_curso' => 2,
        'realizada' => 3,
        'cancelada' => 4,
        'no_presentada' => 5,
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
