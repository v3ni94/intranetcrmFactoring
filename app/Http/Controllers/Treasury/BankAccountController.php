<?php

namespace App\Http\Controllers\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::withCount('transactions')->get();

        return view('treasury.bank-accounts.index', compact('accounts'));
    }
}
