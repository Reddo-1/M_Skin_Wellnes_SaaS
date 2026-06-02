<?php

//datos fiscales del emisor (MSkinWellness) que aparecen en la factura de la suscripción enviada al centro
return [
    'vendor' => 'MSkinWellness',
    'vendor_vat' => 'NIF: 49762018T',
    'street' => 'Carrer Serrella 1',
    'location' => 'La Nucia, 03530 (Alicante)',
    'country' => 'España',
    'email' => env('MAIL_FROM_ADDRESS', 'hola@mskinwellness.com'),
];
