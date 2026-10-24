@props(['title', 'value', 'subtitle' => null])

<div class="bg-white shadow-sm rounded-lg p-5">
    <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
    @if ($subtitle)
        <p class="mt-1 text-xs text-slate-400">{{ $subtitle }}</p>
    @endif
</div>
