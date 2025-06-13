# Parliament Scraper

A Laravel-based web scraper for extracting parliament member, committee, and bill information from parliament.bg.

## Features

- 🏛️ **Parliament Members Scraping**: Extract detailed information about all parliament members
- 🏢 **Committee Management**: Scrape committee data with member relationships
- 📄 **Bills Tracking**: Scrape and monitor legislative bills by committee
- 🔔 **Scheduled Monitoring**: Automated checking for new bills with notifications
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
```

### Scheduled Bill Monitoring

Check for new bills automatically:

```bash
# Check for new bills in last 7 days
php artisan bills:check-new

# Check for new bills in last 30 days
php artisan bills:check-new --days=30

# Check specific committee with notifications
php artisan bills:check-new --committee-id=3613 --notify
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
- **sign**: Official bill number (e.g., 51-554-01-114)
- **bill_date**: Date the bill was submitted
- **path**: Bill category path
- **committee_id**: Committee handling the bill

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
| `parliament:scrape` | Scrape all parliament members |
| `committees:scrape` | Scrape all committees and relationships |
| `bills:scrape` | Scrape bills for committees |
| `bills:check-new` | Check for new bills (scheduled monitoring) |
| `parliament:export-csv` | Export members to CSV |
| `committees:export-csv` | Export committees to CSV |
| `bills:export-csv` | Export bills to CSV |
| `committees:export-files` | Generate individual committee files |

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