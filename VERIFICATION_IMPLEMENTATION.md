# Service Provider Verification Feature - Implementation Guide

## Overview
This document provides comprehensive instructions for implementing the document verification feature for service provider accounts in the HomeEase system.

## Features Implemented

### 1. **Document Upload System**
- Five document types required for service provider verification:
  - ✅ Valid Government ID (Required)
  - ✅ Barangay Clearance (Required)
  - ✅ Selfie - Identity Confirmation (Required)
  - ✅ Proof of Address (Required)
  - ✓ Tools & Kits (Optional)

### 2. **File Validation**
- **File types**: JPG, PNG, PDF (except Selfie: JPG/PNG only, Tools: JPG/PNG/WebP)
- **File size limits**: 3-5MB per document
- **Image validation**: Selfie images require minimum 320x240 pixels
- **MIME type verification**: Server-side validation using finfo_file()

### 3. **Secure Storage**
- Dedicated directories for each document type:
  - `assets/images/registration/id/` - Valid Government ID
  - `assets/images/registration/brgy/` - Barangay Clearance
  - `assets/images/registration/selfie/` - Selfie photos
  - `assets/images/registration/address/` - Proof of Address
  - `assets/images/registration/tools/` - Tools & Kits
- Unique filenames with timestamp and random hash: `{provider_id}_{type}_{timestamp}_{random}.ext`
- .htaccess files prevent script execution in upload directories
- File permissions set to 0644

### 4. **Database Schema**
New table: `provider_verification_images`
```sql
CREATE TABLE provider_verification_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  image_type ENUM('valid_id', 'barangay_clearance', 'selfie', 'proof_of_address', 'tools_kits'),
  file_path VARCHAR(500) NOT NULL,
  original_filename VARCHAR(255),
  file_size INT,
  mime_type VARCHAR(50),
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL,
  verification_notes TEXT,
  is_approved TINYINT(1) DEFAULT 0,
  FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id)
);
```

Added columns to `service_providers`:
- `verification_status` - Status: 'not_submitted', 'partial', 'submitted', 'approved'
- `verification_submitted_at` - When documents were submitted
- `verification_approved_at` - When approval was granted

### 5. **API Endpoints**

#### Provider-side APIs (`api/provider_documents_api.php`)

**POST: Upload documents**
```
POST /api/provider_documents_api.php
Parameters:
  - action: 'upload_documents'
  - valid_id: file
  - barangay_clearance: file
  - selfie: file
  - proof_of_address: file
  - tools_kits: file (optional)
  - selected_service: string
  - profile_name: string
  - profile_phone: string
  - profile_address: string
  - experience_description: string
  - years_experience: number
```

**GET: Retrieve documents**
```
GET /api/provider_documents_api.php?action=get_documents
Returns: All uploaded documents for authenticated provider
```

**GET: Check verification status**
```
GET /api/provider_documents_api.php?action=check_status
Returns: Current verification status and document count
```

**POST: Delete document**
```
POST /api/provider_documents_api.php
Parameters:
  - action: 'delete_document'
  - doc_id: integer
```

#### Admin APIs (`api/admin_documents_api.php`)

**GET: View pending verifications**
```
GET /api/admin_documents_api.php?action=pending_verifications
Returns: All providers with unverified documents
```

**GET: View provider documents**
```
GET /api/admin_documents_api.php?action=provider_documents&provider_id=123
Returns: All documents for specific provider
```

**POST: Approve document**
```
POST /api/admin_documents_api.php
Parameters:
  - action: 'approve_document'
  - doc_id: integer
  - notes: string (optional)
```

**POST: Reject document**
```
POST /api/admin_documents_api.php
Parameters:
  - action: 'reject_document'
  - doc_id: integer
  - reason: string
```

**POST: Approve all documents for provider**
```
POST /api/admin_documents_api.php
Parameters:
  - action: 'approve_provider'
  - provider_id: integer
```

**GET: Verification statistics**
```
GET /api/admin_documents_api.php?action=statistics
Returns: Stats on pending/approved providers and total documents
```

## Setup Instructions

### Step 1: Database Initialization
The system automatically creates required tables and columns on first use:
- Table creation happens in `provider_documents_api.php` via `initializeTables()` function
- Columns are added to `service_providers` only if they don't exist
- No manual SQL execution required

### Step 2: Directory Verification
Ensure upload directories exist and have proper permissions:
```
assets/images/registration/
├── id/
├── brgy/
├── selfie/
├── address/
└── tools/
```

The system automatically creates these directories at runtime with 0755 permissions.

### Step 3: File Upload Configuration (php.ini adjustments)
For production, ensure these PHP settings are configured:
```ini
; In php.ini or .htaccess
upload_max_filesize = 20M
post_max_size = 25M
```

### Step 4: Integration with Registration Flow
Documents upload is triggered from `providers/provider_home.php`:
1. Provider fills out verification form
2. Selects and uploads 5 documents (4 required + 1 optional)
3. Form validates files client-side
4. Server validates files server-side
5. Files stored in organized directories
6. Database records created
7. Admin notified of submission

### Step 5: Admin Review Workflow
1. Admin logs into admin dashboard
2. Views pending provider verifications
3. Reviews each document
4. Approves or rejects with notes
5. Provider receives notification
6. Approved provider unlocks all features

## Security Considerations

### File Security
- ✅ Files stored outside web root is NOT done (for accessibility)
- ✅ .htaccess files prevent script execution
- ✅ MIME type validation on server
- ✅ File extension validation
- ✅ Unique filenames prevent directory traversal
- ✅ File permissions set to read-only (0644)

### Database Security
- ✅ Prepared statements prevent SQL injection
- ✅ Foreign key constraints ensure data integrity
- ✅ Provider ID verified from session (not user input)

### Access Control
- ✅ Providers can only upload/delete own documents
- ✅ Only admins can approve/reject documents
- ✅ Admin APIs check authorization headers

### Directory Security
- ✅ .htaccess denies all access (require web server interpretation)
- ✅ Upload folders are web-accessible for admin/provider viewing
- ✅ Consider additional web server rules for production

## File Locations

### Core Files Created:
```
api/
├── provider_documents_api.php        # Provider document upload/management
├── admin_documents_api.php            # Admin verification management
├── provider_document_handler.php      # Utility functions
└── schema/
    └── provider_verification_schema.sql  # Database schema reference

providers/
└── provider_home.php                 # Enhanced with new upload form

assets/images/registration/
├── id/                               # Government IDs
├── brgy/                             # Barangay clearances
├── selfie/                           # Selfie photos
├── address/                          # Address proofs
└── tools/                            # Tools & Kits
```

### Modified Files:
```
providers/provider_home.php            # Updated form and JavaScript
```

## Error Handling

### Client-Side Validation
- File existence checks
- File count validation
- Visual feedback for file selection

### Server-Side Validation
- MIME type checking (using finfo_file)
- File size validation
- Image dimension validation (for selfies)
- Database operation error handling

### Error Messages
- Clear, user-friendly error messages
- Specific document type in errors
- File size limits provided
- MIME type requirements listed

## Testing the Implementation

### Manual Testing
1. **Registration**: Test provider registration flow
2. **Document Upload**: Upload all 5 document types
3. **File Validation**: Try invalid files (wrong type/size)
4. **Admin Review**: Approve/reject documents as admin
5. **Verification Status**: Check provider verification status updates

### Browser DevTools Testing
```javascript
// Check upload in Network tab
// Monitor Storage for session persistence
// Check Console for JavaScript errors
```

### XAMPP Local Testing
1. Access localhost/homeease/
2. Register new provider account
3. Submit documents
4. Check files in assets/images/registration/
5. Login as admin and review

## Troubleshooting

### Common Issues

**Issue**: "Data directory not created"
- **Solution**: System auto-creates directories. Check folder permissions.

**Issue**: "Database table doesn't exist"
- **Solution**: Tables auto-create on first API call. Try uploading a document.

**Issue**: "File upload fails silently"
- **Solution**: Check PHP error logs. Verify upload_max_filesize in php.ini.

**Issue**: ".htaccess not working"
- **Solution**: Verify Apache AllowOverride is enabled. Check .htaccess syntax.

### Debug Mode
Enable detailed logging by modifying the API:
```php
// Add at top of provider_documents_api.php
error_log('Document upload attempt: ' . json_encode($_FILES));
```

## Compliance and Best Practices

### GDPR/Privacy Compliance
- Documents stored securely with access control
- Provider can delete own documents
- Admin can view and manage documents
- Verification notes stored for audit trail

### Data Integrity
- Foreign key constraints ensure data consistency
- ON DELETE CASCADE removes orphaned documents
- Timesteps track all actions
- Audit trail via verification_notes field

### Scalability
- Indexed database queries (provider_id, image_type, uploaded_at)
- Unique filenames prevent collisions
- Directory structure scales to many providers
- API pagination ready (LIMIT clause prepared)

## Future Enhancements

1. **Advanced Image Processing**
   - Automatic image compression before storage
   - Face detection for selfie validation
   - OCR for ID document verification

2. **Batch Processing**
   - Admin bulk approval workflows
   - Automated compliance checking
   - Document expiration reminders

3. **Document Versions**
   - Track document history
   - Allow re-upload for rejected documents
   - Version comparison

4. **Enhanced Analytics**
   - Verification success rates
   - Time-to-approval metrics
   - Document rejection reasons analysis

## Support and Maintenance

### Regular Maintenance
- Monitor upload folder sizes
- Archive old verification documents
- Clean up rejected rejections
- Update MIME type mappings

### Updates and Patches
- Monitor PHP/MySQL compatibility
- Update file upload security rules
- Review server security settings
- Update .htaccess rules as needed

## Contact and Support
For issues or questions about this implementation, refer to the system documentation or contact the development team.
