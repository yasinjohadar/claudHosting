<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Domain\DomainAvailabilitySearchService;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DomainSearchController extends Controller
{
    public function __construct(
        protected WhmcsApiService $whmcs,
        protected DomainAvailabilitySearchService $availabilitySearch
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

        $results = $this->buildResults($domainsToCheck, $pricing, $currency, $input);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'currency' => $currency,
                'results' => $results,
                'html' => view('frontend.partials.domain-search-results', [
                    'results' => $results,
                    'currency' => $currency,
                    'searchTerm' => $input,
                ])->render(),
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

    /**
     * @param  array<int, string>  $domainsToCheck
     * @param  array<string, mixed>  $pricing
     * @param  array<string, mixed>  $currency
     * @return array<int, array<string, mixed>>
     */
    protected function buildResults(array $domainsToCheck, array $pricing, array $currency, string $searchInput): array
    {
        $currencySuffix = ($currency['suffix'] ?? '') ?: (' ' . ($currency['code'] ?? ''));
        $availabilityPayload = $this->availabilitySearch->search($searchInput);
        $availabilityByDomain = [];
        foreach ($availabilityPayload['rows'] as $availRow) {
            $availabilityByDomain[$availRow['domain'] ?? ''] = $availRow;
        }

        $results = [];

        foreach ($domainsToCheck as $domain) {
            $whoisResult = $this->whmcs->domainWhois($domain);
            $external = $availabilityByDomain[$domain] ?? null;
            $whoisOk = (bool) ($whoisResult['success'] ?? false);

            $available = false;
            $checkState = 'unknown';
            $sources = [];

            if ($whoisOk) {
                $available = ($whoisResult['status'] ?? '') === 'available';
                $checkState = $available ? 'available' : 'unavailable';
                $sources[] = 'WHMCS';
            } elseif ($external !== null) {
                $available = (bool) ($external['any_available'] ?? false);
                $checkState = $available ? 'available' : 'unavailable';
                if ($external['cloudflare'] ?? null) {
                    $sources[] = 'Cloudflare';
                }
                if ($external['namecom'] ?? null) {
                    $sources[] = 'name.com';
                }
            } else {
                $checkState = 'error';
            }

            $tld = substr(strrchr($domain, '.'), 1) ?: '';
            $tldPricing = $pricing[$tld] ?? [];
            $registerPrice = $tldPricing['register'] ?? [];
            $transferPrice = $tldPricing['transfer'] ?? [];
            $renewPrice = $tldPricing['renew'] ?? [];
            $addons = $tldPricing['addons'] ?? [];
            $row = [
                'domain' => $domain,
                'available' => $available,
                'check_state' => $checkState,
                'sources' => $sources,
                'status' => $whoisResult['status'] ?? 'unknown',
                'whois' => $whoisResult['whois'] ?? '',
                'register' => $registerPrice,
                'transfer' => $transferPrice,
                'renew' => $renewPrice,
                'addons' => $addons,
                'cloudflare' => $external['cloudflare'] ?? null,
                'namecom' => $external['namecom'] ?? null,
            ];
            $row['register_text'] = WhmcsApiService::formatDomainPrice($registerPrice, $currencySuffix);
            $row['transfer_text'] = WhmcsApiService::formatDomainPrice($transferPrice, $currencySuffix);
            $row['renew_text'] = WhmcsApiService::formatDomainPrice($renewPrice, $currencySuffix);
            $this->applyRegistrarPrices($row);

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

        if (str_contains($searchInput, '.') && count($results) === 1 && count($availabilityPayload['rows']) > 1) {
            $primaryDomain = $results[0]['domain'];
            foreach ($availabilityPayload['rows'] as $availRow) {
                $d = $availRow['domain'] ?? '';
                if ($d === '' || $d === $primaryDomain) {
                    continue;
                }
                if (collect($results)->contains(fn ($r) => ($r['domain'] ?? '') === $d)) {
                    continue;
                }
                $results[] = $this->rowFromAvailability($d, $availRow, $pricing, $currencySuffix);
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function applyRegistrarPrices(array &$row): void
    {
        if (($row['register_text'] ?? '—') !== '—' && ($row['register_text'] ?? '') !== '') {
            return;
        }
        $cf = $row['cloudflare'] ?? null;
        $nc = $row['namecom'] ?? null;
        if ($cf && ($cf['available'] ?? false) && isset($cf['price'])) {
            $row['register_text'] = '$' . number_format((float) $cf['price'], 2) . ' (Cloudflare)';
        } elseif ($nc && ($nc['available'] ?? false) && isset($nc['price'])) {
            $row['register_text'] = '$' . number_format((float) $nc['price'], 2) . ' (name.com)';
        }
        if (($row['renew_text'] ?? '—') === '—' || ($row['renew_text'] ?? '') === '') {
            if ($cf && isset($cf['renewal'])) {
                $row['renew_text'] = '$' . number_format((float) $cf['renewal'], 2);
            } elseif ($nc && isset($nc['renewal'])) {
                $row['renew_text'] = '$' . number_format((float) $nc['renewal'], 2);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $availRow
     * @return array<string, mixed>
     */
    protected function rowFromAvailability(string $domain, array $availRow, array $pricing, string $currencySuffix): array
    {
        $available = (bool) ($availRow['any_available'] ?? false);
        $sources = [];
        if ($availRow['cloudflare'] ?? null) {
            $sources[] = 'Cloudflare';
        }
        if ($availRow['namecom'] ?? null) {
            $sources[] = 'name.com';
        }
        $tld = substr(strrchr($domain, '.'), 1) ?: '';
        $tldPricing = $pricing[$tld] ?? [];
        $row = [
            'domain' => $domain,
            'available' => $available,
            'check_state' => $available ? 'available' : 'unavailable',
            'sources' => $sources,
            'status' => $available ? 'available' : 'unavailable',
            'whois' => '',
            'register' => $tldPricing['register'] ?? [],
            'transfer' => $tldPricing['transfer'] ?? [],
            'renew' => $tldPricing['renew'] ?? [],
            'addons' => $tldPricing['addons'] ?? [],
            'cloudflare' => $availRow['cloudflare'] ?? null,
            'namecom' => $availRow['namecom'] ?? null,
            'addons_text' => '—',
        ];
        $row['register_text'] = WhmcsApiService::formatDomainPrice($row['register'], $currencySuffix);
        $row['transfer_text'] = WhmcsApiService::formatDomainPrice($row['transfer'], $currencySuffix);
        $row['renew_text'] = WhmcsApiService::formatDomainPrice($row['renew'], $currencySuffix);
        $this->applyRegistrarPrices($row);

        return $row;
    }
}
