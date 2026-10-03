<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'سجلات المساعد','title' => 'المساعد الذكي — السجلات','breadcrumbs' => [['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'سجلات المساعد','title' => 'المساعد الذكي — السجلات','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]])]); ?>

    <div class="space-y-5">
        <?php echo $__env->make('admin.ai._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="">كل الحالات</option>
                    <?php $__currentLoopData = ['ok' => 'ناجح', 'blocked' => 'محجوب', 'error' => 'خطأ', 'fallback' => 'بديل']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['variant' => 'secondary','type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','type' => 'submit']); ?>تصفية <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
                <?php if($status): ?>
                    <a href="<?php echo e(route('admin.ai.logs')); ?>" class="text-sm font-semibold text-gray-500 hover:text-gray-700 px-2 dark:text-gray-400">مسح</a>
                <?php endif; ?>
            </form>
            <a href="<?php echo e(route('admin.ai.stats')); ?>" class="text-sm font-bold text-wajhatak-600 hover:text-wajhatak-700">الإحصاءات ←</a>
        </div>

        <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false,'title' => 'سجل طلبات المساعد','description' => 'بيانات تشغيلية آمنة — لا تُسجل أسرار ولا محتوى رسائل المستخدمين الحرة.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'title' => 'سجل طلبات المساعد','description' => 'بيانات تشغيلية آمنة — لا تُسجل أسرار ولا محتوى رسائل المستخدمين الحرة.']); ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الوقت</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النية</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">المعايير</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">النتائج</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الحالة</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">رمز الخطأ</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">الزمن</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">البحث</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?php echo e($log->created_at?->format('m/d H:i')); ?></td>
                                <td class="px-4 py-3 font-bold"><?php echo e($log->intent ?? '—'); ?></td>
                                <td class="px-4 py-3 max-w-xs truncate text-xs text-gray-500" dir="ltr"><?php echo e($log->structured_filters ? json_encode($log->structured_filters, JSON_UNESCAPED_UNICODE) : '—'); ?></td>
                                <td class="px-4 py-3"><?php echo e($log->results_count); ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                        $colors = [
                                            'ok' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                            'blocked' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                            'error' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                            'fallback' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        ];
                                    ?>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold <?php echo e($colors[$log->status] ?? 'bg-gray-100'); ?>"><?php echo e($log->status); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-red-500" dir="ltr"><?php echo e($log->error_code ?? '—'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo e($log->latency_ms); ?>ms</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?php echo e($log->search_ms); ?>ms</td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">لا توجد طلبات مسجلة بعد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php echo $__env->make('admin.partials.pagination', ['paginator' => $logs], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad5130b5347ab6ecc017d2f5a278b926)): ?>
<?php $attributes = $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926; ?>
<?php unset($__attributesOriginalad5130b5347ab6ecc017d2f5a278b926); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad5130b5347ab6ecc017d2f5a278b926)): ?>
<?php $component = $__componentOriginalad5130b5347ab6ecc017d2f5a278b926; ?>
<?php unset($__componentOriginalad5130b5347ab6ecc017d2f5a278b926); ?>
<?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal069c916459f102ed6d71cf67d43601ae)): ?>
<?php $attributes = $__attributesOriginal069c916459f102ed6d71cf67d43601ae; ?>
<?php unset($__attributesOriginal069c916459f102ed6d71cf67d43601ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal069c916459f102ed6d71cf67d43601ae)): ?>
<?php $component = $__componentOriginal069c916459f102ed6d71cf67d43601ae; ?>
<?php unset($__componentOriginal069c916459f102ed6d71cf67d43601ae); ?>
<?php endif; ?>
<?php /**PATH /app/backend/resources/views/admin/ai/logs.blade.php ENDPATH**/ ?>