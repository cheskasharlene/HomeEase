<?php

if (!function_exists('providerGetVerificationState')) {
    function providerGetVerificationState(?mysqli $existingConn = null, int $providerId = 0): string
    {
        $providerId = $providerId > 0 ? $providerId : (int) ($_SESSION['provider_id'] ?? 0);
        if ($providerId <= 0) {
            return 'not_verified';
        }

        if (!empty($_SESSION['provider_ui_verified'])) {
            $_SESSION['provider_is_verified'] = 1;
            $_SESSION['provider_verification_state'] = 'verified';
            return 'verified';
        }

        if (!empty($_SESSION['provider_approval_ready'])) {
            $_SESSION['provider_is_verified'] = 0;
            $_SESSION['provider_verification_state'] = 'approval_ready';
            return 'approval_ready';
        }

        $conn = $existingConn;
        if (!$conn) {
            require __DIR__ . '/../api/db.php';
            $conn = $conn ?? null;
        }

        if (!$conn instanceof mysqli) {
            return 'not_verified';
        }

        $columns = [];
        $colRes = $conn->query('SHOW COLUMNS FROM service_providers');
        if ($colRes) {
            while ($col = $colRes->fetch_assoc()) {
                $columns[] = (string) ($col['Field'] ?? '');
            }
        }

        $hasVerificationStatus = in_array('verification_status', $columns, true);
        $hasIsVerified = in_array('is_verified', $columns, true);

        $selectParts = [];
        if ($hasVerificationStatus) {
            $selectParts[] = 'verification_status';
        }
        if ($hasIsVerified) {
            $selectParts[] = 'is_verified';
        }

        $fallbackDocs = ['valid_id', 'selfie_verification', 'proof_of_address', 'barangay_clearance', 'tools_&_kits'];
        foreach ($fallbackDocs as $field) {
            if (in_array($field, $columns, true)) {
                $selectParts[] = (strpos($field, '&') !== false) ? '`' . $field . '`' : $field;
            }
        }

        if (!$selectParts) {
            return 'not_verified';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM service_providers WHERE provider_id = ? LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 'not_verified';
        }

        $stmt->bind_param('i', $providerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return 'not_verified';
        }

        $state = 'not_verified';
        if ($hasVerificationStatus) {
            $raw = strtolower(trim((string) ($row['verification_status'] ?? '')));
            if (in_array($raw, ['verified', 'pending_review', 'pending', 'rejected', 'not_verified', 'approval_ready'], true)) {
                if ($raw === 'pending_review') {
                    $raw = 'pending';
                }
                $state = $raw;
            }
        }

        if ($hasIsVerified && (int) ($row['is_verified'] ?? 0) === 1) {
            $state = 'verified';
        }

        // Sync verification_status with is_verified if they're out of sync
        if ($hasVerificationStatus && $hasIsVerified) {
            $isVerified = (int) ($row['is_verified'] ?? 0) === 1;
            $statusValue = strtolower(trim((string) ($row['verification_status'] ?? '')));
            // If is_verified=1 but verification_status is not 'verified', update it
            if ($isVerified && $statusValue !== 'verified') {
                $conn->query("UPDATE service_providers SET verification_status='verified', verification_approved_at=NOW() WHERE provider_id=$providerId LIMIT 1");
            }
        }

        if ($state === 'not_verified') {
            foreach ($fallbackDocs as $field) {
                if (array_key_exists($field, $row) && trim((string) $row[$field]) !== '') {
                    $state = 'pending';
                    break;
                }
            }
        }

        $_SESSION['provider_is_verified'] = ($state === 'verified') ? 1 : 0;
        $_SESSION['provider_verification_state'] = $state;

        return $state;
    }
}

if (!function_exists('providerIsVerified')) {
    function providerIsVerified(?mysqli $existingConn = null, int $providerId = 0): bool
    {
        return providerGetVerificationState($existingConn, $providerId) === 'verified';
    }
}

if (!function_exists('providerCanAccessSection')) {
    function providerCanAccessSection(string $section, bool $isVerified): bool
    {
        if ($isVerified) {
            return true;
        }

        $allowed = ['home', 'notifications', 'profile'];
        return in_array($section, $allowed, true);
    }
}

if (!function_exists('enforceProviderSectionAccess')) {
    function enforceProviderSectionAccess(string $section, ?mysqli $existingConn = null): array
    {
        $providerId = (int) ($_SESSION['provider_id'] ?? 0);
        $state = providerGetVerificationState($existingConn, $providerId);
        $isVerified = ($state === 'verified');

        if (!providerCanAccessSection($section, $isVerified)) {
            header('Location: provider_home.php?restricted=1');
            exit;
        }

        return [
            'state' => $state,
            'is_verified' => $isVerified,
        ];
    }
}

if (!function_exists('providerRequireVerifiedApi')) {
    function providerRequireVerifiedApi(?mysqli $existingConn = null): void
    {
        $providerId = (int) ($_SESSION['provider_id'] ?? 0);
        $state = providerGetVerificationState($existingConn, $providerId);
        if ($state !== 'verified') {
            echo json_encode([
                'success' => false,
                'message' => 'Verification required before accessing this feature.',
                'verification_state' => $state,
            ]);
            exit;
        }
    }
}
