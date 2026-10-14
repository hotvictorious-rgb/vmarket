<div class="second-el d--none">
     <div class="container">
         <div class="row justify-content-center">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h3 class="mb-4 text-capitalize">{{ translate('create_an_account') }}</h3>
                         <div class="border p-3 p-xl-4 rounded">
                             <h4 class="mb-3 text-capitalize">{{ translate('vendor_information') }}</h4>

                             <div class="row">
                                 <div class="col-sm-6">
                                     <div class="form-group mb-4">
                                         <label class="mb-2 text-capitalize"
                                             for="f_name">{{ translate('first_name') }}
                                             <span class="text-danger">*</span></label>
                                         <input class="form-control" type="text" name="f_name"
                                             placeholder="{{ translate('ex') . ': John' }}" required>
                                     </div>
                                     <div class="form-group mb-4">
                                         <label class="mb-2 text-capitalize" for="l_name">
                                             {{ translate('last_name') }}
                                             <span class="text-danger">*</span>
                                         </label>
                                         <input class="form-control" type="text" name="l_name"
                                             placeholder="{{ translate('ex') . ': Doe' }}" required>
                                     </div>
                                 </div>
                                 <div class="col-sm-6">
                                     <div class="">
                                         <div class="d-flex flex-column gap-3 align-items-center">
                                             <div class="upload-file">
                                                 <input type="file" class="upload-file__input" name="image"
                                                        data-max-size="2"
                                                     accept="{{getFileUploadFormats(skip: '.svg,.webp,.gif')}}" >
                                                 <div class="upload-file__img">
                                                     <div class="temp-img-box">
                                                         <div class="d-flex align-items-center flex-column gap-2">
                                                             <i class="bi bi-upload fs-30"></i>
                                                             <div class="fs-12 text-muted text-capitalize">
                                                                 {{ translate('upload_file') }}
                                                             </div>
                                                         </div>
                                                     </div>
                                                     <img src="#" class="dark-support img-fit-contain border"
                                                         alt="" hidden>
                                                 </div>
                                             </div>

                                             <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                                 <h6 class="text-uppercase mb-1">
                                                     {{ translate('vendor_image') }}
                                                     <span class="text-danger">*</span>
                                                 </h6>
                                                 <div class="text-muted text-capitalize">
                                                     {{ translate('image_ratio') . ' ' . '1:1' }}
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <div class="border p-3 p-xl-4 rounded mt-4">
                             <h4 class="mb-3 text-capitalize">{{ translate('shop_information') }}</h4>
                             <div class="form-group mb-4">
                                 <label class="mb-2 text-capitalize" for="store_name">
                                     {{ translate('store_name') }}
                                     <span class="text-danger">*</span>
                                 </label>
                                 <input class="form-control" type="text" name="shop_name"
                                     placeholder="{{ translate('ex') . ': XYZ store' }}" required>
                             </div>
                             <div class="form-group mb-4">
                                 <label class="mb-2 text-capitalize" for="store_address">
                                     {{ translate('store_address') }}
                                     <span class="text-danger">*</span>
                                 </label>
                                 <textarea class="form-control" name="shop_address" rows="3" placeholder="{{ translate('e.g., Shop 12, Victorious Plaza, Ikot Ekpene Road, Uyo, Akwa Ibom State') }}"
                                     required></textarea>
                             </div>

                             <div class="border p-3 p-xl-4 rounded mb-4">
                                 <div class="d-flex flex-column gap-3 align-items-center">
                                     <div class="upload-file">
                                         <input type="file" class="upload-file__input" name="logo"
                                                data-max-size="{{ getFileUploadMaxSize() }}"
                                             accept="{{getFileUploadFormats(skip: '.svg,.gif,.webp')}}">
                                         <div class="upload-file__img">
                                             <div class="temp-img-box">
                                                 <div class="d-flex align-items-center flex-column gap-2">
                                                     <i class="bi bi-upload fs-30"></i>
                                                     <div class="fs-12 text-muted text-capitalize">
                                                         {{ translate('upload_file') }}
                                                     </div>
                                                 </div>
                                             </div>
                                             <img src="#" class="dark-support img-fit-contain border"
                                                 alt="" hidden>
                                         </div>
                                     </div>

                                     <div class="d-flex flex-column gap-1 upload-img-content text-center">
                                         <h6 class="text-uppercase mb-1">
                                             {{ translate('store_image') }}
                                             <span class="text-danger">*</span>
                                         </h6>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('image_ratio') . ' ' . '1:1' }}
                                         </div>
                                         <div class="text-muted text-capitalize">
                                             {{ translate('Image Size : Max 2 MB') }}
                                         </div>
                                     </div>
                                 </div>
                              </div>

                              {{-- [AI] Nigerian Business Structure & Entity Selector (Optional on signup) --}}
                              <div class="border p-3 p-xl-4 rounded mb-4">
                                  <div class="d-flex justify-content-between align-items-center mb-3">
                                      <h4 class="mb-0 text-capitalize">{{ translate('business_structure_&_kyc') }}</h4>
                                      <span class="badge bg-light text-primary fs-12 px-2 py-1">{{ translate('optional_at_signup') }}</span>
                                  </div>
                                  <p class="text-muted fs-12 mb-3">
                                      {{ translate('Choose your legal business structure. You can sign up immediately as an Individual or provide your registered business credentials now.') }}
                                  </p>
                                  <div class="row">
                                      <div class="col-sm-6">
                                          <div class="form-group mb-3">
                                              <label class="mb-2 text-capitalize" for="business_type_select">
                                                  {{ translate('business_type') }}
                                              </label>
                                              <select class="form-control" name="business_type" id="business_type_select">
                                                  <option value="individual" selected>{{ translate('Individual / Sole Trader') }}</option>
                                                  <option value="bn">{{ translate('Registered Business Name (BN)') }}</option>
                                                  <option value="ltd">{{ translate('Limited Liability Company (LTD / PLC)') }}</option>
                                                  <option value="other">{{ translate('Other / Cooperative') }}</option>
                                              </select>
                                          </div>
                                      </div>

                                      <div class="col-sm-6 individual-field">
                                          <div class="form-group mb-3">
                                              <label class="mb-2 text-capitalize" for="nin_input">
                                                  {{ translate('national_identity_number_(NIN)') }}
                                              </label>
                                              <input class="form-control" type="text" name="nin" id="nin_input"
                                                     maxlength="11"
                                                     placeholder="{{ translate('11-digit NIN (can also verify later in dashboard)') }}">
                                          </div>
                                      </div>

                                      <div class="col-sm-6 corporate-field d-none">
                                          <div class="form-group mb-3">
                                              <label class="mb-2 text-capitalize" for="legal_name_input">
                                                  {{ translate('registered_business_name') }}
                                              </label>
                                              <input class="form-control" type="text" name="legal_name" id="legal_name_input"
                                                     placeholder="{{ translate('e.g., Victorious Ventures Ltd') }}">
                                          </div>
                                      </div>

                                      <div class="col-sm-6 corporate-field d-none">
                                          <div class="form-group mb-3">
                                              <label class="mb-2 text-capitalize" for="cac_number_input">
                                                  {{ translate('CAC_registration_number') }}
                                              </label>
                                              <input class="form-control" type="text" name="cac_number" id="cac_number_input"
                                                     placeholder="{{ translate('e.g., RC 1234567 or BN 9876543') }}">
                                          </div>
                                      </div>

                                      <div class="col-12 corporate-field d-none">
                                          <div class="form-group mb-2">
                                              <label class="mb-2 text-capitalize" for="cac_document_input">
                                                  {{ translate('CAC_certificate_document') }}
                                              </label>
                                              <input class="form-control" type="file" name="cac_document" id="cac_document_input"
                                                     accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                                              <small class="text-muted fs-11">{{ translate('Upload CAC registration certificate (PDF, JPG, PNG up to 5MB)') }}</small>
                                          </div>
                                      </div>
                                  </div>
                              </div>

                              {{-- [AI] VM-VEND-003: store banner + secondary banner blocks retired for vendors --}}
                              <div class="border p-3 p-xl-4 rounded">
                                 <div class="row gy-4">
                                     <div class="col-lg-6">
                                         <div>
                                             <h4 class="mb-3 text-capitalize">{{ translate('Business_TIN') }}</h4>
                                             <div class="form-group mb-4">
                                                 <label class="mb-2 text-capitalize"
                                                     for="">{{ translate('taxpayer_identification_number(TIN)') }}
                                                 </label>
                                                 <input class="form-control" type="text"
                                                     name="tax_identification_number"
                                                     placeholder="{{ translate('type_your_user_name') }}">
                                             </div>
                                             <div class="form-group mb-4">
                                                 <label class="mb-2 text-capitalize" for="">
                                                     {{ translate('Expire_Date') }}
                                                 </label>
                                                 <div class="position-relative">
                                                     <span class="bi bi-calendar icon-absolute-on-right"></span>
                                                     <input type="text"
                                                         class="js-daterangepicker_single-date-with-placeholder form-control"
                                                         placeholder="{{ translate('click_to_add_date') }}"
                                                         name="tin_expire_date" value="" readonly>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="col-lg-6">
                                         <div class="form-group mb-0">
                                             <div class="d-flex justify-content-center document-upload-container">
                                                <div class="document-file-assets"
                                                    data-picture-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/picture.svg') }}"
                                                    data-document-icon="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/document.svg') }}"
                                                    data-blank-thumbnail="{{ dynamicAsset(path: 'public/assets/back-end/img/file-placeholder.png') }}">
                                                </div>
                                                 <div class="document-upload-wrapper doc-upload-wrapper">
                                                     <input type="file" name="tin_certificate"
                                                            data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                                            data-validation-error-msg="{{ translate('File_size_is_too_large_Maximum_').' '.getFileUploadMaxSize(type: 'file').' '.'MB' }}"
                                                         class="document_input" accept=".pdf,.doc,.jpg">
                                                     <div class="textbox">
                                                         <img class="svg" alt=""
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/doc-upload-icon.svg') }}">
                                                         <p class="fs-12 mb-0">
                                                             {{ translate('Select_a_file_or') }}
                                                             <span class="font-weight-semibold">
                                                                 {{ translate('Drag_and_Drop_here') }}
                                                             </span>
                                                         </p>
                                                     </div>
                                                 </div>
                                             </div>
                                             <div class="d-flex flex-column gap-1 upload-img-content text-center mt-3">
                                                 <h6 class="text-uppercase mb-1">{{ translate('TIN_Certificate') }}
                                                 </h6>
                                                 <div class="text-muted text-capitalize">
                                                     {{ translate('pdf,_doc,_jpg._file_size_:_max_5_MB') }}
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         @php($recaptcha = getWebConfig(name: 'recaptcha'))

                         @if(isset($recaptcha) && $recaptcha['status'] == 1)
                             <div class="dynamic-default-and-recaptcha-section">
                                 <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="register"
                                        data-input="#login-default-captcha-section"
                                        data-default-captcha="#login-default-captcha-section"
                                 >

                                 <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                      data-placeholder="{{ translate('enter_captcha_value') }}"
                                      data-base-url="{{ route('g-recaptcha-session-store') }}"
                                      data-session="{{ 'vendorRecaptchaSessionKey' }}"
                                 >
                                 </div>
                             </div>
                         @else
                             <div class="default-captcha-container"
                                  data-placeholder="{{ translate('enter_captcha_value') }}"
                                  data-base-url="{{ route('g-recaptcha-session-store') }}"
                                  data-session="{{ 'vendorRecaptchaSessionKey' }}"
                             >
                             </div>
                         @endif

                         <div class="col-12">
                             <label class="custom-checkbox align-items-center">
                                 <input type="checkbox" class="form-check-input" id="terms-checkbox">
                                 <span class="form-check-label">
                                     {{ translate('i_agree_with_the') }}
                                     <a href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}"
                                         target="_blank" class="text-decoration-underline color-bs-primary-force">
                                         {{ translate('terms_&_conditions') }}
                                     </a>
                                 </span>
                             </label>
                         </div>
                         <div class="d-flex justify-content-end mt-4 mb-2 gap-2">
                             <button type="button" class="btn btn-secondary back-to-main-page">
                                 {{ translate('back') }}
                             </button>
                             <button type="submit" class="btn btn-primary disabled" id="vendor-apply-submit">
                                 {{ translate('submit') }}
                             </button>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>
