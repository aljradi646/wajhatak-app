<?php

namespace App\Enums;

/**
 * دور رسالة داخل محادثة المساعد.
 *
 * ملاحظة معمارية: كل enum في ملف منفصل باسم الصنف تمامًا لتصبح متوافقة مع
 * PSR-4؛ وجود أكثر من صنف في ملف واحد يمنع الـ autoloader المُحسَّن من
 * تحميلها فيسقط حفظ المحادثات والسياق بصمت في الإنتاج.
 */
enum AiMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
