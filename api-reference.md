---
layout: page
title: API Reference
permalink: /api-reference/
---

# Parliament.bg API Reference

Comprehensive documentation for all parliament.bg APIs used in the scraper with detailed request/response examples.

## 📚 API Overview

The parliament.bg APIs provide access to parliamentary data including members, committees, bills, transcripts, and meeting videos. All APIs return JSON data and use HTTP GET requests.

### Base URLs
- **API Base**: `https://www.parliament.bg/api/v1/`
- **Static Resources**: `https://www.parliament.bg/`

### Authentication
No authentication is required for these public APIs.

---

## 👥 Parliament Members API

### Get All Parliament Members

**Endpoint**: `GET /coll-list-ns/bg`

```bash
curl "https://www.parliament.bg/api/v1/coll-list-ns/bg"
```

**Response Structure**:
```json
{
  "colListMP": [
    {
      "A_ns_MP_id": 12345,
      "A_ns_MPL_Name1": "FirstName", 
      "A_ns_MPL_Name2": "MiddleName",
      "A_ns_MPL_Name3": "LastName",
      "A_ns_Va_name": "Electoral District Name",
      "A_ns_CL_value_short": "Political Party Abbreviation"
    }
  ]
}
```

**Field Descriptions**:
- `A_ns_MP_id`: Unique parliament member identifier
- `A_ns_MPL_Name1/2/3`: First, middle, and last names
- `A_ns_Va_name`: Electoral district representation
- `A_ns_CL_value_short`: Political party abbreviation

### Get Member Profile Details

**Endpoint**: `GET /mp-profile/bg/{member_id}`

```bash
curl "https://www.parliament.bg/api/v1/mp-profile/bg/12345"
```

**Response Structure**:
```json
{
  "A_ns_MP_Email": "member@parliament.bg",
  "A_ns_MP_Phone": "+359 2 xxx xxxx",
  "A_ns_MP_Fax": "+359 2 xxx xxxx",
  "prsList": [
    {
      "A_ns_MP_Pr_TL_value": "Profession Name"
    }
  ]
}
```

**Field Descriptions**:
- `A_ns_MP_Email`: Official parliament email
- `A_ns_MP_Phone/Fax`: Contact information
- `prsList`: Array of professional backgrounds

---

## 🏢 Committees API

### Get All Committees

**Endpoint**: `GET /coll-list/bg/3`

```bash
curl "https://www.parliament.bg/api/v1/coll-list/bg/3"
```

**Response Structure**:
```json
[
  {
    "A_ns_C_id": 3613,
    "A_ns_CT_id": 3,
    "A_ns_CL_value": "Committee Name in Bulgarian",
    "A_ns_C_active_count": 15,
    "A_ns_C_date_F": "2021-04-15",
    "A_ns_CDend": "9999-12-31"
  }
]
```

**Field Descriptions**:
- `A_ns_C_id`: Unique committee identifier
- `A_ns_CT_id`: Committee type ID (3 = standing committee)
- `A_ns_CL_value`: Committee name in Bulgarian
- `A_ns_C_active_count`: Number of active members
- `A_ns_C_date_F`: Committee formation date
- `A_ns_CDend`: Committee end date (9999-12-31 = active)

### Get Committee Details with Members

**Endpoint**: `GET /coll-list-mp/bg/{committee_id}/3?date=`

```bash
curl "https://www.parliament.bg/api/v1/coll-list-mp/bg/3613/3?date="
```

**Response Structure**:
```json
{
  "A_ns_C_id": 3613,
  "A_ns_CT_id": 3,
  "A_ns_CL_value": "Committee Name",
  "A_ns_C_active_count": 15,
  "A_ns_C_date_F": "2021-04-15",
  "A_ns_CDend": "9999-12-31",
  "A_ns_CDemail": "committee@parliament.bg",
  "A_ns_CDroom": "Room 356",
  "A_ns_CDphone": "+359 2 xxx xxxx",
  "A_ns_CDrules": "Committee rules and regulations text",
  "colListMP": [
    {
      "A_ns_MP_id": 12345,
      "A_ns_MP_PosL_value": "председател",
      "A_ns_MSP_date_F": "2021-04-15",
      "A_ns_MSP_date_T": "9999-12-31"
    }
  ]
}
```

**Field Descriptions**:
- `A_ns_CDemail`: Committee email address
- `A_ns_CDroom`: Committee room number
- `A_ns_CDphone`: Committee phone number
- `A_ns_CDrules`: Committee rules text
- `colListMP`: Array of committee members
  - `A_ns_MP_PosL_value`: Position (председател, зам.-председател, член)
  - `A_ns_MSP_date_F/T`: Member term start/end dates

---

## 📄 Bills API

### Get Bills by Committee

**Endpoint**: `GET /com-acts/bg/{committee_id}/1`

```bash
curl "https://www.parliament.bg/api/v1/com-acts/bg/3613/1"
```

**Response Structure**:
```json
[
  {
    "L_Act_id": 98765,
    "L_ActL_title": "Bill Title in Bulgarian",
    "L_Act_sign": "51-554-01-114",
    "L_Act_date": "2024-01-15",
    "path": "/category/subcategory",
    "L_SesL_value": "51st Parliamentary Session"
  }
]
```

**Field Descriptions**:
- `L_Act_id`: Unique bill identifier
- `L_ActL_title`: Bill title in Bulgarian
- `L_Act_sign`: Official bill signature/number
- `L_Act_date`: Bill submission date
- `path`: Bill category path
- `L_SesL_value`: Parliamentary session

### Get Detailed Bill Information

**Endpoint**: `GET /bill/{bill_id}`

```bash
curl "https://www.parliament.bg/api/v1/bill/98765"
```

**Response Structure**:
```json
{
  "L_Act_sign": "51-554-01-114",
  "L_SesL_value": "51st Parliamentary Session",
  "A_ns_folder": "51",
  "withdrawn": false,
  "imp_list": [
    {
      "A_ns_MPL_Name1": "FirstName",
      "A_ns_MPL_Name2": "MiddleName", 
      "A_ns_MPL_Name3": "LastName",
      "A_ns_CL_value_short": "Party"
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
      "FILENAME": "bill-document.pdf",
      "FILEPATH": "/bills/51/",
      "FILESIZE": "1.2 MB"
    }
  ]
}
```

**Field Descriptions**:
- `withdrawn`: Whether bill was withdrawn
- `imp_list`: Array of bill submitters
- `dist_list`: Array of committees handling the bill
- `file_list`: Array of associated documents

---

## 📜 Transcripts API

### Get Transcript List for Period

**Endpoint**: `GET /archive-period/bg/A_Cm_Steno/{year}/{month}/{committee_id}/0`

```bash
curl "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/2024/8/3613/0"
```

**Response Structure**:
```json
[
  {
    "t_id": 67890,
    "t_label": "Meeting Type Label",
    "t_date": "2024-08-15",
    "t_time": "10:00",
    "t_status": "published"
  }
]
```

**Field Descriptions**:
- `t_id`: Unique transcript identifier
- `t_label`: Type of meeting/transcript
- `t_date`: Meeting date
- `t_time`: Meeting start time
- `t_status`: Publication status

### Get Transcript Content

**Endpoint**: `GET /com-steno/bg/{transcript_id}`

```bash
curl "https://www.parliament.bg/api/v1/com-steno/bg/67890"
```

**Response Structure**:
```json
{
  "A_Cm_St_text": "<html>Full transcript HTML content with speakers and discussions</html>",
  "A_Cm_St_date": "2024-08-15",
  "A_Cm_St_sub": "Meeting Type",
  "A_Cm_Stid": 67890,
  "A_ns_C_id": 3613,
  "acts": [
    {
      "act_id": 123,
      "act_title": "Related Act Title"
    }
  ]
}
```

**Field Descriptions**:
- `A_Cm_St_text`: Full HTML content of transcript
- `A_Cm_St_date`: Meeting date
- `A_Cm_St_sub`: Meeting subject/type
- `A_Cm_Stid`: Transcript ID
- `A_ns_C_id`: Committee ID
- `acts`: Array of related acts/bills discussed

---

## 🎥 Meeting Videos API

### Get Meeting List for Period

**Endpoint**: `GET /archive-period/bg/A_Cm_Sit/{year}/{month}/{committee_id}/0`

```bash
curl "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/2025/7/3613/0"
```

**Response Structure**:
```json
[
  {
    "t_id": 13565,
    "t_label": "Committee Meeting",
    "t_date": "2025-07-03", 
    "t_time": "14:45"
  }
]
```

**Field Descriptions**:
- `t_id`: Meeting identifier (use for detailed meeting data)
- `t_label`: Meeting type
- `t_date`: Meeting date
- `t_time`: Meeting time

### Get Meeting Details with Video URLs

**Endpoint**: `GET /com-meeting/bg/{meeting_id}`

```bash
curl "https://www.parliament.bg/api/v1/com-meeting/bg/13565"
```

**Response Structure**:
```json
{
  "A_Cm_Sitid": 13565,
  "A_ns_C_id": 3613,
  "A_Cm_Sit_date": "2025-07-03 14:45:00",
  "A_Cm_Sit_room": "356",
  "A_Cm_Sit_body": "Meeting agenda and details",
  "video": {
    "Vidate": "2025-07-03",
    "Vicount": 1,
    "Vifile": "autorecord/2025-07-03/20250703-17-21258-3613_",
    "videoArchive": 1,
    "default": "/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part1.mp4",
    "playlist": [
      {
        "item": 1,
        "file": "/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part1.mp4"
      }
    ]
  }
}
```

**Field Descriptions**:
- `A_Cm_Sitid`: Meeting ID
- `A_ns_C_id`: Committee ID
- `A_Cm_Sit_date`: Meeting date and time
- `A_Cm_Sit_room`: Meeting room
- `video.Vidate`: Video recording date
- `video.Vicount`: Number of video parts
- `video.Vifile`: Video file prefix
- `video.default`: Default video URL
- `video.playlist`: Array of video files

---

## 🎬 Video File URL Patterns

### Multi-part Video Files

**Pattern**: `/Gallery/videoCW/{vifile}Part{i}.mp4`

```bash
# Example URLs
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part1.mp4
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part2.mp4
```

### Auto-recorded Video Files

**Pattern**: `/Gallery/videoCW/autorecord/{date}/{formatted_date}-{meeting_id}-{committee_id}_Part{i}.mp4`

```bash
# Example URL structure
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part1.mp4
```

**URL Components**:
- `date`: Meeting date (YYYY-MM-DD)
- `formatted_date`: Date without separators (YYYYMMDD)
- `meeting_id`: Unique meeting identifier
- `committee_id`: Committee identifier
- `Part{i}`: Video part number

---

## 📋 PDF Documents API

### Bill PDF Documents

**Pattern**: `/bills/{session}/{signature}.pdf`

```bash
# Example URL
https://www.parliament.bg/bills/51/51-554-01-114.pdf
```

**URL Components**:
- `session`: Parliamentary session folder
- `signature`: Bill signature/number

---

## ⚠️ Rate Limiting & Best Practices

### Recommended Practices

1. **Timeout Settings**: Use 30-60 second timeouts for API calls
2. **Error Handling**: Always check HTTP status codes
3. **Data Validation**: Verify required fields exist in responses
4. **Retry Logic**: Implement exponential backoff for failed requests
5. **Caching**: Store frequently accessed data locally

### Example Implementation

```php
// Laravel HTTP Client example
$response = Http::timeout(60)
    ->retry(3, 1000) // 3 retries with 1 second delay
    ->get("https://www.parliament.bg/api/v1/coll-list-ns/bg");

if ($response->successful()) {
    $data = $response->json();
    // Process data
} else {
    // Handle error
    logger()->error("API request failed", [
        'url' => $response->effectiveUri(),
        'status' => $response->status(),
        'body' => $response->body()
    ]);
}
```

---

## 🔍 Common Response Codes

### Success Codes
- **200 OK**: Request successful, data returned
- **304 Not Modified**: Data hasn't changed (if using caching headers)

### Error Codes  
- **404 Not Found**: Resource doesn't exist (invalid ID)
- **500 Internal Server Error**: Server-side error
- **503 Service Unavailable**: Server temporarily unavailable

---

## 📊 Data Formats & Types

### Date Formats
- **API Dates**: `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS`
- **Video Dates**: `YYYY-MM-DD` in URLs, `YYYYMMDD` in filenames

### Text Encoding
- **Character Set**: UTF-8
- **Language**: Bulgarian (Cyrillic script)
- **HTML Content**: Transcripts contain HTML markup

### Null Values
- Missing fields may be `null` or omitted entirely
- Always check field existence before accessing

---

## 🛠️ Testing APIs

### Using cURL

```bash
# Test basic connectivity
curl -I "https://www.parliament.bg/api/v1/coll-list-ns/bg"

# Get formatted JSON response
curl -s "https://www.parliament.bg/api/v1/coll-list/bg/3" | jq '.'

# Test specific committee
curl -s "https://www.parliament.bg/api/v1/coll-list-mp/bg/3613/3?date=" | jq '.A_ns_CL_value'
```

### Using Browser Developer Tools

1. Open browser developer tools (F12)
2. Go to Network tab
3. Visit parliament.bg committee pages
4. Filter by XHR requests to see API calls
5. Copy request URLs for testing

---

## 📈 Usage Examples in Parliament Scraper

### Committee Processing Example

```php
// Get all committees
$committees = Http::get('https://www.parliament.bg/api/v1/coll-list/bg/3')->json();

foreach ($committees as $committee) {
    $committeeId = $committee['A_ns_C_id'];
    
    // Get detailed committee info with members
    $details = Http::get("https://www.parliament.bg/api/v1/coll-list-mp/bg/{$committeeId}/3?date=")->json();
    
    // Process members
    foreach ($details['colListMP'] as $member) {
        $memberId = $member['A_ns_MP_id'];
        $position = $member['A_ns_MP_PosL_value'];
        // Store relationship
    }
}
```

### Video Discovery Example

```php
// Find meetings with videos for a committee
$year = 2025;
$month = 7;
$committeeId = 3613;

$meetings = Http::get("https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/{$year}/{$month}/{$committeeId}/0")->json();

foreach ($meetings as $meeting) {
    $meetingId = $meeting['t_id'];
    
    // Get meeting details with video URLs
    $meetingData = Http::get("https://www.parliament.bg/api/v1/com-meeting/bg/{$meetingId}")->json();
    
    if (isset($meetingData['video'])) {
        $videoUrl = 'https://www.parliament.bg' . $meetingData['video']['default'];
        // Process video for transcription
    }
}
```