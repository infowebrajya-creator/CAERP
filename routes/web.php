<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\ComplianceDeadlineController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GstReturnController;
use App\Http\Controllers\ItrFilingController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\Portal\ClientPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TdsReturnController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\VendorLicenseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Licensing & Hardware Activation
Route::get('/license/activate', [LicenseController::class, 'showActivation'])->name('license.activate');
Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.submit');
Route::post('/license/deactivate', [LicenseController::class, 'deactivate'])->middleware('auth')->name('license.deactivate');
Route::get('/license/status', [LicenseController::class, 'status'])->name('license.status');
Route::get('/license/eula', [LicenseController::class, 'showEula'])->name('license.eula');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.read_all');
});

// Staff Practice Management Workspace
Route::middleware(['auth', 'role:super_admin,partner,manager,employee'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [DashboardController::class, 'search'])->name('global.search');

    // Clients CRM
    Route::resource('clients', ClientController::class);

    // GST Compliance
    Route::get('/gst', [GstReturnController::class, 'index'])->name('gst.index');
    Route::post('/gst', [GstReturnController::class, 'store'])->name('gst.store');
    Route::put('/gst/{gst}', [GstReturnController::class, 'update'])->name('gst.update');
    Route::delete('/gst/{gst}', [GstReturnController::class, 'destroy'])->name('gst.destroy');
    Route::post('/gst/return-types', [GstReturnController::class, 'storeReturnType'])->name('gst.return_types.store');

    // Income Tax Return (ITR)
    Route::get('/itr', [ItrFilingController::class, 'index'])->name('itr.index');
    Route::post('/itr', [ItrFilingController::class, 'store'])->name('itr.store');
    Route::put('/itr/{itr}', [ItrFilingController::class, 'update'])->name('itr.update');
    Route::delete('/itr/{itr}', [ItrFilingController::class, 'destroy'])->name('itr.destroy');

    // TDS Management
    Route::get('/tds', [TdsReturnController::class, 'index'])->name('tds.index');
    Route::post('/tds', [TdsReturnController::class, 'store'])->name('tds.store');
    Route::put('/tds/{td}', [TdsReturnController::class, 'update'])->name('tds.update');
    Route::delete('/tds/{td}', [TdsReturnController::class, 'destroy'])->name('tds.destroy');

    // Deadline & Compliance Calendar
    Route::get('/calendar', [ComplianceDeadlineController::class, 'index'])->name('calendar.index');
    Route::post('/calendar', [ComplianceDeadlineController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{deadline}', [ComplianceDeadlineController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{deadline}', [ComplianceDeadlineController::class, 'destroy'])->name('calendar.destroy');
    Route::post('/calendar/rules', [ComplianceDeadlineController::class, 'storeRule'])->name('calendar.rules.store');
    Route::post('/calendar/rules/{rule}/generate', [ComplianceDeadlineController::class, 'generateFromRule'])->name('calendar.rules.generate');

    // Client Document Management
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/{document}/upload', [DocumentController::class, 'uploadVersion'])->name('documents.upload');
    Route::post('/documents/{document}/approve', [DocumentController::class, 'approve'])->name('documents.approve');
    Route::post('/documents/{document}/reject', [DocumentController::class, 'reject'])->name('documents.reject');
    Route::get('/documents/{document}/download/{version?}', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Audit Management
    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
    Route::put('/audits/{audit}', [AuditController::class, 'update'])->name('audits.update');
    Route::delete('/audits/{audit}', [AuditController::class, 'destroy'])->name('audits.destroy');
    Route::post('/audits/{audit}/working-papers', [AuditController::class, 'uploadWorkingPaper'])->name('audits.papers.store');
    Route::get('/audits/{audit}/working-papers/{paper}/download', [AuditController::class, 'downloadWorkingPaper'])->name('audits.papers.download');

    // Billing & Payments
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{invoice}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/{invoice}/payments', [BillingController::class, 'recordPayment'])->name('billing.payments.store');
    Route::get('/billing/{invoice}/pdf', [BillingController::class, 'downloadPdf'])->name('billing.pdf');
    Route::delete('/billing/{invoice}', [BillingController::class, 'destroy'])->name('billing.destroy');

    // Employee & Staff Management
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{staff}/assign-clients', [StaffController::class, 'assignClients'])->name('staff.assign_clients');

    // Task & Workflow Management
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'addAttachment'])->name('tasks.attachments.store');
    Route::get('/tasks/{task}/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('tasks.attachments.download');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Client Communication
    Route::get('/communications', [CommunicationController::class, 'index'])->name('communications.index');
    Route::post('/communications', [CommunicationController::class, 'store'])->name('communications.store');

    // Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export_csv');

    // Firm Settings & Audit Logs (Super Admin / Partner)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // System Backups & Disaster Recovery (Super Admin / Partner)
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{filename}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::post('/backups/upload', [BackupController::class, 'upload'])->name('backups.upload');
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');

    // Software Updates & Releases (Super Admin / Partner)
    Route::post('/updates/check', [UpdateController::class, 'check'])->name('updates.check');
    Route::post('/updates/apply-patch', [UpdateController::class, 'applyPatch'])->name('updates.apply_patch');
});

// Super Admin / Vendor License Manager
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/licenses', [VendorLicenseController::class, 'index'])->name('licenses.index');
    Route::post('/licenses', [VendorLicenseController::class, 'store'])->name('licenses.store');
    Route::post('/licenses/{license}/renew-amc', [VendorLicenseController::class, 'renewAmc'])->name('licenses.renew_amc');
    Route::get('/licenses/export-csv', [VendorLicenseController::class, 'exportCsv'])->name('licenses.export_csv');
    Route::delete('/licenses/{license}', [VendorLicenseController::class, 'destroy'])->name('licenses.destroy');
});

// Isolated Client Portal
Route::prefix('portal')->name('portal.')->middleware(['auth', 'role:client'])->group(function () {
    Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [ClientPortalController::class, 'profile'])->name('profile');
    Route::get('/documents', [ClientPortalController::class, 'documents'])->name('documents');
    Route::post('/documents', [ClientPortalController::class, 'uploadDocument'])->name('documents.upload');
    Route::get('/documents/{document}/download', [ClientPortalController::class, 'downloadDocument'])->name('documents.download');
    Route::get('/filings', [ClientPortalController::class, 'filings'])->name('filings');
    Route::get('/invoices', [ClientPortalController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}/pdf', [ClientPortalController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
    Route::get('/messages', [ClientPortalController::class, 'messages'])->name('messages');
    Route::post('/messages', [ClientPortalController::class, 'sendMessage'])->name('messages.send');
});
