# Admin Live Streaming Dashboard Documentation

## Overview

The admin live streaming dashboard provides comprehensive management and monitoring capabilities for all live streams on the platform. All demo data has been removed and replaced with real-time database integration.

## Features

### 1. Dashboard Analytics

Real-time statistics displayed at the top of the dashboard:

- **Total Streams**: Total count of all streams (live, scheduled, ended, cancelled)
- **Live Now**: Current number of active live streams
- **Current Viewers**: Total viewers across all active streams
- **Stream Revenue**: Total revenue generated from all stream orders

### 2. Stream Performance Metrics

- **Scheduled**: Count of upcoming scheduled streams
- **Completed**: Count of successfully completed streams
- **Cancelled**: Count of cancelled streams
- **Average Revenue Per Stream**: Calculated from total revenue divided by total streams

### 3. Streaming Control Panel

#### Filters
- **Search**: Search streams by title or vendor name
- **Status Filter**: Filter by stream status (Live, Scheduled, Ended, Cancelled)
- **Vendor Filter**: Filter streams by specific vendor
- **Date Filter**: Filter streams by scheduled date
- **Refresh**: Manually refresh stream data

#### Stream Actions
- **Start Stream**: Manually start a scheduled stream
- **Pause Stream**: Temporarily pause a live stream
- **Stop Stream**: End a live stream (marks viewers as inactive)
- **Delete Stream**: Remove a non-live stream from the database
- **View Details**: View comprehensive stream information
- **Chat Moderation**: Access chat moderation tools
- **Edit Schedule**: Modify scheduled stream details

### 4. Quick Actions

#### Generate Stream Key
- Select a vendor from the dropdown
- Click "Generate Key" to create a unique, secure stream key
- Copy the generated key to clipboard
- Stream keys are stored in `vendor_settings` table

#### View Analytics
- Basic analytics display with:
  - Total streams count
  - Total revenue
  - Average revenue per stream
- Placeholder for future detailed analytics

#### Moderation Tools
- Select a stream to view comments
- View all user comments with username and timestamp
- Delete inappropriate comments
- Ban users from specific streams

#### RTMP Settings
- Configure RTMP server URL
- Set server authentication key
- Configure stream quality settings:
  - Max bitrate (kbps)
  - Max resolution (1080p, 720p, 480p)
  - Max stream duration
  - Enable/disable automatic recording

### 5. Top Performing Streams

Displays the top 3 streams by revenue with:
- Stream title
- Vendor name
- Peak viewer count
- Total revenue

### 6. Export Data

Export all stream data to CSV format including:
- Stream ID and title
- Vendor name
- Status
- Viewer counts (current and peak)
- Revenue and order counts
- Interaction metrics (likes, comments)
- Timestamps (scheduled, started, ended)

## API Endpoints

### `/api/admin/streams/list.php`
**Method**: GET  
**Purpose**: Fetch filtered and searched streams  
**Parameters**:
- `search` (optional): Search term for title or vendor
- `status` (optional): Filter by status
- `vendor_id` (optional): Filter by vendor ID
- `date` (optional): Filter by scheduled date

### `/api/admin/streams/stats.php`
**Method**: GET  
**Purpose**: Get real-time dashboard statistics  
**Returns**: All dashboard metrics

### `/api/admin/streams/control.php`
**Method**: POST  
**Purpose**: Control stream state  
**Actions**: `start`, `pause`, `stop`, `delete`, `cancel`  
**Body**: 
```json
{
  "action": "start",
  "stream_id": 123
}
```

### `/api/admin/streams/schedule.php`
**Method**: POST  
**Purpose**: Schedule a new stream  
**Body**:
```json
{
  "vendor_id": 1,
  "title": "Stream Title",
  "description": "Stream description",
  "scheduled_at": "2024-03-20 14:00:00"
}
```

### `/api/admin/streams/stream-key.php`
**Method**: POST  
**Purpose**: Generate unique stream key for vendor  
**Body**:
```json
{
  "vendor_id": 1
}
```

### `/api/admin/streams/export.php`
**Method**: GET  
**Purpose**: Export stream data as CSV  
**Returns**: CSV file download

### `/api/admin/streams/moderation.php`
**Method**: GET/POST  
**Purpose**: Chat moderation  
**Actions**: `get_comments`, `delete_comment`, `ban_user`

### `/api/admin/streams/settings.php`
**Method**: GET/POST  
**Purpose**: Manage RTMP and stream settings  
**Actions**: `get`, `update`

### `/api/admin/streams/vendors.php`
**Method**: GET  
**Purpose**: Get list of all vendors with stream counts

## Database Tables Used

### Primary Tables
- `live_streams`: Main stream data
- `stream_viewers`: Active viewer tracking
- `stream_orders`: Purchase tracking
- `stream_interactions`: Likes, dislikes, comments
- `vendors`: Vendor information
- `vendor_settings`: Stream keys and vendor preferences
- `system_settings`: RTMP configuration

## Security

- All API endpoints require admin authentication
- Session-based authentication check: `$_SESSION['user_role'] === 'admin'`
- CSRF protection recommended for form submissions
- SQL injection protection via prepared statements
- XSS protection via proper HTML escaping

## Auto-Refresh

- Statistics auto-refresh every 30 seconds (when page is visible)
- Manual refresh available via Refresh button
- Real-time updates for live viewer counts

## Empty States

Graceful handling of empty data:
- No streams: Friendly message with call-to-action
- No comments: Appropriate empty state message
- No top performers: Shows "No data available" message

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Bootstrap 5 modal system
- Fetch API for AJAX requests
- ES6+ JavaScript features

## Future Enhancements

- Real-time WebSocket updates for live viewer counts
- Advanced analytics with charts and graphs
- Stream scheduling calendar view
- Automated stream alerts and notifications
- Stream quality monitoring
- Multi-language support
- User banning system with database table
- Stream preview/thumbnail generation
- Automated RTMP server health checks
