# Parliament Scraper

A Laravel-based web scraper for extracting parliament member, committee, and bill information from parliament.bg.

## Features

- 🏛️ **Parliament Members Scraping**: Extract detailed information about all parliament members
- 🏢 **Committee Management**: Scrape committee data with member relationships
- 📄 **Bills Tracking**: Scrape and monitor legislative bills by committee with PDF text extraction
- 📜 **Transcripts Processing**: Scrape and analyze committee meeting transcripts
- 🤖 **AI Analysis**: Automated analysis of transcripts for bill discussions and amendments
- 🔍 **Protocol Extraction**: Extract structured protocol changes using LangExtract
- 🔔 **Scheduled Monitoring**: Automated checking for new bills and transcripts
- 📊 **CSV Export**: Export data to CSV format with UTF-8 encoding for Bulgarian text
- 📁 **Individual Committee Files**: Generate separate files for each committee
- 🔤 **Bulgarian Text Support**: Proper handling and transliteration of Bulgarian characters
- ⚡ **Progress Tracking**: Visual progress bars for long-running operations

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd parliament-scraper
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env` file and run migrations:
```bash
php artisan migrate
```

## Usage

### Scraping Parliament Members

Scrape all parliament members from parliament.bg:

```bash
php artisan parliament:scrape
```

This command:
- Fetches all members from the parliament API
- Retrieves detailed profile information for each member
- Stores data including: name, electoral district, political party, profession, email

### Scraping Committees

Scrape all parliamentary committees and their members:

```bash
php artisan committees:scrape
```

This command:
- Fetches all committees from the parliament API
- Links committee members to parliament members
- Stores committee details and member positions

### Scraping Bills

Scrape legislative bills for committees:

```bash
# Scrape bills for transport committee (default)
php artisan bills:scrape

# Scrape bills for specific committee
php artisan bills:scrape --committee-id=3595

# Scrape bills for all committees
php artisan bills:scrape --all-committees

# Scrape with detailed information and PDF text extraction
php artisan bills:scrape --detailed

# Only download PDFs for existing bills (without scraping new bills)
php artisan bills:scrape --pdf-only
```

Options:
- `--committee-id=`: Specific committee ID to scrape
- `--all-committees`: Scrape bills for all committees
- `--detailed`: Fetch detailed information and extract PDF text
- `--pdf-only`: Only download PDFs for existing bills

### Scraping Transcripts

Scrape committee meeting transcripts:

```bash
# Scrape transcripts for specific committee for current month
php artisan transcripts:scrape --committee=3613

# Scrape transcripts for specific year and month
php artisan transcripts:scrape --committee=3613 --year=2024 --month=6

# Scrape transcripts for all committees for current month
php artisan transcripts:scrape --all
```

Options:
- `--committee=`: Committee ID to scrape transcripts for
- `--year=`: Year to scrape (defaults to current year)
- `--month=`: Month to scrape (defaults to current month)
- `--all`: Scrape transcripts for all committees

### Downloading Meeting Videos

Download video recordings from committee meetings:

```bash
# Interactive committee selection (downloads entire current year by default)
php artisan meetings:download-videos

# Download videos for specific committee (entire current year)
php artisan meetings:download-videos --committee=3613

# Download videos for specific committee and year (all 12 months)
php artisan meetings:download-videos --committee=3613 --year=2024

# Download videos for multiple committees (entire current year)
php artisan meetings:download-videos --committee=3613 --committee=3595

# Download videos for all committees (entire current year - lots of data!)
php artisan meetings:download-videos --all

# Download videos for specific month only
php artisan meetings:download-videos --committee=3613 --year=2024 --month=6

# Download videos for date range
php artisan meetings:download-videos --from=2024-01-01 --to=2024-12-31

# Dry run to see what would be downloaded (recommended first)
php artisan meetings:download-videos --committee=3613 --dry-run

# Custom output directory
php artisan meetings:download-videos --output=my_videos

# Overwrite existing files
php artisan meetings:download-videos --overwrite
```

Features:
- **Interactive committee selection** with Laravel Prompts
- **Smart organization**: Creates directories by committee and meeting date
- **Multiple video formats**: Automatically detects and downloads all available video files
- **Efficient downloads**: Uses `curl` or `wget` for faster, more reliable downloads
- **Resume capability**: Automatically resume interrupted downloads (curl -C -, wget --continue)
- **Progress tracking**: Real-time progress bars and download statistics
- **Retry logic**: Automatic retries for failed downloads with exponential backoff
- **Dry run mode**: Preview what will be downloaded without actually downloading
- **Error handling**: Robust error handling with detailed statistics

Options:
- `--committee=*`: Committee IDs to download videos for (multiple allowed)
- `--all`: Download videos for all committees
- `--year=`: Year to download videos from (defaults to current year)
- `--month=`: Month to download videos from (omit for entire year)
- `--from=`: Download videos from this date (YYYY-MM-DD)
- `--to=`: Download videos to this date (YYYY-MM-DD)
- `--output=`: Custom output directory
- `--dry-run`: Show what would be downloaded without downloading
- `--overwrite`: Overwrite existing files
- `--downloader=curl`: Download tool to use (curl or wget)

### Video Transcription

Transcribe committee meeting videos **directly from parliament.bg URLs** using ElevenLabs Speech-to-Text API - no need to download files first!

```bash
# Transcribe videos for specific committee (recommended)
php artisan videos:transcribe --committee=3613

# Transcribe videos for multiple committees
php artisan videos:transcribe --committee=3613 --committee=3595

# Transcribe all committees
php artisan videos:transcribe --all

# Transcribe specific meeting(s)
php artisan videos:transcribe --meeting=13505

# Transcribe for specific time period
php artisan videos:transcribe --committee=3613 --year=2024 --month=6
php artisan videos:transcribe --committee=3613 --from=2024-01-01 --to=2024-12-31

# Use specific model and language settings
php artisan videos:transcribe --committee=3613 --model=eleven_multilingual_v2 --language=bg

# Enable speaker diarization
php artisan videos:transcribe --committee=3613 --speakers=3

# Overwrite existing transcriptions
php artisan videos:transcribe --committee=3613 --overwrite

# Dry run to see what would be transcribed
php artisan videos:transcribe --committee=3613 --dry-run

# Legacy mode: transcribe from downloaded files
php artisan videos:transcribe --use-files --directory=/path/to/videos
```

**Key Features:**
- ✨ **Direct URL transcription**: Process videos directly from parliament.bg without downloading
- 🔍 **Smart meeting discovery**: Automatically finds meetings with videos for selected committees/periods
- 🎯 **Flexible targeting**: Target specific committees, meetings, or time periods
- 🤖 **ElevenLabs API integration**: Professional speech-to-text with multiple model options
- 👥 **Speaker diarization**: Identify and separate different speakers
- ⏱️ **Word-level timestamps**: Precise timing information for each word
- 🌍 **Language detection**: Automatic language detection with confidence scores
- 💾 **Database storage**: All transcriptions stored with comprehensive metadata
- 📊 **Progress tracking**: Real-time progress bars and detailed statistics
- ⚡ **Batch processing**: Process multiple videos concurrently with rate limiting
- 🔄 **Resume capability**: Skip already transcribed videos (unless `--overwrite`)
- 💰 **Cost tracking**: Estimate and track API usage costs

**Options:**
- `--committee=*`: Committee IDs to process (multiple allowed)
- `--meeting=*`: Specific meeting IDs to process (multiple allowed)
- `--all`: Process all committees
- `--year=`: Year to process videos from
- `--month=`: Month to process videos from (omit for entire year)
- `--from=`: Process videos from this date (YYYY-MM-DD)
- `--to=`: Process videos to this date (YYYY-MM-DD)
- `--model=`: ElevenLabs model (eleven_english_turbo_v2, eleven_multilingual_v2, etc.)
- `--language=`: Language code (e.g., en, bg) - leave empty for auto-detection
- `--speakers=`: Number of speakers for diarization
- `--overwrite`: Re-transcribe already processed videos
- `--dry-run`: Show what would be transcribed without processing
- `--batch-size=3`: Number of concurrent transcription requests
- `--use-files`: Use legacy mode with downloaded files instead of direct URLs

**Prerequisites:**
- Set `ELEVENLABS_API_KEY` in your `.env` file
- Run `committees:scrape` to populate committee data

**No Downloads Required:**
Unlike the old approach, you don't need to download gigabytes of video files first. The command fetches video URLs directly from parliament.bg's API and sends them to ElevenLabs for transcription.

**Fallback Support:**
If direct URL transcription fails, the system automatically falls back to downloading the video temporarily and uploading it to ElevenLabs.

**Storage:**
Transcriptions are stored in the `video_transcriptions` table with:
- Full transcription text with word-level precision
- Language detection results and confidence scores
- Word-level timestamps for precise navigation
- Speaker identification and diarization (if enabled)
- API usage costs and response metadata
- Processing statistics and error handling

### Listing Transcripts

Interactive command to view and manage transcripts with download status:

```bash
# Interactive committee selection with table view
php artisan transcripts:list

# List transcripts for specific committee
php artisan transcripts:list --committee=3613

# List transcripts for entire year (all months)
php artisan transcripts:list --year=2024

# List transcripts for specific year/month
php artisan transcripts:list --year=2024 --month=6

# Show only downloaded transcripts
php artisan transcripts:list --downloaded

# Show only not downloaded transcripts
php artisan transcripts:list --not-downloaded

# Export the list to CSV
php artisan transcripts:list --export
```

Features:
- Interactive committee selection with Laravel Prompts
- Beautiful table view showing download status, content availability, and analysis status
- **Fetches entire year when no month specified** - automatically retrieves all 12 months
- Summary statistics
- Smart download grouping - efficiently downloads missing transcripts by month
- CSV export functionality

Options:
- `--committee=`: Filter by committee ID
- `--year=`: Filter by year (defaults to current year)
- `--month=`: Filter by specific month (omit to get entire year)
- `--downloaded`: Show only downloaded transcripts
- `--not-downloaded`: Show only not downloaded transcripts
- `--export`: Export the list to CSV

### Analyzing Transcripts

Analyze transcripts for bill discussions using AI:

```bash
# Analyze recent unanalyzed transcripts (default: 10)
php artisan analyze:transcripts

# Analyze all transcripts
php artisan analyze:transcripts --all

# Analyze specific transcript IDs
php artisan analyze:transcripts --ids=1 --ids=2 --ids=3

# Analyze transcripts from a specific date
php artisan analyze:transcripts --since=2024-01-01

# Analyze transcripts from specific committee
php artisan analyze:transcripts --committee=3613

# Re-analyze already analyzed transcripts
php artisan analyze:transcripts --reanalyze

# Dry run to see what would be analyzed
php artisan analyze:transcripts --dry-run
```

Options:
- `--all`: Analyze all transcripts
- `--ids=*`: Specific transcript IDs to analyze
- `--since=`: Analyze transcripts since this date (Y-m-d format)
- `--committee=`: Filter by committee ID
- `--reanalyze`: Re-analyze transcripts that have already been analyzed
- `--dry-run`: Show what would be analyzed without actually processing

### Extracting Protocol Changes

Extract structured protocol changes from transcripts using LangExtract:

```bash
# Extract from specific transcripts
php artisan transcripts:extract --transcript=1 --transcript=2

# Extract from committee transcripts
php artisan transcripts:extract --committee=3613

# Extract from date range
php artisan transcripts:extract --from=2024-01-01 --to=2024-12-31

# Extract specific type of information
php artisan transcripts:extract --type=bill_discussions

# Force re-processing of already extracted transcripts
php artisan transcripts:extract --force

# Check Python dependencies
php artisan transcripts:extract --check-deps

# Install Python dependencies
php artisan transcripts:extract --install-deps
```

Options:
- `--transcript=*`: Specific transcript IDs to process
- `--committee=`: Process transcripts from specific committee
- `--from=`: Process transcripts from this date (YYYY-MM-DD)
- `--to=`: Process transcripts until this date (YYYY-MM-DD)
- `--type=`: Extraction type (bill_discussions, committee_decisions, amendments, speaker_statements, all)
- `--limit=`: Limit number of transcripts to process
- `--force`: Re-process already extracted transcripts
- `--check-deps`: Check Python dependencies
- `--install-deps`: Install Python dependencies

### Scheduled Monitoring

#### Check for New Bills

Check for new bills automatically:

```bash
# Check for new bills in last 7 days
php artisan bills:check-new

# Check for new bills in last 30 days
php artisan bills:check-new --days=30

# Check specific committee with notifications
php artisan bills:check-new --committee-id=3613 --notify
```

#### Check for New Transcripts

Monitor for new committee transcripts:

```bash
# Check for new transcripts (all committees, current month)
php artisan transcripts:check-new

# Check specific committee
php artisan transcripts:check-new --committee=3613

# Check with automatic analysis
php artisan transcripts:check-new --analyze

# Send notifications for new transcripts
php artisan transcripts:check-new --notify
```

### Exporting Data

#### Export Parliament Members to CSV

```bash
php artisan parliament:export-csv
```

#### Export Committees to CSV

```bash
php artisan committees:export-csv
```

#### Export Bills to CSV

```bash
# Export all bills
php artisan bills:export-csv

# Export recent bills
php artisan bills:export-csv --days=30

# Export bills for specific committee
php artisan bills:export-csv --committee-id=3613
```

#### Export Individual Committee Files

Generate separate files for each committee:

```bash
# CSV format (default)
php artisan committees:export-files

# Text format
php artisan committees:export-files --format=txt

# Custom folder
php artisan committees:export-files --folder=my_committees
```

#### Export Transcripts for Analysis

Export transcripts with extracted text for external analysis:

```bash
# Export all transcripts
php artisan transcripts:export-analysis

# Export transcripts from specific committee
php artisan transcripts:export-analysis --committee=3613

# Export transcripts from date range
php artisan transcripts:export-analysis --from=2024-01-01 --to=2024-12-31

# Export with custom output directory
php artisan transcripts:export-analysis --output=custom_export

# Export in different format (json or txt)
php artisan transcripts:export-analysis --format=json

# Include AI analysis results
php artisan transcripts:export-analysis --include-analysis
```

Options:
- `--committee=`: Filter by committee ID
- `--from=`: Export transcripts from this date
- `--to=`: Export transcripts until this date
- `--output=`: Custom output directory name
- `--format=`: Export format (json or txt, default: txt)
- `--include-analysis`: Include AI analysis results in export

#### Export Committee Transcripts

Advanced export command with multiple format options and interactive selection:

```bash
# Interactive committee selection
php artisan transcripts:export-committee

# Export specific committees
php artisan transcripts:export-committee --committee=3613 --committee=3595

# Export all committees
php artisan transcripts:export-committee --all

# Export with date range
php artisan transcripts:export-committee --from=2024-01-01 --to=2024-12-31

# Export in different formats (txt, json, csv, html)
php artisan transcripts:export-committee --format=html

# Create separate file for each transcript
php artisan transcripts:export-committee --separate-files

# Include metadata and AI analysis
php artisan transcripts:export-committee --include-metadata --include-analysis

# Custom output directory
php artisan transcripts:export-committee --output=my_exports
```

Features:
- Interactive single or multi-committee selection
- Multiple export formats: TXT, JSON, CSV, HTML
- Option for separate files per transcript or combined files
- Include transcript metadata and AI analysis results
- Automatic directory creation and organization
- Option to open export directory after completion

Options:
- `--committee=*`: Committee IDs to export (multiple allowed)
- `--all`: Export all committees
- `--from=`: Export transcripts from this date (YYYY-MM-DD)
- `--to=`: Export transcripts until this date (YYYY-MM-DD)
- `--format=txt`: Export format (txt, json, csv, html)
- `--separate-files`: Create separate file for each transcript
- `--include-metadata`: Include transcript metadata
- `--include-analysis`: Include AI analysis if available
- `--output=`: Custom output directory name

## Data Structure

### Parliament Members

- **member_id**: Unique parliament ID
- **first_name, middle_name, last_name**: Full name components
- **electoral_district**: Electoral district representation
- **political_party**: Political party affiliation
- **profession**: Professional background
- **email**: Official parliament email

### Committees

- **committee_id**: Unique committee ID
- **name**: Committee name
- **committee_type_id**: Committee type identifier
- **active_count**: Number of active members
- **date_from/date_to**: Activity period
- **email, phone**: Contact information

### Committee Members (Pivot)

- **position**: Member position in committee (председател, зам.-председател, член)
- **date_from/date_to**: Membership period

### Bills

- **bill_id**: Unique bill ID (L_Act_id)
- **title**: Bill title in Bulgarian
- **signature**: Official bill signature
- **sign**: Official bill number (e.g., 51-554-01-114)
- **bill_date**: Date the bill was submitted
- **path**: Bill category path
- **committee_id**: Committee handling the bill
- **session**: Parliamentary session
- **submitters**: JSON array of bill submitters
- **committees**: JSON array of committees involved
- **pdf_url**: URL to bill PDF document
- **pdf_filename**: Name of PDF file
- **extracted_text**: Extracted text from PDF
- **word_count**: Number of words in PDF
- **character_count**: Number of characters in PDF
- **is_detailed**: Whether detailed information has been fetched
- **is_withdrawn**: Whether the bill has been withdrawn

### Transcripts

- **transcript_id**: Unique transcript ID
- **committee_id**: Committee that held the meeting
- **type**: Type of transcript (e.g., stenographic)
- **transcript_date**: Date of the meeting
- **content_html**: Full HTML content of the transcript
- **word_count**: Number of words in transcript
- **metadata**: JSON metadata including acts discussed

### Bill Analyses

- **transcript_id**: Related transcript ID
- **bill_id**: Related bill ID (if identified)
- **bill_identifier**: Bill number or identifier mentioned
- **proposer_name**: Name of person proposing changes
- **amendment_type**: Type of amendment (new_text, modification, deletion, etc.)
- **status**: Discussion status (accepted, rejected, debated, etc.)
- **confidence_score**: AI confidence score (0-1)
- **content**: Relevant discussion content
- **summary**: AI-generated summary
- **metadata**: Additional metadata from analysis

### Protocol Extractions

- **transcript_id**: Related transcript ID
- **extraction_type**: Type of extraction performed
- **extracted_data**: JSON structured data extracted
- **confidence_score**: Extraction confidence score
- **metadata**: Additional extraction metadata

## File Exports

### CSV Files
- UTF-8 BOM encoding for proper Excel compatibility
- Bulgarian text fully supported
- Structured data with proper headers

### Text Files
- Human-readable format
- Committee details and member listings
- Formatted for easy reading

### Generated Files Location
- **CSV exports**: `storage/app/`
- **Committee files**: `storage/app/committees/` or `storage/app/committees_txt/`

## API Endpoints Used

- **Parliament Members**: `https://www.parliament.bg/api/v1/coll-list-ns/bg`
- **Member Profiles**: `https://www.parliament.bg/api/v1/mp-profile/bg/{member_id}`
- **Committees**: `https://www.parliament.bg/api/v1/coll-list/bg/3`
- **Committee Members**: `https://www.parliament.bg/api/v1/coll-list-mp/bg/{committee_id}/3?date=`
- **Bills by Committee**: `https://www.parliament.bg/api/v1/com-acts/bg/{committee_id}/1`
- **Bill Details**: `https://www.parliament.bg/api/v1/bill/{bill_id}`
- **Transcripts List**: `https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/{year}/{month}/{committee_id}/0`
- **Transcript Content**: `https://www.parliament.bg/api/v1/com-steno/bg/{transcript_id}`

## Technical Features

### Bulgarian Text Handling
- Proper UTF-8 encoding with BOM for Excel compatibility
- Bulgarian to Latin transliteration for safe filenames
- Character mapping: а→a, б→b, в→v, etc.

### Database Relationships
- Many-to-many relationship between parliament members and committees
- One-to-many relationship between committees and bills
- Pivot table storing member positions and date ranges
- Eloquent ORM for clean data access

### Error Handling
- API response validation
- Graceful handling of missing data fields
- Progress tracking with error reporting

## Commands Summary

| Command | Description |
|---------|-------------|
| **Scraping Commands** | |
| `parliament:scrape` | Scrape all parliament members |
| `committees:scrape` | Scrape all committees and relationships |
| `bills:scrape` | Scrape bills for committees with optional PDF extraction |
| `transcripts:scrape` | Scrape committee meeting transcripts |
| `meetings:download-videos` | Download video recordings from committee meetings |
| `videos:transcribe` | Transcribe video files using ElevenLabs Speech-to-Text API |
| **View & Management Commands** | |
| `transcripts:list` | Interactive list of transcripts with download status |
| **Analysis Commands** | |
| `analyze:transcripts` | Analyze transcripts for bill discussions using AI |
| `transcripts:extract` | Extract structured protocol changes using LangExtract |
| **Monitoring Commands** | |
| `bills:check-new` | Check for new bills (scheduled monitoring) |
| `transcripts:check-new` | Check for new transcripts (scheduled monitoring) |
| **Export Commands** | |
| `parliament:export-csv` | Export members to CSV |
| `committees:export-csv` | Export committees to CSV |
| `bills:export-csv` | Export bills to CSV |
| `committees:export-files` | Generate individual committee files |
| `transcripts:export-analysis` | Export transcripts for external analysis |
| `transcripts:export-committee` | Advanced committee transcript export with formats |

## Requirements

- PHP 8.1+
- Laravel 10+
- SQLite/MySQL/PostgreSQL
- Internet connection for API access

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `php artisan test`
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).