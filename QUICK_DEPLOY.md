# 🚀 QUICK DEPLOYMENT GUIDE - Contact Form Fix

## ⚡ Upload These Files to Server

```bash
# Via FTP or cPanel File Manager:

📄 MODIFIED:
   pages/contact.php

📄 NEW:
   includes/forms/process_contact.php
   database_contact_messages.sql
   test_contact.php (temporary - delete after testing)
   CONTACT_FORM_FIX.md (documentation)
```

## 🔧 Setup Commands (via cPanel Terminal or SSH)

```bash
# 1. Set permissions for uploads directory
cd /home/u107933880/domains/brodero.online/public_html
mkdir -p uploads/contact
chmod 755 uploads/contact

# 2. Run database migration
mysql -u u107933880_brodero -p u107933880_brodero < database_contact_messages.sql

# 3. Verify email configuration exists
# Check in cPanel → Email Accounts that contact@brodero.online exists
```

## ✅ Testing Checklist

```
1. Access: https://brodero.online/test_contact.php?debug_key=brodero2025
   → All tests should be green ✅

2. Test contact form:
   → Go to: https://brodero.online/pages/contact.php
   → Fill all fields
   → Attach 1-2 files (JPG, PDF)
   → Submit

3. Expected results:
   ✅ NO error "headers already sent"
   ✅ Success message appears
   ✅ Redirected back to contact page
   ✅ Email received at contact@brodero.online
   ✅ Email contains all form data
   ✅ Attachments are included in email
   ✅ Data saved in database

4. Verify in database:
   SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 1;

5. IMPORTANT: Delete test file!
   rm /home/u107933880/domains/brodero.online/public_html/test_contact.php
```

## 🐛 If Email Not Received

```bash
# Check mail logs:
tail -f /var/log/mail.log

# Test PHP mail function:
php -r "echo mail('contact@brodero.online', 'Test', 'Test') ? 'OK' : 'FAIL';"

# Check email configuration in cPanel:
# → Email Deliverability
# → Make sure all checks are green
# → Verify SPF, DKIM, rDNS
```

## 📞 Quick Fixes

### Problem: "headers already sent" still appears
**Solution:** Clear browser cache and server cache (if using caching)

### Problem: Email not received
**Solution:** 
1. Check spam folder
2. Verify contact@brodero.online exists in cPanel
3. Check mail logs for errors
4. Verify SPF record in DNS

### Problem: File upload fails
**Solution:**
```bash
chmod 755 /home/u107933880/domains/brodero.online/public_html/uploads/contact
chown u107933880:u107933880 /home/u107933880/domains/brodero.online/public_html/uploads/contact
```

### Problem: Database error
**Solution:** Re-run database_contact_messages.sql

## 📊 Monitor & Maintain

```sql
-- View recent messages:
SELECT id, name, email, subject, status, created_at 
FROM contact_messages 
ORDER BY created_at DESC 
LIMIT 10;

-- Mark message as read:
UPDATE contact_messages SET status = 'read' WHERE id = X;

-- Clean old messages (6+ months):
DELETE FROM contact_messages 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

## 🎯 Success Criteria

After deployment:
- ✅ Contact form works without errors
- ✅ Emails arrive at contact@brodero.online
- ✅ Attachments are included
- ✅ Messages saved in database
- ✅ User sees success message
- ✅ NO "headers already sent" error

---

**Total Time to Deploy:** ~10 minutes  
**Last Updated:** December 11, 2025  
**Status:** ✅ Production Ready
