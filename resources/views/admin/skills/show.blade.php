@extends('layouts.admin')
@section('title', "技能 #{$skill->id}")
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">技能 #{{ $skill->id }}</h3>
            <a href="{{ route('admin.skills.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回列表</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h4 class="text-sm font-semibold text-slate-900 mb-4">基本信息</h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">产品</dt>
                        <dd class="text-slate-900">{{ $skill->product_name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Endpoint</dt>
                        <dd class="text-slate-900">{{ $skill->endpoint }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">方法</dt>
                        <dd class="text-slate-900">{{ $skill->method }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">定价类型</dt>
                        <dd class="text-slate-900">{{ $skill->pricing_type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">API Key</dt>
                        <dd class="text-slate-900"><code>{{ $skill->masked_api_key }}</code></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">免费额度</dt>
                        <dd class="text-slate-900">{{ $skill->free_quota }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h4 class="text-sm font-semibold text-slate-900 mb-4">分账比例</h4>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Provider</dt>
                        <dd class="text-slate-900">{{ $skill->provider_share }}%</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Platform</dt>
                        <dd class="text-slate-900">{{ $skill->platform_share }}%</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Influencer</dt>
                        <dd class="text-slate-900">{{ $skill->influencer_share }}%</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Donation</dt>
                        <dd class="text-slate-900">{{ $skill->donation_share }}%</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($skill->request_template)
        <details class="bg-white rounded-lg shadow-sm p-4">
            <summary class="text-sm font-medium text-slate-700 cursor-pointer">request_template</summary>
            <pre class="mt-3 text-xs text-slate-600 overflow-x-auto">{{ json_encode($skill->request_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif

        @if($skill->config)
        <details class="bg-white rounded-lg shadow-sm p-4">
            <summary class="text-sm font-medium text-slate-700 cursor-pointer">config</summary>
            <pre class="mt-3 text-xs text-slate-600 overflow-x-auto">{{ json_encode($skill->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
        @endif
    </div>
@endsection
