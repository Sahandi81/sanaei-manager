<?php

namespace App\Http\Controllers;


class PanelController extends Controller
{
	public function index()
	{
		return view('content.dashboard.dashboard');
    }
}
