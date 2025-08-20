---
layout: page
title: Features
permalink: /features/
---

# Features Overview

Parliament Scraper provides comprehensive tools for extracting, processing, and analyzing Bulgarian parliamentary data.

## 🏛️ Parliament Members Management

### Data Extraction
- **Complete Member Profiles**: Extract all parliament members with detailed information
- **Electoral Districts**: Track representation by geographic regions
- **Political Affiliations**: Monitor party memberships and changes
- **Professional Backgrounds**: Capture member professions and expertise
- **Contact Information**: Official parliament email addresses

### Member Data Includes
- Full names (first, middle, last)
- Electoral district representation
- Political party affiliation
- Professional background
- Official parliament email
- Unique parliament member ID

---

## 🏢 Committee Management

### Committee Structure
- **All Parliamentary Committees**: Extract complete committee information
- **Member Relationships**: Track committee memberships and positions
- **Leadership Positions**: Identify chairpersons and deputy chairs
- **Term Tracking**: Monitor membership periods and changes
- **Contact Details**: Committee email and phone information

### Committee Data Includes
- Committee unique identifiers
- Committee names and types
- Active member counts
- Activity periods (date from/to)
- Contact information (email, phone, room)
- Member positions and terms

---

## 📄 Legislative Bills Tracking

### Bill Information
- **Complete Bill Database**: Extract all bills by committee
- **PDF Document Processing**: Download and extract text from bill PDFs
- **Submitter Information**: Track who submitted each bill
- **Committee Assignments**: Monitor which committees handle which bills
- **Status Tracking**: Monitor bill progress and withdrawals

### Bill Data Includes
- Unique bill identifiers
- Official bill signatures and numbers
- Bill titles in Bulgarian
- Submission dates
- PDF document URLs and extracted text
- Word and character counts
- Submitter information (JSON)
- Committee assignments
- Withdrawal status

### Advanced Bill Features
- **Automatic PDF Processing**: Extract text content from bill PDFs
- **Detailed Fetching**: Optional detailed information extraction
- **Scheduled Monitoring**: Check for new bills automatically
- **Committee Filtering**: Process bills for specific committees

---

## 🎥 Video Transcription System

### Direct URL Processing
- **No Downloads Required**: Process videos directly from parliament.bg URLs
- **Multiple Video Formats**: Handle various video file types and structures
- **Smart Meeting Discovery**: Automatically find meetings with available videos
- **Batch Processing**: Process multiple videos concurrently

### AI-Powered Transcription
- **ElevenLabs Integration**: Professional-grade speech-to-text API
- **Multiple Models**: Support for various language models
- **Language Detection**: Automatic language identification with confidence scores
- **Speaker Diarization**: Identify and separate different speakers
- **Word-Level Timestamps**: Precise timing for each word

### Transcription Features
- Process videos from specific committees or meetings
- Filter by date ranges (year, month, custom dates)
- Resume capability (skip already transcribed videos)
- Cost tracking and estimation
- Real-time progress monitoring
- Comprehensive error handling

---

## 📜 Transcript Processing

### Meeting Transcripts
- **Complete Transcript Database**: Extract all committee meeting transcripts
- **HTML Content Processing**: Handle complex HTML formatting
- **Metadata Extraction**: Capture meeting dates, types, and participants
- **Bulk Processing**: Efficiently process multiple months/years
- **Download Status Tracking**: Monitor which transcripts have been downloaded

### Transcript Features
- Interactive committee selection
- Entire year processing (all 12 months automatically)
- Beautiful table views with status indicators
- CSV export functionality
- Download grouping for efficiency

---

## 🤖 AI-Powered Analysis

### Content Analysis
- **Bill Discussion Detection**: Identify when specific bills are discussed
- **Amendment Tracking**: Monitor proposed changes and modifications
- **Speaker Identification**: Track who proposed what changes
- **Confidence Scoring**: AI confidence levels for each analysis
- **Status Classification**: Categorize discussion outcomes (accepted, rejected, debated)

### Analysis Data Includes
- Related transcript and bill IDs
- Bill identifiers mentioned in discussions
- Proposer names and roles
- Amendment types (new text, modification, deletion)
- Discussion status and outcomes
- AI confidence scores (0-1 scale)
- Relevant content excerpts
- Generated summaries

---

## 🔍 Protocol Extraction

### Structured Data Extraction
- **LangExtract Integration**: Advanced language processing for protocol structure
- **Multiple Extraction Types**: Bill discussions, committee decisions, amendments, speaker statements
- **Confidence Scoring**: Quality assessment for extractions
- **Flexible Processing**: Process specific transcripts or date ranges
- **Python Integration**: Seamless integration with Python extraction tools

### Extraction Types
- Bill discussions and debates
- Committee decisions and votes
- Amendment proposals and changes
- Individual speaker statements
- Complete protocol structures

---

## 📊 Data Export & Formats

### Export Formats
- **CSV**: Excel-compatible with UTF-8 BOM encoding
- **JSON**: Structured data for programmatic access
- **HTML**: Formatted output for web viewing
- **TXT**: Human-readable plain text format

### Export Features
- **Bulgarian Text Support**: Proper encoding for Cyrillic characters
- **Individual Committee Files**: Separate exports per committee
- **Date Range Filtering**: Export specific time periods
- **Metadata Inclusion**: Optional metadata and analysis inclusion
- **Custom Output Directories**: Organize exports efficiently

### Specialized Exports
- Parliament members with full profiles
- Committee structures and memberships
- Legislative bills with extracted text
- Meeting transcripts with analysis
- Individual committee files with formatting

---

## 🔔 Monitoring & Automation

### Scheduled Monitoring
- **New Bill Detection**: Automatically check for newly submitted bills
- **Transcript Monitoring**: Monitor for new committee meeting transcripts
- **Notification Support**: Alert systems for new content
- **Configurable Periods**: Set custom monitoring timeframes

### Automation Features
- Scheduled command execution
- Automatic analysis triggers
- Error handling and recovery
- Progress tracking and reporting
- Resource usage optimization

---

## 🌍 Bulgarian Language Support

### Text Processing
- **UTF-8 Encoding**: Full support for Bulgarian Cyrillic characters
- **BOM Encoding**: Excel-compatible CSV files
- **Character Transliteration**: Safe filename generation (а→a, б→b, etc.)
- **Word Count Accuracy**: Proper counting for Bulgarian text
- **Search Functionality**: Bulgarian text search and filtering

### Internationalization
- Bulgarian interface elements
- Proper date formatting
- Currency and number formatting
- Cultural considerations in data processing

---

## 🔧 Technical Features

### Performance Optimization
- **Batch Processing**: Efficient handling of large datasets
- **Progress Tracking**: Real-time progress bars and statistics
- **Memory Management**: Optimized for large file processing
- **Concurrent Operations**: Parallel processing where possible
- **Resume Capability**: Restart interrupted operations

### Error Handling
- **Robust Error Recovery**: Graceful handling of API failures
- **Detailed Logging**: Comprehensive error reporting
- **Retry Logic**: Automatic retries with exponential backoff
- **Validation**: Input and data validation throughout
- **Timeout Management**: Appropriate timeouts for network operations

### Database Features
- **Eloquent ORM**: Clean data access patterns
- **Relationship Management**: Complex many-to-many relationships
- **Migration System**: Version-controlled database changes
- **Indexing Strategy**: Optimized database performance
- **Data Integrity**: Foreign key constraints and validation

---

## 📋 Command Summary

### Core Scraping Commands
- `parliament:scrape` - Extract all parliament members
- `committees:scrape` - Extract committees and relationships
- `bills:scrape` - Extract legislative bills with options
- `transcripts:scrape` - Extract meeting transcripts
- `videos:transcribe-v2` - Transcribe meeting videos

### Analysis Commands
- `analyze:transcripts` - AI analysis of transcript content
- `transcripts:extract` - Extract structured protocol data

### Export Commands
- `parliament:export-csv` - Export members to CSV
- `committees:export-csv` - Export committees to CSV
- `bills:export-csv` - Export bills to CSV
- `transcripts:export-analysis` - Export for external analysis
- `transcripts:export-committee` - Advanced committee exports

### Utility Commands
- `transcripts:list` - Interactive transcript management
- `bills:check-new` - Monitor for new bills
- `transcripts:check-new` - Monitor for new transcripts