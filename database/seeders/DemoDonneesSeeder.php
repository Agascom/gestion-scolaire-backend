<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\Encaissement;
use App\Models\Enseignant;
use App\Models\Frais;
use App\Models\Matiere;
use App\Models\MatiereClasse;
use App\Models\Niveau;
use App\Models\ParentEleve;
use App\Models\Salaire;
use App\Models\School;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Jeu de données de démonstration (ex-complexe scolaire gabonais).
 *
 * Reprend à l'identique les données MOCK qui étaient embarquées dans
 * "bureau-electron/src/renderer/src/data/mock.ts" : 8 élèves, 9 classes,
 * 6 enseignants, 8 matières, 6 encaissements, frais, salaires et
 * affectations matières. Idempotent : peut être relancé sans doublon,
 * sur SQLite locale comme sur MySQL de production.
 */
class DemoDonneesSeeder extends Seeder
{
    use WithoutModelEvents;

    protected const ROLES = [
        'superadmin',
        'admin_ecole',
        'secretaire',
        'caissier',
        'directeur',
        'enseignant',
        'parent',
    ];

    /**
     * Élèves : matricule, identité, classe, statut, tuteur.
     * Copie conforme de listeEleves du mock.
     */
    private const ELEVES = [
        ['matricule' => 'CSNDF-2025-0001', 'nom' => 'BOUKINDA', 'prenom' => 'Aïcha', 'sexe' => 'F', 'date_naissance' => '2011-04-12', 'commune_naissance' => 'Libreville', 'nationalite' => 'Gabonaise', 'classe' => '6eme A', 'statut' => 'inscrit', 'tuteur' => 'Boukinda Jean', 'telephone_tuteur' => '+241 06 12 34 56'],
        ['matricule' => 'CSNDF-2025-0002', 'nom' => 'MOUKAGNI', 'prenom' => 'Steeve', 'sexe' => 'M', 'date_naissance' => '2010-09-03', 'commune_naissance' => 'Franceville', 'nationalite' => 'Gabonaise', 'classe' => '6eme A', 'statut' => 'reinscrit', 'tuteur' => 'Moukagni Léon', 'telephone_tuteur' => '+241 07 98 76 54'],
        ['matricule' => 'CSNDF-2025-0003', 'nom' => 'OBAME', 'prenom' => 'Prisca', 'sexe' => 'F', 'date_naissance' => '2009-11-21', 'commune_naissance' => 'Port-Gentil', 'nationalite' => 'Gabonaise', 'classe' => '5eme B', 'statut' => 'inscrit', 'tuteur' => 'Obame Marcel', 'telephone_tuteur' => '+241 05 45 67 89'],
        ['matricule' => 'CSNDF-2025-0004', 'nom' => 'NDONG', 'prenom' => 'Patrick', 'sexe' => 'M', 'date_naissance' => '2008-07-15', 'commune_naissance' => 'Oyem', 'nationalite' => 'Gabonaise', 'classe' => '4eme A', 'statut' => 'transfere', 'tuteur' => 'Ndong Paul', 'telephone_tuteur' => '+241 06 22 33 44'],
        ['matricule' => 'CSNDF-2025-0005', 'nom' => 'MBOUMBOU', 'prenom' => 'Clarisse', 'sexe' => 'F', 'date_naissance' => '2007-02-27', 'commune_naissance' => 'Lambaréné', 'nationalite' => 'Gabonaise', 'classe' => '3eme A', 'statut' => 'reinscrit', 'tuteur' => 'Mboumbou Aimé', 'telephone_tuteur' => '+241 07 55 66 77'],
        ['matricule' => 'CSNDF-2025-0006', 'nom' => 'AYI', 'prenom' => 'Brice', 'sexe' => 'M', 'date_naissance' => '2006-05-18', 'commune_naissance' => 'Libreville', 'nationalite' => 'Gabonaise', 'classe' => '2nde A', 'statut' => 'inscrit', 'tuteur' => 'Ayi Félix', 'telephone_tuteur' => '+241 06 44 55 66'],
        ['matricule' => 'CSNDF-2025-0007', 'nom' => 'ESSONO', 'prenom' => 'Nathalie', 'sexe' => 'F', 'date_naissance' => '2005-08-09', 'commune_naissance' => 'Tchibanga', 'nationalite' => 'Gabonaise', 'classe' => '1ere D', 'statut' => 'inscrit', 'tuteur' => 'Essono Vincent', 'telephone_tuteur' => '+241 05 33 22 11'],
        ['matricule' => 'CSNDF-2025-0008', 'nom' => 'TSIBA', 'prenom' => 'Gérard', 'sexe' => 'M', 'date_naissance' => '2004-03-30', 'commune_naissance' => 'Libreville', 'nationalite' => 'Gabonaise', 'classe' => 'Terminale C', 'statut' => 'inscrit', 'tuteur' => 'Tsiba Gilbert', 'telephone_tuteur' => '+241 07 12 98 76'],
    ];

    /**
     * Classes : libellé, niveau, section (copie de listeClasses du mock).
     */
    private const CLASSES = [
        ['libelle' => '6eme A', 'niveau' => '6eme', 'section' => 'A'],
        ['libelle' => '5eme B', 'niveau' => '5eme', 'section' => 'B'],
        ['libelle' => '4eme A', 'niveau' => '4eme', 'section' => 'A'],
        ['libelle' => '3eme A', 'niveau' => '3eme', 'section' => 'A'],
        ['libelle' => '2nde A', 'niveau' => '2nde', 'section' => 'A'],
        ['libelle' => '1ere D', 'niveau' => '1ere', 'section' => 'D'],
        ['libelle' => 'Terminale C', 'niveau' => 'Terminale', 'section' => 'C'],
        ['libelle' => 'CP1', 'niveau' => 'CP1', 'section' => null],
        ['libelle' => 'Grande Section', 'niveau' => 'Grande Section', 'section' => null],
    ];

    /**
     * Enseignants : matricule, identité, spécialité, statut, salaire de base,
     * affectations "Classe — Matière" (copie de listeEnseignants du mock).
     */
    private const ENSEIGNANTS = [
        ['matricule' => 'ENS-001', 'nom' => 'BEKA', 'prenom' => 'Sylvie', 'telephone' => '+241 06 01 02 03', 'email' => 's.beka@csndf.ga', 'specialite' => 'Mathématiques', 'statut' => 'titulaire', 'salaire_base' => 350000, 'affectations' => ['6eme A — Maths', '5eme B — Maths']],
        ['matricule' => 'ENS-002', 'nom' => 'NZIENGUI', 'prenom' => 'Thierry', 'telephone' => '+241 07 04 05 06', 'email' => 't.nziengui@csndf.ga', 'specialite' => 'Français', 'statut' => 'titulaire', 'salaire_base' => 320000, 'affectations' => ['6eme A — Français', '4eme A — Français']],
        ['matricule' => 'ENS-003', 'nom' => 'MINTOSSA', 'prenom' => 'Paulette', 'telephone' => '+241 05 07 08 09', 'email' => 'p.mintossa@csndf.ga', 'specialite' => 'Physique-Chimie', 'statut' => 'vacataire', 'salaire_base' => 280000, 'affectations' => ['3eme A — Physique-Chimie', '2nde A — Physique-Chimie']],
        ['matricule' => 'ENS-004', 'nom' => 'KOMBILA', 'prenom' => 'Françoise', 'telephone' => '+241 06 10 11 12', 'email' => 'f.kombila@csndf.ga', 'specialite' => 'SVT', 'statut' => 'titulaire', 'salaire_base' => 310000, 'affectations' => ['1ere D — SVT']],
        ['matricule' => 'ENS-005', 'nom' => 'ABESSOLO', 'prenom' => 'Constant', 'telephone' => '+241 07 13 14 15', 'email' => 'c.abessolo@csndf.ga', 'specialite' => 'Anglais', 'statut' => 'vacataire', 'salaire_base' => 250000, 'affectations' => ['Terminale C — Anglais', '4eme A — Anglais']],
        ['matricule' => 'ENS-006', 'nom' => 'AYOUME', 'prenom' => 'Bertrand', 'telephone' => '+241 05 16 17 18', 'email' => 'b.ayoume@csndf.ga', 'specialite' => 'Histoire-Géographie', 'statut' => 'titulaire', 'salaire_base' => 340000, 'affectations' => ['5eme B — Histoire-Géo']],
    ];

    /**
     * Correspondance libellés courts du mock -> libellés de la table matieres.
     */
    private const MATIERES_MAP = [
        'Maths' => 'Mathématiques',
        'Français' => 'Français',
        'Physique-Chimie' => 'Physique-Chimie',
        'SVT' => 'Sciences de la Vie et de la Terre',
        'Histoire-Géo' => 'Histoire-Géographie',
        'Anglais' => 'Anglais',
    ];

    /**
     * Frais de scolarité (référentiel). Montants en FCFA.
     */
    private const FRAIS = [
        ['libelle' => "Frais d'inscription 2025-2026", 'montant' => 150000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 6e', 'montant' => 600000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 5e', 'montant' => 600000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 4e', 'montant' => 600000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 3e', 'montant' => 500000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 2nde', 'montant' => 750000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité 1ère', 'montant' => 700000, 'periodicite' => 'annee'],
        ['libelle' => 'Scolarité Terminale', 'montant' => 700000, 'periodicite' => 'annee'],
        ['libelle' => 'Frais de cantine', 'montant' => 90000, 'periodicite' => 'trimestre'],
        ['libelle' => 'Frais de transport', 'montant' => 60000, 'periodicite' => 'trimestre'],
    ];

    /**
     * Encaissements (copie de listeEncaissements du mock).
     */
    private const ENCAISSEMENTS = [
        ['matricule' => 'CSNDF-2025-0001', 'frais' => "Frais d'inscription 2025-2026", 'montant' => 150000, 'mode' => 'especes', 'reference' => 'RES-0001', 'statut' => 'paye', 'date' => '2025-09-02'],
        ['matricule' => 'CSNDF-2025-0002', 'frais' => 'Scolarité 6e', 'montant' => 200000, 'mode' => 'mobile_money', 'reference' => 'MM-88231', 'statut' => 'paye', 'date' => '2025-09-05'],
        ['matricule' => 'CSNDF-2025-0003', 'frais' => 'Frais de cantine', 'montant' => 90000, 'mode' => 'virement', 'reference' => 'VIR-45512', 'statut' => 'paye', 'date' => '2025-09-08'],
        ['matricule' => 'CSNDF-2025-0004', 'frais' => "Frais d'inscription 2025-2026", 'montant' => 100000, 'mode' => 'especes', 'reference' => 'RES-0002', 'statut' => 'partiel', 'date' => '2025-09-10'],
        ['matricule' => 'CSNDF-2025-0006', 'frais' => 'Scolarité 2nde', 'montant' => 250000, 'mode' => 'especes', 'reference' => 'RES-0003', 'statut' => 'paye', 'date' => '2025-09-12'],
        ['matricule' => 'CSNDF-2025-0007', 'frais' => 'Scolarité 1ère', 'montant' => 0, 'mode' => 'mobile_money', 'reference' => 'MM-90410', 'statut' => 'en_attente', 'date' => '2025-09-15'],
    ];

    public function run(): void
    {
        $this->commande('Démarrage du seed des données de démonstration…');

        $ecole = $this->assurerReferentiel();

        $this->assurerNiveaux();
        $this->assurerMatieres();

        $annee = AnneeAcademique::where('school_id', $ecole->id)->where('libelle', '2025-2026')->first();
        if (! $annee) {
            $this->command->warn('Aucune année académique 2025-2026 trouvée. Seed interrompu.');

            return;
        }

        $classes = $this->assurerClasses($ecole, $annee);
        $enseignants = $this->assurerEnseignants($ecole, $annee);
        $this->assurerElevesEtParents($ecole, $annee, $classes);
        $frais = $this->assurerFrais($ecole);
        $this->assurerEncaissements($ecole, $annee, $frais);
        $this->assurerSalaires($ecole, $annee, $enseignants);

        $this->commande(sprintf(
            'Terminé : %d classes, %d enseignants, %d élèves, %d frais, %d encaissements.',
            $classes->count(),
            $enseignants->count(),
            Eleve::where('school_id', $ecole->id)->count(),
            $frais->count(),
            Encaissement::where('school_id', $ecole->id)->count()
        ));
    }

    private function commande(string $message): void
    {
        $this->command?->info($message);
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
     * Référentiel minimal : rôles, superadmin, école, année, trimestres.
     * Idempotent (identique à DatabaseSeeder).
     */
    private function assurerReferentiel(): School
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

        return $ecole;
    }

    private function assurerNiveaux(): void
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

    private function assurerMatieres(): void
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
     * @return \Illuminate\Support\Collection<int, Classe>
     */
    private function assurerClasses(School $ecole, AnneeAcademique $annee)
    {
        $classes = collect();

        foreach (self::CLASSES as $donnee) {
            $niveau = Niveau::where('libelle', $donnee['niveau'])->first();
            if (! $niveau) {
                $this->command?->warn("Niveau introuvable : {$donnee['niveau']}");

                continue;
            }

            $classe = Classe::firstOrCreate(
                [
                    'school_id' => $ecole->id,
                    'annee_academique_id' => $annee->id,
                    'libelle' => $donnee['libelle'],
                ],
                [
                    'niveau_id' => $niveau->id,
                    'section' => $donnee['section'],
                ]
            );

            $classes->push($classe);
        }

        return $classes;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Enseignant>
     */
    private function assurerEnseignants(School $ecole, AnneeAcademique $annee)
    {
        $enseignants = collect();

        foreach (self::ENSEIGNANTS as $donnee) {
            $enseignant = Enseignant::firstOrCreate(
                ['school_id' => $ecole->id, 'nom' => $donnee['nom'], 'prenom' => $donnee['prenom']],
                [
                    'telephone' => $donnee['telephone'],
                    'email' => $donnee['email'],
                    'specialite' => $donnee['specialite'],
                    'statut' => $donnee['statut'],
                ]
            );

            foreach ($donnee['affectations'] as $affectation) {
                $this->affecterMatiereClasse($ecole, $annee, $enseignant, $affectation);
            }

            $enseignants->push($enseignant);
        }

        return $enseignants;
    }

    /**
     * Crée une ligne matiere_classe à partir d'une affectation "Classe — Matière".
     */
    private function affecterMatiereClasse(School $ecole, AnneeAcademique $annee, Enseignant $enseignant, string $affectation): void
    {
        [$libelleClasse, $libelleMatiere] = array_map('trim', explode('—', $affectation));
        $libelleMatiere = self::MATIERES_MAP[$libelleMatiere] ?? $libelleMatiere;

        $classe = Classe::where('school_id', $ecole->id)
            ->where('annee_academique_id', $annee->id)
            ->where('libelle', $libelleClasse)
            ->first();

        $matiere = Matiere::where('libelle', $libelleMatiere)->first();

        if (! $classe || ! $matiere) {
            $this->command?->warn("Affectation ignorée : {$affectation}");

            return;
        }

        MatiereClasse::firstOrCreate(
            [
                'classe_id' => $classe->id,
                'matiere_id' => $matiere->id,
            ],
            [
                'school_id' => $ecole->id,
                'enseignant_id' => $enseignant->id,
                'coefficient' => $matiere->coefficient_par_defaut,
            ]
        );
    }

    /**
     * @param \Illuminate\Support\Collection<int, Classe> $classes
     */
    private function assurerElevesEtParents(School $ecole, AnneeAcademique $annee, $classes): void
    {
        foreach (self::ELEVES as $donnee) {
            $eleve = Eleve::firstOrCreate(
                ['matricule' => $donnee['matricule']],
                [
                    'school_id' => $ecole->id,
                    'nom' => $donnee['nom'],
                    'prenom' => $donnee['prenom'],
                    'sexe' => $donnee['sexe'],
                    'date_naissance' => $donnee['date_naissance'],
                    'commune_naissance' => $donnee['commune_naissance'],
                    'nationalite' => $donnee['nationalite'],
                    'statut' => $donnee['statut'],
                ]
            );

            // Tuteur / parent principal.
            $morceaux = preg_split('/\s+/', trim($donnee['tuteur']));
            $nomTuteur = $morceaux[0] ?? $donnee['nom'];
            $prenomTuteur = $morceaux[1] ?? '';

            ParentEleve::firstOrCreate(
                ['eleve_id' => $eleve->id],
                [
                    'nom' => $nomTuteur,
                    'prenom' => $prenomTuteur,
                    'telephone' => $donnee['telephone_tuteur'],
                    'est_tuteur' => true,
                ]
            );

            // Inscription dans sa classe pour l'année en cours.
            $classe = $classes->firstWhere('libelle', $donnee['classe']);
            if ($classe) {
                \App\Models\TableClasse::firstOrCreate([
                    'school_id' => $ecole->id,
                    'classe_id' => $classe->id,
                    'eleve_id' => $eleve->id,
                    'annee_academique_id' => $annee->id,
                ]);
            } else {
                $this->command?->warn("Classe introuvable pour {$donnee['matricule']} : {$donnee['classe']}");
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Frais>
     */
    private function assurerFrais(School $ecole)
    {
        $frais = collect();

        foreach (self::FRAIS as $donnee) {
            $item = Frais::firstOrCreate(
                ['school_id' => $ecole->id, 'libelle' => $donnee['libelle']],
                [
                    'montant' => $donnee['montant'],
                    'periodicite' => $donnee['periodicite'],
                    'actif' => true,
                ]
            );
            $frais->push($item);
        }

        return $frais;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Frais> $frais
     */
    private function assurerEncaissements(School $ecole, AnneeAcademique $annee, $frais): void
    {
        foreach (self::ENCAISSEMENTS as $donnee) {
            $eleve = Eleve::where('matricule', $donnee['matricule'])->first();
            $fraisItem = $frais->firstWhere('libelle', $donnee['frais']);

            if (! $eleve || ! $fraisItem) {
                $this->command?->warn("Encaissement ignoré (élève ou frais introuvable) : {$donnee['reference']}");

                continue;
            }

            Encaissement::firstOrCreate(
                ['reference' => $donnee['reference']],
                [
                    'school_id' => $ecole->id,
                    'annee_academique_id' => $annee->id,
                    'eleve_id' => $eleve->id,
                    'frais_id' => $fraisItem->id,
                    'montant' => $donnee['montant'],
                    'mode' => $donnee['mode'],
                    'statut' => $donnee['statut'],
                    'date_encaissement' => $donnee['date'],
                    'numero_recu' => 'RECU-'.$donnee['date'].'-'.strtoupper($donnee['reference']),
                ]
            );
        }
    }

    /**
     * Salaires de démonstration : salaire de base de chaque enseignant
     * (le mock ne contenait pas de mois ; on reprend la valeur salaireBase).
     *
     * @param \Illuminate\Support\Collection<int, Enseignant> $enseignants
     */
    private function assurerSalaires(School $ecole, AnneeAcademique $annee, $enseignants): void
    {
        $mois = ['2025-09', '2025-10'];

        foreach ($enseignants as $enseignant) {
            $donnee = collect(self::ENSEIGNANTS)->firstWhere('nom', $enseignant->nom);

            foreach ($mois as $moisSalaire) {
                Salaire::firstOrCreate(
                    ['enseignant_id' => $enseignant->id, 'mois' => $moisSalaire],
                    [
                        'school_id' => $ecole->id,
                        'annee_academique_id' => $annee->id,
                        'salaire_base' => $donnee['salaire_base'],
                        'primes' => 0,
                        'avances' => 0,
                        'retenues' => 0,
                        'net_a_payer' => $donnee['salaire_base'],
                        'statut' => $moisSalaire === '2025-09' ? Salaire::STATUT_PAYE : Salaire::STATUT_EN_ATTENTE,
                        'date_paiement' => $moisSalaire === '2025-09' ? '2025-09-30' : null,
                    ]
                );
            }
        }
    }
}
