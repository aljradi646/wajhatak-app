@if($paginator->hasPages())
    <div class="border-t border-gray-100 px-4 py-3 flex items-center justify-between gap-3 flex-wrap dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            عرض {{ $paginator->firstItem() ?? 0 }} — {{ $paginator->lastItem() ?? 0 }} من {{ $paginator->total() }} سجل
        </div>
        <nav class="flex items-center gap-1" aria-label="Pagination">
            {{-- Previous --}}
            @if($paginator->onFirstPage())
                <span class="px-2 py-1 text-sm text-gray-300 dark:text-gray-600">› السابق</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-2 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300">› السابق</a>
            @endif

            {{-- Pages --}}
            @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if($page == $paginator->currentPage())
                    <span class="px-3 py-1 text-sm font-bold text-white rounded-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D);">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded-lg dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-2 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300">التالي ‹</a>
            @else
                <span class="px-2 py-1 text-sm text-gray-300 dark:text-gray-600">التالي ‹</span>
            @endif
        </nav>
    </div>
@endif
