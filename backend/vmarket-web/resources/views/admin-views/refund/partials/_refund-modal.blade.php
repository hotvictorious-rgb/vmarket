@php
    $modalPayInfo = json_decode($refund->payment_info ?? '{}', true) ?: [];
    $modalRefundableMoney = $modalPayInfo['refundable_money_amount'] ?? ($modalPayInfo['money_amount'] ?? ($refund->amount ?? '0.00'));
@endphp
<div class="modal fade" id="refundModal-{{ $refund['id'] }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.refund-section.refund.refund-status-update') }}" method="post"
                  enctype="multipart/form-data" id="submit-refund-form-{{$refund['id']}}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" value="{{ $refund->id}}">
                    <input type="hidden" name="refund_status" value="refunded">
                    <div class="text-center">
                        <img class="mb-3"
                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/refund-approve.png') }}"
                             alt="{{ translate('refund_approve') }}">
                        <h4 class="mb-4 mx-auto max-w-283">
                            {{ translate('confirm_offline_manual_refund_payment') }}
                        </h4>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="amount">{{ translate('transferred_amount') }} (₦)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="amount-{{ $refund['id'] }}"
                               value="{{ $modalRefundableMoney }}" required>
                        <small class="text-muted">{{ translate('must_equal_the_exact_refundable_money_amount') }}: ₦{{ number_format((float)$modalRefundableMoney, 2) }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="">{{ translate('payment_method') }}</label>
                        <div class="select-wrapper">
                            <select class="form-select" name="payment_method" required>
                                <option value="bank_transfer">{{ translate('bank_transfer') }}</option>
                                <option value="manual_offline">{{ translate('manual_offline') }}</option>
                                <option value="cash">{{ translate('cash') }}</option>
                                <option value="pos_card">{{ translate('pos_card') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="">{{ translate('payment_reference') }} / {{ translate('transfer_id') }}</label>
                        <input type="text" class="form-control" name="payment_reference"
                               placeholder="{{ translate('ex').' : '.'NIBSS_SESSION_ID_123456' }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="">{{ translate('payment_date') }}</label>
                        <input type="date" class="form-control" name="payment_date"
                               value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="">{{ translate('supporting_evidence') }} ({{ translate('receipt') }})</label>
                        <input type="file" class="form-control" name="payment_evidence" accept="image/jpeg,image/png,image/jpg,application/pdf">
                        <small class="text-muted">{{ translate('optional_supported_formats') }}: JPG, PNG, PDF (Max: 5MB)</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="">{{ translate('additional_notes') }}</label>
                        <input type="text" class="form-control" name="payment_info"
                               placeholder="{{ translate('optional_internal_notes') }}">
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3 mt-3">
                        <button type="button" class="btn btn-secondary px-3"
                                data-bs-dismiss="modal">{{ translate('close') }}</button>
                        <button type="button" class="btn btn-primary form-submit" data-form-id="submit-refund-form-{{$refund['id']}}"
                                data-message="{{ translate('want_to_refund_this_refund_request').'?' }}"
                                data-redirect-route="{{ route('admin.refund-section.refund.list', ['status'=>$refund['status']]) }}">
                            {{ translate('submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
