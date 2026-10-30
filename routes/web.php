<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect('/dashboard');
});

Volt::route('/dashboard', 'dashboard');
