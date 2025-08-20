<?php

namespace App\Console\Commands;

use App\Models\Committee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
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

class DownloadMeetingVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:download-videos
                            {--committee=* : Committee IDs to download videos for}
                            {--all : Download videos for all committees}
                            {--year= : Year to download videos from}
                            {--month= : Month to download videos from (omit for entire year)}
                            {--from= : Download videos from this date (YYYY-MM-DD)}
                            {--to= : Download videos to this date (YYYY-MM-DD)}
                            {--output= : Custom output directory}
                            {--dry-run : Show what would be downloaded without downloading}
                            {--overwrite : Overwrite existing files}
                            {--downloader=curl : Download tool to use (curl or wget)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download all video files from committee meetings';

    private array $downloadStats = [
        'meetings_processed' => 0,
        'videos_found' => 0,
        'videos_downloaded' => 0,
        'videos_skipped' => 0,
        'errors' => 0,
        'total_size' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('🎥 Starting committee meeting video downloads...');

        // Get committees to process
        $committees = $this->getCommitteesToProcess();

        if ($committees->isEmpty()) {
            error('No committees selected.');
            return 1;
        }

        // Get date range
        $dateParams = $this->getDateParameters();

        // Prepare output directory
        $outputDir = $this->prepareOutputDirectory();

        info("Output directory: {$outputDir}");
        info("Processing " . $committees->count() . " committee(s)...");

        if ($this->option('dry-run')) {
            info('🔍 DRY RUN MODE - No files will be downloaded');
        }

        // Process each committee
        foreach ($committees as $committee) {
            $this->processCommittee($committee, $dateParams, $outputDir);
        }

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
                label: 'Select committees to download videos for:',
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
                label: 'Select a committee to download videos for:',
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
     * Prepare output directory
     */
    private function prepareOutputDirectory(): string
    {
        $dirName = $this->option('output') ?? 'meeting_videos_' . Carbon::now()->format('Y-m-d_His');
        $outputDir = storage_path('app/' . $dirName);

        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        return $outputDir;
    }

    /**
     * Process a single committee
     */
    private function processCommittee(Committee $committee, array $dateParams, string $outputDir): void
    {
        info("\n📁 Processing committee: {$committee->name}");

        // Get all meeting IDs for this committee
        $meetingIds = spin(
            fn() => $this->fetchMeetingIds($committee->committee_id, $dateParams),
            "Fetching meeting IDs for {$committee->name}..."
        );

        if (empty($meetingIds)) {
            warning("No meetings found for {$committee->name}");
            return;
        }

        info("Found " . count($meetingIds) . " meetings");

        // Create committee directory
        $committeeDir = $outputDir . '/' . Str::slug($committee->name, '_');
        if (!File::exists($committeeDir)) {
            File::makeDirectory($committeeDir, 0755, true);
        }

        // Process each meeting
        $progress = progress(
            label: "Processing meetings for {$committee->name}",
            steps: count($meetingIds)
        );

        $progress->start();

        foreach ($meetingIds as $meetingId) {
            $this->processMeeting($meetingId, $committeeDir);
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
     * Process a single meeting
     */
    private function processMeeting(int $meetingId, string $committeeDir): void
    {
        try {
            // Fetch meeting metadata
            $meetingData = $this->fetchMeetingData($meetingId);

            if (!$meetingData) {
                $this->downloadStats['errors']++;
                return;
            }

            $this->downloadStats['meetings_processed']++;

            // Extract video information
            $videos = $this->extractVideoUrls($meetingData);

            if (empty($videos)) {
                return; // No videos for this meeting
            }

            $this->downloadStats['videos_found'] += count($videos);

            // Create meeting directory
            $meetingDate = $this->extractMeetingDate($meetingData);
            $meetingDir = $committeeDir . '/' . $meetingDate . '_' . $meetingId;

            if (!File::exists($meetingDir)) {
                File::makeDirectory($meetingDir, 0755, true);
            }

            // Download each video
            foreach ($videos as $index => $videoUrl) {
                $this->downloadVideo($videoUrl, $meetingDir, $meetingId, $index + 1);
            }

        } catch (\Exception $e) {
            warning("Error processing meeting {$meetingId}: " . $e->getMessage());
            $this->downloadStats['errors']++;
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
     * Extract meeting date for directory naming
     */
    private function extractMeetingDate(array $meetingData): string
    {
        if (isset($meetingData['A_Cm_Sit_date'])) {
            try {
                $date = Carbon::parse($meetingData['A_Cm_Sit_date']);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback to raw date
            }
        }

        return 'unknown_date';
    }

    /**
     * Download a video file using curl
     */
    private function downloadVideo(string $url, string $meetingDir, int $meetingId, int $videoIndex): void
    {
        try {
            // Extract filename from URL
            $filename = $this->extractFilename($url, $meetingId, $videoIndex);
            $filepath = $meetingDir . '/' . $filename;

            // Check if file already exists
            if (File::exists($filepath) && !$this->option('overwrite')) {
                $this->downloadStats['videos_skipped']++;
                info("⏭️  Skipped (exists): {$filename}");
                return;
            }

            if ($this->option('dry-run')) {
                info("📹 Would download: {$url} -> {$filename}");
                return;
            }

            info("⬇️  Downloading: {$filename}");

            // Use external downloader for efficient video downloads
            $downloader = $this->option('downloader');
            $success = match($downloader) {
                'wget' => $this->downloadWithWget($url, $filepath),
                default => $this->downloadWithCurl($url, $filepath),
            };

            if ($success) {
                $this->downloadStats['videos_downloaded']++;

                if (File::exists($filepath)) {
                    $fileSize = File::size($filepath);
                    $this->downloadStats['total_size'] += $fileSize;
                    $sizeInMB = round($fileSize / (1024 * 1024), 2);
                    info("✅ Downloaded: {$filename} ({$sizeInMB} MB)");
                } else {
                    info("✅ Downloaded: {$filename}");
                }
            } else {
                warning("❌ Failed to download: {$filename}");
                $this->downloadStats['errors']++;
            }

        } catch (\Exception $e) {
            warning("❌ Error downloading {$url}: " . $e->getMessage());
            $this->downloadStats['errors']++;
        }
    }

    /**
     * Download file using curl command
     */
    private function downloadWithCurl(string $url, string $filepath): bool
    {
        // Escape shell arguments
        $escapedUrl = escapeshellarg($url);
        $escapedPath = escapeshellarg($filepath);

        // Build curl command with options for reliable downloads
        $curlCommand = sprintf(
            'curl -L -C - --retry 3 --retry-delay 2 --connect-timeout 30 --max-time 1800 -o %s %s 2>/dev/null',
            $escapedPath,
            $escapedUrl
        );

        // Execute the command
        $exitCode = 0;
        $output = [];
        exec($curlCommand, $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Alternative: Download file using wget command
     */
    private function downloadWithWget(string $url, string $filepath): bool
    {
        // Escape shell arguments
        $escapedUrl = escapeshellarg($url);
        $escapedPath = escapeshellarg($filepath);

        // Build wget command with options for reliable downloads
        $wgetCommand = sprintf(
            'wget --continue --tries=3 --timeout=30 --read-timeout=1800 -O %s %s 2>/dev/null',
            $escapedPath,
            $escapedUrl
        );

        // Execute the command
        $exitCode = 0;
        $output = [];
        exec($wgetCommand, $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Extract filename from video URL
     */
    private function extractFilename(string $url, int $meetingId, int $videoIndex): string
    {
        // Try to get filename from URL
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $filename = basename($path);

        // If no proper filename, create one
        if (empty($filename) || !str_contains($filename, '.')) {
            $extension = $this->guessExtension($url);
            $filename = "meeting_{$meetingId}_video_{$videoIndex}.{$extension}";
        }

        return $filename;
    }

    /**
     * Guess file extension from URL or content type
     */
    private function guessExtension(string $url): string
    {
        // Common video extensions
        if (str_contains($url, '.mp4')) return 'mp4';
        if (str_contains($url, '.avi')) return 'avi';
        if (str_contains($url, '.mov')) return 'mov';
        if (str_contains($url, '.wmv')) return 'wmv';
        if (str_contains($url, '.flv')) return 'flv';
        if (str_contains($url, '.webm')) return 'webm';

        // Default to mp4
        return 'mp4';
    }

    /**
     * Show final download statistics
     */
    private function showFinalStats(): void
    {
        info("\n📊 Download Statistics:");
        info("• Meetings processed: " . $this->downloadStats['meetings_processed']);
        info("• Videos found: " . $this->downloadStats['videos_found']);
        info("• Videos downloaded: " . $this->downloadStats['videos_downloaded']);
        info("• Videos skipped: " . $this->downloadStats['videos_skipped']);
        info("• Errors: " . $this->downloadStats['errors']);

        if ($this->downloadStats['total_size'] > 0) {
            $sizeInMB = round($this->downloadStats['total_size'] / (1024 * 1024), 2);
            info("• Total size downloaded: {$sizeInMB} MB");
        }

        if ($this->downloadStats['videos_downloaded'] > 0) {
            info("\n🎉 Download completed successfully!");
        } elseif ($this->downloadStats['videos_found'] === 0) {
            warning("No videos found for the selected criteria.");
        } else {
            warning("No videos were downloaded. Check for errors above.");
        }
    }
}
