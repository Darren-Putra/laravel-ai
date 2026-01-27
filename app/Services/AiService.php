<?php

namespace App\Services;

class AiService
{
    /**
     * Menghitung kemiripan antara dua vektor.
     * Hasilnya 0 (tidak mirip) sampai 1 (sangat mirip).
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        
        foreach ($vec1 as $i => $val) {
            $dotProduct += $val * ($vec2[$i] ?? 0);
            $normA += pow($val, 2);
            $normB += pow(($vec2[$i] ?? 0), 2);
        }
        
        if ($normA == 0 || $normB == 0) return 0;
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}