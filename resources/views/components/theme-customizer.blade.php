@props(['allowedVariables', 'currentSettings'])

<div class="p-6 border-b border-[var(--border)] sticky top-0 bg-white z-10 flex items-center justify-between">
    <div>
        <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)]">تخصيص المظهر</h3>
        <p class="text-xs text-[var(--muted)] mt-1">عدّل الألوان والخطوط وشاهد النتيجة فوراً</p>
    </div>
    <button onclick="toggleThemeCustomizer()" class="w-8 h-8 rounded-lg bg-[var(--cream)] hover:bg-[var(--cream-dark)] flex items-center justify-center transition">
        <i class="fas fa-times text-[var(--muted)]"></i>
    </button>
</div>

<form id="theme-settings-form" class="p-6 space-y-6 pb-24">
    @csrf
    @foreach($allowedVariables as $key => $config)
        @php
            $value = $currentSettings[$key] ?? $config['default'] ?? '';
            $label = $config['label'] ?? $key;
            $type = $config['type'] ?? 'text';
        @endphp

        <div class="space-y-2">
            <label class="text-xs font-bold text-[var(--primary)] uppercase tracking-wide">{{ $label }}</label>
            
            @if($type === 'color')
                <div class="flex items-center gap-3">
                    <input type="color" name="settings[{{ $key }}]" value="{{ $value }}" 
                           class="w-10 h-10 rounded-lg cursor-pointer border-0 bg-transparent" 
                           onchange="previewThemeChange('{{ $key }}', this.value); document.getElementById('text_{{ $key }}').value = this.value">
                    <input type="text" id="text_{{ $key }}" value="{{ $value }}" 
                           class="flex-1 px-3 py-2 rounded-lg bg-[var(--cream)] text-sm font-mono text-[var(--primary)]" 
                           onchange="previewThemeChange('{{ $key }}', this.value); this.previousElementSibling.value = this.value">
                </div>

            @elseif($type === 'select')
                <select name="settings[{{ $key }}]" onchange="previewThemeChange('{{ $key }}', this.value)" 
                        class="w-full px-4 py-2.5 rounded-lg bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm text-[var(--primary)]">
                    @foreach($config['options'] as $optValue => $optLabel)
                        <option value="{{ $optValue }}" {{ $value == $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                    @endforeach
                </select>

            @elseif($type === 'boolean')
                <label class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)] cursor-pointer">
                    <span class="text-sm text-[var(--primary)]">تفعيل</span>
                    <input type="hidden" name="settings[{{ $key }}]" value="0">
                    <input type="checkbox" name="settings[{{ $key }}]" value="1" {{ $value ? 'checked' : '' }} 
                           onchange="previewThemeChange('{{ $key }}', this.checked ? '1' : '0')"
                           class="w-5 h-5 accent-[var(--secondary)] rounded">
                </label>

            @else
                <input type="{{ $type }}" name="settings[{{ $key }}]" value="{{ $value }}" 
                       oninput="previewThemeChange('{{ $key }}', this.value)"
                       class="w-full px-4 py-2.5 rounded-lg bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm text-[var(--primary)]">
            @endif
        </div>
    @endforeach

    <div class="fixed bottom-0 left-0 right-0 p-6 bg-white border-t border-[var(--border)] flex gap-3 z-20">
        <button type="button" onclick="resetTheme()" class="flex-1 py-3 rounded-xl bg-[var(--cream)] text-[var(--primary)] text-sm font-bold hover:bg-[var(--cream-dark)] transition">
            <i class="fas fa-undo ml-1"></i> إعادة تعيين
        </button>
        <button type="submit" id="saveThemeBtn" class="flex-1 py-3 rounded-xl btn-gold text-sm font-bold">
            <i class="fas fa-save ml-1"></i> حفظ الإعدادات
        </button>
    </div>
</form>

<script>
// 1. تحميل الإعدادات الأولية من Laravel
window.initialThemeSettings = @json($currentSettings ?? []);

function previewThemeChange(key, value) {
    // معاينة حية للألوان
    if (key.includes('color')) {
        const cssVar = key.replace('_', '-');
        document.documentElement.style.setProperty(`--${cssVar}`, value);
    }
    // معاينة حية للخطوط
    if (key === 'font_family') {
        document.documentElement.style.setProperty('--font-main', `'${value}', sans-serif`);
    }
}

// 2. حفظ الإعدادات عبر API
document.getElementById('theme-settings-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveThemeBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> جاري الحفظ...';
    btn.disabled = true;

    const formData = new FormData(this);
    const settings = {};
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('settings[')) {
            const settingKey = key.match(/settings\[(.*?)\]/)[1];
            settings[settingKey] = value;
        }
    }

    try {
        const response = await fetch('/restaurant/api/theme/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ settings })
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `Request failed with status ${response.status}.`);
        }
        if (data.success) {
            showToast(data.message);
        }
    } catch (error) {
        showToast('حدث خطأ أثناء الحفظ', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// 3. إعادة التعيين
function resetTheme() {
    if(confirm('هل أنت متأكد من إعادة جميع الإعدادات للقيم الافتراضية؟')) {
        window.initialThemeSettings = {}; // أو جلبها من الـ default في Laravel
        location.reload();
    }
}
</script>