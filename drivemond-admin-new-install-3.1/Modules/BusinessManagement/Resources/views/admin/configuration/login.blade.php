@extends('adminmodule::layouts.master')
@section('title', translate('Login'))
@section('content')
    <!-- login setup Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h4 class="mb-4 fs-20 pb-xxl-1">{{ translate('Login Settings') }}</h4>
            @include('businessmanagement::admin.configuration.partials._login-settings-inline-menu')
            @php
                $gateway = '<a href="' . route('admin.business.configuration.third-party.sms-gateway.index') .'" class="fw-semibold text-info" target="_blank">'. translate('SMS Gateway').'</a>';
                $firebaseOtp = '<a href="' . route('admin.business.configuration.third-party.firebase-otp.index') .'" class="fw-semibold text-info text-decoration-underline" target="_blank">'. translate('Firebase OTP').'</a>';
            @endphp
            <form action="{{ route('admin.business.configuration.login-settings.store') }}" class="submit-by-ajax">
                <div class="">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0">
                                <div class="card-body">
                                    <div class="mb-20">
                                        <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Login Setup') }}</h4>
                                        <p class="fs-14 mb-0">{{ translate('The option you select customer will have the to option to login') }}</p>
                                    </div>
                                    @foreach([CUSTOMER, DRIVER] as $key => $user)
                                            <?php
                                            $loginOptions = businessConfig(key: $user . '_login_options', settingsType: LOGIN_SETTINGS)?->value;
                                            ?>
                                        <div class="p-xxl-20 p-3 bg-F6F6F6 rounded {{ $loop->first ? 'mb-20' : ''  }}">
                                            <div class="mb-xxl-20 mb-3">
                                                <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate($user . ' Login Options') }}</h4>
                                                <p class="fs-14 mb-0">{{ translate('Based on your selection, customers will have the options to login customer app') }}</p>
                                            </div>
                                            <div class="bg-white rounded p-xl-3 p-2">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="custom-checkbox d-flex align-items-start gap-2">
                                                            <input type="checkbox" name="{{ $user }}[manual_login]"
                                                                   id="{{ $user }}-manual_login"
                                                                   class="input-size-20" {{ !isset($loginOptions) || ($loginOptions['manual_login'] ?? 0) == 1 ? 'checked' : '' }}>
                                                            <label for="{{ $user }}-manual_login" class="mb-0">
                                                                <h5 class="fs-14 mb-1">
                                                                    {{ translate('Manual Login') }}
                                                                </h5>
                                                                <p class="fs-12 m-0 opacity-75">
                                                                    {{ translate($user . ' will get the option
                                                                    to create an account and log
                                                                    in using the necessary
                                                                    credentials & password in the
                                                                    app') }}
                                                                </p>
                                                            </label>
                                                        </div>
                                                        <span class="error-text justify-content-start" data-error="{{ $user }}"></span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="custom-checkbox d-flex align-items-start gap-2">
                                                            <input type="checkbox" name="{{ $user }}[otp_login]"
                                                                   id="{{ $user }}-otp_login"
                                                                   class="input-size-20" {{ $isOtpEnabled ? '' : 'disabled' }} {{ ($loginOptions['otp_login'] ?? 0) == 1 ? 'checked' : '' }}>
                                                            <label for="{{ $user }}-otp_login" class="mb-0">
                                                                <h5 class="fs-14 mb-1">
                                                                    {{ translate('OTP Login') }}
                                                                    @if(!$isOtpEnabled)
                                                                        <img
                                                                            src="{{ asset('public/assets/admin-module/img/info-warning-icon.png') }}"
                                                                            class="cursor-pointer"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="right"
                                                                            data-bs-title="{{ translate('Configure your SMS settings to function OTP login properly') }}">
                                                                    @endif
                                                                </h5>
                                                                <p class="fs-12 m-0 opacity-75">
                                                                    {{ translate('With OTP Login, ' . $user .  ' can log in using their phone number without password.') }}
                                                                    @if(!$isOtpEnabled)
                                                                        {!! translate(key: 'To enable this feature, at least one {smsGateway} or {firebaseOtp} needs to be configured.', replace: ['smsGateway' => $gateway, 'firebaseOtp' => $firebaseOtp]) !!}
                                                                    @endif
                                                                </p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0">
                                <div class="card-body">
                                    <div class="mb-20">
                                        <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('User Number Verification') }}</h4>
                                        <p class="fs-14 mb-0">{{ translate('User must verify their number after signup manually.') }}</p>
                                    </div>
                                    <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                        <div class="bg-white rounded p-xl-3 p-2">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="custom-checkbox d-flex gap-2">
                                                        <input type="checkbox" name="customer_verification" id="customer_active" class="input-size-20" {{ $isOtpEnabled ? '' : 'disabled' }} {{ $isCustomerVerificationEnabled ? 'checked' : '' }}>
                                                        <label for="customer_active" class="mb-0">
                                                            <h5 class="fs-14 mb-0">
                                                                {{ translate('Activate Verification for Customer') }}
                                                                @if(!$isOtpEnabled)
                                                                    <img
                                                                        src="{{ asset('public/assets/admin-module/img/info-warning-icon.png') }}"
                                                                        class="cursor-pointer"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-placement="right"
                                                                        data-bs-title="{{ translate('Configure your SMS settings to function OTP login properly') }}">
                                                                @endif
                                                            </h5>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="custom-checkbox d-flex gap-2">
                                                        <input type="checkbox" name="driver_verification" id="driver_verification" class="input-size-20" {{ $isOtpEnabled ? '' : 'disabled' }} {{ $isDriverVerificationEnabled ? 'checked' : '' }}>
                                                        <label for="driver_verification" class="mb-0">
                                                            <h5 class="fs-14 mb-0">
                                                                {{ translate('Activate Verification for Driver') }}
                                                                @if(!$isOtpEnabled)
                                                                    <img
                                                                        src="{{ asset('public/assets/admin-module/img/info-warning-icon.png') }}"
                                                                        class="cursor-pointer"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-placement="right"
                                                                        data-bs-title="{{ translate('Configure your SMS settings to function OTP login properly') }}">
                                                                @endif
                                                            </h5>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-4">
                        <button type="reset" class="btn btn-secondary min-w-120 cmn_focus">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn-primary min-w-120 cmn_focus call-demo">{{ translate('Save Information') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
