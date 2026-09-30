<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportLog extends Model
{
    /** تقارير لوحة الإدارة — تُبنى من بيانات المنصة كاملة. */
    public const ADMIN_TYPES = ['agents', 'properties', 'requests', 'users'];

    /** تقارير الوكيل من داخل التطبيق — مقيّدة ببيانات الوكيل نفسه. */
    public const AGENT_TYPES = ['agent_properties', 'agent_viewing_requests', 'agent_performance'];

    /** الصيغ المدعومة فعليًا في ReportController. */
    public const FORMATS = ['html', 'pdf', 'excel', 'csv', 'json'];

    public const TYPE_LABELS = [
        'agents' => 'تقرير الوكلاء',
        'properties' => 'تقرير العقارات',
        'requests' => 'تقرير طلبات المعاينة',
        'users' => 'تقرير المستخدمين',
        'agent_properties' => 'تقرير عقارات الوكيل',
        'agent_viewing_requests' => 'تقرير طلبات معاينة الوكيل',
        'agent_performance' => 'تقرير أداء الوكيل',
    ];

    /** كل الأنواع المسجَّلة (إدارة + وكيل). */
    public const TYPES = [
        ...self::ADMIN_TYPES,
        ...self::AGENT_TYPES,
    ];

    public const FORMAT_LABELS = [
        'html' => 'معاينة (HTML)',
        'pdf' => 'PDF',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'json' => 'JSON',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'format',
        'filters',
        'row_count',
        'file_name',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'filters' => 'array',
        'row_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** مالك سجل التقرير: لا يُعرض لغير مالكه أو للمشرف. */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function formatLabel(): string
    {
        return self::FORMAT_LABELS[$this->format] ?? strtoupper($this->format);
    }
}
