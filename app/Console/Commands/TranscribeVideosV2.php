<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\VideoTranscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\progress;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;

class TranscribeVideosV2 extends Command
{
    protected $signature = 'videos:transcribe-v2
                            {--committee=* : Committee ID(s) to process}
                            {--meeting= : Specific meeting ID to process}
                            {--since= : Process meetings since date (YYYY-MM-DD)}
                            {--limit=10 : Maximum number of meetings to process}
                            {--overwrite : Overwrite existing transcriptions}
                            {--dry-run : Show what would be processed without actual transcription}';

    protected $description = 'Stream video to audio and transcribe using ElevenLabs (no downloads)';

    public function handle(): int
    {
        info("🎬 Starting streamlined video transcription process...");

        $committees = $this->getCommitteesToProcess();
        $totalMeetings = 0;
        $totalVideos = 0;
        $videosProcessed = 0;
        $transcriptionsCompleted = 0;
        $transcriptionsFailed = 0;

        foreach ($committees as $committee) {
            $meetings = $this->getMeetingsForCommittee($committee);

            if (empty($meetings)) {
                continue;
            }

            $totalMeetings += count($meetings);

            progress(
                label: "Processing meetings for {$committee->committee_name}",
                steps: $meetings,
                callback: function ($meeting, $progress) use (
                    $committee,
                    &$totalVideos,
                    &$videosProcessed,
                    &$transcriptionsCompleted,
                    &$transcriptionsFailed
                ) {
                    $meetingIdKey = $meeting['A_Cm_Sitid'] ?? $meeting['meetingId'] ?? 'unknown';
                    $progress->label("Processing meeting {$meetingIdKey}");

                    $videoUrls = $this->extractVideoUrls($meeting);
                    $totalVideos += count($videoUrls);

                    foreach ($videoUrls as $videoUrl) {
                        $videosProcessed++;

                        if ($this->option('dry-run')) {
                            info("Would process: {$videoUrl}");
                            continue;
                        }

                        $result = $this->processVideoUrl(
                            $videoUrl,
                            $meetingIdKey,
                            $committee->committee_id
                        );

                        if ($result === 'completed') {
                            $transcriptionsCompleted++;
                        } elseif ($result === 'failed') {
                            $transcriptionsFailed++;
                        }
                    }
                }
            );
        }

        $this->displayStats($totalMeetings, $totalVideos, $videosProcessed, $transcriptionsCompleted, $transcriptionsFailed);

        return Command::SUCCESS;
    }

    private function processVideoUrl(string $videoUrl, string $meetingId, string $committeeId): string
    {
        $fileName = basename($videoUrl);

        $existingTranscription = VideoTranscription::where('meeting_id', $meetingId)
            ->where('video_filename', $fileName)
            ->first();

        if ($existingTranscription && !$this->option('overwrite')) {
            if ($existingTranscription->status === 'completed') {
                return 'skipped';
            }
        }

        $tempAudioFile = tempnam(sys_get_temp_dir(), 'parliament_audio_') . '.mp3';

        try {
            info("🎵 Extracting audio from: {$fileName}");

            $ffmpegCommand = sprintf(
                'ffmpeg -i "%s" -vn -acodec libmp3lame -ab 128k "%s" -y -loglevel error',
                $videoUrl,
                $tempAudioFile
            );

            $process = proc_open($ffmpegCommand, [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ], $pipes);

            if (!is_resource($process)) {
                error("Failed to start ffmpeg process");
                return 'failed';
            }

            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            if ($returnCode !== 0) {
                error("ffmpeg failed: {$error}");
                if (File::exists($tempAudioFile)) {
                    File::delete($tempAudioFile);
                }
                return 'failed';
            }

            if (!File::exists($tempAudioFile) || File::size($tempAudioFile) < 1000) {
                error("Audio extraction failed or file too small");
                if (File::exists($tempAudioFile)) {
                    File::delete($tempAudioFile);
                }
                return 'failed';
            }

            $fileSize = File::size($tempAudioFile);
            info("✅ Audio extracted: " . $this->formatFileSize($fileSize));

            $exitCode = Artisan::call('audio:transcribe', [
                'file' => $tempAudioFile,
                '--meeting-id' => $meetingId,
                '--committee-id' => $committeeId,
                '--model' => 'scribe_v1',
                '--timeout' => 1800,
            ]);

            return $exitCode === 0 ? 'completed' : 'failed';

        } catch (\Exception $e) {
            error("Processing failed: {$e->getMessage()}");

            if (File::exists($tempAudioFile)) {
                File::delete($tempAudioFile);
            }

            return 'failed';
        }
    }

    private function getCommitteesToProcess()
    {
        $committeeIds = $this->option('committee');
        $meetingId = $this->option('meeting');

        if ($meetingId) {
            $placeholder = new Committee();
            $placeholder->committee_id = 'direct';
            $placeholder->committee_name = 'Direct Meeting Processing';
            return collect([$placeholder]);
        }

        if (empty($committeeIds)) {
            return Committee::all();
        }

        return Committee::whereIn('committee_id', $committeeIds)->get();
    }

    private function getMeetingsForCommittee($committee): array
    {
        $meetingId = $this->option('meeting');

        if ($meetingId) {
            return $this->fetchMeetingData($meetingId);
        }

        $since = $this->option('since');
        $limit = (int) $this->option('limit');

        try {
            $year = date('Y');
            $month = date('n');
            $url = "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/{$year}/{$month}/{$committee->committee_id}/0";
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                warning("Failed to fetch meetings for committee {$committee->committee_id}");
                return [];
            }

            $data = $response->json() ?? [];
            if (!is_array($data)) {
                return [];
            }

            $meetings = [];
            foreach ($data as $meeting) {
                if (isset($meeting['t_id'])) {
                    $meetingData = $this->fetchMeetingData($meeting['t_id']);
                    if ($meetingData) {
                        $meetings[] = $meetingData;
                    }
                }
            }
            
            return array_slice($meetings, 0, $limit);

        } catch (\Exception $e) {
            warning("Error fetching meetings: {$e->getMessage()}");
            return [];
        }
    }

    private function fetchMeetingData(string $meetingId): array
    {
        try {
            $response = Http::timeout(30)->get("https://www.parliament.bg/api/v1/com-meeting/bg/{$meetingId}");

            if (!$response->successful()) {
                error("Failed to fetch meeting {$meetingId}");
                return [];
            }

            $data = $response->json();
            return $data ? [$data] : [];

        } catch (\Exception $e) {
            error("Error fetching meeting {$meetingId}: {$e->getMessage()}");
            return [];
        }
    }

    private function extractVideoUrls(array $meetingData): array
    {
        $videos = [];
        
        if (isset($meetingData['video']) && is_array($meetingData['video'])) {
            $videoData = $meetingData['video'];
            
            if (isset($videoData['playlist']) && is_array($videoData['playlist'])) {
                foreach ($videoData['playlist'] as $video) {
                    if (is_string($video) && !empty($video)) {
                        if (!str_starts_with($video, 'http')) {
                            $video = 'https://www.parliament.bg' . $video;
                        }
                        $videos[] = $video;
                    }
                }
            }
            
            if (isset($videoData['default']) && !empty($videoData['default'])) {
                $defaultVideo = $videoData['default'];
                if (!str_starts_with($defaultVideo, 'http')) {
                    $defaultVideo = 'https://www.parliament.bg' . $defaultVideo;
                }
                $videos[] = $defaultVideo;
            }
            
            if (isset($videoData['Vifile']) && !empty($videoData['Vifile']) &&
                isset($videoData['Vicount']) && $videoData['Vicount'] > 0) {
                $vifile = $videoData['Vifile'];
                $vicount = intval($videoData['Vicount']);
                
                for ($i = 1; $i <= $vicount; $i++) {
                    $videoUrl = "https://www.parliament.bg/Gallery/videoCW/{$vifile}Part{$i}.mp4";
                    $videos[] = $videoUrl;
                }
            }
            
            if (empty($videos) && isset($videoData['Vidate']) && isset($meetingData['A_Cm_Sitid'])) {
                $date = $videoData['Vidate'];
                $meetingId = $meetingData['A_Cm_Sitid'];
                $committeeId = $meetingData['A_ns_CL_id'] ?? '';
                
                $dateFormatted = str_replace('-', '', $date);
                $videoUrl = "https://www.parliament.bg/Gallery/videoCW/autorecord/{$date}/{$dateFormatted}-{$meetingId}-{$committeeId}_Part1.mp4";
                $videos[] = $videoUrl;
            }
        }
        
        return array_unique($videos);
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function displayStats(int $meetings, int $videos, int $processed, int $completed, int $failed): void
    {
        $this->newLine();
        info("📊 Transcription Statistics:");
        info("• Meetings processed: {$meetings}");
        info("• Videos found: {$videos}");
        info("• Videos processed: {$processed}");
        info("• Videos skipped: " . ($videos - $processed));
        info("• Transcriptions completed: {$completed}");
        info("• Transcriptions failed: {$failed}");

        if ($completed === 0 && $failed > 0) {
            warning("No transcriptions were completed. Check the errors above.");
        } elseif ($completed > 0) {
            info("🎉 Successfully completed {$completed} transcription(s)!");
        }
    }
}
