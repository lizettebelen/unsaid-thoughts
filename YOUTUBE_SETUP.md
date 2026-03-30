# YouTube API Setup Guide

## How to Enable YouTube Song Search

The song search is now connected to YouTube! Follow these steps to get it working:

### Step 1: Create a Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a Project" → "NEW PROJECT"
3. Name it "Unsaid Thoughts" (or whatever you prefer)
4. Click "CREATE"

### Step 2: Enable YouTube Data API
1. In Google Cloud Console, search for "YouTube Data API v3"
2. Click on it and press "ENABLE"

### Step 3: Create an API Key
1. Go to **Credentials** (left sidebar)
2. Click **+ CREATE CREDENTIALS** → **API Key**
3. Copy the generated API key

### Step 4: Add API Key to Your Project
1. Open `search_songs.php` in your editor
2. Find this line:
   ```php
   $youtube_api_key = 'AIzaSyDemoKeyExample'; // Replace with your actual YouTube API key
   ```
3. Replace `'AIzaSyDemoKeyExample'` with your actual API key (keep the quotes)
4. Save the file

### Step 5: Test It!
Try searching for a song in the "Write a Thought" form. You should see real YouTube results with thumbnails!

## Important Security Note
⚠️ **Never commit your API key to public repositories!** 

For production, use environment variables:
```php
$youtube_api_key = getenv('YOUTUBE_API_KEY');
```

## What You Get
- Real song results from YouTube
- Thumbnails of each song
- Direct YouTube links
- Falls back to local database if API fails
- Automatic artist/song title parsing from YouTube

## API Limits
- Free tier: 10,000 API units per day (plenty for a small site!)
- Each search uses ~100 units

## Troubleshooting
- **"No songs found"?** Check if your API key is correct
- **Slow searches?** YouTube API takes 2-3 seconds sometimes
- **API key not working?** Make sure YouTube Data API is ENABLED in Google Cloud Console

Enjoy search-powered song selection! 🎵
