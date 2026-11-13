@extends('layouts.admin')
@section('title', '编辑技能')
@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">编辑技能</h2>
            <a href="{{ route('admin.skills.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">返回列表</a>
        </div>
        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.skills.update', $skillDetail->id) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">技能名称</label>
                <input type="text" name="name" value="{{ old('name', $skillDetail->product->name) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">描述</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">{{ old('description', $skillDetail->product->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">价格 <span class="text-slate-400">（元，1元=1000毫，自动转换）</span></label>
                <input type="number" step="0.001" min="0.001" name="price" value="{{ old('price', number_format($skillDetail->product->price / 1000, 3)) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">AI 供应商</label>
                <select name="provider_id" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">
                    <option value="">默认路由</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" {{ old('provider_id', $skillDetail->provider_id) == $provider->id ? 'selected' : '' }}>{{ e($provider->display_name) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">System Prompt</label>
                <textarea name="system_prompt" rows="6" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm font-mono">{{ old('system_prompt', $skillDetail->system_prompt) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Endpoint</label>
                    <input type="text" name="endpoint" value="{{ old('endpoint', $skillDetail->endpoint) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm" placeholder="/v1/chat/completions">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">HTTP Method</label>
                    <select name="method" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">
                        @foreach (['POST', 'GET', 'PUT', 'PATCH'] as $m)
                            <option value="{{ $m }}" {{ old('method', $skillDetail->method) == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">定价类型</label>
                    <select name="pricing_type" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">
                        <option value="1" {{ old('pricing_type', $skillDetail->pricing_type) == '1' ? 'selected' : '' }}>按次</option>
                        <option value="2" {{ old('pricing_type', $skillDetail->pricing_type) == '2' ? 'selected' : '' }}>按量</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">免费额度</label>
                    <input type="number" min="0" name="free_quota" value="{{ old('free_quota', $skillDetail->free_quota) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">
                </div>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">保存修改</button>
            </div>
        </form>
    </div>
@endsection
