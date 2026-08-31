<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'sidebar_bg',
        'sidebar_text',
        'sidebar_icon',
        'sidebar_active_text',
        'sidebar_active_bg',
        'sidebar_hover_bg',
        'navbar_bg',
        'navbar_text',
        'navbar_border',
        'primary_color',
        'logo_text',
        'invoice_institution_name',
        'invoice_institution_address',
        'invoice_institution_phone',
        'invoice_institution_email',
        'invoice_institution_website',
        'invoice_logo',
        'invoice_header_image',
        'content_bg',
        'content_text',
        'card_bg',
        'card_border',
        'website_name',
        'website_short_name',
        'website_tagline',
        'website_footer_text',
    ];

    public static function instance(): static
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create([]);
        }
        return $setting;
    }

    public function toCssVariables(): string
    {
        return "
            --sidebar-bg: {$this->sidebar_bg};
            --sidebar-text: {$this->sidebar_text};
            --sidebar-active-text: {$this->sidebar_active_text};
            --sidebar-active-bg: {$this->sidebar_active_bg};
            --sidebar-hover-bg: {$this->sidebar_hover_bg};
            --navbar-bg: {$this->navbar_bg};
            --navbar-text: {$this->navbar_text};
            --navbar-border: {$this->navbar_border};
            --primary: {$this->primary_color};
            --primary-dark: {$this->primary_color};
            --logo-text: {$this->logo_text};
        ";
    }
}
