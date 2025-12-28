<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    /**
     * Get common data for all pages
     */
    private function getCommonData(): array
    {
        $siteSettings = SiteSettings::getActive();
        $legalPages = LegalPage::where('is_active', true)->orderBy('order')->get();
        
        return [
            'siteSettings' => $siteSettings ? $siteSettings->toApiArray() : [
                'siteName' => 'PINLY',
                'metaTitle' => 'PINLY – Dijital Kod ve Oyun Satış Platformu',
                'metaDescription' => 'PUBG Mobile UC satın al. Güvenilir, hızlı ve uygun fiyatlı UC satış platformu.',
                'dailyBannerEnabled' => true,
                'dailyBannerTitle' => 'Bugüne Özel Fiyatlar',
                'dailyCountdownEnabled' => true,
                'dailyCountdownLabel' => 'Kampanya bitimine',
            ],
            'legalPages' => $legalPages->map(fn($p) => ['title' => $p->title, 'slug' => $p->slug])->toArray(),
        ];
    }

    /**
     * Home page
     */
    public function home()
    {
        return view('pages.home', $this->getCommonData());
    }

    /**
     * Legal page
     */
    public function legal(string $slug)
    {
        $page = LegalPage::where('slug', $slug)->where('is_active', true)->first();
        
        if (!$page) {
            abort(404);
        }
        
        return view('pages.legal', array_merge($this->getCommonData(), [
            'page' => $page->toApiArray(),
        ]));
    }

    /**
     * Account dashboard
     */
    public function account()
    {
        return view('account.dashboard', $this->getCommonData());
    }

    /**
     * Account orders
     */
    public function accountOrders()
    {
        return view('account.orders', $this->getCommonData());
    }

    /**
     * Account order detail
     */
    public function accountOrderDetail(string $orderId)
    {
        return view('account.order-detail', array_merge($this->getCommonData(), [
            'orderId' => $orderId,
        ]));
    }

    /**
     * Account profile
     */
    public function accountProfile()
    {
        return view('account.profile', $this->getCommonData());
    }

    /**
     * Account security
     */
    public function accountSecurity()
    {
        return view('account.security', $this->getCommonData());
    }

    /**
     * Support tickets list
     */
    public function supportTickets()
    {
        return view('account.support', $this->getCommonData());
    }

    /**
     * Support new ticket
     */
    public function supportNewTicket()
    {
        return view('account.support-new', $this->getCommonData());
    }

    /**
     * Support ticket detail
     */
    public function supportTicketDetail(string $ticketId)
    {
        return view('account.support-detail', array_merge($this->getCommonData(), [
            'ticketId' => $ticketId,
        ]));
    }

    /**
     * Payment success
     */
    public function paymentSuccess()
    {
        return view('pages.payment-success', $this->getCommonData());
    }

    /**
     * Payment failed
     */
    public function paymentFailed()
    {
        return view('pages.payment-failed', $this->getCommonData());
    }

    /**
     * Admin login page
     */
    public function adminLogin()
    {
        return view('admin.login', $this->getCommonData());
    }

    /**
     * Admin dashboard
     */
    public function adminDashboard()
    {
        return view('admin.dashboard', $this->getCommonData());
    }

    /**
     * Admin orders
     */
    public function adminOrders()
    {
        return view('admin.orders', $this->getCommonData());
    }

    /**
     * Admin products
     */
    public function adminProducts()
    {
        return view('admin.products', $this->getCommonData());
    }

    /**
     * Admin support
     */
    public function adminSupport()
    {
        return view('admin.support', $this->getCommonData());
    }

    /**
     * Admin settings
     */
    public function adminSettings()
    {
        return view('admin.settings', $this->getCommonData());
    }
}