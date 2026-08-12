<?php

namespace Tests\Feature;

use App\Models\Enseignant;
use App\Models\ParentEleve;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Parcours complet du portail parent : création de l'élève et du compte
     * parent, puis consultation des élèves, notes, frais et notifications.
     */
    public function test_portail_parent(): void
    {
        $schoolId = School::where('numero_agrement', 'AG-2025-001')->firstOrFail()->id;

        // 1. Connexion superadmin
        $adminToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@complexe.ga',
            'password' => 'password',
        ])->assertStatus(200)->json('data.token');

        // 2. Création de l'élève avec sa fiche parent
        $eleve = $this->withToken($adminToken)->postJson('/api/v1/eleves', [
            'school_id' => $schoolId,
            'nom' => 'Mba',
            'prenom' => 'Junior',
            'sexe' => 'M',
            'date_naissance' => '2015-01-10',
            'parent' => [
                'nom' => 'Mba',
                'prenom' => 'Pierre',
                'telephone' => '+241 06 00 00 00',
                'email' => 'pierre.mba@test.ga',
            ],
        ])->assertStatus(201)->json('data');

        $this->assertNotEmpty($eleve['parent']['id']);

        // 3. Compte utilisateur parent (rôle parent)
        $user = $this->withToken($adminToken)->postJson('/api/v1/utilisateurs', [
            'school_id' => $schoolId,
            'name' => 'Pierre Mba',
            'email' => 'pierre.mba@test.ga',
            'password' => 'secret123',
            'roles' => ['parent'],
        ])->assertStatus(201)->json('data');

        // 4. Liaison de la fiche parent au compte
        $this->assertEquals(
            1,
            ParentEleve::whereKey($eleve['parent']['id'])->update(['user_id' => $user['id']])
        );

        // 5. Connexion en tant que parent : le contexte du rôle renvoie l'élève
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'pierre.mba@test.ga',
            'password' => 'secret123',
        ])->assertStatus(200)->json('data');

        $this->assertSame($user['id'], $login['user']['id']);
        $this->assertSame($eleve['id'], $login['user']['parent'][0]['id']);

        $parentToken = $login['token'];

        // Réinitialise le cache du garde Sanctum : chaque requête HTTP réelle
        // repart d'un conteneur frais, ce que les tests (conteneur partagé) ne font pas.
        $this->app['auth']->forgetGuards();

        // 6. Liste des élèves du parent
        $this->withToken($parentToken)
            ->getJson('/api/v1/parent/eleves')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [['id' => $eleve['id'], 'nom' => 'Mba', 'prenom' => 'Junior']],
            ]);

        // 7. Notes de l'élève (vide mais accessible)
        $this->withToken($parentToken)
            ->getJson("/api/v1/parent/eleves/{$eleve['id']}/notes")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);

        // 8. Bulletins de l'élève
        $this->withToken($parentToken)
            ->getJson("/api/v1/parent/eleves/{$eleve['id']}/bulletins")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);

        // 9. Situation financière de l'élève
        $this->withToken($parentToken)
            ->getJson("/api/v1/parent/eleves/{$eleve['id']}/frais")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_frais', 'total_paye', 'reste', 'encaissements'],
            ]);

        // 10. Notifications du parent
        $this->withToken($parentToken)
            ->getJson('/api/v1/parent/notifications')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);

        // 11. Un élève d'un autre parent est interdit (403)
        $autre = $this->withToken($adminToken)->postJson('/api/v1/eleves', [
            'school_id' => $schoolId,
            'nom' => 'Autre',
            'prenom' => 'Eleve',
            'sexe' => 'F',
            'date_naissance' => '2016-02-20',
            'parent' => [
                'nom' => 'Autre',
                'prenom' => 'Parent',
                'telephone' => '+241 07 00 00 00',
            ],
        ])->assertStatus(201)->json('data');

        $this->withToken($parentToken)
            ->getJson("/api/v1/parent/eleves/{$autre['id']}/notes")
            ->assertStatus(403);
    }

    /**
     * Le contexte enseignant est présent sur la connexion du profil.
     */
    public function test_connexion_enseignant_renvoie_le_profil(): void
    {
        $schoolId = School::where('numero_agrement', 'AG-2025-001')->firstOrFail()->id;

        $adminToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@complexe.ga',
            'password' => 'password',
        ])->json('data.token');

        $user = $this->withToken($adminToken)->postJson('/api/v1/utilisateurs', [
            'school_id' => $schoolId,
            'name' => 'Prof. Obiang',
            'email' => 'obiang@test.ga',
            'password' => 'secret123',
            'roles' => ['enseignant'],
        ])->assertStatus(201)->json('data');

        Enseignant::create([
            'school_id' => $schoolId,
            'user_id' => $user['id'],
            'nom' => 'Obiang',
            'prenom' => 'Albert',
            'specialite' => 'Mathématiques',
            'email' => 'obiang@test.ga',
            'telephone' => '+241 08 00 00 00',
        ]);

        $resp = $this->postJson('/api/v1/auth/login', [
            'email' => 'obiang@test.ga',
            'password' => 'secret123',
        ])->assertStatus(200);

        $resp->assertJsonPath('data.user.profil_enseignant.nom', 'Obiang')
            ->assertJsonPath('data.user.roles', ['enseignant']);
    }
}
