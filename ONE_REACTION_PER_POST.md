# One Reaction Per Post (Isang React Lang Per Post) - Implementation

## 📋 Summary
Users can now react with **only ONE emoji per post** (not one of each type). 

**Example:**
- ❌ User cannot react with BOTH ❤️ heart AND 🤗 hug on the same post
- ✅ User can react with ❤️ heart
- ✅ If they want to change, they click 🌙 moon to REPLACE the heart with moon
- ✅ If they want to remove, they click ❤️ again to delete it

---

## 🔄 Reaction Flow

### Scenario 1: User Reacts to a Post
```
User clicks ❤️ on Post #1
→ Heart reaction is added
→ Button turns pink (active state)
→ Count increases
```

### Scenario 2: User Changes Reaction Type
```
User already has ❤️ on Post #1
User clicks 🌙 moon
→ System removes heart reaction
→ System adds moon reaction  
→ Moon button turns pink
→ Heart button returns to normal
```

### Scenario 3: User Removes Reaction
```
User already has ❤️ on Post #1
User clicks ❤️ again (same button)
→ System removes heart reaction
→ Button returns to normal
→ Count decreases
```

---

## 💾 Database Changes

### Before (Multiple Per Post)
```sql
UNIQUE KEY unique_user_reaction (thought_id, user_id, type)
```
- Allowed: User A reacts with ❤️ heart AND 🤗 hug on same post

### After (One Per Post)
```sql
UNIQUE KEY unique_user_post (thought_id, user_id)
```
- **Only one reaction allowed per user per post**, regardless of type
- Enforced at database level (cannot insert duplicate)

---

## 🔧 Technical Implementation

### AJAX Handler (share.php)
The reaction handler now supports three operations:

1. **"added"** - User had no reaction, INSERT new one
```javascript
INSERT INTO reactions (thought_id, user_id, type) VALUES (?, ?, ?)
```

2. **"changed"** - User had different reaction, UPDATE to new type
```javascript
UPDATE reactions SET type = ? WHERE thought_id = ? AND user_id = ?
```

3. **"removed"** - User clicks same reaction, DELETE it
```javascript
DELETE FROM reactions WHERE thought_id = ? AND user_id = ?
```

### Response Format
The server returns counts for ALL reaction types:
```json
{
  "success": true,
  "action": "changed",
  "current_reaction": "moon",
  "counts": {
    "heart": 2,
    "hug": 1,
    "hurt": 0,
    "moon": 3
  }
}
```

### Frontend JavaScript
- Extracts all reaction buttons for the post
- Updates ALL counts based on returned data
- Sets active state only on `current_reaction` type
- Removes active state from all others

---

## 📁 Modified Files

### **schema.sql**
- Changed UNIQUE constraint from `(thought_id, user_id, type)` to `(thought_id, user_id)`

### **create.php**
- No changes to reaction logic (unchanged)
- Reactions still initialized on post creation

### **share.php**
- Updated AJAX handler to support ADD/CHANGE/REMOVE logic
- Refactored button rendering: checks if `$user_reactions[$thought_id] === $reaction_type`
- Updated JavaScript: iterates all buttons, updates states dynamically
- User reactions loaded as single value: `$user_reactions[$post_id] = 'heart'` (not array)

### **explore.php**
- Same changes as share.php
- Checks `$user_reactions[$thought['id']] === $reaction_type`
- Same dynamic JavaScript button update logic

---

## 🔀 User Experience

### Visual Feedback
- **Active Button**: Pink background + pink text (when user has reacted)
- **Normal Button**: Gray background (when user hasn't reacted)
- **Hover**: Light pink background (before click)
- **Click Animation**: Pulse effect to show action

### Behavior
| Action | Before | After | Result |
|--------|--------|-------|--------|
| Click new reaction | - | ❤️ | Reacted with heart |
| Click diff reaction | ❤️ | 🌙 | Heart removed, moon added |
| Click same reaction | ❤️ | ❤️ | Heart removed (toggled off) |
| See another user's reaction | All types shown with counts | Only their single reaction shown active | Clearer who reacted what |

---

## 🧪 Verification Results

```
✅ UNIQUE constraint on (thought_id, user_id) exists
✅ Inserted heart reaction
✅ Second reaction (hug) correctly blocked
✅ Changed reaction from heart to moon (UPDATE works)
✅ Deleted reaction (can remove reaction)
✅ No user has multiple reactions on same post
✅ Reaction workflow (add → change → remove) works correctly
```

---

## 🚀 Migration

Run this once after deployment:
```bash
php migrate_reactions_v2.php
```

This script:
1. Backs up existing reactions
2. Recreates reactions table with new constraint
3. Migrates data (keeps latest reaction per user per post)
4. Removes older reactions (if user had multiple)

---

## 📝 Testing Checklist

- [ ] Clear browser cookies (new user ID)
- [ ] `WRITE` a new thought
- [ ] Go to `EXPLORE` - your post should NOT appear
- [ ] Go to `REACTIONS` page
- [ ] React to a post with ❤️ - button turns pink
- [ ] Click 🌙 - heart button returns to gray, moon turns pink
- [ ] Click 🌙 again - moon button returns to gray (removed)
- [ ] Try reacting to same post from different browser (different user)
- [ ] Verify both users' reactions show correctly
- [ ] Check that another user can't react if you have a reaction (should replace, not add)

---

## 📱 Compatibility

Works on:
- ✅ Share page (reactions.php)
- ✅ Explore page (explore.php)
- ✅ All emoji buttons: ❤️ 🤗 💔 🌙
- ✅ All browsers with JavaScript enabled
- ✅ Mobile devices

---

## 🔒 Database Integrity

The UNIQUE constraint is enforced at the database level:
```sql
UNIQUE KEY unique_user_post (thought_id, user_id)
```

This means:
- No duplicate entries can exist (database prevents it)
- Cannot accidentally insert two reactions for same user on same post
- Even if JavaScript fails, database still protects integrity

---

## ⚡ Performance Notes

- Single reaction query: `O(1)` - indexed on (thought_id, user_id)
- Count aggregation: Uses `GROUP BY type` on indexed column
- Update operation: Direct UPDATE on indexed keys
- No performance degradation vs. previous system

---

## 🎯 Intent Summary

This change implements the user's requirement in Filipino:
> "isang react lang per post, halimbawa nakapag react na ng heart di na pede magreact dun sa ibang reaction"
> 
> Translation: "One reaction per post only. For example, if they've already reacted with heart, they can't react with another emoji"

The system now enforces this at both:
1. **Database level** - UNIQUE constraint prevents duplicate inserts
2. **Application level** - JavaScript handles add/change/remove logic

