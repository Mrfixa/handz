<?php

namespace Modules\AuthManagement\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegistrationFromOtpStoreRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $driverRoute = str_contains($this->route()->getPrefix(), 'driver');
        return [
            'first_name' => 'required',
            'last_name' => 'sometimes',
            'referral_code' => 'sometimes',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|unique:users,phone',
            'email' => [
                Rule::requiredIf(function () use ($driverRoute) {
                    return $driverRoute;
                }), 'unique:users,email'
            ],
            'address' => 'sometimes',
            'identification_type' => Rule::in(['nid', 'passport', 'driving_license']),
            'identification_number' => 'sometimes',
            'service' => [
                Rule::requiredIf(function () use ($driverRoute) {
                    return $driverRoute;
                })
            ],
            'fcm_token' => 'sometimes',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)),
                403
            )
        );
    }
}
