@php($isUser = $message['role'] === 'user')
<div class="flex {{ $isUser ? 'justify-start' : 'justify-end' }}">
    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed {{ $isUser
        ? 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-ss-md'
        : 'bg-wajhatak-50 dark:bg-wajhatak-500/10 border border-wajhatak-100 dark:border-wajhatak-500/20 text-gray-800 dark:text-gray-100 rounded-se-md' }}">
        <div class="whitespace-pre-wrap">{{ $message['content'] }}</div>

        @unless($isUser)
            @if(!empty($message['property_ids']))
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach($message['property_ids'] as $pid)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 px-2 py-1 text-[11px] font-bold text-gray-600 dark:text-gray-300">
                            🏠 عقار #{{ $pid }}
                        </span>
                    @endforeach
                </div>
            @endif
        @endunless

        @if(isset($message['time']))
            <div class="mt-1 text-[10px] text-gray-400">{{ $message['time'] }}</div>
        @endif
    </div>
</div>
