<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'العقارات','title' => 'العقارات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'العقارات','title' => 'العقارات']); ?>
    <div class="mb-4 flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
        <form method="GET" class="flex flex-wrap flex-1 gap-2">
            <div class="sm:w-64">
                <?php if (isset($component)) { $__componentOriginal187e7f8ae725d0d7c586a97e85953c03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal187e7f8ae725d0d7c586a97e85953c03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.input','data' => ['name' => 'search','value' => ''.e($search).'','placeholder' => 'بحث بالعنوان أو المرجع...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','value' => ''.e($search).'','placeholder' => 'بحث بالعنوان أو المرجع...']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal187e7f8ae725d0d7c586a97e85953c03)): ?>
<?php $attributes = $__attributesOriginal187e7f8ae725d0d7c586a97e85953c03; ?>
<?php unset($__attributesOriginal187e7f8ae725d0d7c586a97e85953c03); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal187e7f8ae725d0d7c586a97e85953c03)): ?>
<?php $component = $__componentOriginal187e7f8ae725d0d7c586a97e85953c03; ?>
<?php unset($__componentOriginal187e7f8ae725d0d7c586a97e85953c03); ?>
<?php endif; ?>
            </div>
            <select name="status" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">كل الحالات</option>
                <?php $__currentLoopData = ['draft','pending','published','rejected','archived']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($s); ?>" <?php if(request('status') === $s): echo 'selected'; endif; ?>>
                        <?php echo e(match($s) { 'draft'=>'مسودة', 'pending'=>'قيد المراجعة', 'published'=>'منشور', 'rejected'=>'مرفوض', 'archived'=>'مؤرشف' }); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="transaction" class="rounded-lg border-gray-300 shadow-sm text-sm">
                <option value="">الكل</option>
                <option value="sale" <?php if(request('transaction') === 'sale'): echo 'selected'; endif; ?>>بيع</option>
                <option value="rent" <?php if(request('transaction') === 'rent'): echo 'selected'; endif; ?>>إيجار</option>
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
            <a href="<?php echo e(route('admin.properties.index')); ?>" class="inline-flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700">مسح</a>
        </form>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.properties.trash')); ?>" class="inline-flex items-center px-4 py-2 text-sm font-semibold bg-white text-gray-700 ring-1 ring-gray-300 rounded-lg hover:bg-gray-50">
                سلة المحذوفات
            </a>
            <a href="<?php echo e(route('admin.properties.create')); ?>" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
                + عقار جديد
            </a>
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
                <button type="button" data-bulk-action="delete" onclick="submitBulk('delete')" class="ms-2 text-sm text-red-600 hover:text-red-800 font-medium">حذف</button>
                <button type="button" data-bulk-action="force" onclick="submitBulk('force')" class="text-sm text-red-700 hover:text-red-900 font-medium">حذف نهائي</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-8"></th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">العنوان</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الوكيل</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">النوع</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">المعاملة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">السعر</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">الغرف</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ النشر</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__empty_1 = true; $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="<?php echo e($property->id); ?>" class="bulk-item h-4 w-4 rounded border-gray-300 text-wajhatak-600">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?php echo e($property->title); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($property->reference_code); ?></div>
                                </td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->agent?->user?->name ?? '—'); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->type?->name_ar ?? $property->property_type_id); ?></td>
                                <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['status' => $property->transaction_type->value]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($property->transaction_type->value)]); ?>
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
                                <td class="px-4 py-3 text-gray-600"><?php echo e(number_format((float) $property->price, 2)); ?> <?php echo e($property->currency); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->bedrooms ?? '—'); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo e($property->published_at?->format('Y-m-d') ?? '—'); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="<?php echo e(route('admin.properties.show', $property)); ?>" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                        <a href="<?php echo e(route('admin.properties.edit', $property)); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                        <form method="POST" action="<?php echo e(route('admin.properties.destroy', $property)); ?>" onsubmit="return confirm('نقل هذا العقار إلى سلة المحذوفات؟');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">لا توجد عقارات.</td>
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
            if (!confirm('تأكيد الإجراء المحدد على العقارات المحددة؟')) return;
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
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\admin\properties\index.blade.php ENDPATH**/ ?>