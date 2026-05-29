<?php

namespace Afterburner\Subscriptions\Tests\Feature;

use Afterburner\Subscriptions\Enums\PromotionDuration;
use Afterburner\Subscriptions\Http\Requests\SaveSubscriptionPromotionRequest;
use Afterburner\Subscriptions\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class SaveSubscriptionPromotionRequestTest extends TestCase
{
    public function test_form_validation_converts_amount_off_to_cents(): void
    {
        $validated = Validator::make([
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'amount_off' => '10.50',
            'duration' => PromotionDuration::Once->value,
            'is_active' => true,
        ], SaveSubscriptionPromotionRequest::formRulesFor(), SaveSubscriptionPromotionRequest::formValidationMessages())->validate();

        $attributes = SaveSubscriptionPromotionRequest::toPromotionAttributes($validated);

        $this->assertSame(1050, $attributes['amount_off_cents']);
        $this->assertArrayNotHasKey('amount_off', $attributes);
    }

    public function test_form_amount_off_must_meet_stripe_minimum(): void
    {
        $validator = Validator::make([
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'amount_off' => '0.10',
            'duration' => PromotionDuration::Once->value,
            'is_active' => true,
        ], SaveSubscriptionPromotionRequest::formRulesFor(), SaveSubscriptionPromotionRequest::formValidationMessages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_off', $validator->errors()->toArray());
    }
}
