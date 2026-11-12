# WebRajya ERP — Accounting Practice Management Software

A complete, responsive **Practice Management ERP for Accounting Professionals** built with **Laravel 13**, **PHP 8.4**, **Bootstrap 5**, **Chart.js**, **DomPDF**, and **MySQL / SQLite**.

## Modules Implemented

1. **Light CA Design & Custom SVG Branding**: Minimalist light theme (`#f4f7fb` / `#ffffff`, soft navy `#1e3a5f`, muted teal `#0d9488`, sky-blue `#0284c7`) and a custom document + growth + checkmark SVG logo ([`ca-logo.blade.php`](resources/views/components/ca-logo.blade.php)).
2. **Role-Based Access Control (5 Roles)**: Super Admin, CA / Partner, Manager, Accountant / Employee, and Client (with strict client-data isolation).
3. **Practice Dashboard**: 12 live database KPI cards, 4 Chart.js analytics charts (Monthly Revenue, Compliance Completion, Client Growth, Employee Workload), Upcoming Deadlines, and Recent Activity feed.
4. **Client Management CRM**: Individual & business client categories, PAN/GSTIN/TAN/CIN, relationship managers, secondary team assignments, optional Client Portal account creation, and 6-tab client profiles (Overview, Compliance, Documents, Tasks, Invoices, Communication).
5. **GST Compliance Management**: Configurable GST return types (`gst_return_types`), monthly/quarterly/annual tracking, checklist items, ARN & filing date recording, GST calendar view, and multi-criteria filtering.
6. **Income Tax Return (ITR) Management**: AY/FY tracking, ITR-1 through ITR-7, document checklists, filing status, acknowledgement numbers, and refund tracking.
7. **TDS Management**: Quarterly Form 24Q, 26Q, 27Q & 27EQ tracking, deduction/deposited amounts, Challan & BSR details.
8. **Deadline & Compliance Calendar**: Monthly calendar grid, overdue tracking, custom deadlines, and configurable due-date rules (`compliance_rules`).
9. **Client Document Management**: Secure private disk storage (`storage/app/private/documents`), document requests, versioning (`document_versions`), approve/reject workflow, and authenticated downloads.
10. **Audit Management**: Assignment tracking, audit team members, interactive checklists with automatic progress calculation, working paper uploads/downloads, and partner review/approval.
11. **Billing & Payment Management**: GST-compliant invoicing (`CGST + SGST`, `IGST`, or `Exempt`), discounts, payment recording, outstanding balance tracking, and downloadable PDF invoices via DomPDF.
12. **Employee & Staff Management**: Staff CRUD, client allocations, workload metrics, and Task Review & Approval Queue.
13. **Task & Workflow Management**: Interactive Kanban Board (drag-and-drop status updates) + Table View, comments, attachments, and task activity logs.
14. **Client Communication & Notifications**: Portal messaging, Laravel Mail reminders, database notifications, and an integration-ready WhatsApp Business Cloud API service layer (`MetaWhatsAppCloudService`).
15. **Client Portal (`/portal/...`)**: Isolated client login area for profile viewing, uploading requested documents, tracking GST/ITR/TDS filings & deadlines, downloading PDF invoices, and messaging the CA firm.
16. **Reports & Analytics**: 10 practice reports with date filters and UTF-8 BOM CSV/Excel exports.

## Local Setup

1. Install dependencies & build assets:
   ```bash
   composer install
   npm install && npm run build
   ```
2. Database configuration:
   - Out-of-the-box local demo uses SQLite (`DB_CONNECTION=sqlite` in `.env`, because no MySQL server is pre-installed in this environment).
   - For **MySQL**, update `.env` (`DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) — all migrations and queries are MySQL-compatible (see `.env.example`).
3. Migrate, seed demo data, and serve:
   ```bash
   php artisan migrate:fresh --seed
   php artisan serve
   ```
4. Run tests:
   ```bash
   php artisan test
   ```

## Demo Credentials (Password: `password`)

- **Super Admin**: `admin@webrajya.in`
- **Principal / Partner**: `partner@webrajya.in`
- **Manager**: `manager@webrajya.in`
- **Accountant / Employee**: `employee@webrajya.in`
- **Client Portal**: `client@apexprecision.in`
