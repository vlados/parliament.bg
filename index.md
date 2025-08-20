---
layout: home
title: Home
---

# Parliament Scraper

A comprehensive Laravel-based web scraper for extracting parliament member, committee, and bill information from parliament.bg with AI-powered analysis capabilities.

## 🚀 Key Features

<div class="feature-grid">
  <div class="feature-card">
    <h3>🏛️ Parliament Data</h3>
    <p>Extract detailed information about parliament members, committees, and their relationships</p>
  </div>
  
  <div class="feature-card">
    <h3>📄 Bills Tracking</h3>
    <p>Scrape and monitor legislative bills with PDF text extraction and automated analysis</p>
  </div>
  
  <div class="feature-card">
    <h3>🎥 Video Transcription</h3>
    <p>AI-powered transcription of committee meeting videos using ElevenLabs Speech-to-Text</p>
  </div>
  
  <div class="feature-card">
    <h3>📜 Transcript Analysis</h3>
    <p>Automated analysis of meeting transcripts for bill discussions and amendments</p>
  </div>
  
  <div class="feature-card">
    <h3>🔍 Protocol Extraction</h3>
    <p>Extract structured protocol changes using advanced language processing</p>
  </div>
  
  <div class="feature-card">
    <h3>📊 Data Export</h3>
    <p>Export data to CSV, JSON, HTML formats with proper Bulgarian text support</p>
  </div>
</div>

## 🔧 Quick Start

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd parliament-scraper

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate
```

### Basic Usage

```bash
# Scrape parliament members
php artisan parliament:scrape

# Scrape committees
php artisan committees:scrape

# Scrape bills for a committee
php artisan bills:scrape --committee-id=3613

# Transcribe meeting videos
php artisan videos:transcribe-v2 --committee=3613 --since=2025-01-01
```

## 📖 Documentation

- [Features Overview](features.html) - Detailed feature descriptions
- [Installation Guide](installation.html) - Complete setup instructions  
- [Usage Examples](usage.html) - Command examples and workflows
- [API Reference](api-reference.html) - Parliament.bg API documentation

## 🎯 Use Cases

- **Civic Monitoring**: Track parliamentary activities and legislative processes
- **Research Projects**: Analyze voting patterns and bill discussions
- **Transparency Initiatives**: Make parliamentary data more accessible
- **Academic Studies**: Research political discourse and decision-making
- **Journalism**: Investigate legislative trends and political activities

## 🌍 Bulgarian Language Support

Full support for Bulgarian text with proper UTF-8 encoding and transliteration:
- Excel-compatible CSV exports with BOM encoding
- Character mapping for safe filenames
- Comprehensive text extraction from PDF documents

## 🤖 AI-Powered Features

- **Speech-to-Text**: Convert meeting videos to searchable text
- **Content Analysis**: Identify bill discussions and amendments
- **Speaker Diarization**: Separate and identify different speakers
- **Protocol Extraction**: Structure unorganized meeting transcripts

## 📊 Data Coverage

The system covers:
- **50+ Parliamentary Committees**
- **1000+ Parliament Members** 
- **Legislative Bills** with full text
- **Meeting Transcripts** and videos
- **Historical Data** going back to 2021

## 🔗 Related Links

- [Parliament.bg Official Site](https://www.parliament.bg)
- [Laravel Framework](https://laravel.com)
- [ElevenLabs Speech-to-Text](https://elevenlabs.io)

<style>
.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin: 2rem 0;
}

.feature-card {
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 1.5rem;
  background: #f8f9fa;
}

.feature-card h3 {
  margin-top: 0;
  color: #2c3e50;
}

.feature-card p {
  margin-bottom: 0;
  color: #5a6c7d;
}
</style>