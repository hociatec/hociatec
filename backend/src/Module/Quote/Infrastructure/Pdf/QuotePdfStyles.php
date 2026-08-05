<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Pdf;

final class QuotePdfStyles
{
    private function __construct()
    {
    }

    public static function documentCss(): string
    {
        return <<<'CSS'
@page { size: A4; margin: 18mm; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; line-height: 1.45; color: #0f172a; }
main { display: block; }
h1, h2 { margin: 0 0 10px; color: #0f172a; }
h1 { font-size: 22pt; }
h2 { font-size: 13pt; margin-top: 24px; }
p { margin: 0 0 8px; }
address { font-style: normal; }
address p { margin: 0 0 6px; }
.lead { margin-bottom: 16px; color: #334155; }
.status { display: inline-block; margin-top: 6px; padding: 4px 10px; border: 1px solid #94a3b8; border-radius: 999px; font-size: 10pt; }
.section-card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; background: #fff; }
.section-card h2 { margin-top: 0; }
.meta-list { margin: 0; padding: 0; }
.meta-list dt { font-weight: 700; margin-top: 8px; }
.meta-list dd { margin: 2px 0 0; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
caption { text-align: left; font-weight: 700; margin-bottom: 8px; }
th, td { border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top; text-align: left; }
thead th { background: #e2e8f0; font-weight: 700; }
.num { text-align: right; white-space: nowrap; }
.totals-table { margin-top: 16px; }
.totals-table th { width: 60%; background: #f8fafc; }
.terms { white-space: normal; }
CSS;
    }
}
