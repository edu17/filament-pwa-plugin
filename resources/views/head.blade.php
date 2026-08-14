<link rel="manifest" href="{{ $panel->route('pwa.manifest') }}">
<meta name="theme-color" content="{{ $plugin->getThemeColor($panel) }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $plugin->getShortName($panel) }}">
<meta name="filament-pwa-service-worker" content="{{ $panel->route('pwa.service-worker') }}">
<meta name="filament-pwa-scope" content="{{ $plugin->getScope($panel) }}">

@if ($appleTouchIcon = $plugin->getAppleTouchIcon($panel))
    <link rel="apple-touch-icon" href="{{ $appleTouchIcon }}">
@endif
