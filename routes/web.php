<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SharedLinkController;


Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', [
    ItemController::class,
    'dashboard'
])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/share/{token}', [SharedLinkController::class, 'show'])->name('shared.show');
// Route::get('/share', [SharedLinkController::class, 'index'])->name('shared.index');
Route::redirect('/share', '/');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    Route::get('/trash', [ItemController::class, 'trash'])->name('items.trash');
    Route::post('/restore/{id}', [ItemController::class, 'restore'])->name('items.restore');
    Route::post('/items/upload', [ItemController::class, 'upload'])->name('items.upload');

    Route::post('/share/{itemId}', [SharedLinkController::class, 'create'])->name('shared.create');
    Route::post('/items/bulk-delete', [ItemController::class, 'bulkDelete'])->name('items.bulkDelete');
    Route::post('/items/trash/bulk-action', [ItemController::class, 'bulkActionTrash'])->name('items.bulkActionTrash');
    Route::post('/items/{id}/rename', [ItemController::class, 'rename'])->name('items.rename');


});

require __DIR__ . '/auth.php';
