@extends('layouts.admin')
@section('title', '账本核对')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">账本核对</h3>
            <a href="{{ route('admin.api-calls.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回列表</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <h4 class="text-sm font-medium text-slate-900 mb-3">调用详情</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-slate-500">Request ID</p>
                    <p class="text-slate-900">{{ e($apiCall->request_id) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">用户 ID</p>
                    <p class="text-slate-900">{{ $apiCall->user_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">技能</p>
                    <p class="text-slate-900">{{ e((string) ($apiCall->product_name ?? '-')) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">消耗金额（毫）</p>
                    <p class="text-slate-900">{{ $apiCall->cost }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">状态码</p>
                    <p class="text-slate-900">{{ $apiCall->status_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">耗时</p>
                    <p class="text-slate-900">{{ $apiCall->duration_ms }}ms</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">调用时间</p>
                    <p class="text-slate-900">{{ e((string) $apiCall->created_at) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4">
            <h4 class="text-sm font-medium text-slate-900 mb-3">账本分录（biz_type=api_call, biz_id={{ $apiCall->id }}）</h4>
            @if ($entries->isEmpty())
                <p class="text-sm text-slate-500">暂无关联账本记录</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs text-slate-500">
                            <th class="pb-2 pr-4">ID</th>
                            <th class="pb-2 pr-4">交易 ID</th>
                            <th class="pb-2 pr-4">账户 ID</th>
                            <th class="pb-2 pr-4">方向</th>
                            <th class="pb-2 pr-4">金额（毫）</th>
                            <th class="pb-2 pr-4">时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4">{{ $entry->id }}</td>
                                <td class="py-2 pr-4">{{ $entry->transaction_id }}</td>
                                <td class="py-2 pr-4">{{ $entry->account_id }}</td>
                                <td class="py-2 pr-4">
                                    @if ($entry->direction === 'credit')
                                        <span class="text-emerald-600">贷（入）</span>
                                    @else
                                        <span class="text-red-600">借（出）</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">{{ $entry->amount }}</td>
                                <td class="py-2 pr-4">{{ e((string) $entry->created_at) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
