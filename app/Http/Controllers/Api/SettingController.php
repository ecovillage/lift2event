<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->settingsResponse(Setting::instance());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'map_center_lat'       => ['sometimes', 'numeric', 'between:-90,90'],
            'map_center_lng'       => ['sometimes', 'numeric', 'between:-180,180'],
            'map_zoom'             => ['sometimes', 'integer', 'between:1,20'],
            'footer_links'         => ['sometimes', 'nullable', 'array'],
            'footer_links.*.label' => ['required_with:footer_links.*', 'string', 'max:255'],
            'footer_links.*.url'           => ['required_with:footer_links.*', 'url', 'max:500'],
            'ride_data_retention_days'     => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'organisation_name'            => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $settings = Setting::instance();
        $settings->update($data);

        return $this->settingsResponse($settings->fresh());
    }

    private function settingsResponse(Setting $settings): JsonResponse
    {
        return response()->json(
            array_merge($settings->toArray(), ['organisation_name' => Setting::organisationName()])
        );
    }
}
