@php
    $url = $record->qrUrl();
@endphp

<div class="flex items-center justify-center">
    <x-offline-qr-code
        :value="$url"
        :alt="'QR Code للطاولة '.$record->number"
        class="rounded border border-gray-200 bg-white p-1 dark:border-gray-700"
    />
</div>
<p class="mt-1 text-center text-xs text-gray-500">طاولة {{ $record->number }}</p>