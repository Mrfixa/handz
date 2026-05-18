<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav d-flex gap-4 flex-nowrap nav--tabs bg-transparent overflow-x-auto text-nowrap">
        <li class="nav-item text-capitalize">
            <a href="{{ route('admin.business.configuration.login-settings.index') }}" class="text-capitalize nav-link rounded-20 fs-14
                {{ request()->routeIs('admin.business.configuration.login-settings.index') ? 'active' : ''}}
            ">{{ translate('login') }}</a>
        </li>
        <li class="nav-item text-capitalize">
            <a href="{{ route('admin.business.configuration.login-settings.otp-login-attempts-index') }}" class="text-capitalize nav-link rounded-20 fs-14
                {{ request()->routeIs('admin.business.configuration.login-settings.otp-login-attempts-index') ? 'active' : ''}}
            ">{{translate('OTP & Login Attempts')}}</a>
        </li>
    </ul>
</div>
