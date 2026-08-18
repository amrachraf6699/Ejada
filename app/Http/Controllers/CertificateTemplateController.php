<?php

namespace App\Http\Controllers;

use App\Models\CertificateTemplate;
use App\Services\CertificateImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CertificateTemplateController extends Controller
{
    public function index(): View
    {
        return view('certificate-templates.index', ['templates' => CertificateTemplate::latest()->get()]);
    }

    public function create(): View
    {
        return view('certificate-templates.create');
    }

    public function store(Request $request, CertificateImageService $images): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'background' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'], 'canvas_json' => ['required', 'json'], 'width' => ['required', 'integer', 'min:1'], 'height' => ['required', 'integer', 'min:1']]);
        $canvas = json_decode($data['canvas_json'], true);
        abort_unless($this->hasStudentName($canvas), 422, 'يجب إضافة موضع اسم الطالب قبل حفظ القالب.');
        $background = $images->storeBackground($request->file('background'));
        CertificateTemplate::create(['name' => $data['name'], 'background_path' => $background['path'], 'width' => $background['width'], 'height' => $background['height'], 'canvas_json' => $canvas, 'is_active' => true]);

        return redirect()->route('certificate-templates.index')->with('success', 'تم حفظ قالب الشهادة بنجاح.');
    }

    public function edit(CertificateTemplate $certificateTemplate): View
    {
        return view('certificate-templates.edit', ['template' => $certificateTemplate, 'backgroundUrl' => Storage::disk('public')->url($certificateTemplate->background_path)]);
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate, CertificateImageService $images): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'], 'canvas_json' => ['required', 'json'], 'width' => ['required', 'integer', 'min:1'], 'height' => ['required', 'integer', 'min:1']]);
        $canvas = json_decode($data['canvas_json'], true);
        abort_unless($this->hasStudentName($canvas), 422, 'يجب إضافة موضع اسم الطالب قبل حفظ القالب.');
        $updates = ['name' => $data['name'], 'canvas_json' => $canvas];
        if ($request->hasFile('background')) {
            $background = $images->storeBackground($request->file('background'));
            $updates += ['background_path' => $background['path'], 'width' => $background['width'], 'height' => $background['height']];
            Storage::disk('public')->delete($certificateTemplate->background_path);
        }
        $certificateTemplate->update($updates);

        return redirect()->route('certificate-templates.index')->with('success', 'تم تحديث قالب الشهادة.');
    }

    public function cloneTemplate(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $disk = Storage::disk('public');
        $extension = pathinfo($certificateTemplate->background_path, PATHINFO_EXTENSION) ?: 'png';
        $backgroundPath = 'certificate-templates/'.Str::uuid().'.'.$extension;

        if (! $disk->copy($certificateTemplate->background_path, $backgroundPath)) {
            return back()->with('error', 'تعذر نسخ صورة خلفية القالب.');
        }

        $clone = CertificateTemplate::create([
            'name' => $data['name'],
            'background_path' => $backgroundPath,
            'width' => $certificateTemplate->width,
            'height' => $certificateTemplate->height,
            'canvas_json' => $certificateTemplate->canvas_json,
            'is_active' => $certificateTemplate->is_active,
        ]);

        return redirect()->route('certificate-templates.edit', $clone)->with('success', 'تم نسخ قالب الشهادة. يمكنك الآن تعديل النسخة الجديدة.');
    }

    public function destroy(CertificateTemplate $certificateTemplate, CertificateImageService $images): RedirectResponse
    {
        $images->delete($certificateTemplate->background_path);
        $certificateTemplate->delete();

        return back()->with('success', 'تم حذف قالب الشهادة.');
    }

    private function hasStudentName(array $canvas): bool
    {
        return collect($canvas['objects'] ?? [])->contains(fn (array $object) => data_get($object, 'data.role') === 'student_name');
    }
}
