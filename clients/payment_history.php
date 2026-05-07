<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
    <title>HomeEase – Payment History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/bookings.css">
    <style>
        .payment-history-card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .payment-history-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-color: #e0e0e0;
        }
        
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .method-cash {
            background: #fef3c7;
            color: #92400e;
        }
        
        .method-gcash {
            background: #dcfce7;
            color: #166534;
        }
        
        .method-bank {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-cancelled {
            background: #f3f4f6;
            color: #374151;
        }
        
        .payment-details {
            flex: 1;
        }
        
        .payment-date {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .payment-amount {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f5f3ff, #ede9fe);
            border-radius: 12px;
            padding: 16px;
            border-left: 4px solid #8b5cf6;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: #1a1a2e;
            margin-top: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div id="toastBox"></div>
    <div class="shell">
        <div class="hdr">
            <div class="hdr-top">
                <div>
                    <div class="hdr-sub">Hi, <?= $userName ?> 👋</div>
                    <div class="hdr-title">Payment History</div>
                </div>
                <a href="../home.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
            </div>
        </div>

        <div class="scroll">
            <div style="padding: 18px 16px 0;">
                <!-- Statistics Section -->
                <div id="statsContainer" class="payment-stats" style="display:none;"></div>
                
                <!-- Payment List -->
                <div id="paymentListContainer">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div>Loading payments...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bnav">
            <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
            <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
            <div class="ni on" onclick="goPage('service_selection.php')"></div>
            <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
            <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function toast(msg, type = 's') {
            const t = document.createElement('div');
            t.className = `toast-n ${type}`;
            t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
            document.getElementById('toastBox').appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
        }

        function getPaymentMethodIcon(method) {
            const icons = {
                'cash': 'bi-cash-coin',
                'gcash': 'bi-wallet2',
                'bank': 'bi-bank2'
            };
            return icons[method] || 'bi-credit-card';
        }

        function getPaymentMethodLabel(method) {
            const labels = {
                'cash': 'Cash',
                'gcash': 'GCash',
                'bank': 'Bank Transfer'
            };
            return labels[method] || 'Unknown';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatCurrency(amount) {
            return '₱' + parseFloat(amount).toFixed(2);
        }

        async function loadPayments() {
            try {
                const response = await fetch('../api/payments_api.php?action=list&limit=50');
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('paymentListContainer').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                            <div>${data.message || 'No payments found'}</div>
                        </div>
                    `;
                    return;
                }

                // Load statistics
                await loadStatistics();

                // Render payment list
                if (data.payments.length === 0) {
                    document.getElementById('paymentListContainer').innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                            <div>No payments recorded yet</div>
                        </div>
                    `;
                    return;
                }

                let html = '';
                data.payments.forEach(payment => {
                    const methodLabel = getPaymentMethodLabel(payment.payment_method);
                    const methodIcon = getPaymentMethodIcon(payment.payment_method);
                    const methodClass = `method-${payment.payment_method}`;
                    const statusClass = `status-${payment.payment_status}`;

                    html += `
                        <div class="payment-history-card">
                            <div class="payment-details">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <div style="font-size: 18px; color: #e8820c;">
                                        <i class="bi ${methodIcon}"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 14px; font-weight: 600; color: #1a1a2e;">
                                            ${payment.service}
                                        </div>
                                        <div class="payment-date">Booking #${payment.booking_id}</div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <span class="payment-method-badge ${methodClass}">
                                        <i class="bi ${methodIcon}" style="font-size: 12px;"></i>
                                        ${methodLabel}
                                    </span>
                                    <span class="status-badge ${statusClass}">
                                        ${payment.payment_status}
                                    </span>
                                </div>
                                <div class="payment-date" style="margin-top: 8px;">
                                    ${formatDate(payment.created_at)}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="payment-amount">${formatCurrency(payment.amount)}</div>
                                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">
                                    TXN: ${payment.transaction_id}
                                </div>
                            </div>
                        </div>
                    `;
                });

                document.getElementById('paymentListContainer').innerHTML = html;
            } catch (error) {
                console.error('Error loading payments:', error);
                toast('Failed to load payments', 'e');
            }
        }

        async function loadStatistics() {
            try {
                const response = await fetch('../api/payments_api.php?action=stats');
                const data = await response.json();

                if (!data.success || !data.statistics) return;

                const stats = data.statistics;
                let statsHtml = `
                    <div class="stat-card">
                        <div class="stat-label">Total Payments</div>
                        <div class="stat-value">${stats.total_payments}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #ef4444;">
                        <div class="stat-label">Total Amount</div>
                        <div class="stat-value">${formatCurrency(stats.total_amount)}</div>
                    </div>
                `;

                document.getElementById('statsContainer').innerHTML = statsHtml;
                document.getElementById('statsContainer').style.display = 'grid';
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Load payments on page load
        document.addEventListener('DOMContentLoaded', loadPayments);
    </script>
</body>
</html>
