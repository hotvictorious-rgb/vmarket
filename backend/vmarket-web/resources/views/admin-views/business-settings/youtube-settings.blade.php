@extends('layouts.admin.app')

@section('title', translate('YouTube_Integration_Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                {{ translate('Third_Party_Setup') }}
            </h2>
        </div>

        @include('admin-views.third-party._third-party-others-menu')

        @php
            $clientId = getWebConfig('youtube_client_id');
            $clientSecret = getWebConfig('youtube_client_secret');
            $refreshToken = getWebConfig('youtube_refresh_token');
        @endphp

        <div class="card">
            <div class="card-body">
                <div class="p-12 p-sm-20 bg-section rounded mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="text-capitalize">{{ translate('YouTube_Video_Upload_Integration') }}</h2>
                            <p class="mb-0 text-muted">
                                {{ translate('Configure Google OAuth2 credentials to allow vendors to upload product videos directly to your store’s YouTube channel.') }}
                            </p>
                        </div>
                        <div>
                            @if($refreshToken)
                                <span class="badge bg-success p-2 fs-12 d-flex align-items-center gap-2">
                                    <span class="dot bg-white"></span> {{ translate('YouTube_Connected') }}
                                </span>
                            @else
                                <span class="badge bg-danger p-2 fs-12 d-flex align-items-center gap-2">
                                    <span class="dot bg-white"></span> {{ translate('YouTube_Disconnected') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <form action="{{ route('admin.third-party.youtube-integration.setup') }}" method="post">
                            @csrf
                            <h4 class="mb-3">{{ translate('API_Credentials') }}</h4>
                            <div class="form-group mb-3">
                                <label class="form-label text-capitalize">{{ translate('Google_Client_ID') }}</label>
                                <input type="text" class="form-control" name="youtube_client_id" value="{{ $clientId }}" required placeholder="{{ translate('Enter_Google_Client_ID') }}">
                            </div>
                            <div class="form-group mb-4">
                                <label class="form-label text-capitalize">{{ translate('Google_Client_Secret') }}</label>
                                <input type="password" class="form-control" name="youtube_client_secret" value="{{ $clientSecret }}" required placeholder="{{ translate('Enter_Google_Client_Secret') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-sr-disk mr-1"></i> {{ translate('Save_Credentials') }}
                            </button>
                        </form>
                    </div>

                    <div class="col-md-6 border-start-md">
                        <h4 class="mb-3">{{ translate('Channel_Authorization') }}</h4>
                        <p class="text-muted">
                            {{ translate('After saving your Client ID and Client Secret, click the button below to log in and authorize your website to upload videos to your YouTube channel.') }}
                        </p>
                        
                        <div class="mt-4">
                            @if($clientId && $clientSecret)
                                <a href="{{ route('admin.third-party.youtube-integration.connect') }}" class="btn btn-info text-white">
                                    <i class="fi fi-sr-link mr-1"></i> {{ $refreshToken ? translate('Reconnect_YouTube_Channel') : translate('Connect_YouTube_Channel') }}
                                </a>
                            @else
                                <button class="btn btn-info text-white" disabled>
                                    <i class="fi fi-sr-link mr-1"></i> {{ translate('Connect_YouTube_Channel') }}
                                </button>
                                <p class="text-danger fs-12 mt-2">
                                    {{ translate('Please enter and save Google API credentials to enable authorization.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-info bg-opacity-10 fs-12 p-3 mt-4 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-bulb text-info fs-16"></i>
                    <span>
                        {{ translate('Important: When configuring your Google project Redirect URI, set it to: ') }}
                        <code class="bg-white px-2 py-1 rounded select-all">{{ route('admin.youtube.callback') }}</code>
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
