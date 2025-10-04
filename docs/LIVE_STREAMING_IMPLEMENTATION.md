# Live Streaming Enhancement Implementation

## Overview
This implementation adds comprehensive interactive features to the FezaMarket Live streaming platform, including real-time statistics, viewer interactions, and post-stream management options.

## Database Schema

### Tables Created

#### 1. `saved_streams`
Stores metadata for streams that sellers choose to save after ending.

```sql
CREATE TABLE saved_streams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stream_id INT NOT NULL,
    vendor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(255),
    duration INT NOT NULL COMMENT 'Duration in seconds',
    viewer_count INT NOT NULL DEFAULT 0,
    total_revenue DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    streamed_at TIMESTAMP NOT NULL,
    saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);
```

#### 2. `stream_interactions`
Tracks likes, dislikes, and comments on live streams.

```sql
CREATE TABLE stream_interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stream_id INT NOT NULL,
    user_id INT,
    interaction_type ENUM('like', 'dislike', 'comment') NOT NULL,
    comment_text TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_like_dislike (stream_id, user_id, interaction_type)
);
```

#### 3. `stream_orders`
Tracks purchases made during live streams.

```sql
CREATE TABLE stream_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stream_id INT NOT NULL,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    vendor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES live_streams(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);
```

## Model Classes

### LiveStream
Main class for managing live streams.

**Key Methods:**
- `getActiveStreams($limit)` - Get all currently active streams
- `getStreamById($streamId)` - Get details of a specific stream
- `createStream($vendorId, $data)` - Create a new stream
- `startStream($streamId)` - Mark stream as live
- `endStream($streamId)` - Mark stream as ended
- `updateViewerCount($streamId, $count)` - Update current viewer count
- `getStreamStats($streamId)` - Get comprehensive statistics
- `getStreamProducts($streamId)` - Get products featured in stream

### SavedStream
Manages saved stream videos.

**Key Methods:**
- `saveStream($streamData)` - Save a stream for on-demand viewing
- `getVendorSavedStreams($vendorId, $limit)` - Get vendor's saved streams
- `getSavedStreamById($id)` - Get specific saved stream
- `deleteSavedStream($id)` - Delete a saved stream

### StreamInteraction
Handles user interactions (likes, dislikes, comments).

**Key Methods:**
- `addInteraction($streamId, $userId, $type, $commentText)` - Add new interaction
- `removeInteraction($streamId, $userId, $type)` - Remove interaction
- `getStreamComments($streamId, $limit)` - Get comments for a stream
- `getUserInteraction($streamId, $userId)` - Get user's current interaction type

### StreamViewer
Tracks stream viewers.

**Key Methods:**
- `addViewer($streamId, $userId, $sessionId, $ipAddress, $userAgent)` - Track new viewer
- `markViewerLeft($viewerId)` - Mark viewer as no longer watching
- `getActiveViewers($streamId)` - Get list of active viewers
- `cleanupInactiveViewers($streamId, $inactiveMinutes)` - Remove stale viewer records

### StreamOrder
Tracks orders placed during streams.

**Key Methods:**
- `recordStreamOrder($streamId, $orderId, $productId, $vendorId, $amount)` - Record new order
- `getStreamOrders($streamId)` - Get all orders for a stream

## API Endpoints

### 1. `/api/live/streams.php`
**Purpose:** Fetch active live streams for public display

**Actions:**
- `list` (GET) - Get all active streams
  - Parameters: `limit` (optional, default: 10)
  - Response: Array of active streams with viewer counts
  
- `get` (GET) - Get detailed stream information
  - Parameters: `stream_id` (required)
  - Response: Stream details, stats, and products
  
- `products` (GET) - Get products for a specific stream
  - Parameters: `stream_id` (required)
  - Response: Array of products with special pricing

### 2. `/api/live/interact.php`
**Purpose:** Handle user interactions (requires authentication)

**Actions:**
- `like` (POST) - Like a stream
- `dislike` (POST) - Dislike a stream
- `unlike` (POST) - Remove like
- `undislike` (POST) - Remove dislike
- `comment` (POST) - Post a comment
  - Parameters: `comment` (required)
- `get_comments` (POST) - Get stream comments
  - Parameters: `limit` (optional, default: 100)
- `get_user_interaction` (POST) - Get user's current interaction

**Authentication:** Returns 401 with redirect URL if not logged in

### 3. `/api/live/stats.php`
**Purpose:** Real-time stream statistics for sellers (requires vendor authentication)

**Parameters:** `stream_id` (required)

**Response:**
```json
{
  "success": true,
  "stats": {
    "likes": 42,
    "dislikes": 3,
    "comments": 156,
    "viewers": 234,
    "current_viewers": 187,
    "orders": 23,
    "revenue": 1847.50,
    "duration": 1845
  },
  "viewers": [...],
  "comments": [...],
  "orders": [...]
}
```

### 4. `/api/live/end-stream.php`
**Purpose:** End a stream with save/delete option (requires vendor authentication)

**Parameters:**
- `stream_id` (required)
- `action` (required) - Either "save" or "delete"
- `video_url` (optional) - URL to saved video file

**Response:**
```json
{
  "success": true,
  "message": "Stream ended and saved successfully",
  "action": "saved",
  "stats": {
    "duration": 1845,
    "viewers": 234,
    "revenue": 1847.50,
    "likes": 42,
    "comments": 156,
    "orders": 23
  }
}
```

### 5. `/api/live/viewers.php`
**Purpose:** Track and retrieve viewer information

**Actions:**
- `join` (POST) - Add viewer to stream (public)
  - Response: `viewer_id` and `viewer_count`
  
- `leave` (POST) - Mark viewer as left (public)
  - Parameters: `viewer_id` (required)
  
- `list` (GET/POST) - Get active viewers (requires authentication)
  - Response: Array of viewers with usernames
  
- `count` (GET) - Get current viewer count (public)
  - Response: Current count

## Frontend Implementation

### Public Live Page (`/live.php`)

**Features:**
1. **Dynamic Stream Loading**: Fetches active streams from database
2. **Viewer Tracking**: Automatically joins/leaves streams
3. **Interactive Buttons**: Like, dislike, and comment with login prompts
4. **Purchase Integration**: "Buy Now" and "Add to Cart" buttons enabled
5. **Real-time Updates**: Viewer count and comments refresh every 10 seconds

**Key Functions:**
- `joinStream(streamId)` - Join stream as viewer
- `leaveStream(streamId, viewerId)` - Leave stream
- `handleLike(button)` - Like with login check
- `handleDislike(button)` - Dislike with login check
- `sendMessage(streamId)` - Post comment
- `loadComments(streamId)` - Refresh comments
- `updateViewerCount(streamId)` - Update viewer display

### Seller Dashboard (`/seller/stream-interface.php`)

**Features:**
1. **Comprehensive Stats Display**:
   - Current viewers count
   - Likes and dislikes
   - Total comments
   - Orders placed
   - Revenue generated
   - Stream duration

2. **Real-time Feeds**:
   - Active viewers list with usernames
   - Live comments feed with timestamps
   - Orders tracker with product details

3. **End Stream Modal**:
   - Shows final statistics
   - Option to save stream for on-demand viewing
   - Option to delete stream permanently

**Key Functions:**
- `startStreaming()` - Start the stream and begin stats tracking
- `updateStreamStats()` - Fetch latest statistics (every 5 seconds)
- `showEndStreamModal()` - Display end stream options
- `endStreamWithAction(action)` - Save or delete stream
- `formatDuration(seconds)` - Format time display
- `formatTimestamp(timestamp)` - Format relative time

## Integration Notes

### Purchase Flow Integration
The implementation integrates with the existing `purchase-flows.js` library for product interactions:
- `buyNow(productId)` - Direct checkout
- `addToCart(productId)` - Add to cart
Both functions handle login requirements automatically.

### Login Requirement Flow
1. User clicks interaction button (like, comment, etc.)
2. If not logged in, redirect to `/login.php?return=/live.php`
3. After login, user returns to live page
4. Interaction is automatically applied

## Migration Instructions

1. Run the migration to create new tables:
   ```bash
   php database/migrate.php up
   ```

2. Verify tables were created:
   ```sql
   SHOW TABLES LIKE 'saved_streams';
   SHOW TABLES LIKE 'stream_interactions';
   SHOW TABLES LIKE 'stream_orders';
   ```

3. Check existing `live_streams` table has required columns:
   - `status` enum should include 'live', 'ended', etc.
   - `viewer_count` and `max_viewers` columns exist

## Testing Checklist

- [ ] Create a live stream as vendor
- [ ] Verify stream appears on public `/live.php` page
- [ ] Test viewer tracking (join/leave)
- [ ] Test like/dislike with and without login
- [ ] Test commenting with and without login
- [ ] Test "Buy Now" and "Add to Cart" buttons
- [ ] Verify real-time stats updates in seller dashboard
- [ ] Test end stream with "Save" option
- [ ] Test end stream with "Delete" option
- [ ] Verify saved stream appears in vendor's saved streams list

## Security Considerations

1. **Authentication**: All seller endpoints verify vendor ownership
2. **Input Validation**: Comments are sanitized before display
3. **SQL Injection**: All queries use prepared statements
4. **CSRF Protection**: Should be added to forms (existing system)
5. **Rate Limiting**: Consider adding to prevent spam comments

## Performance Optimizations

1. **Indexes**: All foreign keys are indexed
2. **Polling Intervals**: 
   - Public page: 10 seconds for stats
   - Seller dashboard: 5 seconds for comprehensive stats
3. **Query Optimization**: Use JOINs to minimize database calls
4. **Caching**: Consider caching active streams list

## Future Enhancements

1. WebSocket integration for true real-time updates
2. Video recording/storage integration
3. Stream scheduling system
4. Push notifications for followers
5. Analytics dashboard for sellers
6. Replay controls for saved streams
7. Stream highlights/clips feature
8. Multi-camera support
9. Screen sharing capability
10. Interactive polls during streams
