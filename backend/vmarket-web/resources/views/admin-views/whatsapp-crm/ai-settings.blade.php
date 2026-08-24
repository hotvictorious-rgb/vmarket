@extends('layouts.admin.app')

@section('title', translate('WhatsApp_AI_Brain_&_Settings'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h1 mb-0 d-flex align-items-center gap-2" style="color: #4A154B;">
                <i class="tio-android-robot"></i> {{ translate('AI_Brain_&_WhatsApp_Gateway_Settings') }}
            </h2>
            <p class="text-muted fs-12 mb-0">{{ translate('Manage Knowledge Base FAQs, Meta Cloud API Credentials, and Continuous Learning') }}</p>
        </div>
        <div>
            <span class="badge bg-primary p-2 fs-12">
                <i class="tio-user"></i> {{ $memoryProfilesCount }} {{ translate('Unique_Customer_AI_Memory_Graphs_Active') }}
            </span>
        </div>
    </div>

    <div class="row g-3">
        <!-- API Credentials Card -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h5 class="card-title font-weight-bold" style="color: #4A154B;">
                        <i class="tio-key"></i> {{ translate('Meta_WhatsApp_Cloud_API_Configuration') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.whatsapp-crm.ai-settings.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Gateway_Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ ($settings['status'] ?? 0) == 1 ? 'selected' : '' }}>{{ translate('Active (Primary Gateway)') }}</option>
                                <option value="0" {{ ($settings['status'] ?? 0) == 0 ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Phone_Number_ID') }}</label>
                            <input type="text" name="phone_number_id" class="form-control" value="{{ $settings['phone_number_id'] ?? '' }}" placeholder="e.g. 104829384729182" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('System_User_Access_Token') }}</label>
                            <textarea name="token" rows="3" class="form-control font-monospace fs-11" placeholder="EAAG..." required>{{ $settings['token'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('WhatsApp_Business_Account_(WABA)_ID') }}</label>
                            <input type="text" name="waba_id" class="form-control" value="{{ $settings['waba_id'] ?? '' }}" placeholder="e.g. 9482910482918">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Authentication_OTP_Template_Name') }}</label>
                            <input type="text" name="template_name" class="form-control" value="{{ $settings['template_name'] ?? 'victorious_otp_auth' }}" placeholder="victorious_otp_auth">
                        </div>

                        <hr class="my-4">
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="tio-cpu"></i> {{ translate('Google_Gemini_AI_Intelligence_Settings') }}
                        </h6>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Gemini_AI_Model') }}</label>
                            <select name="gemini_model" class="form-select">
                                <option value="gemini-1.5-flash" {{ ($geminiModel ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>
                                    ⚡ gemini-1.5-flash (Recommended: Fastest & Most Cost-Effective)
                                </option>
                                <option value="gemini-2.0-flash" {{ ($geminiModel ?? '') == 'gemini-2.0-flash' ? 'selected' : '' }}>
                                    🚀 gemini-2.0-flash (Ultra-Fast Function Calling)
                                </option>
                                <option value="gemini-1.5-pro" {{ ($geminiModel ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>
                                    🧠 gemini-1.5-pro (Deep Reasoning & Complex Queries)
                                </option>
                            </select>
                            <small class="text-muted fs-11">{{ translate('gemini-1.5-flash is optimized for near-instant sub-second WhatsApp commerce.') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">{{ translate('Google_Gemini_API_Key') }}</label>
                            <input type="password" name="gemini_api_key" class="form-control font-monospace fs-12" value="{{ $geminiApiKey ?? '' }}" placeholder="AIzaSy...">
                            <small class="text-muted fs-11">{{ translate('Get your free API key from Google AI Studio (aistudio.google.com).') }}</small>
                        </div>

                        <button type="submit" class="btn btn--primary w-100" style="background: #4A154B; border-color: #4A154B;">
                            {{ translate('Save_AI_&_Gateway_Settings') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- AI Knowledge Base FAQs Card -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title font-weight-bold" style="color: #4A154B;">
                        <i class="tio-book"></i> {{ translate('AI_Knowledge_Base_&_Store_Policies') }}
                    </h5>
                    <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#addFaqModal" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                        <i class="tio-add"></i> {{ translate('Add_FAQ') }}
                    </button>
                </div>
                <div class="card-body">
                    <p class="fs-12 text-muted mb-3">{{ translate('Any policy or FAQ added here is instantly learned by the AI assistant without coding.') }}</p>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Question') }}</th>
                                    <th>{{ translate('Answer') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($faqs as $faq)
                                    <tr>
                                        <td class="font-weight-bold text-truncate" style="max-width: 160px;">{{ $faq->question }}</td>
                                        <td class="text-truncate" style="max-width: 220px;">{{ $faq->answer }}</td>
                                        <td>
                                            <form action="{{ route('admin.whatsapp-crm.ai-settings.faq-delete', $faq->id) }}" method="POST" onsubmit="return confirm('Delete this FAQ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="tio-delete"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            {{ translate('No_custom_FAQs_added_yet._Click_Add_FAQ_above.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add FAQ -->
<div class="modal fade" id="addFaqModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.whatsapp-crm.ai-settings.faq-store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">{{ translate('Add_New_Rule_/_FAQ_to_AI_Brain') }}</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Customer_Question_or_Topic') }}</label>
                        <input type="text" name="question" class="form-control" placeholder="{{ translate('e.g. Do you deliver on Sundays in Uyo?') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Official_Store_Answer') }}</label>
                        <textarea name="answer" rows="3" class="form-control" placeholder="{{ translate('e.g. Yes! Sunday deliveries run between 1:00 PM and 6:00 PM across Uyo.') }}" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Category') }}</label>
                        <select name="category" class="form-select">
                            <option value="delivery">{{ translate('Delivery & Logistics') }}</option>
                            <option value="payment">{{ translate('Payment & Pricing') }}</option>
                            <option value="returns">{{ translate('Returns & Exchanges') }}</option>
                            <option value="general">{{ translate('General Inquiries') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary" style="background: #4A154B; border-color: #4A154B;">{{ translate('Save_to_AI_Brain') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
