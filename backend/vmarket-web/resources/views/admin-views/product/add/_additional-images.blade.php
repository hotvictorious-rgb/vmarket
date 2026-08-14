<div class="additional-image-column-section">
    <div class="card card-body h-100">
        <label class="form-label fw-semibold mb-2">{{ translate('Add_Photos') }}</label>
        <div class="d-flex flex-column bg-section rounded-10" id="additional_Image_Section">
            <div class="position-relative">
                <div class="multi_image_picker d-flex gap-20 p-3"
                     data-ratio="1/1"
                     data-max-filesize="{{getFileUploadMaxSize()}}"
                     data-field-name="images[]"
                     data-required="true"
                     data-required-msg="{{ translate('additional_image_is_required') }}"
                     data-allowed-formats="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                     data-validation-error-msg="{{ translate('File_size_is_too_large_Maximum_').' '.getFileUploadMaxSize().' '.'MB' }}"
                >
                    <div>
                        <div class="imageSlide_prev">
                            <div
                                class="d-flex justify-content-center align-items-center h-100">
                                <button
                                    type="button"
                                    class="btn btn-circle border-0 bg-primary text-white">
                                    <i class="fi fi-sr-angle-left"></i>
                                </button>
                            </div>
                        </div>
                        <div class="imageSlide_next">
                            <div
                                class="d-flex justify-content-center align-items-center h-100">
                                <button
                                    type="button"
                                    class="btn btn-circle border-0 bg-primary text-white">
                                    <i class="fi fi-sr-angle-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
