<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DomainSearchController extends Controller
{
    public function __construct(
        protected WhmcsApiService $whmcs
    ) {}

    /**
     * صفحة بحث النطاقات — جلب أسعار TLDs وعرض النموذج
     */
    public function index()
    {
        $currencyId = (int) config('whmcs.default_currency', 1);
        $pricingResult = $this->whmcs->getTldPricing($currencyId, null);
        $pricing = $pricingResult['success'] ? $pricingResult['pricing'] : [];
        $currency = $pricingResult['success'] ? $pricingResult['currency'] : [];

        return view('frontend.pages.domain-search', [
            'pricing' => $pricing,
            'currency' => $currency,
        ]);
    }

    /**
     * معالجة البحث: التحقق من التوفر وعرض السعر والخيارات
     */
    public function search(Request $request)
    {
        $rules = [
            'domain' => 'required|string|max:253',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('validation.required', ['attribute' => 'domain']), 'results' => []]);
            }
            return redirect()->route('frontend.domain-search')->withErrors($validator)->withInput();
        }

        $input = trim($request->input('domain', ''));
        $selectedTlds = $request->input('tlds', []);
        if (! is_array($selectedTlds)) {
            $selectedTlds = array_filter(explode(',', $selectedTlds));
        }
        $selectedTlds = array_map('strtolower', array_map('trim', $selectedTlds));
        $selectedTlds = array_values(array_filter($selectedTlds, fn ($t) => $t !== ''));

        $currencyId = (int) config('whmcs.default_currency', 1);
        $pricingResult = $this->whmcs->getTldPricing($currencyId, null);
        $pricing = $pricingResult['success'] ? $pricingResult['pricing'] : [];
        $currency = $pricingResult['success'] ? $pricingResult['currency'] : [];

        $domainsToCheck = [];
        if (str_contains($input, '.')) {
            $domainsToCheck[] = strtolower($input);
        } else {
            $name = preg_replace('/\s+/', '', $input);
            if ($name === '') {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'يرجى إدخال اسم أو نطاق صحيح.', 'results' => []]);
                }
                return redirect()->route('frontend.domain-search')->with('error', 'يرجى إدخال اسم أو نطاق صحيح.')->withInput();
            }
            $availableTlds = array_keys($pricing);
            $tldsToUse = ! empty($selectedTlds)
                ? array_intersect($selectedTlds, $availableTlds)
                : array_slice($availableTlds, 0, 12);
            if (empty($tldsToUse)) {
                $tldsToUse = ['com', 'net', 'org'];
            }
            foreach ($tldsToUse as $tld) {
                $domainsToCheck[] = $name . '.' . $tld;
            }
        }

        $results = [];
        foreach ($domainsToCheck as $domain) {
            $whoisResult = $this->whmcs->domainWhois($domain);
            $tld = substr(strrchr($domain, '.'), 1) ?: '';
            $tldPricing = $pricing[$tld] ?? [];
            $registerPrice = $tldPricing['register'] ?? [];
            $transferPrice = $tldPricing['transfer'] ?? [];
            $renewPrice = $tldPricing['renew'] ?? [];
            $addons = $tldPricing['addons'] ?? [];
            $currencySuffix = ($currency['suffix'] ?? '') ?: (' ' . ($currency['code'] ?? ''));
            $row = [
                'domain' => $domain,
                'available' => $whoisResult['status'] === 'available',
                'status' => $whoisResult['status'],
                'whois' => $whoisResult['whois'] ?? '',
                'register' => $registerPrice,
                'transfer' => $transferPrice,
                'renew' => $renewPrice,
                'addons' => $addons,
            ];
            $row['register_text'] = WhmcsApiService::formatDomainPrice($registerPrice, $currencySuffix);
            $row['transfer_text'] = WhmcsApiService::formatDomainPrice($transferPrice, $currencySuffix);
            $row['renew_text'] = WhmcsApiService::formatDomainPrice($renewPrice, $currencySuffix);
            $addonsParts = [];
            if (! empty($addons) && is_array($addons)) {
                foreach ($addons as $addonName => $addonVal) {
                    if ($addonVal !== false && $addonVal !== null && $addonVal !== '') {
                        $name = is_string($addonName) ? $addonName : 'إضافة';
                        $addonsParts[] = $name . ': ' . (is_array($addonVal) ? WhmcsApiService::formatDomainPrice($addonVal, $currencySuffix) : $addonVal . $currencySuffix);
                    }
                }
            }
            $row['addons_text'] = $addonsParts !== [] ? implode(' — ', $addonsParts) : '—';
            $results[] = $row;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'currency' => $currency,
                'results' => $results,
            ]);
        }

        return view('frontend.pages.domain-search', [
            'pricing' => $pricing,
            'currency' => $currency,
            'results' => $results,
            'searchTerm' => $input,
            'selectedTlds' => $selectedTlds,
        ]);
    }
}
