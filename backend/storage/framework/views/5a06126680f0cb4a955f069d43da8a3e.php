<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => 'الوكلاء','title' => 'الوكلاء']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'الوكلاء','title' => 'الوكلاء']); ?>
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <form method="GET" class="flex gap-2 sm:w-80">
            <?php if (isset($component)) { $__componentOriginal187e7f8ae725d0d7c586a97e85953c03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal187e7f8ae725d0d7c586a97e85953c03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.input','data' => ['name' => 'search','value' => ''.e($search).'','placeholder' => 'بحث بالاسم أو الترخيص أو البريد...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','value' => ''.e($search).'','placeholder' => 'بحث بالاسم أو الترخيص أو البريد...']); ?>
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
            <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['variant' => 'secondary','type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','type' => 'submit']); ?>بحث <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
        </form>
        <a href="<?php echo e(route('admin.agents.create')); ?>" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-xl hover:shadow-lg" style="background: linear-gradient(135deg, #075E4A, #0E8A6D, #35C39E); box-shadow: 0 4px 12px rgba(14, 138, 109, 0.25);">
            + وكيل جديد
        </a>
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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الوكيل</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">رقم الترخيص</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">التقييم</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">عدد المراجعات</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">العقارات</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">نشط</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900"><?php echo e($agent->user->name); ?></div>
                                <div class="text-xs text-gray-500"><?php echo e($agent->user->email); ?></div>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e($agent->license_number ?? '—'); ?></td>
                            <td class="px-4 py-3">
                                <span class="text-amber-400 font-semibold">★ <?php echo e(number_format((float) $agent->rating, 2)); ?></span>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e(number_format($agent->reviews_count)); ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e(number_format($agent->properties_count ?? 0)); ?></td>
                            <td class="px-4 py-3">
                                <?php if($agent->is_active): ?>
                                    <span class="inline-flex items-center rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs font-semibold">نشط</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-semibold">غير نشط</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="<?php echo e(route('admin.agents.show', $agent)); ?>" class="text-wajhatak-600 hover:text-wajhatak-700 text-sm font-medium">عرض</a>
                                    <a href="<?php echo e(route('admin.agents.edit', $agent)); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">تعديل</a>
                                    <form method="POST" action="<?php echo e(route('admin.agents.destroy', $agent)); ?>" onsubmit="return confirm('هل أنت متأكد من حذف هذا الوكيل؟');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا يوجد وكلاء.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php echo $__env->make('admin.partials.pagination', ['paginator' => $agents], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views\admin\agents\index.blade.php ENDPATH**/ ?>