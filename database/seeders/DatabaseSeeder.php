<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Cycle;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\School;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Seed principal : rôles, utilisateurs de démonstration et référentiel
 * initial pour l'école "Complexe Scolaire La Réussite" (année 2025-2026).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Rôles de l'application (Spatie Permission).
     */
    protected const ROLES = [
        'superadmin',
        'admin_ecole',
        'secretaire',
        'caissier',
        'directeur',
        'enseignant',
        'parent',
    ];

    public function run(): void
    {
        $this->creerRolesEtSuperadmin();
        $this->creerEcoleDemo();
        $this->creerAnneesEtTrimestres();
        $this->creerCyclesEtNiveaux();
        $this->creerMatieres();
        $this->call(DemoDonneesSeeder::class);
    }

    /**
     * Création des rôles et du compte superadmin.
     */
    private function creerRolesEtSuperadmin(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $superadmin = User::firstOrCreate(
            ['email' => 'admin@complexe.ga'],
            [
                'name' => 'Super Administrateur',
                'password' => $this->motDePasseSeed(),
                'school_id' => null,
            ]
        );

        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }
    }

    /**
     * École de démonstration + administrateur d'école.
     */
    private function creerEcoleDemo(): void
    {
        $ecole = School::firstOrCreate(
            ['numero_agrement' => 'AG-2025-001'],
            [
                'nom' => 'Complexe Scolaire La Réussite',
                'sigle' => 'CSR-LBR',
                'adresse' => 'Boulevard Triomphal, Libreville, Gabon',
                'telephone' => '+241 00 00 00 00',
                'email' => 'contact@reussite.ga',
                'numero_agrement' => 'AG-2025-001',
                'statut' => true,
            ]
        );

        $direction = User::firstOrCreate(
            ['email' => 'direction@complexe.ga'],
            [
                'name' => 'Administratrice du Complexe',
                'password' => $this->motDePasseSeed(),
                'school_id' => $ecole->id,
            ]
        );

        if (! $direction->hasRole('admin_ecole')) {
            $direction->assignRole('admin_ecole');
        }
    }

    /**
     * Année académique 2025-2026 avec ses trois trimestres.
     */
    private function creerAnneesEtTrimestres(): void
    {
        $ecole = School::where('numero_agrement', 'AG-2025-001')->first();
        if (! $ecole) {
            return;
        }

        $annee = AnneeAcademique::firstOrCreate(
            ['school_id' => $ecole->id, 'libelle' => '2025-2026'],
            [
                'date_debut' => '2025-09-22',
                'date_fin' => '2026-06-26',
                'trimestre_en_cours' => 1,
                'cloturee' => false,
                'archivee' => false,
            ]
        );

        $trimestres = [
            ['numero' => 1, 'libelle' => 'Trimestre 1', 'date_debut' => '2025-09-22', 'date_fin' => '2025-12-19'],
            ['numero' => 2, 'libelle' => 'Trimestre 2', 'date_debut' => '2026-01-05', 'date_fin' => '2026-04-03'],
            ['numero' => 3, 'libelle' => 'Trimestre 3', 'date_debut' => '2026-04-20', 'date_fin' => '2026-06-26'],
        ];

        foreach ($trimestres as $trimestre) {
            Trimestre::firstOrCreate(
                ['annee_academique_id' => $annee->id, 'numero' => $trimestre['numero']],
                $trimestre
            );
        }

        // Mémorisation de l'année ainsi créée pour les classes de démonstration.
        $this->anneeDemo = $annee;
    }

    /**
     * Cycles et niveaux du référentiel gabonais.
     */
    private function creerCyclesEtNiveaux(): void
    {
        $structure = [
            'Maternelle' => ['Petite Section', 'Moyenne Section', 'Grande Section'],
            'Primaire' => ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'],
            'Collège' => ['6eme', '5eme', '4eme', '3eme'],
            'Lycée' => ['2nde', '1ere', 'Terminale'],
        ];

        $ordreCycle = 0;
        foreach ($structure as $libelleCycle => $niveaux) {
            $ordreCycle++;
            $cycle = Cycle::firstOrCreate(
                ['libelle' => $libelleCycle],
                ['ordre' => $ordreCycle]
            );

            foreach ($niveaux as $index => $libelleNiveau) {
                Niveau::firstOrCreate(
                    ['cycle_id' => $cycle->id, 'libelle' => $libelleNiveau],
                    ['ordre' => $index + 1]
                );
            }
        }
    }

    /**
     * Matières du référentiel avec coefficients par défaut.
     */
    private function creerMatieres(): void
    {
        $matieres = [
            ['libelle' => 'Mathématiques', 'abreviation' => 'MATH', 'coefficient_par_defaut' => 4],
            ['libelle' => 'Français', 'abreviation' => 'FR', 'coefficient_par_defaut' => 3],
            ['libelle' => 'Anglais', 'abreviation' => 'ANG', 'coefficient_par_defaut' => 3],
            ['libelle' => 'Histoire-Géographie', 'abreviation' => 'HG', 'coefficient_par_defaut' => 2],
            ['libelle' => 'Sciences de la Vie et de la Terre', 'abreviation' => 'SVT', 'coefficient_par_defaut' => 2],
            ['libelle' => 'Physique-Chimie', 'abreviation' => 'PC', 'coefficient_par_defaut' => 3],
            ['libelle' => 'Education Physique et Sportive', 'abreviation' => 'EPS', 'coefficient_par_defaut' => 1],
            ['libelle' => 'Informatique', 'abreviation' => 'INFO', 'coefficient_par_defaut' => 2],
            ['libelle' => 'Education Civique', 'abreviation' => 'EC', 'coefficient_par_defaut' => 1],
        ];

        foreach ($matieres as $matiere) {
            Matiere::firstOrCreate(
                ['libelle' => $matiere['libelle']],
                [
                    'abreviation' => $matiere['abreviation'],
                    'coefficient_par_defaut' => $matiere['coefficient_par_defaut'],
                ]
            );
        }
    }

    /**
     * Mot de passe des comptes de démonstration.
     *
     * En local / test : valeur de démo commune. En production : mot de passe
     * aléatoire généré et affiché en console, à changer immédiatement.
     */
    private function motDePasseSeed(): string
    {
        if (app()->environment('production')) {
            $motDePasse = Str::password(16);
            $this->command?->warn("Mot de passe de démo généré (production) : {$motDePasse}");
            return $motDePasse;
        }

        return 'Demo1234!';
    }

    /**
     * Année de démonstration conservée entre les étapes du seed.
     *
     * @var AnneeAcademique|null
     */
    protected $anneeDemo;
}