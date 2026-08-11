<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow {
    final class StripeCurlTestState
    {
        /** @var list<array<string, mixed>> */
        public static array $handles = [];
        public static int $nextHandle = 1;
        public static bool $forceInitFailure = false;
    }

    function curl_init(): int|false
    {
        if (StripeCurlTestState::$forceInitFailure) {
            return false;
        }

        $id = StripeCurlTestState::$nextHandle++;
        if (!isset(StripeCurlTestState::$handles[$id])) {
            StripeCurlTestState::$handles[$id] = [
                'options' => [],
                'response' => false,
                'status' => 0,
                'error' => '',
            ];
        }

        return $id;
    }

    function curl_setopt(int $handle, int $option, mixed $value): bool
    {
        StripeCurlTestState::$handles[$handle]['options'][$option] = $value;

        return true;
    }

    function curl_exec(int $handle): string|false
    {
        return StripeCurlTestState::$handles[$handle]['response'];
    }

    function curl_getinfo(int $handle, int $option = 0): mixed
    {
        if (\CURLINFO_RESPONSE_CODE === $option) {
            return StripeCurlTestState::$handles[$handle]['status'];
        }

        return null;
    }

    function curl_error(int $handle): string
    {
        return StripeCurlTestState::$handles[$handle]['error'];
    }

    function curl_close(int $handle): void
    {
    }
}

namespace App\Tests\Unit\Module\Admin\Payment {

    use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
    use App\Module\Admin\Application\Payment\Provider\StripePaymentDetailsProvider;
    use App\Module\Order\Application\Workflow\StripeApiClient;
    use App\Module\Order\Application\Workflow\StripeCurlTestState;
    use App\Module\Order\Domain\Entity\OrderCheckoutSession;
    use App\Module\User\Domain\Entity\User;
    use App\Shared\Application\Exception\ExternalServiceException;
    use PHPUnit\Framework\TestCase;

    final class StripeApiClientAndProviderTest extends TestCase
    {
        protected function tearDown(): void
        {
            StripeCurlTestState::$handles = [];
            StripeCurlTestState::$nextHandle = 1;
            StripeCurlTestState::$forceInitFailure = false;
        }

        public function testStripeApiClientCoversCurlErrorJsonErrorAndHttpErrorBranches(): void
        {
            $client = new StripeApiClient('sk_test_123');

            StripeCurlTestState::$handles = [1 => [
                'options' => [],
                'response' => false,
                'status' => 0,
                'error' => 'network down',
            ]];
            StripeCurlTestState::$nextHandle = 1;

            try {
                $client->createRefund(['payment_intent' => 'pi_test_network']);
                self::fail('Expected cURL failure.');
            } catch (ExternalServiceException $exception) {
                self::assertStringContainsString('network down', $exception->getMessage());
                self::assertSame('Le service de paiement est momentanément indisponible.', $exception->publicMessage());
            }

            StripeCurlTestState::$handles = [1 => [
                'options' => [],
                'response' => '{invalid',
                'status' => 200,
                'error' => '',
            ]];
            StripeCurlTestState::$nextHandle = 1;
            try {
                $client->createRefund(['payment_intent' => 'pi_test_json']);
                self::fail('Expected invalid JSON failure.');
            } catch (ExternalServiceException $exception) {
                self::assertSame('Stripe a retourné une réponse invalide.', $exception->getMessage());
                self::assertSame('Le service de paiement a retourné une réponse inattendue.', $exception->publicMessage());
            }

            StripeCurlTestState::$handles = [1 => [
                'options' => [],
                'response' => json_encode(['error' => ['message' => 'denied']], JSON_THROW_ON_ERROR),
                'status' => 402,
                'error' => '',
            ]];
            StripeCurlTestState::$nextHandle = 1;
            try {
                $client->retrievePaymentIntent('pi_test_http');
                self::fail('Expected HTTP error failure.');
            } catch (ExternalServiceException $exception) {
                self::assertStringContainsString('402', $exception->getMessage());
                self::assertSame('Le service de paiement a refusé la requête.', $exception->publicMessage());
            }
        }

        public function testStripeApiClientRejectsMissingSecretAndCurlInitializationFailure(): void
        {
            try {
                (new StripeApiClient(''))->createRefund(['payment_intent' => 'pi_missing']);
                self::fail('Expected missing secret key failure.');
            } catch (ExternalServiceException $exception) {
                self::assertSame('Le service de paiement est momentanément indisponible.', $exception->getMessage());
            }

            StripeCurlTestState::$forceInitFailure = true;
            try {
                (new StripeApiClient('sk_test_init'))->retrieveCheckoutSession('cs_test_init');
                self::fail('Expected curl init failure.');
            } catch (ExternalServiceException $exception) {
                self::assertSame('Impossible d’initialiser le service de paiement.', $exception->getMessage());
            }
        }

        public function testStripeApiClientRejectsJsonScalarResponseAndBuildsGetAndPostRequests(): void
        {
            $client = new StripeApiClient('sk_test_request');

            StripeCurlTestState::$handles = [1 => [
                'options' => [],
                'response' => json_encode('scalar-response', JSON_THROW_ON_ERROR),
                'status' => 200,
                'error' => '',
            ]];
            StripeCurlTestState::$nextHandle = 1;
            try {
                $client->createRefund(['payment_intent' => 'pi_scalar']);
                self::fail('Expected non-object JSON failure.');
            } catch (ExternalServiceException $exception) {
                self::assertSame('Stripe a retourné une réponse JSON non objet.', $exception->getMessage());
            }

            StripeCurlTestState::$handles = [
                1 => [
                    'options' => [],
                    'response' => json_encode(['id' => 'cs_get'], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
                2 => [
                    'options' => [],
                    'response' => json_encode(['id' => 're_test'], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
            ];
            StripeCurlTestState::$nextHandle = 1;

            $client->retrieveCheckoutSession('cs_test/with spaces');
            self::assertStringEndsWith('/checkout/sessions/cs_test%2Fwith%20spaces', (string) StripeCurlTestState::$handles[1]['options'][\CURLOPT_URL]);
            self::assertArrayNotHasKey(\CURLOPT_POSTFIELDS, StripeCurlTestState::$handles[1]['options']);

            $client->createRefund(['payment_intent' => 'pi_success'], '   ');
            self::assertContains('Content-Type: application/x-www-form-urlencoded', StripeCurlTestState::$handles[2]['options'][\CURLOPT_HTTPHEADER]);
            self::assertNotContains('Idempotency-Key: ', StripeCurlTestState::$handles[2]['options'][\CURLOPT_HTTPHEADER]);
            self::assertSame('payment_intent=pi_success', StripeCurlTestState::$handles[2]['options'][\CURLOPT_POSTFIELDS]);
        }

        public function testStripeApiClientCreatesSuccessfulRequestsAndProviderUsesSuccessfulResponses(): void
        {
            $client = new StripeApiClient('sk_test_456');
            StripeCurlTestState::$handles = [
                1 => [
                    'options' => [],
                    'response' => json_encode([
                        'id' => 'cs_test_success',
                        'status' => 'open',
                        'payment_status' => 'unpaid',
                        'payment_intent' => 'pi_test_created',
                        'customer_details' => ['email' => 'create@example.test'],
                        'expires_at' => 1780000000,
                    ], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
                2 => [
                    'options' => [],
                    'response' => json_encode([
                        'id' => 'cs_test_success',
                        'status' => 'complete',
                        'payment_status' => 'paid',
                        'payment_intent' => 'pi_test_success',
                        'customer_details' => ['email' => 'payer@example.test'],
                        'expires_at' => 1780000000,
                    ], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
                3 => [
                    'options' => [],
                    'response' => json_encode([
                        'id' => 'pi_test_success',
                        'status' => 'succeeded',
                        'amount' => 12900,
                        'currency' => 'eur',
                        'last_payment_error' => [
                            'code' => 'card_declined',
                            'decline_code' => 'insufficient_funds',
                            'message' => 'Declined',
                            'type' => 'card_error',
                        ],
                    ], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
            ];
            StripeCurlTestState::$nextHandle = 1;

            $sessionPayload = $client->createCheckoutSession(['mode' => 'payment', 'success_url' => 'https://front.test/success'], 'idem-123');
            self::assertSame('cs_test_success', $sessionPayload['id']);
            self::assertStringContainsString('/checkout/sessions', (string) StripeCurlTestState::$handles[1]['options'][\CURLOPT_URL]);
            self::assertContains('Idempotency-Key: idem-123', StripeCurlTestState::$handles[1]['options'][\CURLOPT_HTTPHEADER]);
            self::assertSame('mode=payment&success_url=https%3A%2F%2Ffront.test%2Fsuccess', StripeCurlTestState::$handles[1]['options'][\CURLOPT_POSTFIELDS]);

            $payment = $this->payment();
            $details = (new StripePaymentDetailsProvider($client, new AdminPaymentFormatter()))->provide($payment);

            self::assertSame('cs_test_success', $details['checkoutSession']['id']);
            self::assertSame('Terminée', $details['checkoutSession']['statusLabel']);
            self::assertSame('Payé', $details['checkoutSession']['paymentStatusLabel']);
            self::assertSame('pi_test_success', $details['checkoutSession']['paymentIntent']);
            self::assertSame('payer@example.test', $details['checkoutSession']['customerEmail']);
            self::assertSame('pi_test_success', $details['paymentIntent']['id']);
            self::assertSame('Réussi', $details['paymentIntent']['statusLabel']);
            self::assertSame(12900, $details['paymentIntent']['amount']);
            self::assertSame('eur', $details['paymentIntent']['currency']);
            self::assertSame('card_declined', $details['paymentIntent']['lastPaymentError']['code']);
        }

        public function testStripeApiClientRetriesOnlyRetryableRequests(): void
        {
            $client = new StripeApiClient('sk_test_retry');
            StripeCurlTestState::$handles = [
                1 => [
                    'options' => [],
                    'response' => false,
                    'status' => 0,
                    'error' => 'temporary network issue',
                ],
                2 => [
                    'options' => [],
                    'response' => json_encode(['id' => 'cs_retry_ok'], JSON_THROW_ON_ERROR),
                    'status' => 200,
                    'error' => '',
                ],
            ];
            StripeCurlTestState::$nextHandle = 1;

            $payload = $client->retrieveCheckoutSession('cs_retry');
            self::assertSame('cs_retry_ok', $payload['id']);
            self::assertContains('X-Hociatec-Retry-Attempt: 2', StripeCurlTestState::$handles[2]['options'][\CURLOPT_HTTPHEADER]);

            StripeCurlTestState::$handles = [
                1 => [
                    'options' => [],
                    'response' => false,
                    'status' => 0,
                    'error' => 'temporary network issue',
                ],
            ];
            StripeCurlTestState::$nextHandle = 1;

            try {
                $client->createRefund(['payment_intent' => 'pi_no_retry']);
                self::fail('Expected non-idempotent request to fail without retry.');
            } catch (ExternalServiceException $exception) {
                self::assertStringContainsString('temporary network issue', $exception->getMessage());
                self::assertCount(1, StripeCurlTestState::$handles);
            }
        }

        private function payment(): OrderCheckoutSession
        {
            $user = new User('stripe@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
            $user->setPassword('hashed');

            return (new OrderCheckoutSession('pay-token-stripe', $user, 'cart-token-stripe', 12, 'stripe-session-live', 'https://checkout.test'))
                ->setTotalPriceCents(12900)
                ->markPaid('pi_local_fallback', 'paid', 'checkout.session.completed')
                ->setOrderId(null);
        }
    }
}
