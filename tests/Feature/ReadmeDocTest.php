<?php

/**
 * README Documentation Tests
 *
 * This file tests that all code examples in README.md work exactly as documented.
 * It is generated/maintained by the readme-doc-test skill — do not edit manually.
 */

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Mail;
use Lettr\Builders\EmailBuilder;
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\DoubleOptInConfig;
use Lettr\Dto\Audience\ListAudienceContactsFilter;
use Lettr\Dto\Audience\SegmentCondition;
use Lettr\Dto\Audience\SegmentConditionGroup;
use Lettr\Dto\Audience\SegmentConditionsInput;
use Lettr\Dto\Audience\UpdateAudiencePropertyData;
use Lettr\Dto\Audience\UpdateAudienceSegmentData;
use Lettr\Enums\AudienceContactStatus;
use Lettr\Enums\AudiencePropertyType;
use Lettr\Enums\AudienceTopicDefaultSubscription;
use Lettr\Enums\AudienceTopicVisibility;
use Lettr\Enums\EventType;
use Lettr\Enums\SegmentOperator;
use Lettr\Laravel\Facades\Lettr;
use Lettr\Laravel\Mail\InlineLettrMailable;
use Lettr\Laravel\Mail\LettrMailable;
use Lettr\Laravel\Mail\LettrPendingMail;
use Lettr\Services\Audience\AudienceContactService;
use Lettr\Services\Audience\AudienceListService;
use Lettr\Services\Audience\AudiencePropertyService;
use Lettr\Services\Audience\AudienceSegmentService;
use Lettr\Services\Audience\AudienceTopicService;
use Lettr\Services\AudienceService;

// ---------------------------------------------------------------------------
// Section: Using the Lettr Facade Directly — Email Builder
// ---------------------------------------------------------------------------

it('emails service create returns an email builder', function () {
    $builder = Lettr::emails()->create();

    expect($builder)->toBeInstanceOf(EmailBuilder::class);
});

it('email builder supports fluent from to subject html chain', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com', 'Sender Name')
        ->to(['recipient@example.com'])
        ->subject('Hello from Lettr')
        ->html('<h1>Hello!</h1><p>This is a test email.</p>');

    expect($email)->toBeInstanceOf(EmailBuilder::class);
    $data = $email->build();
    expect($data->from->address)->toBe('sender@example.com');
    expect($data->from->name)->toBe('Sender Name');
    expect($data->subject->value)->toBe('Hello from Lettr');
    expect($data->html)->toBe('<h1>Hello!</h1><p>This is a test email.</p>');
});

it('email builder supports cc bcc replyTo and text', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->cc(['cc@example.com'])
        ->bcc(['bcc@example.com'])
        ->replyTo('reply@example.com')
        ->subject('Welcome!')
        ->html('<h1>Welcome</h1>')
        ->text('Welcome (plain text fallback)');

    expect($email)->toBeInstanceOf(EmailBuilder::class);
});

it('email builder supports tracking and transactional options', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->subject('Newsletter')
        ->html('<p>Content</p>')
        ->transactional()
        ->withClickTracking(true)
        ->withOpenTracking(true);

    expect($email)->toBeInstanceOf(EmailBuilder::class);
});

it('email builder supports metadata substitutionData and tag', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->subject('Hello!')
        ->html('<p>Hi</p>')
        ->metadata(['user_id' => '123', 'campaign' => 'welcome'])
        ->substitutionData(['name' => 'John', 'company' => 'Acme'])
        ->tag('welcome');

    expect($email)->toBeInstanceOf(EmailBuilder::class);
});

it('email builder supports all email options including inline css and substitutions', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->subject('Newsletter')
        ->html('<p>Content</p>')
        ->withClickTracking(true)
        ->withOpenTracking(true)
        ->transactional(false)
        ->withInlineCss(true)
        ->withSubstitutions(true);

    expect($email)->toBeInstanceOf(EmailBuilder::class);
});

// ---------------------------------------------------------------------------
// Section: Templates with Substitution Data (EmailBuilder useTemplate)
// ---------------------------------------------------------------------------

it('email builder supports useTemplate with version and substitution data', function () {
    $email = Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->useTemplate('order-confirmation', version: 1)
        ->substitutionData([
            'order_id' => '12345',
            'customer_name' => 'John Doe',
            'items' => [
                ['name' => 'Product A', 'price' => 29.99],
                ['name' => 'Product B', 'price' => 49.99],
            ],
            'total' => 79.98,
        ]);

    expect($email)->toBeInstanceOf(EmailBuilder::class);
    $data = $email->build();
    expect($data->templateSlug)->toBe('order-confirmation');
    expect($data->templateVersion)->toBe(1);
});

// ---------------------------------------------------------------------------
// Section: Using Lettr Templates with Mailables (LettrMailable)
// ---------------------------------------------------------------------------

it('LettrMailable template method sets slug and version and returns static', function () {
    $mailable = new class extends LettrMailable
    {
        public function build(): static
        {
            return $this->template('welcome-email', version: 2);
        }
    };

    $result = $mailable->build();

    expect($result)->toBeInstanceOf(LettrMailable::class);

    $reflection = new ReflectionClass($result);
    $slug = $reflection->getProperty('templateSlug');
    $slug->setAccessible(true);
    $version = $reflection->getProperty('templateVersion');
    $version->setAccessible(true);

    expect($slug->getValue($result))->toBe('welcome-email');
    expect($version->getValue($result))->toBe(2);
});

it('LettrMailable templateVersion method sets version separately', function () {
    $mailable = new class extends LettrMailable
    {
        public function build(): static
        {
            return $this->template('welcome-email')->templateVersion(3);
        }
    };

    $result = $mailable->build();

    $reflection = new ReflectionClass($result);
    $version = $reflection->getProperty('templateVersion');
    $version->setAccessible(true);

    expect($version->getValue($result))->toBe(3);
});

it('LettrMailable substitutionData method merges data into the mailable', function () {
    $mailable = new class extends LettrMailable
    {
        public function build(): static
        {
            return $this
                ->template('welcome-email', version: 2)
                ->substitutionData([
                    'user_name' => 'John',
                    'activation_url' => 'https://example.com/activate/abc123',
                ]);
        }
    };

    $result = $mailable->build();

    $reflection = new ReflectionClass($result);
    $data = $reflection->getProperty('substitutionData');
    $data->setAccessible(true);

    expect($data->getValue($result))->toBe([
        'user_name' => 'John',
        'activation_url' => 'https://example.com/activate/abc123',
    ]);
});

// ---------------------------------------------------------------------------
// Section: Inline Template Sending (Mail lettr)
// ---------------------------------------------------------------------------

it('Mail lettr returns a LettrPendingMail instance', function () {
    $result = Mail::lettr();

    expect($result)->toBeInstanceOf(LettrPendingMail::class);
});

it('can send template inline using Mail lettr with fake', function () {
    Mail::fake();

    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('welcome-email', substitutionData: ['name' => 'John']);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
});

it('can override template subject when sending inline', function () {
    Mail::fake();

    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('welcome-email', subject: 'Hey John!', substitutionData: ['name' => 'John']);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
});

it('can specify template version when sending inline', function () {
    Mail::fake();

    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('order-confirmation', substitutionData: [
            'order_id' => 123,
            'items' => [],
        ], version: 2);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
});

it('can use cc and bcc when sending template inline', function () {
    Mail::fake();

    Mail::lettr()
        ->to('user@example.com')
        ->cc('manager@example.com')
        ->bcc('records@example.com')
        ->sendTemplate('invoice', substitutionData: ['amount' => 99.99]);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com')
            && $mailable->hasCc('manager@example.com')
            && $mailable->hasBcc('records@example.com');
    });
});

it('can pass an Arrayable DTO as substitution data when sending inline', function () {
    Mail::fake();

    $dto = new class implements Arrayable
    {
        public function toArray(): array
        {
            return [
                'userName' => 'John',
                'activationUrl' => 'https://example.com/activate/abc123',
            ];
        }
    };

    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('welcome-email', substitutionData: $dto);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
});

// ---------------------------------------------------------------------------
// Section: Testing with Mail fake (examples from README)
// ---------------------------------------------------------------------------

it('welcome email is sent to correct recipient using Mail fake', function () {
    Mail::fake();

    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('welcome-email', substitutionData: ['name' => 'John']);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
});

it('order confirmation has correct to cc and bcc recipients using Mail fake', function () {
    Mail::fake();

    Mail::lettr()
        ->to('customer@example.com')
        ->cc('sales@example.com')
        ->bcc('records@example.com')
        ->sendTemplate('order-confirmation', substitutionData: ['order_id' => 123]);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('customer@example.com')
            && $mailable->hasCc('sales@example.com')
            && $mailable->hasBcc('records@example.com');
    });
});

// ---------------------------------------------------------------------------
// Section: EventType Enum
// ---------------------------------------------------------------------------

it('EventType Delivery has label Delivery', function () {
    $type = EventType::Delivery;

    expect($type->label())->toBe('Delivery');
});

it('EventType Delivery isSuccess returns true', function () {
    $type = EventType::Delivery;

    expect($type->isSuccess())->toBeTrue();
});

it('EventType Delivery isFailure returns false', function () {
    $type = EventType::Delivery;

    expect($type->isFailure())->toBeFalse();
});

it('EventType Delivery isEngagement returns false', function () {
    $type = EventType::Delivery;

    expect($type->isEngagement())->toBeFalse();
});

it('EventType Delivery isUnsubscribe returns false', function () {
    $type = EventType::Delivery;

    expect($type->isUnsubscribe())->toBeFalse();
});

it('EventType Bounce isFailure returns true and isSuccess returns false', function () {
    expect(EventType::Bounce->isFailure())->toBeTrue();
    expect(EventType::Bounce->isSuccess())->toBeFalse();
});

it('EventType Click isEngagement returns true and isSuccess returns false', function () {
    expect(EventType::Click->isEngagement())->toBeTrue();
    expect(EventType::Click->isSuccess())->toBeFalse();
});

it('EventType ListUnsubscribe isUnsubscribe returns true and isSuccess returns false', function () {
    expect(EventType::ListUnsubscribe->isUnsubscribe())->toBeTrue();
    expect(EventType::ListUnsubscribe->isSuccess())->toBeFalse();
});

it('EventType enum contains all documented event type values', function () {
    $documented = [
        'injection', 'delivery', 'bounce', 'delay', 'policy_rejection',
        'out_of_band', 'open', 'initial_open', 'click', 'generation_failure',
        'generation_rejection', 'spam_complaint', 'list_unsubscribe', 'link_unsubscribe',
    ];

    $actual = array_map(fn ($case) => $case->value, EventType::cases());

    foreach ($documented as $value) {
        expect($actual)->toContain($value);
    }
});

// ---------------------------------------------------------------------------
// Section: Audience Management — facade resolution
// ---------------------------------------------------------------------------

it('audience facade returns the AudienceService', function () {
    expect(Lettr::audience())->toBeInstanceOf(AudienceService::class);
});

it('audience exposes the five documented sub-services', function () {
    $audience = Lettr::audience();

    expect($audience->contacts())->toBeInstanceOf(AudienceContactService::class)
        ->and($audience->lists())->toBeInstanceOf(AudienceListService::class)
        ->and($audience->segments())->toBeInstanceOf(AudienceSegmentService::class)
        ->and($audience->topics())->toBeInstanceOf(AudienceTopicService::class)
        ->and($audience->properties())->toBeInstanceOf(AudiencePropertyService::class);
});

// ---------------------------------------------------------------------------
// Section: Audience Management — Contacts
// ---------------------------------------------------------------------------

it('CreateAudienceContactData carries email, list id and properties', function () {
    $data = new CreateAudienceContactData(
        email: 'jane@example.com',
        listId: 'list-uuid',
        properties: ['first_name' => 'Jane', 'plan' => 'pro'],
    );

    expect($data->email)->toBe('jane@example.com')
        ->and($data->listId)->toBe('list-uuid')
        ->and($data->properties)->toBe(['first_name' => 'Jane', 'plan' => 'pro']);
});

it('DoubleOptInConfig keeps the documented confirmation settings', function () {
    $config = new DoubleOptInConfig(
        from: 'hello@example.com',
        subject: 'Confirm your subscription',
        templateSlug: 'email-confirmation',
        redirectUrl: 'https://example.com/confirmed',
        fromName: 'Example',
    );

    expect($config->from)->toBe('hello@example.com')
        ->and($config->subject)->toBe('Confirm your subscription')
        ->and($config->templateSlug)->toBe('email-confirmation')
        ->and($config->redirectUrl)->toBe('https://example.com/confirmed')
        ->and($config->fromName)->toBe('Example');
});

it('ListAudienceContactsFilter builds fluently into query params', function () {
    $filter = ListAudienceContactsFilter::create()
        ->page(1)
        ->perPage(50)
        ->search('jane')
        ->status(AudienceContactStatus::Subscribed)
        ->listId('list-uuid');

    expect($filter->toArray())->toBe([
        'page' => 1,
        'per_page' => 50,
        'search' => 'jane',
        'status' => 'subscribed',
        'list_id' => 'list-uuid',
    ]);
});

// ---------------------------------------------------------------------------
// Section: Audience Management — Segments
// ---------------------------------------------------------------------------

it('SegmentConditionsInput serializes groups and conditions as documented', function () {
    $conditions = new SegmentConditionsInput(groups: [
        new SegmentConditionGroup(conditions: [
            new SegmentCondition('email', SegmentOperator::EndsWith, '@example.com'),
            new SegmentCondition('plan', SegmentOperator::Equals, 'pro'),
        ]),
    ]);

    expect($conditions->toArray())->toBe([
        'groups' => [
            [
                'conditions' => [
                    ['field' => 'email', 'operator' => 'ends_with', 'value' => '@example.com'],
                    ['field' => 'plan', 'operator' => 'equals', 'value' => 'pro'],
                ],
            ],
        ],
    ]);
});

it('UpdateAudienceSegmentData builder produces an instance', function () {
    $conditions = new SegmentConditionsInput(groups: [
        new SegmentConditionGroup(conditions: [
            new SegmentCondition('email', SegmentOperator::Contains, '@example.com'),
        ]),
    ]);

    $data = UpdateAudienceSegmentData::empty()
        ->withName('Renamed segment')
        ->withConditions($conditions);

    expect($data)->toBeInstanceOf(UpdateAudienceSegmentData::class);
});

// ---------------------------------------------------------------------------
// Section: Audience Management — Properties update builders
// ---------------------------------------------------------------------------

it('UpdateAudiencePropertyData exposes withFallback and clearFallback', function () {
    expect(UpdateAudiencePropertyData::withFallback('basic'))->toBeInstanceOf(UpdateAudiencePropertyData::class)
        ->and(UpdateAudiencePropertyData::clearFallback())->toBeInstanceOf(UpdateAudiencePropertyData::class);
});

// ---------------------------------------------------------------------------
// Section: Audience Management — Enums
// ---------------------------------------------------------------------------

it('AudienceContactStatus reports labels and email eligibility', function () {
    expect(AudienceContactStatus::Subscribed->label())->toBe('Subscribed')
        ->and(AudienceContactStatus::Subscribed->canReceiveEmails())->toBeTrue()
        ->and(AudienceContactStatus::Unsubscribed->canReceiveEmails())->toBeFalse();
});

it('SegmentOperator reports whether a value is required', function () {
    expect(SegmentOperator::Contains->requiresValue())->toBeTrue()
        ->and(SegmentOperator::IsTrue->requiresValue())->toBeFalse()
        ->and(SegmentOperator::IsFalse->requiresValue())->toBeFalse()
        ->and(SegmentOperator::Contains->label())->toBe('Contains');
});

it('AudiencePropertyType exposes the documented type values', function () {
    expect(AudiencePropertyType::StringType->value)->toBe('string')
        ->and(AudiencePropertyType::NumberType->value)->toBe('number')
        ->and(AudiencePropertyType::BooleanType->value)->toBe('boolean')
        ->and(AudiencePropertyType::DateType->value)->toBe('date')
        ->and(AudiencePropertyType::JsonType->value)->toBe('json');
});

it('AudienceTopicVisibility reports public vs private', function () {
    expect(AudienceTopicVisibility::PublicVisibility->isPublic())->toBeTrue()
        ->and(AudienceTopicVisibility::PrivateVisibility->isPublic())->toBeFalse()
        ->and(AudienceTopicVisibility::PublicVisibility->value)->toBe('public');
});

it('AudienceTopicDefaultSubscription reports opt-in semantics', function () {
    expect(AudienceTopicDefaultSubscription::OptIn->isOptIn())->toBeTrue()
        ->and(AudienceTopicDefaultSubscription::OptIn->subscribesNewContactsByDefault())->toBeFalse()
        ->and(AudienceTopicDefaultSubscription::OptOut->subscribesNewContactsByDefault())->toBeTrue();
});
