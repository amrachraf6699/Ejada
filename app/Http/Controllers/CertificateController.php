<?php

namespace App\Http\Controllers;

use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Models\Student;
use App\Services\CertificateImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(): View
    {
        return view('certificates.index', ['certificates' => IssuedCertificate::with('student', 'template')->latest('issued_at')->paginate(12)]);
    }

    public function create(): View
    {
        return view('certificates.create', ['templates' => CertificateTemplate::where('is_active', true)->latest()->get(), 'students' => Student::orderBy('name')->get()]);
    }

    public function store(Request $request, CertificateImageService $images): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['certificate_template_id' => ['required', 'exists:certificate_templates,id'], 'student_id' => ['required', 'exists:students,id'], 'image' => ['required', 'string', 'max:25000000']]);
        $student = Student::findOrFail($data['student_id']);
        $path = $images->storeIssuedPng($data['image']);
        $certificate = IssuedCertificate::create(['certificate_template_id' => $data['certificate_template_id'], 'student_id' => $student->id, 'issued_by' => $request->user()->id, 'student_name_snapshot' => $student->name, 'image_path' => $path, 'metadata_json' => ['source' => 'fabric.js'], 'issued_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['download_url' => route('certificates.download', $certificate)]);
        }
        return redirect()->route('certificates.index')->with('success', 'تم إصدار الشهادة وحفظها بنجاح.');
    }

    public function download(IssuedCertificate $certificate)
    {
        abort_unless(Storage::disk('public')->exists($certificate->image_path), 404);
        return Storage::disk('public')->download($certificate->image_path, 'certificate-' . $certificate->id . '.png');
    }

    public function destroy(IssuedCertificate $certificate, CertificateImageService $images): RedirectResponse
    {
        $images->delete($certificate->image_path);
        $certificate->delete();
        return back()->with('success', 'تم حذف الشهادة الصادرة.');
    }
}
