<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\CustomerReview;

class SentimentRadarService
{
    /**
     * Analyze actual customer reviews to detect sentiment trends, compliments, and complaints.
     * Never fabricates customer reviews.
     */
    public function analyzeSentiment(Business $business): array
    {
        $reviews = CustomerReview::where('business_id', $business->id)->latest()->get();
        $totalReviews = $reviews->count();

        if ($totalReviews === 0) {
            return [
                'has_data' => false,
                'total_reviews' => 0,
                'average_rating' => 0.0,
                'sentiment_breakdown' => [
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                ],
                'common_compliments' => [],
                'common_complaints' => [],
                'product_satisfaction' => 0.0,
                'service_satisfaction' => 0.0,
                'message' => 'No customer reviews collected yet. Share your digital menu QR with diners to collect verified feedback.',
            ];
        }

        $avgRating = round((float) $reviews->avg('rating'), 1);

        $positiveCount = $reviews->where('rating', '>=', 4)->count();
        $neutralCount = $reviews->where('rating', 3)->count();
        $negativeCount = $reviews->where('rating', '<=', 2)->count();

        // Extract keyword patterns from real comments
        $compliments = [];
        $complaints = [];

        $positiveKeywords = [
            'delicious' => 'Delicious taste & flavor',
            'fast' => 'Quick service & fast preparation',
            'clean' => 'Clean & welcoming atmosphere',
            'fresh' => 'Fresh ingredients & quality',
            'friendly' => 'Friendly & attentive staff',
            'great' => 'Great overall value & experience',
            'hot' => 'Served fresh and hot',
        ];

        $negativeKeywords = [
            'slow' => 'Slow preparation or waiting time',
            'cold' => 'Food served lukewarm',
            'salt' => 'Over-seasoned or salty',
            'sweet' => 'Too sweet or high sugar',
            'small' => 'Portion size smaller than expected',
            'missing' => 'Missing item or wrong order',
            'expensive' => 'Priced higher than expected',
        ];

        foreach ($reviews as $rev) {
            $commentLower = strtolower($rev->comment ?? '');
            if (empty($commentLower)) {
                continue;
            }

            foreach ($positiveKeywords as $kw => $label) {
                if (str_contains($commentLower, $kw)) {
                    $compliments[$label] = ($compliments[$label] ?? 0) + 1;
                }
            }

            foreach ($negativeKeywords as $kw => $label) {
                if (str_contains($commentLower, $kw)) {
                    $complaints[$label] = ($complaints[$label] ?? 0) + 1;
                }
            }
        }

        arsort($compliments);
        arsort($complaints);

        return [
            'has_data' => true,
            'total_reviews' => $totalReviews,
            'average_rating' => $avgRating,
            'sentiment_breakdown' => [
                'positive' => round(($positiveCount / $totalReviews) * 100),
                'neutral' => round(($neutralCount / $totalReviews) * 100),
                'negative' => round(($negativeCount / $totalReviews) * 100),
            ],
            'common_compliments' => array_slice(array_keys($compliments), 0, 5),
            'common_complaints' => array_slice(array_keys($complaints), 0, 5),
            'product_satisfaction' => round(min(100, ($avgRating / 5) * 100)),
            'service_satisfaction' => round(min(100, (($avgRating * 0.95) / 5) * 100)),
            'analyzed_at' => now()->toIso8601String(),
        ];
    }
}
