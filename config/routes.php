<?php
// config/routes.php — Predefined routes and distances (in km)
// Features Kolhapur local hubs (matching KITCoEK project context) & city landmarks

return [
    // Kolhapur Local Landmark Routes
    'KIT College of Engg|Central Bus Stand'    => 8.5,
    'KIT College of Engg|Railway Station'      => 9.2,
    'KIT College of Engg|Mahalaxmi Temple'     => 11.0,
    'KIT College of Engg|Rankala Lake'         => 12.5,
    'KIT College of Engg|DYP City Mall'        => 7.8,
    'KIT College of Engg|Shivaji University'   => 4.2,
    'KIT College of Engg|CPR Hospital'         => 10.2,
    'KIT College of Engg|Shiroli MIDC'         => 15.0,

    'Central Bus Stand|Railway Station'       => 1.5,
    'Central Bus Stand|Mahalaxmi Temple'      => 4.0,
    'Central Bus Stand|Rankala Lake'          => 5.5,
    'Central Bus Stand|DYP City Mall'         => 2.8,
    'Central Bus Stand|Shivaji University'    => 6.2,
    'Central Bus Stand|CPR Hospital'          => 2.2,
    'Central Bus Stand|Shiroli MIDC'          => 7.5,

    'Railway Station|Mahalaxmi Temple'        => 3.5,
    'Railway Station|Rankala Lake'            => 5.2,
    'Railway Station|DYP City Mall'           => 3.2,
    'Railway Station|Shivaji University'      => 6.5,
    'Railway Station|CPR Hospital'            => 1.8,
    'Railway Station|Shiroli MIDC'            => 8.0,

    'Mahalaxmi Temple|Rankala Lake'           => 2.8,
    'Mahalaxmi Temple|DYP City Mall'          => 5.2,
    'Mahalaxmi Temple|Shivaji University'     => 7.5,
    'Mahalaxmi Temple|CPR Hospital'           => 3.8,

    'Rankala Lake|DYP City Mall'              => 6.5,
    'Rankala Lake|Shivaji University'         => 8.2,
    'Rankala Lake|CPR Hospital'               => 4.5,

    'DYP City Mall|Shivaji University'        => 4.8,
    'DYP City Mall|CPR Hospital'              => 3.0,
    'DYP City Mall|Shiroli MIDC'              => 6.2,

    'Shivaji University|CPR Hospital'         => 7.0,
    'CPR Hospital|Shiroli MIDC'               => 8.5,

    // General City & Airport connections
    'Central Bus Stand|Airport'               => 9.5,
    'Railway Station|Airport'                 => 10.2,
    'KIT College of Engg|Airport'             => 6.5,
    'DYP City Mall|Airport'                   => 8.8,
];
