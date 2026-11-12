"""
WebRajya ERP - MySQL to Supabase Data Export Script
Reads all data from MySQL (cavault) and generates supabase/seed_data.sql
ready to be executed in Supabase SQL editor.
"""

import os
import json
import pymysql

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_USER = os.getenv("DB_USERNAME", "root")
DB_PASS = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_DATABASE", "cavault")

output_file = "supabase/seed_data.sql"

def escape_sql(val):
    if val is None:
        return "NULL"
    if isinstance(val, (int, float)):
        return str(val)
    if isinstance(val, bool):
        return "true" if val else "false"
    if isinstance(val, (dict, list)):
        val_str = json.dumps(val)
        return "'" + val_str.replace("'", "''") + "'::JSONB"
    # Format strings and dates
    s = str(val).replace("'", "''")
    return f"'{s}'"

def main():
    try:
        conn = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME,
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor
        )
    except Exception as e:
        print(f"Failed to connect to MySQL: {e}")
        return

    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    
    with conn.cursor() as cursor, open(output_file, "w", encoding="utf-8") as out:
        out.write("-- WebRajya ERP - Data Seed from MySQL\n")
        out.write("BEGIN;\n\n")

        # 1. Firm Settings
        cursor.execute("SELECT * FROM firm_settings")
        rows = cursor.fetchall()
        if rows:
            out.write("-- 1. Firm Settings\n")
            for r in rows:
                out.write(f"INSERT INTO public.firm_settings (key, value, group_name) VALUES ({escape_sql(r['key'])}, {escape_sql(r['value'])}, {escape_sql(r.get('group', 'firm'))}) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;\n")
            out.write("\n")

        # 2. GST Return Types
        cursor.execute("SELECT * FROM gst_return_types")
        rows = cursor.fetchall()
        if rows:
            out.write("-- 2. GST Return Types\n")
            for r in rows:
                out.write(f"INSERT INTO public.gst_return_types (id, code, name, frequency, default_due_day, default_due_month_offset, description, is_active) VALUES ({r['id']}, {escape_sql(r['code'])}, {escape_sql(r['name'])}, {escape_sql(r['frequency'])}, {r['default_due_day']}, {r['default_due_month_offset']}, {escape_sql(r['description'])}, {escape_sql(r['is_active'])}) ON CONFLICT (id) DO NOTHING;\n")
            out.write("\n")

        # 3. Compliance Rules
        cursor.execute("SELECT * FROM compliance_rules")
        rows = cursor.fetchall()
        if rows:
            out.write("-- 3. Compliance Rules\n")
            for r in rows:
                out.write(f"INSERT INTO public.compliance_rules (id, name, compliance_type, frequency, due_day, due_month, month_offset, reminder_days_before, description, is_active) VALUES ({r['id']}, {escape_sql(r['name'])}, {escape_sql(r['compliance_type'])}, {escape_sql(r['frequency'])}, {r['due_day']}, {escape_sql(r['due_month'])}, {r['month_offset']}, {r['reminder_days_before']}, {escape_sql(r['description'])}, {escape_sql(r['is_active'])}) ON CONFLICT (id) DO NOTHING;\n")
            out.write("\n")

        out.write("COMMIT;\n")
        print(f"Data export completed successfully -> {output_file}")

if __name__ == "__main__":
    main()
