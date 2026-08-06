<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Pdf;

use App\Module\Audit\Domain\Entity\AuditRequest;

final class AuditPdfHtmlBuilder
{
    public function detailed(AuditRequest $audit): string
    {
        $grouped = [];
        foreach ($audit->getItems() as $it) {
            $grouped[$it->getCategory()][] = $it;
        }
        ksort($grouped);

        $sections = '';
        foreach ($grouped as $cat => $items) {
            usort($items, fn ($a, $b) => $a->getPosition() <=> $b->getPosition());
            $rows = '';
            foreach ($items as $it) {
                $status = $it->getIsCompliant();
                $statusLabel = null === $status ? 'À évaluer' : ($status ? 'Conforme' : 'Non conforme');
                $level = $it->getLevel() ? ' ('.$it->getLevel().')' : '';
                $comment = $it->getComment() ? '<div class="comment">'.nl2br(htmlspecialchars((string) $it->getComment())).'</div>' : '';
                $rows .= sprintf(
                    '<tr><td>%s%s</td><td>%s</td><td class="status %s">%s</td></tr><tr><td colspan="3">%s</td></tr>',
                    htmlspecialchars($it->getLabel()),
                    $level,
                    htmlspecialchars($it->getCriterionKey()),
                    true === $status ? 'ok' : (false === $status ? 'ko' : 'na'),
                    $statusLabel,
                    $comment
                );
            }
            $sections .= sprintf(
                '<h2 class="section">%s</h2><table class="table">%s</table>',
                htmlspecialchars((string) $cat),
                $rows
            );
        }

        $meta = sprintf(
            '<div class="meta">Type: %s — Cible: %s — Statut: %s — Date: %s</div>',
            htmlspecialchars($audit->getType()->value),
            htmlspecialchars($audit->getTargetUrl()),
            htmlspecialchars($audit->getStatus()),
            $audit->getCreatedAt()->format('d/m/Y')
        );

        $objectives = $audit->getObjectives() ? '<div class="box"><strong>Objectifs</strong><br/>'.nl2br(htmlspecialchars((string) $audit->getObjectives())).'</div>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    h2.section { font-size: 14px; margin: 18px 0 8px; }
    .muted { color: #555; }
    .meta { margin-bottom: 8px; }
    .box { background: #f7fafc; padding: 8px; margin: 10px 0; border: 1px solid #e5e7eb; }
    .table { width: 100%; border-collapse: collapse; }
    .table td { border-bottom: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
    .table td.status { text-align: right; white-space: nowrap; }
    .table td.status.ok { color: #047857; }
    .table td.status.ko { color: #b91c1c; }
    .table td.status.na { color: #6b7280; }
    .comment { color: #374151; font-size: 11px; margin-top: 4px; }
  </style>
  <title>{$audit->getNumber()} - Rapport détaillé</title>
  </head>
  <body>
    <h1>Audit {$audit->getNumber()} — Rapport détaillé</h1>
    {$meta}
    {$objectives}
    {$sections}
  </body>
  </html>
HTML;
    }

    public function summary(AuditRequest $audit): string
    {
        $statsByCat = [];
        $total = ['ok' => 0, 'ko' => 0, 'na' => 0];
        foreach ($audit->getItems() as $it) {
            $cat = $it->getCategory();
            $statsByCat[$cat] = $statsByCat[$cat] ?? ['ok' => 0, 'ko' => 0, 'na' => 0, 'total' => 0];
            ++$statsByCat[$cat]['total'];
            $status = $it->getIsCompliant();
            if (true === $status) {
                ++$statsByCat[$cat]['ok'];
                ++$total['ok'];
            } elseif (false === $status) {
                ++$statsByCat[$cat]['ko'];
                ++$total['ko'];
            } else {
                ++$statsByCat[$cat]['na'];
                ++$total['na'];
            }
        }
        ksort($statsByCat);

        $rows = '';
        foreach ($statsByCat as $cat => $st) {
            $rate = round(($st['ok'] / $st['total']) * 100);
            $rows .= sprintf('<tr><td>%s</td><td class="num">%d</td><td class="num">%d</td><td class="num">%d</td><td class="num">%d%%</td></tr>',
                htmlspecialchars((string) $cat), $st['ok'], $st['ko'], $st['na'], $rate);
        }
        $grandTotal = $total['ok'] + $total['ko'] + $total['na'];
        $globalRate = $grandTotal > 0 ? round(($total['ok'] / $grandTotal) * 100) : 0;

        $meta = sprintf(
            '<div class="meta">Type: %s — Cible: %s — Statut: %s — Date: %s</div>',
            htmlspecialchars($audit->getType()->value),
            htmlspecialchars($audit->getTargetUrl()),
            htmlspecialchars($audit->getStatus()),
            $audit->getCreatedAt()->format('d/m/Y')
        );

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
    th { background: #f5f5f5; }
    .num { text-align: right; white-space: nowrap; }
    .summary { margin-top: 10px; }
  </style>
  <title>{$audit->getNumber()} - Synthèse</title>
  </head>
  <body>
    <h1>Audit {$audit->getNumber()} — Synthèse</h1>
    {$meta}
    <table>
      <thead>
        <tr><th>Catégorie</th><th>Conformes</th><th>Non conformes</th><th>À évaluer</th><th>Taux</th></tr>
      </thead>
      <tbody>
        {$rows}
      </tbody>
    </table>
    <div class="summary">Taux global de conformité: <strong>{$globalRate}%</strong> (sur {$grandTotal} critères)</div>
  </body>
  </html>
HTML;
    }
}
