<?php

namespace Tests\Feature;

use App\Models\Payment_gateway;
use App\Models\PaymentHistoryEntry;
use App\Models\User;
use App\Models\Users;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Tests\TestCase;

class EloquentModelAuditTest extends TestCase
{
    /**
     * @return list<class-string<Model>>
     */
    public static function modelClasses(): array
    {
        $classes = [];

        foreach (glob(app_path('Models').'/*.php') ?: [] as $file) {
            $class = 'App\\Models\\'.pathinfo($file, PATHINFO_FILENAME);

            if (is_subclass_of($class, Model::class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    public function test_models_do_not_disable_mass_assignment_protection_globally(): void
    {
        foreach (self::modelClasses() as $class) {
            $model = new $class;

            $this->assertNotSame(
                [],
                $model->getGuarded(),
                "{$class} must not use unguarded mass assignment."
            );
        }
    }

    public function test_user_table_models_hide_authentication_secrets_when_serialized(): void
    {
        foreach ([User::class, Users::class] as $class) {
            $model = new $class([
                'email' => 'person@example.com',
                'password' => 'hashed-secret',
            ]);
            $model->setAttribute('remember_token', 'remember-me');

            $serialized = $model->toArray();

            $this->assertArrayNotHasKey('password', $serialized, "{$class} exposes password during serialization.");
            $this->assertArrayNotHasKey('remember_token', $serialized, "{$class} exposes remember token during serialization.");
        }
    }

    public function test_legacy_users_model_does_not_mass_assign_primary_key(): void
    {
        $model = new Users([
            'id' => 123,
            'email' => 'legacy@example.com',
        ]);

        $this->assertNull($model->getKey());
    }

    public function test_sensitive_payment_models_hide_credential_material_when_serialized(): void
    {
        $gateway = new Payment_gateway([
            'identifier' => 'stripe',
            'keys' => json_encode(['secret_key' => 'sk_test_secret']),
        ]);

        $history = new PaymentHistoryEntry([
            'transaction_keys' => json_encode(['token' => 'provider-token']),
            'transaction_id' => 'txn-secret',
            'order_id' => 'order-secret',
        ]);

        $this->assertArrayNotHasKey('keys', $gateway->toArray());
        $this->assertArrayNotHasKey('transaction_keys', $history->toArray());
        $this->assertArrayNotHasKey('transaction_id', $history->toArray());
        $this->assertArrayNotHasKey('order_id', $history->toArray());
    }

    public function test_all_models_use_default_connection_or_explicit_connection_name(): void
    {
        foreach (self::modelClasses() as $class) {
            $model = new $class;
            $connection = $model->getConnectionName();

            $this->assertTrue(
                $connection === null || is_string($connection),
                "{$class} has an invalid connection name."
            );
        }
    }

    public function test_model_audit_can_reflect_all_models_without_constructor_side_effects(): void
    {
        foreach (self::modelClasses() as $class) {
            $reflection = new ReflectionClass($class);

            $this->assertFalse($reflection->isAbstract(), "{$class} should not be abstract in app/Models.");
            $this->assertInstanceOf(Model::class, $reflection->newInstance());
        }
    }
}
