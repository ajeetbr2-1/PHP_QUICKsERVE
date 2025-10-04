# 💬 QuickServe Chat System - Quick Start Guide

## 🚀 Overview
Complete real-time messaging system between customers and service providers with location sharing capabilities.

---

## ⚡ Quick Setup (First Time Only)

### Step 1: Initialize Chat Database
```
http://localhost/QuickServe/NearByMe/database/init-chat-system.php
```
This will:
- Create `conversations` table
- Create `messages` table  
- Add address fields to `users` table (address, city, pincode, latitude, longitude)
- Create necessary indexes

**Important**: Run this AFTER the main database initialization!

### Step 2: Verify Setup
You should see green checkmarks (✅) for:
- ✅ Created table: conversations
- ✅ Created table: messages
- ✅ Updated table: users
- ✅ Created index on: users
- ✅ Created index on: messages

---

## 📱 Using the Chat System

### Accessing Chat
1. **Login** to your account (customer or provider)
2. Click the **💬 Messages** link in navigation
3. Chat interface will load

### Customer View
- See list of conversations with providers
- Start new conversations when booking services
- Send text messages
- Share your location
- View unread message count

### Provider View
- See list of conversations with customers
- Respond to customer messages
- Share your location
- Track which messages are unread

---

## 🎯 Key Features

### 1. Real-Time Messaging
- **Auto-refresh**: Messages update every 3 seconds
- **Conversation list**: Updates every 10 seconds
- **Unread badges**: Shows unread message count
- **Time ago**: "Just now", "5 min ago", etc.

### 2. Location Sharing
- Click the **📍** button to share location
- Uses browser's built-in geolocation
- Receiver can open in Google Maps
- Shows coordinates and address

### 3. Conversation Management
- Conversations linked to specific services
- One conversation per customer-provider-service
- Last message preview in conversation list
- Automatic "read" status tracking

### 4. Mobile Responsive
- Works perfectly on phones and tablets
- Split-screen on desktop
- Single-column on mobile
- Touch-friendly interface

---

## 🗂️ File Structure

```
NearByMe/
├── chat.php                             # Main chat interface
├── database/
│   ├── add-chat-tables.sql              # SQL schema for chat
│   └── init-chat-system.php             # Setup script
└── api/
    ├── chat-create-conversation.php     # Create/get conversations
    ├── chat-send-message.php            # Send messages
    ├── chat-get-messages.php            # Fetch messages
    └── chat-get-conversations.php       # Get conversation list
```

---

## 🛠️ Technical Details

### Database Tables

#### `conversations`
```sql
- id (primary key)
- customer_id (foreign key → users)
- provider_id (foreign key → users)
- service_id (foreign key → services, nullable)
- last_message (text)
- last_message_time (datetime)
- customer_unread (int)
- provider_unread (int)
- created_at, updated_at (timestamps)
```

#### `messages`
```sql
- id (primary key)
- conversation_id (foreign key → conversations)
- sender_id (foreign key → users)
- message_type (enum: 'text', 'location', 'image', 'system')
- message_text (text)
- location_lat, location_lng (decimal, nullable)
- location_address (varchar, nullable)
- attachment_url (varchar, nullable)
- is_read (boolean)
- created_at (timestamp)
```

#### `users` (added fields)
```sql
- address (varchar 500)
- city (varchar 100)
- pincode (varchar 10)
- latitude (decimal 10,8)
- longitude (decimal 11,8)
```

---

## 🎨 UI Components

### Conversation List (Left Panel)
- User avatar (👨‍🔧 for provider, 👤 for customer)
- User name
- Service title (if linked)
- Last message preview
- Time ago
- Unread badge (red)

### Message Window (Right Panel)
- Chat header with user info
- Messages container (scrollable)
- Message bubbles (yours on right, theirs on left)
- Input field with location button
- Send button

---

## ⚙️ Configuration Options

### Adjust Refresh Intervals
Edit `chat.php`:

```javascript
// Line 402 - Conversation list refresh (default: 10 seconds)
setInterval(loadConversations, 10000);

// Line 466-470 - Message refresh (default: 3 seconds)
refreshInterval = setInterval(() => {
    if (currentConversationId) {
        loadNewMessages();
    }
}, 3000);
```

### Customize Message Display
Edit `chat.php` starting from line 530 - `createMessageHTML()` function

### Change Color Scheme
Edit inline styles in `chat.php` (lines 31-338)
- `.message.mine`: Your messages (green tint)
- `.message-content`: Other user's messages
- `.unread-badge`: Red notification badge

---

## 🐛 Troubleshooting

### Chat page doesn't load
1. Make sure you ran `init-chat-system.php`
2. Check if conversations table exists in phpMyAdmin
3. Verify user is logged in

### Messages not sending
1. Check browser console for errors (F12)
2. Verify `chat-send-message.php` exists in api/ folder
3. Check database connection

### Location sharing not working
1. Browser must support geolocation
2. User must grant location permission
3. HTTPS recommended (or localhost)

### Messages not updating
1. Check JavaScript console for errors
2. Verify `chat-get-messages.php` API works
3. Ensure conversation_id is correct

---

## 🔒 Security Features

- ✅ **Authentication**: Must be logged in
- ✅ **Authorization**: Can only access your conversations
- ✅ **SQL Injection**: Prepared statements used
- ✅ **XSS Protection**: HTML escaped on output
- ✅ **Access Control**: Role-based permissions

---

## 📊 API Endpoints

### 1. Create Conversation
```
POST api/chat-create-conversation.php
Body: {
    "other_user_id": 123,
    "service_id": 45 (optional)
}
Response: {
    "success": true,
    "conversation_id": 1,
    "exists": false
}
```

### 2. Send Message
```
POST api/chat-send-message.php
Body: {
    "conversation_id": 1,
    "message_text": "Hello!",
    "message_type": "text"
}
Response: {
    "success": true,
    "message_id": 123
}
```

### 3. Get Messages
```
GET api/chat-get-messages.php?conversation_id=1&limit=50&offset=0
Response: {
    "success": true,
    "messages": [...],
    "count": 10
}
```

### 4. Get Conversations
```
GET api/chat-get-conversations.php
Response: {
    "success": true,
    "conversations": [...],
    "count": 5
}
```

---

## ✨ Future Enhancements (Ideas)

- [ ] Image attachments in chat
- [ ] Typing indicators ("User is typing...")
- [ ] Voice messages
- [ ] Video call integration
- [ ] Read receipts with checkmarks
- [ ] Message search functionality
- [ ] Delete/edit sent messages
- [ ] Emoji picker
- [ ] File sharing
- [ ] Push notifications
- [ ] WebSocket for true real-time (instead of polling)

---

## 📞 Support

For issues or questions:
1. Check AGENT.md for detailed documentation
2. Review this guide
3. Check browser console for errors
4. Verify database tables exist
5. Test API endpoints directly

---

**Version**: 1.3.0  
**Last Updated**: 2025-10-03  
**Status**: ✅ Production Ready

---

## 🎉 Success Criteria

Your chat system is working correctly if:
- ✅ Can access chat page via 💬 Messages link
- ✅ Conversation list loads
- ✅ Can send and receive messages
- ✅ Messages update automatically
- ✅ Can share location
- ✅ Unread badges appear
- ✅ Mobile view works properly
- ✅ No JavaScript errors in console

---

**Congratulations! Your chat system is ready to use! 🚀**
