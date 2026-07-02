<?php

namespace Tests\Unit;

use App\Enums\AccountActivationStatus;
use App\Enums\ContentStatus;
use App\Enums\MediaFileType;
use App\Enums\MembershipRole;
use App\Enums\PaymentGatewayIdentifier;
use App\Enums\PaytmTransactionStatus;
use App\Enums\PostType;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\VideoCategory;
use App\Enums\Visibility;
use App\Models\PaymentGateway;
use App\Models\PaymentHistoryEntry;
use App\Services\Payments\Gateways\Flutterwave;
use App\Services\Payments\Gateways\Paypal;
use App\Services\Payments\Gateways\Paystack;
use App\Services\Payments\Gateways\Paytm;
use App\Services\Payments\Gateways\Razorpay;
use App\Services\Payments\Gateways\StripePay;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class DomainEnumsTest extends TestCase
{
    public function test_domain_enum_values_match_persisted_legacy_values(): void
    {
        $this->assertSame(['admin', 'general', 'member'], UserRole::values());
        $this->assertSame(['admin', 'general'], MembershipRole::values());
        $this->assertSame([0, 1], UserAccountStatus::values());
        $this->assertSame(['public', 'friends', 'private'], Visibility::values());
        $this->assertSame(['active', 'inactive'], ContentStatus::values());
        $this->assertSame(['image', 'video'], MediaFileType::values());
        $this->assertSame(['pending'], AccountActivationStatus::values());
        $this->assertSame([0, 1, 2], PaytmTransactionStatus::values());
        $this->assertSame(['video', 'shorts'], VideoCategory::values());
        $this->assertSame([
            'general',
            'event',
            'live_streaming',
            'share',
            'profile_picture',
            'cover_photo',
            'fundraiser',
        ], PostType::values());
    }

    public function test_payment_gateway_identifiers_map_to_existing_gateway_services(): void
    {
        $this->assertSame(StripePay::class, PaymentGatewayIdentifier::Stripe->serviceClass());
        $this->assertSame(Razorpay::class, PaymentGatewayIdentifier::Razorpay->serviceClass());
        $this->assertSame(Flutterwave::class, PaymentGatewayIdentifier::Flutterwave->serviceClass());
        $this->assertSame(Paypal::class, PaymentGatewayIdentifier::Paypal->serviceClass());
        $this->assertSame(Paystack::class, PaymentGatewayIdentifier::Paystack->serviceClass());
        $this->assertSame(Paytm::class, PaymentGatewayIdentifier::Paytm->serviceClass());

        foreach (PaymentGatewayIdentifier::cases() as $identifier) {
            $this->assertTrue(class_exists($identifier->serviceClass()));
        }
    }

    public function test_laravel_enum_validation_accepts_visibility_values(): void
    {
        $valid = Validator::make(
            ['privacy' => Visibility::Public->value],
            ['privacy' => ['required', Rule::enum(Visibility::class)]]
        );
        $invalid = Validator::make(
            ['privacy' => 'followers'],
            ['privacy' => ['required', Rule::enum(Visibility::class)]]
        );

        $this->assertTrue($valid->passes());
        $this->assertFalse($invalid->passes());
    }

    public function test_payment_history_status_cast_maps_paytm_transaction_state(): void
    {
        $entry = new PaymentHistoryEntry;
        $entry->status = PaytmTransactionStatus::Successful->value;

        $this->assertSame(PaytmTransactionStatus::Successful, $entry->status);
    }

    public function test_payment_gateway_scope_accepts_identifier_enum(): void
    {
        $gateway = PaymentGateway::query()
            ->forIdentifier(PaymentGatewayIdentifier::Stripe)
            ->firstOrFail();

        $this->assertSame(PaymentGatewayIdentifier::Stripe->value, $gateway->identifier);
    }
}
