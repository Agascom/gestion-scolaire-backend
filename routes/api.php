<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\ArchivageController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BulletinController;
use App\Http\Controllers\Api\EcoleController;
use App\Http\Controllers\Api\EleveController;
use App\Http\Controllers\Api\EmploiDuTempsController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\MaterielController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'API v1
|--------------------------------------------------------------------------
|
| Toutes les routes sont protégées par auth:sanctum, excepté la connexion.
| Chaque requête porte automatiquement l'isolation multi-écoles (TenantScope).
|
*/

Route::prefix('v1')->group(function () {

    // --- Authentification (publique) ---
    Route::post('/auth/login', [AuthController::class, 'login']);

    // --- Routes authentifiées ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Référentiel & multi-écoles
        Route::get('/ecoles', [EcoleController::class, 'index']);
        Route::post('/ecoles', [EcoleController::class, 'store']);
        Route::get('/ecoles/{id}', [EcoleController::class, 'show']);
        Route::put('/ecoles/{id}', [EcoleController::class, 'update']);
        Route::get('/cycles', [ReferenceController::class, 'cycles']);
        Route::get('/niveaux', [ReferenceController::class, 'niveaux']);
        Route::get('/matieres', [ReferenceController::class, 'matieres']);
        Route::get('/salles', [ReferenceController::class, 'salles']);
        Route::post('/salles', [ReferenceController::class, 'creerSalle']);
        Route::get('/classes', [BulletinController::class, 'classes']);

        // Module Élèves
        Route::get('/eleves', [EleveController::class, 'index']);
        Route::post('/eleves', [EleveController::class, 'store']);
        Route::get('/eleves/{id}', [EleveController::class, 'show']);
        Route::put('/eleves/{id}', [EleveController::class, 'update']);
        Route::post('/eleves/{id}/documents', [EleveController::class, 'ajouterDocument']);
        Route::post('/eleves/{id}/inscription', [EleveController::class, 'inscrire']);
        Route::post('/eleves/{id}/transfert', [EleveController::class, 'transferer']);
        Route::get('/eleves/{id}/notes', [NoteController::class, 'notesEleve']);
        Route::get('/eleves/{id}/moyennes', [NoteController::class, 'moyennesEleve']);
        Route::get('/eleves/{id}/passages', [ArchivageController::class, 'passagesEleve']);

        // Module Enseignants
        Route::get('/enseignants', [EleveController::class, 'enseignants']);
        Route::get('/repertoire', [EleveController::class, 'repertoire']);

        // Module Notes & évaluations
        Route::get('/evaluations', [NoteController::class, 'index']);
        Route::post('/evaluations', [NoteController::class, 'creerEvaluation']);
        Route::get('/evaluations/{evaluation}', [NoteController::class, 'show']);
        Route::post('/evaluations/{evaluation}/notes', [NoteController::class, 'saisirNotes']);
        Route::post('/evaluations/{evaluation}/publier', [NoteController::class, 'publier']);
        Route::post('/evaluations/{evaluation}/deverrouiller', [NoteController::class, 'deverrouiller']);

        // Module Absences
        Route::get('/absences', [AbsenceController::class, 'index']);
        Route::post('/absences', [AbsenceController::class, 'store']);

        // Module Trimestres & Bulletins
        Route::get('/annees-academiques', [BulletinController::class, 'annees']);
        Route::post('/bulletins/generer-classe', [BulletinController::class, 'genererClasse']);
        Route::post('/bulletins/{bulletin}/publier', [BulletinController::class, 'publier']);
        Route::post('/trimestres/{trimestre}/cloture', [BulletinController::class, 'cloturerTrimestre']);

        // Module Emploi du temps
        Route::get('/emplois-du-temps', [EmploiDuTempsController::class, 'consulter']);
        Route::post('/emplois-du-temps', [EmploiDuTempsController::class, 'creerCreneau']);
        Route::delete('/emplois-du-temps/{id}', [EmploiDuTempsController::class, 'supprimer']);

        // Module Finances
        Route::get('/frais', [FinanceController::class, 'fraisIndex']);
        Route::post('/frais', [FinanceController::class, 'fraisStore']);
        Route::get('/encaissements', [FinanceController::class, 'encaissementsIndex']);
        Route::post('/encaissements', [FinanceController::class, 'encaisser']);
        Route::get('/restes-a-payer', [FinanceController::class, 'restesAPayer']);
        Route::post('/depenses', [FinanceController::class, 'depenseStore']);
        Route::post('/salaires', [FinanceController::class, 'salaireStore']);
        Route::post('/produits', [FinanceController::class, 'produitStore']);
        Route::post('/mouvements-stock', [FinanceController::class, 'stockStore']);
        Route::get('/journal-caisse', [FinanceController::class, 'journalCaisse']);
        Route::get('/situation-financiere', [FinanceController::class, 'situation']);

        // Module Matériel
        Route::get('/materiel', [MaterielController::class, 'index']);
        Route::post('/materiel', [MaterielController::class, 'store']);
        Route::put('/materiel/{id}/etat', [MaterielController::class, 'miseAJourEtat']);
        Route::get('/materiel/{materiel}/amortissement', [MaterielController::class, 'valeurResiduelle']);

        // Module Utilisateurs
        Route::get('/utilisateurs', [UtilisateurController::class, 'index']);
        Route::post('/utilisateurs', [UtilisateurController::class, 'store']);
        Route::post('/utilisateurs/{user}/statut', [UtilisateurController::class, 'activerDesactiver']);
        Route::post('/utilisateurs/{user}/mot-de-passe', [UtilisateurController::class, 'reinitialiserMotDePasse']);
        Route::delete('/utilisateurs/{user}', [UtilisateurController::class, 'destroy']);

        // Module Archivage & clôture d'année
        Route::post('/annees/cloturer', [ArchivageController::class, 'cloturerAnnee']);
        Route::get('/archives', [ArchivageController::class, 'listeAnnexesArchives']);
        Route::get('/archives/{annee}', [ArchivageController::class, 'consulterArchive']);
    });
});