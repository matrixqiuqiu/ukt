<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SemesterAktifController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.tahun-akademik.index');
    }

    public function update(Request $request)
    {
        return redirect()->route('admin.tahun-akademik.index');
    }
}
