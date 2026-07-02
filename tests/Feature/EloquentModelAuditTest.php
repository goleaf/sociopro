<?php

namespace Tests\Feature;

use App\Models\Comments;
use App\Models\FeelingAndActivity;
use App\Models\PaymentGateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Posts;
use App\Models\Stories;
use App\Models\User;
use App\Models\Users;
use Illuminate\Database\Eloquent\MassAssignmentException;
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

    public function test_models_declare_explicit_mass_assignment_contracts(): void
    {
        foreach (self::modelClasses() as $class) {
            $reflection = new ReflectionClass($class);

            $declaresFillable = $reflection->hasProperty('fillable')
                && $reflection->getProperty('fillable')->getDeclaringClass()->getName() === $class;
            $declaresGuarded = $reflection->hasProperty('guarded')
                && $reflection->getProperty('guarded')->getDeclaringClass()->getName() === $class;

            $this->assertTrue(
                $declaresFillable || $declaresGuarded,
                "{$class} must declare either fillable or guarded fields."
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

    public function test_user_table_models_block_sensitive_mass_assignment_fields(): void
    {
        $sensitiveAttributes = [
            'id' => 123,
            'password' => 'plain-text-password',
            'remember_token' => 'remember-me',
            'user_role' => 'admin',
            'status' => 1,
            'friends' => '[1]',
            'followers' => '[2]',
            'email_verified_at' => now(),
            'lastActive' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([User::class, Users::class] as $class) {
            $model = new $class($sensitiveAttributes);

            foreach (array_keys($sensitiveAttributes) as $attribute) {
                $this->assertFalse(
                    $model->offsetExists($attribute),
                    "{$class} allows sensitive [{$attribute}] mass assignment."
                );
            }
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

    public function test_legacy_primary_key_models_do_not_mass_assign_primary_keys(): void
    {
        $expectedPrimaryKeys = [
            Comments::class => 'comment_id',
            FeelingAndActivity::class => 'feeling_and_activity_id',
            Posts::class => 'post_id',
            Stories::class => 'story_id',
        ];

        foreach (self::modelClasses() as $class) {
            $primaryKey = $expectedPrimaryKeys[$class] ?? (new $class)->getKeyName();
            $model = new $class;

            $this->assertSame($primaryKey, $model->getKeyName());

            try {
                $model = new $class([$primaryKey => 123]);
            } catch (MassAssignmentException) {
                $model = new $class;
            }

            $this->assertFalse(
                $model->offsetExists($primaryKey),
                "{$class} allows primary key [{$primaryKey}] mass assignment."
            );
            $this->assertNull($model->getKey(), "{$class} mass assigned its primary key.");
        }
    }

    public function test_payment_models_block_sensitive_mass_assignment_fields(): void
    {
        $gateway = new PaymentGateway([
            'keys' => json_encode(['secret_key' => 'sk_test_secret']),
            'model_name' => 'TamperedGateway',
            'test_model' => 'TamperedTestGateway',
            'status' => 0,
            'is_addon' => 1,
        ]);

        foreach (['keys', 'model_name', 'test_model', 'status', 'is_addon'] as $attribute) {
            $this->assertFalse(
                $gateway->offsetExists($attribute),
                PaymentGateway::class." allows sensitive [{$attribute}] mass assignment."
            );
        }

        $history = new PaymentHistoryEntry([
            'transaction_keys' => json_encode(['token' => 'provider-token']),
            'transaction_id' => 'txn-secret',
            'order_id' => 'order-secret',
            'status' => 'successful',
        ]);

        foreach (['transaction_keys', 'transaction_id', 'order_id', 'status'] as $attribute) {
            $this->assertFalse(
                $history->offsetExists($attribute),
                PaymentHistoryEntry::class." allows sensitive [{$attribute}] mass assignment."
            );
        }
    }

    public function test_sensitive_payment_models_hide_credential_material_when_serialized(): void
    {
        $gateway = new PaymentGateway([
            'identifier' => 'stripe',
        ]);
        $gateway->forceFill([
            'keys' => json_encode(['secret_key' => 'sk_test_secret']),
        ]);

        $history = new PaymentHistoryEntry;
        $history->forceFill([
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
