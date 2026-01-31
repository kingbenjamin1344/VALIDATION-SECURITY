# Quick Start Guide - OTP & Security Question System

## 📋 Pre-Installation Checklist

- [ ] Backup your database
- [ ] Have phpMyAdmin access ready
- [ ] Know your Gmail SMTP credentials (or email provider)
- [ ] XAMPPrunning with MySQL/Apache

---

## 🚀 Installation (5 minutes)

### Step 1: Update Database (1 minute)
```
1. Open phpMyAdmin
2. Select database: it107_security_sql
3. Click "SQL" tab
4. Copy entire content from SETUP.sql
5. Paste into SQL editor
6. Click "Go"
```
**Result:** 3 new tables created

### Step 2: Update Email Settings (1 minute)
```
File: forgot/forgot_password.php
Lines: ~86-95

Update:
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->setFrom('your-email@gmail.com', 'Your App');
```

### Step 3: Done! ✅
All files are already updated in your workspace.

---

## 🧪 Testing (5 minutes)

### Test Case 1: Complete OTP Flow
```
1. Go to: login.php
2. Click: "Forgot Password?"
3. Enter: Your test email
4. Check: Email for OTP (check spam folder)
5. Enter: 6-digit OTP
6. Result: Should redirect to password reset or security questions
```

### Test Case 2: OTP Expiration
```
1. Request OTP
2. Wait 3+ minutes
3. Try to enter OTP
4. Result: "OTP expired" message
```

### Test Case 3: Max Attempts (3)
```
1. Request OTP
2. Enter wrong OTP 3 times
3. Result: Email blocked for 10 minutes
4. Try to request new OTP
5. Result: Cannot request, must wait 10 minutes
```

### Test Case 4: Max Resends (3)
```
1. Request OTP
2. Click "Resend OTP" 3 times
3. Result: After 3rd resend, email blocked for 10 minutes
```

### Test Case 5: Security Questions (if set)
```
1. Complete OTP verification
2. Arrive at security questions page
3. Answer wrong 5 times
4. Result: Modal appears, button redirects to login
```

---

## 📁 File Structure Quick Reference

```
MAIN WORKFLOW:
  forgot_password.php
    ↓ (OTP sent)
  verify_otp.php
    ↓ (OTP correct)
  security_verification.php [IF USER HAS QUESTIONS]
    ↓ (Answers correct)
  reset_password.php
    ↓ (Password updated)
  login.php [Redirect]

SETTINGS:
  security_question.php (Set questions)
    ↓
  input_security_question.php (Enter answers)
    ↓
  delete_security.php (Delete questions)

UI:
  css/modal.css (Styling)
  jsform/modal.js (Functionality)
```

---

## ⚙️ Configuration

### Email Provider Setup

#### Gmail
```
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password in configuration
4. Allow less secure apps (if not using 2FA)
```

#### Other Providers
Update in `forgot_password.php`:
```php
$mail->Host = 'smtp.your-provider.com';
$mail->Port = 587; // or 465
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
```

---

## 🔐 Security Defaults

| Feature | Setting | Can Change? |
|---------|---------|------------|
| OTP Length | 6 digits | Yes (line in forgot_password.php) |
| OTP Expiration | 3 minutes | Yes ('+3 minutes' in code) |
| Max OTP Attempts | 3 | Yes (>= 3 check in verify_otp.php) |
| Max OTP Resends | 3 | Yes (>= 3 check in verify_otp.php) |
| Resend Cooldown | 60 seconds | Yes (60 in JavaScript) |
| Block Duration | 10 minutes | Yes ('+10 minutes' in code) |
| Security Q Attempts | 5 | Yes (>= 5 check in security_verification.php) |
| Password Min Length | 8 characters | Yes (minlength="8" in HTML) |

---

## 🐛 Troubleshooting

### Problem: OTP not sending
**Solution:**
1. Check email configuration in `forgot_password.php`
2. Verify Gmail app password is correct
3. Check spam folder
4. Check server error logs

### Problem: OTP verification not working
**Solution:**
1. Clear browser cache
2. Check database `password_resets` table has data
3. Verify email matches
4. Check system time is correct

### Problem: Security questions not showing
**Solution:**
1. Verify user is logged in
2. Check `security_questions` table for user_id
3. Ensure user has set security questions in dashboard

### Problem: Timer not counting down
**Solution:**
1. Check browser console for JavaScript errors
2. Verify `modal.js` is loaded
3. Check browser supports ES6

### Problem: Modal not appearing
**Solution:**
1. Verify `modal.css` is loaded
2. Check browser console for JavaScript errors
3. Verify `modal.js` is loaded correctly

---

## 📊 Database Structure

### password_resets table
```sql
email              varchar - User email
otp                varchar(6) - 6-digit OTP
otp_attempts       int - Tracks failed attempts
resend_count       int - Tracks resend attempts
is_blocked         tinyint - Block flag
blocked_until      datetime - When block expires
last_resend_time   datetime - Last resend time
expires_at         datetime - OTP expiration time
```

### security_questions table
```sql
user_id            int - Reference to users table
question_1         varchar - Security question 1
answer_1           varchar - Security answer 1
question_2         varchar - Security question 2
answer_2           varchar - Security answer 2
question_3         varchar - Security question 3
answer_3           varchar - Security answer 3
```

---

## 🎯 Key Features

✅ **OTP System**
- 6-digit OTP generation
- 3-minute expiration with countdown
- 3 attempt limit
- 3 resend limit
- 60-second resend cooldown
- 10-minute email blocking

✅ **Security Questions**
- Optional per user
- 3 questions required to set
- 5 attempt verification limit
- Case-insensitive answers
- Modal alert on exceeded attempts

✅ **Password Reset**
- Email verification via OTP
- Optional security question verification
- 8-character minimum password
- Password confirmation required

✅ **UI/UX**
- Modern modal dialogs
- Real-time countdown timers
- Disabled input fields during blocks
- Alert notifications (success/error/warning)
- Responsive design
- Professional styling

---

## 🔄 Workflow Examples

### User WITH Security Questions
```
1. User clicks "Forgot Password?"
2. Enters email
3. Receives OTP via email
4. Enters correct OTP
5. System detects user has security questions
6. User is asked to answer 3 security questions
7. User answers correctly
8. User sets new password
9. User redirected to login
```

### User WITHOUT Security Questions
```
1. User clicks "Forgot Password?"
2. Enters email
3. Receives OTP via email
4. Enters correct OTP
5. System detects user has NO security questions
6. User goes directly to set new password
7. User redirected to login
```

### User EXCEEDS Max OTP Attempts
```
1. User enters wrong OTP 3 times
2. Email is blocked for 10 minutes
3. Input field becomes disabled
4. Modal shows message about block
5. User must wait 10 minutes
6. User can try again after block expires
```

---

## 📚 Related Documentation

- **IMPLEMENTATION_GUIDE.md** - Detailed technical documentation
- **CHANGES_SUMMARY.md** - Summary of all changes made
- **updated_schema.sql** - Database schema file
- **SETUP.sql** - Quick setup SQL file

---

## ✨ Tips & Best Practices

1. **Passwords:** Use strong passwords (mix of uppercase, lowercase, numbers, symbols)
2. **Security Questions:** Choose questions only you can answer
3. **Email:** Keep your email address updated
4. **OTP:** Don't share OTP with anyone
5. **Session:** Close browser after password reset for security

---

## 🆘 Need Help?

1. Check console for JavaScript errors (F12 → Console)
2. Check server error logs
3. Verify database tables exist
4. Verify email configuration
5. Check password_resets table for OTP records
6. Verify system time is correct

---

**Happy Testing! 🎉**
