<?php

return [
    'title' => 'Erreur - {type}',
    'main_title' => "Une erreur c'est produite",
    'http_code' => 'Code HTTP : {code} - {label}',
    'show_details' => '🔍 Afficher les détails techniques',
    'hide_details' => '🔼 Masquer les détails techniques',
    'details' => [
        'location' => '📍 Localisation',
        'file' => 'Fichier :',
        'line' => 'Ligne :',
        'code_number' => 'Code erreur :',
        'trace' => "📚 Trace d'exécution",
    ],
    'production' => [
        'title' => "Oups ! Quelque chose c'est mal passé",
        'lines' => [
            "Une erreur inattendue c'est produite lors du traitement de votre requête.",
            'Nos équipes ont été notifiées et travaillent à résoudre le problème.',
            'Veuillez réessayer dans quelques instants.',
            'Si le problème persiste, veuillez contacter le support technique',
        ]
    ],
    'development' => '<strong>Mode développement</strong> - Détails complets affichés'
];
