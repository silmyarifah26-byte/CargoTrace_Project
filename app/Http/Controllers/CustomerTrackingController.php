<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTrackingController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $resi = trim((string) $request->query('resi', ''));
        $selectedId = $request->query('shipment_id');

        // Ambil semua shipment milik customer yang sedang login
        $shipments = Shipment::with(['carrier', 'originLocation', 'destinationLocation'])
            ->where('customer_id', $userId)
            ->latest()
            ->get();

        $activeShipment = null;

        if ($resi !== '') {
            // Cari berdasarkan shipment_number / tracking number milik customer ini saja
            $activeShipment = Shipment::with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'trackingEvents' => function ($query) {
                    $query->orderByDesc('occurred_at')->orderByDesc('id');
                },
                'exceptions' => function ($query) {
                    $query->latest();
                },
            ])
                ->where('customer_id', $userId)
                ->where(function ($query) use ($resi) {
                    $query->where('shipment_number', 'like', '%' . $resi . '%')
                          ->orWhere('reference_number', 'like', '%' . $resi . '%');
                })
                ->first();
        } elseif ($selectedId) {
            $activeShipment = Shipment::with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'trackingEvents' => function ($query) {
                    $query->orderByDesc('occurred_at')->orderByDesc('id');
                },
                'exceptions' => function ($query) {
                    $query->latest();
                },
            ])
                ->where('customer_id', $userId)
                ->find($selectedId);
        } else {
            // Default: tampilkan shipment pertama milik customer jika ada
            $firstShipment = $shipments->first();
            if ($firstShipment) {
                $activeShipment = Shipment::with([
                    'carrier',
                    'originLocation',
                    'destinationLocation',
                    'trackingEvents' => function ($query) {
                        $query->orderByDesc('occurred_at')->orderByDesc('id');
                    },
                    'exceptions' => function ($query) {
                        $query->latest();
                    },
                ])
                    ->where('customer_id', $userId)
                    ->find($firstShipment->id);
            }
        }

        $stages = [
            'CREATED'    => __('Created'),
            'BOOKED'     => __('Booked'),
            'PICKED_UP'  => __('Picked Up'),
            'IN_TRANSIT' => __('In Transit'),
            'DELIVERED'  => __('Delivered'),
        ];

        $currentStageIndex = $activeShipment
            ? array_search(strtoupper($activeShipment->current_status ?? 'CREATED'), array_keys($stages), true)
            : 0;

        if ($currentStageIndex === false) {
            $currentStageIndex = 0;
        }

        return view('customer.tracking', [
            'shipments'         => $shipments,
            'activeShipment'    => $activeShipment,
            'resi'              => $resi,
            'stages'            => $stages,
            'currentStageIndex' => $currentStageIndex,
        ]);
    }
}