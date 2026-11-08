@extends('layouts.admin')
@section('title', '编辑商品')
@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">编辑商品</h2>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">返回列表</a>
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
        <form method="POST" action="{{ route('admin.products.update', $product->id) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">名称</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">描述</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">{{ old('description', $product->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">价格 <span class="text-slate-400">（1元=1000毫）</span></label>
                <input type="number" name="price" value="{{ old('price', $product->price) }}" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">类型</label>
                <select name="type" class="w-full px-3 py-2 border border-slate-300 rounded-md text-sm">
                    <option value="1" {{ old('type', $product->type) == 1 ? 'selected' : '' }}>技能</option>
                    <option value="2" {{ old('type', $product->type) == 2 ? 'selected' : '' }}>商品</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">当前状态</label>
                <span class="px-2 py-0.5 text-xs rounded-full {{ $product->status === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->status === 1 ? '上架' : '下架' }}</span>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">保存修改</button>
            </div>
        </form>
    </div>
@endsection
