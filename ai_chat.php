<?php
error_reporting(0);
ini_set('display_errors', 0);

// PHP 8: disable mysqli exceptions so failed queries return false instead of crashing
mysqli_report(MYSQLI_REPORT_OFF);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "db.php";

// Global safety net
set_exception_handler(function($e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    exit();
});

/* ─── INPUT ─── */
$input    = json_decode(file_get_contents("php://input"), true);
$user_id  = isset($input["user_id"])  ? (int)$input["user_id"]  : 0;
$org_code = isset($input["org_code"]) ? trim($input["org_code"]) : "";
$message  = isset($input["message"])  ? trim($input["message"])  : "";

if ($user_id <= 0 || $org_code === "" || $message === "") {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit();
}

$text = strtolower($message);

/* ─── HELPERS ─── */
function respond($reply, $intent = "general") {
    echo json_encode(["status" => "success", "reply" => $reply, "intent" => $intent], JSON_UNESCAPED_UNICODE);
    exit();
}

function has($text, $keywords) {
    foreach ($keywords as $kw) {
        if (strpos($text, $kw) !== false) return true;
    }
    return false;
}

/* ─── 1) USER PROFILE ─── */
$userName = "there";
$user     = array();
$s = $conn->prepare("SELECT full_name, email, phone_number, flat FROM users WHERE id = ? LIMIT 1");
if ($s) {
    $s->bind_param("i", $user_id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if ($row) {
        $user     = $row;
        $userName = $row["full_name"] ?: "there";
    }
    $s->close();
}

/* ─── 2) ORGANIZATION (simple query, no JOIN) ─── */
$org     = array();
$orgName = "your organization";
$orgId   = 0;
$s = $conn->prepare("SELECT id, org_name, org_code, city, state, address, contact_phone, contact_email, description FROM organizations WHERE org_code = ? LIMIT 1");
if ($s) {
    $s->bind_param("s", $org_code);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if ($row) {
        $org     = $row;
        $orgName = $row["org_name"] ?: "your organization";
        $orgId   = (int)$row["id"];
    }
    $s->close();
}

/* ─── 3) PARKING CONFIG ─── */
$parking = array();
if ($orgId > 0) {
    $s = $conn->prepare("SELECT total_slots, two_wheeler_slots, four_wheeler_slots, visitor_slots, disabled_slots, ev_slots, parking_hours, parking_rules FROM organization_parking WHERE organization_id = ? LIMIT 1");
    if ($s) {
        $s->bind_param("i", $orgId);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        if ($row) $parking = $row;
        $s->close();
    }
}

/* ─── 4) USER VEHICLES ─── */
$vehicles = array();
$s = $conn->prepare("SELECT vehicle_number, vehicle_type, parking_slot, pillar_number, landmark, floor, zone_name FROM user_vehicles WHERE user_id = ? AND org_code = ? AND status = 'ACTIVE' ORDER BY id DESC");
if ($s) {
    $s->bind_param("is", $user_id, $org_code);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) $vehicles[] = $row;
    $s->close();
}

/* ─── 5) VISITOR PASSES ─── */
$passes = array();
$s = $conn->prepare("SELECT visitor_name, vehicle_number, purpose, duration_hours, status, pass_code FROM visitor_passes WHERE user_id = ? ORDER BY id DESC LIMIT 5");
if ($s) {
    $s->bind_param("i", $user_id);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) $passes[] = $row;
    $s->close();
}

/* ─── 6) EMERGENCY CONTACTS ─── */
$emergency = array();
$s = $conn->prepare("SELECT * FROM emergency_contacts WHERE org_code = ? LIMIT 10");
if ($s) {
    $s->bind_param("s", $org_code);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) $emergency[] = $row;
    $s->close();
}

/* ─── 7) COMMUNITY POSTS ─── */
$posts = array();
$s = $conn->prepare("SELECT title, description, author_name FROM community_posts WHERE org_code = ? ORDER BY id DESC LIMIT 3");
if ($s) {
    $s->bind_param("s", $org_code);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) $posts[] = $row;
    $s->close();
}

/* ─── 8) APPROVED MEMBER COUNT ─── */
$memberCount = 0;
$s = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_organizations WHERE org_code = ? AND status = 'APPROVED'");
if ($s) {
    $s->bind_param("s", $org_code);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if ($row) $memberCount = (int)$row["cnt"];
    $s->close();
}

/* ─── VEHICLE FORMATTER ─── */
function fmtVehicle($v) {
    $line = ($v["vehicle_number"] ?: "—");
    if (!empty($v["vehicle_type"])) $line .= " (" . $v["vehicle_type"] . ")";
    $parts = array();
    if (!empty($v["zone_name"]))    $parts[] = "Zone " . $v["zone_name"];
    if (!empty($v["floor"]))        $parts[] = "Floor " . $v["floor"];
    if (!empty($v["parking_slot"])) $parts[] = "Slot " . $v["parking_slot"];
    if (!empty($v["pillar_number"])) $parts[] = "Pillar " . $v["pillar_number"];
    if (!empty($v["landmark"]))     $parts[] = $v["landmark"];
    if (!empty($parts)) $line .= " — " . implode(", ", $parts);
    return $line;
}

/* ─────────── INTENT MATCHING ─────────── */

/* GREETING */
if (has($text, array("hi", "hello", "hey", "good morning", "good evening", "good afternoon"))) {
    $vCount = count($vehicles);
    $aCount = 0;
    foreach ($passes as $p) { if (strtoupper($p["status"]) === "ACTIVE") $aCount++; }
    respond(
        "Hi $userName! 👋 Welcome to SmartPark AI.\n\n" .
        "📊 Your account at a glance:\n" .
        "🏢 Organization: $orgName\n" .
        (!empty($user["flat"]) ? "🏠 Flat: " . $user["flat"] . "\n" : "") .
        "🚗 Vehicles registered: $vCount\n" .
        "🎫 Active visitor passes: $aCount\n\n" .
        "What can I help you with today?",
        "greeting"
    );
}

/* HELP */
if (has($text, array("help", "what can you do", "commands", "options", "features"))) {
    respond(
        "Here's what I can help with:\n\n" .
        "🚗 'my vehicles' — Your registered vehicles\n" .
        "📍 'parking location' — Where your car is parked\n" .
        "🎫 'visitor passes' — Your recent guest passes\n" .
        "🏢 'organization' — Details about $orgName\n" .
        "🅿️ 'parking info' — Slot counts & hours\n" .
        "📋 'rules' — Parking rules\n" .
        "🆘 'emergency' — Emergency contacts\n" .
        "💬 'community' — Latest announcements\n" .
        "👤 'my profile' — Your account info",
        "help"
    );
}

/* MY VEHICLES */
if (has($text, array("my vehicle", "my car", "list vehicle", "show vehicle", "which vehicle", "vehicles i have"))) {
    if (empty($vehicles)) {
        respond("You don't have any active vehicles registered in $orgName yet.\n\nUse Find My Vehicle in the app to add one.", "my_vehicles");
    }
    $lines = array();
    foreach ($vehicles as $v) $lines[] = "• " . fmtVehicle($v);
    respond("Your registered vehicles in $orgName (" . count($vehicles) . " total):\n\n" . implode("\n", $lines), "my_vehicles");
}

/* PARKING LOCATION */
if (has($text, array("where is my", "parking location", "where did i park", "my slot", "my floor", "my zone", "find my car", "find my vehicle", "parked at"))) {
    if (empty($vehicles)) {
        respond("You haven't registered any vehicles in $orgName yet.", "parking_location");
    }
    $v = $vehicles[0];
    $parts = array();
    if (!empty($v["zone_name"]))    $parts[] = "Zone: " . $v["zone_name"];
    if (!empty($v["floor"]))        $parts[] = "Floor: " . $v["floor"];
    if (!empty($v["parking_slot"])) $parts[] = "Slot: " . $v["parking_slot"];
    if (!empty($v["pillar_number"])) $parts[] = "Pillar: " . $v["pillar_number"];
    if (!empty($v["landmark"]))     $parts[] = "Landmark: " . $v["landmark"];
    if (empty($parts)) {
        respond("Your vehicle " . $v["vehicle_number"] . " is registered but parking location details haven't been filled in yet.", "parking_location");
    }
    $extra = count($vehicles) > 1 ? "\n(You have " . count($vehicles) . " vehicles total — ask 'my vehicles' to see all.)" : "";
    respond("🚗 " . $v["vehicle_number"] . "\n📍 Parking location:\n" . implode("\n", $parts) . $extra, "parking_location");
}

/* VISITOR PASSES */
if (has($text, array("visitor pass", "my passes", "visitor", "guest pass", "guest"))) {
    if (empty($passes)) {
        respond("You haven't created any visitor passes yet.\n\nGo to Visitor Parking in the app to create one.", "visitor_passes");
    }
    $aCount = 0;
    foreach ($passes as $p) { if (strtoupper($p["status"]) === "ACTIVE") $aCount++; }
    $lines = array();
    foreach ($passes as $p) {
        $st   = strtoupper($p["status"] ?: "");
        $icon = $st === "ACTIVE" ? "✅" : ($st === "PENDING" ? "⏳" : "❌");
        $line = "$icon " . ($p["visitor_name"] ?: "Visitor");
        if (!empty($p["vehicle_number"]))  $line .= " — " . $p["vehicle_number"];
        if (!empty($p["purpose"]))         $line .= " (" . $p["purpose"] . ")";
        if (!empty($p["duration_hours"]))  $line .= " · " . $p["duration_hours"] . "h";
        if (!empty($p["pass_code"]))       $line .= "\n   Code: " . $p["pass_code"];
        $lines[] = $line;
    }
    respond("Your recent visitor passes ($aCount active):\n\n" . implode("\n\n", $lines), "visitor_passes");
}

/* ORGANIZATION */
if (has($text, array("organization", "my org", "current org", "org info", "org name", "which org", "about org"))) {
    if (empty($org)) respond("I couldn't find your organization details.", "organization");
    $reply = "🏢 $orgName (Code: $org_code)";
    if (!empty($org["city"]))          $reply .= "\n📍 " . $org["city"] . (!empty($org["state"]) ? ", " . $org["state"] : "");
    if (!empty($org["address"]))       $reply .= "\n🗺️ " . $org["address"];
    if (!empty($org["contact_phone"])) $reply .= "\n📞 " . $org["contact_phone"];
    if (!empty($org["contact_email"])) $reply .= "\n📧 " . $org["contact_email"];
    if (!empty($org["description"]))   $reply .= "\n\nℹ️ " . $org["description"];
    $reply .= "\n\n👥 Members: $memberCount approved residents";
    respond($reply, "organization");
}

/* PARKING INFO / SLOTS */
if (has($text, array("total slot", "how many slot", "slot available", "parking slot", "ev slot", "visitor slot", "parking hour", "two wheeler", "four wheeler", "parking info"))) {
    $reply = "🅿️ Parking at $orgName:";
    if (!empty($parking["total_slots"]))        $reply .= "\n• Total slots: " . $parking["total_slots"];
    if (!empty($parking["two_wheeler_slots"]))  $reply .= "\n• Two-wheeler: " . $parking["two_wheeler_slots"];
    if (!empty($parking["four_wheeler_slots"])) $reply .= "\n• Four-wheeler: " . $parking["four_wheeler_slots"];
    if (!empty($parking["visitor_slots"]))      $reply .= "\n• Visitor slots: " . $parking["visitor_slots"];
    if (!empty($parking["disabled_slots"]))     $reply .= "\n• Disabled slots: " . $parking["disabled_slots"];
    if (!empty($parking["ev_slots"]))           $reply .= "\n• EV charging: " . $parking["ev_slots"];
    if (!empty($parking["parking_hours"]))      $reply .= "\n\n🕐 Hours: " . $parking["parking_hours"];
    if ($reply === "🅿️ Parking at $orgName:") {
        $reply .= "\nParking configuration hasn't been set up yet. Ask your admin to configure it.";
    }
    respond($reply, "parking_info");
}

/* RULES */
if (has($text, array("rule", "guideline", "policy", "regulation"))) {
    $rText = isset($parking["parking_rules"]) ? trim($parking["parking_rules"]) : "";
    if (empty($rText)) {
        respond("No parking rules configured for $orgName yet. Check community posts for announcements.", "rules");
    }
    $lines = array_filter(array_map("trim", explode("\n", $rText)));
    $reply = "📋 Parking rules for $orgName:\n\n";
    $i = 1;
    foreach ($lines as $l) { $reply .= "$i. $l\n"; $i++; }
    respond(trim($reply), "rules");
}

/* EMERGENCY */
if (has($text, array("emergency", "sos", "helpline", "urgent", "security number", "help number", "danger"))) {
    $reply = "🆘 Emergency contacts:\n\nNational:\n• 112 — Emergency\n• 100 — Police\n• 101 — Fire\n• 108 — Ambulance\n• 1033 — Road Accident";
    if (!empty($emergency)) {
        $reply .= "\n\n📞 $orgName contacts:";
        foreach ($emergency as $e) {
            $name  = isset($e["name"])  ? $e["name"]  : (isset($e["label"]) ? $e["label"] : "Contact");
            $phone = "";
            foreach (array("phone", "number", "contact_number", "phone_number") as $k) {
                if (!empty($e[$k])) { $phone = $e[$k]; break; }
            }
            $role = isset($e["role"]) ? $e["role"] : (isset($e["designation"]) ? $e["designation"] : "");
            $reply .= "\n• $name" . ($role ? " ($role)" : "") . ($phone ? ": $phone" : "");
        }
    } else {
        $reply .= "\n\nNo org contacts added yet. Your admin can add them in the Emergency section.";
    }
    respond($reply, "emergency");
}

/* COMMUNITY */
if (has($text, array("community", "announcement", "notice", "post", "news"))) {
    if (empty($posts)) {
        respond("No community posts in $orgName yet. Admins can post announcements in the Community section.", "community");
    }
    $reply = "💬 Latest community posts from $orgName:\n\n";
    foreach ($posts as $p) {
        $reply .= "📌 " . ($p["title"] ?: "Untitled") . "\n";
        if (!empty($p["description"])) {
            $desc = mb_substr($p["description"], 0, 120);
            $reply .= "   " . $desc . (mb_strlen($p["description"]) > 120 ? "…" : "") . "\n";
        }
        if (!empty($p["author_name"])) $reply .= "   — " . $p["author_name"] . "\n";
        $reply .= "\n";
    }
    respond(trim($reply), "community");
}

/* PROFILE */
if (has($text, array("my profile", "my name", "my email", "my phone", "my flat", "my account", "my details"))) {
    $reply = "👤 Your profile:\n";
    $reply .= "• Name: " . (isset($user["full_name"]) ? $user["full_name"] : "—") . "\n";
    $reply .= "• Email: " . (isset($user["email"]) ? $user["email"] : "—") . "\n";
    $reply .= "• Phone: " . (isset($user["phone_number"]) ? $user["phone_number"] : "—") . "\n";
    if (!empty($user["flat"])) $reply .= "• Flat: " . $user["flat"] . "\n";
    $reply .= "\nTo update details, go to Profile in the app.";
    respond($reply, "profile");
}

/* MEMBERS */
if (has($text, array("how many member", "total member", "member count", "resident", "how many resident"))) {
    respond("👥 $orgName has $memberCount approved members.", "members");
}

/* THANKS */
if (has($text, array("thank", "thanks", "thank you", "ty"))) {
    respond("You're welcome, $userName! 😊 Feel free to ask anything else.", "thanks");
}

/* GOODBYE */
if (has($text, array("bye", "goodbye", "see you", "later"))) {
    respond("Goodbye, $userName! 👋 Stay safe and happy parking! 🚗", "goodbye");
}

/* ─── FALLBACK ─── */
$vNums = array();
foreach ($vehicles as $v) { if (!empty($v["vehicle_number"])) $vNums[] = $v["vehicle_number"]; }
$fallback = "I'm not sure about that. Here's a quick snapshot of your account:\n\n";
if (!empty($vNums)) $fallback .= "🚗 Vehicles: " . implode(", ", $vNums) . "\n";
$fallback .= "🎫 Visitor passes: " . count($passes) . " total\n";
$fallback .= "🏢 Org: $orgName ($org_code)\n\n";
$fallback .= "Try asking:\n• 'my vehicles'\n• 'parking location'\n• 'visitor passes'\n• 'emergency contacts'\n• 'parking rules'";

respond($fallback, "fallback");
