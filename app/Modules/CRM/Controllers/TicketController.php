<?php
/**
 * کنترلر تیکت‌های کاربر جاری (همهٔ نقش‌ها)
 */

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Modules\CRM\Models\Ticket;
use App\Modules\CRM\Models\TicketReply;
use Core\Auth;
use Core\CodeGenerator;
use Core\Controller;
use Core\Database;
use Core\Events;
use Core\Jalali;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Statuses;

final class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('CRM::tickets', [
            'title'    => 'تیکت‌های پشتیبانی من',
            'tickets'  => Ticket::builder()->where('user_id', (int) Auth::id())->orderBy('id', 'DESC')->get(),
            'statuses' => Statuses::TICKET,
        ]);
    }

    public function create(Request $request): Response
    {
        $myOrders = Database::select('SELECT code FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 20', [Auth::id()]);
        return $this->view('CRM::ticket-form', [
            'title'    => 'تیکت جدید',
            'myOrders' => $myOrders,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'subject'    => 'required|min:5|max:200',
            'body'       => 'required|min:10|max:5000',
            'category'   => 'required|in:order,product,payment,complaint,other',
            'priority'   => 'in:low,normal,high,urgent',
            'order_code' => 'max:20',
        ], ['subject' => 'موضوع', 'body' => 'متن پیام']);

        $ticket = Ticket::create([
            'code'       => CodeGenerator::ticketCode(Jalali::currentYear()),
            'user_id'    => (int) Auth::id(),
            'subject'    => $data['subject'],
            'body'       => $data['body'],
            'category'   => $data['category'],
            'priority'   => $data['priority'] ?? 'normal',
            'status'     => 'open',
            'order_code' => ($data['order_code'] ?? '') !== '' ? strtoupper($data['order_code']) : null,
        ]);

        Session::flash('success', 'تیکت شما با کد «' . $ticket->code . '» ثبت شد.');
        return $this->redirect(url('my/tickets/' . $ticket->id()));
    }

    public function show(Request $request): Response
    {
        $ticket = Ticket::builder()->where('id', $request->int('id'))->where('user_id', (int) Auth::id())->first();
        if ($ticket === null) {
            throw new \Core\HttpException(404, 'تیکت یافت نشد.');
        }
        return $this->view('CRM::ticket-detail', [
            'title'   => 'تیکت ' . $ticket->code,
            'ticket'  => $ticket,
            'replies' => Database::select(
                'SELECT tr.*, u.name, u.code AS user_code FROM ticket_replies tr
                 JOIN users u ON u.id = tr.user_id WHERE tr.ticket_id = ? ORDER BY tr.id',
                [$ticket->id()]
            ),
            'mine'    => true,
        ]);
    }

    public function reply(Request $request): Response
    {
        $ticket = Ticket::builder()->where('id', $request->int('id'))->where('user_id', (int) Auth::id())->first();
        if ($ticket === null) {
            throw new \Core\HttpException(404);
        }
        $data = $this->validate($request->all(), [
            'body' => 'required|min:2|max:5000',
        ], ['body' => 'متن پاسخ']);

        TicketReply::create([
            'ticket_id'  => (int) $ticket->id(),
            'user_id'    => (int) Auth::id(),
            'body'       => $data['body'],
            'is_staff'   => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Database::update('UPDATE tickets SET status = "open", updated_at = NOW() WHERE id = ?', [$ticket->id()]);

        Session::flash('success', 'پاسخ شما ثبت شد.');
        return $this->redirect(url('my/tickets/' . $ticket->id()));
    }
}
