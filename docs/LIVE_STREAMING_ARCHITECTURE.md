# Live Streaming Feature - Architecture Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PUBLIC LIVE PAGE                              │
│                          (live.php)                                  │
│                                                                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────────┐  │
│  │  Stream Video   │  │  Chat/Comments  │  │ Featured Products │  │
│  │                 │  │                 │  │                   │  │
│  │  👥 234 watching│  │  💬 Live chat   │  │  🛒 Buy Now      │  │
│  │  ⏰ 45:23       │  │  Login to chat  │  │  📦 Add to Cart  │  │
│  │                 │  │                 │  │                   │  │
│  │  👍 42 👎 3    │  │                 │  │                   │  │
│  └─────────────────┘  └─────────────────┘  └──────────────────┘  │
│         │                     │                      │             │
│         └─────────────────────┼──────────────────────┘             │
│                               │                                     │
└───────────────────────────────┼─────────────────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │    API Layer          │
                    │                       │
                    │  /api/live/          │
                    │  ├─ streams.php      │ ◄─── Get active streams
                    │  ├─ interact.php     │ ◄─── Like/Comment (auth)
                    │  ├─ viewers.php      │ ◄─── Join/Leave stream
                    │  ├─ stats.php        │ ◄─── Get statistics (vendor)
                    │  └─ end-stream.php   │ ◄─── Save/Delete (vendor)
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Model Classes       │
                    │                       │
                    │  ├─ LiveStream        │
                    │  ├─ SavedStream       │
                    │  ├─ StreamInteraction │
                    │  ├─ StreamViewer      │
                    │  └─ StreamOrder       │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │     Database          │
                    │                       │
                    │  Tables:              │
                    │  ├─ live_streams      │ ◄─── Main stream records
                    │  ├─ saved_streams     │ ◄─── Saved videos
                    │  ├─ stream_interactions│ ◄─── Likes/Comments
                    │  ├─ stream_viewers    │ ◄─── Viewer tracking
                    │  ├─ stream_orders     │ ◄─── Purchase tracking
                    │  ├─ products          │ ◄─── Product catalog
                    │  └─ orders            │ ◄─── Order records
                    └───────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                    SELLER DASHBOARD                                  │
│                (seller/stream-interface.php)                         │
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐             │
│  │ Video Setup  │  │ Stream Stats │  │ Live Feeds   │             │
│  │              │  │              │  │              │             │
│  │ 📹 Camera    │  │ 👥 187 views │  │ 💬 Comments  │             │
│  │ 🎤 Mic       │  │ 👍 42 likes  │  │ 👤 Viewers   │             │
│  │ 🔴 Go Live   │  │ 💬 156 chats │  │ 🛍️ Orders    │             │
│  │ 🛑 End       │  │ 🛒 23 orders │  │              │             │
│  │              │  │ 💰 $1,847    │  │ Auto-refresh │             │
│  └──────────────┘  └──────────────┘  └──────────────┘             │
│                           │                                          │
│                           ▼                                          │
│                  ┌──────────────────┐                               │
│                  │  End Stream      │                               │
│                  │  Modal           │                               │
│                  │                  │                               │
│                  │  Final Stats:    │                               │
│                  │  ⏰ 45:23 min    │                               │
│                  │  👥 234 viewers  │                               │
│                  │  💰 $1,847.50    │                               │
│                  │                  │                               │
│                  │  [💾 Save]       │ ──► saved_streams table       │
│                  │  [🗑️ Delete]     │ ──► Stream deleted            │
│                  └──────────────────┘                               │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagrams

### 1. Viewer Joins Stream
```
User visits /live.php
    │
    ▼
Load active streams from database
    │
    ├─► LiveStream::getActiveStreams()
    │       │
    │       ▼
    │   SELECT * FROM live_streams WHERE status='live'
    │
    ▼
Display streams with interaction buttons
    │
    ▼
Auto-join stream (viewer tracking)
    │
    ├─► POST /api/live/viewers.php {action: 'join'}
    │       │
    │       ▼
    │   StreamViewer::addViewer()
    │       │
    │       ▼
    │   INSERT INTO stream_viewers
    │
    ▼
Start polling for updates (every 10s)
    │
    ├─► GET /api/live/viewers.php?action=count
    ├─► POST /api/live/interact.php {action: 'get_comments'}
    │
    ▼
Display real-time data
```

### 2. User Likes Stream (Not Logged In)
```
Click Like button
    │
    ▼
Check if logged in (JavaScript)
    │
    ▼ (not logged in)
Redirect to login page
    │
    └─► /login.php?return=/live.php
            │
            ▼ (after login)
        Return to /live.php
            │
            ▼
        Re-click Like
            │
            ▼
        POST /api/live/interact.php
            │
            ├─► Check authentication (Session::isLoggedIn())
            │
            ├─► StreamInteraction::addInteraction()
            │       │
            │       ▼
            │   INSERT INTO stream_interactions
            │   (stream_id, user_id, interaction_type='like')
            │
            ▼
        Return success
            │
            ▼
        Update UI (increment counter)
```

### 3. User Purchases Product During Stream
```
Click "Buy Now" button
    │
    ▼
Call buyNow(productId) from purchase-flows.js
    │
    ├─► Check if logged in
    │   │
    │   ▼ (not logged in)
    │   Redirect to login
    │
    ▼ (logged in)
Add to cart and redirect to checkout
    │
    ▼
Order completed
    │
    ├─► Create order record
    │   │
    │   ▼
    │   INSERT INTO orders
    │
    ├─► Link to stream
    │   │
    │   ▼
    │   StreamOrder::recordStreamOrder()
    │   │
    │   ▼
    │   INSERT INTO stream_orders
    │   (stream_id, order_id, product_id, amount)
    │
    ▼
Seller sees order in real-time dashboard
```

### 4. Seller Monitors Stream
```
Seller starts stream
    │
    ├─► POST /api/live/streams.php (create stream)
    │   │
    │   ▼
    │   LiveStream::createStream()
    │   │
    │   ▼
    │   INSERT INTO live_streams (status='scheduled')
    │
    ├─► LiveStream::startStream()
    │   │
    │   ▼
    │   UPDATE live_streams SET status='live', started_at=NOW()
    │
    ▼
Start polling for statistics (every 5s)
    │
    └─► GET /api/live/stats.php?stream_id=123
            │
            ├─► Verify vendor ownership
            │
            ├─► LiveStream::getStreamStats()
            │   │
            │   ▼
            │   SELECT with multiple JOINs:
            │   - Count likes/dislikes from stream_interactions
            │   - Count comments from stream_interactions
            │   - Count viewers from stream_viewers
            │   - Sum revenue from stream_orders
            │
            ├─► StreamViewer::getActiveViewers()
            │   │
            │   ▼
            │   SELECT * FROM stream_viewers
            │   WHERE stream_id=123 AND is_active=1
            │
            ├─► StreamInteraction::getStreamComments()
            │
            ├─► StreamOrder::getStreamOrders()
            │
            ▼
        Return comprehensive stats
            │
            ▼
        Update dashboard UI
```

### 5. Seller Ends Stream
```
Click "End Stream" button
    │
    ▼
Show end stream modal
    │
    ├─► Fetch final statistics
    │   │
    │   └─► GET /api/live/stats.php
    │
    ▼
Display modal with options
    │
    ├─► Click "Save Stream"
    │   │
    │   ├─► POST /api/live/end-stream.php {action: 'save'}
    │   │   │
    │   │   ├─► LiveStream::endStream()
    │   │   │   │
    │   │   │   ▼
    │   │   │   UPDATE live_streams SET status='ended'
    │   │   │
    │   │   ├─► SavedStream::saveStream()
    │   │   │   │
    │   │   │   ▼
    │   │   │   INSERT INTO saved_streams
    │   │   │   (stream_id, video_url, duration, stats...)
    │   │   │
    │   │   ▼
    │   │   Return success with saved stream ID
    │   │
    │   ▼
    │   Show success message
    │   "Stream saved for on-demand viewing"
    │
    └─► Click "Delete Stream"
        │
        ├─► POST /api/live/end-stream.php {action: 'delete'}
        │   │
        │   ├─► LiveStream::endStream()
        │   │   │
        │   │   ▼
        │   │   UPDATE live_streams SET status='ended'
        │   │
        │   └─► (No saved_streams record created)
        │
        ▼
        Show success message
        "Stream ended (not saved)"
```

## Database Schema Relationships

```
┌─────────────────┐
│  live_streams   │ ◄────────┐
│  - id (PK)      │          │
│  - vendor_id    │          │
│  - title        │          │ (FK: stream_id)
│  - status       │          │
│  - started_at   │          │
│  - ended_at     │          │
└────────┬────────┘          │
         │                   │
         │ (FK: stream_id)   │
         │                   │
    ┌────┴────┬──────────────┴──────────┬─────────────┐
    │         │                          │             │
    ▼         ▼                          ▼             ▼
┌────────┐ ┌──────────────────┐  ┌──────────────┐ ┌──────────────┐
│saved_  │ │stream_           │  │stream_       │ │stream_orders │
│streams │ │interactions      │  │viewers       │ │              │
├────────┤ ├──────────────────┤  ├──────────────┤ ├──────────────┤
│- id    │ │- id              │  │- id          │ │- id          │
│- stream│ │- stream_id (FK)  │  │- stream_id(FK)│ │- stream_id(FK)│
│  _id   │ │- user_id (FK)    │  │- user_id (FK)│ │- order_id (FK)│
│- video │ │- interaction_type│  │- joined_at   │ │- product_id  │
│  _url  │ │- comment_text    │  │- left_at     │ │- amount      │
│- dura  │ │- created_at      │  │- is_active   │ │- created_at  │
│  tion  │ └──────────────────┘  └──────────────┘ └──────────────┘
│- stats │            │                   │
└────────┘            │                   │
                      ▼                   ▼
                ┌───────────┐       ┌───────────┐
                │   users   │       │   users   │
                │  - id(PK) │       │  - id(PK) │
                │  - username│       │  - username│
                └───────────┘       └───────────┘
```

## Performance Considerations

### Polling Strategy
```
Public Page (live.php):
├─ Viewer count: Every 10 seconds
├─ Comments: Every 10 seconds
└─ Join/Leave: On page load/unload

Seller Dashboard:
├─ Full stats: Every 5 seconds
├─ Viewers list: Every 5 seconds
├─ Comments feed: Every 5 seconds
└─ Orders: Every 5 seconds
```

### Database Optimization
```
Indexes Created:
├─ saved_streams
│  ├─ idx_vendor_id (vendor_id)
│  ├─ idx_streamed_at (streamed_at)
│  └─ idx_saved_at (saved_at)
│
├─ stream_interactions
│  ├─ idx_stream_user (stream_id, user_id)
│  ├─ idx_stream_type (stream_id, interaction_type)
│  └─ idx_created_at (created_at)
│
├─ stream_viewers
│  └─ stream_id (foreign key auto-indexed)
│
└─ stream_orders
   ├─ idx_stream_id (stream_id)
   ├─ idx_vendor_id (vendor_id)
   └─ idx_created_at (created_at)

Query Optimization:
├─ Use JOINs instead of multiple queries
├─ Prepared statements prevent SQL injection
├─ Aggregate functions (COUNT, SUM) in single query
└─ WHERE clause uses indexed columns
```

## Security Architecture

```
Authentication Flow:
┌──────────────┐
│ Public APIs  │
│ - streams    │ ──► No auth required
│ - viewers    │ ──► No auth required (join/count)
└──────────────┘

┌──────────────┐
│ User APIs    │
│ - interact   │ ──► Session::isLoggedIn()
│ - like       │     ├─ Returns 401 if not logged in
│ - comment    │     └─ Includes redirect URL
└──────────────┘

┌──────────────┐
│ Vendor APIs  │
│ - stats      │ ──► Session::isLoggedIn() +
│ - end-stream │     Vendor::findByUserId() +
└──────────────┘     Stream ownership check

Input Sanitization:
├─ htmlspecialchars() for output
├─ Prepared statements for SQL
├─ Type casting for IDs (int)
└─ trim() for text input
```

## Scalability Path

```
Current Implementation:
└─ HTTP Polling (5-10 second intervals)

Future Enhancements:
├─ WebSocket Integration
│  └─ Socket.io or similar for real-time updates
│
├─ Video CDN
│  └─ Store recordings on CDN for saved streams
│
├─ Redis Caching
│  └─ Cache active stream lists and viewer counts
│
└─ Load Balancing
   └─ Multiple API servers for high traffic
```

This architecture provides a solid foundation that can scale from dozens to thousands of concurrent viewers with proper infrastructure upgrades.
