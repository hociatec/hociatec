<?php

declare(strict_types=1);

namespace App\Module\Audit\Entity;

enum AuditType: string
{
    case PERFORMANCE = 'performance';
    case SECURITY = 'security';
    case UX = 'ux';
    case SEO = 'seo';
    case TECHNICAL = 'technical';
    case ACCESSIBILITY = 'accessibility'; // accessibilite numerique
}

