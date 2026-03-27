<?php

declare(strict_types=1);

namespace WebtronicIE\WorldPay;

use Igniter\PayRegister\Models\Payment;
use Igniter\System\Classes\BaseExtension;
use Illuminate\Support\Facades\Event;
use Override;
use WebtronicIE\WorldPay\Payments\WorldPay;


class Extension extends BaseExtension
{

    #[Override]
    public function registerPaymentGateways(): array
    {
        return [

            WorldPay::class => [
                'code' => 'worldpay',
                'name' => 'WorldPay',
                'description' => 'Pay securely using WorldPay.',
            ],

        ];
    }

    #[Override]
    public function boot(): void
    {
        Event::listen('main.theme.activated', function(): void {
            Payment::syncAll();
        });


    }
}
