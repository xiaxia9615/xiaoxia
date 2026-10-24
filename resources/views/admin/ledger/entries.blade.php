@extends('layouts.admin')

@section('title', "账户 #{$accountId} 流水")

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">账户 #{{ $accountId }} 流水</h3>
            @if(Route::has('admin.ledger.index'))
                <a href="{{ route('admin.ledger.index') }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800">返回账户列表</a>
            @endif
        </div>

        <x-table
            :headers="['时间', '交易ID', '方向', '金额', '业务类型', '业务ID']"
            :rows="$rows"
            empty="暂无流水" />
        {{ $entries->links() }}
    </div>
@endsection
