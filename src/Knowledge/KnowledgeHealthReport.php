<?php

namespace Atom\Knowledge;

use PDO;

/**
 * Single source of truth for the ATOM brain/knowledge health snapshot
 * (chunk counts, training quality, health score). Every viewer — CLI
 * `/status`, the web dashboard, and the admin panel — reads this same
 * computation so a fix to the formula or its queries applies everywhere
 * at once, per the AGENTS.md cross-client parity rule.
 */
class KnowledgeHealthReport
{
    public static function compute(?PDO $pdo): array
    {
        $stats = [
            'knowledge_count' => 0,
            'document_count'  => 0,
            'training_count'  => 0,
            'optimized_count' => 0,
            'duplicate_count' => 0,
            'conversations'   => 0,
            'health_score'    => 90,
        ];

        if ($pdo === null) {
            return $stats;
        }

        try {
            $stats['knowledge_count'] = (int)$pdo->query("SELECT COUNT(*) FROM atom_document_chunks")->fetchColumn();
            $stats['document_count']  = (int)$pdo->query("SELECT COUNT(*) FROM atom_documents")->fetchColumn();
            $stats['training_count']  = (int)$pdo->query("SELECT COUNT(*) FROM atom_training_examples")->fetchColumn();
            $stats['optimized_count'] = (int)$pdo->query("SELECT COUNT(*) FROM atom_training_examples WHERE quality = 'VERIFIED'")->fetchColumn();
            $stats['duplicate_count'] = (int)$pdo->query("SELECT COUNT(*) FROM atom_training_examples WHERE quality = 'REJECTED'")->fetchColumn();
            $stats['conversations']   = (int)$pdo->query("SELECT COUNT(*) FROM atom_sessions")->fetchColumn();

            // Deduct 2 points for every unverified/unreviewed training record.
            $unreviewed = (int)$pdo->query("SELECT COUNT(*) FROM atom_training_examples WHERE quality = 'UNREVIEWED'")->fetchColumn();
            $stats['health_score'] = max(50, min(100, 100 - ($unreviewed * 2)));
        } catch (\Exception $e) {
            // Leave defaults on failure — matches prior fallback behavior.
        }

        return $stats;
    }
}
