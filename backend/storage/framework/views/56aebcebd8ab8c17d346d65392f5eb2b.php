<?php if (isset($component)) { $__componentOriginal069c916459f102ed6d71cf67d43601ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal069c916459f102ed6d71cf67d43601ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.layouts.admin','data' => ['heading' => $definition['label'],'title' => 'المساعد الذكي — '.$definition['label'],'breadcrumbs' => [['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definition['label']),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('المساعد الذكي — '.$definition['label']),'breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')], ['label' => 'المساعد الذكي', 'url' => route('admin.ai.index')]])]); ?>

    <div class="space-y-5">
        <?php echo $__env->make('admin.ai._nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(session('status')): ?>
            <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-500/10 dark:border-green-500/30 px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-500/10 dark:border-red-500/30 px-4 py-3 text-sm font-bold text-red-700 dark:text-red-400">
                <ul class="list-disc ms-5 space-y-0.5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('admin.ai.settings.update', ['section' => $section])); ?>" class="space-y-5">
            <?php echo csrf_field(); ?>

            <?php if (isset($component)) { $__componentOriginalad5130b5347ab6ecc017d2f5a278b926 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad5130b5347ab6ecc017d2f5a278b926 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.card','data' => ['title' => $definition['label'],'description' => $definition['description']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definition['label']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definition['description'])]); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php $__currentLoopData = $definition['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $key = $field['key'];
                            $type = $field['type'] ?? 'text';
                            $value = $values[$key] ?? null;
                        ?>

                        <?php if($type === 'boolean'): ?>
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                <input type="checkbox" name="<?php echo e($key); ?>" value="1" <?php if((bool) $value): echo 'checked'; endif; ?> class="h-5 w-5 rounded accent-wajhatak-600">
                                <span class="text-sm font-bold"><?php echo e($field['label']); ?></span>
                            </label>
                        <?php elseif($type === 'forced'): ?>
                            <label class="flex items-center gap-3 rounded-xl border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10 p-3">
                                <input type="checkbox" checked disabled class="h-5 w-5 rounded accent-wajhatak-600 opacity-60">
                                <span class="text-sm font-bold opacity-80"><?php echo e($field['label']); ?></span>
                                <span class="ms-auto text-xs font-bold text-green-600 dark:text-green-400">مُفعّل دائمًا</span>
                            </label>
                        <?php elseif($type === 'textarea'): ?>
                            <div class="md:col-span-2">
                                <?php if (isset($component)) { $__componentOriginal694712473b787cd740db4e46be9da3f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal694712473b787cd740db4e46be9da3f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.textarea','data' => ['label' => $field['label'],'name' => $key,'value' => $value,'rows' => $field['rows'] ?? 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['label']),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'rows' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['rows'] ?? 3)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal694712473b787cd740db4e46be9da3f9)): ?>
<?php $attributes = $__attributesOriginal694712473b787cd740db4e46be9da3f9; ?>
<?php unset($__attributesOriginal694712473b787cd740db4e46be9da3f9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal694712473b787cd740db4e46be9da3f9)): ?>
<?php $component = $__componentOriginal694712473b787cd740db4e46be9da3f9; ?>
<?php unset($__componentOriginal694712473b787cd740db4e46be9da3f9); ?>
<?php endif; ?>
                            </div>
                        <?php elseif($type === 'select'): ?>
                            <div>
                                <label for="<?php echo e($key); ?>" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1"><?php echo e($field['label']); ?></label>
                                <select id="<?php echo e($key); ?>" name="<?php echo e($key); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm dark:bg-gray-800 dark:border-gray-600">
                                    <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue => $optionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($optionValue); ?>" <?php if((string) $value === (string) $optionValue): echo 'selected'; endif; ?>><?php echo e($optionLabel); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = [$key];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        <?php else: ?>
                            <?php if (isset($component)) { $__componentOriginal187e7f8ae725d0d7c586a97e85953c03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal187e7f8ae725d0d7c586a97e85953c03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.input','data' => ['label' => $field['label'],'name' => $key,'type' => $type,'value' => $value,'min' => $field['min'] ?? null,'max' => $field['max'] ?? null,'step' => $field['step'] ?? null,'required' => in_array('required', $field['rules'] ?? [], true)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['label']),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($value),'min' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['min'] ?? null),'max' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['max'] ?? null),'step' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field['step'] ?? null),'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(in_array('required', $field['rules'] ?? [], true))]); ?>
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
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
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

            <div class="flex items-center justify-end gap-3">
                <a href="<?php echo e(route('admin.ai.index')); ?>" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400">إلغاء</a>
                <?php if (isset($component)) { $__componentOriginal60a020e5340f3f52bbc4501dc9f93102 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>حفظ «<?php echo e($definition['label']); ?>» <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $attributes = $__attributesOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__attributesOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102)): ?>
<?php $component = $__componentOriginal60a020e5340f3f52bbc4501dc9f93102; ?>
<?php unset($__componentOriginal60a020e5340f3f52bbc4501dc9f93102); ?>
<?php endif; ?>
            </div>
        </form>
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
<?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views/admin/ai/settings.blade.php ENDPATH**/ ?>