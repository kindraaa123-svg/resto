<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $system = [
            'name' => config('app.name'),
            'logo' => null,
        ];

        try {
            $sysData = DB::table('system')->first();
            if ($sysData) {
                $system['name'] = $sysData->systemname ?? $system['name'];
                $system['logo'] = $sysData->systemlogo ?? null;
            }
        } catch (\Exception $e) {
            // Ignore DB errors
        }

        return [
            ...parent::share($request),
            'flash' => [
                'message' => $request->hasSession() ? $request->session()->get('message') : null,
                'success' => $request->hasSession() ? $request->session()->get('success') : null,
                'error' => $request->hasSession() ? $request->session()->get('error') : null,
            ],
            'system' => $system,
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phonenumber' => $request->user()->phonenumber,
                    'branchid' => $request->user()->branchid,
                    'is_superadmin' => method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Superadmin'),
                    'permissions' => method_exists($request->user(), 'getAllPermissions')
                        ? $request->user()->getAllPermissions()->pluck('name')
                        : [],
                ] : null,
            ],
            'locale' => app()->getLocale(),
            'translations' => [
                'ui' => __('ui'),
            ],
            'ziggy' => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),
        ];
    }
}
