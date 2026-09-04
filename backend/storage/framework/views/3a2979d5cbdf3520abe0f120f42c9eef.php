<?php if($paginator->hasPages()): ?>
    <div class="border-t border-gray-100 px-4 py-3 flex items-center justify-between gap-3 flex-wrap dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            عرض <?php echo e($paginator->firstItem() ?? 0); ?> — <?php echo e($paginator->lastItem() ?? 0); ?> من <?php echo e($paginator->total()); ?> سجل
        </div>
        <nav class="flex items-center gap-1" aria-label="Pagination">
            
            <?php if($paginator->onFirstPage()): ?>
                <span class="px-2 py-1 text-sm text-gray-300 dark:text-gray-600">› السابق</span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="px-2 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300">› السابق</a>
            <?php endif; ?>

            
            <?php $__currentLoopData = $paginator->getUrlRange(1, $paginator->lastPage()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($page == $paginator->currentPage()): ?>
                    <span class="px-3 py-1 text-sm font-bold text-white rounded-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D);"><?php echo e($page); ?></span>
                <?php else: ?>
                    <a href="<?php echo e($url); ?>" class="px-3 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded-lg dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300"><?php echo e($page); ?></a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="px-2 py-1 text-sm text-gray-600 hover:bg-wajhatak-50 hover:text-wajhatak-700 rounded dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-wajhatak-300">التالي ‹</a>
            <?php else: ?>
                <span class="px-2 py-1 text-sm text-gray-300 dark:text-gray-600">التالي ‹</span>
            <?php endif; ?>
        </nav>
    </div>
<?php endif; ?>
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\admin\partials\pagination.blade.php ENDPATH**/ ?>