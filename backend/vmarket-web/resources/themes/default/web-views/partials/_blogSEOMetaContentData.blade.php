@if(isset($blogDetails) && isset($metaContentData))
    @if($metaContentData?->title)
        <meta name="title" content="{{ $metaContentData?->title }}">
        <meta property="og:title" content="{{ $metaContentData?->title }}">
        <meta name="twitter:title" content="{{ $metaContentData?->title }}">
    @else
        <meta name="title" content="{{ $blogDetails?->name }}">
        <meta property="og:title" content="{{ $blogDetails?->name }}">
        <meta name="twitter:title" content="{{ $blogDetails?->name }}">
    @endif

    @if($metaContentData?->description)
        <meta name="description" content="{!! Str::limit($metaContentData?->description, 160) !!}">
        <meta property="og:description" content="{!! Str::limit($metaContentData?->description, 160) !!}">
        <meta name="twitter:description" content="{!! Str::limit($metaContentData?->description, 160) !!}">
    @else
        <meta name="description" content="@foreach(explode(' ',$blogDetails['name']) as $keyword) {{$keyword.' , '}} @endforeach">
        <meta property="og:description" content="@foreach(explode(' ',$blogDetails['name']) as $keyword) {{$keyword.' , '}} @endforeach">
        <meta name="twitter:description" content="@foreach(explode(' ',$blogDetails['name']) as $keyword) {{$keyword.' , '}} @endforeach">
    @endif

    <meta name="keywords" content="@foreach(explode(' ',$blogDetails['name']) as $keyword) {{$keyword.' , '}} @endforeach">

    <meta name="author" content="{{ $blogDetails->writer }}">

    <meta property="og:image" content="{{ $metaContentData?->image_full_url['path'] }}">
    <meta name="twitter:image" content="{{ $metaContentData?->image_full_url['path'] }}">

    <meta property="og:url" content="{{ route('frontend.blog.details', [$blogDetails->slug]) }}">
    <meta name="twitter:url" content="{{ route('frontend.blog.details', [$blogDetails->slug]) }}">

    @if($metaContentData?->index != 'noindex')
        <meta name="robots" content="index">
    @endif

    @if($metaContentData?->no_follow || $metaContentData?->no_image_index || $metaContentData?->no_archive || $metaContentData?->no_snippet)
        <meta name="robots" content="{{ ($metaContentData?->no_follow ? 'nofollow' : '') . ($metaContentData?->no_image_index ? ' noimageindex' : '') . ($metaContentData?->no_archive ? ' noarchive' : '') . ($metaContentData?->no_snippet ? ' nosnippet' : '') }}">
    @endif

    @if($metaContentData?->meta_max_snippet)
        <meta name="robots" content="max-snippet{{ $metaContentData?->max_snippet_value ? ': ' . $metaContentData?->max_snippet_value : '' }}">
    @endif

    @if($metaContentData?->max_video_preview)
        <meta name="robots" content="max-video-preview{{ $metaContentData?->max_video_preview_value ? ': ' . $metaContentData?->max_video_preview_value : '' }}">
    @endif

    @if($metaContentData?->max_image_preview)
        <meta name="robots" content="max-image-preview{{ $metaContentData?->max_image_preview_value ? ': ' . $metaContentData?->max_image_preview_value : '' }}">
    @endif
    @foreach($blogDetails->translations->unique('locale') as $translation)
        <link rel="alternate" type="text/html" hreflang="{{ getLanguageCode(country_code: $translation->locale)  }}"
              href="{{ route('frontend.blog.details', ['slug' => $blogDetails->slug, 'locale' => $translation->locale]) }}" title="{{ $blogDetails->title }}"/>
    @endforeach

    {{-- Google Schema.org Article / BlogPosting JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "{{ route('frontend.blog.details', [$blogDetails->slug]) }}"
        },
        "headline": "{{ addslashes($metaContentData?->title ?: ($blogDetails->title ?? $blogDetails->name ?? 'Victorious Blog')) }}",
        "description": "{{ addslashes(Str::limit(strip_tags($metaContentData?->description ?: ($blogDetails->description ?? '')), 200)) }}",
        "image": [
            "{{ $metaContentData?->image_full_url['path'] ?? (isset($blogDetails->thumbnail_full_url) ? (is_array($blogDetails->thumbnail_full_url) ? ($blogDetails->thumbnail_full_url['path'] ?? '') : $blogDetails->thumbnail_full_url) : asset('public/assets/front-end/img/image-place-holder.png')) }}"
        ],
        "datePublished": "{{ isset($blogDetails->created_at) ? $blogDetails->created_at->toIso8601String() : now()->toIso8601String() }}",
        "dateModified": "{{ isset($blogDetails->updated_at) ? $blogDetails->updated_at->toIso8601String() : now()->toIso8601String() }}",
        "author": {
            "@type": "Person",
            "name": "{{ addslashes($blogDetails->writer ?? 'Victorious Editorial') }}"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Victorious MARKET",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ asset('public/assets/front-end/img/logo.png') }}"
            }
        }
    }
    </script>
@endif


