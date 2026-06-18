# Unified Notification System

## Overview
All notification/message boxes across the Faculty Evaluation System now use the **same consistent design and behavior** based on the "Evaluation is closed" notification style.

---

## 📦 Files Created

### 1. `unified_notifications.css`
Central CSS file containing all notification styling:
- Success notifications (green)
- Error notifications (red)
- Warning notifications (orange)
- Info notifications (blue)

### 2. `unified_notifications.js`
Universal JavaScript functions for showing notifications:
- `showNotification(message, type, duration)`
- `showGlobalNotification(message, type)`
- `showAlertModal(message)` - Backwards compatible

---

## 🎯 Implementation

### Integrated into All Pages:
✅ **FacultyAdmin.php** (Admin Dashboard)
✅ **FacultyUser.php** (Student Portal)
✅ **FacultyInstructor.php** (Faculty/Instructor Portal)

### How It Was Added:
```html
<!-- In <head> section -->
<link rel="stylesheet" href="unified_notifications.css?v=<?php echo time(); ?>">

<!-- Before </body> tag -->
<script src="unified_notifications.js?v=<?php echo time(); ?>"></script>
```

---

## 💻 Usage

### Basic Usage:
```javascript
// Success notification (green)
showNotification("Operation successful!", "success");

// Error notification (red)
showNotification("Something went wrong", "error");

// Warning notification (orange/amber)
showNotification("Please check your input", "warning");

// Info notification (blue)
showNotification("Here's some information", "info");
```

### With Custom Duration:
```javascript
// Show for 5 seconds instead of default 3 seconds
showNotification("This will stay longer", "success", 5000);
```

### Backwards Compatibility:
Old color-based calls still work automatically:
```javascript
// These are automatically converted:
showNotification("Success!", "#4caf50");     // → success (green)
showNotification("Error!", "#f44336");       // → error (red)
showNotification("Warning!", "#f59e0b");     // → warning (orange)
showNotification("Info!", "#3b82f6");        // → info (blue)
```

---

## 🎨 Notification Types

### 1. Success (Green)
**Usage:** Successful operations, confirmations
**Color:** `#ecfdf5` background, `#16a34a` icon
**Icon:** Check circle
```javascript
showNotification("Data saved successfully!", "success");
```

### 2. Error (Red)
**Usage:** Errors, failures, critical issues
**Color:** `#fef2f2` background, `#dc2626` icon
**Icon:** Warning circle
```javascript
showNotification("Failed to delete record", "error");
```

### 3. Warning (Orange)
**Usage:** Warnings, cautions, period errors, evaluation closed
**Color:** `#fffbeb` background, `#f59e0b` icon
**Icon:** Warning triangle
```javascript
showNotification("Period has already ended", "warning");
showNotification("Evaluation is closed", "warning");
```

### 4. Info (Blue)
**Usage:** General information, tips, neutral messages
**Color:** `#eff6ff` background, `#3b82f6` icon
**Icon:** Info circle
```javascript
showNotification("Please wait while we process...", "info");
```

---

## 📍 Position & Appearance

### Desktop:
- **Position:** Top center of screen (80px from top)
- **Width:** 320px - 500px
- **Style:** Rounded corners (12px), shadow, backdrop blur

### Mobile:
- **Position:** Top center (70px from top)
- **Width:** 90% of viewport
- **Responsive:** Adjusted padding and icon sizes

---

## ⏱️ Animation & Timing

### Show Animation:
- Slides down from top with fade-in
- Duration: 0.3 seconds
- Easing: ease

### Auto-Hide:
- Default duration: 3 seconds
- Customizable via third parameter
- Smooth fade-out transition

### Example:
```javascript
// Show for 5 seconds
showNotification("Long message here", "info", 5000);

// Show for 10 seconds
showNotification("Very important message", "warning", 10000);
```

---

## 🔄 Migration from Old System

### Admin (FacultyAdmin.js):
**Before:**
```javascript
showAlertModal("Period has already ended");  // Uses modal overlay
```

**After:**
```javascript
showNotification("Period has already ended", "warning");  // Uses unified notification
```

### Student (FacultyUser.js):
**Before:**
```javascript
showGlobalNotification("Evaluation is closed", "warning");  // Custom implementation
```

**After:**
```javascript
showNotification("Evaluation is closed", "warning");  // Unified system (automatically works)
```

---

## 🛠️ Technical Details

### CSS Classes:
- `.global-notification` - Container
- `.notification-content` - Content wrapper
- `.notification-icon` - Icon container
- `.notification-text` - Text content
- `.success`, `.error`, `.warning`, `.info` - Type modifiers
- `.show` - Visibility state

### JavaScript Functions:
```javascript
// Main function
showNotification(message, type, duration)
  // message: string - The text to display
  // type: 'success' | 'error' | 'warning' | 'info'
  // duration: number - Milliseconds to show (default: 3000)

// Alias (same as showNotification)
showGlobalNotification(message, type)

// Backwards compatible (converts to notification)
showAlertModal(message)
```

---

## 📊 Examples by Page

### Admin Dashboard:
```javascript
// Period management
showNotification("Period added successfully!", "success");
showNotification("Period has already ended", "warning");
showNotification("Unable to activate period", "error");

// Faculty management
showNotification("Faculty added successfully!", "success");
showNotification("Required fields missing!", "error");

// Student management
showNotification("Student archived successfully", "success");
```

### Student Portal:
```javascript
// Evaluation access
showNotification("Evaluation is closed", "warning");
showNotification("Evaluation period has ended", "warning");

// Profile updates
showNotification("Password updated successfully!", "success");
showNotification("Current password is incorrect", "error");

// Evaluation submission
showNotification("Evaluation submitted successfully!", "success");
```

### Faculty/Instructor Portal:
```javascript
// Report generation
showNotification("Report generated successfully!", "success");
showNotification("Failed to generate PDF report", "error");

// Profile updates
showNotification("Profile updated successfully!", "success");
```

---

## 🔍 Troubleshooting

### Notification Not Showing:
1. Check browser console for errors
2. Verify `unified_notifications.js` is loaded
3. Verify `unified_notifications.css` is loaded
4. Check that Phosphor Icons library is loaded (for icons)

### Console Log:
When loaded successfully, you should see:
```
✅ Unified Notification System loaded
```

### Styling Issues:
1. Clear browser cache (Ctrl+F5)
2. Check for CSS conflicts
3. Verify z-index is not overridden (should be 10000)

---

## 🎯 Benefits

✅ **Consistency:** Same look and feel across all pages
✅ **Maintainability:** One place to update styling
✅ **Backwards Compatible:** Old code still works
✅ **Flexible:** Easy to customize colors and timing
✅ **Responsive:** Works on all screen sizes
✅ **Accessible:** Clear visual feedback with icons
✅ **Modern:** Smooth animations and transitions

---

## 🚀 Future Enhancements

Possible improvements:
- Add close button (X) to dismiss manually
- Add sound notifications (optional)
- Add notification queue system (multiple notifications)
- Add notification history/log
- Add custom icon support
- Add position customization (top/bottom, left/right)

---

## 📝 Code Examples

### Complete Example - Admin Panel:
```javascript
// Delete operation
function deleteRecord(id) {
  fetch('delete.php', {
    method: 'POST',
    body: JSON.stringify({ id: id })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification("Record deleted successfully!", "success");
      refreshTable();
    } else {
      showNotification("Failed to delete: " + data.message, "error");
    }
  })
  .catch(error => {
    showNotification("Network error occurred", "error");
  });
}
```

### Complete Example - Student Portal:
```javascript
// Check evaluation access
async function checkEvaluationAccess() {
  try {
    const response = await fetch("periods_api.php?action=status");
    const status = await response.json();
    
    if (!status.evaluation_open) {
      showNotification("Evaluation is closed", "warning");
      return false;
    }
    
    return true;
  } catch (error) {
    showNotification("Unable to check evaluation status", "error");
    return false;
  }
}
```

---

## 📚 Related Files

- `unified_notifications.css` - All notification styles
- `unified_notifications.js` - All notification functions
- `FacultyAdmin.php` - Admin implementation
- `FacultyUser.php` - Student implementation
- `FacultyInstructor.php` - Faculty implementation
- `session_keepalive.js` - Session management (uses notifications)

---

## ✨ Summary

All notification messages across the entire Faculty Evaluation System now use a **unified, consistent design** that matches the "Evaluation is closed" notification style. This creates a cohesive user experience and makes the application feel more professional and polished.

**Implementation Date:** June 14, 2026
**Status:** ✅ Fully Implemented Across All Pages
