<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Beneficiary;
use App\Models\District;
use App\Models\HealthCenter;
use App\Models\IvrCall;
use App\Models\Kit;
use App\Models\KitEvent;
use App\Models\Project;
use App\Models\Region;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. UTILISATEURS PRINCIPAUX
        |--------------------------------------------------------------------------
        */

        $direction = User::updateOrCreate(
            ['email' => 'direction@hope.test'],
            [
                'matricule' => 'DIR-001',
                'name' => 'Direction HOPE',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DIRECTION,
                'phone' => '+22370000001',
                'job_title' => 'Direction Générale',
                'preferred_language' => 'fr',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. RÉGIONS
        |--------------------------------------------------------------------------
        */

        $regionsData = [
            ['name' => 'Kayes', 'code' => 'ML-KY'],
            ['name' => 'Koulikoro', 'code' => 'ML-KL'],
            ['name' => 'Bamako', 'code' => 'ML-BK'],
            ['name' => 'Sikasso', 'code' => 'ML-SK'],
            ['name' => 'Ségou', 'code' => 'ML-SG'],
        ];

        $regions = [];

        foreach ($regionsData as $data) {
            $regions[$data['code']] = Region::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. DISTRICTS
        |--------------------------------------------------------------------------
        */

        $districtsData = [
            ['region' => 'ML-KY', 'name' => 'Kéniéba', 'code' => 'KY-KNB'],
            ['region' => 'ML-KY', 'name' => 'Kayes', 'code' => 'KY-KYS'],

            ['region' => 'ML-KL', 'name' => 'Kati', 'code' => 'KL-KTI'],
            ['region' => 'ML-KL', 'name' => 'Kangaba', 'code' => 'KL-KGB'],

            ['region' => 'ML-BK', 'name' => 'Commune I', 'code' => 'BK-C1'],
            ['region' => 'ML-BK', 'name' => 'Commune IV', 'code' => 'BK-C4'],

            ['region' => 'ML-SK', 'name' => 'Sikasso', 'code' => 'SK-SKS'],
            ['region' => 'ML-SK', 'name' => 'Koutiala', 'code' => 'SK-KTL'],

            ['region' => 'ML-SG', 'name' => 'Ségou', 'code' => 'SG-SGO'],
            ['region' => 'ML-SG', 'name' => 'San', 'code' => 'SG-SAN'],
        ];

        $districts = [];

        foreach ($districtsData as $data) {
            $districts[$data['code']] = District::updateOrCreate(
                [
                    'region_id' => $regions[$data['region']]->id,
                    'name' => $data['name'],
                ],
                [
                    'code' => $data['code'],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. CENTRES DE SANTÉ
        |--------------------------------------------------------------------------
        */

        $healthCentersData = [
            [
                'district' => 'KY-KNB',
                'name' => 'CSCOM Dabia',
                'code' => 'CSCOM-DABIA-01',
                'type' => 'CSCOM',
                'latitude' => 12.8395000,
                'longitude' => -11.9350000,
                'phone' => '+22370000101',
            ],
            [
                'district' => 'KY-KNB',
                'name' => 'CSCOM Faléa',
                'code' => 'CSCOM-FALEA-01',
                'type' => 'CSCOM',
                'latitude' => 12.9000000,
                'longitude' => -11.5000000,
                'phone' => '+22370000102',
            ],
            [
                'district' => 'KY-KYS',
                'name' => 'CSREF Kayes',
                'code' => 'CSREF-KAYES-01',
                'type' => 'CSREF',
                'latitude' => 14.4460000,
                'longitude' => -11.4440000,
                'phone' => '+22370000103',
            ],
            [
                'district' => 'KL-KTI',
                'name' => 'CSCOM Kati',
                'code' => 'CSCOM-KATI-01',
                'type' => 'CSCOM',
                'latitude' => 12.7440000,
                'longitude' => -8.0730000,
                'phone' => '+22370000104',
            ],
            [
                'district' => 'KL-KGB',
                'name' => 'CSCOM Kangaba',
                'code' => 'CSCOM-KANGABA-01',
                'type' => 'CSCOM',
                'latitude' => 11.9330000,
                'longitude' => -8.4170000,
                'phone' => '+22370000105',
            ],
            [
                'district' => 'BK-C1',
                'name' => 'CSREF Commune I',
                'code' => 'CSREF-BAMAKO-C1',
                'type' => 'CSREF',
                'latitude' => 12.6650000,
                'longitude' => -7.9800000,
                'phone' => '+22370000106',
            ],
            [
                'district' => 'BK-C4',
                'name' => 'CSCOM Lafiabougou',
                'code' => 'CSCOM-LAFIA-01',
                'type' => 'CSCOM',
                'latitude' => 12.6280000,
                'longitude' => -8.0200000,
                'phone' => '+22370000107',
            ],
            [
                'district' => 'SK-SKS',
                'name' => 'CSREF Sikasso',
                'code' => 'CSREF-SIKASSO-01',
                'type' => 'CSREF',
                'latitude' => 11.3170000,
                'longitude' => -5.6670000,
                'phone' => '+22370000108',
            ],
            [
                'district' => 'SK-KTL',
                'name' => 'CSCOM Koutiala',
                'code' => 'CSCOM-KOUTIALA-01',
                'type' => 'CSCOM',
                'latitude' => 12.3830000,
                'longitude' => -5.4670000,
                'phone' => '+22370000109',
            ],
            [
                'district' => 'SG-SGO',
                'name' => 'CSREF Ségou',
                'code' => 'CSREF-SEGOU-01',
                'type' => 'CSREF',
                'latitude' => 13.4300000,
                'longitude' => -6.2700000,
                'phone' => '+22370000110',
            ],
            [
                'district' => 'SG-SAN',
                'name' => 'CSCOM San',
                'code' => 'CSCOM-SAN-01',
                'type' => 'CSCOM',
                'latitude' => 13.3000000,
                'longitude' => -4.9000000,
                'phone' => '+22370000111',
            ],
        ];

        $healthCenters = [];

        foreach ($healthCentersData as $data) {
            $healthCenters[$data['code']] = HealthCenter::updateOrCreate(
                ['code' => $data['code']],
                [
                    'district_id' => $districts[$data['district']]->id,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. PROJETS
        |--------------------------------------------------------------------------
        */

        $projectDabia = Project::updateOrCreate(
            ['name' => 'Kit Néné – Kayes'],
            [
                'partner' => 'AMPDP',
                'description' => 'Programme de santé communautaire et de distribution des Kits Néné dans la région de Kayes.',
                'started_at' => now()->subMonths(10)->toDateString(),
                'ended_at' => null,
                'is_active' => true,
            ]
        );

        $projectSud = Project::updateOrCreate(
            ['name' => 'Santé Maternelle – Sud Mali'],
            [
                'partner' => 'HOPE Health and Care',
                'description' => 'Projet de suivi des femmes enceintes et de distribution de kits dans le sud du Mali.',
                'started_at' => now()->subMonths(7)->toDateString(),
                'ended_at' => null,
                'is_active' => true,
            ]
        );

        $projectBamako = Project::updateOrCreate(
            ['name' => 'Programme Pilote Bamako'],
            [
                'partner' => 'Partenaire Santé',
                'description' => 'Projet pilote de suivi numérique des distributions de Kits Néné à Bamako.',
                'started_at' => now()->subMonths(4)->toDateString(),
                'ended_at' => null,
                'is_active' => true,
            ]
        );

        $projectDabia->healthCenters()->syncWithoutDetaching([
            $healthCenters['CSCOM-DABIA-01']->id,
            $healthCenters['CSCOM-FALEA-01']->id,
            $healthCenters['CSREF-KAYES-01']->id,
        ]);

        $projectSud->healthCenters()->syncWithoutDetaching([
            $healthCenters['CSREF-SIKASSO-01']->id,
            $healthCenters['CSCOM-KOUTIALA-01']->id,
            $healthCenters['CSREF-SEGOU-01']->id,
            $healthCenters['CSCOM-SAN-01']->id,
            $healthCenters['CSCOM-KANGABA-01']->id,
        ]);

        $projectBamako->healthCenters()->syncWithoutDetaching([
            $healthCenters['CSREF-BAMAKO-C1']->id,
            $healthCenters['CSCOM-LAFIA-01']->id,
            $healthCenters['CSCOM-KATI-01']->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. COORDINATEURS
        |--------------------------------------------------------------------------
        */

        $coordinateurKayes = User::updateOrCreate(
            ['email' => 'coordinateur.kayes@hope.test'],
            [
                'matricule' => 'COORD-001',
                'name' => 'Moussa Traoré',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COORDINATEUR,
                'phone' => '+22370000201',
                'job_title' => 'Coordinateur de projet Kayes',
                'health_center_id' => $healthCenters['CSCOM-DABIA-01']->id,
                'project_id' => $projectDabia->id,
                'preferred_language' => 'fr',
                'is_active' => true,
            ]
        );

        $coordinateurSud = User::updateOrCreate(
            ['email' => 'coordinateur.sud@hope.test'],
            [
                'matricule' => 'COORD-002',
                'name' => 'Aminata Coulibaly',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COORDINATEUR,
                'phone' => '+22370000202',
                'job_title' => 'Coordinateur régional Sud',
                'health_center_id' => $healthCenters['CSREF-SIKASSO-01']->id,
                'project_id' => $projectSud->id,
                'preferred_language' => 'fr',
                'is_active' => true,
            ]
        );

        $coordinateurBamako = User::updateOrCreate(
            ['email' => 'coordinateur.bamako@hope.test'],
            [
                'matricule' => 'COORD-003',
                'name' => 'Fatoumata Diarra',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COORDINATEUR,
                'phone' => '+22370000203',
                'job_title' => 'Coordinateur Programme Bamako',
                'health_center_id' => $healthCenters['CSREF-BAMAKO-C1']->id,
                'project_id' => $projectBamako->id,
                'preferred_language' => 'fr',
                'is_active' => true,
            ]
        );

        $coordinateurKayes->projects()->syncWithoutDetaching([
            $projectDabia->id,
        ]);

        $coordinateurSud->projects()->syncWithoutDetaching([
            $projectSud->id,
        ]);

        $coordinateurBamako->projects()->syncWithoutDetaching([
            $projectBamako->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 7. AGENTS
        |--------------------------------------------------------------------------
        */

        $agents = [];

        $agentDefinitions = [
            [
                'matricule' => 'LOG-001',
                'name' => 'Ibrahim Koné',
                'email' => 'logistique.kayes@hope.test',
                'role' => User::ROLE_LOGISTIQUE,
                'center' => 'CSCOM-DABIA-01',
                'project' => $projectDabia,
            ],
            [
                'matricule' => 'AGENT-001',
                'name' => 'Fatou Traoré',
                'email' => 'agent.dabia@hope.test',
                'role' => User::ROLE_AGENT_SANTE,
                'center' => 'CSCOM-DABIA-01',
                'project' => $projectDabia,
            ],
            [
                'matricule' => 'AGENT-002',
                'name' => 'Aïssata Diallo',
                'email' => 'agent.falea@hope.test',
                'role' => User::ROLE_AGENT_SANTE,
                'center' => 'CSCOM-FALEA-01',
                'project' => $projectDabia,
            ],
            [
                'matricule' => 'AGENT-003',
                'name' => 'Mariam Coulibaly',
                'email' => 'agent.sikasso@hope.test',
                'role' => User::ROLE_AGENT_SANTE,
                'center' => 'CSREF-SIKASSO-01',
                'project' => $projectSud,
            ],
            [
                'matricule' => 'AGENT-004',
                'name' => 'Oumar Keïta',
                'email' => 'agent.koutiala@hope.test',
                'role' => User::ROLE_AGENT_SANTE,
                'center' => 'CSCOM-KOUTIALA-01',
                'project' => $projectSud,
            ],
            [
                'matricule' => 'AGENT-005',
                'name' => 'Bintou Sangaré',
                'email' => 'agent.bamako@hope.test',
                'role' => User::ROLE_AGENT_SANTE,
                'center' => 'CSREF-BAMAKO-C1',
                'project' => $projectBamako,
            ],
            [
                'matricule' => 'LOG-002',
                'name' => 'Cheick Doumbia',
                'email' => 'logistique.bamako@hope.test',
                'role' => User::ROLE_LOGISTIQUE,
                'center' => 'CSCOM-LAFIA-01',
                'project' => $projectBamako,
            ],
        ];

        foreach ($agentDefinitions as $index => $data) {
            $agents[$data['email']] = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'matricule' => $data['matricule'],
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'phone' => '+22371000' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'job_title' => $data['role'] === User::ROLE_LOGISTIQUE
                        ? 'Agent logistique'
                        : 'Agent de santé communautaire',
                    'health_center_id' => $healthCenters[$data['center']]->id,
                    'project_id' => $data['project']->id,
                    'preferred_language' => 'fr',
                    'is_active' => true,
                ]
            );

            $agents[$data['email']]->projects()->syncWithoutDetaching([
                $data['project']->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. BÉNÉFICIAIRES
        |--------------------------------------------------------------------------
        */

        $beneficiaries = [];

        $beneficiaryDefinitions = [
            ['Fatou', 'Traoré', '72000001', 'bambara', 'CSCOM-DABIA-01', 'agent.dabia@hope.test', 2],
            ['Aïssata', 'Coulibaly', '72000002', 'francais', 'CSCOM-DABIA-01', 'agent.dabia@hope.test', 3],
            ['Mariam', 'Diallo', '72000003', 'peulh', 'CSCOM-DABIA-01', 'agent.dabia@hope.test', 1],
            ['Hawa', 'Konaté', '72000004', 'bambara', 'CSCOM-FALEA-01', 'agent.falea@hope.test', 2],
            ['Fanta', 'Keïta', '72000005', 'soninke', 'CSCOM-FALEA-01', 'agent.falea@hope.test', 4],
            ['Kadidia', 'Touré', '72000006', 'francais', 'CSREF-KAYES-01', 'agent.dabia@hope.test', 1],
            ['Aminata', 'Sangaré', '72000007', 'bambara', 'CSREF-SIKASSO-01', 'agent.sikasso@hope.test', 2],
            ['Djeneba', 'Coulibaly', '72000008', 'francais', 'CSREF-SIKASSO-01', 'agent.sikasso@hope.test', 3],
            ['Kadiatou', 'Dembélé', '72000009', 'bambara', 'CSCOM-KOUTIALA-01', 'agent.koutiala@hope.test', 1],
            ['Nafissatou', 'Traoré', '72000010', 'peulh', 'CSCOM-KOUTIALA-01', 'agent.koutiala@hope.test', 2],
            ['Sitan', 'Camara', '72000011', 'bambara', 'CSREF-BAMAKO-C1', 'agent.bamako@hope.test', 2],
            ['Mariam', 'Koné', '72000012', 'francais', 'CSREF-BAMAKO-C1', 'agent.bamako@hope.test', 1],
            ['Fanta', 'Diakité', '72000013', 'bambara', 'CSCOM-LAFIA-01', 'agent.bamako@hope.test', 3],
            ['Rokia', 'Maïga', '72000014', 'francais', 'CSCOM-LAFIA-01', 'agent.bamako@hope.test', 2],
            ['Bintou', 'Sissoko', '72000015', 'soninke', 'CSREF-SEGOU-01', 'agent.sikasso@hope.test', 3],
            ['Awa', 'Doumbia', '72000016', 'bambara', 'CSCOM-SAN-01', 'agent.sikasso@hope.test', 2],
        ];

        foreach ($beneficiaryDefinitions as $index => $data) {
            [$firstName, $lastName, $phoneSuffix, $language, $centerCode, $agentEmail, $months] = $data;

            $phone = is_numeric($phoneSuffix)
                ? '+223' . $phoneSuffix
                : '+22372009999';

            $beneficiaries[] = Beneficiary::updateOrCreate(
                ['phone' => $phone],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'preferred_language' => $language,
                    'health_center_id' => $healthCenters[$centerCode]->id,
                    'registered_by' => $agents[$agentEmail]->id,
                    'expected_delivery_date' => now()->addMonths($months)->toDateString(),
                    'actual_delivery_date' => $index % 6 === 0
                        ? now()->subDays(rand(5, 40))->toDateString()
                        : null,
                    'delivery_location' => $index % 3 === 0
                        ? 'domicile'
                        : 'centre_de_sante',
                    'ivr_consent' => $index % 5 !== 0,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. KITS ET ÉVÉNEMENTS
        |--------------------------------------------------------------------------
        */

        $projectByCenter = [
            'CSCOM-DABIA-01' => $projectDabia,
            'CSCOM-FALEA-01' => $projectDabia,
            'CSREF-KAYES-01' => $projectDabia,

            'CSREF-SIKASSO-01' => $projectSud,
            'CSCOM-KOUTIALA-01' => $projectSud,
            'CSREF-SEGOU-01' => $projectSud,
            'CSCOM-SAN-01' => $projectSud,

            'CSREF-BAMAKO-C1' => $projectBamako,
            'CSCOM-LAFIA-01' => $projectBamako,
            'CSCOM-KATI-01' => $projectBamako,
        ];

        $beneficiariesByCenter = [];

        foreach ($beneficiaries as $beneficiary) {
            $beneficiariesByCenter[$beneficiary->health_center_id][] = $beneficiary;
        }

        $kitCounter = 1;
        $allKits = [];

        foreach ($projectByCenter as $centerCode => $project) {
            $center = $healthCenters[$centerCode];
            $centerBeneficiaries = $beneficiariesByCenter[$center->id] ?? [];

            $statuses = [
                'created',
                'created',
                'in_stock',
                'in_stock',
                'in_stock',
                'distributed',
                'distributed',
                'used',
                'used',
                'not_used',
            ];

            foreach ($statuses as $statusIndex => $status) {
                $beneficiary = null;

                if (
                    in_array($status, ['distributed', 'used', 'not_used'], true)
                    && count($centerBeneficiaries) > 0
                ) {
                    $beneficiary = $centerBeneficiaries[
                        $statusIndex % count($centerBeneficiaries)
                    ];
                }

                $receivedAt = null;
                $distributedAt = null;
                $usedAt = null;

                if ($status !== 'created') {
                    $receivedAt = now()->subDays(rand(15, 90));
                }

                if (in_array($status, ['distributed', 'used', 'not_used'], true)) {
                    $distributedAt = Carbon::parse($receivedAt)
                        ->addDays(rand(2, 20));
                }

                if ($status === 'used') {
                    $usedAt = Carbon::parse($distributedAt)
                        ->addDays(rand(1, 30));
                }

                if ($status === 'not_used') {
                    $usedAt = Carbon::parse($distributedAt)
                        ->addDays(rand(5, 45));
                }

                $qrCode = 'HOPE-KIT-' . str_pad(
                    (string) $kitCounter,
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                $kit = Kit::updateOrCreate(
                    ['qr_code' => $qrCode],
                    [
                        'batch_number' => 'BATCH-2026-' .
                            str_pad((string) (int) ceil($kitCounter / 20), 3, '0', STR_PAD_LEFT),

                        'project_id' => $project->id,
                        'status' => $status,

                        'current_health_center_id' => $status === 'created'
                            ? null
                            : $center->id,

                        'beneficiary_id' => $beneficiary?->id,

                        'received_at' => $receivedAt,
                        'distributed_at' => $distributedAt,
                        'used_at' => $usedAt,
                    ]
                );

                $allKits[] = $kit;

                $agent = collect($agents)->first(
                    fn (User $user) => $user->health_center_id === $center->id
                ) ?? $direction;

                if ($receivedAt) {
                    KitEvent::updateOrCreate(
                        [
                            'kit_id' => $kit->id,
                            'event_type' => 'received',
                        ],
                        [
                            'client_uuid' => (string) Str::uuid(),
                            'user_id' => $agent->id,
                            'health_center_id' => $center->id,
                            'beneficiary_id' => null,
                            'payload' => [
                                'source' => 'seed',
                                'description' => 'Réception du kit au centre de santé.',
                            ],
                            'occurred_at' => $receivedAt,
                            'synced_at' => $receivedAt,
                        ]
                    );
                }

                if ($distributedAt && $beneficiary) {
                    KitEvent::updateOrCreate(
                        [
                            'kit_id' => $kit->id,
                            'event_type' => 'distributed',
                        ],
                        [
                            'client_uuid' => (string) Str::uuid(),
                            'user_id' => $agent->id,
                            'health_center_id' => $center->id,
                            'beneficiary_id' => $beneficiary->id,
                            'payload' => [
                                'source' => 'seed',
                                'description' => 'Kit distribué à la bénéficiaire.',
                            ],
                            'occurred_at' => $distributedAt,
                            'synced_at' => $distributedAt,
                        ]
                    );
                }

                if ($status === 'used' && $usedAt && $beneficiary) {
                    KitEvent::updateOrCreate(
                        [
                            'kit_id' => $kit->id,
                            'event_type' => 'used',
                        ],
                        [
                            'client_uuid' => (string) Str::uuid(),
                            'user_id' => $agent->id,
                            'health_center_id' => $center->id,
                            'beneficiary_id' => $beneficiary->id,
                            'payload' => [
                                'source' => 'seed',
                                'description' => 'Kit confirmé utilisé.',
                                'delivery_location' => 'centre_de_sante',
                            ],
                            'occurred_at' => $usedAt,
                            'synced_at' => $usedAt,
                        ]
                    );
                }

                if ($status === 'not_used' && $usedAt && $beneficiary) {
                    KitEvent::updateOrCreate(
                        [
                            'kit_id' => $kit->id,
                            'event_type' => 'not_used',
                        ],
                        [
                            'client_uuid' => (string) Str::uuid(),
                            'user_id' => $agent->id,
                            'health_center_id' => $center->id,
                            'beneficiary_id' => $beneficiary->id,
                            'payload' => [
                                'source' => 'seed',
                                'description' => 'Kit confirmé non utilisé.',
                                'reason' => 'Accouchement hors centre de santé',
                            ],
                            'occurred_at' => $usedAt,
                            'synced_at' => $usedAt,
                        ]
                    );
                }

                $kitCounter++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 10. APPELS IVR
        |--------------------------------------------------------------------------
        */

        $ivrStatuses = [
            'pending',
            'sent',
            'answered',
            'no_answer',
            'failed',
            'cancelled',
        ];

        foreach ($beneficiaries as $index => $beneficiary) {
            foreach (['cpn_reminder', 'delivery_reminder'] as $typeIndex => $callType) {
                $status = $ivrStatuses[
                    ($index + $typeIndex) % count($ivrStatuses)
                ];

                $scheduledAt = match ($status) {
                    'pending' => now()->addDays(rand(1, 10)),
                    'cancelled' => now()->subDays(rand(1, 10)),
                    default => now()->subDays(rand(1, 30)),
                };

                IvrCall::updateOrCreate(
                    [
                        'beneficiary_id' => $beneficiary->id,
                        'call_type' => $callType,
                    ],
                    [
                        'scheduled_at' => $scheduledAt,
                        'attempt_count' => in_array(
                            $status,
                            ['answered', 'no_answer', 'failed', 'sent'],
                            true
                        ) ? rand(1, 3) : 0,

                        'status' => $status,

                        'last_attempt_at' => in_array(
                            $status,
                            ['answered', 'no_answer', 'failed', 'sent'],
                            true
                        )
                            ? Carbon::parse($scheduledAt)->addMinutes(rand(1, 120))
                            : null,
                    ]
                );
            }

            IvrCall::updateOrCreate(
                [
                    'beneficiary_id' => $beneficiary->id,
                    'call_type' => 'custom',
                ],
                [
                    'scheduled_at' => now()->addDays(rand(2, 20)),
                    'attempt_count' => 0,
                    'status' => 'pending',
                    'last_attempt_at' => null,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 11. ALERTES
        |--------------------------------------------------------------------------
        */

        $distributedKit = collect($allKits)
            ->first(fn (Kit $kit) => $kit->status === 'distributed');

        $inStockKit = collect($allKits)
            ->first(fn (Kit $kit) => $kit->status === 'in_stock');

        $usedKit = collect($allKits)
            ->first(fn (Kit $kit) => $kit->status === 'used');

        Alert::updateOrCreate(
            [
                'type' => 'low_stock',
                'health_center_id' => $healthCenters['CSCOM-DABIA-01']->id,
                'kit_id' => null,
                'status' => 'open',
            ],
            [
                'severity' => 'critical',
                'message' => 'Stock critique : moins de 5 kits disponibles au CSCOM Dabia.',
                'detected_at' => now()->subHours(3),
                'resolved_at' => null,
                'resolved_by' => null,
            ]
        );

        if ($distributedKit) {
            Alert::updateOrCreate(
                [
                    'type' => 'stale_distribution',
                    'health_center_id' => $distributedKit->current_health_center_id,
                    'kit_id' => $distributedKit->id,
                    'status' => 'open',
                ],
                [
                    'severity' => 'warning',
                    'message' => 'Kit distribué depuis plusieurs jours sans confirmation d’utilisation.',
                    'detected_at' => now()->subDays(7),
                    'resolved_at' => null,
                    'resolved_by' => null,
                ]
            );
        }

        if ($inStockKit) {
            Alert::updateOrCreate(
                [
                    'type' => 'low_stock',
                    'health_center_id' => $inStockKit->current_health_center_id,
                    'kit_id' => $inStockKit->id,
                    'status' => 'open',
                ],
                [
                    'severity' => 'warning',
                    'message' => 'Surveillance du niveau de stock recommandée.',
                    'detected_at' => now()->subDay(),
                    'resolved_at' => null,
                    'resolved_by' => null,
                ]
            );
        }

        if ($usedKit) {
            Alert::updateOrCreate(
                [
                    'type' => 'stale_distribution',
                    'health_center_id' => $usedKit->current_health_center_id,
                    'kit_id' => $usedKit->id,
                    'status' => 'resolved',
                ],
                [
                    'severity' => 'info',
                    'message' => 'Alerte historique résolue après confirmation de l’utilisation du kit.',
                    'detected_at' => now()->subDays(20),
                    'resolved_at' => now()->subDays(15),
                    'resolved_by' => $direction->id,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 12. RAPPORTS
        |--------------------------------------------------------------------------
        */

        $reports = [
            [
                'type' => 'distribution',
                'title' => 'Rapport de distribution mensuel',
                'filters' => [
                    'date_from' => now()->startOfMonth()->toDateString(),
                    'date_to' => now()->endOfMonth()->toDateString(),
                ],
                'row_count' => Kit::whereIn(
                    'status',
                    ['distributed', 'used', 'not_used']
                )->count(),
            ],
            [
                'type' => 'usage',
                'title' => 'Rapport d’utilisation des Kits Néné',
                'filters' => [
                    'project_id' => $projectDabia->id,
                ],
                'row_count' => Kit::where('status', 'used')->count(),
            ],
            [
                'type' => 'performance_par_centre',
                'title' => 'Performance des centres de santé',
                'filters' => [
                    'region_id' => $regions['ML-KY']->id,
                ],
                'row_count' => HealthCenter::count(),
            ],
        ];

        foreach ($reports as $index => $data) {
            Report::updateOrCreate(
                ['title' => $data['title']],
                [
                    'type' => $data['type'],
                    'filters' => $data['filters'],
                    'format' => 'csv',
                    'file_path' => null,
                    'row_count' => $data['row_count'],
                    'generated_by' => $index % 2 === 0
                        ? $direction->id
                        : $coordinateurKayes->id,
                ]
            );
        }

        $this->command?->info('');
        $this->command?->info('==============================================');
        $this->command?->info('HOPE / KIT NÉNÉ - DONNÉES DE TEST CRÉÉES');
        $this->command?->info('==============================================');

        $this->command?->info('Régions : ' . Region::count());
        $this->command?->info('Districts : ' . District::count());
        $this->command?->info('Centres de santé : ' . HealthCenter::count());
        $this->command?->info('Projets : ' . Project::count());
        $this->command?->info('Utilisateurs : ' . User::count());
        $this->command?->info('Bénéficiaires : ' . Beneficiary::count());
        $this->command?->info('Kits : ' . Kit::count());
        $this->command?->info('Événements : ' . KitEvent::count());
        $this->command?->info('Appels IVR : ' . IvrCall::count());
        $this->command?->info('Alertes : ' . Alert::count());
        $this->command?->info('Rapports : ' . Report::count());

        $this->command?->info('');
        $this->command?->info('COMPTES DE TEST');
        $this->command?->info('Direction : direction@hope.test / password');
        $this->command?->info('Coordinateur Kayes : coordinateur.kayes@hope.test / password');
        $this->command?->info('Coordinateur Sud : coordinateur.sud@hope.test / password');
        $this->command?->info('Coordinateur Bamako : coordinateur.bamako@hope.test / password');
        $this->command?->info('Agent santé : agent.dabia@hope.test / password');
        $this->command?->info('Agent logistique : logistique.kayes@hope.test / password');

        $this->command?->info('==============================================');
    }
}

