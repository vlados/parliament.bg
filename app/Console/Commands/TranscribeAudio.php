<?php

namespace App\Console\Commands;

use App\Models\VideoTranscription;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\warning;

class TranscribeAudio extends Command
{
    protected $signature = 'audio:transcribe 
                            {file : Audio file path to transcribe}
                            {--meeting-id= : Meeting ID for database record}
                            {--committee-id= : Committee ID for database record}
                            {--model=scribe_v1 : ElevenLabs model to use}
                            {--timeout=1800 : API timeout in seconds}';

    protected $description = 'Transcribe an audio file using ElevenLabs Speech-to-Text API';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $meetingId = $this->option('meeting-id');
        $committeeId = $this->option('committee-id');
        $model = $this->option('model');
        $timeout = (int) $this->option('timeout');

        if (!File::exists($filePath)) {
            error("Audio file not found: {$filePath}");
            return Command::FAILURE;
        }

        $fileSize = File::size($filePath);
        $fileName = basename($filePath);

        info("🎤 Starting transcription for: {$fileName}");
        info("📄 File size: " . $this->formatFileSize($fileSize));

        $transcriptionRecord = null;
        
        if ($meetingId && $committeeId) {
            $transcriptionRecord = VideoTranscription::updateOrCreate([
                'meeting_id' => $meetingId,
                'video_filename' => $fileName,
            ], [
                'committee_id' => $committeeId,
                'video_filepath' => $filePath,
                'file_size_bytes' => $fileSize,
                'status' => 'processing',
                'elevenlabs_model_id' => $model,
                'transcription_started_at' => now(),
            ]);
        }

        try {
            $response = Http::timeout($timeout)
                ->attach('file', fopen($filePath, 'r'), $fileName)
                ->post('https://api.elevenlabs.io/v1/speech-to-text', [
                    'model_id' => $model,
                    'language_code' => 'bg',
                    'word_timestamps' => true,
                    'speaker_diarization' => true,
                ], [
                    'xi-api-key' => config('services.elevenlabs.api_key'),
                ]);

            if (!$response->successful()) {
                $errorMsg = "API request failed: {$response->status()} - {$response->body()}";
                error($errorMsg);
                
                if ($transcriptionRecord) {
                    $transcriptionRecord->update([
                        'status' => 'failed',
                        'error_message' => $errorMsg,
                        'transcription_completed_at' => now(),
                    ]);
                }
                
                return Command::FAILURE;
            }

            $data = $response->json();
            
            info("✅ Transcription completed successfully!");
            info("🎯 Language: {$data['language_code']} (confidence: " . 
                 round($data['language_probability'] * 100, 1) . "%)");
            
            if (isset($data['audio_duration_seconds'])) {
                info("⏱️  Duration: " . $this->formatDuration($data['audio_duration_seconds']));
            }

            if ($transcriptionRecord) {
                $transcriptionRecord->update([
                    'transcription_text' => $data['text'] ?? null,
                    'language_code' => $data['language_code'] ?? null,
                    'language_probability' => $data['language_probability'] ?? null,
                    'word_timestamps' => $data['word_timestamps'] ?? null,
                    'speaker_diarization' => $data['speaker_diarization'] ?? null,
                    'audio_duration_seconds' => $data['audio_duration_seconds'] ?? null,
                    'api_response_metadata' => $data,
                    'status' => 'completed',
                    'transcription_completed_at' => now(),
                ]);
                
                info("💾 Transcription saved to database");
            }

            $this->newLine();
            $this->line("📝 Transcription text:");
            $this->line("─────────────────────");
            $this->line($data['text'] ?? 'No text returned');

            return Command::SUCCESS;

        } catch (ConnectionException $e) {
            $errorMsg = "Connection timeout or error: {$e->getMessage()}";
            error($errorMsg);
            
            if ($transcriptionRecord) {
                $transcriptionRecord->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'transcription_completed_at' => now(),
                ]);
            }
            
            return Command::FAILURE;
        } catch (\Exception $e) {
            $errorMsg = "Transcription failed: {$e->getMessage()}";
            error($errorMsg);
            
            if ($transcriptionRecord) {
                $transcriptionRecord->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'transcription_completed_at' => now(),
                ]);
            }
            
            return Command::FAILURE;
        } finally {
            if (File::exists($filePath)) {
                File::delete($filePath);
                info("🗑️  Temporary audio file deleted");
            }
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }
    }
}
