<?php

namespace App\Providers;

use App\Models\AuctionSheetVerificationRequest;
use App\Models\CarFinanceRequest;
use App\Models\CarInspectionRequest;
use App\Models\CarOwnershipRequest;
use App\Models\CarRegistrationRequest;
use App\Models\Language;
use App\Models\SellForMeRequest;
use App\Models\Setting;
use App\Services\CachingService;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /*** Header File ***/
        // View::composer('layouts.topbar', static function ($view) {
        //     $languages = CachingService::getLanguages();
        //     // $defaultLanguage = CachingService::getDefaultLanguage();
        //     $settings = CachingService::getSystemSettings();

        //     // $currentLanguageCode = ::get('language');
        //     $defaultLanguage = Setting::where('name', 'default_language')->first();

        //     // $currentLanguageCode = Setting::where('name', 'default_language')->first();

        //     $currentLanguage = Language::where('code', Session::get('locale'))->first();

        //     Session::put('language', $defaultLanguage);

        //     $view->with([
        //         'languages' => $languages,
        //         'defaultLanguage' => $defaultLanguage,
        //         'currentLanguage' => $currentLanguage,
        //         'settings' => $settings
        //     ]);
        // });

      View::composer('layouts.topbar', function ($view) {
            $languages = CachingService::getLanguages();
            
            // Always get the most recent default from DB
            $defaultLangCode = Setting::where('name', 'default_language')->value('value') ?? 'en';
            $defaultLanguage = $languages->where('code', $defaultLangCode)->first();

            // If session is empty, use the database default
            $currentLocale = Session::get('locale', $defaultLangCode);
            $currentLanguage = $languages->where('code', $currentLocale)->first();

            $view->with([
                'languages'       => $languages,
                'defaultLanguage' => $defaultLanguage, // Now correctly shows the DB value
                'currentLanguage' => $currentLanguage,
                'settings'        => CachingService::getSystemSettings()
            ]);
        });




        View::composer('layouts.sidebar', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('company_logo');
            $pendingServices = [
                'car_inspection' => Schema::hasTable('car_inspection_requests')
                    && CarInspectionRequest::where('status', CarInspectionRequest::STATUS_PENDING)->exists(),
                'sell_for_me' => Schema::hasTable('sell_for_me_requests')
                    && SellForMeRequest::where('status', SellForMeRequest::STATUS_PENDING)->exists(),
                'auction_sheet_verification' => Schema::hasTable('auction_sheet_verification_requests')
                    && AuctionSheetVerificationRequest::where('status', AuctionSheetVerificationRequest::STATUS_PENDING)->exists(),
                'car_registration' => Schema::hasTable('car_registration_requests')
                    && CarRegistrationRequest::where('status', CarRegistrationRequest::STATUS_PENDING)->exists(),
                'car_ownership' => Schema::hasTable('car_ownership_requests')
                    && CarOwnershipRequest::where('status', CarOwnershipRequest::STATUS_PENDING)->exists(),
                'car_finance' => Schema::hasTable('car_finance_requests')
                    && CarFinanceRequest::where('status', CarFinanceRequest::STATUS_PENDING)->exists(),
            ];

            $view->with([
                'company_logo' => $settings ?? '',
                'pendingServices' => $pendingServices,
            ]);
        });

        View::composer('layouts.main', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('favicon_icon');
            $view->with('favicon', $settings ?? '');
            $view->with('lang', Session::get('language'));
        });

        View::composer('auth.login', static function (\Illuminate\View\View $view) {
            $favicon_icon = CachingService::getSystemSettings('favicon_icon');
            $company_logo = CachingService::getSystemSettings('company_logo');
            $login_image = CachingService::getSystemSettings('login_image');
            $view->with('company_logo', $company_logo ?? '');
            $view->with('favicon', $favicon_icon ?? '');
            $view->with('login_bg_image', $login_image ?? '');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
