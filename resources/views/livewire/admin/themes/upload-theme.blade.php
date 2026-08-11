<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="container mx-auto px-4 py-8">
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                            رفع ثيم جديد
                        </span>
                    </h1>
                    <p class="text-gray-400">قم برفع ثيم جديد لمنصتك بسهولة وأمان</p>
                </div>
                <flux:button href="{{ route('themes.index') }}" variant="ghost" class="text-gray-400 hover:text-white">
                    <flux:icon.arrow-left class="w-5 h-5 ml-2" />
                    العودة للقائمة
                </flux:button>
            </div>
        </div>

        {{-- Progress Steps --}}
        <div class="mb-8">
            <div class="flex items-center justify-center">
                @foreach([1, 2, 3] as $step)
                    <div class="flex items-center">
                        <div class="relative">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full 
                                {{ $currentStep >= $step 
                                    ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-white' 
                                    : 'bg-gray-700 text-gray-400' }} 
                                transition-all duration-300">
                                @if($currentStep > $step)
                                    <flux:icon.check class="w-6 h-6" />
                                @else
                                    <span class="text-lg font-bold">{{ $step }}</span>
                                @endif
                            </div>
                            @if($step < 3)
                                <div class="absolute top-1/2 left-full w-16 h-0.5 -translate-y-1/2 
                                    {{ $currentStep > $step ? 'bg-gradient-to-r from-amber-400 to-orange-500' : 'bg-gray-700' }}">
                                </div>
                            @endif
                        </div>
                        <div class="ml-4 text-right">
                            <p class="text-sm font-medium {{ $currentStep >= $step ? 'text-white' : 'text-gray-500' }}">
                                @if($step === 1) رفع الملف والتحقق @endif
                                @if($step === 2) معاينة الثيم @endif
                                @if($step === 3) التأكيد والرفع @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Main Card --}}
        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden">
            <div class="p-8">
                {{-- Step 1: Upload and Validate --}}
                @if($currentStep === 1)
                    <div class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-white mb-2">اختر ملف الثيم</h2>
                            <p class="text-gray-400">يجب أن يكون الملف بصيغة ZIP ويحتوي على theme.json و layout.blade.php</p>
                        </div>

                        {{-- Drop Zone --}}
                        <div class="relative">
                            <input 
                                type="file" 
                                wire:model.live="themeZip" 
                                accept=".zip"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                            />
                            <div class="border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300
                                {{ $themeZip 
                                    ? 'border-amber-500 bg-amber-500/10' 
                                    : 'border-gray-600 hover:border-amber-500 hover:bg-gray-800/50' }}">
                                <div class="space-y-4">
                                    @if($themeZip)
                                        <flux:icon.archive-box class="w-16 h-16 mx-auto text-amber-500" />
                                        <p class="text-white font-medium">{{ $themeZip->getClientOriginalName() }}</p>
                                        <p class="text-gray-400 text-sm">{{ number_format($themeZip->getSize() / 1024, 2) }} KB</p>
                                    @else
                                        <flux:icon.cloud-arrow-up class="w-16 h-16 mx-auto text-gray-500" />
                                        <p class="text-gray-300 font-medium">اسحب الملف هنا أو انقر للاختيار</p>
                                        <p class="text-gray-500 text-sm">الحد الأقصى: 50 ميجابايت</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Validation Results --}}
                        @if(count($validationErrors) > 0)
                            <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-6">
                                <div class="flex items-start">
                                    <flux:icon.exclamation-circle class="w-6 h-6 text-red-500 ml-3 flex-shrink-0" />
                                    <div>
                                        <h3 class="text-red-400 font-bold mb-2">أخطاء التحقق:</h3>
                                        <ul class="space-y-1">
                                            @foreach($validationErrors as $error)
                                                <li class="text-red-300 text-sm">• {{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($validationSuccess)
                            <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-6">
                                <div class="flex items-start">
                                    <flux:icon.check-circle class="w-6 h-6 text-green-500 ml-3 flex-shrink-0" />
                                    <div>
                                        <h3 class="text-green-400 font-bold mb-2">تم التحقق بنجاح!</h3>
                                        <p class="text-green-300">الثيم صالح وجاهز للمعاينة والرفع</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Requirements Info --}}
                        <div class="bg-blue-500/10 border border-blue-500/50 rounded-xl p-6">
                            <h3 class="text-blue-400 font-bold mb-3">متطلبات الثيم:</h3>
                            <ul class="space-y-2 text-gray-300 text-sm">
                                <li class="flex items-center">
                                    <flux:icon.check class="w-4 h-4 text-blue-500 ml-2" />
                                    ملف theme.json في الجذر مع الحقول المطلوبة (name, author, version)
                                </li>
                                <li class="flex items-center">
                                    <flux:icon.check class="w-4 h-4 text-blue-500 ml-2" />
                                    default_settings كمصفوفة تحتوي على إعدادات الثيم الافتراضية
                                </li>
                                <li class="flex items-center">
                                    <flux:icon.check class="w-4 h-4 text-blue-500 ml-2" />
                                    allowed_variables كمصفوفة تحدد المتغيرات المسموحة
                                </li>
                                <li class="flex items-center">
                                    <flux:icon.check class="w-4 h-4 text-blue-500 ml-2" />
                                    ملف layout.blade.php الرئيسي
                                </li>
                                <li class="flex items-center">
                                    <flux:icon.check class="w-4 h-4 text-blue-500 ml-2" />
                                    صورة معاينة اختيارية (preview.png أو preview.jpg)
                                </li>
                            </ul>
                        </div>

                        <div class="flex justify-end">
                            <flux:button 
                                wire:click="nextStep" 
                                :disabled="!$validationSuccess"
                                class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white px-8 py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                                التالي
                                <flux:icon.arrow-right class="w-5 h-5 mr-2" />
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Preview --}}
                @if($currentStep === 2)
                    <div class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-white mb-2">معاينة الثيم</h2>
                            <p class="text-gray-400">راجع معلومات الثيم قبل الرفع</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            {{-- Preview Image --}}
                            @if($themePreview)
                                <div class="bg-gray-800/50 rounded-2xl p-6">
                                    <img src="{{ $themePreview }}" alt="Theme Preview" class="rounded-xl w-full h-auto shadow-lg" />
                                </div>
                            @else
                                <div class="bg-gray-800/50 rounded-2xl p-6 flex items-center justify-center">
                                    <div class="text-center">
                                        <flux:icon.image class="w-16 h-16 text-gray-600 mx-auto mb-3" />
                                        <p class="text-gray-500">لا توجد صورة معاينة</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Theme Info --}}
                            <div class="space-y-4">
                                <div class="bg-gray-800/50 rounded-xl p-4">
                                    <label class="text-gray-400 text-sm block mb-1">اسم الثيم</label>
                                    <p class="text-white font-bold text-lg">{{ $themeName }}</p>
                                </div>
                                
                                <div class="bg-gray-800/50 rounded-xl p-4">
                                    <label class="text-gray-400 text-sm block mb-1">المطور</label>
                                    <p class="text-white">{{ $themeAuthor }}</p>
                                </div>
                                
                                <div class="bg-gray-800/50 rounded-xl p-4">
                                    <label class="text-gray-400 text-sm block mb-1">الإصدار</label>
                                    <p class="text-white">{{ $themeVersion }}</p>
                                </div>
                                
                                @if($themeDescription)
                                    <div class="bg-gray-800/50 rounded-xl p-4">
                                        <label class="text-gray-400 text-sm block mb-1">الوصف</label>
                                        <p class="text-gray-300">{{ $themeDescription }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Default Settings Preview --}}
                        @if($themeJsonData && isset($themeJsonData['default_settings']))
                            <div class="bg-gray-800/50 rounded-2xl p-6">
                                <h3 class="text-white font-bold mb-4">الإعدادات الافتراضية:</h3>
                                <div class="grid md:grid-cols-3 gap-4">
                                    @foreach($themeJsonData['default_settings'] as $key => $value)
                                        <div class="bg-gray-900/50 rounded-lg p-3">
                                            <p class="text-gray-400 text-xs">{{ $key }}</p>
                                            @if(is_string($value) && str_starts_with($value, '#'))
                                                <div class="flex items-center mt-1">
                                                    <div class="w-4 h-4 rounded mr-2" style="background-color: {{ $value }}"></div>
                                                    <p class="text-white text-sm">{{ $value }}</p>
                                                </div>
                                            @else
                                                <p class="text-white text-sm">{{ is_bool($value) ? ($value ? 'نعم' : 'لا') : $value }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <flux:button 
                                wire:click="previousStep"
                                variant="ghost"
                                class="text-gray-400 hover:text-white">
                                <flux:icon.arrow-left class="w-5 h-5 ml-2" />
                                السابق
                            </flux:button>
                            
                            <flux:button 
                                wire:click="nextStep"
                                class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white px-8 py-3 rounded-xl">
                                التالي
                                <flux:icon.arrow-right class="w-5 h-5 mr-2" />
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Confirm and Upload --}}
                @if($currentStep === 3)
                    <div class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-white mb-2">تأكيد الرفع</h2>
                            <p class="text-gray-400">هل أنت متأكد من رفع هذا الثيم؟</p>
                        </div>

                        <div class="bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-500/50 rounded-2xl p-8">
                            <div class="flex items-center justify-center mb-6">
                                <flux:icon.archive-box class="w-20 h-20 text-amber-500" />
                            </div>
                            
                            <div class="text-center space-y-3 mb-6">
                                <h3 class="text-2xl font-bold text-white">{{ $themeName }}</h3>
                                <p class="text-gray-400">الإصدار: {{ $themeVersion }}</p>
                                <p class="text-gray-400">المطور: {{ $themeAuthor }}</p>
                            </div>

                            <div class="bg-gray-900/50 rounded-xl p-4 mb-6">
                                <p class="text-gray-300 text-sm text-center">
                                    سيتم نسخ ملفات الثيم إلى مجلد الثيمات وإنشاء سجل في قاعدة البيانات
                                </p>
                            </div>

                            <div class="flex items-center justify-center text-amber-400 text-sm">
                                <flux:icon.exclamation-triangle class="w-5 h-5 ml-2" />
                                تأكد من أن الثيم قد تم اختباره جيداً قبل الرفع
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <flux:button 
                                wire:click="previousStep"
                                variant="ghost"
                                class="text-gray-400 hover:text-white">
                                <flux:icon.arrow-left class="w-5 h-5 ml-2" />
                                السابق
                            </flux:button>
                            
                            <flux:button 
                                wire:click="uploadTheme"
                                wire:loading.attr="disabled"
                                class="bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white px-8 py-3 rounded-xl">
                                <flux:icon.cloud-arrow-up class="w-5 h-5 ml-2" wire:loading.remove />
                                <flux:spinner class="w-5 h-5 ml-2" wire:loading />
                                تأكيد ورفع الثيم
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Help --}}
        <div class="mt-8 bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
            <h3 class="text-white font-bold mb-4 flex items-center">
                <flux:icon.light-bulb class="w-6 h-6 text-amber-500 ml-2" />
                نصائح سريعة
            </h3>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-gray-800/50 rounded-xl p-4">
                    <h4 class="text-amber-400 font-bold mb-2">📦 تحضير الملف</h4>
                    <p class="text-gray-400 text-sm">تأكد من أن ملف ZIP يحتوي على جميع الملفات الضرورية في الجذر</p>
                </div>
                <div class="bg-gray-800/50 rounded-xl p-4">
                    <h4 class="text-amber-400 font-bold mb-2">🎨 المعاينة</h4>
                    <p class="text-gray-400 text-sm">أضف صورة preview.png لجعل الثيم أكثر جاذبية</p>
                </div>
                <div class="bg-gray-800/50 rounded-xl p-4">
                    <h4 class="text-amber-400 font-bold mb-2">⚙️ الإعدادات</h4>
                    <p class="text-gray-400 text-sm">حدد allowed_variables بعناية للسماح بتخصيص آمن</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('alert', (data) => {
                alert(data[0].message);
            });
        });
    </script>
</div>
