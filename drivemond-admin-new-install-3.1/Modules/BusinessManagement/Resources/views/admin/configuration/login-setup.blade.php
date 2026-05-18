@extends('adminmodule::layouts.master')

@section('content')
    <!-- login setup Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h4 class="mb-4 fs-20 pb-xxl-1">{{ translate('Login Settings') }}</h4>

            <!---- login setup ---->
            <div class="">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Login Setup') }}</h4>
                                    <p class="fs-14 mb-0">{{ translate('The option you select customer will have the to option to login') }}</p>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded mb-20">
                                    <div class="mb-xxl-20 mb-3">
                                        <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Customer Login Options') }}</h4>
                                        <p class="fs-14 mb-0">{{ translate('Based on your selection, customers will have the options to login customer app') }}</p>
                                    </div>
                                    <div class="bg-white rounded p-xl-3 p-2">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="custom-checkbox d-flex align-items-start gap-2">
                                                    <input type="checkbox" name="manual_login" id="manual_login" class="input-size-20" checked>
                                                    <label for="manual_login" class="mb-0">
                                                        <h5 class="fs-14 mb-1">
                                                            {{ translate('Manual Login') }}
                                                             <img src="{{ asset('public/assets/admin-module/img/info-warning-icon.png') }}" class="cursor-pointer"
                                                                data-bs-toggle="tooltip" data-bs-placement="right"
                                                                data-bs-title="{{ translate('Enter the amount you want to refund to the customer') }}">
                                                        </h5>
                                                        <p class="fs-12 m-0 opacity-75">
                                                            {{ translate('Customers will get the option
                                                            to create an account and log
                                                            in using the necessary
                                                            credentials & password in the
                                                            app') }}
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="custom-checkbox d-flex align-items-start gap-2">
                                                    <input type="checkbox" name="otp_login" id="otp_login" class="input-size-20" checked>
                                                    <label for="otp_login" class="mb-0">
                                                        <h5 class="fs-14 mb-1">{{ translate('OTP Login') }}</h5>
                                                        <p class="fs-12 m-0 opacity-75">
                                                            {{ translate('With OTP Login, customers
                                                            can log in using their phone
                                                            number without password. To enable this feature') }}
                                                            <a href="#0" class="text-decoration-underline text-info">{{ translate('Configure SMS Setup') }}</a>
                                                            {{ translate('Here.') }}
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                    <div class="mb-xxl-20 mb-3">
                                        <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Driver Login Options') }}</h4>
                                        <p class="fs-14 mb-0">{{ translate('Based on your selection, drivers will have the options to login customer app') }}</p>
                                    </div>
                                    <div class="bg-white rounded p-xl-3 p-2">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="custom-checkbox d-flex align-items-start gap-2">
                                                    <input type="checkbox" name="manual_login_driver" id="manual_login_driver" class="input-size-20" checked>
                                                    <label for="manual_login_driver" class="mb-0">
                                                        <h5 class="fs-14 mb-1">{{ translate('Manual Login') }}</h5>
                                                        <p class="fs-12 m-0 opacity-75">
                                                            {{ translate('Customers will get the option
                                                            to create an account and log
                                                            in using the necessary
                                                            credentials & password in the
                                                            app') }}
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="custom-checkbox d-flex align-items-start gap-2">
                                                    <input type="checkbox" name="otp_login_driver" id="otp_login_driver" class="input-size-20" checked>
                                                    <label for="otp_login_driver" class="mb-0">
                                                        <h5 class="fs-14 mb-1">{{ translate('OTP Login') }}</h5>
                                                        <p class="fs-12 m-0 opacity-75">
                                                            {{ translate('With OTP Login, driver
                                                            can log in using their phone
                                                            number without password. To enable this feature') }}
                                                            <a href="#0" class="text-decoration-underline text-info">{{ translate('Configure SMS Setup') }}</a>
                                                            {{ translate('Here.') }}
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                                    <input type="checkbox" name="customer_active" id="customer_active" class="input-size-20" checked>
                                                    <label for="customer_active" class="mb-0">
                                                        <h5 class="fs-14 mb-0">{{ translate('Activate Verification for Customer') }}</h5>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="custom-checkbox d-flex gap-2">
                                                    <input type="checkbox" name="driver_active" id="driver_active" class="input-size-20" checked>
                                                    <label for="driver_active" class="mb-0">
                                                        <h5 class="fs-14 mb-0">{{ translate('Activate Verification for Driver') }}</h5>
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
                    <button type="#0" class="btn btn-primary min-w-120 cmn_focus call-demo">{{ translate('Save Information') }}</button>
                </div>
            </div>
            <!---- OPT setup ---->
            <div class="">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('OTP Setup') }}</h4>
                                    <p class="fs-14 mb-0">{{ translate('Manage the settings for how many times a user can try to enter the otp.') }}</p>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                    <div class="row g-4">
                                        <div class="col-md-6 col-lg-4">
                                            <label for="max_opt" class="fs-14 text-dark mb-10px d-flex align-items-center gap-1">
                                                {{ translate('Maximum OTP hit') }}
                                                <i class="bi bi-info-circle-fill text-muted cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right"
                                                            data-bs-title="{{ translate('Content need') }}"></i>
                                            </label>
                                            <div class="form-grop">
                                                <input type="text" class="form-control" id="max_opt" placeholder="EX: 5">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <label for="resend_opt" class="fs-14 text-dark mb-10px d-flex align-items-center gap-1">
                                                {{ translate('OTP resend time (Sec)') }}
                                                <i class="bi bi-info-circle-fill text-muted cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right"
                                                            data-bs-title="{{ translate('Content need') }}"></i>
                                            </label>
                                            <div class="form-grop">
                                                <input type="text" class="form-control" id="resend_opt" placeholder="EX: 5">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <label for="temporary_opt" class="fs-14 text-dark mb-10px d-flex align-items-center gap-1">
                                                {{ translate('Temporary block time (Sec)') }}
                                                <i class="bi bi-info-circle-fill text-muted cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right"
                                                            data-bs-title="{{ translate('Content need') }}"></i>
                                            </label>
                                            <div class="form-grop">
                                                <input type="text" class="form-control" id="temporary_opt" placeholder="EX: 5">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Login Setup') }}</h4>
                                    <p class="fs-14 mb-0">{{ translate('Manage the settings for how many times a user can try to log in to the system.') }}</p>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                    <div class="row g-4">
                                        <div class="col-md-6 col-lg-4">
                                            <label for="max_opt_hit" class="fs-14 text-dark mb-10px d-flex align-items-center gap-1">
                                                {{ translate('Maximum Login hit') }}
                                                <i class="bi bi-info-circle-fill text-muted cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right"
                                                            data-bs-title="{{ translate('Content need') }}"></i>
                                            </label>
                                            <div class="form-grop">
                                                <input type="text" class="form-control" id="max_opt_hit" placeholder="EX: 5">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <label for="temporary_opt_titme" class="fs-14 text-dark mb-10px d-flex align-items-center gap-1">
                                                {{ translate('Temporary login block time (Sec)') }}
                                                <i class="bi bi-info-circle-fill text-muted cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="right"
                                                            data-bs-title="{{ translate('Content need') }}"></i>
                                            </label>
                                            <div class="form-grop">
                                                <input type="text" class="form-control" id="temporary_opt_titme" placeholder="EX: 5">
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
                    <button type="#0" class="btn btn-primary min-w-120 cmn_focus call-demo">{{ translate('Save Information') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Additional Dynamic Data Setup Content -->
    <div class="main-content d-none">
        <div class="container-fluid">
            <h4 class="mb-4 fs-20 pb-xxl-1">{{ translate('Additional Dynamic Data Setup') }}</h4>
            <div class="position-relative nav--tab-wrapper mb-4">
                <ul class="nav d-flex gap-4 flex-nowrap nav--tabs bg-transparent overflow-x-auto text-nowrap">
                    <li class="nav-item text-capitalize">
                        <a href="#0" class="nav-link active-rounded-20 active">{{ translate('Customer Registration Form') }}</a>
                    </li>
                    <li class="nav-item">
                        <a href="#0" class="nav-link active-rounded-20">{{ translate('Driver Registration Form') }}</a>
                    </li>
                </ul>
            </div>
            <div class="">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Default Input Fields') }}</h4>
                                    <p class="fs-14 mb-0">{{ translate('These are the required standard fields that must be collected during restaurant registration.') }}</p>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                    <ul class="d-flex flex-wrap gap-3 list-inline">
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('First Name') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Last Name') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Phone') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Refer Code') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Password') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Confirm Password') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Default Input Fields') }}</h4>
                                    <p class="fs-14 mb-0">{{ translate('These are the required standard fields that must be collected during restaurant registration.') }}</p>
                                </div>
                                <div class="p-xxl-20 p-3 bg-F6F6F6 rounded">
                                    <ul class="d-flex flex-wrap gap-3 list-inline">
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('First Name') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Last Name') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Phone') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Refer Code') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Phone') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Password') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Confirm Password') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Email') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Address') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Identity Type') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Identity Number') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Identity Image') }}
                                        </li>
                                        <li class="fs-14 text-dark d-flex align-items-center gap-1 max-w-180px w-100">
                                            <i class="bi bi-dot fs-4"></i>
                                            {{ translate('Other Documents') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card border-0 custom-input_field_wrap">
                            <div class="card-body">
                                <div class="mb-20 d-flex align-items-center gap-2 justify-content-between flex-wrap">
                                    <h4 class="fs-16 mb-0 font-semibold d-block">{{ translate('Custom Input Fields') }}</h4>
                                    <div class="add-field-top d-none">
                                        <button type="button" class="btn fw-semibold fs-14 btn-primary add-filed-cmnBtn">
                                            <i class="bi bi-plus fs-18"></i>
                                            {{ translate('Add New Field') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="py-5 empty-estate-addfield text-center">
                                    <div class="">
                                        <div class="mb-20">
                                            <i class="bi bi-plus-circle-fill fs-2 text-muted"></i>
                                        </div>
                                        <h4 class="fs-16 mb-2 font-semibold d-block">{{ translate('Add Custom Input Fields') }}</h4>
                                        <p class="fs-12 mb-20">{{ translate('Customize the customer registration form by adding fields based on business requirements') }}</p>
                                        <div class="d-flex justify-content-center add-filed-cmnBtn-emptystate">
                                            <button type="button" class="btn fw-semibold fs-14 btn-primary add-filed-cmnBtn">
                                                <i class="bi bi-plus fs-18"></i>
                                                {{ translate('Add New Field') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-column gap-2 custom-main-filed-item d-none">
                                        <div class="bg-F6F6F6 rounded">
                                            <div class="border-bottom">
                                                <div class="d-flex align-items-center gap-2 justify-content-between p-xxl-20 p-3">
                                                    <span class="mb-0">
                                                        <p class="fs-14 m-0 fw-semibold">
                                                            {{ translate('Filed 1') }}
                                                        </p>
                                                    </span>
                                                    <div class="gap-xxl-20 gap-3 d-flex align-items-center">
                                                        <label class="custom-checkbox d-flex align-items-center gap-2 m-0">
                                                            <input type="checkbox" name="is_requird" class="" checked>
                                                            <span class="mb-0">
                                                                <p class="fs-14 m-0 fw-semibold">
                                                                    {{ translate('Is Required') }}
                                                                </p>
                                                            </span>
                                                        </label>
                                                        <button type="button" class="btn btn-danger px-1 w-30px h-30px py-1 cutom_mainItem-RemoveBtn">
                                                            <i class="bi bi-trash3 fs-14"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-xxl-20 p-3">
                                                <div class="row g-4">
                                                    <div class="col-md-6 col-lg-4">
                                                        <label for="max_opt" class="fs-14 text-black mb-10px d-flex align-items-center gap-1">
                                                            {{ translate('Type') }} <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="form-grop">
                                                            <select name="type_select" class="custom-input-field_select bg-white bs-border-cus form-select min-h-45px px-3 rounded">
                                                                <option value="text">{{ translate('Text') }}</option>
                                                                <option value="number">{{ translate('Number') }}</option>
                                                                <option value="date">{{ translate('Date') }}</option>
                                                                <option value="email">{{ translate('Email') }}</option>
                                                                <option value="phone">{{ translate('Phone') }}</option>
                                                                <option value="check_boxes">{{ translate('Check Box') }}</option>
                                                                <option value="radio_group">{{ translate('Radio') }}</option>
                                                                <option value="file_upload">{{ translate('file Upload') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-lg-4">
                                                        <label for="input_field_title" class="fs-14 text-black mb-10px d-flex align-items-center gap-1">
                                                            {{ translate('Input Field Title') }} <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="form-grop">
                                                            <input type="text" class="form-control" id="input_field_title" placeholder="Ex: 1235 5648 2314">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-lg-4 flexible-placeholder">
                                                        <label for="place_holder" class="fs-14 text-black mb-10px d-flex align-items-center gap-1">
                                                            {{ translate('Place holder') }} <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="form-grop">
                                                            <input type="text" class="form-control" id="place_holder" placeholder="Ex: 1235 5648 2314">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-lg-4 flexible-file-format">
                                                        <label for="file_formats" class="fs-14 text-black mb-10px d-flex align-items-center gap-1">
                                                            {{ translate('File Format') }} <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="form-grop bs-border-cus gap-4 d-flex flex-xxl-nowrap flex-wrap bg-white min-h-45px px-3 py-10px rounded">
                                                            <div>
                                                                <div class="custom-checkbox d-flex align-items-center gap-2">
                                                                    <input type="checkbox" name="img_format" id="img_format" class="" checked>
                                                                    <label for="img_format" class="mb-0">
                                                                        <p class="fs-14 m-0 opacity-75">
                                                                            {{ translate('Jpg, Jpeg, Png') }}
                                                                        </p>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="custom-checkbox d-flex align-items-center gap-2">
                                                                    <input type="checkbox" name="pdf_format" id="pdf_format" class="" checked>
                                                                    <label for="pdf_format" class="mb-0">
                                                                        <p class="fs-14 m-0 opacity-75">
                                                                            {{ translate('Pdf') }}
                                                                        </p>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="custom-checkbox d-flex align-items-center gap-2">
                                                                    <input type="checkbox" name="docs_format" id="docs_format" class="" checked>
                                                                    <label for="docs_format" class="mb-0">
                                                                        <p class="fs-14 m-0 opacity-75">
                                                                            {{ translate('Docs') }}
                                                                        </p>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 col-lg-4 flexible-file-format">
                                                        <div class="mb-10px d-flex justify-content-between align-items-center gap-1 flex-wrap">
                                                            <label for="unlimites_q_holder" class="fs-14 text-black d-flex align-items-center gap-1 mb-0">
                                                                {{ translate('Quantity') }} <span class="text-danger">*</span>
                                                            </label>
                                                            <div class="custom-checkbox d-flex align-items-center gap-2">
                                                                <label for="unlimites_quantity" class="mb-0">
                                                                    <p class="fs-14 m-0">
                                                                        {{ translate('Unlimited') }}
                                                                    </p>
                                                                </label>
                                                                <input type="checkbox" name="unlimites_quantity" id="unlimites_quantity" class="" checked>
                                                            </div>
                                                        </div>
                                                        <div class="form-grop">
                                                            <input type="text" class="form-control" id="unlimites_q_holder" placeholder="Unlimited">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Checkmark -->
                                        <div class="checkmark-flexible-wrapper">
                                            <div class="d-flex flex-column gap-2">
                                                <div class="bg-F6F6F6 rounded p-xxl-20 p-3">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Add Checkmark Option') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Option Name') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <input type="text" placeholder="Ex: Enter option" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3 d-flex justify-content-end">
                                                            <button type="button" class="btn fs-14 px-3 btn-outline-primary add-checkmark-btn">
                                                                {{ translate('Add') }} <i class="bi fs-16 bi-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-F6F6F6 rounded p-xxl-20 p-3 add-checkmark-item d-none">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Add Checkmark Option') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Option Name') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <input type="text" placeholder="Ex: Enter option" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3 d-flex justify-content-end">
                                                            <button type="button" class="btn btn-danger px-1 w-30px h-30px py-1 checkmark-item-removeBtn">
                                                                <i class="bi bi-trash3 fs-14"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Radio -->
                                        <div class="flexible-radio">
                                            <div class="d-flex flex-column gap-2">
                                                <div class="bg-F6F6F6 rounded p-xxl-20 p-3">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Add Radio Option') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Option Name') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <input type="text" placeholder="Ex: Enter option" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3 d-flex justify-content-end">
                                                            <button type="button" class="btn fs-14 px-3 btn-outline-primary add-radio-btn">
                                                                {{ translate('Add') }} <i class="bi fs-16 bi-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-F6F6F6 rounded p-xxl-20 p-3 add-radio-item d-none">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Add Checkmark Option') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <h6 class="m-0 fw-medium">{{ translate('Option Name') }}</h6>
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3">
                                                            <input type="text" placeholder="Ex: Enter option" class="form-control">
                                                        </div>
                                                        <div class="col-sm-6 col-md-6 col-lg-3 d-flex justify-content-end">
                                                            <button type="button" class="btn btn-danger px-1 w-30px h-30px py-1 radio-item-removeBtn">
                                                                <i class="bi bi-trash3 fs-14"></i>
                                                            </button>
                                                        </div>
                                                    </div>
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
                    <button type="#0" class="btn btn-primary min-w-120 cmn_focus call-demo">{{ translate('Save Information') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
