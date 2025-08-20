<?php

namespace App\Services;

use App\Models\BillAnalysis;
use App\Models\Transcript;
use App\Models\Bill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TranscriptAnalysisService
{
    private ?string $apiKey;
    private string $apiEndpoint;
    private string $model;

    public function __construct()
    {
        // Configure API settings - you can use either OpenAI or Anthropic Claude
        $this->apiKey = config('services.openai.api_key') ?? config('services.anthropic.api_key');
        $this->apiEndpoint = config('services.openai.api_key') ? 'https://api.openai.com/v1/chat/completions' : 'https://api.anthropic.com/v1/messages';
        $this->model = config('services.openai.api_key') ? 'gpt-4o-mini' : 'claude-3-haiku-20240307';
    }

    /**
     * Analyze a single transcript for bill discussions
     */
    public function analyzeTranscript(Transcript $transcript): Collection
    {
        if (!$this->apiKey) {
            throw new \Exception("No API key configured. Please set either OPENAI_API_KEY or ANTHROPIC_API_KEY in your .env file.");
        }

        try {
            Log::info("Starting analysis for transcript ID: {$transcript->id}");

            // Get transcript content
            $content = $this->prepareTranscriptContent($transcript);
            
            if (empty($content)) {
                Log::warning("No content found for transcript ID: {$transcript->id}");
                return collect();
            }

            // Split content into chunks if too large
            $chunks = $this->splitContentIntoChunks($content);
            $allAnalyses = collect();

            foreach ($chunks as $chunkIndex => $chunk) {
                Log::info("Analyzing chunk {$chunkIndex} for transcript ID: {$transcript->id}");
                
                $chunkAnalyses = $this->analyzeContentChunk($chunk);
                
                foreach ($chunkAnalyses as $analysis) {
                    $billAnalysis = $this->createBillAnalysis($transcript, $analysis);
                    if ($billAnalysis) {
                        $allAnalyses->push($billAnalysis);
                    }
                }
            }

            Log::info("Completed analysis for transcript ID: {$transcript->id}. Found {$allAnalyses->count()} bill discussions");
            
            return $allAnalyses;

        } catch (\Exception $e) {
            Log::error("Error analyzing transcript ID {$transcript->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare transcript content for analysis
     */
    private function prepareTranscriptContent(Transcript $transcript): string
    {
        $content = $transcript->content_text ?? $transcript->extractTextFromHtml();
        
        // Clean and normalize the content
        $content = $this->cleanTranscriptContent($content);
        
        return $content;
    }

    /**
     * Clean transcript content
     */
    private function cleanTranscriptContent(string $content): string
    {
        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove common transcript artifacts
        $content = preg_replace('/\[.*?\]/', '', $content); // Remove bracketed content
        $content = preg_replace('/\d{2}:\d{2}:\d{2}/', '', $content); // Remove timestamps
        
        return trim($content);
    }

    /**
     * Split content into manageable chunks
     */
    private function splitContentIntoChunks(string $content, int $maxChunkSize = 8000): array
    {
        if (strlen($content) <= $maxChunkSize) {
            return [$content];
        }

        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $content);
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . $sentence) > $maxChunkSize && !empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Analyze a content chunk using AI
     */
    private function analyzeContentChunk(string $content): array
    {
        $prompt = $this->buildAnalysisPrompt($content);
        
        if (config('services.openai.api_key')) {
            return $this->callOpenAI($prompt);
        } else {
            return $this->callClaude($prompt);
        }
    }

    /**
     * Build the analysis prompt
     */
    private function buildAnalysisPrompt(string $content): string
    {
        return "
Анализирай следния текст от стенограма на българския парламент и идентифицирай обсъжданията на законопроекти.

ЗАДАЧА: Извлечи структурирана информация за всички споменати законопроекти, предложени изменения и гласувания.

ТЕКСТ ЗА АНАЛИЗ:
{$content}

ИЗИСКВАНИЯ:
1. Идентифицирай всички споменати законопроекти (обикновено с номера като 'ПЗ №123', 'Проект № 456', 'законопроект №789', etc.)
2. За всеки законопроект извлечи:
   - Номера/идентификатора на законопроекта
   - Кой предлага изменението (име на депутат/министър)
   - Какъв тип изменение се предлага (ново, изменение, заличаване)
   - Описание на предложеното изменение
   - Статус (предложено, одобрено, отхвърлено, в очакване)
   - Резултати от гласуване (ако има)
   - Контекст (кратък цитат от стенограмата)

ОТГОВОРИ В JSON ФОРМАТ:
{
  \"bill_discussions\": [
    {
      \"bill_identifier\": \"ПЗ №123\",
      \"proposer_name\": \"Иван Петров\",
      \"amendment_type\": \"modification\",
      \"amendment_description\": \"Предлага изменение в член 5, ал. 2\",
      \"status\": \"proposed\",
      \"vote_results\": {\"for\": 120, \"against\": 30, \"abstained\": 5},
      \"confidence\": 0.95,
      \"raw_context\": \"Соответният цитат от стенограмата\"
    }
  ]
}

Ако няма споменати законопроекти, върни празен масив в bill_discussions.
Ако не си сигурен за някоя информация, постави null за съответното поле и намали confidence оценката.
";
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->post($this->apiEndpoint, [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ти си експерт анализатор на парламентарни стенограми. Отговаряй винаги на български език и в JSON формат.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 4000,
        ]);

        if (!$response->successful()) {
            throw new \Exception("OpenAI API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        return $this->parseAIResponse($content);
    }

    /**
     * Call Claude API
     */
    private function callClaude(string $prompt): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])
        ->timeout(120)
        ->post($this->apiEndpoint, [
            'model' => $this->model,
            'max_tokens' => 4000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception("Claude API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['content'][0]['text'] ?? '';
        
        return $this->parseAIResponse($content);
    }

    /**
     * Parse AI response and extract structured data
     */
    private function parseAIResponse(string $response): array
    {
        try {
            // Try to extract JSON from the response
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonData = json_decode($matches[0], true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['bill_discussions'])) {
                    return $jsonData['bill_discussions'];
                }
            }

            // If JSON parsing fails, try to parse manually
            return $this->fallbackResponseParsing($response);

        } catch (\Exception $e) {
            Log::warning("Failed to parse AI response: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fallback parsing for non-JSON responses
     */
    private function fallbackResponseParsing(string $response): array
    {
        // Simple fallback - look for bill identifiers
        $analyses = [];
        
        if (preg_match_all('/(?:ПЗ|Проект|законопроект)\s*№?\s*(\d+)/ui', $response, $matches)) {
            foreach ($matches[1] as $billNumber) {
                $analyses[] = [
                    'bill_identifier' => "ПЗ №{$billNumber}",
                    'proposer_name' => null,
                    'amendment_type' => null,
                    'amendment_description' => "Споменат в стенограмата (автоматично извлечен)",
                    'status' => 'pending',
                    'vote_results' => null,
                    'confidence' => 0.3,
                    'raw_context' => substr($response, 0, 500)
                ];
            }
        }

        return $analyses;
    }

    /**
     * Create BillAnalysis record from analysis data
     */
    private function createBillAnalysis(Transcript $transcript, array $analysisData): ?BillAnalysis
    {
        try {
            // Try to find matching bill by identifier
            $bill = null;
            if (!empty($analysisData['bill_identifier'])) {
                $bill = $this->findBillByIdentifier($analysisData['bill_identifier']);
            }

            $billAnalysis = BillAnalysis::create([
                'transcript_id' => $transcript->id,
                'bill_id' => $bill?->id,
                'bill_identifier' => $analysisData['bill_identifier'] ?? null,
                'proposer_name' => $analysisData['proposer_name'] ?? null,
                'amendment_type' => $analysisData['amendment_type'] ?? null,
                'amendment_description' => $analysisData['amendment_description'] ?? null,
                'status' => $analysisData['status'] ?? 'pending',
                'vote_results' => $analysisData['vote_results'] ?? null,
                'ai_confidence' => $analysisData['confidence'] ?? null,
                'raw_context' => $analysisData['raw_context'] ?? null,
                'metadata' => [
                    'analyzed_at' => now()->toISOString(),
                    'ai_model' => $this->model,
                    'transcript_date' => $transcript->transcript_date?->toISOString(),
                ],
            ]);

            Log::info("Created bill analysis ID: {$billAnalysis->id} for transcript ID: {$transcript->id}");
            
            return $billAnalysis;

        } catch (\Exception $e) {
            Log::error("Error creating bill analysis: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find bill by identifier
     */
    private function findBillByIdentifier(string $identifier): ?Bill
    {
        // Clean up the identifier
        $cleanIdentifier = preg_replace('/[^\d]/', '', $identifier);
        
        if (empty($cleanIdentifier)) {
            return null;
        }

        // Try to find by various fields
        return Bill::where('bill_id', $cleanIdentifier)
            ->orWhere('sign', 'LIKE', "%{$cleanIdentifier}%")
            ->orWhere('signature', 'LIKE', "%{$cleanIdentifier}%")
            ->first();
    }

    /**
     * Analyze multiple transcripts
     */
    public function analyzeMultipleTranscripts(Collection $transcripts): Collection
    {
        $allAnalyses = collect();

        foreach ($transcripts as $transcript) {
            $analyses = $this->analyzeTranscript($transcript);
            $allAnalyses = $allAnalyses->merge($analyses);
        }

        return $allAnalyses;
    }

    /**
     * Get analysis statistics
     */
    public function getAnalysisStatistics(): array
    {
        return [
            'total_analyses' => BillAnalysis::count(),
            'high_confidence' => BillAnalysis::highConfidence()->count(),
            'low_confidence' => BillAnalysis::lowConfidence()->count(),
            'by_status' => BillAnalysis::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_amendment_type' => BillAnalysis::selectRaw('amendment_type, COUNT(*) as count')
                ->whereNotNull('amendment_type')
                ->groupBy('amendment_type')
                ->pluck('count', 'amendment_type')
                ->toArray(),
        ];
    }
}