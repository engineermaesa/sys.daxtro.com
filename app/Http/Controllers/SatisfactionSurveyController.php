<?php

namespace App\Http\Controllers;

class SatisfactionSurveyController extends Controller
{
    // Halaman hanya shell; data diambil lewat API /api/aftersales/satisfaction-surveys.
    public function index()
    {
        return view('pages.satisfaction-survey.index');
    }
}
