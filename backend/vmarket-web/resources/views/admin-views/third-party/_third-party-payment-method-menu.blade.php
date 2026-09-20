<div class="position-relative nav--tab-wrapper mb-4">
    <ul class="nav nav-pills nav--tab" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/third-party/payment-method') ?'active':'' }}"
               href="{{ route('admin.third-party.payment-method.index') }}">
                {{ translate('digital_payment') }}
            </a>
        </li>

        {{-- [AI] Directive 57326: offline_payment tab removed — V1 only supports Paystack (digital). --}}
    </ul>
    <div class="nav--tab__prev">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-left"></i>
        </button>
    </div>
    <div class="nav--tab__next">
        <button type="button" class="btn btn-circle border-0 bg-white text-primary">
            <i class="fi fi-sr-angle-right"></i>
        </button>
    </div>

</div>
