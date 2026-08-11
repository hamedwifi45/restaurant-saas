<x-filament-panels::page>
    <div class="fi-resource-upload-theme">
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">
                📦 رفع ثيم جديد
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                قم برفع ملف ZIP يحتوي على الثيم الخاص بك. يجب أن يطابق الثيم المعايير التالية:
            </p>
            <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                <li>ملف <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">theme.json</code> في المجلد الرئيسي</li>
                <li>ملف <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">layout.blade.php</code></li>
                <li>مجلد <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">pages/</code> يحتوي على: home, menu, product</li>
                <li>مجلد <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">cart/</code> يحتوي على: cart, checkout, success</li>
                <li>مجلد <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">orders/</code> يحتوي على: track, track-form, invoice</li>
                <li>مجلد <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">reviews/</code> يحتوي على: reviews, review-form</li>
            </ul>
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
