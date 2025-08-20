---
layout: page
title: Examples
permalink: /examples/
---

# API Examples

Real-world examples of using the parliament.bg APIs with actual data structures and response formats.

## 🏛️ Parliament Members Examples

### Fetching All Members

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/coll-list-ns/bg"
```

**Sample Response**:
```json
{
  "colListMP": [
    {
      "A_ns_MP_id": 21234,
      "A_ns_MPL_Name1": "Димитър",
      "A_ns_MPL_Name2": "Петров", 
      "A_ns_MPL_Name3": "Иванов",
      "A_ns_Va_name": "МИР 24 - София",
      "A_ns_CL_value_short": "ГЕРБ-СДС"
    },
    {
      "A_ns_MP_id": 21235,
      "A_ns_MPL_Name1": "Мария",
      "A_ns_MPL_Name2": "Георгиева",
      "A_ns_MPL_Name3": "Петрова", 
      "A_ns_Va_name": "МИР 25 - Пловдив",
      "A_ns_CL_value_short": "ПП-ДБ"
    }
  ]
}
```

### Member Profile Details

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/mp-profile/bg/21234"
```

**Sample Response**:
```json
{
  "A_ns_MP_Email": "d.ivanov@parliament.bg",
  "A_ns_MP_Phone": "+359 2 939 3141",
  "A_ns_MP_Fax": "+359 2 981 8469",
  "prsList": [
    {
      "A_ns_MP_Pr_TL_value": "инженер"
    },
    {
      "A_ns_MP_Pr_TL_value": "икономист"
    }
  ]
}
```

---

## 🏢 Committee Examples

### Transport Committee (3613)

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/coll-list-mp/bg/3613/3?date="
```

**Sample Response**:
```json
{
  "A_ns_C_id": 3613,
  "A_ns_CT_id": 3,
  "A_ns_CL_value": "Комисия по транспорт и съобщения",
  "A_ns_C_active_count": 13,
  "A_ns_C_date_F": "2021-04-15",
  "A_ns_CDend": "9999-12-31",
  "A_ns_CDemail": "transport@parliament.bg",
  "A_ns_CDroom": "356",
  "A_ns_CDphone": "+359 2 939 3356",
  "A_ns_CDrules": "Комисията по транспорт и съобщения разглежда...",
  "colListMP": [
    {
      "A_ns_MP_id": 21234,
      "A_ns_MP_PosL_value": "председател",
      "A_ns_MSP_date_F": "2021-04-15",
      "A_ns_MSP_date_T": "9999-12-31"
    },
    {
      "A_ns_MP_id": 21235, 
      "A_ns_MP_PosL_value": "зам.-председател",
      "A_ns_MSP_date_F": "2021-04-15",
      "A_ns_MSP_date_T": "9999-12-31"
    },
    {
      "A_ns_MP_id": 21236,
      "A_ns_MP_PosL_value": "член",
      "A_ns_MSP_date_F": "2021-05-01", 
      "A_ns_MSP_date_T": "9999-12-31"
    }
  ]
}
```

### All Committees List

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/coll-list/bg/3"
```

**Sample Response**:
```json
[
  {
    "A_ns_C_id": 3595,
    "A_ns_CT_id": 3,
    "A_ns_CL_value": "Комисия по правни въпроси",
    "A_ns_C_active_count": 15,
    "A_ns_C_date_F": "2021-04-15",
    "A_ns_CDend": "9999-12-31"
  },
  {
    "A_ns_C_id": 3613,
    "A_ns_CT_id": 3, 
    "A_ns_CL_value": "Комисия по транспорт и съобщения",
    "A_ns_C_active_count": 13,
    "A_ns_C_date_F": "2021-04-15",
    "A_ns_CDend": "9999-12-31"
  }
]
```

---

## 📄 Bills Examples

### Bills by Transport Committee

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/com-acts/bg/3613/1"
```

**Sample Response**:
```json
[
  {
    "L_Act_id": 98765,
    "L_ActL_title": "Закон за изменение и допълнение на Закона за автомобилните превози",
    "L_Act_sign": "51-554-01-114",
    "L_Act_date": "2024-03-15",
    "path": "/transport/automotive",
    "L_SesL_value": "51-то НС"
  },
  {
    "L_Act_id": 98766,
    "L_ActL_title": "Закон за железопътния транспорт",
    "L_Act_sign": "51-555-02-115", 
    "L_Act_date": "2024-04-20",
    "path": "/transport/railway",
    "L_SesL_value": "51-то НС"
  }
]
```

### Detailed Bill Information

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/bill/98765"
```

**Sample Response**:
```json
{
  "L_Act_sign": "51-554-01-114",
  "L_SesL_value": "51-то НС",
  "A_ns_folder": "51",
  "withdrawn": false,
  "imp_list": [
    {
      "A_ns_MPL_Name1": "Димитър",
      "A_ns_MPL_Name2": "Петров",
      "A_ns_MPL_Name3": "Иванов",
      "A_ns_CL_value_short": "ГЕРБ-СДС"
    },
    {
      "A_ns_MPL_Name1": "Мария", 
      "A_ns_MPL_Name2": "Георгиева",
      "A_ns_MPL_Name3": "Петрова",
      "A_ns_CL_value_short": "ПП-ДБ"
    }
  ],
  "dist_list": [
    {
      "A_ns_CL_value": "Комисия по транспорт и съобщения",
      "L_Act_DTL_value": "водеща"
    },
    {
      "A_ns_CL_value": "Комисия по правни въпроси", 
      "L_Act_DTL_value": "становище"
    }
  ],
  "file_list": [
    {
      "FILENAME": "51-554-01-114.pdf",
      "FILEPATH": "/bills/51/",
      "FILESIZE": "847 KB"
    }
  ]
}
```

---

## 📜 Transcript Examples

### July 2025 Transport Committee Meetings

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Steno/2025/7/3613/0"
```

**Sample Response**:
```json
[
  {
    "t_id": 67890,
    "t_label": "заседание", 
    "t_date": "2025-07-03",
    "t_time": "14:45",
    "t_status": "published"
  },
  {
    "t_id": 67891,
    "t_label": "заседание",
    "t_date": "2025-07-17", 
    "t_time": "14:30",
    "t_status": "published"
  },
  {
    "t_id": 67892,
    "t_label": "заседание",
    "t_date": "2025-07-31",
    "t_time": "14:30", 
    "t_status": "published"
  }
]
```

### Transcript Content

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/com-steno/bg/67890"
```

**Sample Response**:
```json
{
  "A_Cm_St_text": "<div class=\"stenogram\"><p><strong>ПРЕДСЕДАТЕЛ ДИМИТЪР ИВАНОВ:</strong> Уважаеми колеги, откривам заседанието на Комисията по транспорт и съобщения.</p><p>Днес разглеждаме законопроекта за изменение и допълнение на Закона за автомобилните превози.</p><p><strong>МАРИЯ ПЕТРОВА:</strong> Г-н председател, имам въпрос относно член 15 от законопроекта...</p></div>",
  "A_Cm_St_date": "2025-07-03",
  "A_Cm_St_sub": "заседание",
  "A_Cm_Stid": 67890,
  "A_ns_C_id": 3613,
  "acts": [
    {
      "act_id": 98765,
      "act_title": "Закон за изменение и допълнение на Закона за автомобилните превози"
    }
  ]
}
```

---

## 🎥 Video Meeting Examples

### July 2025 Video Meetings

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/2025/7/3613/0"
```

**Sample Response**:
```json
[
  {
    "t_id": 13565,
    "t_label": "заседание",
    "t_date": "2025-07-03",
    "t_time": "14:45"
  },
  {
    "t_id": 13605, 
    "t_label": "заседание",
    "t_date": "2025-07-17",
    "t_time": "14:30"
  },
  {
    "t_id": 13640,
    "t_label": "заседание", 
    "t_date": "2025-07-31",
    "t_time": "14:30"
  }
]
```

### Meeting Video Details

**Request**:
```bash
curl "https://www.parliament.bg/api/v1/com-meeting/bg/13565"
```

**Sample Response**:
```json
{
  "A_ns_CL_value_short": "Транспорт",
  "A_ns_C_id": "3613",
  "A_Cm_SitPL_value": "пл. \"Княз Александъp I\" №1",
  "A_Cm_Sitid": "13565",
  "A_Cm_Sit_date": "2025-07-03 14:45:00",
  "A_Cm_Sit_room": "356",
  "A_Cm_Sit_body": "Дневен ред:\n1. Разглеждане на законопроект...\n2. Обсъждане на предложения...",
  "media": [],
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
  },
  "videoArchive": 1
}
```

### Multi-part Video Example

**Sample Response with Multiple Parts**:
```json
{
  "A_Cm_Sitid": "13075", 
  "A_ns_C_id": "3613",
  "A_Cm_Sit_date": "2025-04-16 10:00:00",
  "video": {
    "Vidate": "2025-04-16",
    "Vicount": 3,
    "Vifile": "autorecord/2025-04-16/20250416-10-20717-3613_",
    "videoArchive": 1,
    "default": "/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part1.mp4",
    "playlist": [
      {
        "item": 1,
        "file": "/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part1.mp4"
      },
      {
        "item": 2, 
        "file": "/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part2.mp4"
      },
      {
        "item": 3,
        "file": "/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part3.mp4"
      }
    ]
  }
}
```

---

## 🔗 Full Video URL Examples

### Direct Video URLs

Based on the meeting data above, the complete video URLs would be:

```bash
# Single part video
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-07-03/20250703-17-21258-3613_Part1.mp4

# Multi-part video series
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part1.mp4
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part2.mp4  
https://www.parliament.bg/Gallery/videoCW/autorecord/2025-04-16/20250416-10-20717-3613_Part3.mp4
```

### URL Pattern Analysis

```
https://www.parliament.bg/Gallery/videoCW/autorecord/{date}/{formatted_date}-{hour}-{meeting_id}-{committee_id}_Part{part}.mp4
```

**Components**:
- `date`: Meeting date (YYYY-MM-DD)
- `formatted_date`: Date without separators (YYYYMMDD)  
- `hour`: Meeting start hour (10, 14, 17, etc.)
- `meeting_id`: Unique meeting identifier (13565, 13075, etc.)
- `committee_id`: Committee identifier (3613)
- `part`: Video part number (1, 2, 3, etc.)

---

## 📋 PDF Document Examples

### Bill PDF URLs

Based on bill data, PDF documents follow this pattern:

```bash
# Transport committee bill
https://www.parliament.bg/bills/51/51-554-01-114.pdf

# Another bill example  
https://www.parliament.bg/bills/51/51-555-02-115.pdf
```

### URL Structure

```
https://www.parliament.bg/bills/{session}/{bill_signature}.pdf
```

**Components**:
- `session`: Parliamentary session number (51)
- `bill_signature`: Full bill signature (51-554-01-114)

---

## 🔍 Search and Filter Examples

### Finding Active Committees

```bash
# Get all committees and filter for active ones
curl "https://www.parliament.bg/api/v1/coll-list/bg/3" | jq '.[] | select(.A_ns_CDend == "9999-12-31")'
```

### Finding Recent Bills

```bash
# Get bills and filter by date
curl "https://www.parliament.bg/api/v1/com-acts/bg/3613/1" | jq '.[] | select(.L_Act_date >= "2024-01-01")'
```

### Committee Chairs

```bash
# Get committee details and extract chairman
curl "https://www.parliament.bg/api/v1/coll-list-mp/bg/3613/3?date=" | jq '.colListMP[] | select(.A_ns_MP_PosL_value == "председател")'
```

---

## 🛠️ Programming Examples

### PHP Laravel Implementation

```php
use Illuminate\Support\Facades\Http;

// Get all committees
$committees = Http::get('https://www.parliament.bg/api/v1/coll-list/bg/3')->json();

foreach ($committees as $committee) {
    $committeeId = $committee['A_ns_C_id'];
    $committeeName = $committee['A_ns_CL_value'];
    
    echo "Processing committee: {$committeeName} (ID: {$committeeId})\n";
    
    // Get meetings with videos for July 2025
    $meetings = Http::get("https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/2025/7/{$committeeId}/0")->json();
    
    foreach ($meetings as $meeting) {
        $meetingId = $meeting['t_id'];
        
        // Get meeting details
        $meetingData = Http::get("https://www.parliament.bg/api/v1/com-meeting/bg/{$meetingId}")->json();
        
        if (isset($meetingData['video'])) {
            $videoUrl = 'https://www.parliament.bg' . $meetingData['video']['default'];
            echo "Found video: {$videoUrl}\n";
        }
    }
}
```

### Python Implementation

```python
import requests
import json

# Get committee details
def get_committee_info(committee_id):
    url = f"https://www.parliament.bg/api/v1/coll-list-mp/bg/{committee_id}/3?date="
    response = requests.get(url)
    
    if response.status_code == 200:
        data = response.json()
        return {
            'name': data['A_ns_CL_value'],
            'members': len(data['colListMP']),
            'chairman': next((member for member in data['colListMP'] 
                            if member['A_ns_MP_PosL_value'] == 'председател'), None)
        }
    return None

# Example usage
committee_info = get_committee_info(3613)
print(f"Committee: {committee_info['name']}")
print(f"Members: {committee_info['members']}")
if committee_info['chairman']:
    print(f"Chairman ID: {committee_info['chairman']['A_ns_MP_id']}")
```

### JavaScript/Node.js Implementation

```javascript
const axios = require('axios');

async function getCommitteeVideos(committeeId, year, month) {
    try {
        // Get meetings for the period
        const meetingsUrl = `https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/${year}/${month}/${committeeId}/0`;
        const meetingsResponse = await axios.get(meetingsUrl);
        
        const videos = [];
        
        for (const meeting of meetingsResponse.data) {
            // Get meeting details
            const meetingUrl = `https://www.parliament.bg/api/v1/com-meeting/bg/${meeting.t_id}`;
            const meetingResponse = await axios.get(meetingUrl);
            
            if (meetingResponse.data.video) {
                videos.push({
                    meetingId: meeting.t_id,
                    date: meeting.t_date,
                    videoUrl: 'https://www.parliament.bg' + meetingResponse.data.video.default
                });
            }
        }
        
        return videos;
    } catch (error) {
        console.error('Error fetching videos:', error);
        return [];
    }
}

// Example usage
getCommitteeVideos(3613, 2025, 7).then(videos => {
    console.log(`Found ${videos.length} videos:`);
    videos.forEach(video => {
        console.log(`${video.date}: ${video.videoUrl}`);
    });
});
```

---

## 📊 Data Analysis Examples

### Committee Activity Analysis

```bash
# Get meeting counts by committee for July 2025
for committee_id in 3595 3613 3614 3615; do
    count=$(curl -s "https://www.parliament.bg/api/v1/archive-period/bg/A_Cm_Sit/2025/7/${committee_id}/0" | jq 'length')
    echo "Committee ${committee_id}: ${count} meetings"
done
```

### Video Availability Check

```bash
# Check which meetings have videos
meeting_id=13565
meeting_data=$(curl -s "https://www.parliament.bg/api/v1/com-meeting/bg/${meeting_id}")
has_video=$(echo $meeting_data | jq 'has("video")')
echo "Meeting ${meeting_id} has video: ${has_video}"
```

### Bill Submission Trends

```bash
# Get bills by date for analysis
curl -s "https://www.parliament.bg/api/v1/com-acts/bg/3613/1" | jq '.[] | {date: .L_Act_date, title: .L_ActL_title}' | jq -s 'sort_by(.date)'
```