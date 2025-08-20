---
layout: page
title: Usage
permalink: /usage/
---

# Usage Guide

Practical examples and workflows for using Parliament Scraper to extract and analyze Bulgarian parliamentary data.

## 🚀 Quick Start Workflow

### Complete Data Extraction Pipeline

```bash
# 1. Extract basic parliamentary structure
php artisan parliament:scrape
php artisan committees:scrape

# 2. Extract legislative content
php artisan bills:scrape --all-committees --detailed

# 3. Extract meeting content
php artisan transcripts:scrape --all --year=2024

# 4. Transcribe video content (requires ElevenLabs API)
php artisan videos:transcribe-v2 --committee=3613 --since=2024-01-01

# 5. Analyze content with AI
php artisan analyze:transcripts --since=2024-01-01
```

---

## 👥 Parliament Members

### Basic Member Extraction

```bash
# Extract all parliament members
php artisan parliament:scrape
```

**What this does**:
- Fetches all current parliament members
- Extracts names, electoral districts, political parties
- Downloads detailed profiles including professions and email addresses
- Stores relationships between members and their details

### Export Members Data

```bash
# Export to CSV with Bulgarian text support
php artisan parliament:export-csv
```

**Output**: `storage/app/parliament_members.csv` with UTF-8 BOM encoding for Excel compatibility.

---

## 🏢 Committees

### Committee Data Extraction

```bash
# Extract all committees and their members
php artisan committees:scrape
```

**What this does**:
- Fetches all parliamentary committees
- Extracts committee details (names, contact info, rules)
- Maps members to committees with their positions
- Tracks leadership roles (chairpersons, deputy chairs)

### Committee-Specific Operations

```bash
# Export committee structure
php artisan committees:export-csv

# Generate individual committee files
php artisan committees:export-files --format=txt

# Custom output directory
php artisan committees:export-files --folder=my_committees
```

---

## 📄 Legislative Bills

### Bill Scraping Options

```bash
# Scrape bills for specific committee
php artisan bills:scrape --committee-id=3613

# Scrape bills for all committees (comprehensive)
php artisan bills:scrape --all-committees

# Include PDF text extraction and detailed analysis
php artisan bills:scrape --all-committees --detailed

# Only download PDFs for existing bills
php artisan bills:scrape --pdf-only
```

### Bill Monitoring

```bash
# Check for new bills in last 7 days
php artisan bills:check-new

# Check for new bills in last 30 days  
php artisan bills:check-new --days=30

# Monitor specific committee with notifications
php artisan bills:check-new --committee-id=3613 --notify
```

### Bill Data Export

```bash
# Export all bills
php artisan bills:export-csv

# Export recent bills only
php artisan bills:export-csv --days=30

# Export bills for specific committee
php artisan bills:export-csv --committee-id=3613
```

---

## 📜 Meeting Transcripts

### Transcript Extraction

```bash
# Scrape transcripts for specific committee and month
php artisan transcripts:scrape --committee=3613 --year=2024 --month=6

# Scrape transcripts for entire year (all 12 months)
php artisan transcripts:scrape --committee=3613 --year=2024

# Scrape transcripts for all committees (current month)
php artisan transcripts:scrape --all
```

### Interactive Transcript Management

```bash
# Interactive committee selection with table view
php artisan transcripts:list

# List transcripts for specific committee
php artisan transcripts:list --committee=3613

# Show only downloaded transcripts
php artisan transcripts:list --downloaded

# Export transcript list to CSV
php artisan transcripts:list --export
```

### Transcript Monitoring

```bash
# Check for new transcripts (all committees, current month)
php artisan transcripts:check-new

# Check specific committee with automatic analysis
php artisan transcripts:check-new --committee=3613 --analyze

# Send notifications for new transcripts
php artisan transcripts:check-new --notify
```

---

## 🎥 Video Transcription

### Basic Video Transcription

```bash
# Transcribe videos for specific committee (recommended approach)
php artisan videos:transcribe-v2 --committee=3613 --since=2025-01-01

# Transcribe videos for multiple committees
php artisan videos:transcribe-v2 --committee=3613 --committee=3595 --since=2024-01-01

# Transcribe all committees (resource intensive)
php artisan videos:transcribe-v2 --all --since=2024-01-01
```

### Targeted Video Processing

```bash
# Transcribe specific meeting
php artisan videos:transcribe-v2 --meeting=13565

# Transcribe for specific time period with limit
php artisan videos:transcribe-v2 --committee=3613 --since=2024-06-01 --limit=20

# Overwrite existing transcriptions
php artisan videos:transcribe-v2 --committee=3613 --overwrite
```

### Video Transcription Options

```bash
# Use specific ElevenLabs model
php artisan videos:transcribe-v2 --committee=3613 --model=eleven_multilingual_v2

# Dry run to see what would be processed
php artisan videos:transcribe-v2 --committee=3613 --dry-run

# Process with high memory limit (for large videos)
php -d memory_limit=-1 artisan videos:transcribe-v2 --committee=3613
```

### Legacy Video Processing (from downloaded files)

```bash
# Download videos first (if needed)
php artisan meetings:download-videos --committee=3613 --year=2024

# Transcribe from downloaded files
php artisan videos:transcribe --use-files --directory=/path/to/videos
```

---

## 🤖 AI Analysis

### Transcript Analysis

```bash
# Analyze recent unanalyzed transcripts (default: 10)
php artisan analyze:transcripts

# Analyze all transcripts
php artisan analyze:transcripts --all

# Analyze specific transcript IDs
php artisan analyze:transcripts --ids=1 --ids=2 --ids=3

# Analyze transcripts from specific date
php artisan analyze:transcripts --since=2024-01-01

# Analyze transcripts from specific committee
php artisan analyze:transcripts --committee=3613

# Re-analyze already analyzed transcripts
php artisan analyze:transcripts --reanalyze

# Dry run to see what would be analyzed
php artisan analyze:transcripts --dry-run
```

### Protocol Extraction

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
```

---

## 📊 Data Export & Analysis

### Advanced Transcript Export

```bash
# Interactive committee selection for export
php artisan transcripts:export-committee

# Export specific committees in different formats
php artisan transcripts:export-committee --committee=3613 --format=html

# Export with date range and metadata
php artisan transcripts:export-committee --from=2024-01-01 --to=2024-12-31 --include-metadata

# Create separate files per transcript
php artisan transcripts:export-committee --separate-files --include-analysis
```

### Export for External Analysis

```bash
# Export transcripts for analysis
php artisan transcripts:export-analysis --committee=3613

# Export in JSON format with AI analysis
php artisan transcripts:export-analysis --format=json --include-analysis

# Custom output directory
php artisan transcripts:export-analysis --output=research_data
```

---

## 🔄 Automated Workflows

### Daily Monitoring Setup

Create a shell script for daily parliamentary monitoring:

```bash
#!/bin/bash
# daily_monitor.sh

echo "Starting daily parliamentary monitoring..."

# Check for new bills
php artisan bills:check-new --days=1

# Check for new transcripts  
php artisan transcripts:check-new

# Analyze any new transcripts
php artisan analyze:transcripts --since=$(date -d '1 day ago' +%Y-%m-%d)

echo "Daily monitoring complete."
```

### Weekly Data Refresh

```bash
#!/bin/bash
# weekly_refresh.sh

echo "Starting weekly data refresh..."

# Update parliament members (in case of changes)
php artisan parliament:scrape

# Update committee memberships
php artisan committees:scrape

# Check for new bills in last week
php artisan bills:check-new --days=7

# Export updated data
php artisan parliament:export-csv
php artisan committees:export-csv
php artisan bills:export-csv --days=7

echo "Weekly refresh complete."
```

---

## 📋 Common Use Cases

### Research Workflow

```bash
# 1. Set up base data
php artisan parliament:scrape
php artisan committees:scrape

# 2. Focus on specific research period
php artisan transcripts:scrape --committee=3613 --year=2024
php artisan bills:scrape --committee-id=3613 --detailed

# 3. Analyze content
php artisan analyze:transcripts --committee=3613
php artisan transcripts:extract --committee=3613

# 4. Export for analysis
php artisan transcripts:export-analysis --committee=3613 --include-analysis
```

### Journalism Workflow

```bash
# 1. Monitor recent activity
php artisan bills:check-new --days=30
php artisan transcripts:check-new

# 2. Deep dive on specific topics
php artisan videos:transcribe-v2 --committee=3613 --since=2024-01-01
php artisan analyze:transcripts --committee=3613 --since=2024-01-01

# 3. Export findings
php artisan transcripts:export-committee --committee=3613 --format=html --include-analysis
```

### Academic Research Workflow

```bash
# 1. Comprehensive data collection
php artisan parliament:scrape
php artisan committees:scrape
php artisan bills:scrape --all-committees --detailed

# 2. Multi-year transcript analysis
for year in 2022 2023 2024; do
  php artisan transcripts:scrape --all --year=$year
done

# 3. AI analysis across time periods
php artisan analyze:transcripts --all
php artisan transcripts:extract --from=2022-01-01 --to=2024-12-31

# 4. Structured data export
php artisan transcripts:export-analysis --format=json --include-analysis
```

---

## ⚠️ Performance Tips

### Memory Management

```bash
# For large operations, increase memory limit
php -d memory_limit=-1 artisan videos:transcribe-v2 --all

# Process in smaller batches
php artisan transcripts:scrape --committee=3613 --year=2024 --month=1
php artisan transcripts:scrape --committee=3613 --year=2024 --month=2
# ... continue for each month
```

### API Rate Limiting

```bash
# Add delays between operations for large datasets
php artisan bills:scrape --all-committees --detailed
sleep 60
php artisan transcripts:scrape --all --year=2024
```

### Efficient Processing

```bash
# Use dry-run first to estimate scope
php artisan videos:transcribe-v2 --committee=3613 --dry-run

# Then process with appropriate limits
php artisan videos:transcribe-v2 --committee=3613 --limit=50
```

---

## 🔍 Troubleshooting Common Issues

### API Connection Issues

```bash
# Test basic connectivity
curl -I "https://www.parliament.bg/api/v1/coll-list-ns/bg"

# Check if specific committee exists
php artisan committees:scrape --limit=1
```

### Memory Issues

```bash
# Check current memory usage
php -i | grep memory_limit

# Run with unlimited memory for large operations
php -d memory_limit=-1 artisan your:command
```

### ElevenLabs API Issues

```bash
# Verify API key is set
php artisan tinker
# In tinker: config('services.elevenlabs.api_key')

# Test with small transcript first
php artisan videos:transcribe-v2 --meeting=13565 --dry-run
```

### Database Issues

```bash
# Check database connection
php artisan migrate:status

# Reset and rebuild if needed
php artisan migrate:fresh
php artisan parliament:scrape
```