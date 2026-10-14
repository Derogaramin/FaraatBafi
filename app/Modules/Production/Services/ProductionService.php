<?php
/**
 * سرویس تولید — چرخهٔ عمر سفارش بافت (Work Order)
 *
 * جریان: پیش‌نویس → تخصیص به بافنده (W) → دریافت مواد → بافت → تحویل به انبار
 *        → بازرسی کیفیت (QC) → ساخت کدهای اصالت (FB) + فیش دستمزد (PAY) → تسویه
 *
 * تمام گذارها با ماشین حالت WorkOrderStatus (enum) کنترل می‌شوند.
 */

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Marketplace\Models\AuthenticityCode;
use App\Modules\Members\Models\Weaver;
use App\Modules\Production\Models\InventoryMovement;
use App\Modules\Production\Models\Material;
use App\Modules\Production\Models\MaterialDistribution;
use App\Modules\Production\Models\QualityInspection;
use App\Modules\Production\Models\WorkOrder;
use Core\Auth;
use Core\CodeGenerator;
use Core\Database;
use Core\Events;
use Core\Jalali;
use Core\QR;
use RuntimeException;

final class ProductionService
{
    /** سقف کدهای اصالت در هر سفارش تولید (برای نمونه‌داده) */
    private const MAX_AUTHENTICITY_PER_WO = 25;

    /** ساخت سفارش تولید جدید (کد WO-سال-سری) */
    public static function create(array $data): WorkOrder
    {
        $variant = ProductVariant::find((int) $data['variant_id']);
        if ($variant === null) {
            throw new RuntimeException('واریانت محصول یافت نشد.');
        }
        $weaverId = ($data['weaver_id'] ?? null) ? (int) $data['weaver_id'] : null;
        $unitWage = (int) ($data['unit_wage'] ?? 0);
        if ($weaverId !== null && $unitWage === 0) {
            $weaver = Weaver::find($weaverId);
            $unitWage = (int) ($weaver->wage_rate ?? 0);
        }

        $wo = WorkOrder::create([
            'code'       => CodeGenerator::workOrderCode(Jalali::currentYear()),
            'variant_id' => (int) $data['variant_id'],
            'order_id'   => ($data['order_id'] ?? null) ? (int) $data['order_id'] : null,
            'weaver_id'  => $weaverId,
            'quantity'   => max(1, (int) ($data['quantity'] ?? 1)),
            'unit_wage'  => $unitWage,
            'status'     => $weaverId !== null ? 'assigned' : 'draft',
            'priority'   => $data['priority'] ?? 'normal',
            'due_date'   => ($data['due_date'] ?? null) ?: null,
            'assigned_at'=> $weaverId !== null ? date('Y-m-d H:i:s') : null,
            'note'       => $data['note'] ?? null,
        ]);

        if ($weaverId !== null) {
            Events::dispatch('workorder.assigned', ['work_order' => $wo, '_entity' => 'work_order', '_entity_id' => $wo->id()]);
        }
        return $wo;
    }

    /** تخصیص بافنده به سفارش تولید */
    public static function assign(WorkOrder $wo, int $weaverId): void
    {
        $weaver = Weaver::find($weaverId);
        if ($weaver === null || $weaver->status !== 'active') {
            throw new RuntimeException('بافندهٔ انتخابی فعال نیست.');
        }
        if (!$wo->canTransitionTo('assigned') && $wo->status !== 'assigned') {
            throw new RuntimeException('در وضعیت فعلی امکان تخصیص بافنده نیست.');
        }
        $wo->update([
            'weaver_id'  => $weaverId,
            'unit_wage'  => $wo->unit_wage ?: (int) $weaver->wage_rate,
        ]);
        if ($wo->status === 'draft') {
            $wo->transitionTo('assigned');
        }
        Events::dispatch('workorder.assigned', ['work_order' => $wo, '_entity' => 'work_order', '_entity_id' => $wo->id()]);
    }

    /**
     * گذار وضعیت با اعتبارسنجی ماشین حالت.
     * در تحویل: ورود محصول به انبار ثبت می‌شود.
     */
    public static function changeStatus(WorkOrder $wo, string $newStatus, string $by = 'admin'): void
    {
        if (!$wo->canTransitionTo($newStatus)) {
            throw new RuntimeException(
                'گذار از «' . $wo->statusLabel() . '» به «' . (\Core\Statuses::label('work_order', $newStatus)) . '» مجاز نیست.'
            );
        }

        Database::transaction(function () use ($wo, $newStatus): void {
            $wo->transitionTo($newStatus);

            if ($newStatus === 'delivered') {
                // ورود محصول بافته‌شده به انبار
                Database::update('UPDATE product_variants SET stock = stock + ? WHERE id = ?', [$wo->quantity, $wo->variant_id]);
                InventoryMovement::create([
                    'variant_id' => (int) $wo->variant_id,
                    'direction'  => 'in',
                    'quantity'   => (int) $wo->quantity,
                    'ref_type'   => 'delivery',
                    'ref_id'     => $wo->id(),
                    'user_id'    => Auth::id(),
                    'note'       => 'تحویل بافت — ' . $wo->code,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });

        Events::dispatch('workorder.' . $newStatus, ['work_order' => $wo, '_entity' => 'work_order', '_entity_id' => $wo->id()]);
        Events::dispatch('workorder.status_changed', ['work_order' => $wo, 'from' => $wo->status, 'to' => $newStatus, 'by' => $by]);

        // اگر به سفارش مشتری متصل است و همهٔ WOها تحویل/تأیید شده‌اند → سفارش «آمادهٔ ارسال»
        if (in_array($newStatus, ['delivered', 'qc_passed'], true) && $wo->order_id) {
            self::maybeMarkOrderReady((int) $wo->order_id);
        }
    }

    /** توزیع مادهٔ اولیه برای یک سفارش تولید */
    public static function distributeMaterial(WorkOrder $wo, int $materialId, float $qty, ?string $note = null): void
    {
        $material = Material::find($materialId);
        if ($material === null) {
            throw new RuntimeException('مادهٔ اولیه یافت نشد.');
        }
        if ($qty <= 0) {
            throw new RuntimeException('مقدار توزیع باید مثبت باشد.');
        }

        Database::transaction(function () use ($wo, $material, $qty, $note): void {
            $affected = Database::update(
                'UPDATE materials SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$qty, $material->id(), $qty]
            );
            if ($affected === 0) {
                throw new RuntimeException('موجودی انبار «' . $material->name_fa . '» کافی نیست.');
            }
            MaterialDistribution::create([
                'work_order_id'  => $wo->id(),
                'material_id'    => $material->id(),
                'quantity'       => $qty,
                'distributed_by' => Auth::id(),
                'distributed_at' => date('Y-m-d H:i:s'),
                'note'           => $note,
            ]);
            InventoryMovement::create([
                'material_id' => $material->id(),
                'direction'   => 'out',
                'quantity'    => $qty,
                'ref_type'    => 'distribution',
                'ref_id'      => $wo->id(),
                'user_id'     => Auth::id(),
                'note'        => 'توزیع برای ' . $wo->code,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        });
    }

    /** ثبت بازرسی کیفیت و اعمال نتیجه روی سفارش تولید */
    public static function inspect(WorkOrder $wo, string $result, ?int $defectTypeId, ?int $score, ?string $note): QualityInspection
    {
        if ($wo->status !== 'delivered') {
            throw new RuntimeException('فقط سفارش تحویل‌شده قابل بازرسی است.');
        }
        $inspection = QualityInspection::create([
            'code'           => CodeGenerator::qualityCode(Jalali::currentYear()),
            'work_order_id'  => $wo->id(),
            'inspector_id'   => Auth::id(),
            'result'         => $result,
            'defect_type_id' => $defectTypeId,
            'score'          => $score,
            'note'           => $note,
            'inspected_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($result === 'passed' || $result === 'conditional') {
            self::changeStatus($wo, 'qc_passed');
        } elseif ($result === 'rejected') {
            self::changeStatus($wo, 'qc_rejected');
        }
        return $inspection;
    }

    /**
     * صدور کدهای اصالت (FB-سال-سری-بافنده) پس از تأیید کیفیت.
     * هر کد به QR حاوی «آدرس صفحهٔ اصالت» تبدیل می‌شود (نه کد خام).
     *
     * @return string[] کدهای ساخته‌شده
     */
    public static function issueAuthenticityCodes(WorkOrder $wo): array
    {
        if ($wo->weaver_id === null) {
            return [];
        }
        $weaver = Weaver::find((int) $wo->weaver_id);
        if ($weaver === null) {
            return [];
        }
        // از قبل صادر شده‌اند؟
        $existing = Database::selectOne(
            'SELECT COUNT(*) AS c FROM authenticity_codes WHERE work_order_id = ?',
            [$wo->id()]
        );
        if ((int) ($existing['c'] ?? 0) > 0) {
            return [];
        }

        $count = min((int) $wo->quantity, self::MAX_AUTHENTICITY_PER_WO);
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = CodeGenerator::authenticityCode((string) $weaver->code, Jalali::currentYear());
            AuthenticityCode::create([
                'code'          => $code,
                'variant_id'    => (int) $wo->variant_id,
                'work_order_id' => $wo->id(),
                'weaver_id'     => (int) $wo->weaver_id,
                'qr_url'        => QR::authenticityUrl($code),
                'status'        => 'reserved',
            ]);
            $codes[] = $code;
        }
        return $codes;
    }

    /** تنظیم موجودی انبار مادهٔ اولیه (اصلاح دستی) — با ممیزی */
    public static function adjustMaterialStock(Material $material, float $newStock, ?string $note = null): void
    {
        $old = (float) $material->stock;
        $material->update(['stock' => $newStock]);
        \Core\Audit::log('material', (int) $material->id(), (string) $material->code, 'stock', (string) $old, (string) $newStock, $note);
        InventoryMovement::create([
            'material_id' => $material->id(),
            'direction'   => $newStock >= $old ? 'in' : 'out',
            'quantity'    => abs($newStock - $old),
            'ref_type'    => 'adjust',
            'ref_id'      => $material->id(),
            'user_id'     => Auth::id(),
            'note'        => $note ?: 'اصلاح دستی موجودی',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /** اگر همهٔ سفارش‌های تولیدِ یک سفارش مشتری به مرحلهٔ تحویل/تأیید رسیده‌اند → ready */
    private static function maybeMarkOrderReady(int $orderId): void
    {
        $row = Database::selectOne(
            'SELECT
                SUM(status IN ("delivered","qc_passed","completed")) AS done,
                COUNT(*) AS total
             FROM work_orders WHERE order_id = ? AND status != "cancelled"',
            [$orderId]
        );
        if (!$row || (int) $row['total'] === 0 || (int) $row['done'] < (int) $row['total']) {
            return;
        }
        $order = Database::selectOne('SELECT status FROM orders WHERE id = ?', [$orderId]);
        if ($order && in_array($order['status'], ['confirmed', 'in_production'], true)) {
            Database::update('UPDATE orders SET status = "ready", updated_at = NOW() WHERE id = ?', [$orderId]);
            Events::dispatch('order.ready', ['order_id' => $orderId]);
        }
    }
}
