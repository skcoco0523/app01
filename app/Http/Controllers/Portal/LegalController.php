<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    /**
     * プライバシーポリシー表示
     */
    public function privacy()
    {
        return view('portal.legal.privacy');
    }

    /**
     * 利用規約表示
     */
    public function terms()
    {
        return view('portal.legal.terms');
    }

    /**
     * 特定商取引法に基づく表記表示
     */
    public function tokushoho()
    {
        return view('portal.legal.tokushoho');
    }

    /**
     * 運営者情報・サービス概要表示
     */
    public function about()
    {
        return view('portal.legal.about');
    }
}