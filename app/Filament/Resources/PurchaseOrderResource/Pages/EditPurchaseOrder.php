<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Supplier;
use App\Models\InventoryItem;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /* ================= SUPPLIER ================= */
        if (!empty($data['supplier_id'])) {
            $supplier = Supplier::with('vatGroup')->find($data['supplier_id']);

            if ($supplier && $supplier->vatGroup) {
                $data['supplier_name']            = $supplier->name;
                $data['supplier_email']           = $supplier->email;
                $data['supplier_phone']           = $supplier->phone_1;
                $data['supplier_vat_group_id']    = $supplier->vatGroup->id;
                $data['supplier_vat_group_name']  = $supplier->vatGroup->vat_group_name;
                $data['supplier_vat_rate']        = $supplier->vatGroup->vat_rate;
            }
        }

        /* ================= ITEMS + VAT GROUP ================= */
        foreach ($data['items'] ?? [] as $index => $item) {

            if (!empty($item['inventory_item_id'])) {
                $inventoryItem = InventoryItem::with('vatGroup')
                    ->find($item['inventory_item_id']);

                if ($inventoryItem && $inventoryItem->vatGroup) {

                    // 🔒 snapshot (saved)
                    $data['items'][$index]['inventory_vat_group_id']
                        = $inventoryItem->vatGroup->id;

                    $data['items'][$index]['inventory_vat_rate']
                        = $inventoryItem->vatGroup->vat_rate;

                    // 🖥 display-only (NOT saved)
                    $data['items'][$index]['vat_group_name']
                        = $inventoryItem->vatGroup->vat_group_name;

                    $data['items'][$index]['vat_rate']
                        = $inventoryItem->vatGroup->vat_rate;
                }
            }
        }

        /* ================= SUMMARY ================= */
        $sub = $vat = $grand = 0;

        foreach ($data['items'] ?? [] as $item) {
            $sub   += (float) ($item['item_subtotal'] ?? 0);
            $vat   += (float) ($item['item_vat_amount'] ?? 0);
            $grand += (float) ($item['item_grand_total'] ?? 0);
        }

        $data['items_sub_total_sum']      = round($sub, 2);
        $data['items_vat_sum']            = round($vat, 2);
        $data['items_total_with_vat_sum'] = round($grand, 2);

        /* ================= FINAL VAT ================= */
        if (($data['vat_base'] ?? 'item_vat') === 'supplier_vat') {
            $rate = (float) ($data['supplier_vat_rate'] ?? 0);
            $svat = round(($sub * $rate) / 100, 2);

            $data['final_vat_amount'] = $svat;
            $data['final_grand_total'] = round($sub + $svat, 2);
        } else {
            $data['final_vat_amount'] = $vat;
            $data['final_grand_total'] = $grand;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', [
            'record' => $this->record->getKey(),
        ]);
    }
}
