<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::query()->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%' . $request->string('search') . '%'))->latest()->paginate(10)->withQueryString();
        return view('students.index', compact('students'));
    }

    public function create(): View { return view('students.create'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']], ['name.required' => 'يرجى إدخال اسم الطالب.', 'name.max' => 'اسم الطالب طويل جدًا.']);
        Student::create($data);
        return redirect()->route('students.index')->with('success', 'تمت إضافة الطالب بنجاح.');
    }

    public function edit(Student $student): View { return view('students.edit', compact('student')); }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']], ['name.required' => 'يرجى إدخال اسم الطالب.', 'name.max' => 'اسم الطالب طويل جدًا.']);
        $student->update($data);
        return redirect()->route('students.index')->with('success', 'تم تحديث بيانات الطالب.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();
        return redirect()->route('students.index')->with('success', 'تم حذف الطالب.');
    }
}
