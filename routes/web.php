<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProgressImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/dashboard');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::resource('students', StudentController::class)->except('show');
    Route::get('/students/import', [StudentProgressImportController::class, 'create'])->name('students.import.create');
    Route::post('/students/import', [StudentProgressImportController::class, 'store'])->name('students.import.store');
    Route::get('/students/import/template', [StudentProgressImportController::class, 'template'])->name('students.import.template');
    Route::post('certificate-templates/{certificateTemplate}/clone', [CertificateTemplateController::class, 'cloneTemplate'])->name('certificate-templates.clone');
    Route::resource('certificate-templates', CertificateTemplateController::class)->except('show');
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/issue', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates/issue', [CertificateController::class, 'store'])->name('certificates.store');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');
    Route::prefix('session')->name('session.')->group(function () {
        Route::get('/students', [SessionController::class, 'students'])->name('students');
        Route::get('/readings/{student}', [SessionController::class, 'readings'])->name('readings');
        Route::get('/readings/{student}/{qiraat}', [SessionController::class, 'narrations'])->name('narrations');
        Route::get('/mushaf/{student}/{qiraat}/both', [SessionController::class, 'mushafBoth'])->name('mushaf.both');
        Route::get('/mushaf/{student}/{narration}', [SessionController::class, 'mushaf'])->name('mushaf');
        Route::get('/mushaf/{student}/{narration}/page/{page}', [SessionController::class, 'page'])->name('mushaf.page');
        Route::post('/mushaf/{student}/{qiraat}/both/confirm', [SessionController::class, 'confirmBoth'])->name('confirm-both');
        Route::post('/mushaf/{student}/{qiraat}/both/finish', [SessionController::class, 'finishBoth'])->name('finish-both');
        Route::post('/{session}/confirm', [SessionController::class, 'confirm'])->name('confirm');
        Route::post('/{session}/finish', [SessionController::class, 'finish'])->name('finish');
    });
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::post('/analytics/report/telegram', [AnalyticsController::class, 'sendReport'])->name('analytics.report.telegram');
    Route::get('/analytics/students/{student}', [AnalyticsController::class, 'student'])->name('analytics.student');
});
