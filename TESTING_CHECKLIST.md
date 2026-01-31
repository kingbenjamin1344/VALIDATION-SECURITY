# Testing & Validation Guide

## Pre-Testing Requirements
- [ ] Database updated with new schema (run SETUP.sql)
- [ ] Email configuration updated in forgot_password.php
- [ ] Test user account exists in users table
- [ ] Test user email is accessible

---

## OTP Flow Tests

### Test 1.1: Basic OTP Request
**Steps:**
1. Navigate to `forget/forgot_password.php`
2. Enter valid email address
3. Click "Send OTP"

**Expected:**
- ✅ Message: "OTP sent successfully"
- ✅ Redirect to verify_otp.php
- ✅ Email received with 6-digit OTP
- ✅ Database: password_resets record created

**Actual:** ___

---

### Test 1.2: OTP Verification - Correct
**Steps:**
1. Request OTP (from Test 1.1)
2. Copy 6-digit OTP from email
3. Paste into OTP field
4. Click "Verify OTP"

**Expected:**
- ✅ If user has security questions → redirect to security_verification.php
- ✅ If user has no questions → redirect to reset_password.php
- ✅ Database: OTP marked as verified

**Actual:** ___

---

### Test 1.3: OTP Verification - Wrong (1st Attempt)
**Steps:**
1. Request OTP
2. Enter random 6-digit number
3. Click "Verify OTP"

**Expected:**
- ✅ Message: "Invalid OTP. 2 attempt(s) remaining."
- ✅ User stays on verify_otp.php
- ✅ Database: otp_attempts = 1

**Actual:** ___

---

### Test 1.4: OTP Verification - Wrong (2nd Attempt)
**Steps:**
1. From Test 1.3, enter another wrong OTP
2. Click "Verify OTP"

**Expected:**
- ✅ Message: "Invalid OTP. 1 attempt(s) remaining."
- ✅ User stays on verify_otp.php
- ✅ Database: otp_attempts = 2

**Actual:** ___

---

### Test 1.5: OTP Verification - Wrong (3rd Attempt - MAX)
**Steps:**
1. From Test 1.4, enter another wrong OTP
2. Click "Verify OTP"

**Expected:**
- ✅ Message: "Maximum OTP attempts exceeded. Email blocked for 10 minutes."
- ✅ Modal appears: "Maximum Attempts Exceeded"
- ✅ Input field disabled (cannot type)
- ✅ Database: is_blocked = 1, blocked_until = NOW() + 10 mins

**Actual:** ___

---

### Test 1.6: OTP Resend - Cooldown (60 seconds)
**Steps:**
1. Request OTP
2. Immediately click "Resend OTP"
3. Try to click again within 60 seconds

**Expected:**
- ✅ First resend succeeds
- ✅ "Resend available in: 59s" appears
- ✅ Button disabled for 60 seconds
- ✅ Timer counts down
- ✅ After 60s, button becomes enabled
- ✅ Database: resend_count = 1, last_resend_time = NOW()

**Actual:** ___

---

### Test 1.7: OTP Resend - Max Attempts (3)
**Steps:**
1. Request OTP
2. Click "Resend OTP" 3 times (respecting 60s cooldown)
3. Try to resend 4th time

**Expected:**
- ✅ After 3rd resend: success
- ✅ 4th resend attempt: Message "Maximum resend attempts exceeded. Email blocked for 10 minutes."
- ✅ Email blocked for 10 minutes
- ✅ Modal appears
- ✅ Database: resend_count = 3, is_blocked = 1

**Actual:** ___

---

### Test 1.8: OTP Expiration (3 minutes)
**Steps:**
1. Request OTP
2. Wait 3+ minutes (or manually set server time forward)
3. Try to enter OTP

**Expected:**
- ✅ Timer shows "OTP expires in: 0:00" or expired
- ✅ Message: "OTP expired. Please request a new one."
- ✅ Submit button disabled
- ✅ Input field disabled

**Actual:** ___

---

### Test 1.9: Email Not Found
**Steps:**
1. Navigate to forgot_password.php
2. Enter non-existent email
3. Click "Send OTP"

**Expected:**
- ✅ Message: "Email not found in our system."
- ✅ No email sent
- ✅ User stays on forgot_password.php
- ✅ Database: No password_resets record created

**Actual:** ___

---

### Test 1.10: Email Blocked - Cannot Request New OTP
**Steps:**
1. From Test 1.5, email is blocked
2. Go to forgot_password.php
3. Enter the blocked email
4. Click "Send OTP"

**Expected:**
- ✅ Message: "This email is blocked. Please try again in X minute(s)."
- ✅ Shows remaining block time
- ✅ No new OTP sent
- ✅ User must wait

**Actual:** ___

---

## Security Questions Flow Tests

### Test 2.1: Security Questions - Set Questions (First Time)
**Steps:**
1. Login as user
2. Navigate to security/security_question.php
3. Select 3 different questions from dropdowns
4. Click "Next"

**Expected:**
- ✅ Redirects to input_security_question.php
- ✅ Shows selected questions
- ✅ Can enter answers

**Actual:** ___

---

### Test 2.2: Security Questions - Enter Answers
**Steps:**
1. From Test 2.1, on input_security_question.php
2. Enter answers for all 3 questions
3. Click "Save Security Answers"

**Expected:**
- ✅ Message: "Security questions saved successfully!"
- ✅ Redirects back to security_question.php
- ✅ Now shows "Your Security Questions" section
- ✅ Database: security_questions record created
- ✅ Answers stored (case-insensitive comparison ready)

**Actual:** ___

---

### Test 2.3: Security Verification - Correct Answers
**Steps:**
1. Request OTP for user with security questions
2. Enter correct OTP
3. Redirected to security_verification.php
4. Enter correct answers for all 3 questions
5. Click "Verify Answers"

**Expected:**
- ✅ All answers match (case-insensitive)
- ✅ Redirects to reset_password.php
- ✅ User can now reset password
- ✅ Database: Updated sec_attempts = 0

**Actual:** ___

---

### Test 2.4: Security Verification - Wrong Answers (1st)
**Steps:**
1. From security_verification.php
2. Enter wrong answer to at least one question
3. Click "Verify Answers"

**Expected:**
- ✅ Message: "Incorrect answers. 4 attempt(s) remaining."
- ✅ User stays on security_verification.php
- ✅ Progress bar or counter shows attempts: 1/5
- ✅ Database: Session sec_attempts = 1

**Actual:** ___

---

### Test 2.5: Security Verification - Wrong Answers (5 Attempts - MAX)
**Steps:**
1. From security_verification.php
2. Enter wrong answers 5 times total
3. On 5th attempt, click "Verify Answers"

**Expected:**
- ✅ After 4th wrong: Message with 0 attempts remaining
- ✅ On 5th wrong: Modal appears "Maximum Attempts Exceeded"
- ✅ Modal text: "You have exceeded the maximum number of attempts..."
- ✅ Button: "Back to Login" that redirects to login.php
- ✅ Form becomes disabled
- ✅ Database: Session sec_attempts = 5

**Actual:** ___

---

### Test 2.6: Security Questions - Edit Existing
**Steps:**
1. User with security questions goes to security_question.php
2. Click "Edit" button
3. Select different questions
4. Click "Next"
5. Enter new answers
6. Click "Save"

**Expected:**
- ✅ Old security questions replaced
- ✅ New questions saved
- ✅ No duplicate records in DB
- ✅ Answers updated

**Actual:** ___

---

### Test 2.7: Security Questions - Delete
**Steps:**
1. User with security questions goes to security_question.php
2. Click "Delete" button
3. Confirm in popup

**Expected:**
- ✅ Record deleted from security_questions table
- ✅ Redirects back to security_question.php with success message
- ✅ Now shows form to select new questions
- ✅ User can set new questions

**Actual:** ___

---

### Test 2.8: Case-Insensitive Answers
**Steps:**
1. Security question answer is: "New York"
2. During verification, enter: "new york" (lowercase)
3. Click verify

**Expected:**
- ✅ Answer accepted as correct (case-insensitive)
- ✅ Continue to password reset

**Actual:** ___

---

## Password Reset Tests

### Test 3.1: Password Reset - Valid Password
**Steps:**
1. Complete OTP verification (and security questions if applicable)
2. On reset_password.php, enter:
   - New Password: `SecurePass123!`
   - Confirm: `SecurePass123!`
3. Click "Reset Password"

**Expected:**
- ✅ Message: "Password reset successfully!"
- ✅ Redirect to login.php after 2 seconds
- ✅ Session destroyed
- ✅ Database: users.password updated with hash
- ✅ Database: password_resets record deleted
- ✅ Can login with new password

**Actual:** ___

---

### Test 3.2: Password Reset - Password Too Short
**Steps:**
1. On reset_password.php
2. Enter password: `Short1!` (7 characters)
3. Click "Reset Password"

**Expected:**
- ✅ Message: "Password must be at least 8 characters long."
- ✅ User stays on reset_password.php
- ✅ Password NOT updated in database

**Actual:** ___

---

### Test 3.3: Password Reset - Passwords Don't Match
**Steps:**
1. On reset_password.php
2. New Password: `SecurePass123!`
3. Confirm: `DifferentPass123!`
4. Click "Reset Password"

**Expected:**
- ✅ Message: "Passwords do not match."
- ✅ User stays on reset_password.php
- ✅ Password NOT updated

**Actual:** ___

---

### Test 3.4: Password Reset - Empty Field
**Steps:**
1. On reset_password.php
2. Leave one or both fields empty
3. Click "Reset Password"

**Expected:**
- ✅ Browser validation prevents submission (HTML required)
- ✅ Or message: "Please fill in all password fields."

**Actual:** ___

---

## Complete Workflow Tests

### Test 4.1: Complete Flow - User WITH Security Questions
**Steps:**
1. Click "Forgot Password?"
2. Enter email of user with security questions
3. Receive and enter OTP
4. Answer security questions correctly
5. Reset password
6. Login with new password

**Expected:**
- ✅ All steps succeed without errors
- ✅ Can access dashboard with new password

**Actual:** ___

---

### Test 4.2: Complete Flow - User WITHOUT Security Questions
**Steps:**
1. Click "Forgot Password?"
2. Enter email of user WITHOUT security questions
3. Receive and enter OTP
4. Skipped to password reset directly (no security questions)
5. Reset password
6. Login with new password

**Expected:**
- ✅ No security_verification.php shown
- ✅ Goes directly to reset_password.php
- ✅ Can access dashboard with new password

**Actual:** ___

---

## UI/UX Tests

### Test 5.1: Modal Appearance
**Steps:**
1. Trigger modal condition (e.g., max attempts exceeded)
2. Observe modal

**Expected:**
- ✅ Modal appears centered on screen
- ✅ Smooth fade-in animation
- ✅ Background dimmed
- ✅ Content readable and well-formatted
- ✅ Button clickable

**Actual:** ___

---

### Test 5.2: Timer Display
**Steps:**
1. Request OTP
2. Observe timer on verify_otp.php

**Expected:**
- ✅ Timer displays "OTP expires in: 3:00"
- ✅ Counts down every second
- ✅ Format correct (M:SS)
- ✅ Changes color to red when < 1 minute remaining

**Actual:** ___

---

### Test 5.3: Disabled Input During Block
**Steps:**
1. Get email blocked (exceed max attempts)
2. Try to type in OTP input field

**Expected:**
- ✅ Input field is greyed out / disabled
- ✅ Cannot type in field
- ✅ Cannot submit form

**Actual:** ___

---

### Test 5.4: Alert Messages
**Steps:**
1. Trigger various error/success conditions
2. Observe alert messages

**Expected:**
- ✅ Success messages green
- ✅ Error messages red
- ✅ Warning messages yellow
- ✅ Messages appear and disappear appropriately
- ✅ Messages are readable and clear

**Actual:** ___

---

## Database Verification Tests

### Test 6.1: Database Records - OTP Request
**Steps:**
1. Request OTP for test email
2. Check phpMyAdmin → password_resets table

**Expected:**
- ✅ New row with: email, otp, otp_attempts=0, resend_count=0, is_blocked=0, expires_at=NOW()+3mins

**Actual:** ___

---

### Test 6.2: Database Records - OTP Failed Attempt
**Steps:**
1. Request OTP
2. Enter wrong OTP
3. Check password_resets record

**Expected:**
- ✅ otp_attempts incremented to 1

**Actual:** ___

---

### Test 6.3: Database Records - Email Blocked
**Steps:**
1. Exceed max OTP attempts
2. Check password_resets record

**Expected:**
- ✅ is_blocked = 1
- ✅ blocked_until = NOW() + 10 minutes
- ✅ otp_attempts = 3 (or resend_count = 3)

**Actual:** ___

---

### Test 6.4: Database Records - Security Questions Saved
**Steps:**
1. Set security questions for user
2. Check security_questions table

**Expected:**
- ✅ New row with user_id, question_1, answer_1, question_2, answer_2, question_3, answer_3
- ✅ Answers stored as plain text (not hashed)

**Actual:** ___

---

## Browser Compatibility Tests

### Test 7.1: Chrome/Edge
- [ ] All features working
- [ ] Styling correct
- [ ] Timers functioning
- [ ] Modals appear correctly

### Test 7.2: Firefox
- [ ] All features working
- [ ] Styling correct
- [ ] Timers functioning
- [ ] Modals appear correctly

### Test 7.3: Safari
- [ ] All features working
- [ ] Styling correct
- [ ] Timers functioning
- [ ] Modals appear correctly

---

## Security Tests

### Test 8.1: SQL Injection Prevention
**Steps:**
1. Try SQL injection in email field: `' OR '1'='1`
2. Try SQL injection in OTP field: `' OR '1'='1`

**Expected:**
- ✅ Properly escaped/prepared statements
- ✅ No SQL errors
- ✅ No unauthorized access

**Actual:** ___

---

### Test 8.2: XSS Prevention
**Steps:**
1. Try XSS in email: `<script>alert('xss')</script>`
2. Try XSS in error messages by triggering errors

**Expected:**
- ✅ Scripts not executed
- ✅ htmlspecialchars() applied to outputs
- ✅ Content properly escaped

**Actual:** ___

---

### Test 8.3: Session Security
**Steps:**
1. Complete password reset
2. Check if session variables are cleared

**Expected:**
- ✅ otp_verified session cleared
- ✅ reset_email session cleared
- ✅ Session destroyed after reset

**Actual:** ___

---

## Performance Tests

### Test 9.1: Email Sending Performance
**Steps:**
1. Request OTP
2. Measure time to receive email

**Expected:**
- ✅ Email received within 30 seconds

**Actual:** ___ seconds

---

### Test 9.2: Page Load Time
**Steps:**
1. Load forgot_password.php
2. Load verify_otp.php
3. Load security_verification.php
4. Load reset_password.php

**Expected:**
- ✅ All pages load in < 2 seconds

**Actual:** ___ seconds

---

## Test Results Summary

| Category | Total Tests | Passed | Failed | Notes |
|----------|------------|--------|--------|-------|
| OTP Flow | 10 | ___ | ___ | |
| Security Questions | 8 | ___ | ___ | |
| Password Reset | 4 | ___ | ___ | |
| Complete Workflow | 2 | ___ | ___ | |
| UI/UX | 4 | ___ | ___ | |
| Database | 4 | ___ | ___ | |
| Browser Compat | 3 | ___ | ___ | |
| Security | 3 | ___ | ___ | |
| Performance | 2 | ___ | ___ | |
| **TOTAL** | **40** | ___ | ___ | |

---

## Notes & Issues Found

```
Issue #1: [Description]
Location: [File/Line]
Resolution: [What to fix]
Status: [Fixed/Pending/Won't Fix]

---

Issue #2: [Description]
Location: [File/Line]
Resolution: [What to fix]
Status: [Fixed/Pending/Won't Fix]
```

---

**Test Date:** ___________  
**Tester Name:** ___________  
**Overall Status:** ✅ PASS / ⚠️ PARTIAL / ❌ FAIL
