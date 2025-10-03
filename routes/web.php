<?php

use Illuminate\Support\Facades\Route;

// Home
Route::get('/', function () {
    return view('pages.home');
})->name('home');

// Static pages
Route::view('/about', 'pages.about')->name('about');
Route::view('/dining', 'pages.dining')->name('dining');
Route::view('/gallery', 'pages.gallery')->name('gallery');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/booking', 'pages.booking')->name('booking');
Route::view('/privacy', 'pages.privacy')->name('privacy');

// Experiences
Route::prefix('experiences')->name('experiences.')->group(function () {
    Route::view('/', 'pages.experiences.index')->name('index');
    Route::view('/cultural-encounters', 'pages.experiences.cultural')->name('cultural');
    Route::view('/guided-bush-walks', 'pages.experiences.walks')->name('walks');
    Route::view('/hot-air-balloon-safari', 'pages.experiences.balloon')->name('balloon');
    Route::view('/panoramic-game-drives', 'pages.experiences.drives')->name('drives');
    Route::view('/wellbeing', 'pages.experiences.wellbeing')->name('wellbeing');
});
