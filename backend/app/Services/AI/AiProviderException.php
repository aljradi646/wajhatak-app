<?php

namespace App\Services\AI;

use RuntimeException;

/** استثناء موحد لأخطاء مزود الاستدلال (offline / timeout / HTTP error / JSON غير صالح). */
class AiProviderException extends RuntimeException {}
