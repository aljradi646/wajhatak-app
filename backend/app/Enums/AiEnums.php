<?php

namespace App\Enums;

enum AiMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}

enum AiRequestStatus: string
{
    case Ok = 'ok';
    case Blocked = 'blocked';
    case Error = 'error';
    case Fallback = 'fallback';
}
