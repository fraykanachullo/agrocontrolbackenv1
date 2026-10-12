<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_usuario_y_devuelve_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'nombre_completo' => 'Agricultor Demo',
            'correo_electronico' => 'demo@agrocontrol.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'token_type'],
                'message',
            ])
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'demo@agrocontrol.com',
            'plan_suscripcion' => 'gratuito',
            'rol' => 'propietario',
        ]);

        $user = User::where('email', 'demo@agrocontrol.com')->first();
        $this->assertNotNull($user->tenant_id);
    }

    public function test_registro_valida_datos_obligatorios(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['nombre_completo', 'correo_electronico', 'password']]);
    }

    public function test_registro_requiere_password_minimo_8(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'nombre_completo' => 'Agricultor Demo',
            'correo_electronico' => 'demo@agrocontrol.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_registro_rechaza_correo_duplicado(): void
    {
        User::factory()->create(['email' => 'demo@agrocontrol.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'nombre_completo' => 'Agricultor Demo',
            'correo_electronico' => 'demo@agrocontrol.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['correo_electronico']]);
    }

    public function test_login_exitoso(): void
    {
        $user = User::factory()->create([
            'email' => 'login@agrocontrol.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'correo_electronico' => 'login@agrocontrol.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'token_type'],
            ])
            ->assertJsonPath('data.user.email', 'login@agrocontrol.com');

        $this->assertIsString($response->json('data.token'));
    }

    public function test_login_con_credenciales_incorrectas(): void
    {
        $user = User::factory()->create([
            'email' => 'login@agrocontrol.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'correo_electronico' => 'login@agrocontrol.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_me_devuelve_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $user->update(['tenant_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_sin_token_devuelve_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_logout_invalida_token(): void
    {
        $user = User::factory()->create();
        $user->update(['tenant_id' => $user->id]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_forgot_password_envia_enlace(): void
    {
        $user = User::factory()->create(['email' => 'reset@agrocontrol.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'correo_electronico' => 'reset@agrocontrol.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_forgot_password_valida_correo(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'correo_electronico' => 'no-existe@agrocontrol.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_funciona(): void
    {
        $user = User::factory()->create(['email' => 'reset@agrocontrol.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'correo_electronico' => 'reset@agrocontrol.com',
            'token' => $token,
            'password' => 'nueva123',
            'password_confirmation' => 'nueva123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($user->fresh()->password !== null);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nueva123', $user->fresh()->password));
    }

    public function test_reset_password_con_token_invalido(): void
    {
        $user = User::factory()->create(['email' => 'reset@agrocontrol.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'correo_electronico' => 'reset@agrocontrol.com',
            'token' => 'token-invalido',
            'password' => 'nueva123',
            'password_confirmation' => 'nueva123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}