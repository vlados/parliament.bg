<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LangExtractService
{
    private string $pythonPath;
    private string $scriptsPath;
    
    public function __construct()
    {
        // Use virtual environment Python if it exists
        $venvPath = base_path('venv/bin/python');
        if (file_exists($venvPath)) {
            $this->pythonPath = $venvPath;
        } else {
            $this->pythonPath = config('services.python.path', 'python3');
        }
        
        $this->scriptsPath = base_path('python_scripts');
        
        // Ensure scripts directory exists
        if (!file_exists($this->scriptsPath)) {
            mkdir($this->scriptsPath, 0755, true);
        }
    }
    
    /**
     * Extract protocol changes from transcript content
     */
    public function extractProtocolChanges(string $content, array $options = []): array
    {
        try {
            // Save content to temporary file
            $tempFile = $this->scriptsPath . '/temp_transcript_' . uniqid() . '.txt';
            file_put_contents($tempFile, $content);
            
            // Run Python extraction script
            $scriptPath = $this->scriptsPath . '/extract_protocol_changes.py';
            $command = sprintf(
                '%s %s %s',
                $this->pythonPath,
                escapeshellarg($scriptPath),
                escapeshellarg($tempFile)
            );
            
            if (!empty($options)) {
                $command .= ' ' . escapeshellarg(json_encode($options));
            }
            
            $result = Process::run($command);
            
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            if ($result->failed()) {
                Log::error('LangExtract failed', [
                    'error' => $result->errorOutput(),
                    'command' => $command
                ]);
                return ['error' => 'Extraction failed: ' . $result->errorOutput()];
            }
            
            // Parse JSON output
            $output = json_decode($result->output(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse LangExtract output', [
                    'output' => $result->output(),
                    'json_error' => json_last_error_msg()
                ]);
                return ['error' => 'Failed to parse extraction results'];
            }
            
            return $output;
            
        } catch (\Exception $e) {
            Log::error('LangExtract service error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Extract bill discussions from transcript
     */
    public function extractBillDiscussions(string $content): array
    {
        $options = [
            'extraction_type' => 'bill_discussions',
            'include_speakers' => true,
            'include_votes' => true,
            'include_amendments' => true
        ];
        
        return $this->extractProtocolChanges($content, $options);
    }
    
    /**
     * Extract committee decisions from transcript
     */
    public function extractCommitteeDecisions(string $content): array
    {
        $options = [
            'extraction_type' => 'committee_decisions',
            'include_voting_results' => true,
            'include_participants' => true
        ];
        
        return $this->extractProtocolChanges($content, $options);
    }
    
    /**
     * Extract speaker statements from transcript
     */
    public function extractSpeakerStatements(string $content): array
    {
        $options = [
            'extraction_type' => 'speaker_statements',
            'group_by_speaker' => true,
            'include_timestamps' => true
        ];
        
        return $this->extractProtocolChanges($content, $options);
    }
    
    /**
     * Check if Python and required packages are installed
     */
    public function checkDependencies(): array
    {
        $checks = [];
        
        // Check Python
        $pythonCheck = Process::run(sprintf('%s --version', $this->pythonPath));
        $checks['python'] = [
            'installed' => $pythonCheck->successful(),
            'version' => $pythonCheck->successful() ? trim($pythonCheck->output()) : null
        ];
        
        // Check LangExtract
        $langExtractCheck = Process::run(sprintf('%s -c "import langextract; print(\'LangExtract available\')"', $this->pythonPath));
        $checks['langextract'] = [
            'installed' => $langExtractCheck->successful(),
            'version' => $langExtractCheck->successful() ? trim($langExtractCheck->output()) : null
        ];
        
        // Check Gemini API configuration
        $checks['gemini_api'] = [
            'configured' => !empty(config('services.google.gemini_api_key'))
        ];
        
        return $checks;
    }
    
    /**
     * Install Python dependencies
     */
    public function installDependencies(): bool
    {
        $commands = [
            'pip install --upgrade pip',
            'pip install langextract',
            'pip install google-generativeai'
        ];
        
        foreach ($commands as $command) {
            $result = Process::run($command);
            if ($result->failed()) {
                Log::error('Failed to install dependency', [
                    'command' => $command,
                    'error' => $result->errorOutput()
                ]);
                return false;
            }
        }
        
        return true;
    }
}