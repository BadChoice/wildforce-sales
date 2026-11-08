<?php

return [
    'discovery' => [
        'provider' => env('GROWTH_DISCOVERY_PROVIDER', 'openai'),
        'model' => env('GROWTH_DISCOVERY_MODEL', 'gpt-6-luna'),
        'qualified_score' => 70,
        'timeout' => 90,
    ],

    'enrichment' => [
        'provider' => env('GROWTH_ENRICHMENT_PROVIDER', 'openai'),
        'model' => env('GROWTH_ENRICHMENT_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
    ],

    'outreach' => [
        'provider' => env('GROWTH_OUTREACH_PROVIDER', 'openai'),
        'model' => env('GROWTH_OUTREACH_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
        'value_propositions' => [
            'ai_personalized_routines' => [
                'segments' => ['gym', 'boutique_gym'],
                'ca' => 'Dona més valor als socis amb rutines personalitzades amb IA.',
                'es' => 'Da más valor a los socios con rutinas personalizadas con IA.',
            ],
            'scheduled_check_ins' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Fes un seguiment exhaustiu amb check-ins programats.',
                'es' => 'Haz un seguimiento exhaustivo con check-ins programados.',
            ],
            'premium_client_experience' => [
                'segments' => ['coach', 'boutique_gym'],
                'ca' => 'Ofereix una experiència de seguiment més personalitzada sense multiplicar la gestió.',
                'es' => 'Ofrece una experiencia de seguimiento más personalizada sin multiplicar la gestión.',
            ],
            'centralized_progress' => [
                'segments' => ['gym', 'boutique_gym'],
                'ca' => 'Centralitza el progrés dels clients perquè l’equip el pugui seguir amb claredat.',
                'es' => 'Centraliza el progreso de los clientes para que el equipo pueda seguirlo con claridad.',
            ],
            'ai_nutrition_plans' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Crea plans de nutrició personalitzats amb IA.',
                'es' => 'Crea planes de nutrición personalizados con IA.',
            ],
            'body_progress_photos' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Registra fotos de progrés corporal perquè el client vegi la seva evolució.',
                'es' => 'Registra fotos de progreso corporal para que el cliente vea su evolución.',
            ],
            'exercise_progress_analysis' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Analitza el progrés de cada exercici amb més claredat.',
                'es' => 'Analiza el progreso de cada ejercicio con más claridad.',
            ],
            'body_metrics_tracking' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Fes seguiment de mètriques corporals en un sol lloc.',
                'es' => 'Haz seguimiento de métricas corporales en un solo lugar.',
            ],
            'custom_exercise_library' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Adapta exercicis del catàleg o crea els teus amb vídeos i notes pròpies.',
                'es' => 'Adapta ejercicios del catálogo o crea los tuyos con vídeos y notas propias.',
            ],
            'room_management_and_bookings' => [
                'segments' => ['boutique_gym', 'gym'],
                'ca' => 'Gestiona sales i reserves des del mateix espai de treball.',
                'es' => 'Gestiona salas y reservas desde el mismo espacio de trabajo.',
            ],
            'automatic_client_payments' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Automatitza els cobraments recurrents dels clients.',
                'es' => 'Automatiza los cobros recurrentes de los clientes.',
            ],
            'training_plans_and_results' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Gestiona rutines, plans d’entrenament i resultats dels clients en un sol lloc.',
                'es' => 'Gestiona rutinas, planes de entrenamiento y resultados de los clientes en un solo lugar.',
            ],
            'client_mobile_app' => [
                'segments' => ['coach', 'boutique_gym', 'gym'],
                'ca' => 'Ofereix una app per a iOS i Android on els clients reben check-ins, entrenaments i nutrició.',
                'es' => 'Ofrece una app para iOS y Android donde los clientes reciben check-ins, entrenamientos y nutrición.',
            ],
        ],
    ],

    'reply' => [
        'provider' => env('GROWTH_REPLY_PROVIDER', 'openai'),
        'model' => env('GROWTH_REPLY_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
    ],
];
