<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>嗣夏 AI 技能市场</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col antialiased">

    {{-- 吸顶毛玻璃导航 --}}
    <nav class="sticky top-0 z-50 backdrop-blur-md bg-white/80 border-b border-gray-200/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">嗣</div>
                    <span class="font-bold text-xl bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">嗣夏平台</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="/" class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">首页</a>
                    <a href="/login" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">登录</a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero 区域：深色高级感背景 + 超大渐变字 --}}
    <section class="relative bg-slate-900 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/60 via-slate-900 to-purple-900/40"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 text-center">
            <p class="text-indigo-300 font-medium tracking-widest text-sm mb-6 uppercase">Sixia AI Skill Marketplace</p>
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-300 via-white to-purple-300 mb-6 leading-tight">
                嗣夏 AI 技能市场
            </h1>
            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-slate-300 mb-10">
                一站式 AI 能力交易平台，连接顶尖开发者与企业需求，释放你的无限潜能。
            </p>
            <div class="flex justify-center space-x-4">
                <a href="#products" class="inline-flex items-center px-8 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-600/30">
                    浏览技能
                </a>
                <a href="/login" class="inline-flex items-center px-8 py-3 rounded-xl border border-slate-600 text-slate-200 font-semibold hover:border-indigo-400 hover:text-white transition-all duration-300">
                    成为开发者
                </a>
            </div>
        </div>
    </section>

    {{-- 商品列表 --}}
    <main id="products" class="flex-grow w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">精选 AI 技能</h2>
                <p class="text-gray-500">严选上架，即买即用的高质量 AI 能力</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($products as $product)
                    <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-gray-100 hover:-translate-y-2 hover:shadow-xl transition-all duration-300 flex flex-col">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600 mb-5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $product->name }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">{{ \Illuminate\Support\Str::limit($product->description, 50) }}</p>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                            <span class="text-3xl font-extrabold text-indigo-600 tracking-tight">
                                <span class="text-lg align-top">¥</span>{{ number_format($product->price / 1000, 2) }}
                            </span>
                            <button class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-600/20">立即购买</button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-20">
                        <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">暂无商品</h3>
                        <p class="text-gray-400 text-sm">优质 AI 技能正在筹备上架中，敬请期待</p>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- 页脚 --}}
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-center">
            <div class="flex items-center justify-center space-x-2 mb-3">
                <div class="w-6 h-6 rounded-md bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs">嗣</div>
                <span class="font-semibold text-gray-700">嗣夏平台</span>
            </div>
            <p class="text-gray-400 text-sm">&copy; 2026 嗣夏平台. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
