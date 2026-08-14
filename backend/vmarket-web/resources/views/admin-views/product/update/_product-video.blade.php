<div class="card card-body mt-3 rest-part">
    <div>
        <h3 class="mb-1">{{ translate('Product_Video') }}</h3>
        <p class="fs-12 mb-3">
            {{ translate('Paste a YouTube embed link.') }}
        </p>
    </div>
    <div class="bg-section rounded-10 p-12 p-sm-20">
        <!-- Pasted Link Section -->
        <div id="video_link_section">
            <div class="mb-3">
                <label class="form-label mb-0">
                    {{ translate('youtube_video_link') }}
                </label>
                <span> ({{ translate('optional') }})</span>
            </div>
            <input type="text" name="video_url" id="video_url_input"
                   value="{{ isset($product) ? $product['video_url'] : '' }}"
                   placeholder="{{ translate('ex').': https://www.youtube.com/embed/5R06LRdUCSE' }}"
                   class="form-control">
            <p class="mt-1 mb-0 fs-12 text-muted">{{ translate('please_provide_embed_link_not_direct_link.') }}</p>
        </div>
    </div>
</div>
