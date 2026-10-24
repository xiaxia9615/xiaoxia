@props(['status' => 'ok', 'label' => null])

@php
    $map = [
        'ok'      => ['color' => 'bg-emerald-500',  'text' => $label ?? '正常'],
        'warn'    => ['color' => 'bg-amber-500',    'text' => $label ?? '关注'],
        'down'    => ['color' => 'bg-red-500',       'text' => $label ?? '异常'],
        'unknown' => ['color' => 'bg-slate-300',    'text' => $label ?? '未知'],
    ];
    $badge = $map[$status] ?? $map['unknown'];
@endphp

<span class="inline-flex items-center gap-1.5">
    <span class="w-2 h-2 rounded-full {{ $badge['color'] }}"></span>
    <span class="text-sm text-slate-600">{{ $badge['text'] }}</span>
</span>
