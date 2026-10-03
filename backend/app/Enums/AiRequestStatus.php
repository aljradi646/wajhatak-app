<?php

namespace App\Enums;

/** حالة طلب المساعد المسجَّل في ai_request_logs. */
enum AiRequestStatus: string
{
    case Ok = 'ok';
    case Blocked = 'blocked';
    case Error = 'error';
    case Fallback = 'fallback';
}
