<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Provider;

use App\Module\Audit\Domain\Entity\AuditType;

/**
 * Provides default checklist templates per audit type.
 */
class AuditTemplateProvider
{
    /**
     * @return array<string, list<array{key: string, label: string, level?: string}>> category => items
     */
    public function getTemplate(AuditType $type): array
    {
        return match ($type) {
            AuditType::ACCESSIBILITY => AuditTemplateDefinitions::accessibilityTemplate(),
            AuditType::PERFORMANCE => AuditTemplateDefinitions::performanceTemplate(),
            AuditType::SECURITY => AuditTemplateDefinitions::securityTemplate(),
            AuditType::UX => AuditTemplateDefinitions::uxTemplate(),
            AuditType::SEO => AuditTemplateDefinitions::seoTemplate(),
            AuditType::TECHNICAL => AuditTemplateDefinitions::technicalTemplate(),
        };
    }
}
