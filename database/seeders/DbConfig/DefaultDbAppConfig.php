<?php

namespace Database\Seeders\DbConfig;

class DefaultDbAppConfig implements DbAppConfigInterface
{
    public function getParams(): array
    {
        return [
            'towerify.stripe.plans.essential.name' => 'Essentiel',
            'towerify.stripe.plans.essential.description' => null,
            'towerify.stripe.plans.essential.features' => '',
            'towerify.stripe.plans.essential.monthly_price' => null,
            'towerify.stripe.plans.essential.monthly_price_id' => null,
            'towerify.stripe.plans.essential.yearly_price' => null,
            'towerify.stripe.plans.essential.yearly_price_id' => null,
            'towerify.stripe.plans.essential.onetime_price' => null,
            'towerify.stripe.plans.essential.onetime_price_id' => null,

            'towerify.stripe.plans.standard.name' => 'Standard',
            'towerify.stripe.plans.standard.description' => null,
            'towerify.stripe.plans.standard.features' => '',
            'towerify.stripe.plans.standard.monthly_price' => null,
            'towerify.stripe.plans.standard.monthly_price_id' => null,
            'towerify.stripe.plans.standard.yearly_price' => null,
            'towerify.stripe.plans.standard.yearly_price_id' => null,
            'towerify.stripe.plans.standard.onetime_price' => null,
            'towerify.stripe.plans.standard.onetime_price_id' => null,

            'towerify.stripe.plans.premium.name' => 'Premium',
            'towerify.stripe.plans.premium.description' => null,
            'towerify.stripe.plans.premium.features' => '',
            'towerify.stripe.plans.premium.monthly_price' => null,
            'towerify.stripe.plans.premium.monthly_price_id' => null,
            'towerify.stripe.plans.premium.yearly_price' => null,
            'towerify.stripe.plans.premium.yearly_price_id' => null,
            'towerify.stripe.plans.premium.onetime_price' => null,
            'towerify.stripe.plans.premium.onetime_price_id' => null,
        ];
    }
}
