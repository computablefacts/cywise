<?php

namespace App\Http\Requests;


use App\Contracts\Requests\Subscribe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** @deprecated */
class SubscribeRequest extends FormRequest implements Subscribe
{
    /**
     * @inheritDoc
     */
    public function rules()
    {
        $essential = config('towerify.stripe.plans.essential');
        $standard = config('towerify.stripe.plans.standard');
        $premium = config('towerify.stripe.plans.premium');

        return [
            'plan' => ['required', 'string', Rule::in([$essential, $standard, $premium])],
        ];
    }

    /**
     * @inheritDoc
     */
    public function authorize()
    {
        return Auth::check();
    }
}
