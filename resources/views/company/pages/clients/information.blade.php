<!-- Modal -->
<div class="modal fade" id="myModalInformation" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"
    data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-inspinia text-primary" id="title">
                    create
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="myForm" action="" method="POST" novalidate>
                    @csrf
                    <input id="companyId" type="hidden" name="companyId">

                    <div class="section-wrapper bg-white rounded p-4 mb-4">
                        <h6 class="section-title text-muted mb-4">Información</h6>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <input type="text" class="form-control" id="name" name="name" readonly value="texasdfds"
                                            >
                                    </div>
                                 
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">

                                        <input type="text" class="form-control" id="kana" name="company_kana"
                                            style="border: none;">
                                    </div>
                                   
                                </div>
                            </div>
                        </div>


                    </div>

                    {{-- <!-- Representative Section -->
                    <div class="section-wrapper bg-white rounded p-4">
                        <h6 class="section-title text-muted mb-4">{{ __('admin.representative') }}</h6>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ __('admin.name') }}</span>
                                        </div>
                                        <input type="text" class="form-control" id="employee_name"
                                            name="employee_name" style="border: none;">
                                    </div>
                                    <div class="invalid-feedback" id="employee_nameError"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ __('admin.kana') }}</span>
                                        </div>
                                        <input type="text" class="form-control" id="employee_kana"
                                            name="employee_kana" style="border: none;">
                                    </div>
                                    <div class="invalid-feedback" id="employee_kanaError"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12 mb-3">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <label for="employee_email"
                                                class="input-group-text">{{ __('admin.email') }}</label>
                                        </div>
                                        <input type="email" class="form-control" id="employee_email"
                                            name="employee_email" style="border: none;">
                                    </div>
                                    <div class="invalid-feedback" id="employee_emailError"></div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ __('admin.phone') }}</span>
                                        </div>
                                        <input type="text" class="form-control" id="employee_phone"
                                            name="employee_phone" style="border: none;">
                                    </div>
                                    <div class="invalid-feedback" id="employee_phoneError"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ __('admin.phone2') }}</span>
                                        </div>
                                        <input type="text" class="form-control" id="employee_phone2"
                                            name="employee_phone2" style="border: none;">
                                    </div>
                                    <div class="invalid-feedback" id="employee_phone2Error"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="input-group shadow-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ __('admin.password') }}</span>
                                        </div>
                                        <input type="password" class="form-control" id="employee_password"
                                            name="employee_password" style="border: none;">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="fa fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="employee_passwordError"></div>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="text-center mt-4">
                        <button id="submitBtn" type="submit" class="btn btn-primary px-4 py-2">
                            {{ __('admin.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-content {
        border: none;
        border-radius: 0.5rem;
    }

    .modal-header {
        background-color: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .modal-body {
        padding: 1.5rem;
        background-color: #f8f9fa;
    }

    .section-wrapper {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .section-title {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
    }

    .input-group {
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
    }

    .input-group:focus-within {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .input-group-text {
        min-width: 120px;
        background-color: #f8f9fa;
        border: none;
        color: #495057;
        font-size: 0.875rem;
    }

    .form-control {
        height: calc(1.5em + 1rem + 2px);
    }

    .form-control:focus {
        box-shadow: none;
    }

    select.form-control {
        padding-right: 1.75rem;
        background-position: right 0.75rem center;
    }

    .btn-primary {
        font-weight: 500;
        letter-spacing: 0.5px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .toggle-password {
        border: none;
        background: transparent;
        height: 100%;
        color: #6c757d;
        padding: 0 1rem;
    }

    .toggle-password:hover {
        color: #495057;
    }

    .toggle-password:focus {
        box-shadow: none;
        outline: none;
    }

    .invalid-feedback {
        margin-top: 0.25rem;
        margin-left: 0.5rem;
        font-size: 80%;
    }

    .input-group-append .btn {
        border: none;
        background: transparent;
        color: #6c757d;
        padding: 0 1rem;
    }

    .input-group-append .btn:hover {
        color: #495057;
    }

    .input-group-append .btn:focus {
        box-shadow: none;
        outline: none;
    }

    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
