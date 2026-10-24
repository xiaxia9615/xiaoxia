@props(['headers' => [], 'rows' => [], 'empty' => '暂无数据'])

<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                <tr class="hover:bg-slate-50">
                    @foreach ($row as $cell)
                        <td class="px-4 py-3 text-sm text-slate-700">{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-8 text-center text-sm text-slate-400" colspan="{{ count($headers) }}">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
