<?php

return [

    // Fournisseur d'appels utilisé par IvrGateway (voir
    // app/Services/Ivr/) — "log" par défaut pour le développement, sans
    // dépendance externe. À changer pour "twilio" ou "africastalking"
    // une fois le vrai opérateur choisi.
    'driver' => env('IVR_DRIVER', 'log'),

    // Nombre de jours avant la Date Prévue d'Accouchement (DPA) auxquels
    // un rappel de consultation prénatale (CPN) est programmé.
    // ⚠️ Calendrier indicatif — À VALIDER avec l'équipe médicale de HOPE
    // (cahier des charges, section IVR). Basé sur un accouchement à terme
    // (40 semaines), en l'absence de date des dernières règles précise.
    'cpn_reminder_days_before_dpa' => [210, 150, 90, 45, 14],

    // Rappels rapprochés de la date d'accouchement elle-même
    'delivery_reminder_days_before_dpa' => [7, 2],

    // Nombre de tentatives d'appel avant d'abandonner (no_answer répété)
    'max_attempts' => 3,

    // Délai (en minutes) avant de retenter un appel resté sans réponse
    'retry_delay_minutes' => 120,

    // Messages vocaux par langue — en pratique, ce champ contiendra soit
    // le texte à envoyer à un moteur de synthèse vocale, soit un
    // identifiant de fichier audio pré-enregistré (ex: chemin S3) selon
    // le fournisseur retenu.
    // ⚠️ IMPORTANT : les traductions bambara / peulh / soninké ci-dessous
    // sont des ébauches générées automatiquement, PAS des traductions
    // validées. Elles doivent impérativement être relues et corrigées par
    // des locuteurs natifs (ou un traducteur professionnel) avant tout
    // usage réel — une consigne médicale mal traduite peut être dangereuse.
    // Ne jamais déployer ces messages en production sans validation.
    'messages' => [
        'cpn_reminder' => [
            'bambara' => "I ni sɔgɔma. Hope Health and Care don. A ka kan ka taa CPN kɔnɔ. Taa aw ka kɛnɛya so la ɲinan lɔgɔkun kɔnɔ. Ni joli bɔra, farigan kɔrɔ walima dimi bɔra, taa aw ka kɛnɛya so la joona.",
            'peulh' => "Jam waalaa. Ko Hope Health and Care. Waktu maa consultation prénatale arii. Njahee suudu safeteende ndee e nder yontere ndee. So maa yiitii ƴiiƴam, wella asamaan, wella metteende sattugol, njahee saraa suudu safeteende ndee.",
            'soninke' => "I sooninke. Hope Health and Care le. A yankandiyaanu ka taxa CPN gundo. Taxa i ya kεεnεya-so ra saraxu wa. Ni ji bo, kεεna-koroo maana dungu bo, taxa i ya kεεnεya-so ra joona.",
            'francais' => "Bonjour. C'est Hope Health and Care. Il est temps de faire votre consultation prénatale. Rendez-vous dans votre centre de santé cette semaine. Si vous présentez des saignements, une forte fièvre ou des douleurs importantes, rendez-vous immédiatement au centre de santé le plus proche. Merci.",
        ],
        'delivery_reminder' => [
            'bambara' => "I ni sɔgɔma. Hope Health and Care don. Aw denw wolo waati bɛnnen don. Aw ka Kit Néné labɛn. Ni dimi bɔra, taa aw ka kɛnɛya so la joona.",
            'peulh' => "Jam waalaa. Ko Hope Health and Care. Yontere yiwugol maa ɓadike. Heblo Kit Néné maa. So metteende arii, njahee suudu safeteende ndee saraa.",
            'soninke' => "I sooninke. Hope Health and Care le. I den woloo waxatu gaanu. I ya Kit Néné labεn. Ni dungu bo, taxa kεεnεya-so ra joona.",
            'francais' => "Bonjour. C'est Hope Health and Care. La date prévue de votre accouchement approche. Préparez votre Kit Néné. Si vous ressentez des douleurs, rendez-vous immédiatement au centre de santé.",
        ],
    ],

];
