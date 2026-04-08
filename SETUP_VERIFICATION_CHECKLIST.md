# Installation & Setup Verification Checklist

Use this checklist to verify the service provider verification feature is properly installed.

## ✅ Pre-Installation Requirements

- [ ] XAMPP running (Apache + MySQL)
- [ ] HomeEase project accessible at `localhost/homeease/`
- [ ] Database connection working
- [ ] Provider account already registered with test data

## ✅ File Installation Verification

### Created Files (7 files)
- [ ] `api/provider_documents_api.php` exists
- [ ] `api/admin_documents_api.php` exists
- [ ] `api/provider_document_handler.php` exists
- [ ] `api/schema/provider_verification_schema.sql` exists (reference only)
- [ ] `assets/css/verification_documents.css` exists
- [ ] `VERIFICATION_IMPLEMENTATION.md` exists
- [ ] `QUICK_START_VERIFICATION.md` exists

### Modified Files (1 file)
- [ ] `providers/provider_home.php` updated with new form
- [ ] CSS link added to provider_home.php `<head>`
- [ ] JavaScript updated to use new API endpoint

### Documentation Files (1 file)
- [ ] `IMPLEMENTATION_COMPLETE.md` exists

## ✅ Directory Structure Verification

```
HomeEase/
├── api/
│   ├── provider_documents_api.php ✅
│   ├── admin_documents_api.php ✅
│   ├── provider_document_handler.php ✅
│   └── schema/
│       └── provider_verification_schema.sql ✅
│
├── assets/
│   ├── css/
│   │   └── verification_documents.css ✅
│   └── images/
│       └── registration/
│           ├── id/ ✅
│           ├── brgy/ ✅
│           ├── selfie/ ✅
│           ├── address/ ✅
│           └── tools/ ✅
│
└── providers/
    └── provider_home.php ✅
```

Verification: `[ ]` All directories exist with proper structure

## ✅ Database Verification

### Table Creation Test
```sql
-- Run in phpMyAdmin or MySQL CLI:
DESCRIBE provider_verification_images;
```

Expected columns:
- [ ] `id` (Primary Key, INT)
- [ ] `provider_id` (INT, Foreign Key)
- [ ] `image_type` (ENUM)
- [ ] `file_path` (VARCHAR 500)
- [ ] `original_filename` (VARCHAR 255)
- [ ] `file_size` (INT)
- [ ] `mime_type` (VARCHAR 50)
- [ ] `uploaded_at` (TIMESTAMP)
- [ ] `verified_at` (TIMESTAMP)
- [ ] `verification_notes` (TEXT)
- [ ] `is_approved` (TINYINT)

### Service Providers Column Addition Test
```sql
-- Check if new columns exist:
DESCRIBE service_providers;
```

Expected new columns:
- [ ] `verification_status` (VARCHAR 50)
- [ ] `verification_submitted_at` (TIMESTAMP)
- [ ] `verification_approved_at` (TIMESTAMP)

## ✅ File Upload Test

### Test Document Upload
1. [ ] Login as test provider
2. [ ] Navigate to provider verification form
3. [ ] Select and upload:
   - [ ] Valid ID (JPG/PNG/PDF, <5MB)
   - [ ] Barangay Clearance (JPG/PNG/PDF, <5MB)
   - [ ] Selfie (JPG/PNG, <3MB, min 320x240px)
   - [ ] Proof of Address (JPG/PNG/PDF, <5MB)
   - [ ] Tools & Kits (JPG/PNG/WebP, <5MB) - Optional

4. [ ] Click "Submit Requirements"
5. [ ] Verify success message appears

### File System Verification
Check if files were created:
```bash
Directory: HomeEase/assets/images/registration/
├── id/
│   └── [Files named like: 123_valid_id_1712500000_a1b2c3d4.jpg] ✅
├── brgy/
│   └── [Files named like: 123_barangay_clearance_...] ✅
├── selfie/
│   └── [Files named like: 123_selfie_...] ✅
├── address/
│   └── [Files named like: 123_proof_of_address_...] ✅
└── tools/
    └── [Files named like: 123_tools_kits_...] ✅
```

Verification: `[ ]` Files created in correct directories

### Security Verification
- [ ] Files have permissions 644 (not executable)
- [ ] Directories have permissions 755
- [ ] `.htaccess` file exists in each upload directory
- [ ] Files not directly executable via browser

## ✅ API Endpoint Testing

### Provider API Tests
```javascript
// Test 1: Check verification status
fetch('/homeease/api/provider_documents_api.php?action=check_status')
  .then(r => r.json())
  .then(d => console.log(d))
```
Expected: `{ success: true, status: "...", document_count: ... }`

```javascript
// Test 2: Get documents list
fetch('/homeease/api/provider_documents_api.php?action=get_documents')
  .then(r => r.json())
  .then(d => console.log(d))
```
Expected: `{ success: true, documents: {...} }`

### Admin API Tests
```javascript
// Test 3: Get pending verifications (Admin only)
fetch('/homeease/api/admin_documents_api.php?action=pending_verifications')
  .then(r => r.json())
  .then(d => console.log(d))
```
Expected: `{ success: true, providers: [...] }`

```javascript
// Test 4: Get provider documents (Admin)
fetch('/homeease/api/admin_documents_api.php?action=provider_documents&provider_id=123')
  .then(r => r.json())
  .then(d => console.log(d))
```
Expected: `{ success: true, documents: {...} }`

## ✅ Validation Testing

### Valid File Upload Test
- [ ] JPG file uploads successfully
- [ ] PNG file uploads successfully
- [ ] PDF file uploads successfully (where allowed)
- [ ] File appears in database with correct metadata

### Invalid File Testing
- [ ] Oversized file (>5MB) rejected with clear message
- [ ] Wrong format (EXE, ZIP) rejected
- [ ] Corrupted file handled gracefully
- [ ] Selfie < 320x240 pixels rejected with message

## ✅ Database Query Testing

### Provider Documents Query
```sql
SELECT * FROM provider_verification_images WHERE provider_id = 1;
```
Expected: Returns all documents for provider 1 with correct paths

### Approval Status Query
```sql
SELECT provider_id, verification_status FROM service_providers WHERE is_verified = 1;
```
Expected: Shows approved providers with correct status

## ✅ Admin Workflow Testing

### Admin Review Process
1. [ ] Admin can view pending verifications list
2. [ ] Admin can click to view specific provider documents
3. [ ] Admin can approve individual documents
4. [ ] Admin can reject documents with reason
5. [ ] Admin can approve entire provider at once
6. [ ] Database updates correctly after actions
7. [ ] Provider receives notification (if implemented)

### Admin API Calls
- [ ] `action=pending_verifications` returns correct data
- [ ] `action=provider_documents` returns specific provider docs
- [ ] `action=approve_document` updates database
- [ ] `action=reject_document` deletes file and record
- [ ] `action=approve_provider` sets is_verified = 1
- [ ] `action=statistics` returns accurate counts

## ✅ Integration Testing

### Verification Flow
1. [ ] Provider registers account
2. [ ] Redirected to provider_home.php
3. [ ] Verification form displays with 5 document fields
4. [ ] Can upload documents
5. [ ] Status changes to "pending" after submission
6. [ ] Admin can review documents
7. [ ] Admin approval sets status to "approved"
8. [ ] Provider can see "Verified" badge after approval

### Session Handling
- [ ] Provider ID preserved in session
- [ ] Session timeout handled gracefully
- [ ] Logout clears documents (access denied after logout)
- [ ] Admin session required for admin APIs

## ✅ Error Handling Testing

### Error Scenarios
- [ ] Network timeout handled gracefully
- [ ] Invalid file uploads show specific error
- [ ] Database errors show user-friendly message
- [ ] Missing files return 404 (not 500)
- [ ] Unauthorized access returns 401/403
- [ ] Form validation prevents empty uploads

## ✅ Browser Compatibility Testing

Test in multiple browsers:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Edge
- [ ] Safari (if macOS available)

Expected: Form displays correctly, files upload successfully

## ✅ Mobile Responsiveness Testing

- [ ] Form displays properly on mobile devices
- [ ] File upload works on mobile
- [ ] Document list responsive
- [ ] Buttons clickable on touch devices
- [ ] No horizontal scroll needed

## ✅ Performance Testing

- [ ] Upload completes within reasonable time
- [ ] Database queries complete in <100ms
- [ ] No memory leaks during file processing
- [ ] Can handle multiple concurrent uploads
- [ ] Large files (5MB) process without timeout

## ✅ Security Testing

- [ ] SQL injection attempts fail (parameterized queries)
- [ ] Session hijacking not possible (session checks)
- [ ] File access control working (ownership verified)
- [ ] Admin-only endpoints protected
- [ ] File permissions prevent execution
- [ ] MIME type validation enforced

## ✅ Backup & Recovery Testing

- [ ] Database backup includes new tables
- [ ] Database restore works with new schema
- [ ] Files recoverable from backup
- [ ] Verification can resume after restore

## ✅ Documentation Verification

- [ ] VERIFICATION_IMPLEMENTATION.md exists and is complete
- [ ] QUICK_START_VERIFICATION.md exists and is complete
- [ ] IMPLEMENTATION_COMPLETE.md exists
- [ ] All APIs documented with examples
- [ ] Setup instructions are clear
- [ ] Troubleshooting section comprehensive

## ✅ Final Production Checklist

Before going live:
- [ ] All tests passed
- [ ] No error logs in MySQL
- [ ] No error logs in PHP
- [ ] File permissions correct (644/755)
- [ ] .htaccess files properly configured
- [ ] Database backed up
- [ ] Admin trained on approval workflow
- [ ] Providers notified of new requirement
- [ ] Support team briefed on new feature

## ✅ Post-Deployment Monitoring

After deployment:
- [ ] Monitor error logs daily
- [ ] Check disk space for upload folder
- [ ] Monitor database size growth
- [ ] Track upload success/failure rates
- [ ] Collect provider feedback
- [ ] Monitor admin workflow efficiency

## ✅ Sign-Off

- [ ] **Implementer Name**: _________________
- [ ] **Implementation Date**: _________________
- [ ] **Testing Completed**: _________________
- [ ] **Ready for Production**: Yes ☐  No ☐
- [ ] **Comments/Notes**: 
  ```
  
  
  ```

---

## Quick Command Reference

### Check if tables exist
```sql
USE homeease_db;
SHOW TABLES LIKE 'provider_verification%';
```

### Check upload directory
```bash
ls -la assets/images/registration/
```

### Test API endpoint
```bash
curl -X POST http://localhost/homeease/api/provider_documents_api.php \
  -F "action=check_status"
```

### View error logs
```bash
# PHP errors
tail -f /xampp/apache/logs/error.log

# MySQL errors
tail -f /xampp/mysql/data/[hostname].err
```

---

## Support Resources

- **Full Documentation**: VERIFICATION_IMPLEMENTATION.md
- **Quick Reference**: QUICK_START_VERIFICATION.md
- **Implementation Summary**: IMPLEMENTATION_COMPLETE.md
- **This Checklist**: SETUP_VERIFICATION_CHECKLIST.md

**Verification Complete! System is ready for use.** ✅
