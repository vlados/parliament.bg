---
layout: page
title: Installation
permalink: /installation/
---

# Installation Guide

Complete setup instructions for Parliament Scraper on your local development environment.

## 📋 Requirements

### System Requirements
- **PHP**: 8.3+ (recommended) or 8.1+
- **Laravel**: 12+ (current) or 10+
- **Database**: SQLite, MySQL, or PostgreSQL
- **Node.js**: 16+ for asset compilation
- **Composer**: Latest version
- **FFmpeg**: For video processing (optional but recommended)

### Optional Requirements
- **Python**: 3.8+ for LangExtract protocol processing
- **ElevenLabs API Key**: For video transcription features
- **Git**: For version control

---

## 🚀 Quick Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/parliament-scraper.git
cd parliament-scraper
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate
```

### 6. Build Assets (Optional)

```bash
# For development
npm run dev

# For production
npm run build
```

---

## ⚙️ Environment Configuration

### Basic Configuration

Edit your `.env` file with the following settings:

```env
APP_NAME="Parliament Scraper"
APP_ENV=local
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database Configuration
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=parliament_scraper
# DB_USERNAME=root
# DB_PASSWORD=
```

### Database Options

#### SQLite (Recommended for Development)
```env
DB_CONNECTION=sqlite
# No additional configuration needed
```

#### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=parliament_scraper
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=parliament_scraper
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 🔑 API Keys Configuration

### ElevenLabs API (for Video Transcription)

1. Sign up at [ElevenLabs](https://elevenlabs.io)
2. Get your API key from the dashboard
3. Add to your `.env` file:

```env
ELEVENLABS_API_KEY=your_elevenlabs_api_key
```

### OpenAI API (for AI Analysis)

```env
OPENAI_API_KEY=your_openai_api_key
```

### Anthropic API (Alternative AI Provider)

```env
ANTHROPIC_API_KEY=your_anthropic_api_key
```

### Google Gemini API

```env
GEMINI_API_KEY=your_gemini_api_key
```

---

## 🐍 Python Setup (Optional)

For advanced protocol extraction features:

### 1. Install Python Dependencies

```bash
# Create virtual environment
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt
```

### 2. Configure Python Path

Add to your `.env` file:

```env
PYTHON_PATH=/path/to/your/python3
# Or if using virtual environment:
PYTHON_PATH=/path/to/your/venv/bin/python
```

### 3. Test Installation

```bash
php artisan transcripts:extract --check-deps
```

---

## 🎥 FFmpeg Setup (Optional)

For video processing capabilities:

### macOS (using Homebrew)
```bash
brew install ffmpeg
```

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install ffmpeg
```

### Windows
Download from [FFmpeg website](https://ffmpeg.org/download.html) and add to PATH.

### Verify Installation
```bash
ffmpeg -version
```

---

## 🗄️ Database Setup Details

### Run Migrations

```bash
php artisan migrate
```

### Available Tables

The migration will create these tables:
- `parliament_members` - Parliament member information
- `committees` - Committee details
- `committee_parliament_member` - Many-to-many relationships
- `bills` - Legislative bills data
- `transcripts` - Meeting transcripts
- `bill_analyses` - AI analysis results
- `video_transcriptions` - Video transcription data
- `protocol_extractions` - Extracted protocol data

### Seed Data (Optional)

```bash
# If seeders are available
php artisan db:seed
```

---

## 🔧 Advanced Configuration

### File Permissions

Ensure proper permissions for storage and cache directories:

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Symbolic Link for Storage

```bash
php artisan storage:link
```

### Cache Configuration

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🏃‍♂️ Running the Application

### Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

### Filament Admin Panel

If using Filament admin features:
```bash
# Create admin user
php artisan make:filament-user
```

Visit `http://localhost:8000/admin` for the admin interface.

---

## ✅ Verify Installation

### Test Basic Commands

```bash
# Test parliament member scraping
php artisan parliament:scrape --help

# Test committee scraping
php artisan committees:scrape --help

# Check available commands
php artisan list
```

### Test Database Connection

```bash
# Test with tinker
php artisan tinker
# In tinker: DB::connection()->getPdo()
```

### Test API Access

```bash
# Test parliament.bg API access
php artisan committees:scrape --limit=1
```

---

## 🐛 Troubleshooting

### Common Issues

#### Memory Limit Errors
```bash
# Increase memory limit for specific commands
php -d memory_limit=-1 artisan your:command
```

#### Permission Errors
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

#### Database Connection Issues
- Verify database credentials in `.env`
- Ensure database server is running
- Check firewall settings

#### FFmpeg Not Found
```bash
# Check if FFmpeg is installed
which ffmpeg

# Install if missing (see FFmpeg setup above)
```

### Laravel-Specific Issues

#### Key Not Set Error
```bash
php artisan key:generate
```

#### Migration Errors
```bash
# Reset and re-run migrations
php artisan migrate:reset
php artisan migrate
```

#### Composer Issues
```bash
# Clear composer cache
composer clear-cache
composer install --no-cache
```

---

## 🔄 Updating

### Update Dependencies

```bash
# Update PHP dependencies
composer update

# Update Node.js dependencies
npm update

# Run any new migrations
php artisan migrate
```

### Update Configuration

```bash
# Clear and recache configuration
php artisan config:clear
php artisan config:cache
```

---

## 🚀 Production Deployment

### Environment Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

### Optimization

```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build production assets
npm run build
```

### Security

- Use HTTPS in production
- Set strong database passwords
- Keep API keys secure
- Regular security updates

---

## 📞 Support

If you encounter issues during installation:

1. Check the [Troubleshooting](#-troubleshooting) section
2. Review Laravel documentation
3. Check project issues on GitHub
4. Ensure all requirements are met

### Useful Commands for Debugging

```bash
# Check PHP version and extensions
php -v
php -m

# Check Laravel installation
php artisan --version

# View detailed error logs
tail -f storage/logs/laravel.log

# Test database connection
php artisan migrate:status
```