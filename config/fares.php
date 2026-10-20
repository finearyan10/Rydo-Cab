<?php
// config/fares.php — Per-km rates and pricing structure for RYDO
// Includes Bike Taxi, Auto, Cab, and RYDO Parcel delivery

return [
    'Bike' => [
        'label'        => 'RYDO Bike Taxi',
        'icon'         => '🛵',
        'base_fare'    => 20,    // ₹ flat charge for first 2 km
        'per_km'       => 8,     // ₹ per km after base distance
        'base_km'      => 2,
        'tag'          => 'Fastest & Pocket Friendly',
        'capacity'     => '1 Passenger',
        'description'  => 'Micro-mobility bike ride with certified captain & helmet',
    ],
    'Auto' => [
        'label'        => 'RYDO Auto',
        'icon'         => '🛺',
        'base_fare'    => 30,
        'per_km'       => 12,
        'base_km'      => 2,
        'tag'          => 'Reliable Everyday Commute',
        'capacity'     => 'Up to 3 Passengers',
        'description'  => 'Spacious auto ride for local city travel',
    ],
    'Cab' => [
        'label'        => 'RYDO Prime Cab',
        'icon'         => '🚗',
        'base_fare'    => 50,
        'per_km'       => 16,
        'base_km'      => 2,
        'tag'          => 'AC & Maximum Comfort',
        'capacity'     => 'Up to 4 Passengers',
        'description'  => 'Air-conditioned sedan/hatchback with top-rated captains',
    ],
    'Parcel' => [
        'label'        => 'RYDO Parcel',
        'icon'         => '📦',
        'base_fare'    => 25,
        'per_km'       => 10,
        'base_km'      => 2,
        'tag'          => 'Intra-City Delivery (≤ 5kg)',
        'capacity'     => 'Max 5 kg parcel',
        'description'  => 'Quick door-to-door package delivery with receiver OTP verification',
    ],
];
