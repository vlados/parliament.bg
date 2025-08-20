<?php

namespace App\Console\Commands;

use App\Models\Committee;
use App\Models\VideoTranscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\progress;

class TranscribeVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:transcribe
                            {--directory= : Path to directory containing video files (legacy mode)}
                            {--committee=* : Committee IDs to transcribe videos for}
                            {--meeting=* : Specific meeting IDs to process}
                            {--all : Process all committees}
                            {--year= : Year to process videos from}
                            {--month= : Month to process videos from}
                            {--from= : Process videos from this date (YYYY-MM-DD)}
                            {--to= : Process videos to this date (YYYY-MM-DD)}
                            {--model=eleven_english_turbo_v2 : ElevenLabs model to use for transcription}
                            {--language= : Language code (e.g. en, bg) - leave empty for auto-detection}
                            {--speakers= : Number of speakers for diarization}
                            {--overwrite : Re-transcribe already processed videos}
                            {--dry-run : Show what would be transcribed without actually doing it}
                            {--batch-size=3 : Number of concurrent transcription requests}
                            {--use-files : Use downloaded files instead of direct URLs (legacy mode)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transcribe committee meeting videos directly from parliament.bg URLs using ElevenLabs Speech-to-Text API';

    private array $stats = [
        'meetings_found' => 0,
        'videos_found' => 0,
        'videos_processed' => 0,
        'videos_skipped' => 0,
        'transcriptions_completed' => 0,
        'transcriptions_failed' => 0,
        'total_duration' => 0,
        'total_cost' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        info('🎙️  Starting video transcription process...');

        // Check API key
        if (!config('services.elevenlabs.api_key')) {
            error('ElevenLabs API key not configured. Please set ELEVENLABS_API_KEY in your .env file.');
            return 1;
        }

        // Legacy mode: use downloaded files
        if ($this->option('use-files') || $this->option('directory')) {
            return $this->handleLegacyMode();
        }

        // New mode: use direct URLs from parliament.bg
        return $this->handleUrlMode();
    }

    /**
     * Handle URL-based transcription (default mode)
     */
    private function handleUrlMode(): int
    {
        // Get committees to process
        $committees = $this->getCommitteesToProcess();
        if ($committees->isEmpty()) {
            error('No committees selected.');
            return 1;
        }

        // Get date range
        $dateParams = $this->getDateParameters();

        info("Processing " . $committees->count() . " committee(s)...");

        if ($this->option('dry-run')) {
            info('🔍 DRY RUN MODE - No transcriptions will be performed');
        }

        // Process each committee
        foreach ($committees as $committee) {
            $this->processCommitteeVideos($committee, $dateParams);
        }

        // Show final statistics
        $this->showFinalStats();

        return 0;
    }

    /**
     * Handle legacy file-based transcription
     */
    private function handleLegacyMode(): int
    {
        info('📁 Running in legacy mode (using downloaded files)...');
        
        // Get video directory
        $directory = $this->getVideoDirectory();
        if (!$directory) {
            return 1;
        }

        // Find video files
        $videoFiles = $this->findVideoFiles($directory);
        if (empty($videoFiles)) {
            warning('No video files found in the specified directory.');
            return 0;
        }

        $this->stats['videos_found'] = count($videoFiles);
        info("Found {$this->stats['videos_found']} video files");

        if ($this->option('dry-run')) {
            info('🔍 DRY RUN MODE - No transcriptions will be performed');
            $this->showVideoFiles($videoFiles);
            return 0;
        }

        // Process video files
        $this->processVideoFiles($videoFiles);

        // Show final statistics
        $this->showFinalStats();

        return 0;
    }

    /**
     * Get committees to process
     */
    private function getCommitteesToProcess()
    {
        if ($this->option('all')) {
            return Committee::orderBy('name')->get();
        }

        $committeeIds = $this->option('committee');
        $meetingIds = $this->option('meeting');

        // If specific meetings are provided, get committees from those meetings
        if (!empty($meetingIds)) {
            // We'll need to fetch meeting data to get committee IDs
            $committees = collect();
            foreach ($meetingIds as $meetingId) {
                $meetingData = $this->fetchMeetingData($meetingId);
                if ($meetingData && isset($meetingData['A_ns_CL_id'])) {
                    $committee = Committee::where('committee_id', $meetingData['A_ns_CL_id'])->first();
                    if ($committee && !$committees->contains('committee_id', $committee->committee_id)) {
                        $committees->push($committee);
                    }
                } else {
                    // If we can't fetch meeting data, create a placeholder committee
                    // This allows processing meetings directly without committee lookup
                    $placeholder = new Committee();
                    $placeholder->committee_id = 'unknown';
                    $placeholder->name = 'Direct Meeting Processing';
                    $committees->push($placeholder);
                }
            }
            return $committees;
        }

        if (!empty($committeeIds)) {
            return Committee::whereIn('committee_id', $committeeIds)->get();
        }

        // Interactive selection
        $committees = Committee::orderBy('name')->get();

        if ($committees->isEmpty()) {
            error('No committees found. Please run committees:scrape first.');
            return collect();
        }

        if (confirm('Do you want to select multiple committees?', true)) {
            $options = [];
            foreach ($committees as $committee) {
                $options[$committee->committee_id] = $committee->name;
            }

            $selected = multiselect(
                label: 'Select committees to transcribe videos for:',
                options: $options,
                required: true
            );

            return Committee::whereIn('committee_id', $selected)->get();
        } else {
            $options = [];
            foreach ($committees as $committee) {
                $options[$committee->committee_id] = $committee->name;
            }

            $selected = select(
                label: 'Select a committee to transcribe videos for:',
                options: $options
            );

            return Committee::where('committee_id', $selected)->get();
        }
    }

    /**
     * Get date parameters for filtering
     */
    private function getDateParameters(): array
    {
        $params = [];

        if ($this->option('from') && $this->option('to')) {
            $params['from'] = Carbon::parse($this->option('from'));
            $params['to'] = Carbon::parse($this->option('to'));
            $params['mode'] = 'range';
        } else {
            $year = $this->option('year') ?? Carbon::now()->year;
            $month = $this->option('month'); // Will be null if not specified = whole year

            $params['year'] = $year;
            $params['month'] = $month;
            $params['mode'] = 'period';
        }

        return $params;
    }

    /**
     * Process videos for a committee
     */
    private function processCommitteeVideos(Committee $committee, array $dateParams): void
    {
        info("\\n📁 Processing committee: {$committee->name}");

        // Get all meeting IDs for this committee
        $meetingIds = $this->option('meeting') 
            ? $this->option('meeting') 
            : ($committee->committee_id === 'unknown' ? [] : $this->fetchMeetingIds($committee->committee_id, $dateParams));

        if (empty($meetingIds)) {
            warning("No meetings found for {$committee->name}");
            return;
        }

        $this->stats['meetings_found'] += count($meetingIds);
        info("Found " . count($meetingIds) . " meetings");

        // Process each meeting
        $progress = progress(
            label: "Processing meetings for {$committee->name}",
            steps: count($meetingIds)
        );

        $progress->start();

        foreach ($meetingIds as $meetingId) {
            $this->processMeetingVideosFromUrl($meetingId, $committee->committee_id);
            $progress->advance();
        }

        $progress->finish();
    }

    /**
     * Fetch meeting IDs for a committee and date range
     */
    private function fetchMeetingIds(int $committeeId, array $dateParams): array
    {
        $meetingIds = [];

        if ($dateParams['mode'] === 'range') {
            // Fetch by date range - we need to iterate through months
            $start = $dateParams['from'];
            $end = $dateParams['to'];

            while ($start->lte($end)) {
                $ids = $this->fetchMeetingIdsForPeriod($committeeId, $start->year, $start->month);
                $meetingIds = array_merge($meetingIds, $ids);
                $start->addMonth();
            }
        } else {
            // Fetch by year/month
            if ($dateParams['month']) {
                // Specific month
                $meetingIds = $this->fetchMeetingIdsForPeriod(
                    $committeeId,
                    $dateParams['year'],
                    $dateParams['month']
                );
            } else {
                // Entire year
                for ($month = 1; $month <= 12; $month++) {
                    $ids = $this->fetchMeetingIdsForPeriod($committeeId, $dateParams['year'], $month);
                    $meetingIds = array_merge($meetingIds, $ids);
                }
            }
        }

        return array_unique($meetingIds);
    }

    /**
     * Fetch meeting IDs for a specific period
     */
    private function fetchMeetingIdsForPeriod(int $committeeId, int $year, int $month): array
    {
        $url = "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/{$year}/{$month}/{$committeeId}/0";

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                return [];
            }

            // Extract meeting IDs
            $meetingIds = [];
            foreach ($data as $meeting) {
                if (isset($meeting['t_id'])) {
                    $meetingIds[] = $meeting['t_id'];
                }
            }

            return $meetingIds;

        } catch (\Exception $e) {
            warning("Error fetching meetings for {$year}/{$month}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process videos for a meeting using direct URLs
     */
    private function processMeetingVideosFromUrl(int $meetingId, int $committeeId): void
    {
        try {
            // Fetch meeting metadata
            $meetingData = $this->fetchMeetingData($meetingId);

            if (!$meetingData) {
                $this->stats['transcriptions_failed']++;
                return;
            }

            // Extract video URLs
            $videos = $this->extractVideoUrls($meetingData);

            if (empty($videos)) {
                return; // No videos for this meeting
            }

            $this->stats['videos_found'] += count($videos);

            // Process each video URL
            foreach ($videos as $index => $videoUrl) {
                if ($this->option('dry-run')) {
                    info("  Found video URL: {$videoUrl}");
                }
                $this->transcribeVideoFromUrl($videoUrl, $meetingId, $committeeId, $index + 1, $meetingData);
            }

        } catch (\Exception $e) {
            warning("Error processing meeting {$meetingId}: " . $e->getMessage());
            $this->stats['transcriptions_failed']++;
        }
    }

    /**
     * Fetch meeting data from API
     */
    private function fetchMeetingData(int $meetingId): ?array
    {
        $url = "https://www.parliament.bg/api/v1/com-meeting/bg/{$meetingId}";

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract video URLs from meeting data
     */
    private function extractVideoUrls(array $meetingData): array
    {
        $videos = [];

        // Check if meeting has video data
        if (isset($meetingData['video']) && is_array($meetingData['video'])) {
            $videoData = $meetingData['video'];

            // Check for playlist (most common)
            if (isset($videoData['playlist']) && is_array($videoData['playlist'])) {
                foreach ($videoData['playlist'] as $video) {
                    if (is_string($video) && !empty($video)) {
                        // Make sure URL is absolute
                        if (!str_starts_with($video, 'http')) {
                            $video = 'https://www.parliament.bg' . $video;
                        }
                        $videos[] = $video;
                    }
                }
            }

            // Check for default video (fallback)
            if (isset($videoData['default']) && !empty($videoData['default'])) {
                $defaultVideo = $videoData['default'];
                if (!str_starts_with($defaultVideo, 'http')) {
                    $defaultVideo = 'https://www.parliament.bg' . $defaultVideo;
                }
                $videos[] = $defaultVideo;
            }

            // Check for constructed URLs based on Vifile pattern
            if (isset($videoData['Vifile']) && !empty($videoData['Vifile']) &&
                isset($videoData['Vicount']) && $videoData['Vicount'] > 0) {

                $vifile = $videoData['Vifile'];
                $vicount = intval($videoData['Vicount']);

                // Construct URLs for each part
                for ($i = 1; $i <= $vicount; $i++) {
                    $videoUrl = "https://www.parliament.bg/Gallery/videoCW/{$vifile}Part{$i}.mp4";
                    $videos[] = $videoUrl;
                }
            }

            // Alternative construction if we have date information
            if (empty($videos) && isset($videoData['Vidate']) && isset($meetingData['A_Cm_Sitid'])) {
                $date = $videoData['Vidate'];
                $meetingId = $meetingData['A_Cm_Sitid'];
                $committeeId = $meetingData['A_ns_CL_id'] ?? '';

                // Try to construct typical video URL pattern
                $dateFormatted = Carbon::parse($date)->format('Ymd');
                $videoUrl = "https://www.parliament.bg/Gallery/videoCW/autorecord/{$date}/{$dateFormatted}-{$meetingId}-{$committeeId}_Part1.mp4";
                $videos[] = $videoUrl;
            }
        }

        return array_unique($videos);
    }

    /**
     * Transcribe a video from URL
     */
    private function transcribeVideoFromUrl(string $videoUrl, int $meetingId, int $committeeId, int $videoIndex, array $meetingData): void
    {
        try {
            $videoFilename = basename(parse_url($videoUrl, PHP_URL_PATH)) ?: "meeting_{$meetingId}_video_{$videoIndex}.mp4";
            
            // Check if already processed
            if (!$this->option('overwrite')) {
                $existing = VideoTranscription::where('meeting_id', $meetingId)
                    ->where('video_filename', $videoFilename)
                    ->where('status', '!=', 'failed')
                    ->exists();
                    
                if ($existing) {
                    $this->stats['videos_skipped']++;
                    return;
                }
            }

            if ($this->option('dry-run')) {
                info("📹 Would transcribe: {$videoUrl} -> {$videoFilename}");
                return;
            }

            // Create or update database record
            $transcription = VideoTranscription::updateOrCreate(
                [
                    'meeting_id' => $meetingId,
                    'video_filename' => $videoFilename,
                ],
                [
                    'committee_id' => $committeeId,
                    'video_filepath' => $videoUrl, // Store URL instead of file path
                    'file_size_bytes' => null, // Unknown for URLs
                    'status' => 'processing',
                    'elevenlabs_model_id' => $this->option('model'),
                    'transcription_started_at' => now(),
                    'error_message' => null,
                ]
            );
            
            info("📹 Transcribing from URL: {$videoUrl}");
            
            // Call ElevenLabs API with URL
            $response = $this->callElevenLabsAPIWithUrl($videoUrl);
            
            if ($response['success']) {
                $transcription->update([
                    'status' => 'completed',
                    'transcription_text' => $response['data']['text'] ?? null,
                    'language_code' => $response['data']['language_code'] ?? null,
                    'language_probability' => $response['data']['language_probability'] ?? null,
                    'word_timestamps' => $response['data']['words'] ?? null,
                    'speaker_diarization' => $response['data']['speaker_diarization'] ?? null,
                    'audio_duration_seconds' => $response['data']['audio_duration'] ?? null,
                    'api_cost' => $response['cost'] ?? null,
                    'api_response_metadata' => $response['metadata'] ?? null,
                    'transcription_completed_at' => now(),
                ]);
                
                $this->stats['transcriptions_completed']++;
                $this->stats['total_duration'] += $response['data']['audio_duration'] ?? 0;
                $this->stats['total_cost'] += $response['cost'] ?? 0;
                
                info("✅ Completed: {$videoFilename}");
            } else {
                $transcription->update([
                    'status' => 'failed',
                    'error_message' => $response['error'] ?? 'Unknown error',
                    'transcription_completed_at' => now(),
                ]);
                
                $this->stats['transcriptions_failed']++;
                warning("❌ Failed: {$videoFilename} - {$response['error']}");
            }
            
            $this->stats['videos_processed']++;
            
        } catch (\Exception $e) {
            $this->stats['transcriptions_failed']++;
            warning("❌ Error transcribing {$videoUrl}: " . $e->getMessage());
        }
    }

    /**
     * Call ElevenLabs API with video URL
     */
    private function callElevenLabsAPIWithUrl(string $videoUrl): array
    {
        try {
            $apiKey = config('services.elevenlabs.api_key');
            
            // Since ElevenLabs requires cloud storage URLs but parliament.bg URLs are direct,
            // we need to use a workaround. We'll try using the URL as if it were a cloud storage URL
            // or download the video temporarily and use file upload
            
            // First, let's try to use the URL directly (this might not work but worth trying)
            $requestData = [
                'model_id' => $this->option('model'),
            ];
            
            // Add optional parameters
            if ($this->option('language')) {
                $requestData['language_code'] = $this->option('language');
            }
            
            if ($this->option('speakers')) {
                $requestData['num_speakers'] = $this->option('speakers');
            }

            // Try with cloud_storage_url parameter first
            $response = Http::withHeaders([
                'xi-api-key' => $apiKey,
            ])
            ->timeout(1800) // 30 minutes timeout for large files
            ->asForm()
            ->post('https://api.elevenlabs.io/v1/speech-to-text', array_merge($requestData, [
                'cloud_storage_url' => $videoUrl,
            ]));
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data,
                    'cost' => $this->estimateCost($data),
                    'metadata' => [
                        'api_response_time' => $response->transferStats?->getTransferTime(),
                        'api_status_code' => $response->status(),
                        'method' => 'cloud_storage_url',
                    ],
                ];
            } else {
                // If cloud_storage_url doesn't work, fall back to downloading the file
                return $this->fallbackToFileDownload($videoUrl);
            }
            
        } catch (\Exception $e) {
            // Try fallback method
            return $this->fallbackToFileDownload($videoUrl);
        }
    }

    /**
     * Fallback to downloading file and uploading to ElevenLabs
     */
    private function fallbackToFileDownload(string $videoUrl): array
    {
        try {
            // Create temporary files
            $tempVideo = tempnam(sys_get_temp_dir(), 'parliament_video_') . '.mp4';
            $tempAudio = tempnam(sys_get_temp_dir(), 'parliament_audio_') . '.mp3';
            
            info("⬇️  Downloading and converting video to audio...");
            
            // Download video using curl/wget for better handling of large files
            $downloadSuccess = $this->downloadVideoWithCurl($videoUrl, $tempVideo);
            
            if (!$downloadSuccess) {
                // Try wget as fallback
                $downloadSuccess = $this->downloadVideoWithWget($videoUrl, $tempVideo);
            }
            
            if (!$downloadSuccess) {
                throw new \Exception("Failed to download video from URL");
            }
            
            // Convert video to audio using ffmpeg
            info("🎵 Extracting audio from video...");
            $audioExtracted = $this->extractAudioFromVideo($tempVideo, $tempAudio);
            
            if (!$audioExtracted) {
                // If audio extraction fails, try to use the video file directly
                warning("Audio extraction failed, using video file directly...");
                $result = $this->callElevenLabsAPI($tempVideo);
            } else {
                // Use the extracted audio file
                $audioSize = filesize($tempAudio);
                $audioSizeMB = round($audioSize / (1024 * 1024), 2);
                info("✅ Audio extracted: {$audioSizeMB} MB");
                
                $result = $this->callElevenLabsAPI($tempAudio);
            }
            
            // Add metadata about method used
            if (isset($result['metadata'])) {
                $result['metadata']['method'] = 'audio_extraction_fallback';
                $result['metadata']['audio_size'] = $audioSize ?? null;
            }
            
            // Clean up temporary files
            if (file_exists($tempVideo)) unlink($tempVideo);
            if (file_exists($tempAudio)) unlink($tempAudio);
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Fallback download failed: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Download video using curl
     */
    private function downloadVideoWithCurl(string $url, string $filepath): bool
    {
        $escapedUrl = escapeshellarg($url);
        $escapedPath = escapeshellarg($filepath);
        
        // Use curl with resume capability and longer timeout
        $curlCommand = sprintf(
            'curl -L -C - --retry 3 --retry-delay 5 --connect-timeout 30 --max-time 3600 -o %s %s 2>&1',
            $escapedPath,
            $escapedUrl
        );
        
        $output = [];
        $exitCode = 0;
        exec($curlCommand, $output, $exitCode);
        
        return $exitCode === 0 && file_exists($filepath) && filesize($filepath) > 0;
    }
    
    /**
     * Download video using wget
     */
    private function downloadVideoWithWget(string $url, string $filepath): bool
    {
        $escapedUrl = escapeshellarg($url);
        $escapedPath = escapeshellarg($filepath);
        
        // Use wget with resume capability
        $wgetCommand = sprintf(
            'wget --continue --tries=3 --timeout=30 --read-timeout=3600 -O %s %s 2>&1',
            $escapedPath,
            $escapedUrl
        );
        
        $output = [];
        $exitCode = 0;
        exec($wgetCommand, $output, $exitCode);
        
        return $exitCode === 0 && file_exists($filepath) && filesize($filepath) > 0;
    }
    
    /**
     * Extract audio from video file using ffmpeg
     */
    private function extractAudioFromVideo(string $videoPath, string $audioPath): bool
    {
        if (!file_exists($videoPath)) {
            return false;
        }
        
        // Check if ffmpeg is available
        $ffmpegCheck = shell_exec('which ffmpeg 2>/dev/null');
        if (empty($ffmpegCheck)) {
            warning("ffmpeg not found. Please install ffmpeg to extract audio from videos.");
            return false;
        }
        
        $escapedVideo = escapeshellarg($videoPath);
        $escapedAudio = escapeshellarg($audioPath);
        
        // Extract audio as MP3 with good compression
        // -vn: no video
        // -acodec mp3: use MP3 codec
        // -ab 128k: 128 kbps bitrate (good quality for speech)
        // -ar 44100: 44.1 kHz sample rate
        $ffmpegCommand = sprintf(
            'ffmpeg -i %s -vn -acodec mp3 -ab 128k -ar 44100 %s -y 2>&1',
            $escapedVideo,
            $escapedAudio
        );
        
        $output = [];
        $exitCode = 0;
        exec($ffmpegCommand, $output, $exitCode);
        
        return $exitCode === 0 && file_exists($audioPath) && filesize($audioPath) > 0;
    }

    /**
     * Get the video directory to process
     */
    private function getVideoDirectory(): ?string
    {
        $directory = $this->option('directory');
        
        if (!$directory) {
            // Interactive selection of recently downloaded video directories
            $videoDirectories = $this->findVideoDirectories();
            
            if (empty($videoDirectories)) {
                error('No video directories found. Please specify --directory or download videos first using meetings:download-videos.');
                return null;
            }
            
            if (count($videoDirectories) === 1) {
                $directory = $videoDirectories[0];
                info("Using directory: {$directory}");
            } else {
                $options = [];
                foreach ($videoDirectories as $dir) {
                    $basename = basename($dir);
                    $options[$dir] = $basename;
                }
                
                $directory = select(
                    label: 'Select a video directory to process:',
                    options: $options
                );
            }
        }
        
        if (!File::exists($directory) || !File::isDirectory($directory)) {
            error("Directory does not exist: {$directory}");
            return null;
        }
        
        return $directory;
    }

    /**
     * Find video directories in storage
     */
    private function findVideoDirectories(): array
    {
        $storageApp = storage_path('app');
        $directories = [];
        
        // Look for meeting_videos_* directories
        $pattern = $storageApp . '/meeting_videos_*';
        $matches = glob($pattern, GLOB_ONLYDIR);
        
        foreach ($matches as $dir) {
            if (File::exists($dir)) {
                $directories[] = $dir;
            }
        }
        
        // Sort by modification time (newest first)
        usort($directories, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return array_slice($directories, 0, 10); // Show only last 10
    }

    /**
     * Find video files in directory
     */
    private function findVideoFiles(string $directory): array
    {
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        $videoFiles = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($extension, $videoExtensions)) {
                    $relativePath = $file->getPathname();
                    
                    // Extract meeting ID and committee ID from path structure
                    $pathParts = explode('/', str_replace($directory, '', $relativePath));
                    $committeeDir = $pathParts[1] ?? null;
                    $meetingDir = $pathParts[2] ?? null;
                    
                    $meetingId = null;
                    $committeeId = null;
                    
                    if ($meetingDir && preg_match('/(\d{4}-\d{2}-\d{2})_(\d+)/', $meetingDir, $matches)) {
                        $meetingId = $matches[2];
                    }
                    
                    // Try to find committee ID from directory name or database
                    if ($committeeDir) {
                        $committee = Committee::where('name', 'LIKE', '%' . str_replace('_', '%', $committeeDir) . '%')->first();
                        $committeeId = $committee ? $committee->committee_id : null;
                    }
                    
                    $videoFiles[] = [
                        'filepath' => $relativePath,
                        'filename' => $file->getFilename(),
                        'filesize' => $file->getSize(),
                        'meeting_id' => $meetingId,
                        'committee_id' => $committeeId,
                        'committee_dir' => $committeeDir,
                    ];
                }
            }
        }
        
        // Apply filters
        if ($this->option('committee')) {
            $committeeFilter = $this->option('committee');
            $videoFiles = array_filter($videoFiles, function($file) use ($committeeFilter) {
                return $file['committee_id'] == $committeeFilter;
            });
        }
        
        if ($this->option('meeting')) {
            $meetingFilter = $this->option('meeting');
            $videoFiles = array_filter($videoFiles, function($file) use ($meetingFilter) {
                return $file['meeting_id'] == $meetingFilter;
            });
        }
        
        return array_values($videoFiles);
    }

    /**
     * Show video files that would be processed
     */
    private function showVideoFiles(array $videoFiles): void
    {
        info("\\nVideo files to process:");
        
        foreach ($videoFiles as $index => $file) {
            $size = $this->formatFileSize($file['filesize']);
            $meetingInfo = $file['meeting_id'] ? "Meeting: {$file['meeting_id']}" : 'Meeting: Unknown';
            $committeeInfo = $file['committee_id'] ? "Committee: {$file['committee_id']}" : 'Committee: Unknown';
            
            info(($index + 1) . ". {$file['filename']}");
            info("   Path: {$file['filepath']}");
            info("   Size: {$size} | {$meetingInfo} | {$committeeInfo}");
        }
    }

    /**
     * Process video files for transcription
     */
    private function processVideoFiles(array $videoFiles): void
    {
        $batchSize = intval($this->option('batch-size'));
        
        // Filter out already processed files unless overwrite is specified
        if (!$this->option('overwrite')) {
            $videoFiles = $this->filterUnprocessedFiles($videoFiles);
            info("Processing " . count($videoFiles) . " unprocessed video files...");
        }
        
        if (empty($videoFiles)) {
            info('All video files have already been transcribed. Use --overwrite to re-process.');
            return;
        }
        
        $progress = progress(
            label: 'Transcribing videos',
            steps: count($videoFiles)
        );
        
        $progress->start();
        
        // Process files in batches
        $batches = array_chunk($videoFiles, $batchSize);
        
        foreach ($batches as $batch) {
            $this->processBatch($batch);
            
            foreach ($batch as $file) {
                $progress->advance();
            }
            
            // Small delay between batches to avoid rate limiting
            if (count($batches) > 1) {
                sleep(1);
            }
        }
        
        $progress->finish();
    }

    /**
     * Filter out already processed video files
     */
    private function filterUnprocessedFiles(array $videoFiles): array
    {
        return array_filter($videoFiles, function($file) {
            if (!$file['meeting_id'] || !$file['committee_id']) {
                return true; // Process files with unknown meeting/committee info
            }
            
            $existing = VideoTranscription::where('meeting_id', $file['meeting_id'])
                ->where('video_filename', $file['filename'])
                ->where('status', '!=', 'failed')
                ->exists();
                
            if ($existing) {
                $this->stats['videos_skipped']++;
                return false;
            }
            
            return true;
        });
    }

    /**
     * Process a batch of video files
     */
    private function processBatch(array $batch): void
    {
        foreach ($batch as $file) {
            $this->transcribeVideo($file);
            $this->stats['videos_processed']++;
        }
    }

    /**
     * Transcribe a single video file
     */
    private function transcribeVideo(array $file): void
    {
        try {
            // Create or update database record
            $transcription = VideoTranscription::updateOrCreate(
                [
                    'meeting_id' => $file['meeting_id'] ?? 'unknown',
                    'video_filename' => $file['filename'],
                ],
                [
                    'committee_id' => $file['committee_id'] ?? 'unknown',
                    'video_filepath' => $file['filepath'],
                    'file_size_bytes' => $file['filesize'],
                    'status' => 'processing',
                    'elevenlabs_model_id' => $this->option('model'),
                    'transcription_started_at' => now(),
                    'error_message' => null,
                ]
            );
            
            info("📹 Transcribing: {$file['filename']}");
            
            // Call ElevenLabs API
            $response = $this->callElevenLabsAPI($file['filepath']);
            
            if ($response['success']) {
                $transcription->update([
                    'status' => 'completed',
                    'transcription_text' => $response['data']['text'] ?? null,
                    'language_code' => $response['data']['language_code'] ?? null,
                    'language_probability' => $response['data']['language_probability'] ?? null,
                    'word_timestamps' => $response['data']['words'] ?? null,
                    'speaker_diarization' => $response['data']['speaker_diarization'] ?? null,
                    'audio_duration_seconds' => $response['data']['audio_duration'] ?? null,
                    'api_cost' => $response['cost'] ?? null,
                    'api_response_metadata' => $response['metadata'] ?? null,
                    'transcription_completed_at' => now(),
                ]);
                
                $this->stats['transcriptions_completed']++;
                $this->stats['total_duration'] += $response['data']['audio_duration'] ?? 0;
                $this->stats['total_cost'] += $response['cost'] ?? 0;
                
                info("✅ Completed: {$file['filename']}");
            } else {
                $transcription->update([
                    'status' => 'failed',
                    'error_message' => $response['error'] ?? 'Unknown error',
                    'transcription_completed_at' => now(),
                ]);
                
                $this->stats['transcriptions_failed']++;
                warning("❌ Failed: {$file['filename']} - {$response['error']}");
            }
            
        } catch (\Exception $e) {
            $this->stats['transcriptions_failed']++;
            warning("❌ Error transcribing {$file['filename']}: {$e->getMessage()}");
        }
    }

    /**
     * Call ElevenLabs Speech-to-Text API
     */
    private function callElevenLabsAPI(string $filePath): array
    {
        try {
            $apiKey = config('services.elevenlabs.api_key');
            
            // Prepare form data
            $formData = ['model_id' => $this->option('model')];
            if ($this->option('language')) {
                $formData['language_code'] = $this->option('language');
            }
            if ($this->option('speakers')) {
                $formData['num_speakers'] = $this->option('speakers');
            }

            $response = Http::withHeaders([
                'xi-api-key' => $apiKey,
            ])
            ->timeout(1800) // 30 minutes timeout for large files
            ->attach('file', fopen($filePath, 'r'), basename($filePath))
            ->post('https://api.elevenlabs.io/v1/speech-to-text', $formData);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data,
                    'cost' => $this->estimateCost($data),
                    'metadata' => [
                        'api_response_time' => $response->transferStats?->getTransferTime(),
                        'api_status_code' => $response->status(),
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status() . ' - ' . $response->body(),
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Estimate API cost (rough estimation)
     */
    private function estimateCost(array $responseData): float
    {
        // ElevenLabs charges per minute of audio
        // This is a rough estimation - check their current pricing
        $durationMinutes = ($responseData['audio_duration'] ?? 0) / 60;
        $costPerMinute = 0.018; // Approximate cost per minute (check actual pricing)
        
        return round($durationMinutes * $costPerMinute, 6);
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Show final transcription statistics
     */
    private function showFinalStats(): void
    {
        info("\\n📊 Transcription Statistics:");
        if (isset($this->stats['meetings_found'])) {
            info("• Meetings processed: {$this->stats['meetings_found']}");
        }
        info("• Videos found: {$this->stats['videos_found']}");
        info("• Videos processed: {$this->stats['videos_processed']}");
        info("• Videos skipped: {$this->stats['videos_skipped']}");
        info("• Transcriptions completed: {$this->stats['transcriptions_completed']}");
        info("• Transcriptions failed: {$this->stats['transcriptions_failed']}");
        
        if ($this->stats['total_duration'] > 0) {
            $totalHours = round($this->stats['total_duration'] / 3600, 2);
            info("• Total audio processed: {$totalHours} hours");
        }
        
        if ($this->stats['total_cost'] > 0) {
            $cost = number_format($this->stats['total_cost'], 4);
            info("• Estimated API cost: \${$cost}");
        }
        
        if ($this->stats['transcriptions_completed'] > 0) {
            info("\\n🎉 Transcription process completed successfully!");
            info("💡 View results in the video_transcriptions table or create a Filament resource to manage them.");
        } else {
            warning("No transcriptions were completed. Check the errors above.");
        }
    }
}
