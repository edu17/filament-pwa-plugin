const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return
    }

    const serviceWorker = document.querySelector('meta[name="filament-pwa-service-worker"]')?.content
    const scope = document.querySelector('meta[name="filament-pwa-scope"]')?.content

    if (!serviceWorker || !scope) {
        return
    }

    navigator.serviceWorker.register(serviceWorker, {
        scope,
        updateViaCache: 'none',
    }).catch((error) => {
        console.error('Unable to register the Filament PWA service worker.', error)
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerServiceWorker, { once: true })
} else {
    registerServiceWorker()
}
