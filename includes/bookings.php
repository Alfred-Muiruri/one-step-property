<?php
/**
 * Confirm or reject a pending booking and tell the tenant.
 * $onlyLandlordId: if set, the booking must belong to a house owned by that landlord.
 * Returns true on success.
 */
function set_booking_status($bookingId, $newStatus, $onlyLandlordId = null) {
    if (!in_array($newStatus, ['confirmed', 'rejected'], true)) return false;
    $pdo = db();

    $s = $pdo->prepare(
        'SELECT b.*, h.title, h.landlord_id, h.location, h.price FROM bookings b JOIN houses h ON h.id=b.house_id WHERE b.id=?'
    );
    $s->execute([$bookingId]);
    $b = $s->fetch();
    if (!$b || $b['status'] !== 'pending') return false;
    if ($onlyLandlordId !== null && (int)$b['landlord_id'] !== (int)$onlyLandlordId) return false;

    $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$newStatus, $bookingId]);

    // Office staff / admin acting for the landlord: log it and tell the landlord.
    if ($onlyLandlordId === null) {
        log_activity('Booking ' . $newStatus, '#' . $bookingId . ' · ' . $b['title']);
        $lq = $pdo->prepare('SELECT * FROM users WHERE id=?'); $lq->execute([$b['landlord_id']]);
        if ($ll = $lq->fetch()) {
            notify_user($ll, 'Booking ' . $newStatus . ': ' . $b['title'],
                'The One Step office has ' . $newStatus . ' a booking for "' . $b['title'] . '" on your behalf.', 'View bookings', 'landlord/bookings.php');
        }
    }

    $t = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $t->execute([$b['tenant_id']]);
    $tenant = $t->fetch();

    if ($newStatus === 'confirmed') {
        $pdo->prepare("UPDATE houses SET status='booked' WHERE id=?")->execute([$b['house_id']]);

        // Reject everyone else who was waiting for this house and tell them.
        $others = $pdo->prepare("SELECT b.id AS booking_id, u.* FROM bookings b JOIN users u ON u.id=b.tenant_id WHERE b.house_id=? AND b.id<>? AND b.status='pending'");
        $others->execute([$b['house_id'], $bookingId]);
        foreach ($others->fetchAll() as $o) {
            $pdo->prepare("UPDATE bookings SET status='rejected' WHERE id=?")->execute([$o['booking_id']]);
            notify_user($o, 'Booking update: ' . $b['title'],
                'Sorry, "' . $b['title'] . '" has just been taken by another tenant. Please browse our other available houses.',
                'Browse houses', 'houses.php');
        }
        // Agent commission: earned when a booking they brought in is confirmed.
        if (!empty($b['agent_id'])) {
            $ap = $pdo->prepare('SELECT u.*, tp.commission_percent FROM users u LEFT JOIN team_profiles tp ON tp.user_id=u.id WHERE u.id=? AND u.role=\'agent\'');
            $ap->execute([$b['agent_id']]);
            if ($agent = $ap->fetch()) {
                $pct = (float)($agent['commission_percent'] ?? 0);
                $amount = round($b['price'] * $pct / 100, 2);
                if ($pct > 0) {
                    $ins = $pdo->prepare('INSERT IGNORE INTO commissions (agent_id, booking_id, house_title, rent, percent, amount) VALUES (?,?,?,?,?,?)');
                    $ins->execute([$agent['id'], $bookingId, $b['title'], $b['price'], $pct, $amount]);
                    if ($ins->rowCount()) {
                        notify_user($agent, 'Commission earned: ' . $b['title'],
                            'Your booking for "' . $b['title'] . '" was confirmed. Commission earned: KES ' . number_format($amount) . ' (' . $pct . '% of rent). It will be marked paid once processed.',
                            'My commissions', 'agent/commissions.php');
                    }
                }
            }
        }
        if ($tenant) {
            $msg = 'Good news! Your booking for "' . $b['title'] . '" (' . $b['location'] . ') has been CONFIRMED.';
            if (PAY_INSTRUCTIONS !== '') $msg .= "\n\n" . PAY_INSTRUCTIONS;
            notify_user($tenant, 'Booking confirmed: ' . $b['title'], $msg, 'View my bookings', 'tenant/my_bookings.php');
        }
    } elseif ($tenant) {
        notify_user($tenant, 'Booking update: ' . $b['title'],
            'Unfortunately your booking request for "' . $b['title'] . '" could not be accepted. You are welcome to try another house.',
            'Browse houses', 'houses.php');
    }
    return true;
}
