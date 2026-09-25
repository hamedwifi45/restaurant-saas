@props(['restaurant'])

@php
    $settings = $restaurant?->themeSetting?->settings ?? [];
    $allowedVars = $restaurant?->theme?->allowed_variables ?? [];
@endphp

<style id="dynamic-theme-vars">
    :root {
        @foreach($allowedVars as $key => $config)
            @if(!empty($config['css_var']))
                {{ $config['css_var'] }}: {{ $settings[$key] ?? $config['default'] ?? 'inherit' }};
            @endif
        @endforeach
    }
</style>

{{-- الاستماع للتحديثات اللحظية القادمة من Filament عبر الـ Iframe --}}
<script>
    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'THEME_VARIABLE_CHANGE') {
            const { cssVar, value } = event.data;
            if (cssVar) {
                document.documentElement.style.setProperty(cssVar, value);
            }
        }
    });
</script>