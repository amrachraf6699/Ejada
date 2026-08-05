<?php

namespace App\Http\Controllers;

use App\Services\StudentProgressImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentProgressImportController extends Controller
{
    public function create(): View { return view('students.import'); }

    public function store(Request $request, StudentProgressImportService $importer): RedirectResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);
        try { $result = $importer->import($data['file']); } catch (\Throwable $exception) { return back()->withInput()->withErrors(['file' => $exception->getMessage()]); }
        return redirect()->route('students.import.create')->with('import_result', $result);
    }

    public function template()
    {
        $sheet = (new Spreadsheet())->getActiveSheet(); $sheet->setRightToLeft(true);
        $sheet->fromArray([['اسم الطالب', 'القراءة', 'الرواية', 'رقم السورة', 'رقم الآية', 'رقم الصفحة'], ['طالب جديد', 'عاصم الكوفي', 'حفص', 1, 7, 1]]);
        foreach (range('A', 'F') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $path = storage_path('app/نماذج/نموذج_استيراد_تقدم_الطلاب.xlsx'); File::ensureDirectoryExists(dirname($path)); (new Xlsx($sheet->getParent()))->save($path);
        return response()->download($path, 'نموذج_استيراد_تقدم_الطلاب.xlsx')->deleteFileAfterSend();
    }
}
