# Parliament.bg API Documentation

This document provides comprehensive documentation for all APIs used from parliament.bg in this project.

## API Overview

The parliament.bg APIs provide access to parliamentary data including members, committees, bills, transcripts, and meeting videos. All APIs return JSON data and use HTTP GET requests.

## Base URLs

- **API Base**: `https://www.parliament.bg/api/v1/`
- **Static Resources**: `https://www.parliament.bg/`

## API Endpoints

### 1. Parliament Members API

| Endpoint | Purpose | Parameters | Response Data |
|----------|---------|------------|---------------|
| `/coll-list-ns/bg` | Get all parliament members | None | Array of member objects with basic info |
| `/mp-profile/bg/{member_id}` | Get detailed member profile | `member_id` (integer) | Detailed member information including profession, email |

**Usage Examples:**
- Get all members: `GET https://www.parliament.bg/api/v1/coll-list-ns/bg`
- Get member profile: `GET https://www.parliament.bg/api/v1/mp-profile/bg/12345`

**Response Structure - Member List:**
```json
{
  "colListMP": [
    {
      "A_ns_MP_id": 12345,
      "A_ns_MPL_Name1": "FirstName",
      "A_ns_MPL_Name2": "MiddleName", 
      "A_ns_MPL_Name3": "LastName",
      "A_ns_Va_name": "Electoral District",
      "A_ns_CL_value_short": "Political Party"
    }
  ]
}
```

**Response Structure - Member Profile:**
```json
{
  "A_ns_MP_Email": "email@example.com",
  "prsList": [
    {
      "A_ns_MP_Pr_TL_value": "Profession"
    }
  ]
}
```

### 2. Committees API

| Endpoint | Purpose | Parameters | Response Data |
|----------|---------|------------|---------------|
| `/coll-list/bg/3` | Get all committees | None | Array of committee objects |
| `/coll-list-mp/bg/{committee_id}/3?date=` | Get committee details with members | `committee_id` (integer), `date` (optional) | Committee details and member list |

**Usage Examples:**
- Get all committees: `GET https://www.parliament.bg/api/v1/coll-list/bg/3`
- Get committee details: `GET https://www.parliament.bg/api/v1/coll-list-mp/bg/3613/3?date=`

**Response Structure - Committee Details:**
```json
{
  "A_ns_C_id": 3613,
  "A_ns_CT_id": 3,
  "A_ns_CL_value": "Committee Name",
  "A_ns_C_active_count": 15,
  "A_ns_C_date_F": "2021-04-15",
  "A_ns_CDend": "9999-12-31",
  "A_ns_CDemail": "committee@parliament.bg",
  "A_ns_CDroom": "Room 123",
  "A_ns_CDphone": "+359 2 xxx xxxx",
  "A_ns_CDrules": "Committee rules text",
  "colListMP": [
    {
      "A_ns_MP_id": 12345,
      "A_ns_MP_PosL_value": "Position",
      "A_ns_MSP_date_F": "2021-04-15",
      "A_ns_MSP_date_T": "9999-12-31"
    }
  ]
}
```

### 3. Bills API

| Endpoint | Purpose | Parameters | Response Data |
|----------|---------|------------|---------------|
| `/com-acts/bg/{committee_id}/1` | Get bills by committee | `committee_id` (integer) | Array of bill objects |
| `/bill/{bill_id}` | Get detailed bill information | `bill_id` (integer) | Detailed bill data with submitters, committees, files |

**Usage Examples:**
- Get bills for committee: `GET https://www.parliament.bg/api/v1/com-acts/bg/3613/1`
- Get bill details: `GET https://www.parliament.bg/api/v1/bill/12345`

**Response Structure - Bills List:**
```json
[
  {
    "L_Act_id": 12345,
    "L_ActL_title": "Bill Title",
    "L_Act_sign": "51-554-01-114",
    "L_Act_date": "2024-01-15",
    "path": "/path/to/bill"
  }
]
```

**Response Structure - Bill Details:**
```json
{
  "L_Act_sign": "51-554-01-114",
  "L_SesL_value": "51st Session",
  "A_ns_folder": "51",
  "withdrawn": false,
  "imp_list": [
    {
      "A_ns_MPL_Name1": "FirstName",
      "A_ns_MPL_Name2": "MiddleName",
      "A_ns_MPL_Name3": "LastName"
    }
  ],
  "dist_list": [
    {
      "A_ns_CL_value": "Committee Name",
      "L_Act_DTL_value": "Main Committee"
    }
  ],
  "file_list": [
    {
      "FILENAME": "bill-document.pdf"
    }
  ]
}
```

### 4. Transcripts API

| Endpoint | Purpose | Parameters | Response Data |
|----------|---------|------------|---------------|
| `/archive-period/bg/A_Cm_Steno/{year}/{month}/{committee_id}/0` | Get transcript list for period | `year` (integer), `month` (integer), `committee_id` (integer) | Array of transcript metadata |
| `/com-steno/bg/{transcript_id}` | Get transcript content | `transcript_id` (integer) | Full transcript HTML content |

**Usage Examples:**
- Get transcripts for period: `GET https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/2024/8/3613/0`
- Get transcript content: `GET https://www.parliament.bg/api/v1/com-steno/bg/67890`

**Response Structure - Transcript List:**
```json
[
  {
    "t_id": 67890,
    "t_label": "Meeting Type",
    "t_date": "2024-08-15",
    "t_time": "10:00",
    "t_status": "published"
  }
]
```

**Response Structure - Transcript Content:**
```json
{
  "A_Cm_St_text": "<html>Full transcript HTML content</html>",
  "A_Cm_St_date": "2024-08-15",
  "A_Cm_St_sub": "Meeting Type",
  "A_Cm_Stid": 67890,
  "acts": []
}
```

### 5. Meeting Videos API

| Endpoint | Purpose | Parameters | Response Data |
|----------|---------|------------|---------------|
| `/archive-period/bg/A_Cm_Sit/{year}/{month}/{committee_id}/0` | Get meeting list for period | `year` (integer), `month` (integer), `committee_id` (integer) | Array of meeting metadata |
| `/com-meeting/bg/{meeting_id}` | Get meeting details with video URLs | `meeting_id` (integer) | Meeting details including video file paths |

**Usage Examples:**
- Get meetings for period: `GET https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/2024/8/3613/0`
- Get meeting details: `GET https://www.parliament.bg/api/v1/com-meeting/bg/98765`

**Response Structure - Meeting Details:**
```json
{
  "meeting_id": 98765,
  "committee_id": 3613,
  "meeting_date": "2024-08-15",
  "video_files": [
    "/Gallery/videoCW/file1.mp4",
    "/Gallery/videoCW/file2.mp4"
  ],
  "vifile": "meeting_recording",
  "default_video": "/Gallery/videoCW/default.mp4"
}
```

## Video File URLs

Meeting videos are served from the static content area:

| URL Pattern | Purpose | Parameters |
|-------------|---------|------------|
| `/Gallery/videoCW/{vifile}Part{i}.mp4` | Multi-part video files | `vifile` (string), `i` (part number) |
| `/Gallery/videoCW/autorecord/{date}/{formatted_date}-{meeting_id}-{committee_id}_Part1.mp4` | Auto-recorded videos | `date`, `meeting_id`, `committee_id` |

**Examples:**
- Multi-part video: `https://www.parliament.bg/Gallery/videoCW/meeting_recording_Part1.mp4`
- Auto-recorded: `https://www.parliament.bg/Gallery/videoCW/autorecord/2024/08/15/20240815-98765-3613_Part1.mp4`

## PDF Documents

Bill PDF documents are available at:

| URL Pattern | Purpose | Parameters |
|-------------|---------|------------|
| `/bills/{session}/{signature}.pdf` | Bill PDF documents | `session` (string), `signature` (string) |

**Example:**
- Bill PDF: `https://www.parliament.bg/bills/51/51-554-01-114.pdf`

## Rate Limiting & Best Practices

1. **Timeout**: All API calls use 30-second timeout
2. **Error Handling**: Check HTTP status codes before processing responses
3. **Data Validation**: Verify required fields exist in responses
4. **Retry Logic**: Implement exponential backoff for failed requests
5. **Caching**: Store frequently accessed data to reduce API calls

## Data Models Used

The following Laravel models store the retrieved data:

- **ParliamentMember**: Stores member information from `/coll-list-ns/bg` and `/mp-profile/bg/{id}`
- **Committee**: Stores committee data from `/coll-list/bg/3` and member relationships
- **Bill**: Stores bill information from `/com-acts/bg/{committee_id}/1` and `/bill/{id}`
- **Transcript**: Stores transcript data from steno APIs
- **VideoTranscription**: Stores video transcription results (not from parliament.bg API)

## Authentication

No authentication is required for these public APIs.

## Response Formats

All APIs return JSON data. Error responses typically include HTTP status codes:
- `200`: Success
- `404`: Resource not found
- `500`: Server error

## Common Issues

1. **Date Formats**: Dates may be in different formats across APIs
2. **Null Values**: Some fields may be null or missing
3. **HTML Content**: Transcript content includes HTML markup
4. **Large Responses**: Some endpoints return large datasets
5. **Video Availability**: Not all meetings have video recordings

## Usage in Commands

This API documentation corresponds to the following Artisan commands:

- `parliament:scrape` - Uses member APIs
- `committees:scrape` - Uses committee APIs  
- `bills:scrape` - Uses bill APIs
- `transcripts:scrape` - Uses transcript APIs
- `meetings:download-videos` - Uses meeting video APIs
- `transcripts:transcribe-videos` - Uses video APIs for transcription