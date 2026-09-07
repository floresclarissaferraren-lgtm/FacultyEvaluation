# Academic Year Display Fix - Summary

## Problem
Kapag nag-add ng academic year/period at nag-refresh ang page, nawawala yung data sa UI. Hindi nagdidisplay yung inadd na academic year kahit naka-save na sa database.

## Root Cause
Walang code na nag-load ng periods data kapag:
1. User clicks Academic Year section sa sidebar
2. Page nag-refresh at bumalik sa Academic Year section

## Solution Applied

### 1. Auto-load Periods When Academic Year Section is Shown
**File:** `FacultyAdmin.js`  
**Function:** `showSection()`  
**Line Added:**
```javascript
if (id === "academic-year-section") loadPeriods?.();
```

**What it does:**
- Kapag nag-click ng "Academic Year" sa sidebar, automatic na tumatawag ng `loadPeriods()`
- Kukunin lahat ng evaluation periods from database via `periods_api.php?action=list`
- Ididisplay sa table with correct status (Active/Inactive)

### 2. Dashboard Dropdowns Now Pull from Database
**File:** `FacultyAdmin.js`  
**Functions Modified:**
- `populateAcademicYearSelect()` - Changed to async, fetches from database
- `populateSemesterSelect()` - New function, fetches semesters based on selected AY
- `syncDashboardPeriodSetting()` - Now async, calls both populate functions

**File:** `FacultyAdmin.php`  
**Change:** Removed hardcoded semester options from HTML

**What it does:**
- Dashboard dropdowns now show only academic years/semesters na naka-add sa database
- Kapag nag-add ng new period, automatic lalabas sa dropdowns
- Kapag walang data sa database, shows "No academic year available"

### 3. Period Activation Now Sends Email Notifications
**File:** `periods_api.php`  
**Action:** `set_active`  
**Added:**
```php
$notifyResult = sendEvaluationOpenNotifications($conn, $notifyAy, $notifySemester);
```

**What it does:**
- Kapag mag-activate ng period, automatic na nagsesend ng email notifications sa students
- Returns count of sent/failed notifications

## Complete Flow

### When Adding New Academic Year:
1. User clicks "Add New" button
2. Fills form (AY, Semester, Start/End dates)
3. Clicks "Save"
4. Data saves to `evaluation_periods` table
5. Table automatically refreshes via `loadPeriods()`
6. New period appears in the table with "Inactive" status

### After Page Refresh:
1. Page reloads → Dashboard is shown
2. Dashboard dropdowns automatically populate from database
3. User clicks "Academic Year" in sidebar
4. `showSection('academic-year-section')` is called
5. `loadPeriods()` is automatically triggered
6. ✅ **All data is displayed correctly!**

### When Activating a Period:
1. User clicks "Inactive" button
2. Backend (`periods_api.php`):
   - Sets all other periods to `is_active = 0`
   - Sets selected period to `is_active = 1`
   - Sets `evaluation_open = 1`
   - Sends email notifications to students
3. Frontend:
   - Button changes to "Active" (green)
   - Table refreshes automatically
4. After refresh:
   - Status persists (still shows "Active")
   - Evaluation remains open

## Files Modified

1. **FacultyAdmin.js**
   - Added `loadPeriods?.()` call in `showSection()`
   - Modified `populateAcademicYearSelect()` to fetch from database
   - Added `populateSemesterSelect()` function
   - Updated `syncDashboardPeriodSetting()` to be async

2. **FacultyAdmin.php**
   - Removed hardcoded semester options from dashboard dropdown

3. **periods_api.php**
   - Added email notification sending in `set_active` action

## Testing

### Test 1: Add and Refresh
1. Go to Academic Year section
2. Add new period (e.g., 2024-2025, 1st Semester)
3. Press F5 to refresh
4. Click Academic Year in sidebar
5. ✅ Data should still be there

### Test 2: Activation Persistence
1. Add a period
2. Click "Inactive" button → becomes "Active"
3. Refresh page (F5)
4. Go back to Academic Year section
5. ✅ Status should still show "Active"

### Test 3: Dashboard Dropdowns
1. Add multiple periods with different AYs and semesters
2. Refresh page
3. Check dashboard dropdowns
4. ✅ All added AYs and semesters should appear in dropdowns

### Test 4: Email Notifications
1. Activate a period
2. ✅ Students should receive email notifications
3. Frontend shows: "Evaluation opened. Email notifications sent to X students"

## Database Tables Involved

### evaluation_periods
- `id` - Period ID
- `ay` - Academic Year (e.g., 2024-2025)
- `semester` - Semester (1st Semester, 2nd Semester, Summer)
- `start_date` - Start date
- `end_date` - End date
- `is_active` - 1 if active, 0 if inactive

### evaluation_settings
- `evaluation_open` - 1 if open, 0 if closed
- `active_period_id` - ID of the active period
- `selected_ay` - Dashboard selected academic year
- `selected_semester` - Dashboard selected semester

## API Endpoints Used

### GET `periods_api.php?action=list`
Returns all evaluation periods from database

### POST `periods_api.php?action=set_active`
Activates a period and sends notifications
```json
{
  "id": 1
}
```

Response:
```json
{
  "success": true,
  "message": "Period activated and evaluation opened.",
  "notify_sent": 45,
  "notify_failed": 0
}
```

## Result
✅ Academic year data now persists correctly after refresh  
✅ Dashboard dropdowns populate from database  
✅ Period activation sends email notifications  
✅ Status (Active/Inactive) is displayed and persists correctly  
✅ Only one period can be active at a time  
