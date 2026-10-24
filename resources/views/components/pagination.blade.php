@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between mt-4">
        <p class="text-sm text-slate-500">
            共 {{ $paginator->total() }} 条 · 第 {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} 页
        </p>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-slate-300 bg-slate-50 rounded-md">上一页</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="px-3 py-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50">上一页</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="px-3 py-1.5 text-sm text-slate-600 bg-white border border-slate-200 rounded-md hover:bg-slate-50">下一页</a>
            @else
                <span class="px-3 py-1.5 text-sm text-slate-300 bg-slate-50 rounded-md">下一页</span>
            @endif
        </div>
    </div>
@endif
