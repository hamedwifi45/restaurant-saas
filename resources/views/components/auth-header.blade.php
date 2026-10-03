@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h2 class="text-3xl font-black tracking-tight text-zinc-950">{{ $title }}</h2>
    <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $description }}</p>
</div>
