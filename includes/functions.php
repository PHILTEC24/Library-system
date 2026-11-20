<?php
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = in_array($flash['type'], ['error', 'danger']) ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning');
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function flashMessage() {
    displayFlash();
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_admin() {
    return isAdmin();
}

function page_header($title, $subtitle = '', $actions = '') {
    $html = '<div class="lms-page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">';
    $html .= '<div>';
    $html .= '<h1 class="lms-page-header__title mb-1">' . e($title) . '</h1>';
    if ($subtitle !== '') {
        $html .= '<p class="lms-page-header__subtitle text-muted mb-0">' . e($subtitle) . '</p>';
    }
    $html .= '</div>';
    if ($actions !== '') {
        $html .= '<div class="lms-page-header__actions d-flex flex-wrap gap-2 align-items-center">' . $actions . '</div>';
    }
    $html .= '</div>';
    return $html;
}

function pluralize($count, $singular, $plural = null) {
    $count = (int)$count;
    if ($plural === null) {
        $plural = $singular . 's';
    }
    return number_format($count) . ' ' . ($count === 1 ? $singular : $plural);
}

function format_date($date, $format = 'd M Y') {
    if (!$date || $date === '0000-00-00') return '—';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : e($date);
}

function relative_due($dueDate, $dateReturned = null) {
    if ($dateReturned) {
        return '<span class="text-muted">Returned</span>';
    }
    $today = new DateTime('today');
    $due = new DateTime($dueDate);
    $diffDays = (int)$today->diff($due)->format('%r%a');
    
    if ($diffDays < 0) {
        $overdue = abs($diffDays);
        $finePercent = $overdue * 5;
        return '<span class="text-danger fw-semibold">' . $overdue . ' ' . ($overdue === 1 ? 'day' : 'days') . ' overdue</span> <span class="badge bg-danger-subtle text-danger ms-1">' . $finePercent . '% fine</span>';
    } elseif ($diffDays === 0) {
        return '<span class="text-warning fw-semibold">Due today</span>';
    } elseif ($diffDays <= 3) {
        return '<span class="text-warning fw-medium">Due in ' . $diffDays . ' ' . ($diffDays === 1 ? 'day' : 'days') . '</span>';
    } else {
        return '<span class="text-muted">in ' . $diffDays . ' days</span>';
    }
}

function spine_tile($title, $categoryId = 1, $size = 'sm') {
    $cleanTitle = trim((string)$title);
    $firstChar = mb_strtoupper(mb_substr($cleanTitle, 0, 1, 'UTF-8'), 'UTF-8');
    if ($firstChar === '' || !preg_match('/[A-Z0-9]/i', $firstChar)) {
        $firstChar = '•';
    }
    $catIdx = (abs((int)$categoryId) % 8) + 1;
    $sizeClass = in_array($size, ['sm', 'md', 'lg']) ? 'lms-spine--' . $size : 'lms-spine--sm';
    return '<span class="lms-spine ' . $sizeClass . ' lms-spine--cat-' . $catIdx . '" title="' . e($cleanTitle) . '" aria-hidden="true">' .
           '<span class="lms-spine__binding"></span>' .
           '<span class="lms-spine__cover">' .
               '<span class="lms-spine__emboss">' .
                   '<span class="lms-spine__letter">' . e($firstChar) . '</span>' .
               '</span>' .
           '</span>' .
           '<span class="lms-spine__pages"></span>' .
           '<span class="lms-spine__ribbon"></span>' .
           '</span>';
}

function avatar($name, $size = 36) {
    $name = trim((string)$name);
    $parts = preg_split('/\s+/', $name);
    $initials = '';
    if (!empty($parts[0])) {
        $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8');
    }
    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8'), 'UTF-8');
    }
    if ($initials === '') {
        $initials = 'U';
    }
    $palette = ['#3F5BA9', '#2E7D6B', '#8C4A6B', '#A85A17', '#5A6B3A', '#5E548E'];
    $bg = $palette[abs(crc32($name)) % count($palette)];
    $fontSize = max(11, round($size * 0.38));
    return '<span class="lms-avatar" style="width:' . $size . 'px; height:' . $size . 'px; font-size:' . $fontSize . 'px; background-color:' . $bg . ';" aria-hidden="true">' .
           e($initials) .
           '</span>';
}

function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            return false;
        }
    }
    return true;
}

function generateMemberNumber($pdo = null) {
    do {
        $number = 'MEM' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        if (!$pdo) {
            return $number;
        }
        $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_no = ? LIMIT 1');
        $stmt->execute([$number]);
    } while ($stmt->fetch());
    return $number;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Returns the status key string for a loan row array.
 * Uses keys: date_returned, due_date
 */
function loan_status(array $loan): string {
    if (!empty($loan['date_returned'])) return 'returned';
    $today = date('Y-m-d');
    if ($loan['due_date'] < $today) return 'overdue';
    $threeDays = date('Y-m-d', strtotime('+3 days'));
    if ($loan['due_date'] <= $threeDays) return 'due_soon';
    return 'active';
}

/**
 * Returns an .lms-pill HTML span for a loan/status label.
 * $variant: 'success' | 'danger' | 'warning' | 'info' | 'neutral'
 */
function status_pill(string $label, string $variant = 'neutral'): string {
    $allowed = ['success','danger','warning','info','neutral'];
    $v = in_array($variant, $allowed) ? $variant : 'neutral';
    return '<span class="lms-pill lms-pill--' . $v . '">' . e($label) . '</span>';
}

/**
 * Returns the availability meter HTML (.lms-avail) for a book.
 * Shows pip circles + text count.
 */
function availability_meter(int $available, int $total): string {
    if ($total <= 0) {
        return '<span class="text-muted small">—</span>';
    }
    $pct = ($available > 0) ? ($available / $total) : 0.0;
    $stateClass = ($pct === 0.0) ? 'lms-avail--danger' : ($pct <= 0.4 ? 'lms-avail--warning' : 'lms-avail--success');
    $maxPips = min($total, 5);
    $filledPips = ($total <= 5) ? $available : (int)round($pct * 5);
    $html = '<div class="lms-avail ' . $stateClass . '">';
    $html .= '<div class="lms-avail-pips">';
    for ($i = 0; $i < $maxPips; $i++) {
        $html .= '<span class="lms-pip' . ($i < $filledPips ? ' is-filled' : '') . '"></span>';
    }
    $html .= '</div>';
    $html .= '<span class="lms-avail-label">' . $available . ' / ' . $total . ' available</span>';
    $html .= '</div>';
    return $html;
}
