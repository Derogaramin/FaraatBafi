<?php
/**
 * پیکربندی احراز هویت و ماتریس مجوزهای RBAC
 *
 * نقش‌ها: member(M) staff(S) weaver(W) agency(A) customer(C)
 * '*' یعنی همهٔ نقش‌های واردشده.
 */

declare(strict_types=1);

return [
    'session_key' => 'user_id',

    // نقش‌هایی که برای «عملیات حساس» (POSTهای مالی/حاکمیت/تنظیمات)
    // باید احراز هویت دو مرحله‌ای (TOTP) را فعال و در نشست تأیید کرده باشند.
    'require_2fa_for' => ['member'],

    // ماتریس مجوزها — کلید: نام مجوز، مقدار: نقش‌های مجاز
    'permissions' => [
        // داشبورد و مدیریت کل
        'admin.access'        => ['member', 'staff'],

        // محصولات و کاتالوگ
        'products.view'       => ['member', 'staff'],
        'products.manage'     => ['member', 'staff'],
        'catalog.manage'      => ['member', 'staff'], // انواع/مدل/رنگ/طرح

        // سفارش‌های فروش
        'orders.view'         => ['member', 'staff', 'agency'],
        'orders.manage'       => ['member', 'staff'],
        'orders.own'          => ['customer'],

        // تولید و بافندگان
        'production.view'     => ['member', 'staff'],
        'production.manage'   => ['member', 'staff'],
        'weavers.view'        => ['member', 'staff'],
        'weavers.manage'      => ['member', 'staff'],
        'weaver.own'          => ['weaver'],
        'quality.view'        => ['member', 'staff'],
        'quality.manage'      => ['member', 'staff'],

        // انبار
        'inventory.view'      => ['member', 'staff'],
        'inventory.manage'    => ['member', 'staff'],

        // مالی
        'finance.view'        => ['member'],
        'finance.manage'      => ['member'],
        'payroll.view'        => ['member', 'staff'],
        'payroll.manage'      => ['member'],
        'weaver.payroll.own'  => ['weaver'],

        // اعضا و منابع انسانی
        'members.view'        => ['member'],
        'members.manage'      => ['member'],
        'hr.view'             => ['member', 'staff'],
        'hr.manage'           => ['member', 'staff'],
        'customers.view'      => ['member', 'staff'],
        'customers.manage'    => ['member', 'staff'],

        // نمایندگی‌ها
        'agencies.view'       => ['member', 'staff'],
        'agencies.manage'     => ['member'],
        'agency.own'          => ['agency'],

        // محتوا
        'blog.view'           => ['member', 'staff'],
        'blog.manage'         => ['member', 'staff'],

        // پشتیبانی (CRM)
        'tickets.view'        => ['member', 'staff'],
        'tickets.manage'      => ['member', 'staff'],
        'tickets.own'         => ['customer', 'weaver', 'agency', 'member', 'staff'],

        // حاکمیت (فاز ۳)
        'governance.view'     => ['member'],
        'governance.manage'   => ['member'],
        'voting.participate'  => ['member'],

        // هوش تجاری
        'bi.view'             => ['member', 'staff'],
    ],
];
