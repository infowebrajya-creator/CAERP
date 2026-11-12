<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FirmSetting;
use App\Models\GstReturnType;
use App\Models\ComplianceRule;

$out = fopen(__DIR__ . '/../seed_data.sql', 'w');
fwrite($out, "-- ============================================================================\n");
fwrite($out, "-- WebRajya ERP - Initial Supabase Seed Data\n");
fwrite($out, "-- ============================================================================\n\nBEGIN;\n\n");

function sql_val($v) {
    if (is_null($v)) return "NULL";
    if (is_bool($v)) return $v ? "true" : "false";
    if (is_numeric($v)) return (string)$v;
    if (is_array($v)) return "'" . str_replace("'", "''", json_encode($v)) . "'::JSONB";
    return "'" . str_replace("'", "''", (string)$v) . "'";
}

// 1. Firm Settings
fwrite($out, "-- 1. Firm Settings\n");
foreach (FirmSetting::all() as $fs) {
    fwrite($out, "INSERT INTO public.firm_settings (key, value, group_name) VALUES (" . sql_val($fs->key) . ", " . sql_val($fs->value) . ", " . sql_val($fs->group ?? 'firm') . ") ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;\n");
}
fwrite($out, "\n");

// 2. GST Return Types
fwrite($out, "-- 2. GST Return Types\n");
foreach (GstReturnType::all() as $rt) {
    fwrite($out, "INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES ({$rt->id}, " . sql_val($rt->code) . ", " . sql_val($rt->name) . ", " . sql_val($rt->frequency) . ", {$rt->default_due_day}, {$rt->default_due_month_offset}, " . sql_val($rt->description) . ", " . sql_val($rt->is_active) . ") ON CONFLICT (id) DO NOTHING;\n");
}
fwrite($out, "\n");

// 3. Compliance Rules
fwrite($out, "-- 3. Compliance Rules\n");
foreach (ComplianceRule::all() as $cr) {
    fwrite($out, "INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES ({$cr->id}, " . sql_val($cr->name) . ", " . sql_val($cr->compliance_type) . ", " . sql_val($cr->frequency) . ", {$cr->due_day}, " . sql_val($cr->due_month) . ", {$cr->month_offset}, {$cr->reminder_days_before}, " . sql_val($cr->description) . ", " . sql_val($cr->is_active) . ") ON CONFLICT (id) DO NOTHING;\n");
}
fwrite($out, "\nCOMMIT;\n");
fclose($out);

echo "Successfully exported seed_data.sql\n";
