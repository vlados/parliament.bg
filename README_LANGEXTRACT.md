# LangExtract Integration for Parliament Transcript Analysis

This implementation integrates Google's LangExtract library to extract structured information from Bulgarian Parliament transcripts.

## Features

- **Bill Discussions Extraction**: Identifies bills being discussed, voting results, and outcomes
- **Committee Decisions**: Extracts committee decisions with voting details
- **Amendments Tracking**: Captures proposed amendments and their status
- **Speaker Statements**: Analyzes who spoke and their key points
- **Structured Data Storage**: Saves extractions in JSON format for easy querying

## Setup

### 1. Install Python Dependencies

```bash
pip install -r python_scripts/requirements.txt
```

Or use the built-in installer:

```bash
php artisan transcripts:extract --install-deps
```

### 2. Set up Python Virtual Environment

Create and activate a Python virtual environment:

```bash
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r python_scripts/requirements.txt
```

### 3. Configure Gemini API Key

Add your Gemini API key to `.env`:

```
GEMINI_API_KEY=your-real-gemini-api-key-here
```

Get your API key from: https://aistudio.google.com/app/apikey

**Note:** Replace `test-key-for-dependency-check` with your actual API key for real extraction.

### 4. Run Database Migration

```bash
php artisan migrate
```

## Usage

### Check Dependencies

```bash
php artisan transcripts:extract --check-deps
```

### Extract from All Transcripts

```bash
# Extract all types of information from all transcripts
php artisan transcripts:extract

# Extract only bill discussions
php artisan transcripts:extract --type=bill_discussions

# Extract from specific committee
php artisan transcripts:extract --committee=2014

# Extract from date range
php artisan transcripts:extract --from=2024-01-01 --to=2024-12-31

# Limit processing
php artisan transcripts:extract --limit=10

# Re-process already extracted transcripts
php artisan transcripts:extract --force
```

### Extraction Types

- `bill_discussions` - Extract bill discussions and voting results
- `committee_decisions` - Extract committee decisions
- `amendments` - Extract proposed amendments
- `speaker_statements` - Extract speaker statements
- `all` - Extract all types (default)

## Architecture

### PHP Components

1. **LangExtractService** (`app/Services/LangExtractService.php`)
   - PHP wrapper for Python extraction script
   - Handles temporary file management
   - Provides specific extraction methods

2. **ExtractProtocolChanges Command** (`app/Console/Commands/ExtractProtocolChanges.php`)
   - Artisan command for batch processing
   - Supports various filtering options
   - Progress tracking and error handling

3. **ProtocolExtraction Model** (`app/Models/ProtocolExtraction.php`)
   - Eloquent model for stored extractions
   - Helper methods for accessing extracted data
   - Statistics and analysis methods

### Python Components

1. **extract_protocol_changes.py** (`python_scripts/extract_protocol_changes.py`)
   - Main extraction script using LangExtract
   - Defines extraction schemas for different types
   - Uses Gemini 1.5 Pro for analysis

## Database Schema

The `protocol_extractions` table stores:
- `transcript_id` - Link to source transcript
- `extraction_type` - Type of extraction performed
- `extracted_data` - JSON containing extracted information
- `extraction_date` - When extraction was performed
- `metadata` - Additional metadata about the extraction

## Example Extracted Data

### Bill Discussion
```json
{
  "bill_number": "402-01-45",
  "bill_title": "Законопроект за изменение на Закона за енергетиката",
  "reading": "първо четене",
  "speakers": ["Иванов", "Петров"],
  "voting_results": {
    "for": 142,
    "against": 45,
    "abstained": 12
  },
  "outcome": "приет"
}
```

### Committee Decision
```json
{
  "committee": "Комисия по правни въпроси",
  "decision_type": "одобрение на предложение",
  "subject": "промени в Наказателния кодекс",
  "members_present": 12,
  "voting": {
    "for": 8,
    "against": 3,
    "abstained": 1
  },
  "resolution": "внасяне в пленарна зала"
}
```

## Monitoring & Debugging

Check extraction logs:
```bash
tail -f storage/logs/laravel.log
```

View extraction statistics in database:
```sql
SELECT 
    extraction_type,
    COUNT(*) as count,
    MAX(extraction_date) as latest
FROM protocol_extractions
GROUP BY extraction_type;
```

## Limitations & Considerations

1. **API Costs**: Gemini API usage may incur costs for large-scale processing
2. **Processing Time**: Large transcripts may take time to process
3. **Language**: Optimized for Bulgarian language transcripts
4. **Accuracy**: Results depend on transcript quality and structure

## Future Enhancements

- [ ] Add web interface for viewing extractions
- [ ] Implement real-time extraction on new transcripts
- [ ] Add export functionality (CSV, Excel)
- [ ] Create analytics dashboard
- [ ] Implement caching for repeated extractions
- [ ] Add support for batch API calls