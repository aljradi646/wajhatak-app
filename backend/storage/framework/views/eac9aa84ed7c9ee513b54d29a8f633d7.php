<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'سلة المحذوفات','title' => 'سلة المحذوفات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'سلة المحذوفات','title' => 'سلة المحذوفات']); ?>
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">العقارات المحذوفة مؤقتًا. يمكنك استعادتها أو حذفها نهائيًا.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.properties.index')); ?>" class="inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">› العودة للعقارات</a>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['padding' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
        <form method="POST" action="<?php echo e(route('admin.properties.bulk')); ?>" data-bulk-form>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="">
            <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50">
                <span class="text-sm text-gray-600">تحديد الكل:</span>
                <input type="checkbox" data-select-all class="h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                <button type="button" onclick="submitBulk('restore')" class="ms-2 text-sm text-green-600 hover:text-green-800 font-medium">استعادة</button>
                <button type="button" onclick="submitBulk('force')" class="text-sm text-red-700 hover:text-red-900 font-medium">حذف نهائي</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">العنوان</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">المرجع</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ الحذف</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__empty_1 = true; $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="<?php echo e($property->id); ?>" class="bulk-item h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?php echo e($property->title); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->reference_code); ?></td>
                                <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['status' => $property->status->value]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($property->status->value)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->deleted_at?->format('Y-m-d H:i')); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="<?php echo e(route('admin.properties.restore', $property)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">استعادة</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.properties.force-delete', $property)); ?>" onsubmit="return confirm('حذف نهائي لا يمكن التراجع عنه؟ ستحذف كل البيانات المرتبطة.');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف نهائي</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">سلة المحذوفات فارغة.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php echo $__env->make('admin.partials.pagination', ['paginator' => $properties], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </form>
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

    <?php $__env->startPush('scripts'); ?>
    <script>
        document.querySelectorAll('[data-select-all]').forEach(function (box) {
            box.addEventListener('change', function () {
                document.querySelectorAll('.bulk-item').forEach(function (i) { i.checked = box.checked; });
            });
        });
        window.submitBulk = function (action) {
            const form = document.querySelector('[data-bulk-form]');
            if (!form.querySelector('.bulk-item:checked')) { alert('حدد عقارًا واحدًا على الأقل.'); return; }
            form.querySelector('input[name="action"]').value = action;
            form.submit();
        };
    </script>
    <?php $__env->stopPush(); ?>
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
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\admin\properties\trash.blade.php ENDPATH**/ ?>