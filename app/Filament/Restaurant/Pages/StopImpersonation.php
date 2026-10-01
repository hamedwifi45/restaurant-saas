<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StopImpersonation extends Page
{
    protected string $view = 'filament.pages.stop-impersonation';

    protected static ?string $title = 'إنهاء تسجيل الدخول بالنيابة';

    protected static ?string $navigationLabel = 'العودة إلى الإدارة';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'restaurant_admin'
            && session()->has('super_admin_impersonator_id');
    }

    public function stopImpersonating()
    {
        $superAdmin = User::query()
            ->whereKey(session()->pull('super_admin_impersonator_id'))
            ->where('role', 'super_admin')
            ->first();

        if (! $superAdmin) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/sham/login');
        }

        Auth::login($superAdmin);
        request()->session()->regenerate();

        Notification::make()->title('تمت العودة إلى لوحة الإدارة')->success()->send();

        return redirect('/sham');
    }
}
