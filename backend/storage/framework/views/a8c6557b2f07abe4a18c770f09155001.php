<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title><?php echo e($report['heading']); ?> — <?php echo e($report['site']['name']); ?></title>
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm 16mm 12mm; }
        * { box-sizing: border-box; }
        html, body {
            font-family: Amiri, serif;
            font-size: 11pt;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.55;
        }
        .header {
            border-bottom: 2px solid #075E4A;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo { width: 56px; height: 56px; object-fit: contain; }
        .brand-name { font-size: 17pt; font-weight: bold; color: #075E4A; }
        .brand-tagline { font-size: 9.5pt; color: #4B5563; }
        .report-title { font-size: 13pt; font-weight: bold; color: #0E8A6D; text-align: left; }
        .meta { font-size: 8.5pt; color: #6B7280; margin: 2px 0; }
        .filters { margin: 6px 0; }
        .filter-chip {
            display: inline-block;
            border: 1px solid #A7F3D0;
            background: #ECFDF5;
            color: #047857;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 1px 8px;
            margin: 0 0 3px 4px;
            border-radius: 10px;
        }
        .summary { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 10px 0 14px 0; }
        .summary td {
            background: #F0FDF4;
            border: 1px solid #D1FAE5;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
        }
        .summary .label { font-size: 8pt; color: #065F46; display: block; }
        .summary .value { font-size: 11pt; font-weight: bold; color: #065F46; display: block; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th, table.data td { border: 1px solid #D1D5DB; padding: 4px 7px; text-align: center; }
        table.data th { background: #075E4A; color: #FFFFFF; font-weight: bold; font-size: 9.5pt; }
        table.data td { font-size: 9.5pt; }
        table.data tr:nth-child(even) td { background: #F9FAFB; }
        .badge {
            display: inline-block;
            font-size: 8pt;
            font-weight: bold;
            padding: 0px 7px;
            border-radius: 9px;
            border: 1px solid transparent;
        }
        .b-green { background: #D1FAE5; color: #065F46; border-color: #6EE7B7; }
        .b-amber { background: #FEF3C7; color: #92400E; border-color: #FCD34D; }
        .b-red { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }
        .b-gray { background: #F3F4F6; color: #374151; border-color: #D1D5DB; }
        .b-blue { background: #E0F2FE; color: #075985; border-color: #7DD3FC; }
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; text-align: center; font-size: 8pt; color: #9CA3AF; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 60px;">
                    <?php if(! empty($report['site']['logo'])): ?>
                        <img class="logo" src="<?php echo $report['site']['logo']; ?>" alt="الشعار">
                    <?php endif; ?>
                </td>
                <td>
                    <div class="brand-name"><?php echo e($report['site']['name']); ?></div>
                    <div class="brand-tagline"><?php echo e($report['site']['tagline']); ?></div>
                </td>
                <td style="text-align: left;">
                    <div class="report-title"><?php echo e($report['heading']); ?></div>
                    <div class="meta">تاريخ الإنشاء: <?php echo e($report['generated_at']->translatedFormat('d MMMM Y')); ?> • <?php echo e($report['generated_at']->format('H:i')); ?></div>
                </td>
            </tr>
        </table>
        <?php if(! empty($report['filters'])): ?>
            <div class="filters">
                <?php $__currentLoopData = $report['filters']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="filter-chip"><?php echo e($filter['label']); ?>: <?php echo e($filter['value']); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <table class="summary">
        <tr>
            <?php $__currentLoopData = $report['summary']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <td>
                    <span class="value"><?php echo e($item['value']); ?></span>
                    <span class="label"><?php echo e($item['label']); ?></span>
                </td>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <?php $__currentLoopData = $report['columns']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th><?php echo e($col['label']); ?></th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $report['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <?php $__currentLoopData = $report['columns']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <td>
                            <?php if($col['type'] === 'badge'): ?>
                                <?php
                                    $state = $row[$col['key']] ?? null;
                                    $class = 'b-'.($col['colors'][$state] ?? 'gray');
                                ?>
                                <span class="badge <?php echo e($class); ?>"><?php echo e($fmt($col, $state)); ?></span>
                            <?php else: ?>
                                <?php echo e($fmt($col, $row[$col['key']] ?? null)); ?>

                            <?php endif; ?>
                        </td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <?php ($colspan = max(count($report['columns']), 1)); ?>
                    <td colspan="<?php echo e($colspan); ?>" style="padding: 18px; color: #6B7280;">لا توجد بيانات مطابقة لشروط التقرير.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        تم إنشاء هذا التقرير بواسطة منصة <?php echo e($report['site']['name']); ?> — صفحة {PAGE_NUM} من {PAGE_COUNT}
    </div>

</body>
</html><?php /**PATH E:\home\Wajhatak_Production_Ready\backend\resources\views/admin/reports/pdf.blade.php ENDPATH**/ ?>