# Security System - Complete OTP & Security Questions Implementation

## Overview
This document outlines the complete implementation of OTP-based password reset with security question verification and all security constraints.

---

## Database Schema Updates

### Required SQL Changes
Run the file `updated_schema.sql` to create the necessary tables:

1. **password_resets** - Tracks OTP requests with attempt/resend limits
2. **security_questions** - Stores user security questions and answers
3. **security_attempts** - Tracks security question verification attempts

---

## OTP Workflow - Complete Flow

### 1. **Forgot Password Page** (`forgot/forgot_password.php`)
- User enters email address
- System checks if email exists
- System checks if email is currently blocked (10-minute block)
- If not blocked, generates 6-digit OTP
- OTP sent via email
- OTP expires in **3 minutes**
- Redirects to verify OTP page

### 2. **OTP Verification** (`forgot/verify_otp.php`)
**OTP Rules:**
- Max **3 attempts** to enter correct OTP
- Max **3 resends** of OTP
- **60-second cooldown** between resends
- OTP **expires after 3 minutes**
- If max attempts (3) or max resends (3) exceeded → **email blocked for 10 minutes**
- Input field **disabled during block period**

**After OTP Verification:**
- System checks if user has security questions
  - **Has security questions** → Redirects to `security_verification.php`
  - **No security questions** → Redirects directly to `reset_password.php`

### 3. **Security Verification** (`forgot/security_verification.php`)
**Security Question Rules:**
- Max **5 attempts** to answer correctly
- All 3 answers must match exactly (case-insensitive)
- If max attempts exceeded → Modal shows "Maximum Attempts Exceeded" with button to return to login
- On success → Redirects to `reset_password.php`

### 4. **Reset Password** (`forgot/reset_password.php`)
- User enters new password (minimum 8 characters)
- Confirms password
- Password is hashed and updated
- Session cleaned up
- Redirect to login page

---

## Security Question Management

### Setting Security Questions
**Location:** `security/security_question.php`
- User logs in and navigates to dashboard
- Can set 3 security questions from predefined lists:
  - Personal questions
  - Childhood questions
  - Preference-based questions
- User provides answers (case-insensitive)
- Questions and answers stored in `security_questions` table

### Editing/Deleting Questions
- Users can edit existing questions
- Users can delete questions (optional for password reset)

---

## UI Components

### Modal System
**Files:**
- `css/modal.css` - Modal styling
- `jsform/modal.js` - Modal JavaScript class

**Modal Features:**
- Responsive modal dialogs
- Alert messages (success, error, warning, info)
- Timer display for countdowns
- Disabled input states

**Usage Example:**
```javascript
const modal = new Modal('modalId');
modal.show('Title', 'Message', [
    { text: 'OK', class: 'btn-primary', onclick: () => {} }
]);
```

---

## Security Features Implemented

### OTP Security
✅ 6-digit OTP generation  
✅ 3-minute expiration  
✅ 3 attempt limit  
✅ 3 resend limit  
✅ 60-second resend cooldown  
✅ 10-minute email block after exceeding limits  
✅ Input disabled during block period  

### Password Reset Security
✅ Email verification required  
✅ OTP verification required  
✅ Optional security question verification  
✅ Password minimum 8 characters  
✅ Password confirmation required  

### Security Question Security
✅ 5 attempt maximum  
✅ Case-insensitive answers  
✅ Modal alert on exceeded attempts  
✅ Automatic redirect to login on failure  

---

## File Structure

```
security2/
├── forgot/
│   ├── forgot_password.php          # Initial forgot password form
│   ├── verify_otp.php               # OTP verification page
│   ├── security_verification.php    # Security questions verification
│   └── reset_password.php           # New password form
├── security/
│   ├── security_question.php        # Set security questions
│   ├── input_security_question.php  # Input security answers
│   └── delete_security.php          # Delete security questions
├── css/
│   ├── modal.css                    # Modal and UI components
│   └── [other CSS files]
├── jsform/
│   ├── modal.js                     # Modal JavaScript helper
│   └── [other JS files]
├── updated_schema.sql               # Database schema updates
└── [other files]
```

---

## Installation Instructions

1. **Update Database Schema**
   - Open phpMyAdmin
   - Go to your database: `it107_security_sql`
   - Import or run `updated_schema.sql`

2. **Update Email Configuration** (if needed)
   - Edit `forgot/forgot_password.php`
   - Update Gmail SMTP credentials on lines with `Username` and `Password`

3. **Test the Flow**
   - Go to login page
   - Click "Forgot Password?"
   - Enter email address
   - Follow the OTP verification flow
   - Complete password reset

---

## API Responses & Behavior

### Success Messages
- "OTP sent successfully. Check your email."
- "Password reset successfully!"

### Error Messages
- "Email not found in our system."
- "This email is blocked. Please try again in X minute(s)."
- "Invalid OTP. X attempt(s) remaining."
- "Maximum OTP attempts exceeded. Email blocked for 10 minutes."
- "OTP expired. Please request a new one."
- "Incorrect answers. X attempt(s) remaining."
- "Maximum attempts exceeded." (with modal)

---

## Browser Compatibility
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- IE11: ⚠️ Limited (no CSS Grid/Flexbox issues, but some styling)

---

## Testing Checklist

- [ ] User can request OTP
- [ ] OTP expires after 3 minutes
- [ ] OTP max 3 attempts works
- [ ] OTP max 3 resends works
- [ ] 60-second resend cooldown works
- [ ] Email blocks for 10 minutes after max attempts
- [ ] User with security questions must verify them
- [ ] User without security questions goes directly to reset
- [ ] Security questions have 5 attempt limit
- [ ] Modal shows on security question max attempts
- [ ] Password is updated successfully
- [ ] Session is cleaned up after reset

---

## Security Notes

1. **OTP Storage:** OTPs are stored in plain text in database (can be hashed for production)
2. **Answers:** Security answers are stored in plain text (should be hashed in production)
3. **Email Configuration:** Update SMTP credentials for production
4. **HTTPS:** Deploy with HTTPS in production to protect credentials in transit

---

## Troubleshooting

### OTP not sending
- Check Gmail SMTP credentials in `forgot_password.php`
- Ensure Gmail app password is used (not regular password)
- Enable "Less secure app access" if using regular Gmail password

### Security questions not saving
- Verify `security_questions` table exists in database
- Check user ID is correctly retrieved
- Verify database user has INSERT/UPDATE permissions

### Timer not updating
- Check browser console for JavaScript errors
- Ensure `modal.js` is properly loaded
- Check system time is correct

---

## Future Enhancements

1. Add TOTP (Time-based One-Time Password) support
2. Email verification/confirmation
3. IP-based blocking
4. Security question history/audit log
5. Password strength indicator
6. Biometric authentication option

---

**Last Updated:** January 30, 2026  
**Version:** 1.0.0
