<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6">
        <div>
            <flux:heading size="xl">مرحباً {{ auth()->user()->name }}</flux:heading>
            <flux:subheading>اختر أداة المطعم للمتابعة.</flux:subheading>
        </div>

        @if(auth()->user()->restaurant)
            <div class="grid gap-4 sm:grid-cols-2">
                @can('access-pos', auth()->user()->restaurant->slug)
                    <flux:card class="flex flex-col gap-4">
                        <flux:heading>نقطة البيع</flux:heading>
                        <flux:text>إنشاء الطلبات وإدارة حسابات العملاء.</flux:text>
                        <flux:button
                            variant="primary"
                            :href="route('pos.index', ['slug' => auth()->user()->restaurant->slug])"
                        >
                            فتح نقطة البيع
                        </flux:button>
                    </flux:card>
                @endcan

                @can('access-kds')
                    <flux:card class="flex flex-col gap-4">
                        <flux:heading>شاشة المطبخ</flux:heading>
                        <flux:text>متابعة الطلبات وحالات التحضير.</flux:text>
                        <flux:button
                            :href="route('kitchen.display', ['slug' => auth()->user()->restaurant->slug])"
                        >
                            فتح شاشة المطبخ
                        </flux:button>
                    </flux:card>
                @endcan
            </div>
        @else
            <flux:callout variant="warning">
                لا يوجد مطعم مرتبط بهذا الحساب.
            </flux:callout>
        @endif
    </div>
</x-layouts::app>
