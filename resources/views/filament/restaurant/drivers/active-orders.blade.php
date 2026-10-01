<div class="overflow-x-auto">
    @if ($orders->isEmpty())
        <p class="py-6 text-center text-sm text-gray-500">لا توجد طلبات نشطة لهذا السائق حالياً.</p>
    @else
        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead>
                <tr class="text-right">
                    <th class="px-3 py-2 font-semibold">رقم الطلب</th>
                    <th class="px-3 py-2 font-semibold">العميل</th>
                    <th class="px-3 py-2 font-semibold">الحالة</th>
                    <th class="px-3 py-2 font-semibold">تاريخ الطلب</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($orders as $order)
                    <tr>
                        <td class="px-3 py-2">{{ $order->id }}</td>
                        <td class="px-3 py-2">{{ $order->customer_name }}</td>
                        <td class="px-3 py-2">{{ $order->status }}</td>
                        <td class="px-3 py-2">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
